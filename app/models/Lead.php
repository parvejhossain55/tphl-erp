<?php

class Lead extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    public function getAll($period = null, $roleId = null, $teamId = null)
    {
        // Base query
        $sqlQuery = "
        SELECT 
            l.*,
            u.id AS assigned_id,
            u.full_name AS agent_name
        FROM leads AS l
        LEFT JOIN users AS u ON l.assigned_id = u.id
        WHERE l.deleted_at IS NULL
    ";

        $params = [];
        $types = "";

        //  ROLE BASED FILTER
        // If teamId is provided, filter by team regardless of role
        if (!empty($teamId)) {
            $sqlQuery .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types .= "i";
        }

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

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

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


    public function countAllWithFilter($year = null, $month = null, $lastAction = null, $roleId = null, $teamId = null, $assigned = null, $assigned_user = null, $date = null, $search = null)
    {
        $sql = "SELECT COUNT(l.id) AS total_count FROM leads AS l WHERE l.deleted_at IS NULL";

        $params = [];
        $types  = "";

        // ROLE BASED FILTER
        if (in_array($roleId, [1, 3, 5]) && !empty($teamId)) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types .= "i";
        }

        // Date filter
        if (!empty($date)) {
            $sql .= " AND DATE(l.created_at) = ?";
            $params[] = $date;
            $types .= "s";
        } else {
            // Year filter
            if (!empty($year)) {
                $sql .= " AND YEAR(l.created_at) = ?";
                $params[] = $year;
                $types .= "i";
            }
            // Month filter
            if (!empty($month)) {
                $sql .= " AND MONTH(l.created_at) = ?";
                $params[] = $month;
                $types .= "i";
            }
        }

        // Last action filter
        if (!empty($lastAction)) {
            $sql .= " AND l.last_lead_action = ?";
            $params[] = $lastAction;
            $types .= "s";
        }

        // Assigned filter
        if (isset($assigned) && $assigned !== '') {
            if ($assigned === 'assigned') {
                $sql .= " AND l.assigned_id IS NOT NULL";
            } elseif ($assigned === 'unassigned') {
                $sql .= " AND l.assigned_id IS NULL";
            }
        }
        
        // Assigned User filter
        if (isset($assigned_user) && $assigned_user !== '') {
            $sql .= " AND l.assigned_id = ?";
            $params[] = $assigned_user;
            $types .= "i";
        }

        // Search Filter
        if (!empty($search)) {
            $sql .= " AND (l.full_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sss";
        }

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            return 0;
        }

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? (int)$row['total_count'] : 0;
    }

    public function getAllWithFilter($year = null, $month = null, $lastAction = null, $roleId = null, $teamId = null, $assigned = null, $assigned_user = null, $date = null, $limit = null, $offset = null, $search = null)
    {
        // Optimized query: server e large dataset (3000+) handle korte
        // Correlated subquery replace kora hoyeche simpler LEFT JOIN diye
        $sql = "SELECT 
        l.*,
        u.full_name AS agent_name,
        p.name AS project_name,
        lf.next_action,
        lf.follow_up_type,
        lf.next_follow_up_date
    FROM leads AS l
    LEFT JOIN users AS u ON l.assigned_id = u.id
    LEFT JOIN (
        SELECT lf1.lead_id, lf1.next_action, lf1.follow_up_type, lf1.next_follow_up_date
        FROM lead_follow_ups lf1
        WHERE lf1.id = (
            SELECT MAX(lf2.id) FROM lead_follow_ups lf2
            WHERE lf2.lead_id = lf1.lead_id
        )
    ) lf ON lf.lead_id = l.id
    LEFT JOIN (
        SELECT lb1.lead_id, lb1.project_id
        FROM lead_bookings lb1
        WHERE lb1.id = (
            SELECT MAX(lb2.id) FROM lead_bookings lb2
            WHERE lb2.lead_id = lb1.lead_id
        )
    ) lb ON lb.lead_id = l.id
    LEFT JOIN projects AS p ON lb.project_id = p.id
    WHERE l.deleted_at IS NULL
    ";

        //  IMPORTANT: declare first
        $params = [];
        $types  = "";

        //  ROLE BASED FILTER
        if (in_array($roleId, [1, 3, 5]) && !empty($teamId)) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types .= "i";
        }

        // Date filter (priority)
        if (!empty($date)) {
            $sql .= " AND DATE(l.created_at) = ?";
            $params[] = $date;
            $types .= "s";
        } else {

            // Year filter
            if (!empty($year)) {
                $sql .= " AND YEAR(l.created_at) = ?";
                $params[] = $year;
                $types .= "i";
            }

            // Month filter
            if (!empty($month)) {
                $sql .= " AND MONTH(l.created_at) = ?";
                $params[] = $month;
                $types .= "i";
            }
        }

        //  Last action filter
        if (!empty($lastAction)) {
            $sql .= " AND l.last_lead_action = ?";
            $params[] = $lastAction;
            $types .= "s";
        }

        // Assigned filter
        if (isset($assigned) && $assigned !== '') {
            if ($assigned === 'assigned') {
                $sql .= " AND l.assigned_id IS NOT NULL";
            } elseif ($assigned === 'unassigned') {
                $sql .= " AND l.assigned_id IS NULL";
            }
        }
        // Assigned User filter
      if (isset($assigned_user) && $assigned_user !== '') {
    $sql .= " AND l.assigned_id = ?";
    $params[] = $assigned_user;
    $types .= "i";
}

        // Search Filter
        if (!empty($search)) {
            $sql .= " AND (l.full_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sss";
        }



        //  Order by created_at DESC for latest leads first
        $sql .= " ORDER BY l.created_at DESC, l.id DESC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= "ii";
        }

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
            p.name AS project_name,
            lf.status AS last_follow_up_status,
            lf.discussion_note AS last_follow_up_note,
            lf.next_follow_up_date,
            lf.follow_up_type
        FROM leads AS l
        LEFT JOIN projects AS p ON l.project_id = p.id
        LEFT JOIN (
            SELECT t1.*
            FROM lead_follow_ups t1
            INNER JOIN (
                SELECT lead_id, MAX(id) AS max_id
                FROM lead_follow_ups
                GROUP BY lead_id
            ) t2 ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
        ) lf ON lf.lead_id = l.id
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
    public function getAllMyLeads(int $userId, $year = null, $month = null, $lastAction = null, $call_status = null)
    {
        $sql = "SELECT leads.*, 
        lf.next_action,
        lf.follow_up_type,
        lf.status AS last_follow_up_status,
        lf.discussion_note AS last_follow_up_note,
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
        if (!empty($call_status)) {
                $sql .= " AND leads.call_status = ?";
                $params[] = $call_status;
                $types .= "s";
        }

        // Try sorting by created_at instead
        $sql .= " ORDER BY leads.created_at DESC, leads.id DESC";

        // Log the final SQL for debugging
        error_log("Final SQL: " . $sql);
        error_log("Params: " . print_r($params, true));

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Error: " . $this->db->error);
            return [];
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            error_log("SQL Execute Error: " . $stmt->error);
            return [];
        }

        $result = $stmt->get_result();
        $leads = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // Debug output
        if (!empty($leads)) {
            echo "<!-- DEBUG: First lead ID: " . $leads[0]['id'] . " -->";
            echo "<!-- DEBUG: Last lead ID: " . $leads[count($leads) - 1]['id'] . " -->";
        }

        $stmt->close();

        return $leads;
    }

    public function getSalesTeamKpiData(array $options = [])
    {
        $search = trim($options['search'] ?? '');
        $executiveId = !empty($options['executive']) ? (int) $options['executive'] : null;
        $year = !empty($options['year']) ? (int) $options['year'] : null;
        $month = !empty($options['month']) ? (int) $options['month'] : null;
        $hasBookings = !empty($options['has_bookings']);
        $hasVisits = !empty($options['has_visits']);

        // Fetch sales users
        $sql = "SELECT DISTINCT u.id, u.full_name, u.email, r.name as role_name
            FROM users u
            JOIN leads l ON l.assigned_id = u.id
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE l.deleted_at IS NULL";

        $params = [];
        $types = "";

        if ($executiveId) {
            $sql .= " AND u.id = ?";
            $params[] = $executiveId;
            $types .= "i";
        }

        if ($search !== '') {
            $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR r.name LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "ssss";
        }

        $sql .= " ORDER BY u.full_name";

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) return [];

        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $salesUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $kpiData = [];

        foreach ($salesUsers as $user) {
            $userId = $user['id'];

            // Total leads
            $leadWhere = ["assigned_id = ?", "deleted_at IS NULL"];
            $leadParams = [$userId];
            $leadTypes = "i";
            if ($year) {
                $leadWhere[] = "YEAR(created_at) = ?";
                $leadParams[] = $year;
                $leadTypes .= "i";
            }
            if ($month) {
                $leadWhere[] = "MONTH(created_at) = ?";
                $leadParams[] = $month;
                $leadTypes .= "i";
            }
            $leadsSql = "SELECT COUNT(*) AS count FROM leads WHERE " . implode(" AND ", $leadWhere);
            $leadsStmt = $this->db->prepare($leadsSql);
            $leadsStmt->bind_param($leadTypes, ...$leadParams);
            $leadsStmt->execute();
            $totalLeads = $leadsStmt->get_result()->fetch_assoc()['count'] ?? 0;

            // Completed visits
            $visitWhere = ["assigned_with = ?", "follow_up_type = 'visit'", "status = 'complete'", "deleted_at IS NULL"];
            $visitParams = [$userId];
            $visitTypes = "i";
            if ($year) {
                $visitWhere[] = "YEAR(follow_up_date) = ?";
                $visitParams[] = $year;
                $visitTypes .= "i";
            }
            if ($month) {
                $visitWhere[] = "MONTH(follow_up_date) = ?";
                $visitParams[] = $month;
                $visitTypes .= "i";
            }
            $visitsSql = "SELECT COUNT(*) AS count FROM lead_follow_ups WHERE " . implode(" AND ", $visitWhere);
            $visitsStmt = $this->db->prepare($visitsSql);
            $visitsStmt->bind_param($visitTypes, ...$visitParams);
            $visitsStmt->execute();
            $totalVisits = $visitsStmt->get_result()->fetch_assoc()['count'] ?? 0;

            // Total bookings (paid only, filtered by sales_by_id)
            $bookingWhere = ["lb.sales_by_id = ?", "lb.deleted_at IS NULL", "lp.payment_status = 'paid'"];
            $bookingParams = [$userId];
            $bookingTypes = "i";
            if ($year) {
                $bookingWhere[] = "YEAR(lb.booking_date) = ?";
                $bookingParams[] = $year;
                $bookingTypes .= "i";
            }
            if ($month) {
                $bookingWhere[] = "MONTH(lb.booking_date) = ?";
                $bookingParams[] = $month;
                $bookingTypes .= "i";
            }
            $bookingsSql = "SELECT COUNT(DISTINCT lb.id) AS total_bookings 
                        FROM lead_bookings lb
                        JOIN lead_payments lp ON lp.booking_id = lb.id
                        WHERE " . implode(" AND ", $bookingWhere);
            $bookingsStmt = $this->db->prepare($bookingsSql);
            $bookingsStmt->bind_param($bookingTypes, ...$bookingParams);
            $bookingsStmt->execute();
            $totalBookings = $bookingsStmt->get_result()->fetch_assoc()['total_bookings'] ?? 0;

            // Total money collected
            $moneySql = "SELECT SUM(lp.amount) AS total_money 
                     FROM lead_bookings lb
                     JOIN lead_payments lp ON lp.booking_id = lb.id AND lp.payment_status = 'paid'
                     WHERE lb.sales_by_id = ?" .
                ($year ? " AND YEAR(lb.booking_date) = ?" : "") .
                ($month ? " AND MONTH(lb.booking_date) = ?" : "");
            $moneyStmt = $this->db->prepare($moneySql);

            $moneyParams = [$userId];
            $moneyTypes = "i";
            if ($year) {
                $moneyParams[] = $year;
                $moneyTypes .= "i";
            }
            if ($month) {
                $moneyParams[] = $month;
                $moneyTypes .= "i";
            }

            $moneyStmt->bind_param($moneyTypes, ...$moneyParams);
            $moneyStmt->execute();
            $totalMoney = $moneyStmt->get_result()->fetch_assoc()['total_money'] ?? 0;

            // Skip if filters require bookings or visits
            if ($hasBookings && $totalBookings === 0) continue;
            if ($hasVisits && $totalVisits === 0) continue;

            // Conversion rate
            $conversionRate = $totalLeads > 0 ? round(($totalBookings / $totalLeads) * 100, 2) : 0;

            // Project-wise bookings
            $projectSql = "SELECT p.id AS project_id, p.name AS project_name, 
                       COUNT(DISTINCT lb.id) AS total_bookings,
                       SUM(lp.amount) AS total_money
                       FROM lead_bookings lb
                       JOIN lead_payments lp ON lp.booking_id = lb.id AND lp.payment_status = 'paid'
                       JOIN projects p ON lb.project_id = p.id
                       WHERE lb.deleted_at IS NULL AND lb.sales_by_id = ?";
            $projectParams = [$userId];
            $projectTypes = "i";
            if ($year) {
                $projectSql .= " AND YEAR(lb.booking_date) = ?";
                $projectParams[] = $year;
                $projectTypes .= "i";
            }
            if ($month) {
                $projectSql .= " AND MONTH(lb.booking_date) = ?";
                $projectParams[] = $month;
                $projectTypes .= "i";
            }
            $projectSql .= " GROUP BY p.id, p.name";

            $projectStmt = $this->db->prepare($projectSql);
            $projectStmt->bind_param($projectTypes, ...$projectParams);
            $projectStmt->execute();
            $projectBookings = $projectStmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $kpiData[] = [
                'user' => $user,
                'total_leads' => (int) $totalLeads,
                'total_visits' => (int) $totalVisits,
                'total_bookings' => (int) $totalBookings,
                'total_money' => (float) $totalMoney,
                'conversion_rate' => $conversionRate,
                'project_bookings' => $projectBookings
            ];
        }

        return $kpiData;
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
    public function createFromCsvBulk($data)
    {
        $sql = "INSERT INTO leads (
            full_name, job_title, company_name, phone, alt_phone_number, email, 
            lead_image, lead_name_bn, father_name_bn, mother_name_bn, profession_bn, 
            nationality, religion, nominee_name, nomi_father_name, nomi_mother_name, 
            nomi_profession, relation_with_lead, nomi_dob, nomi_nid_passport, 
            nomi_phone, nomi_email, nomi_present_add, nomi_permanent_address, nomi_image,
            status, visibility, created_by, team_id, assigned_id, uploaded_from
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?
        )";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) throw new Exception($this->db->error);

        // Helper to handle empty strings as null
        $v = function($key) use ($data) {
            return (isset($data[$key]) && trim($data[$key]) !== '') ? trim($data[$key]) : null;
        };

        // Extract ALL values into named variables (bind_param requires variables by reference)
        $full_name             = $v('full_name');
        $job_title             = $v('job_title');
        $company_name          = $v('company_name');
        $phone                 = $v('phone');
        $alt_phone_number      = $v('alt_phone_number');
        $email                 = $v('email');
        $lead_image            = $v('lead_image');
        $lead_name_bn          = $v('lead_name_bn');
        $father_name_bn        = $v('father_name_bn');
        $mother_name_bn        = $v('mother_name_bn');
        $profession_bn         = $v('profession_bn');
        $nationality           = $v('nationality');
        $religion              = $v('religion');
        $nominee_name          = $v('nominee_name');
        $nomi_father_name      = $v('nomi_father_name');
        $nomi_mother_name      = $v('nomi_mother_name');
        $nomi_profession       = $v('nomi_profession');
        $relation_with_lead    = $v('relation_with_lead');
        $nomi_dob              = $v('nomi_dob');
        $nomi_nid_passport     = $v('nomi_nid_passport');
        $nomi_phone            = $v('nomi_phone');
        $nomi_email            = $v('nomi_email');
        $nomi_present_add      = $v('nomi_present_add');
        $nomi_permanent_address = $v('nomi_permanent_address');
        $nomi_image            = $v('nomi_image');
        $status                = $data['status']       ?? 'active';
        $visibility            = $data['visibility']   ?? 'public';
        $createdBy             = $data['created_by']   ?? null;
        $team_id               = $v('team_id');
        $assigned_id           = $v('assigned_id');
        $uploadedFrom          = $data['uploaded_from'] ?? 'From Company csv';

        // 31 columns: 25 s + status(s) + visibility(s) = 27s, then i(created_by), s(team_id), s(assigned_id), s(uploaded_from) = 31
        $stmt->bind_param(
            "sssssssssssssssssssssssssssiiss",
            $full_name, $job_title, $company_name, $phone, $alt_phone_number, $email,
            $lead_image, $lead_name_bn, $father_name_bn, $mother_name_bn, $profession_bn,
            $nationality, $religion, $nominee_name, $nomi_father_name, $nomi_mother_name,
            $nomi_profession, $relation_with_lead, $nomi_dob, $nomi_nid_passport,
            $nomi_phone, $nomi_email, $nomi_present_add, $nomi_permanent_address, $nomi_image,
            $status, $visibility, $createdBy, $team_id, $assigned_id, $uploadedFrom
        );

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }
        return true;
    }

    public function update($id, $data)
    {
        $old = $this->find($id);
        if (!$old) {
            return false;
        }

        // ======================
        // Nullable field helpers — empty string → null
        // ======================
        $nn = function($val) {
            return (isset($val) && trim((string)$val) !== '') ? $val : null;
        };

        // Date fields (MySQL DATE cannot accept empty string '')
        $dob      = $nn($data['dob']);
        $nomi_dob = $nn($data['nomi_dob']);

        // Other nullable fields
        $full_name            = $nn($data['full_name']);
        $father_name          = $nn($data['father_name']);
        $mother_name          = $nn($data['mother_name']);
        $permanent_address    = $nn($data['permanent_address']);
        $nid_or_passport      = $nn($data['nid_or_passport']);
        $phone                = $nn($data['phone']);
        $alt_phone_number     = $nn($data['alt_phone_number']);
        $email                = $nn($data['email']);
        $address              = $nn($data['address']);
        $designation          = $nn($data['designation']);
        $lead_name_bn         = $nn($data['lead_name_bn']);
        $father_name_bn       = $nn($data['father_name_bn']);
        $mother_name_bn       = $nn($data['mother_name_bn']);
        $identity_type        = $nn($data['identity_type']);
        $permanent_add_bn     = $nn($data['permanent_add_bn']);
        $present_add_bn       = $nn($data['present_add_bn']);
        $profession_bn        = $nn($data['profession_bn']);
        $nationality          = $nn($data['nationality']) ?? 'Bangladeshi';
        $religion             = $nn($data['religion']);
        $nominee_name         = $nn($data['nominee_name']);
        $relation_with_lead   = $nn($data['relation_with_lead']);
        $nomi_father_name     = $nn($data['nomi_father_name']);
        $nomi_mother_name     = $nn($data['nomi_mother_name']);
        $nomi_profession      = $nn($data['nomi_profession']);
        $nomi_phone           = $nn($data['nomi_phone']);
        $nomi_email           = $nn($data['nomi_email']);
        $nomi_nid_passport    = $nn($data['nomi_nid_passport']);
        $nomi_present_add     = $nn($data['nomi_present_add']);
        $nomi_permanent_address = $nn($data['nomi_permanent_address']);
        $description          = $nn($data['description']);
        $created_by           = $nn($data['created_by']);

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
        nomi_permanent_address = ?, nomi_image = ?, description = ?, created_by = ?
        WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        // ======================
        // Bind all strings + id as integer (35 strings + 1 integer)
        // ======================
        $stmt->bind_param(
            str_repeat("s", 35) . "i",
            $full_name,
            $father_name,
            $mother_name,
            $dob,
            $permanent_address,
            $nid_or_passport,
            $phone,
            $alt_phone_number,
            $email,
            $address,
            $designation,
            $lead_name_bn,
            $father_name_bn,
            $mother_name_bn,
            $identity_type,
            $permanent_add_bn,
            $present_add_bn,
            $profession_bn,
            $nationality,
            $religion,
            $lead_image,
            $nominee_name,
            $relation_with_lead,
            $nomi_father_name,
            $nomi_mother_name,
            $nomi_profession,
            $nomi_dob,
            $nomi_phone,
            $nomi_email,
            $nomi_nid_passport,
            $nomi_present_add,
            $nomi_permanent_address,
            $nomi_image,
            $description,
            $created_by,
            $id
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

    public function permanentDelete($leadId)
    {
        $this->db->begin_transaction();

        try {
            // Delete payments associated with bookings for this lead
            $stmt = $this->db->prepare("DELETE FROM lead_payments WHERE booking_id IN (SELECT id FROM lead_bookings WHERE lead_id = ?)");
            $stmt->bind_param("i", $leadId);
            $stmt->execute();
            $stmt->close();

            // Delete lead bookings
            $stmt = $this->db->prepare("DELETE FROM lead_bookings WHERE lead_id = ?");
            $stmt->bind_param("i", $leadId);
            $stmt->execute();
            $stmt->close();

            // Delete lead follow ups
            $stmt = $this->db->prepare("DELETE FROM lead_follow_ups WHERE lead_id = ?");
            $stmt->bind_param("i", $leadId);
            $stmt->execute();
            $stmt->close();

            // Delete lead
            $stmt = $this->db->prepare("DELETE FROM leads WHERE id = ?");
            $stmt->bind_param("i", $leadId);
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
        $stmt2 = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE created_at >= CURDATE() AND deleted_at IS NULL");
        $stmt->execute();
        $stats['total_leads'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt2->execute();
        $stats['today_uploads'] = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;

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
        return $this->getMarketingLeadsFlexible($userId, null, '7days', 'DESC');
    }

    /**
     * Count marketing leads for AJAX pagination (mirrors getMarketingLeadsFlexible filters)
     */
    public function countMarketingLeadsFlexible($teamId = null, $dateFilter = 'all', $search = null)
    {
        $sql = "SELECT COUNT(*) AS total FROM leads l WHERE l.deleted_at IS NULL";

        $params = [];
        $types  = "";

        if ($teamId) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types   .= "i";
        }

        if ($dateFilter === 'today') {
            $sql .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($dateFilter === 'old') {
            $sql .= " AND DATE(l.created_at) < CURDATE()";
        } elseif ($dateFilter === '15days') {
            $sql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
        }

        if (!empty($search)) {
            $sql .= " AND (l.full_name LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR l.campaign_name LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            $types   .= "ssss";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    }

    /**
     * Paginated marketing leads for AJAX (same contact_status logic, adds LIMIT/OFFSET + search)
     */
    public function getMarketingLeadsPaginated($teamId = null, $dateFilter = 'all', $sortOrder = 'DESC', $limit = 10, $offset = 0, $search = null)
    {
        $sql = "
        SELECT
            l.id, l.full_name, l.phone_number, l.email, l.job_title,
            l.platform, l.campaign_name, l.lead_type, l.call_note,
            l.created_at,
            ls.name  AS source_name,
            p.name   AS project_name,
            t.name   AS team_name,
            (SELECT u2.full_name
             FROM users u2
             INNER JOIN employees e2 ON u2.id = e2.user_id
             WHERE e2.team_id = l.team_id AND u2.role_id = 5
             LIMIT 1) AS sales_team_lead_name,
            -- Follow-up count
            COALESCE((
                SELECT COUNT(*) FROM lead_follow_ups lfc
                WHERE lfc.lead_id = l.id AND lfc.deleted_at IS NULL
            ), 0) AS follow_up_count,
            -- Contact status (3 conditions)
            CASE
                WHEN (
                    EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                    OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                    OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                ) THEN 'Contacted'
                ELSE 'Not Contacted'
            END AS contact_status,
            -- Last follow-up type
            lfu.follow_up_type AS last_follow_up_type
        FROM leads l
        LEFT JOIN lead_sources ls ON l.source_type_id = ls.id
        LEFT JOIN projects     p  ON l.project_id = p.id
        LEFT JOIN teams        t  ON l.team_id    = t.id
        LEFT JOIN (
            SELECT lf1.lead_id, lf1.follow_up_type
            FROM lead_follow_ups lf1
            WHERE lf1.id = (
                SELECT MAX(lf2.id) FROM lead_follow_ups lf2
                WHERE lf2.lead_id = lf1.lead_id AND lf2.deleted_at IS NULL
            ) AND lf1.deleted_at IS NULL
        ) lfu ON lfu.lead_id = l.id
        WHERE l.deleted_at IS NULL
        ";

        $params = [];
        $types  = "";

        if ($teamId) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types   .= "i";
        }

        if ($dateFilter === 'today') {
            $sql .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($dateFilter === 'old') {
            $sql .= " AND DATE(l.created_at) < CURDATE()";
        } elseif ($dateFilter === '15days') {
            $sql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
        }

        if (!empty($search)) {
            $sql .= " AND (l.full_name LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR l.campaign_name LIKE ?)";
            $like = "%$search%";
            $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
            $types   .= "ssss";
        }

        $order  = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';
        $sql   .= " ORDER BY l.created_at $order LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types   .= "ii";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Flexible marketing lead fetcher
     * @param int|null $userId
     * @param int|null $teamId
     * @param string $dateFilter 'today', 'old', '7days', 'all'
     * @param string $sortOrder 'ASC', 'DESC'
     */
    public function getMarketingLeadsFlexible($userId = null, $teamId = null, $dateFilter = 'all', $sortOrder = 'DESC')
    {
        $sql = "
        SELECT 
            l.*,
            ls.name AS source_name,
            p.name AS project_name,
            t.name AS team_name,
            (SELECT u.full_name 
             FROM users u 
             INNER JOIN employees e ON u.id = e.user_id 
             WHERE e.team_id = l.team_id AND u.role_id = 5 
             LIMIT 1) AS sales_team_lead_name,
            -- ── Follow-up count (batch badge) ─────────────────────────────
            COALESCE((
                SELECT COUNT(*)
                FROM lead_follow_ups lfc
                WHERE lfc.lead_id = l.id
                  AND lfc.deleted_at IS NULL
            ), 0) AS follow_up_count,
            -- ── Contact status: contacted if ANY of 3 conditions met ───────
            -- 1. lead_follow_ups e record ache
            -- 2. lead_type NULL theke onno kichu te update hoyeche
            -- 3. call_note te value ache
            CASE
                WHEN (
                    EXISTS (
                        SELECT 1 FROM lead_follow_ups lfe
                        WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL
                    )
                    OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                    OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                ) THEN 'Contacted'
                ELSE 'Not Contacted'
            END AS contact_status,
            -- ── Last follow-up type (for display) ─────────────────────────
            lfu.follow_up_type AS last_follow_up_type,
            lfu.created_at AS last_follow_up_at
        FROM leads l
        LEFT JOIN lead_sources ls ON l.source_type_id = ls.id
        LEFT JOIN projects p ON l.project_id = p.id
        LEFT JOIN teams t ON l.team_id = t.id
        LEFT JOIN (
            SELECT lf1.lead_id, lf1.follow_up_type, lf1.created_at
            FROM lead_follow_ups lf1
            WHERE lf1.id = (
                SELECT MAX(lf2.id) FROM lead_follow_ups lf2
                WHERE lf2.lead_id = lf1.lead_id
                AND lf2.deleted_at IS NULL
            )
            AND lf1.deleted_at IS NULL
        ) lfu ON lfu.lead_id = l.id
        WHERE l.deleted_at IS NULL
    ";

        $params = [];
        $types = "";

        // if ($userId) {
        //     $sql .= " AND l.created_by = ?";
        //     $params[] = $userId;
        //     $types .= "i";
        // }

        if ($teamId) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types .= "i";
        }

        // Date filtering
        if ($dateFilter === 'today') {
            $sql .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($dateFilter === 'old') {
            $sql .= " AND DATE(l.created_at) < CURDATE()";
        } elseif ($dateFilter === '15days') {
    $sql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY)";
}
        // elseif ($dateFilter === '7days') {
        //     $sql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        // }

        // Sorting
        $order = ($sortOrder === 'ASC') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY l.created_at $order";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) die($this->db->error);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getLeadsByTeamId($teamId)
    {
        $stmt = $this->db->prepare("
        SELECT 
            l.*,
            ls.name AS source_name,
            p.name AS project_name,
            t.name AS team_name,
            (SELECT u.full_name 
             FROM users u 
             INNER JOIN employees e ON u.id = e.user_id 
             WHERE e.team_id = l.team_id AND u.role_id = 5 
             LIMIT 1) AS sales_team_lead_name
        FROM leads l
        LEFT JOIN lead_sources ls ON l.source_type_id = ls.id
        LEFT JOIN projects p ON l.project_id = p.id
        LEFT JOIN teams t ON l.team_id = t.id
        WHERE l.team_id = ?
          AND l.deleted_at IS NULL
        ORDER BY l.created_at DESC
    ");

        $stmt->bind_param("i", $teamId);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getAllMarketingLeads(int $days = 30)
    {
        $stmt = $this->db->prepare("
        SELECT 
            l.*,
            ls.name AS source_name,
            p.name AS project_name,
            t.name AS team_name,
            (SELECT u.full_name 
             FROM users u 
             INNER JOIN employees e ON u.id = e.user_id 
             WHERE e.team_id = l.team_id AND u.role_id = 5 
             LIMIT 1) AS sales_team_lead_name
        FROM leads l
        LEFT JOIN lead_sources ls ON l.source_type_id = ls.id
        LEFT JOIN projects p ON l.project_id = p.id
        LEFT JOIN teams t ON l.team_id = t.id
        WHERE l.deleted_at IS NULL
          AND (l.created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) OR l.created_at IS NULL)
        ORDER BY l.created_at DESC
    ");

        $stmt->bind_param("i", $days);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get lead counts grouped by raw platform strings for CPL reporting
     */
    public function getMarketingPlatformLeadCounts($monthFilter = null)
    {
        $sql = "
            SELECT 
                LOWER(TRIM(platform)) as raw_platform,
                COUNT(id) as total_leads
            FROM leads 
            WHERE deleted_at IS NULL 
              AND platform IS NOT NULL 
              AND platform != ''
        ";
        
        $params = [];
        $types = "";

        if ($monthFilter) {
            // Month filter is expected in 'YYYY-MM-01' format. We extract 'YYYY-MM' to match the leads
            $yearMonth = substr($monthFilter, 0, 7);
            $sql .= " AND DATE_FORMAT(created_at, '%Y-%m') = ?";
            $params[] = $yearMonth;
            $types .= "s";
        }
        
        $sql .= " GROUP BY raw_platform ORDER BY total_leads DESC";
        
        $stmt = $this->db->prepare($sql);
        if ($monthFilter) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
