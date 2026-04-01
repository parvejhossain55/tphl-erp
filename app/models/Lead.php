<?php

class Lead extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    public function getAll($period = null)
    {
        // Base query
        $sqlQuery = "
        SELECT 
            l.*,
            u.id AS assigned_id,
            u.full_name AS agent_name
        FROM leads AS l
        LEFT JOIN users AS u ON l.assigned_id = u.id
        AND l.deleted_at IS NULL
    ";

        // Add period filter
        if ($period) {
            switch ($period) {
                case 'daily':
                    $sqlQuery .= " AND DATE(l.created_at) = CURDATE()";
                    break;
                case 'weekly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                case 'yearly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
            }
        }

        $stmt = $this->db->prepare($sqlQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getAllByDate($period, $previous = false)
    {
        $sqlQuery = "
        SELECT l.* FROM leads AS l
        WHERE l.status = 'active' AND l.deleted_at IS NULL
    ";

        $today = date('Y-m-d');

        if ($period) {
            switch ($period) {
                case 'daily':
                    $date = $previous ? date('Y-m-d', strtotime('-1 day')) : $today;
                    $sqlQuery .= " AND DATE(l.created_at) = '$date'";
                    break;
                case 'weekly':
                    if ($previous) {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    } else {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    }
                    break;
                case 'monthly':
                    if ($previous) {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 60 DAY) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    } else {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    }
                    break;
                case 'yearly':
                    if ($previous) {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    } else {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    }
                    break;
            }
        }

        $stmt = $this->db->prepare($sqlQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }


    public function getAllWithFilter($year = null, $month = null, $lastAction = null, $roleId = null, $teamId = null)
    {
        $sql = "SELECT 
        l.*,
        u.id AS assigned_id,
        u.full_name AS agent_name,
        p.name AS project_name,
        lf.next_action,
        lf.follow_up_type,
        lf.next_follow_up_date
    FROM leads AS l

    LEFT JOIN (
        SELECT t1.*
        FROM lead_follow_ups t1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM lead_follow_ups
            GROUP BY lead_id
        ) t2 
        ON t1.id = t2.max_id
    ) lf ON lf.lead_id = l.id

    LEFT JOIN users AS u ON l.assigned_id = u.id

    LEFT JOIN (
        SELECT t1.*
        FROM lead_bookings t1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM lead_bookings
            GROUP BY lead_id
        ) t2 
        ON t1.id = t2.max_id
    ) lb ON lb.lead_id = l.id

    LEFT JOIN projects AS p ON lb.project_id = p.id

    WHERE l.deleted_at IS NULL
    ";

        //  IMPORTANT: declare first
        $params = [];
        $types  = "";

        //  ROLE BASED FILTER
        if ($roleId == 5 && !empty($teamId)) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types .= "i";
        }

        //  Year filter
        if (!empty($year)) {
            $sql .= " AND YEAR(l.created_at) = ?";
            $params[] = $year;
            $types .= "i";
        }

        // 📅 Month filter
        if (!empty($month)) {
            $sql .= " AND MONTH(l.created_at) = ?";
            $params[] = $month;
            $types .= "i";
        }

        //  Last action filter
        if (!empty($lastAction)) {
            $sql .= " AND l.last_lead_action = ?";
            $params[] = $lastAction;
            $types .= "s";
        }

        //  Order
        $sql .= " ORDER BY l.id DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        if (!$stmt->execute()) {
            return [];
        }

        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getAllLeadsWithProjectContact($project_id = null, $search = null)
    {
        $sql = "SELECT 
                l.*,
                p.name AS project_name,
                loc.name As location_name,
                ls.name AS source
            FROM leads AS l
            LEFT JOIN projects AS p 
                ON l.project_id = p.id
            LEFT JOIN locations AS loc
                ON l.pref_location_id = loc.id
            LEFT JOIN lead_sources AS ls
                ON l.source_type_id = ls.id
            WHERE l.deleted_at IS NULL";

        $params = [];
        $types  = "";

        // filter by project_id
        if ($project_id) {
            $sql .= " AND l.project_id = ?";
            $params[] = $project_id;
            $types .= "i";
        }

        // search by project name
        if ($search) {
            $sql .= " AND p.name LIKE ?";
            $params[] = "%" . $search . "%";
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalHotLeads()
    {
        $sql = $this->db->prepare("SELECT COUNT(*) AS total_hot FROM leads where lead_type = 'Hot' And deleted_at IS NULL AND status = 'active' AND last_lead_action != 'cancel'");
        $sql->execute();
        $result = $sql->get_result()->fetch_assoc();
        return $result['total_hot'] ?? 0;
    }
    public function getVisitedLeadCount($period = null)
    {
        $sqlQuery = "
            SELECT COUNT(*) AS total_visited
            FROM leads
            WHERE status = 'active'
            AND deleted_at IS NULL
            AND last_lead_action = 'visited'
        ";

        // Add period filter
        if ($period) {
            switch ($period) {
                case 'daily':
                    $sqlQuery .= " AND DATE(created_at) = CURDATE()";
                    break;
                case 'weekly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                case 'yearly':
                    $sqlQuery .= " AND created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
            }
        }

        $stmt = $this->db->prepare($sqlQuery);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return (int)($result['total_visited'] ?? 0);
    }


    public function getTodayLeadsByUser(int $userId)
    {
        $userId = (int) $userId;

        $sql = "
        SELECT 
            l.*,
            p.name AS project_name
        FROM leads AS l
        LEFT JOIN projects AS p ON l.project_id = p.id
        WHERE l.assigned_id = {$userId}
          AND l.deleted_at IS NULL
          AND (
                (l.created_at >= CURDATE() AND l.created_at < CURDATE() + INTERVAL 1 DAY)
                OR
                (l.assigned_at IS NOT NULL AND l.assigned_at >= CURDATE() AND l.assigned_at < CURDATE() + INTERVAL 1 DAY)
              )
        ORDER BY l.id DESC
    ";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function getAllLeadsByUser(int $userId)
    {
        $userId = (int) $userId;

        $sql = "
        SELECT *
        FROM leads
        WHERE assigned_id = {$userId}
          
          AND deleted_at IS NULL
        ORDER BY id DESC
    ";

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    public function getAllMyLeads(int $userId, $year = null, $month = null, $lastAction = null)
    {
        $sql = "SELECT leads.*, 
        lf.next_action,
        lf.follow_up_type,
        p.name AS project_name,
        f.flat_name,
        lf.next_follow_up_date
    FROM leads
    LEFT JOIN (
        SELECT t1.*
        FROM lead_follow_ups t1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM lead_follow_ups
            GROUP BY lead_id
        ) t2 
        ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
    ) lf ON lf.lead_id = leads.id
    LEFT JOIN projects AS p ON lf.project_id = p.id
    LEFT JOIN flats AS f ON lf.flat_id = f.id
    WHERE leads.assigned_id = ?";

        $params = [$userId];
        $types  = "i";

        if (!empty($year)) {
            $sql .= " AND YEAR(leads.created_at) = ?";
            $params[] = (int)$year;
            $types .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH(leads.created_at) = ?";
            $params[] = (int)$month;
            $types .= "i";
        }

        if (!empty($lastAction)) {
            $sql .= " AND leads.last_lead_action = ?";
            $params[] = $lastAction;
            $types .= "s";
        }

        $sql .= " ORDER BY leads.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }






    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO leads (
        campaign_name,
        platform,
        which_apartment_size_are_you_interested_in,
        which_floor_are_you_for_shop,
        when_are_you_planning_to_buy,
        would_you_like_to_schedule_a_site_visit,
        when_is_the_best_time_to_contact_you,
        job_title,
        city,
        full_name,
        father_name,
        mother_name,
        dob,
        permanent_address,
        nid_or_passport,
        phone_number,
        alt_phone_number,
        email,
        source_type_id,
        assigned_id,
        address,
        pref_location_id,
        designation,
        project_id,
        pref_flat_size,
        communication_type,
        tags,
        description,
        visibility,
        status,
        lead_quality,
        lead_type,
        company_name,
        team_id,
        created_by ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?, ?,?)");

        if (!$stmt) {
            setError("Prepare failed: " . $this->db->error);
            return false;
        }

        // Assign variables (CSV columns first)
        $campaign_name = $data['campaign_name'] ?? null;
        $platform = $data['platform'] ?? null;
        $which_apartment_size_are_you_interested_in = $data['which_apartment_size_are_you_interested_in'] ?? null;
        $which_floor_are_you_for_shop = $data['which_floor_are_you_for_shop'] ?? null;
        $when_are_you_planning_to_buy = $data['when_are_you_planning_to_buy'] ?? null;
        $would_you_like_to_schedule_a_site_visit = $data['would_you_like_to_schedule_a_site_visit'] ?? null;
        $when_is_the_best_time_to_contact_you = $data['when_is_the_best_time_to_contact_you'] ?? null;
        $job_title = $data['job_title'] ?? null;
        $city = $data['city'] ?? null;

        // Existing fields
        $full_name = $data['full_name'] ?? null;
        $father_name = $data['father_name'] ?? null;
        $mother_name = $data['mother_name'] ?? null;
        $dob = isset($data['dob']) && trim($data['dob']) !== ''
            ? $data['dob']
            : null;
        $permanent_address = $data['permanent_address'] ?? null;
        $nid_or_passport = $data['nid_or_passport'] ?? null;
        $phone_number = $data['phone_number'] ?? null;
        $alt_phone_number = $data['alt_phone_number'] ?? null;
        $email = $data['email'] ?? null;
        $source_type_id = $data['source_type_id'] ?? null;
        $assigned_id = $data['assigned_id'] ?? null;
        $address = $data['address'] ?? null;
        $pref_location_id = $data['pref_location_id'] ?? null;
        $designation = $data['designation'] ?? null;
        $project_id = $data['project_id'] ?? null;
        $pref_flat_size = $data['pref_flat_size'] ?? null;
        $communication_type = $data['communication_type'] ?? null;
        $tags = $data['tags'] ?? null;
        $description = $data['description'] ?? null;
        $visibility = $data['visibility'] ?? 'public';
        $status = $data['status'] ?? 'active';
        $lead_quality = $data['lead_quality'] ?? 'Pending';
        $lead_type = $data['lead_type'] ?? null;
        $company_name = $data['company_name'] ?? null;
        $team_id = $data['team_id'] ?? null;
        $created_by = $data['created_by'] ?? null;

        // Bind parameters (CSV first, then existing)
        $stmt->bind_param(
            "ssssssssssssssssssiisisisssssssssii",
            $campaign_name,
            $platform,
            $which_apartment_size_are_you_interested_in,
            $which_floor_are_you_for_shop,
            $when_are_you_planning_to_buy,
            $would_you_like_to_schedule_a_site_visit,
            $when_is_the_best_time_to_contact_you,
            $job_title,
            $city,
            $full_name,
            $father_name,
            $mother_name,
            $dob,
            $permanent_address,
            $nid_or_passport,
            $phone_number,
            $alt_phone_number,
            $email,
            $source_type_id,
            $assigned_id,
            $address,
            $pref_location_id,
            $designation,
            $project_id,
            $pref_flat_size,
            $communication_type,
            $tags,
            $description,
            $visibility,
            $status,
            $lead_quality,
            $lead_type,
            $company_name,
            $team_id,
            $created_by
        );

        if ($stmt->execute()) {
            setSuccess("Lead created successfully!");
            return true;
        } else {
            setError("Lead creation failed: " . $stmt->error);
            return false;
        }
    }



    public function find($id)
    {


        $stmt = $this->db->prepare(
            "SELECT 
            leads.*,
            p.name AS project_name,
            s.name AS source_name,
            u.full_name AS user_name,
            l.name AS location_name,
            COUNT(f.id) AS follow_up_count

        FROM leads
        LEFT JOIN lead_sources s 
            ON leads.source_type_id = s.id
        LEFT JOIN lead_follow_ups f 
            ON leads.id = f.lead_id
        LEFT JOIN users u 
            ON leads.assigned_id = u.id
                    LEFT JOIN projects AS p ON leads.project_id = p.id
                    LEFT JOIN locations AS l ON leads.pref_location_id = l.id
        WHERE leads.id = ?
        GROUP BY leads.id"
        );

        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    /**
     * Update lead
     */
    public function update($id, $data)
    {
        $old = $this->find($id);
        if (!$old) {
            return false;
        }
        $nomi_dob = !empty($data['nomi_dob']) ? $data['nomi_dob'] : null;
        // ======================
        // Image Upload (fallback old)
        // ======================
        $lead_image = !empty($_FILES['lead_image']['name'])
            ? uploadImage('lead_image', 'leads', 'fixed')
            : $old['lead_image'];

        $nomi_image = !empty($_FILES['nomi_image']['name'])
            ? uploadImage('nomi_image', 'leads', 'fixed')
            : $old['nomi_image'];

        // ======================
        // Prepare SQL
        // ======================
        $sql = "UPDATE leads SET
        full_name = ?, father_name = ?, mother_name = ?, dob = ?, permanent_address = ?, nid_or_passport = ?,
        phone = ?, alt_phone_number = ?, email = ?, address = ?, designation = ?,
        lead_name_bn = ?, father_name_bn = ?, mother_name_bn = ?, identity_type = ?, permanent_add_bn = ?,
        present_add_bn = ?, profession_bn = ?, nationality = ?, religion = ?, lead_image = ?,
        nominee_name = ?, relation_with_lead = ?, nomi_father_name = ?, nomi_mother_name = ?, nomi_profession = ?,
        nomi_dob = ?, nomi_phone = ?, nomi_email = ?, nomi_nid_passport = ?, nomi_present_add = ?, 
        nomi_permanent_address = ?, nomi_image = ?, description = ?,created_by = ?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        // ======================
        // Bind all strings + id as integer
        // ======================
        $stmt->bind_param(
            str_repeat("s", 34) . "ii", // 34 strings + 1 integer for id
            $data['full_name'],
            $data['father_name'],
            $data['mother_name'],
            $data['dob'],
            $data['permanent_address'],
            $data['nid_or_passport'],
            $data['phone'],
            $data['alt_phone_number'],
            $data['email'],
            $data['address'],
            $data['designation'],
            $data['lead_name_bn'],
            $data['father_name_bn'],
            $data['mother_name_bn'],
            $data['identity_type'],
            $data['permanent_add_bn'],
            $data['present_add_bn'],
            $data['profession_bn'],
            $data['nationality'],
            $data['religion'],
            $lead_image,
            $data['nominee_name'],
            $data['relation_with_lead'],
            $data['nomi_father_name'],
            $data['nomi_mother_name'],
            $data['nomi_profession'],
            $nomi_dob,
            $data['nomi_phone'],
            $data['nomi_email'],
            $data['nomi_nid_passport'],
            $data['nomi_present_add'],
            $data['nomi_permanent_address'],
            $nomi_image,
            $data['description'],
            $data['created_by'],
            $id // integer
        );

        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }

        return true;
    }
    public function countLeadsByMonth($year, $month)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) AS total
        FROM leads
        WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?
    ");
        $stmt->bind_param("ii", $year, $month);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)($result['total'] ?? 0);
    }



    public function updateLastLeadAction($leadId, $lastAction)
    {
        $allowedActions = ['new', 'booked', 'follow up', 'visited', 'cancel'];

        if (!in_array($lastAction, $allowedActions)) {
            return false;
        }

        $sql = "UPDATE leads 
            SET last_lead_action = ?, 
                updated_at = NOW()
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $lastAction, $leadId);

        return $stmt->execute();
    }


    public function softDelete($leadId)
    {
        $this->db->begin_transaction();

        try {
            $now = date('Y-m-d H:i:s');

            // 1. Leads table (also update status)
            $stmt = $this->db->prepare("
            UPDATE leads 
            SET deleted_at = ?, status = 'inactive'
            WHERE id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            // 2. Lead bookings
            $stmt = $this->db->prepare("
            UPDATE lead_bookings 
            SET deleted_at = ?
            WHERE lead_id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            // 3. Lead follow ups
            $stmt = $this->db->prepare("
            UPDATE lead_follow_ups 
            SET deleted_at = ?
            WHERE lead_id = ?
        ");
            $stmt->bind_param("si", $now, $leadId);
            $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function getDigitalMarketingStats()
    {
        $stats = [];

        // Total Leads (Active and not deleted)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE status = 'active' AND deleted_at IS NULL");
        $stmt->execute();
        $stats['total_leads'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Uploaded This Week (Leads created in the last 7 days)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND deleted_at IS NULL");
        $stmt->execute();
        $stats['leads_this_week'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Failed / Errors (Inactive or Cancelled leads)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE (status = 'inactive' OR last_lead_action = 'cancel') AND deleted_at IS NULL");
        $stmt->execute();
        $stats['failed_leads'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Total Uploads (Leads with campaign_name, assuming they are from CSV/Marketing)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE campaign_name IS NOT NULL AND deleted_at IS NULL");
        $stmt->execute();
        $stats['total_uploads'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        return $stats;
    }
    public function getLeadsByCreatedByLast7Days(int $userId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                ls.name AS source_name,
                p.name AS project_name,
                t.name AS team_name
            FROM leads l
            LEFT JOIN lead_sources ls ON l.source_type_id = ls.id
            LEFT JOIN projects p ON l.project_id = p.id
            LEFT JOIN teams t ON l.team_id = t.id
            WHERE l.created_by = ? 
              AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              AND l.deleted_at IS NULL
            ORDER BY l.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
