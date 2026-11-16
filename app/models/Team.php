<?php

class Team extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM teams ORDER BY id ASC");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    /**
     * Fetch all teams along with their assigned members and team leader
     */
    public function getAllWithMembers()
    {
        $teams = $this->getAll();
        if (empty($teams)) {
            return [];
        }

        $sql = "SELECT e.id, e.employee_id, e.full_name, e.team_id, e.user_id, e.is_team_leader 
                FROM employees e 
                WHERE e.team_id IS NOT NULL AND e.team_id > 0
                  AND (e.employee_status IS NULL OR e.employee_status != 'resigned')
                ORDER BY e.is_team_leader DESC, e.full_name ASC";
        $res = $this->db->query($sql);
        $membersByTeam = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $membersByTeam[$row['team_id']][] = $row;
            }
        }

        foreach ($teams as &$t) {
            $t['members'] = $membersByTeam[$t['id']] ?? [];
            $t['member_count'] = count($t['members']);
            $t['member_ids'] = array_map('intval', array_column($t['members'], 'id'));

            // Identify current team leader
            $leader = null;
            foreach ($t['members'] as $m) {
                if ((int)($m['is_team_leader'] ?? 0) === 1) {
                    $leader = $m;
                    break;
                }
            }
            $t['leader'] = $leader;
            $t['leader_id'] = $leader ? (int)$leader['id'] : 0;
            $t['leader_name'] = $leader ? $leader['full_name'] : null;
            $t['manager_user_id'] = 0;
            $t['manager_name'] = null;
            $t['manager_assigned_at'] = null;
        }
        unset($t);

        // Attach active Sales Manager per team (if pivot table exists)
        if ($this->hasSalesManagerTeamsTable()) {
            $mSql = "SELECT smt.team_id, smt.manager_user_id, smt.assigned_at, u.full_name AS manager_name
                     FROM sales_manager_teams smt
                     INNER JOIN users u ON u.id = smt.manager_user_id
                     WHERE smt.is_active = 1
                     ORDER BY smt.id DESC";
            $mRes = $this->db->query($mSql);
            if ($mRes) {
                $managersByTeam = [];
                while ($mRow = $mRes->fetch_assoc()) {
                    $tid = (int)$mRow['team_id'];
                    if (!isset($managersByTeam[$tid])) {
                        $managersByTeam[$tid] = $mRow;
                    }
                }
                foreach ($teams as &$t) {
                    $tid = (int)$t['id'];
                    if (isset($managersByTeam[$tid])) {
                        $t['manager_user_id'] = (int)$managersByTeam[$tid]['manager_user_id'];
                        $t['manager_name'] = $managersByTeam[$tid]['manager_name'];
                        $t['manager_assigned_at'] = $managersByTeam[$tid]['assigned_at'];
                    }
                }
                unset($t);
            }
        }

        return $teams;
    }

    /**
     * Check if sales_manager_teams pivot table exists (safe for pre-migration live code).
     */
    public function hasSalesManagerTeamsTable(): bool
    {
        static $exists = null;
        if ($exists === null) {
            $res = @$this->db->query("SHOW TABLES LIKE 'sales_manager_teams'");
            $exists = ($res && $res->num_rows > 0);
        }
        return $exists;
    }

    /**
     * Fetch active users who hold the new Sales Manager role.
     */
    public function getSalesManagers(): array
    {
        $sql = "SELECT u.id, u.full_name, u.phone, e.employee_id AS employee_code
                FROM users u
                LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
                LEFT JOIN roles r ON r.id = u.role_id
                LEFT JOIN roles sr ON sr.id = u.secondary_role_id
                WHERE (
                    (LOWER(TRIM(r.name)) = 'sales manager' AND r.id != 5)
                    OR
                    (LOWER(TRIM(sr.name)) = 'sales manager' AND sr.id != 5)
                )
                  AND (e.employee_status IS NULL OR (e.deleted_at IS NULL AND LOWER(TRIM(e.employee_status)) NOT IN ('resigned', 'inactive', 'terminated')))
                  AND (e.employee_type IS NULL OR LOWER(TRIM(e.employee_type)) NOT IN ('resigned', 'inactive', 'terminated'))
                ORDER BY u.full_name ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Assign or change the Sales Manager for a team while preserving full history.
     */
    public function syncTeamManager(int $teamId, int $managerUserId, ?int $assignedBy = null, ?string $note = null): bool
    {
        if ($teamId <= 0 || !$this->hasSalesManagerTeamsTable()) {
            return false;
        }

        // Check current active manager
        $currentManagerId = 0;
        $chk = $this->db->prepare("SELECT manager_user_id FROM sales_manager_teams WHERE team_id = ? AND is_active = 1 ORDER BY id DESC LIMIT 1");
        if ($chk) {
            $chk->bind_param("i", $teamId);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            $currentManagerId = (int)($row['manager_user_id'] ?? 0);
            $chk->close();
        }

        if ($currentManagerId === $managerUserId) {
            return true; // No change
        }

        $this->db->begin_transaction();
        try {
            // Deactivate previous active assignment(s) for this team
            $deact = $this->db->prepare(
                "UPDATE sales_manager_teams
                 SET is_active = 0, unassigned_by = ?, unassigned_at = NOW()
                 WHERE team_id = ? AND is_active = 1"
            );
            if ($deact) {
                $deact->bind_param("ii", $assignedBy, $teamId);
                $deact->execute();
                $deact->close();
            }

            // Insert new active assignment if a manager was selected
            if ($managerUserId > 0) {
                $ins = $this->db->prepare(
                    "INSERT INTO sales_manager_teams (manager_user_id, team_id, is_active, assigned_by, assigned_at, note)
                     VALUES (?, ?, 1, ?, NOW(), ?)"
                );
                if ($ins) {
                    $ins->bind_param("iiis", $managerUserId, $teamId, $assignedBy, $note);
                    $ins->execute();
                    $ins->close();
                }
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Fetch full Sales Manager assignment history for a team.
     */
    public function getTeamManagerHistory(int $teamId): array
    {
        if ($teamId <= 0 || !$this->hasSalesManagerTeamsTable()) {
            return [];
        }

        $sql = "SELECT smt.id, smt.manager_user_id, smt.team_id, smt.is_active,
                       smt.assigned_at, smt.unassigned_at, smt.note,
                       u_mgr.full_name AS manager_name,
                       u_by.full_name AS assigned_by_name,
                       u_un.full_name AS unassigned_by_name
                FROM sales_manager_teams smt
                LEFT JOIN users u_mgr ON u_mgr.id = smt.manager_user_id
                LEFT JOIN users u_by ON u_by.id = smt.assigned_by
                LEFT JOIN users u_un ON u_un.id = smt.unassigned_by
                WHERE smt.team_id = ?
                ORDER BY smt.is_active DESC, smt.assigned_at DESC, smt.id DESC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $teamId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    /**
     * Fetch sales department employees for assignment
     */
    public function getSalesEmployees()
    {
        $sql = "SELECT e.id, e.employee_id, e.full_name, e.team_id, e.user_id, e.is_team_leader, t.name as current_team_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN teams t ON e.team_id = t.id
                WHERE (e.department_id = 1 OR LOWER(d.name) LIKE '%sales%' OR (e.team_id IS NOT NULL AND e.team_id > 0))
                  AND (e.employee_status IS NULL OR e.employee_status != 'resigned')
                ORDER BY e.full_name ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Sync team members: updates employees.team_id, employees.is_team_leader and users.team_id
     */
    public function syncTeamMembers($teamId, array $selectedEmployeeIds, $leaderId = 0)
    {
        $teamId = (int)$teamId;
        if ($teamId <= 0) {
            return false;
        }

        $leaderId = (int)$leaderId;
        $selectedEmployeeIds = array_values(array_filter(array_map('intval', $selectedEmployeeIds)));

        // If a leader was chosen, ensure leader is included in team members list
        if ($leaderId > 0 && !in_array($leaderId, $selectedEmployeeIds)) {
            $selectedEmployeeIds[] = $leaderId;
        }

        // 1. Fetch current members assigned to this team
        $stmt = $this->db->prepare("SELECT id, user_id, is_team_leader FROM employees WHERE team_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $teamId);
            $stmt->execute();
            $currentRes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        } else {
            $currentRes = [];
        }

        $currentEmpIds = array_map('intval', array_column($currentRes, 'id'));

        // 2. Identify employees removed from this team
        $toRemove = array_diff($currentEmpIds, $selectedEmployeeIds);
        if (!empty($toRemove)) {
            $inRemove = implode(',', $toRemove);
            // Reset team_id and is_team_leader in employees
            $this->db->query("UPDATE employees SET team_id = 0, is_team_leader = 0 WHERE id IN ($inRemove)");

            // Reset team_id in users (linked via user_id)
            $this->db->query("UPDATE users u 
                              INNER JOIN employees e ON e.user_id = u.id 
                              SET u.team_id = 0 
                              WHERE e.id IN ($inRemove)");

            // Reset team_id in users (linked via employee_id)
            $this->db->query("UPDATE users SET team_id = 0 WHERE employee_id IN ($inRemove)");

            // Reset active/unbooked leads belonging to this specific team for removed employees
            $this->db->query("UPDATE leads l 
                              INNER JOIN employees e ON (l.assigned_id = e.user_id OR (e.user_id IS NULL AND l.assigned_id = e.id))
                              SET l.team_id = 0 
                              WHERE e.id IN ($inRemove) 
                                AND l.team_id = $teamId
                                AND l.deleted_at IS NULL
                                AND (l.last_lead_action IS NULL OR LOWER(l.last_lead_action) != 'booked')
                                AND NOT EXISTS (
                                    SELECT 1 FROM lead_bookings lb 
                                    WHERE lb.lead_id = l.id 
                                      AND lb.deleted_at IS NULL 
                                      AND lb.booking_status IN ('booked', 'completed')
                                )");
        }

        // 3. Assign selected employees to this team
        if (!empty($selectedEmployeeIds)) {
            $inSelect = implode(',', $selectedEmployeeIds);
            // Update employees table
            $this->db->query("UPDATE employees SET team_id = $teamId WHERE id IN ($inSelect)");

            // Update users table (linked via user_id)
            $this->db->query("UPDATE users u 
                              INNER JOIN employees e ON e.user_id = u.id 
                              SET u.team_id = $teamId 
                              WHERE e.id IN ($inSelect)");

            // Update users table (linked via employee_id)
            $this->db->query("UPDATE users SET team_id = $teamId WHERE employee_id IN ($inSelect)");

            // Automatically sync active/unbooked leads assigned to these users to the new team_id
            (new Lead($this->db))->syncActiveLeadsTeamByEmployeeIds($selectedEmployeeIds, $teamId);
        }

        // 4. Update is_team_leader in employees table for this team
        // Reset all members of this team to is_team_leader = 0
        $this->db->query("UPDATE employees SET is_team_leader = 0 WHERE team_id = $teamId");

        // Set the chosen leader to is_team_leader = 1
        if ($leaderId > 0) {
            $this->db->query("UPDATE employees SET is_team_leader = 1 WHERE id = $leaderId AND team_id = $teamId");
        }

        return true;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO teams (name, status, created_by) VALUES (?, ?, ?)"
        );
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->bind_param("sii", $data['name'], $data['status'], $data['created_by']);
        if ($stmt->execute()) {
            $insertId = $this->db->insert_id;
            $stmt->close();
            return $insertId;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function update($data)
    {
        $stmt = $this->db->prepare(
            "UPDATE teams SET name = ?, status = ? WHERE id = ?"
        );
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->bind_param("sii", $data['name'], $data['status'], $data['id']);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function delete($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return false;
        }

        // Reset team_id and is_team_leader for members before deleting team
        $this->db->query("UPDATE employees SET team_id = 0, is_team_leader = 0 WHERE team_id = $id");
        $this->db->query("UPDATE users SET team_id = 0 WHERE team_id = $id");

        $stmt = $this->db->prepare("DELETE FROM teams WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }
}
