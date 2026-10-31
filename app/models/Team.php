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
        }
        unset($t);

        return $teams;
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
