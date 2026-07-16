<?php

class ErpTracker extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getActiveSession()
    {
        $sql = "SELECT * FROM erp_tracker_sessions WHERE status = 'Open' ORDER BY id DESC LIMIT 1";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_assoc() : null;
    }

    public function getAllSessions()
    {
        $sql = "SELECT s.*, 
                (SELECT COUNT(DISTINCT department_id) FROM erp_tracker_submissions WHERE session_id = s.id) as submitted_departments
                FROM erp_tracker_sessions s ORDER BY s.id DESC";
        $res = $this->db->query($sql);
        $sessions = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $sessions[] = $row;
            }
        }
        return $sessions;
    }

    public function getSession($id)
    {
        $sql = "SELECT * FROM erp_tracker_sessions WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function createSession($meeting_date, $topic, $created_by)
    {
        // Check if a session already exists for this week
        $year_week = date('Y-W', strtotime($meeting_date));
        $sqlCheck = "SELECT id FROM erp_tracker_sessions WHERE DATE_FORMAT(meeting_date, '%Y-%v') = ?";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->bind_param("s", $year_week);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        if ($resCheck->num_rows > 0) {
            return false; // Session for this week already exists
        }

        // Close any currently open sessions
        $this->db->query("UPDATE erp_tracker_sessions SET status = 'Closed' WHERE status = 'Open'");

        $sql = "INSERT INTO erp_tracker_sessions (meeting_date, topic, status, created_by) VALUES (?, ?, 'Open', ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $meeting_date, $topic, $created_by);
        if ($stmt->execute()) {
            $new_session_id = $stmt->insert_id;
            
            // Carry-forward logic: Pull pending tasks from previous session
            $this->carryForwardTasks($new_session_id);
            
            return $new_session_id;
        }
        return false;
    }
    
    public function closeSession($id)
    {
        $sql = "UPDATE erp_tracker_sessions SET status = 'Closed' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    public function deleteSession($id)
    {
        $stmt1 = $this->db->prepare("DELETE FROM erp_tracker_actions WHERE session_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        
        $stmt2 = $this->db->prepare("DELETE FROM erp_tracker_submissions WHERE session_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        
        $stmt3 = $this->db->prepare("DELETE FROM erp_tracker_sessions WHERE id = ?");
        $stmt3->bind_param("i", $id);
        return $stmt3->execute();
    }

    private function carryForwardTasks($new_session_id)
    {
        // Get the latest closed session (previous session)
        $sqlPrev = "SELECT id FROM erp_tracker_sessions WHERE id < ? ORDER BY id DESC LIMIT 1";
        $stmtPrev = $this->db->prepare($sqlPrev);
        $stmtPrev->bind_param("i", $new_session_id);
        $stmtPrev->execute();
        $resPrev = $stmtPrev->get_result();
        if ($resPrev->num_rows > 0) {
            $prev_session = $resPrev->fetch_assoc();
            $prev_session_id = $prev_session['id'];

            // Get pending/overdue actions from prev session
            $sqlTasks = "SELECT * FROM erp_tracker_actions WHERE session_id = ? AND status IN ('Pending', 'Overdue')";
            $stmtTasks = $this->db->prepare($sqlTasks);
            $stmtTasks->bind_param("i", $prev_session_id);
            $stmtTasks->execute();
            $tasks = $stmtTasks->get_result();

            // Insert into new session
            $sqlInsert = "INSERT INTO erp_tracker_actions (session_id, department_id, action_name, owner_user_id, priority, deadline, status, original_action_id) 
                          VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)";
            $stmtInsert = $this->db->prepare($sqlInsert);

            while ($task = $tasks->fetch_assoc()) {
                $original_id = $task['original_action_id'] ? $task['original_action_id'] : $task['id'];
                $stmtInsert->bind_param("iisisss", $new_session_id, $task['department_id'], $task['action_name'], $task['owner_user_id'], $task['priority'], $task['deadline'], $original_id);
                $stmtInsert->execute();
            }
        }
    }

    public function getSubmission($session_id, $department_id)
    {
        $sql = "SELECT * FROM erp_tracker_submissions WHERE session_id = ? AND department_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $session_id, $department_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getActions($session_id, $department_id = null)
    {
        $sql = "SELECT a.*, u.full_name as owner_name 
                FROM erp_tracker_actions a
                LEFT JOIN users u ON a.owner_user_id = u.id
                WHERE a.session_id = ?";
        if ($department_id) {
            $sql .= " AND a.department_id = ?";
        }
        $stmt = $this->db->prepare($sql);
        if ($department_id) {
            $stmt->bind_param("ii", $session_id, $department_id);
        } else {
            $stmt->bind_param("i", $session_id);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $actions = [];
        while ($row = $res->fetch_assoc()) {
            $actions[] = $row;
        }
        return $actions;
    }
    
    public function saveSubmission($data)
    {
        // Insert submission if not exists, else update
        $existing = $this->getSubmission($data['session_id'], $data['department_id']);
        if ($existing) {
            $sql = "UPDATE erp_tracker_submissions SET 
                    champion_user_id = ?, paperless_current = ?, paperless_target = ?, paperless_deadline = ?,
                    issues_desc = ?, issues_priority = ?, it_support_req = ?, it_support_desc = ?, feature_req = ?, status = 'Submitted'
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("isssssissi", 
                $data['champion_user_id'], $data['paperless_current'], $data['paperless_target'], $data['paperless_deadline'],
                $data['issues_desc'], $data['issues_priority'], $data['it_support_req'], $data['it_support_desc'], $data['feature_req'],
                $existing['id']);
            $stmt->execute();
            return $existing['id'];
        } else {
            $sql = "INSERT INTO erp_tracker_submissions 
                    (session_id, department_id, champion_user_id, paperless_current, paperless_target, paperless_deadline, issues_desc, issues_priority, it_support_req, it_support_desc, feature_req, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Submitted')";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("iiisssssiss", 
                $data['session_id'], $data['department_id'], $data['champion_user_id'], $data['paperless_current'], $data['paperless_target'], $data['paperless_deadline'],
                $data['issues_desc'], $data['issues_priority'], $data['it_support_req'], $data['it_support_desc'], $data['feature_req']);
            $stmt->execute();
            return $stmt->insert_id;
        }
    }

    public function updateAction($id, $status, $pending_reason = null)
    {
        $sql = "UPDATE erp_tracker_actions SET status = ?, pending_reason = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $status, $pending_reason, $id);
        return $stmt->execute();
    }

    public function addAction($session_id, $department_id, $action_name, $owner_user_id, $priority, $deadline)
    {
        $sql = "INSERT INTO erp_tracker_actions (session_id, department_id, action_name, owner_user_id, priority, deadline, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iisiss", $session_id, $department_id, $action_name, $owner_user_id, $priority, $deadline);
        if ($stmt->execute()) {
            return $stmt->insert_id;
        }
        return false;
    }

    public function getAction($id)
    {
        $sql = "SELECT a.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone, d.name as department_name 
                FROM erp_tracker_actions a
                LEFT JOIN users u ON a.owner_user_id = u.id
                LEFT JOIN departments d ON a.department_id = d.id
                WHERE a.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Dashboard Analytics
    public function getDashboardStats($session_id)
    {
        $stats = [
            'total_actions' => 0,
            'completed_actions' => 0,
            'pending_actions' => 0,
            'overdue_actions' => 0,
            'departments_submitted' => 0,
            'total_departments' => 0,
        ];
        
        $sql = "SELECT COUNT(DISTINCT department_id) as c FROM erp_tracker_champions";
        $res = $this->db->query($sql);
        if($res) $stats['total_departments'] = $res->fetch_assoc()['c'];

        $sql2 = "SELECT COUNT(*) as c FROM erp_tracker_submissions WHERE session_id = ? AND status = 'Submitted'";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->bind_param("i", $session_id);
        $stmt2->execute();
        $stats['departments_submitted'] = $stmt2->get_result()->fetch_assoc()['c'];

        $sql3 = "SELECT status, COUNT(*) as c FROM erp_tracker_actions WHERE session_id = ? GROUP BY status";
        $stmt3 = $this->db->prepare($sql3);
        $stmt3->bind_param("i", $session_id);
        $stmt3->execute();
        $res3 = $stmt3->get_result();
        while($row = $res3->fetch_assoc()) {
            $stats['total_actions'] += $row['c'];
            if($row['status'] == 'Completed') $stats['completed_actions'] += $row['c'];
            if($row['status'] == 'Pending') $stats['pending_actions'] += $row['c'];
            if($row['status'] == 'Overdue') $stats['overdue_actions'] += $row['c'];
        }

        return $stats;
    }
    
    public function getDepartmentRankings($session_id)
    {
        // (Completed Actions / Total Actions) * 100
        $sql = "SELECT d.id, d.name,
                (SELECT COUNT(*) FROM erp_tracker_actions WHERE session_id = ? AND department_id = d.id) as total_actions,
                (SELECT COUNT(*) FROM erp_tracker_actions WHERE session_id = ? AND department_id = d.id AND status = 'Completed') as completed_actions
                FROM departments d 
                JOIN erp_tracker_champions c ON d.id = c.department_id
                WHERE d.status = 1
                GROUP BY d.id";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ii", $session_id, $session_id);
        $stmt->execute();
        $res = $stmt->get_result();
        
        $rankings = [];
        while($row = $res->fetch_assoc()) {
            $pct = $row['total_actions'] > 0 ? round(($row['completed_actions'] / $row['total_actions']) * 100) : 0;
            $row['completion_pct'] = $pct;
            $rankings[] = $row;
        }
        
        usort($rankings, function($a, $b) {
            return $b['completion_pct'] <=> $a['completion_pct'];
        });
        
        return $rankings;
    }
}
