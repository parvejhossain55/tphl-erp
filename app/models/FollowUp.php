<?php

class FollowUp extends Model
{
    public $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db; // mysqli connection
    }

    // Get all follow-ups with related lead, project, flat, and creator info
    public function getAllFollowUp()
    {
        $sql = "SELECT fu.*, 
               l.full_name, 
               p.name AS project_name, 
               f.flat_name AS flat_name, 
               u1.full_name AS created_by_name
        FROM lead_follow_ups fu
        LEFT JOIN leads l ON fu.lead_id = l.id
        LEFT JOIN flats f ON fu.flat_id = f.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN users u1 ON fu.created_by = u1.id
         WHERE fu.deleted_at IS NULL
        ORDER BY fu.follow_up_date DESC, fu.follow_up_time ASC ";
        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Create a new follow-up
    public function create($data)
    {
        // ==============================
        // DATE FORMAT FIX
        // ==============================
        $follow_up_date = !empty($data['follow_up_date'])
            ? normalizeDateYear($data['follow_up_date'])
            : date('Y-m-d'); // fallback: today (follow_up_date is NOT NULL in DB)

        $next_follow_up_date = !empty($data['next_follow_up_date'])
            ? normalizeDateYear($data['next_follow_up_date'])
            : null;

        // ==============================
        // OPTIONAL FIELD HANDLING
        // ==============================
        $flat_id = !empty($data['flat_id']) ? (int)$data['flat_id'] : null;
        $follow_up_time = !empty($data['follow_up_time']) ? $data['follow_up_time'] : null;

        // ==============================
        // VALIDATE status & next_action AGAINST ENUM
        // ==============================
        $allowedStatuses = ['pending', 'completed', 'missed', 'canceled'];
        $status = in_array($data['status'] ?? '', $allowedStatuses) ? $data['status'] : 'pending';

        $allowedNextActions = ['site visit', 'booking', 'call_back', 'take_time'];
        $next_action = in_array($data['next_action'], $allowedNextActions) ? $data['next_action'] : null;

        $follow_up_type = $data['follow_up_type'] ?? null;
        if ($follow_up_type === 'email') {
            $follow_up_type = 'Email';
        }

        // ==============================
        // PREPARE QUERY
        // ==============================
        $stmt = $this->db->prepare("
        INSERT INTO lead_follow_ups 
        (lead_id, project_id, flat_id, follow_up_date, follow_up_time, follow_up_type, status, discussion_note, next_follow_up_date, next_action, created_by, assigned_with, follow_up_agent_id, lead_status, next_follow_up_plan_notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }

        $stmt->bind_param(
            "iiisssssssiiiss",
            $data['lead_id'],
            $data['project_id'],
            $flat_id,
            $follow_up_date,
            $follow_up_time,
            $follow_up_type,
            $status,
            $data['discussion_note'],
            $next_follow_up_date,
            $next_action,
            $data['created_by'],
            $data['assigned_with'],
            $data['follow_up_agent_id'],
            $data['lead_status'],
            $data['next_follow_up_plan_notes']
        );

        if (!$stmt->execute()) {
            setError($stmt->error);
            return false;
        }

        // ==============================
        // LEAD STATUS AUTO UPDATE
        // ==============================
        $leadAction = null;
        if ($data['follow_up_type'] === 'visit' && $data['status'] === 'completed') {
            $leadAction = 'visited';
        } elseif ($data['status'] === 'pending') {
            $leadAction = 'follow up';
        } elseif ($data['status'] === 'canceled') {
            $leadAction = 'cancel';
        }

        if ($leadAction !== null || isset($data['project_id'])) {
            $updateLead = $this->db->prepare("
            UPDATE leads 
            SET last_lead_action = ?, project_id = ?
            WHERE id = ?
        ");

            if ($updateLead) {
                $updateLead->bind_param(
                    "sii",
                    $leadAction,
                    $data['project_id'],
                    $data['lead_id']
                );
                $updateLead->execute();
            }
        }

        return true;
    }




    // Get a single follow-up by ID
    public function getById($id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                fu.*, 
                l.full_name,
                p.name AS project_name,
                f.flat_name AS unit_name,
                u.full_name AS created_by_name
            FROM lead_follow_ups fu
            LEFT JOIN leads l 
                ON fu.lead_id = l.id
            LEFT JOIN projects p 
                ON fu.project_id = p.id
            LEFT JOIN flats f 
                ON fu.flat_id = f.id
            LEFT JOIN users u 
                ON fu.created_by = u.id
            WHERE fu.id = ?
        ");


        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Update follow-up by ID
    public function update($data)
    {
        $follow_up_type = $data['follow_up_type'] ?? null;
        if ($follow_up_type === 'email') {
            $follow_up_type = 'Email';
        }

        $allowedStatuses = ['pending', 'completed', 'missed', 'canceled'];
        $status = in_array($data['status'] ?? '', $allowedStatuses) ? $data['status'] : 'pending';

        $follow_up_time = !empty($data['follow_up_time']) ? $data['follow_up_time'] : null;

        $stmt = $this->db->prepare("
        UPDATE lead_follow_ups SET
            lead_id = ?,
            flat_id = ?,
            follow_up_date = ?,
            follow_up_time = ?,
            follow_up_type = ?,
            status = ?,
            discussion_note = ?,
            next_follow_up_date = ?,
            next_action = ?,
            follow_up_agent_id = ?,
            lead_status = ?,
            next_follow_up_plan_notes = ?
        WHERE id = ?
    ");

        if (!$stmt) {
            setError($this->db->error);
            return false;
        }
        $norm_follow_up_date = !empty($data['follow_up_date']) ? normalizeDateYear($data['follow_up_date']) : null;
        $norm_next_follow_up_date = !empty($data['next_follow_up_date']) ? normalizeDateYear($data['next_follow_up_date']) : null;

        $stmt->bind_param(
            "iisssssssissi",
            $data['lead_id'],
            $data['flat_id'],   // NULL or valid flats.id
            $norm_follow_up_date,
            $follow_up_time,
            $follow_up_type,
            $status,
            $data['discussion_note'],
            $norm_next_follow_up_date,
            $data['next_action'],
            $data['follow_up_agent_id'],
            $data['lead_status'],
            $data['next_follow_up_plan_notes'],
            $data['id']
        );


        return $stmt->execute();
    }
    public function getByLead($lead_id)
    {
        $stmt = $this->db->prepare("
        SELECT f.*, 
               l.full_name, 
               p.name AS project_name,
              fl.flat_name AS flat_name,
               usr.full_name AS created_by_name,
               ur.full_name AS assigned_user,
               fl.price_value
        FROM lead_follow_ups f
        LEFT JOIN leads l ON f.lead_id = l.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN flats fl ON f.flat_id = fl.id
        LEFT JOIN users usr ON f.created_by = usr.id
        LEFT JOIN users ur ON f.assigned_with = ur.id
        WHERE f.deleted_at IS NULL
          AND f.lead_id = ?
          AND (fl.deleted_at IS NULL OR fl.id IS NULL)
          AND (p.deleted_at IS NULL OR p.id IS NULL)
        ORDER BY f.follow_up_date DESC, f.follow_up_time DESC
    ");

        if (!$stmt) {
            setError($this->db->error);
            return [];
        }

        $stmt->bind_param("i", $lead_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    public function getFollowUpCountByLead()
    {
        $sql = "
        SELECT lead_id, COUNT(*) AS follow_up_count
        FROM lead_follow_ups
        WHERE lead_follow_ups.deleted_at IS NULL
        GROUP BY lead_id
    ";

        $result = $this->db->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[$row['lead_id']] = $row['follow_up_count'];
        }

        return $data;
    }

    // ✅ Optimized: শুধু current page এর lead IDs এর জন্য count করে
    public function getFollowUpCountByLeadIds(array $leadIds)
    {
        if (empty($leadIds)) return [];

        $ids = implode(',', array_map('intval', $leadIds));
        $sql = "SELECT lead_id, COUNT(*) AS follow_up_count
                FROM lead_follow_ups
                WHERE deleted_at IS NULL
                  AND lead_id IN ($ids)
                GROUP BY lead_id";

        $result = $this->db->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['lead_id']] = $row['follow_up_count'];
            }
        }
        return $data;
    }

    public function getTodayFollowUpCountByLead()
    {
        $sql = "SELECT lf.lead_id, COUNT(*) AS follow_up_count
        FROM lead_follow_ups lf
        WHERE lf.deleted_at IS NULL
        AND lf.next_follow_up_date >= CURDATE()
        AND lf.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
        AND lf.deleted_at IS NULL
        GROUP BY lf.lead_id
        ORDER BY follow_up_count DESC";

        $result = $this->db->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[$row['lead_id']] = $row['follow_up_count'];
        }

        return $data;
    }

    public function getTodayFollowUpPendingCount()
    {
        $sql = "SELECT lf.lead_id, COUNT(*) AS follow_up_count
        FROM lead_follow_ups lf
        WHERE lf.deleted_at IS NULL
        AND lf.next_follow_up_date >= CURDATE()
        AND lf.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
        AND lf.status = 'pending'
        GROUP BY lf.lead_id
        ORDER BY follow_up_count DESC";

        $result = $this->db->query($sql);
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[$row['lead_id']] = $row['follow_up_count'];
        }

        return $data;
    }

    // Delete follow-up by ID
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM lead_follow_ups WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }


    public function getTodaysFollowUps($teamId = null)
    {
        $today = date('Y-m-d'); // today date

        $sql = "
    SELECT f.*, 
           l.full_name, 
           p.name AS project_name,
           CONCAT(fl.flat_name, ' - ', fl.id) AS flat_name,
           usr.full_name AS created_by_name,
           fl.price_value,
           COALESCE(CONCAT(usr.full_name,' - ','ID-',usr.id), 'Not assigned') AS assigned_with_name
    FROM lead_follow_ups f
    LEFT JOIN leads l ON f.lead_id = l.id
    LEFT JOIN projects p ON f.project_id = p.id
    LEFT JOIN flats fl ON f.flat_id = fl.id
    LEFT JOIN users usr ON f.created_by = usr.id
    LEFT JOIN employees emp ON f.assigned_with = emp.id
    WHERE DATE(f.next_follow_up_date) = '$today'
    AND f.deleted_at IS NULL
    AND f.status = 'pending'
    AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))
    ";

        if (!empty($teamId)) {
            $sql .= " AND l.team_id = " . (int)$teamId;
        }

        $sql .= " ORDER BY f.follow_up_date DESC, f.follow_up_time DESC";

        $result = $this->db->query($sql);

        $followUps = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $followUps[] = $row;
            }
        }

        return $followUps;
    }
    public function getTodayFollowUpLoginUser(int $userId)
    {
        $userId = (int) $userId;
        $today = date('Y-m-d');

        $sql = "
        SELECT f.*, 
               l.full_name,
               l.id AS lead_id,
               l.phone_number,
               l.phone,
               p.name AS project_name,
               CONCAT(fl.flat_name, ' - ', fl.id) AS flat_name,
               creator.full_name AS created_by_name,
               fl.price_value,
               COALESCE(
                   CONCAT(assignee.full_name, ' - ID-', assignee.id),
                   'Not assigned'
               ) AS assigned_with_name
        FROM lead_follow_ups f
        LEFT JOIN leads l ON f.lead_id = l.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN flats fl ON f.flat_id = fl.id
        LEFT JOIN users creator ON f.created_by = creator.id
        LEFT JOIN users assignee ON f.assigned_with = assignee.id
        WHERE DATE(f.next_follow_up_date) = '{$today}'
          AND f.assigned_with = {$userId}
          AND f.deleted_at IS NULL
        ORDER BY f.follow_up_date DESC, f.follow_up_time DESC
    ";

        $result = $this->db->query($sql);

        $followUps = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $followUps[] = $row;
            }
        }

        return $followUps;
    }


    public function getTodayFollowUpCountLoginUser(int $userId)
    {
        $today = date('Y-m-d');

        $sql = "
        SELECT COUNT(*) as total
        FROM lead_follow_ups f
        WHERE DATE(f.next_follow_up_date) = ?
        AND f.assigned_with = ?
        AND f.status = 'pending'
        AND f.deleted_at IS NULL
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $today, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['total'] ?? 0;
    }
    public function getTodayFollowUpCompleteCount($userId)
    {
        $today = date('Y-m-d');

        $sql = "
        SELECT COUNT(*) as total
        FROM lead_follow_ups f
        WHERE DATE(f.next_follow_up_date) = ?
        AND f.assigned_with = ?
        AND f.status = 'completed'
        AND f.deleted_at IS NULL
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $today, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['total'] ?? 0;
    }
    public function getOverdueFollowUpCountLoginUser(int $userId)
    {
        $today = date('Y-m-d');

        $sql = "
        SELECT COUNT(*) as total
        FROM lead_follow_ups f
        WHERE f.next_follow_up_date IS NOT NULL
        AND f.next_follow_up_date > '1000-01-01'
        AND f.next_follow_up_date < ?
        AND f.status = 'pending'
        AND f.assigned_with = ?
        AND f.deleted_at IS NULL
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $today, $userId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['total'] ?? 0;
    }

    public function upcomingFollowUp($userID)
    {
        // Cast to int just to be safe
        $userID = (int) $userID;

        $sql = "
        SELECT 
            f.id,
            f.lead_id,
            f.project_id,
            f.flat_id,
            f.follow_up_date,
            f.follow_up_time,
            f.status,
            f.next_follow_up_date,
            f.discussion_note,
            f.next_action,
            l.full_name AS lead_name,
            l.phone_number,
            p.name AS project_name,
            CONCAT(fl.flat_name, ' - ', fl.id) AS flat_name,
            usr.full_name AS created_by_name,
            COALESCE(CONCAT(aw.full_name,' - ID-',aw.id), 'Not assigned') AS assigned_with_name
        FROM lead_follow_ups f
        LEFT JOIN leads l ON f.lead_id = l.id
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN flats fl ON f.flat_id = fl.id
        LEFT JOIN users usr ON f.created_by = usr.id
        LEFT JOIN users aw ON f.assigned_with = aw.id
        WHERE f.deleted_at IS NULL
          AND f.assigned_with = ?
          AND f.next_follow_up_date > CURDATE()
          AND f.next_follow_up_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ORDER BY f.next_follow_up_date ASC, f.follow_up_time ASC
    ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            // Debug if prepare failed
            error_log("MySQL Prepare Failed: " . $this->db->error);
            return [];
        }

        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();

        // Ensure we return array even if empty
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getMissingFollowUpCount(int $userId)
    {
        $today = date('Y-m-d');
        $sql = "
            SELECT COUNT(DISTINCT l.id) as total
            FROM leads l
            INNER JOIN (
                SELECT t1.lead_id, t1.next_follow_up_date
                FROM lead_follow_ups t1
                INNER JOIN (
                    SELECT lead_id, MAX(id) AS max_id
                    FROM lead_follow_ups
                    WHERE deleted_at IS NULL
                    GROUP BY lead_id
                ) t2 ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
            ) lf ON lf.lead_id = l.id
            WHERE l.assigned_id = ?
              AND lf.next_follow_up_date IS NOT NULL
              AND lf.next_follow_up_date > '1000-01-01'
              AND DATE(lf.next_follow_up_date) <= ?
              AND l.deleted_at IS NULL
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;
        $stmt->bind_param("is", $userId, $today);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }
}
