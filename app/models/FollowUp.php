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

    /**
     * Count existing 'No Response' follow-ups for a lead
     */
    public function getNoResponseCountByLead(int $leadId, ?int $excludeFollowUpId = null): int
    {
        $sql = "SELECT COUNT(*) AS c FROM lead_follow_ups WHERE lead_id = ? AND lead_status = 'No Response' AND deleted_at IS NULL";
        if ($excludeFollowUpId !== null) {
            $sql .= " AND id != " . (int)$excludeFollowUpId;
        }
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param("i", $leadId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['c'] ?? 0);
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
        $next_action = in_array($data['next_action'] ?? '', $allowedNextActions) ? $data['next_action'] : null;

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
        if (($data['follow_up_type'] ?? '') === 'visit' && ($data['status'] ?? '') === 'completed') {
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

        $projectId = !empty($data['project_id']) ? (int)$data['project_id'] : null;
        $flatId = !empty($data['flat_id']) ? (int)$data['flat_id'] : null;

        $stmt = $this->db->prepare("
        UPDATE lead_follow_ups SET
            lead_id = ?,
            project_id = ?,
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
        $follow_up_time = !empty($data['follow_up_time']) ? $data['follow_up_time'] : null;

        $stmt->bind_param(
            "iiisssssssissi",
            $data['lead_id'],
            $projectId,
            $flatId,   // NULL or valid flats.id
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
           COALESCE(CONCAT(usr.full_name,' - ', COALESCE(emp_creator.employee_id, CONCAT('ID-', usr.id))), 'Not assigned') AS assigned_with_name
    FROM lead_follow_ups f
    LEFT JOIN leads l ON f.lead_id = l.id
    LEFT JOIN projects p ON f.project_id = p.id
    LEFT JOIN flats fl ON f.flat_id = fl.id
    LEFT JOIN users usr ON f.created_by = usr.id
    LEFT JOIN employees emp ON f.assigned_with = emp.user_id
    LEFT JOIN employees emp_creator ON usr.id = emp_creator.user_id
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
    public function getTodayFollowUpLoginUser(int $userId, $projectId = null, $projectCategoryId = null, $country = null)
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
        WHERE DATE(f.next_follow_up_date) = ?
          AND f.assigned_with = ?
          AND f.deleted_at IS NULL
    ";

        $params = [$today, $userId];
        $types  = "si";

        if (!empty($projectId)) {
            $sql .= " AND (f.project_id = ? OR l.project_id = ?)";
            $params[] = (int)$projectId;
            $params[] = (int)$projectId;
            $types .= "ii";
        }

        if (!empty($projectCategoryId)) {
            $sql .= " AND l.project_category_id = ?";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
        }

        if (!empty($country)) {
            $countryList = is_array($country) ? array_filter($country, fn($val) => trim($val) !== '') : [trim((string)$country)];
            if (!empty($countryList)) {
                $allConds = [];
                foreach ($countryList as $cItem) {
                    $synonyms = [$cItem];
                    if (strcasecmp($cItem, 'UAE') === 0 || strcasecmp($cItem, 'United Arab Emirates') === 0) {
                        $synonyms = ['UAE', 'United Arab Emirates'];
                    } elseif (strcasecmp($cItem, 'USA') === 0 || strcasecmp($cItem, 'United States') === 0) {
                        $synonyms = ['USA', 'United States'];
                    } elseif (strcasecmp($cItem, 'UK') === 0 || strcasecmp($cItem, 'United Kingdom') === 0) {
                        $synonyms = ['UK', 'United Kingdom'];
                    }

                    $subConds = [];
                    foreach ($synonyms as $syn) {
                        $subConds[] = "l.country = ?";
                        $subConds[] = "l.city = ?";
                        $subConds[] = "l.city LIKE ?";
                        $subConds[] = "l.address LIKE ?";
                        $params[] = $syn;
                        $params[] = $syn;
                        $params[] = "%" . $syn . "%";
                        $params[] = "%" . $syn . "%";
                        $types .= "ssss";
                    }
                    $allConds[] = "(" . implode(" OR ", $subConds) . ")";
                }
                if (!empty($allConds)) {
                    $sql .= " AND (" . implode(" OR ", $allConds) . ")";
                }
            }
        }

        $sql .= " ORDER BY f.follow_up_date DESC, f.follow_up_time DESC";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $followUps = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $followUps[] = $row;
                }
            }
            $stmt->close();
            return $followUps;
        }

        return [];
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
          AND (f.assigned_with = ? OR (f.assigned_with IS NULL AND l.assigned_id = ?))
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

        $stmt->bind_param("ii", $userID, $userID);
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

    public function getCedActivityReport($agentId, $startDate, $endDate)
    {
        $agentCond1 = ""; $agentCond2 = "";
        $dateCond1 = ""; $dateCond2 = "";

        if ($agentId) {
            $agentId = (int)$agentId;
            $agentCond1 = " AND fu.created_by = $agentId ";
            $agentCond2 = " AND ef.added_by = $agentId ";
        }

        if ($startDate && $endDate) {
            $sd = $this->db->real_escape_string($startDate);
            $ed = $this->db->real_escape_string($endDate);
            $dateCond1 = " AND DATE(fu.follow_up_date) BETWEEN '$sd' AND '$ed' ";
            $dateCond2 = " AND DATE(ef.created_at) BETWEEN '$sd' AND '$ed' ";
        }

        $sql = "
            SELECT 
                fu.id,
                fu.lead_id,
                fu.follow_up_date,
                fu.follow_up_time,
                fu.follow_up_type,
                fu.status,
                fu.discussion_note,
                fu.next_action,
                fu.next_follow_up_date,
                NULL AS activity_date,
                NULL AS activity_mode,
                NULL AS activity_reason,
                l.full_name AS client_name, 
                l.phone AS client_phone,
                p.name AS project_name, 
                f.flat_name AS flat_name, 
                u1.full_name AS created_by_name
            FROM lead_follow_ups fu
            LEFT JOIN leads l ON fu.lead_id = l.id
            LEFT JOIN flats f ON fu.flat_id = f.id
            LEFT JOIN projects p ON fu.project_id = p.id
            LEFT JOIN users u1 ON fu.created_by = u1.id
            WHERE fu.deleted_at IS NULL
            $agentCond1
            $dateCond1

            UNION ALL

            SELECT 
                ef.id,
                lb.lead_id,
                DATE(ef.created_at) AS follow_up_date,
                TIME(ef.created_at) AS follow_up_time,
                CASE 
                    WHEN ef.emi_no = 0 THEN 'General EMI Follow Up'
                    WHEN ef.emi_no = 999 THEN 'Development Charge Follow Up'
                    ELSE CONCAT('EMI #', ef.emi_no, ' Follow Up')
                END AS follow_up_type,
                'completed' AS status,
                ef.note AS discussion_note,
                NULL AS next_action,
                NULL AS next_follow_up_date,
                ef.activity_date AS activity_date,
                ef.activity_mode AS activity_mode,
                ef.activity_reason AS activity_reason,
                l2.full_name AS client_name, 
                l2.phone AS client_phone,
                p2.name AS project_name, 
                f2.flat_name AS flat_name, 
                u2.full_name AS created_by_name
            FROM emi_followups ef
            INNER JOIN lead_bookings lb ON ef.booking_id = lb.id
            LEFT JOIN leads l2 ON lb.lead_id = l2.id
            LEFT JOIN projects p2 ON lb.project_id = p2.id
            LEFT JOIN flats f2 ON lb.flat_id = f2.id
            LEFT JOIN users u2 ON ef.added_by = u2.id
            WHERE 1=1
            $agentCond2
            $dateCond2

            ORDER BY follow_up_date DESC, follow_up_time DESC
        ";

        $result = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        // Summarize
        $totalNotes = count($result);
        $uniqueClients = [];
        foreach ($result as $row) {
            if (!empty($row['lead_id'])) {
                $uniqueClients[$row['lead_id']] = true;
            }
        }
        $totalClients = count($uniqueClients);

        return [
            'summary' => [
                'total_notes' => $totalNotes,
                'total_clients' => $totalClients
            ],
            'details' => $result
        ];
    }
}
