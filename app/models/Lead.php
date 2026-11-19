<?php

class Lead extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->ensureDeleteRequestsTable();
    }

    private static ?bool $hasIsAutomatedCol = null;
    private static ?bool $hasDistributeCol = null;

    public function getLeadAutomationColumnStatus(): array
    {
        if (self::$hasIsAutomatedCol === null || self::$hasDistributeCol === null) {
            self::$hasIsAutomatedCol = false;
            self::$hasDistributeCol = false;
            if ($this->db) {
                try {
                    $res = $this->db->query("SHOW COLUMNS FROM `leads` LIKE 'is_automated'");
                    if ($res && $res->num_rows > 0) {
                        self::$hasIsAutomatedCol = true;
                    }
                    $distRes = $this->db->query("SHOW COLUMNS FROM `leads` LIKE 'distribute'");
                    if ($distRes && $distRes->num_rows > 0) {
                        self::$hasDistributeCol = true;
                    }
                } catch (Throwable $t) {}
            }
        }
        return [self::$hasIsAutomatedCol, self::$hasDistributeCol];
    }

    protected static array $tableColumnsCache = [];

    public function hasTableColumn(string $table, string $column): bool
    {
        if (!isset(self::$tableColumnsCache[$table])) {
            self::$tableColumnsCache[$table] = [];
            if ($this->db) {
                try {
                    $res = $this->db->query("SHOW COLUMNS FROM `" . $this->db->real_escape_string($table) . "`");
                    while ($res && $r = $res->fetch_assoc()) {
                        self::$tableColumnsCache[$table][$r['Field']] = true;
                    }
                } catch (Throwable $e) {}
            }
        }
        return isset(self::$tableColumnsCache[$table][$column]);
    }

    public function ensureDeleteRequestsTable()
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        if ($this->db) {
            $this->db->query("CREATE TABLE IF NOT EXISTS `lead_delete_requests` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `lead_id` INT NOT NULL,
              `requested_by` INT NOT NULL,
              `team_id` INT DEFAULT 0,
              `reason` TEXT NOT NULL,
              `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
              `action_by` INT DEFAULT NULL,
              `action_at` DATETIME DEFAULT NULL,
              `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`lead_id`),
              INDEX (`requested_by`),
              INDEX (`team_id`),
              INDEX (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }
    }

    public function ensureLeadsEnumColumns(): void
    {
        static $enumChecked = false;
        if ($enumChecked || !$this->db) {
            return;
        }
        $enumChecked = true;

        try {
            $res = $this->db->query("SHOW COLUMNS FROM `leads` WHERE Field IN ('lead_type', 'lead_quality', 'last_lead_action', 'call_status', 'identity_type', 'religion')");
            $cols = [];
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $cols[$row['Field']] = $row;
                }
            }

            $alterParts = [];
            if (isset($cols['lead_type']) && stripos($cols['lead_type']['Type'], 'Qualification in Progress') === false) {
                $alterParts[] = "MODIFY COLUMN `lead_type` ENUM('No Response','Pending','Qualification in Progress','Potential','High Potential','Visit Scheduled','Visit Completed','Prospect','High Prospect','Will Visit','Visited','Sold','Lead Transferred','Project Transferred','Not Interested','Don''t Call Again','Refund','Future','Invalid Lead','Cold','Hot','Warm','Irrelevant') NULL DEFAULT NULL";
            }
            if (isset($cols['lead_quality']) && stripos($cols['lead_quality']['Type'], 'Potential') === false) {
                $alterParts[] = "MODIFY COLUMN `lead_quality` ENUM('Not Interested','Potential','Prospect','Pending') NULL DEFAULT 'Pending'";
            }
            if (isset($cols['last_lead_action']) && strtoupper($cols['last_lead_action']['Null'] ?? '') === 'NO') {
                $alterParts[] = "MODIFY COLUMN `last_lead_action` ENUM('new','booked','follow up','visited','cancel','contacted') NULL DEFAULT 'new'";
            }
            if (isset($cols['call_status']) && stripos($cols['call_status']['Type'], 'busy') === false) {
                $alterParts[] = "MODIFY COLUMN `call_status` ENUM('not_called','called','received','not_received','busy','no_answer') NULL DEFAULT 'not_called'";
            }
            if (isset($cols['identity_type']) && stripos($cols['identity_type']['Type'], 'Birth Certificate') === false) {
                $alterParts[] = "MODIFY COLUMN `identity_type` ENUM('NID','Passport','Birth Certificate') NULL DEFAULT NULL";
            }
            if (isset($cols['religion']) && stripos($cols['religion']['Type'], "'Hindu'") === false) {
                $alterParts[] = "MODIFY COLUMN `religion` ENUM('Islam','Hinduism','Hindu','Buddhism','Buddhist','Christianity','Christian','Other') NULL DEFAULT NULL";
            }

            if (!empty($alterParts)) {
                $this->db->query("ALTER TABLE `leads` " . implode(", ", $alterParts));
            }
        } catch (Throwable $e) {
            error_log("Lead::ensureLeadsEnumColumns error: " . $e->getMessage());
        }
    }

    public function findDuplicate($name, $phone)
    {
        $sql = "SELECT * FROM leads WHERE full_name = ? AND (phone_number = ? OR phone = ?) AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param("sss", $name, $phone, $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
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
        LEFT JOIN employees AS emp ON emp.user_id = l.assigned_id
        WHERE l.deleted_at IS NULL
          /* employee_status filter removed */
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
                    $sqlQuery .= " AND l.created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-26')";
                    break;
                case 'yearly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
                case 'custom':
                    $fromDate = $_GET['from_date'] ?? '1970-01-01';
                    $toDate = $_GET['to_date'] ?? '2099-12-31';
                    $sqlQuery .= " AND DATE(l.created_at) >= '$fromDate' AND DATE(l.created_at) <= '$toDate'";
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
                        $sqlQuery .= " AND l.created_at >= DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH, '%Y-%m-26') AND l.created_at < DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-26')";
                    } else {
                        $sqlQuery .= " AND l.created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-26')";
                    }
                    break;
                case 'yearly':
                    if ($previous) {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    } else {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    }
                    break;
                case 'custom':
                    if (!$previous) {
                        $fromDate = $_GET['from_date'] ?? '1970-01-01';
                        $toDate = $_GET['to_date'] ?? '2099-12-31';
                        $sqlQuery .= " AND DATE(l.created_at) >= '$fromDate' AND DATE(l.created_at) <= '$toDate'";
                    }
                    break;
            }
        }

        $stmt = $this->db->prepare($sqlQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function countAll($period = null, $roleId = null, $teamId = null)
    {
        $sqlQuery = "
            SELECT COUNT(l.id) AS total
            FROM leads AS l
            LEFT JOIN employees AS emp ON emp.user_id = l.assigned_id
            WHERE l.deleted_at IS NULL
              /* employee_status filter removed */
        ";
        
        $params = [];
        $types = "";
        
        // Handle flexible parameter passing: countAll($period, $teamId) or countAll($period, $roleId, $teamId)
        $actualTeamId = !empty($teamId) ? $teamId : (!empty($roleId) ? $roleId : null);

        if (!empty($actualTeamId)) {
            $sqlQuery .= " AND (l.team_id = ? OR l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id = ?))";
            $params[] = $actualTeamId;
            $params[] = $actualTeamId;
            $types .= "ii";
        }
        if($period) {
            switch ($period) {
                case 'daily':
                    $sqlQuery .= " AND DATE(l.created_at) = CURDATE()";
                    break;
                case 'weekly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'monthly':
                    $sqlQuery .= " AND l.created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-26')";
                    break;
                case 'yearly':
                    $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    break;
                case 'custom':
                    $fromDate = $_GET['from_date'] ?? '1970-01-01';
                    $toDate = $_GET['to_date'] ?? '2099-12-31';
                    $sqlQuery .= " AND DATE(l.created_at) >= '$fromDate' AND DATE(l.created_at) <= '$toDate'";
                    break;
            }
        }
        
        $stmt = $this->db->prepare($sqlQuery);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ? (int)$res['total'] : 0;
    }

    public function countAllByDate($period, $previous = false)
    {
        $sqlQuery = "
            SELECT COUNT(l.id) AS total FROM leads AS l
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
                        $sqlQuery .= " AND l.created_at >= DATE_FORMAT(CURDATE() - INTERVAL 2 MONTH, '%Y-%m-26') AND l.created_at < DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-26')";
                    } else {
                        $sqlQuery .= " AND l.created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-26')";
                    }
                    break;
                case 'yearly':
                    if ($previous) {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR) AND l.created_at < DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    } else {
                        $sqlQuery .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                    }
                    break;
                case 'custom':
                    if (!$previous) {
                        $fromDate = $_GET['from_date'] ?? '1970-01-01';
                        $toDate = $_GET['to_date'] ?? '2099-12-31';
                        $sqlQuery .= " AND DATE(l.created_at) >= '$fromDate' AND DATE(l.created_at) <= '$toDate'";
                    }
                    break;
            }
        }
        
        $stmt = $this->db->prepare($sqlQuery);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ? (int)$res['total'] : 0;
    }

    public function countAllByDateRange($startDate, $endDate)
    {
        if (empty($startDate) || empty($endDate)) {
            return 0;
        }
        $sql = "SELECT COUNT(id) AS total FROM leads WHERE status = 'active' AND deleted_at IS NULL AND created_at BETWEEN ? AND ?";
        $stmt = $this->db->prepare($sql);
        $startStr = $startDate . ' 00:00:00';
        $endStr = $endDate . ' 23:59:59';
        $stmt->bind_param("ss", $startStr, $endStr);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ? (int)$res['total'] : 0;
    }


    /**
     * Return known campaign keywords for a project ID
     */
    public function getProjectKeywords(int $projectId): array
    {
        $map = [
            6  => ['Maple', 'Mapple'],
            9  => ['Crown Pavilion', 'Crown Leads'],
            50 => ['Noble Court'],
            51 => ['Velvet Heights'],
            62 => ['TPSC_Commercial'],
            63 => ['Amberleaf'],
            5  => ['Legacy Residence'],
            68 => ['Smart City'],
            55 => ['Canvas of Happiness', 'Canvas of Happness', 'Canvass'],
            57 => ['Horizon'],
            64 => ['Pearl'],
            67 => ['Lumina'],
            14 => ['Velinor'],
            19 => ['Armonia'],
            16 => ['Ariston'],
            49 => ['Prestige Heights'],
            60 => ['Florence'],
            69 => ['Novus'],
            70 => ['Zenith Heights'],
            61 => ['Serenity'],
            37 => ['Studio Suite 7', 'Studio 7'],
            38 => ['Studio Suite 6'],
            41 => ['Studio Suite 5'],
            42 => ['Studio Suite 4'],
            15 => ['Studio Suite 3'],
            39 => ['Studio Suite 2'],
            40 => ['Studio Suite 1'],
            43 => ['Sunleaf'],
            45 => ['Floral Heaven'],
            46 => ['Green Heaven'],
            47 => ['Harmony Residence'],
            48 => ['Glory Garden'],
            52 => ['Monarch Residence'],
            53 => ['Orchid Residence'],
            54 => ['Green Valley'],
            56 => ['Elite Garden'],
            58 => ['Azure Residence'],
            59 => ['Vertex Garden'],
            65 => ['Green Paradise'],
            27 => ['Sky Point', 'Skypoint'],
            17 => ['South Point', 'Southpoint'],
            21 => ['Dream Palace'],
            22 => ['Lake View Terrace'],
            31 => ['Fortune Residence'],
            32 => ['Luxoria Residence'],
            33 => ['Aurora View'],
            34 => ['Southern Residence'],
            35 => ['Breeze Residence'],
            36 => ['Urban Residence'],
            44 => ['Classic Residence'],
            12 => ['Crown Tower'],
            28 => ['Majesty Tower'],
            29 => ['Imperial Tower'],
            66 => ['TPRC Commercial', 'Royel City Commercial', 'Free Motion'],
        ];

        return $map[$projectId] ?? [];
    }

    /**
     * Build SQL condition for project filtering with fallback to campaign_name keywords
     */
    protected function buildProjectFilterClause(int $projectId, string $tablePrefix = 'l.'): array
    {
        $keywords = $this->getProjectKeywords($projectId);
        $clauses = ["{$tablePrefix}project_id = ?"];
        $params = [$projectId];
        $types = "i";

        if (!empty($keywords)) {
            foreach ($keywords as $kw) {
                $clauses[] = "(({$tablePrefix}project_id IS NULL OR {$tablePrefix}project_id = 0) AND {$tablePrefix}campaign_name LIKE ?)";
                $params[] = "%" . $kw . "%";
                $types .= "s";
            }
        }

        return [
            'sql' => " AND (" . implode(" OR ", $clauses) . ")",
            'params' => $params,
            'types' => $types
        ];
    }

    /**
     * Append team-based SQL filter supporting either a single team_id or an array of team_ids.
     */
    private function appendLeadTeamFilter(string &$sql, array &$params, string &$types, $teamId, bool $forBookingsQuery = false): void
    {
        if (empty($teamId)) {
            return;
        }

        $teamIds = is_array($teamId)
            ? array_values(array_filter(array_map('intval', $teamId), fn($id) => $id !== 0))
            : [(int)$teamId];

        if (empty($teamIds)) {
            $sql .= " AND 1 = 0";
            return;
        }

        $placeholders = implode(',', array_fill(0, count($teamIds), '?'));
        $tTypes = str_repeat('i', count($teamIds));
        $loggedInRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $isGlobalViewer = in_array($loggedInRole, [1, 3, 9, 32, 33, 34, 35], true);
        $loggedInUserId = (int)($_SESSION['user']['id'] ?? 0);

        if ($forBookingsQuery) {
            if (!$isGlobalViewer) {
                $sql .= " AND (
                    (l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND (l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id IN ($placeholders)) OR l.assigned_id = ?))
                    OR ((l.assigned_id IS NULL OR l.assigned_id = 0) AND l.team_id IN ($placeholders))
                    OR lb.sales_by_id = ?
                    OR lb.sales_by_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id IN ($placeholders))
                )";
                foreach ($teamIds as $tid) { $params[] = $tid; }
                $params[] = $loggedInUserId;
                foreach ($teamIds as $tid) { $params[] = $tid; }
                $params[] = $loggedInUserId;
                foreach ($teamIds as $tid) { $params[] = $tid; }
                $types .= $tTypes . 'i' . $tTypes . 'i' . $tTypes;
            } else {
                $sql .= " AND (
                    (l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id IN ($placeholders)))
                    OR ((l.assigned_id IS NULL OR l.assigned_id = 0) AND l.team_id IN ($placeholders))
                    OR lb.sales_by_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id IN ($placeholders))
                )";
                foreach ($teamIds as $tid) { $params[] = $tid; }
                foreach ($teamIds as $tid) { $params[] = $tid; }
                foreach ($teamIds as $tid) { $params[] = $tid; }
                $types .= $tTypes . $tTypes . $tTypes;
            }
            return;
        }

        if (!$isGlobalViewer) {
            $sql .= " AND (
                (l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND (l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id IN ($placeholders)) OR l.assigned_id = ?))
                OR
                (l.team_id IN ($placeholders))
                OR EXISTS (
                    SELECT 1 FROM lead_bookings lb_tm 
                    WHERE lb_tm.lead_id = l.id 
                      AND (lb_tm.sales_by_id = ? OR lb_tm.sales_by_id IN (SELECT u_tm2.id FROM users u_tm2 WHERE u_tm2.team_id IN ($placeholders))) 
                      AND lb_tm.deleted_at IS NULL
                )
            )";
            foreach ($teamIds as $tid) { $params[] = $tid; }
            $params[] = $loggedInUserId;
            foreach ($teamIds as $tid) { $params[] = $tid; }
            $params[] = $loggedInUserId;
            foreach ($teamIds as $tid) { $params[] = $tid; }
            $types .= $tTypes . 'i' . $tTypes . 'i' . $tTypes;
        } else {
            $sql .= " AND (
                (l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id IN ($placeholders)))
                OR
                (l.team_id IN ($placeholders))
                OR EXISTS (
                    SELECT 1 FROM lead_bookings lb_tm 
                    WHERE lb_tm.lead_id = l.id 
                      AND lb_tm.sales_by_id IN (SELECT u_tm2.id FROM users u_tm2 WHERE u_tm2.team_id IN ($placeholders)) 
                      AND lb_tm.deleted_at IS NULL
                )
            )";
            foreach ($teamIds as $tid) { $params[] = $tid; }
            foreach ($teamIds as $tid) { $params[] = $tid; }
            foreach ($teamIds as $tid) { $params[] = $tid; }
            $types .= $tTypes . $tTypes . $tTypes;
        }
    }

    public function countAllWithFilter($year = null, $month = null, $lastAction = null, $roleId = null, $teamId = null, $assigned = null, $assigned_user = null, $date = null, $search = null, $from_date = null, $to_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $date_type = 'created_at')
    {
        $sql = "SELECT COUNT(l.id) AS total_count FROM leads AS l 
                LEFT JOIN users AS u ON l.assigned_id = u.id
                LEFT JOIN employees AS emp ON emp.user_id = l.assigned_id
                WHERE l.deleted_at IS NULL
                  /* employee_status filter removed */";

        $params = [];
        $types  = "";

        // Hide leads that belong to Role 33 and 35's teams from other sales users (like Role 5 and 9)
        $loggedInRole = $_SESSION['user']['role_id'] ?? 0;
        if (!in_array((int)$loggedInRole, [1, 3, 9, 32, 33, 34, 35])) {
            $sql .= " AND (l.team_id IS NULL OR l.team_id = 0 OR l.team_id NOT IN (
                SELECT e.team_id FROM employees e JOIN users u ON u.id = e.user_id WHERE u.role_id IN (33, 35) AND e.team_id IS NOT NULL AND e.team_id > 0
            ))";
        }

        // Hide undistributed automated leads from sales users until approved (distribute = 1)
        // Existing CRM leads, manual leads, etc. (is_automated = 0 or NULL) are ALWAYS visible to sales!
        if (!in_array((int)$loggedInRole, [1, 10, 11])) {
            [$hasAutomatedCol, $hasDistributeCol] = $this->getLeadAutomationColumnStatus();
            if ($hasAutomatedCol && $hasDistributeCol) {
                $sql .= " AND NOT (l.is_automated = 1 AND l.distribute = 0)";
            }
        }

        // TEAM BASED FILTER
        $this->appendLeadTeamFilter($sql, $params, $types, $teamId, false);

        // Determine if we should apply created_at date filter
        $applyCreatedDateFilter = true;
        if (in_array($filter_card, ['total_booked', 'booked', 'today_follow_up', 'pending_follow_ups'])) {
            $applyCreatedDateFilter = false;
        }

        // Date range filter: based on user-selected date_type ('created_at' by default, or 'assigned_at')
        if ($applyCreatedDateFilter) {
            $dateCol = ($date_type === 'assigned_at') 
                ? "(CASE WHEN (l.assigned_at IS NOT NULL AND l.assigned_at > '1970-01-01 00:00:00') THEN l.assigned_at ELSE l.created_at END)" 
                : 'l.created_at';
            
            if (!empty($from_date) && !empty($to_date)) {
                $sql .= " AND DATE($dateCol) BETWEEN ? AND ?";
                $params[] = $from_date;
                $params[] = $to_date;
                $types .= "ss";
            } elseif (!empty($from_date)) {
                $sql .= " AND DATE($dateCol) >= ?";
                $params[] = $from_date;
                $types .= "s";
            } elseif (!empty($to_date)) {
                $sql .= " AND DATE($dateCol) <= ?";
                $params[] = $to_date;
                $types .= "s";
            } elseif (!empty($date)) {
                $sql .= " AND DATE($dateCol) = ?";
                $params[] = $date;
                $types .= "s";
            } else {
                // Year filter
                if (!empty($year)) {
                    $sql .= " AND YEAR($dateCol) = ?";
                    $params[] = $year;
                    $types .= "i";
                }
                // Month filter
                if (!empty($month)) {
                    $sql .= " AND MONTH($dateCol) = ?";
                    $params[] = $month;
                    $types .= "i";
                }
            }
        }

        //  Last action filter
        if (!empty($lastAction)) {
            if (in_array(strtolower($lastAction), ['irrelevant', 'invalid_lead', 'invalid lead'])) {
                $sql .= " AND (l.lead_type IN ('Invalid Lead', 'Irrelevant') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status IN ('Invalid Lead', 'Irrelevant') AND lf_sub.deleted_at IS NULL))";
            } elseif (in_array(strtolower($lastAction), ['no_response', 'no response'])) {
                $sql .= " AND (
                    CASE 
                        WHEN EXISTS (SELECT 1 FROM lead_follow_ups lf_chk WHERE lf_chk.lead_id = l.id AND lf_chk.deleted_at IS NULL) THEN
                            EXISTS (
                                SELECT 1 FROM lead_follow_ups lf_last
                                WHERE lf_last.id = (
                                    SELECT MAX(lf_max.id) FROM lead_follow_ups lf_max 
                                    WHERE lf_max.lead_id = l.id AND lf_max.deleted_at IS NULL
                                )
                                AND (
                                    lf_last.lead_status = 'No Response'
                                    OR lf_last.follow_up_type IN ('Number Off', 'Wrong Number', 'Invalid Number')
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%number off%'
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%no response%'
                                    OR LOWER(COALESCE(lf_last.lead_status, '')) LIKE '%no response%'
                                    OR (COALESCE(lf_last.lead_status, '') = '' AND l.lead_type = 'No Response')
                                )
                            )
                        ELSE
                            (
                                l.lead_type = 'No Response'
                                OR l.call_status = 'not_received'
                                OR LOWER(COALESCE(l.last_lead_action, '')) IN ('no response', 'not received')
                            )
                    END
                )";
            } elseif (strtolower($lastAction) === 'visited') {
                $sql .= " AND (LOWER(l.last_lead_action) = 'visited' OR l.lead_type IN ('Visit Completed', 'Visited') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_v WHERE lf_v.lead_id = l.id AND lf_v.deleted_at IS NULL AND (lf_v.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf_v.follow_up_type) = 'visit')))";
            } else {
                $sql .= " AND l.last_lead_action = ?";
                $params[] = $lastAction;
                $types .= "s";
            }
        }

        // Assigned filter
        if (isset($assigned) && $assigned !== '') {
            if ($assigned === 'assigned') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND u.id IS NOT NULL";
            } elseif ($assigned === 'unassigned') {
                $sql .= " AND (l.assigned_id IS NULL OR l.assigned_id = 0 OR u.id IS NULL)";
            }
        }
        
        // Assigned User filter (Primary assigned or closed booking salesperson)
        if (isset($assigned_user) && $assigned_user !== '') {
            $sql .= " AND (l.assigned_id = ? OR EXISTS (SELECT 1 FROM lead_bookings lb_sel WHERE lb_sel.lead_id = l.id AND lb_sel.sales_by_id = ? AND lb_sel.deleted_at IS NULL))";
            $params[] = (int)$assigned_user;
            $params[] = (int)$assigned_user;
            $types .= "ii";
        }

        // Assigned Hours filter (Last 1, 2, 3, 4, 5, 6, etc. hours)
        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (l.assigned_at IS NOT NULL AND l.assigned_at != '0000-00-00 00:00:00' AND l.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((l.assigned_at IS NULL OR l.assigned_at = '0000-00-00 00:00:00') AND l.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
            )";
            $params[] = $hrs;
            $params[] = $hrs;
            $types .= "ii";
        }

        // Search Filter
        if (!empty($search)) {
            $sql .= " AND (l.full_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR l.company_name LIKE ?)";
            $searchParam = "%" . trim($search) . "%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sssss";
        }

        // Quick Filter Card
        if (!empty($filter_card)) {
            if ($filter_card === 'total_booked' || $filter_card === 'booked') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) BETWEEN ? AND ? OR DATE(lb_sub.created_at) BETWEEN ? AND ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ssss";
                } elseif (!empty($from_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) >= ? OR DATE(lb_sub.created_at) >= ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $from_date;
                    $types .= "ss";
                } elseif (!empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) <= ? OR DATE(lb_sub.created_at) <= ?)
                    )";
                    $params[] = $to_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } elseif (!empty($year) || !empty($month)) {
                    $ySub = !empty($year) ? " AND (YEAR(lb_sub.booking_date) = " . (int)$year . " OR YEAR(lb_sub.created_at) = " . (int)$year . ")" : "";
                    $mSub = !empty($month) ? " AND (MONTH(lb_sub.booking_date) = " . (int)$month . " OR MONTH(lb_sub.created_at) = " . (int)$month . ")" : "";
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked')
                          $ySub $mSub
                    )";
                } else {
                    $sql .= " AND (l.last_lead_action = 'booked' OR EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked')
                    ))";
                }
            } elseif ($filter_card === 'today_follow_up') {
                $sql .= " AND l.assigned_id > 0
                          AND COALESCE(l.lead_type, '') NOT IN ('Not Interested', 'Don\'t Call Again', 'Invalid Lead', 'Irrelevant', 'Refund', 'Lead Transferred', 'Project Transferred', 'Sold')
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_bookings b_sub 
                              WHERE b_sub.lead_id = l.id 
                                AND b_sub.deleted_at IS NULL 
                                AND b_sub.booking_status IN ('booked', 'completed')
                          )";
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) = CURDATE()
                    )";
                }
            } elseif ($filter_card === 'pending_follow_ups') {
                $sql .= " AND l.assigned_id > 0
                          AND COALESCE(l.lead_type, '') NOT IN ('Not Interested', 'Don\'t Call Again', 'Invalid Lead', 'Irrelevant', 'Refund', 'Lead Transferred', 'Project Transferred', 'Sold')
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_bookings b_sub 
                              WHERE b_sub.lead_id = l.id 
                                AND b_sub.deleted_at IS NULL 
                                AND b_sub.booking_status IN ('booked', 'completed')
                          )";
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    ) AND NOT EXISTS (
                        SELECT 1 FROM lead_follow_ups done_sub
                        WHERE done_sub.lead_id = l.id
                          AND (DATE(done_sub.created_at) BETWEEN ? AND ? OR done_sub.follow_up_date BETWEEN ? AND ?)
                          AND done_sub.deleted_at IS NULL
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ssssss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) = CURDATE()
                    ) AND NOT EXISTS (
                        SELECT 1 FROM lead_follow_ups done_sub
                        WHERE done_sub.lead_id = l.id
                          AND (DATE(done_sub.created_at) = CURDATE() OR done_sub.follow_up_date = CURDATE())
                          AND done_sub.deleted_at IS NULL
                    )";
                }
            } elseif ($filter_card === 'hot_leads' || $filter_card === 'high_potential') {
                $sql .= " AND (l.lead_type IN ('High Potential', 'Hot', 'High Prospect') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status IN ('High Potential', 'Hot', 'High Prospect') AND lf_sub.deleted_at IS NULL))";
            } elseif ($filter_card === 'contacted_leads' || $filter_card === 'contacted') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND (
                    l.last_lead_action IN ('contacted', 'follow up', 'booked', 'visited')
                    OR l.call_status IN ('called', 'received')
                    OR (l.communication_type IS NOT NULL AND l.communication_type != '' AND LOWER(l.communication_type) != 'not select')
                    OR EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                    )
                    OR EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub
                        WHERE lb_sub.lead_id = l.id
                          AND lb_sub.deleted_at IS NULL
                    )
                )";
            } elseif ($filter_card === 'not_contacted_leads' || $filter_card === 'not_contacted' || $filter_card === 'not_called') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0
                          AND (l.last_lead_action IS NULL OR l.last_lead_action NOT IN ('contacted', 'follow up', 'booked', 'visited'))
                          AND (l.call_status IS NULL OR l.call_status NOT IN ('called', 'received'))
                          AND (l.communication_type IS NULL OR l.communication_type = '' OR LOWER(l.communication_type) = 'not select')
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_follow_ups lf_sub
                              WHERE lf_sub.lead_id = l.id
                                AND lf_sub.deleted_at IS NULL
                          )
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_bookings lb_sub
                              WHERE lb_sub.lead_id = l.id
                                AND lb_sub.deleted_at IS NULL
                          )";
            } elseif ($filter_card === 'assigned_leads') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND u.id IS NOT NULL";
            } elseif ($filter_card === 'not_assigned_leads') {
                $sql .= " AND (l.assigned_id IS NULL OR l.assigned_id = 0 OR u.id IS NULL)";
            } elseif ($filter_card === 'has_follow_up') {
                $sql .= " AND EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL)";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (l.lead_type IN ('Invalid Lead', 'Irrelevant') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status IN ('Invalid Lead', 'Irrelevant') AND lf_sub.deleted_at IS NULL))";
            } elseif ($filter_card === 'no_response' || $filter_card === 'no_response_leads') {
                $sql .= " AND (
                    CASE 
                        WHEN EXISTS (SELECT 1 FROM lead_follow_ups lf_chk WHERE lf_chk.lead_id = l.id AND lf_chk.deleted_at IS NULL) THEN
                            EXISTS (
                                SELECT 1 FROM lead_follow_ups lf_last
                                WHERE lf_last.id = (
                                    SELECT MAX(lf_max.id) FROM lead_follow_ups lf_max 
                                    WHERE lf_max.lead_id = l.id AND lf_max.deleted_at IS NULL
                                )
                                AND (
                                    lf_last.lead_status = 'No Response'
                                    OR lf_last.follow_up_type IN ('Number Off', 'Wrong Number', 'Invalid Number')
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%number off%'
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%no response%'
                                    OR LOWER(COALESCE(lf_last.lead_status, '')) LIKE '%no response%'
                                    OR (COALESCE(lf_last.lead_status, '') = '' AND l.lead_type = 'No Response')
                                )
                            )
                        ELSE
                            (
                                l.lead_type = 'No Response'
                                OR l.call_status = 'not_received'
                                OR LOWER(COALESCE(l.last_lead_action, '')) IN ('no response', 'not received')
                            )
                    END
                )";
            } elseif ($filter_card === 'delete_requests' || $filter_card === 'delete_request') {
                $loggedInRole = $_SESSION['user']['role_id'] ?? 0;
                $loggedInUserId = $_SESSION['user']['id'] ?? 0;
                if (in_array((int)$loggedInRole, [1, 3, 9, 32, 33, 34, 35])) {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_delete_requests ldr WHERE ldr.lead_id = l.id AND ldr.status = 'pending')";
                } elseif (!empty($teamId)) {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_delete_requests ldr WHERE ldr.lead_id = l.id AND ldr.status = 'pending' AND (ldr.team_id = ? OR l.team_id = ? OR ldr.requested_by = ? OR l.assigned_id = ? OR l.created_by = ? OR l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id = ?)))";
                    $params[] = $teamId;
                    $params[] = $teamId;
                    $params[] = $loggedInUserId;
                    $params[] = $loggedInUserId;
                    $params[] = $loggedInUserId;
                    $params[] = $teamId;
                    $types .= "iiiiii";
                } else {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_delete_requests ldr WHERE ldr.lead_id = l.id AND ldr.status = 'pending')";
                }
            } elseif (in_array($filter_card, ['potential_leads', 'potential'])) {
                $sql .= " AND (
                    COALESCE(
                        NULLIF((SELECT lf_sub.lead_status FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL ORDER BY lf_sub.id DESC LIMIT 1), ''),
                        l.lead_type
                    ) IN ('Potential', 'Prospect', 'Warm')
                )";
            } elseif (in_array($filter_card, ['visit_scheduled', 'visit_scheduled_leads'])) {
                $sql .= " AND (
                    COALESCE(
                        NULLIF((SELECT lf_sub.lead_status FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL ORDER BY lf_sub.id DESC LIMIT 1), ''),
                        l.lead_type
                    ) IN ('Visit Scheduled', 'Will Visit')
                )";
            } elseif (in_array($filter_card, ['visited_leads', 'visited', 'visit_completed'])) {
                $sql .= " AND (
                    COALESCE(
                        NULLIF((SELECT lf_sub.lead_status FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL ORDER BY lf_sub.id DESC LIMIT 1), ''),
                        l.lead_type
                    ) IN ('Visit Completed', 'Visited')
                )";
            } elseif (in_array($filter_card, ['interested_other_location', 'interested_in_other_location', 'other_location'])) {
                $sql .= " AND (
                    COALESCE(
                        NULLIF((SELECT lf_sub.lead_status FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL ORDER BY lf_sub.id DESC LIMIT 1), ''),
                        l.lead_type
                    ) = 'Interested in Other Location'
                )";
            }
        }

        if (!empty($projectId)) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'l.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types .= $projClause['types'];
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

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->ensureDeleteRequestsTable();
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("LeadModel countAllWithFilter prepare failed: " . $this->db->error);
                return 0;
            }
        }
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

    /**
     * Count total booking units/contracts from lead_bookings matching current filters.
     * This aligns the "Total Booked" count on Lead Lists with Sales Performance and Sales Dashboard (e.g. 1,647 bookings).
     */
    public function countBookingsWithFilter($year = null, $month = null, $roleId = null, $teamId = null, $assigned = null, $assigned_user = null, $from_date = null, $to_date = null, $projectId = null, $projectCategoryId = null, $country = null, $date_type = 'created_at')
    {
        $sql = "SELECT COUNT(lb.id) AS total_count 
                FROM lead_bookings AS lb
                JOIN leads AS l ON l.id = lb.lead_id
                WHERE lb.deleted_at IS NULL 
                  AND lb.booking_status IN ('completed', 'booked')
                  AND l.deleted_at IS NULL";

        $params = [];
        $types  = "";

        // Team filter: check assigned executive's team, or closing sales executive's team, or unassigned lead's team
        $this->appendLeadTeamFilter($sql, $params, $types, $teamId, true);

        // Assigned user filter
        if (!empty($assigned_user)) {
            $sql .= " AND (l.assigned_id = ? OR lb.sales_by_id = ?)";
            $params[] = (int)$assigned_user;
            $params[] = (int)$assigned_user;
            $types .= "ii";
        }

        // Date filter on booking_date or created_at
        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) BETWEEN ? AND ? OR DATE(lb.created_at) BETWEEN ? AND ?)";
            $params[] = $from_date;
            $params[] = $to_date;
            $params[] = $from_date;
            $params[] = $to_date;
            $types .= "ssss";
        } elseif (!empty($from_date)) {
            $sql .= " AND (DATE(lb.booking_date) >= ? OR DATE(lb.created_at) >= ?)";
            $params[] = $from_date;
            $params[] = $from_date;
            $types .= "ss";
        } elseif (!empty($to_date)) {
            $sql .= " AND (DATE(lb.booking_date) <= ? OR DATE(lb.created_at) <= ?)";
            $params[] = $to_date;
            $params[] = $to_date;
            $types .= "ss";
        } elseif (!empty($year) || !empty($month)) {
            if (!empty($year)) {
                $sql .= " AND (YEAR(lb.booking_date) = ? OR YEAR(lb.created_at) = ?)";
                $params[] = (int)$year;
                $params[] = (int)$year;
                $types .= "ii";
            }
            if (!empty($month)) {
                $sql .= " AND (MONTH(lb.booking_date) = ? OR MONTH(lb.created_at) = ?)";
                $params[] = (int)$month;
                $params[] = (int)$month;
                $types .= "ii";
            }
        }

        // Project filter
        if (!empty($projectId)) {
            $sql .= " AND (lb.project_id = ? OR l.project_id = ?)";
            $params[] = (int)$projectId;
            $params[] = (int)$projectId;
            $types .= "ii";
        }

        // Project category filter
        if (!empty($projectCategoryId)) {
            $sql .= " AND l.project_category_id = ?";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
        }

        // Country filter
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

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("LeadModel countBookingsWithFilter prepare failed: " . $this->db->error);
            return 0;
        }
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

    public function getAllWithFilter($year = null, $month = null, $lastAction = null, $roleId = null, $teamId = null, $assigned = null, $assigned_user = null, $date = null, $limit = null, $offset = null, $search = null, $from_date = null, $to_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $date_type = 'created_at')
    {
        // Optimized query: server e large dataset (3000+) handle korte
        // Correlated subquery replace kora hoyeche simpler LEFT JOIN diye
        $sql = "SELECT 
        l.*,
        u.full_name AS agent_name,
        u_assigned_by.full_name AS team_assigner_name,
        COALESCE(u_assigned_by.full_name, u_by.full_name, 'System') AS assigned_by_name,
        u_by.full_name AS creator_name,
        u_appr.full_name AS approved_by_name,
        t.name AS team_name,
        COALESCE(p.name, lp.name) AS project_name,
        lpc.name AS project_category_name,
        lf.next_action,
        lf.follow_up_type,
        lf.next_follow_up_date,
        lf.discussion_note,
        lf.last_follow_up_lead_status,
        lf.last_follow_up_interested_location,
        ldr.delete_request_reason,
        ldr.delete_requested_at,
        ldr.delete_requested_by_name
    FROM leads AS l
    LEFT JOIN users AS u ON l.assigned_id = u.id
    LEFT JOIN users AS u_assigned_by ON l.assigned_by = u_assigned_by.id
    LEFT JOIN users AS u_by ON l.created_by = u_by.id
    LEFT JOIN users AS u_appr ON l.approved_by = u_appr.id
    LEFT JOIN teams AS t ON l.team_id = t.id
    LEFT JOIN employees AS emp ON emp.user_id = l.assigned_id
    LEFT JOIN (
        SELECT lf1.lead_id, lf1.next_action, lf1.follow_up_type, lf1.next_follow_up_date, lf1.discussion_note, lf1.lead_status AS last_follow_up_lead_status, lf1.interested_location_name AS last_follow_up_interested_location
        FROM lead_follow_ups lf1
        WHERE lf1.id = (
            SELECT MAX(lf2.id) FROM lead_follow_ups lf2
            WHERE lf2.lead_id = lf1.lead_id AND lf2.deleted_at IS NULL
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
    LEFT JOIN (
        SELECT ldr1.lead_id, ldr1.reason AS delete_request_reason, ldr1.created_at AS delete_requested_at, u_ldr.full_name AS delete_requested_by_name
        FROM lead_delete_requests ldr1
        LEFT JOIN users u_ldr ON u_ldr.id = ldr1.requested_by
        WHERE ldr1.status = 'pending' AND ldr1.id = (
            SELECT MAX(ldr2.id) FROM lead_delete_requests ldr2 WHERE ldr2.lead_id = ldr1.lead_id AND ldr2.status = 'pending'
        )
    ) ldr ON ldr.lead_id = l.id
    LEFT JOIN projects AS p ON lb.project_id = p.id
    LEFT JOIN projects AS lp ON l.project_id = lp.id
    LEFT JOIN project_categories AS lpc ON l.project_category_id = lpc.id
    WHERE l.deleted_at IS NULL
      /* employee_status filter removed */
    ";

        //  IMPORTANT: declare first
        $params = [];
        $types  = "";

        // Hide leads that belong to Role 33 and 35's teams from other sales users (like Role 5 and 9)
        $loggedInRole = $_SESSION['user']['role_id'] ?? 0;
        if (!in_array((int)$loggedInRole, [1, 3, 9, 32, 33, 34, 35])) {
            $sql .= " AND (l.team_id IS NULL OR l.team_id = 0 OR l.team_id NOT IN (
                SELECT e.team_id FROM employees e JOIN users u ON u.id = e.user_id WHERE u.role_id IN (33, 35) AND e.team_id IS NOT NULL AND e.team_id > 0
            ))";
        }

        // Hide undistributed automated leads from sales users until approved (distribute = 1)
        // Existing CRM leads, manual leads, etc. (is_automated = 0 or NULL) are ALWAYS visible to sales!
        if (!in_array((int)$loggedInRole, [1, 10, 11])) {
            [$hasAutomatedCol, $hasDistributeCol] = $this->getLeadAutomationColumnStatus();
            if ($hasAutomatedCol && $hasDistributeCol) {
                $sql .= " AND NOT (l.is_automated = 1 AND l.distribute = 0)";
            }
        }

        // TEAM BASED FILTER
        $this->appendLeadTeamFilter($sql, $params, $types, $teamId, false);

        // Determine if we should apply created_at date filter
        $applyCreatedDateFilter = true;
        if (in_array($filter_card, ['total_booked', 'booked', 'today_follow_up', 'pending_follow_ups'])) {
            $applyCreatedDateFilter = false;
        }

        // Date range filter: based on user-selected date_type ('created_at' by default, or 'assigned_at')
        if ($applyCreatedDateFilter) {
            $dateCol = ($date_type === 'assigned_at') 
                ? "(CASE WHEN (l.assigned_at IS NOT NULL AND l.assigned_at > '1970-01-01 00:00:00') THEN l.assigned_at ELSE l.created_at END)" 
                : 'l.created_at';

            if (!empty($from_date) && !empty($to_date)) {
                $sql .= " AND DATE($dateCol) BETWEEN ? AND ?";
                $params[] = $from_date;
                $params[] = $to_date;
                $types .= "ss";
            } elseif (!empty($from_date)) {
                $sql .= " AND DATE($dateCol) >= ?";
                $params[] = $from_date;
                $types .= "s";
            } elseif (!empty($to_date)) {
                $sql .= " AND DATE($dateCol) <= ?";
                $params[] = $to_date;
                $types .= "s";
            } elseif (!empty($date)) {
                $sql .= " AND DATE($dateCol) = ?";
                $params[] = $date;
                $types .= "s";
            } else {
                // Year filter
                if (!empty($year)) {
                    $sql .= " AND YEAR($dateCol) = ?";
                    $params[] = $year;
                    $types .= "i";
                }
                // Month filter
                if (!empty($month)) {
                    $sql .= " AND MONTH($dateCol) = ?";
                    $params[] = $month;
                    $types .= "i";
                }
            }
        }

        //  Last action filter
        if (!empty($lastAction)) {
            if (in_array(strtolower($lastAction), ['irrelevant', 'invalid_lead', 'invalid lead'])) {
                $sql .= " AND (l.lead_type IN ('Invalid Lead', 'Irrelevant') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status IN ('Invalid Lead', 'Irrelevant') AND lf_sub.deleted_at IS NULL))";
            } elseif (in_array(strtolower($lastAction), ['no_response', 'no response'])) {
                $sql .= " AND (
                    CASE 
                        WHEN EXISTS (SELECT 1 FROM lead_follow_ups lf_chk WHERE lf_chk.lead_id = l.id AND lf_chk.deleted_at IS NULL) THEN
                            EXISTS (
                                SELECT 1 FROM lead_follow_ups lf_last
                                WHERE lf_last.id = (
                                    SELECT MAX(lf_max.id) FROM lead_follow_ups lf_max 
                                    WHERE lf_max.lead_id = l.id AND lf_max.deleted_at IS NULL
                                )
                                AND (
                                    lf_last.lead_status = 'No Response'
                                    OR lf_last.follow_up_type IN ('Number Off', 'Wrong Number', 'Invalid Number')
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%number off%'
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%no response%'
                                    OR LOWER(COALESCE(lf_last.lead_status, '')) LIKE '%no response%'
                                    OR (COALESCE(lf_last.lead_status, '') = '' AND l.lead_type = 'No Response')
                                )
                            )
                        ELSE
                            (
                                l.lead_type = 'No Response'
                                OR l.call_status = 'not_received'
                                OR LOWER(COALESCE(l.last_lead_action, '')) IN ('no response', 'not received')
                            )
                    END
                )";
            } elseif (strtolower($lastAction) === 'visited') {
                $sql .= " AND (LOWER(l.last_lead_action) = 'visited' OR l.lead_type IN ('Visit Completed', 'Visited') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_v WHERE lf_v.lead_id = l.id AND lf_v.deleted_at IS NULL AND (lf_v.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf_v.follow_up_type) = 'visit')))";
            } else {
                $sql .= " AND l.last_lead_action = ?";
                $params[] = $lastAction;
                $types .= "s";
            }
        }

        // Assigned filter
        if (isset($assigned) && $assigned !== '') {
            if ($assigned === 'assigned') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND u.id IS NOT NULL";
            } elseif ($assigned === 'unassigned') {
                $sql .= " AND (l.assigned_id IS NULL OR l.assigned_id = 0 OR u.id IS NULL)";
            }
        }
        // Assigned User filter (Primary assigned or closed booking salesperson)
        if (isset($assigned_user) && $assigned_user !== '') {
            $sql .= " AND (l.assigned_id = ? OR EXISTS (SELECT 1 FROM lead_bookings lb_sel WHERE lb_sel.lead_id = l.id AND lb_sel.sales_by_id = ? AND lb_sel.deleted_at IS NULL))";
            $params[] = (int)$assigned_user;
            $params[] = (int)$assigned_user;
            $types .= "ii";
        }

        // Assigned Hours filter (Last 1, 2, 3, 4, 5, 6, etc. hours)
        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (l.assigned_at IS NOT NULL AND l.assigned_at != '0000-00-00 00:00:00' AND l.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((l.assigned_at IS NULL OR l.assigned_at = '0000-00-00 00:00:00') AND l.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
            )";
            $params[] = $hrs;
            $params[] = $hrs;
            $types .= "ii";
        }

        // Search Filter
        if (!empty($search)) {
            $sql .= " AND (l.full_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ? OR l.email LIKE ? OR l.company_name LIKE ?)";
            $searchParam = "%" . trim($search) . "%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sssss";
        }

        // Quick Filter Card
        if (!empty($filter_card)) {
            if ($filter_card === 'total_booked' || $filter_card === 'booked') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) BETWEEN ? AND ? OR DATE(lb_sub.created_at) BETWEEN ? AND ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ssss";
                } elseif (!empty($from_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) >= ? OR DATE(lb_sub.created_at) >= ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $from_date;
                    $types .= "ss";
                } elseif (!empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) <= ? OR DATE(lb_sub.created_at) <= ?)
                    )";
                    $params[] = $to_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } elseif (!empty($year) || !empty($month)) {
                    $ySub = !empty($year) ? " AND (YEAR(lb_sub.booking_date) = " . (int)$year . " OR YEAR(lb_sub.created_at) = " . (int)$year . ")" : "";
                    $mSub = !empty($month) ? " AND (MONTH(lb_sub.booking_date) = " . (int)$month . " OR MONTH(lb_sub.created_at) = " . (int)$month . ")" : "";
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked')
                          $ySub $mSub
                    )";
                } else {
                    $sql .= " AND (l.last_lead_action = 'booked' OR EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = l.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked')
                    ))";
                }
            } elseif ($filter_card === 'today_follow_up') {
                $sql .= " AND l.assigned_id > 0
                          AND COALESCE(l.lead_type, '') NOT IN ('Not Interested', 'Don\'t Call Again', 'Invalid Lead', 'Irrelevant', 'Refund', 'Lead Transferred', 'Project Transferred', 'Sold')
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_bookings b_sub 
                              WHERE b_sub.lead_id = l.id 
                                AND b_sub.deleted_at IS NULL 
                                AND b_sub.booking_status IN ('booked', 'completed')
                          )";
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) = CURDATE()
                    )";
                }
            } elseif ($filter_card === 'pending_follow_ups') {
                $sql .= " AND l.assigned_id > 0
                          AND COALESCE(l.lead_type, '') NOT IN ('Not Interested', 'Don\'t Call Again', 'Invalid Lead', 'Irrelevant', 'Refund', 'Lead Transferred', 'Project Transferred', 'Sold')
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_bookings b_sub 
                              WHERE b_sub.lead_id = l.id 
                                AND b_sub.deleted_at IS NULL 
                                AND b_sub.booking_status IN ('booked', 'completed')
                          )";
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    ) AND NOT EXISTS (
                        SELECT 1 FROM lead_follow_ups done_sub
                        WHERE done_sub.lead_id = l.id
                          AND (DATE(done_sub.created_at) BETWEEN ? AND ? OR done_sub.follow_up_date BETWEEN ? AND ?)
                          AND done_sub.deleted_at IS NULL
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ssssss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        JOIN (SELECT lead_id, MAX(id) AS mid FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id) m ON m.mid = lf_sub.id
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND DATE(lf_sub.next_follow_up_date) = CURDATE()
                    ) AND NOT EXISTS (
                        SELECT 1 FROM lead_follow_ups done_sub
                        WHERE done_sub.lead_id = l.id
                          AND (DATE(done_sub.created_at) = CURDATE() OR done_sub.follow_up_date = CURDATE())
                          AND done_sub.deleted_at IS NULL
                    )";
                }
            } elseif ($filter_card === 'hot_leads' || $filter_card === 'high_potential') {
                $sql .= " AND (l.lead_type IN ('High Potential', 'Hot', 'High Prospect') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status IN ('High Potential', 'Hot', 'High Prospect') AND lf_sub.deleted_at IS NULL))";
            } elseif ($filter_card === 'contacted_leads' || $filter_card === 'contacted') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND (
                    l.last_lead_action IN ('contacted', 'follow up', 'booked', 'visited')
                    OR l.call_status IN ('called', 'received')
                    OR (l.communication_type IS NOT NULL AND l.communication_type != '' AND LOWER(l.communication_type) != 'not select')
                    OR EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                    )
                    OR EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub
                        WHERE lb_sub.lead_id = l.id
                          AND lb_sub.deleted_at IS NULL
                    )
                )";
            } elseif ($filter_card === 'not_contacted_leads' || $filter_card === 'not_contacted' || $filter_card === 'not_called') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0
                          AND (l.last_lead_action IS NULL OR l.last_lead_action NOT IN ('contacted', 'follow up', 'booked', 'visited'))
                          AND (l.call_status IS NULL OR l.call_status NOT IN ('called', 'received'))
                          AND (l.communication_type IS NULL OR l.communication_type = '' OR LOWER(l.communication_type) = 'not select')
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_follow_ups lf_sub
                              WHERE lf_sub.lead_id = l.id
                                AND lf_sub.deleted_at IS NULL
                          )
                          AND NOT EXISTS (
                              SELECT 1 FROM lead_bookings lb_sub
                              WHERE lb_sub.lead_id = l.id
                                AND lb_sub.deleted_at IS NULL
                          )";
            } elseif ($filter_card === 'assigned_leads') {
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND u.id IS NOT NULL";
            } elseif ($filter_card === 'not_assigned_leads') {
                $sql .= " AND (l.assigned_id IS NULL OR l.assigned_id = 0 OR u.id IS NULL)";
            } elseif ($filter_card === 'has_follow_up') {
                $sql .= " AND EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL)";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (l.lead_type IN ('Invalid Lead', 'Irrelevant') OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status IN ('Invalid Lead', 'Irrelevant') AND lf_sub.deleted_at IS NULL))";
            } elseif ($filter_card === 'no_response' || $filter_card === 'no_response_leads') {
                $sql .= " AND (
                    CASE 
                        WHEN EXISTS (SELECT 1 FROM lead_follow_ups lf_chk WHERE lf_chk.lead_id = l.id AND lf_chk.deleted_at IS NULL) THEN
                            EXISTS (
                                SELECT 1 FROM lead_follow_ups lf_last
                                WHERE lf_last.id = (
                                    SELECT MAX(lf_max.id) FROM lead_follow_ups lf_max 
                                    WHERE lf_max.lead_id = l.id AND lf_max.deleted_at IS NULL
                                )
                                AND (
                                    lf_last.lead_status = 'No Response'
                                    OR lf_last.follow_up_type IN ('Number Off', 'Wrong Number', 'Invalid Number')
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%number off%'
                                    OR LOWER(COALESCE(lf_last.follow_up_type, '')) LIKE '%no response%'
                                    OR LOWER(COALESCE(lf_last.lead_status, '')) LIKE '%no response%'
                                    OR (COALESCE(lf_last.lead_status, '') = '' AND l.lead_type = 'No Response')
                                )
                            )
                        ELSE
                            (
                                l.lead_type = 'No Response'
                                OR l.call_status = 'not_received'
                                OR LOWER(COALESCE(l.last_lead_action, '')) IN ('no response', 'not received')
                            )
                    END
                )";
            } elseif ($filter_card === 'delete_requests' || $filter_card === 'delete_request') {
                $loggedInRole = $_SESSION['user']['role_id'] ?? 0;
                $loggedInUserId = $_SESSION['user']['id'] ?? 0;
                if (in_array((int)$loggedInRole, [1, 3, 9, 32, 33, 34, 35])) {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_delete_requests ldr WHERE ldr.lead_id = l.id AND ldr.status = 'pending')";
                } elseif (!empty($teamId)) {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_delete_requests ldr WHERE ldr.lead_id = l.id AND ldr.status = 'pending' AND (ldr.team_id = ? OR l.team_id = ? OR ldr.requested_by = ? OR l.assigned_id = ? OR l.created_by = ? OR l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id = ?)))";
                    $params[] = $teamId;
                    $params[] = $teamId;
                    $params[] = $loggedInUserId;
                    $params[] = $loggedInUserId;
                    $params[] = $loggedInUserId;
                    $params[] = $teamId;
                    $types .= "iiiiii";
                } else {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_delete_requests ldr WHERE ldr.lead_id = l.id AND ldr.status = 'pending')";
                }
            } elseif (in_array($filter_card, ['potential_leads', 'potential'])) {
                $sql .= " AND (COALESCE(NULLIF(lf.last_follow_up_lead_status, ''), l.lead_type) IN ('Potential', 'Prospect', 'Warm'))";
            } elseif (in_array($filter_card, ['visit_scheduled', 'visit_scheduled_leads'])) {
                $sql .= " AND (COALESCE(NULLIF(lf.last_follow_up_lead_status, ''), l.lead_type) IN ('Visit Scheduled', 'Will Visit'))";
            } elseif (in_array($filter_card, ['visited_leads', 'visited', 'visit_completed'])) {
                $sql .= " AND (COALESCE(NULLIF(lf.last_follow_up_lead_status, ''), l.lead_type) IN ('Visit Completed', 'Visited'))";
            } elseif (in_array($filter_card, ['interested_other_location', 'interested_in_other_location', 'other_location'])) {
                $sql .= " AND (COALESCE(NULLIF(lf.last_follow_up_lead_status, ''), l.lead_type) = 'Interested in Other Location')";
            }
        }



        if (!empty($projectId)) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'l.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types .= $projClause['types'];
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

        //  Order by created_at DESC for latest leads first
        $sql .= " ORDER BY l.created_at DESC, l.id DESC";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= "ii";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->ensureDeleteRequestsTable();
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                error_log("LeadModel getAllWithFilter prepare failed: " . $this->db->error);
                return [];
            }
        }

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

    public function getTotalHotLeads($assignedUserId = null, $teamId = null)
    {
        $sql = "SELECT COUNT(DISTINCT l.id) AS total_hot FROM leads l
                LEFT JOIN employees emp ON emp.user_id = l.assigned_id
                WHERE l.deleted_at IS NULL AND l.status = 'active' AND l.last_lead_action != 'cancel' AND l.lead_type IN ('High Potential', 'Hot', 'High Prospect')
                  /* employee_status filter removed */";
        if (!empty($assignedUserId)) {
            $sql .= " AND l.assigned_id = " . (int)$assignedUserId;
        }
        if (!empty($teamId)) {
            $sql .= " AND l.team_id = " . (int)$teamId;
        }
        $res = $this->db->query($sql);
        return $res ? (int)($res->fetch_assoc()['total_hot'] ?? 0) : 0;
    }
    public function getTotalContactedLeads($assignedUserId = null, $teamId = null)
    {
        $sql = "SELECT COUNT(DISTINCT l.id) AS total_contacted 
                FROM leads l
                LEFT JOIN employees emp ON emp.user_id = l.assigned_id
                WHERE l.deleted_at IS NULL 
                  /* employee_status filter removed */
                  AND (
                      l.last_lead_action IN ('contacted', 'follow up', 'booked', 'visited')
                      OR EXISTS (
                          SELECT 1 FROM lead_follow_ups lf 
                          WHERE lf.lead_id = l.id AND lf.deleted_at IS NULL
                      )
                  )";
        if (!empty($assignedUserId)) {
            $sql .= " AND l.assigned_id = " . (int)$assignedUserId;
        }
        if (!empty($teamId)) {
            $sql .= " AND l.team_id = " . (int)$teamId;
        }
        $res = $this->db->query($sql);
        return $res ? (int)($res->fetch_assoc()['total_contacted'] ?? 0) : 0;
    }

    public function getTotalNotContactedLeads($assignedUserId = null, $teamId = null)
    {
        $sql = "SELECT COUNT(DISTINCT l.id) AS total_not_contacted 
                FROM leads l
                LEFT JOIN employees emp ON emp.user_id = l.assigned_id
                WHERE l.deleted_at IS NULL 
                  /* employee_status filter removed */
                  AND (l.last_lead_action IS NULL OR l.last_lead_action NOT IN ('contacted', 'follow up', 'booked', 'visited'))
                  AND NOT EXISTS (
                      SELECT 1 FROM lead_follow_ups lf 
                      WHERE lf.lead_id = l.id AND lf.deleted_at IS NULL
                  )";
        if (!empty($assignedUserId)) {
            $sql .= " AND l.assigned_id = " . (int)$assignedUserId;
        }
        if (!empty($teamId)) {
            $sql .= " AND l.team_id = " . (int)$teamId;
        }
        $res = $this->db->query($sql);
        return $res ? (int)($res->fetch_assoc()['total_not_contacted'] ?? 0) : 0;
    }

    public function getTotalAssignedLeads($assignedUserId = null, $teamId = null)
    {
        $sql = "SELECT COUNT(DISTINCT l.id) AS total_assigned 
                FROM leads l
                LEFT JOIN employees emp ON emp.user_id = l.assigned_id
                WHERE l.deleted_at IS NULL 
                  /* employee_status filter removed */
                  AND l.assigned_id IS NOT NULL AND l.assigned_id > 0";
        if (!empty($assignedUserId)) {
            $sql .= " AND l.assigned_id = " . (int)$assignedUserId;
        }
        if (!empty($teamId)) {
            $sql .= " AND l.team_id = " . (int)$teamId;
        }
        $res = $this->db->query($sql);
        return $res ? (int)($res->fetch_assoc()['total_assigned'] ?? 0) : 0;
    }

    public function getTotalNotAssignedLeads($assignedUserId = null, $teamId = null)
    {
        if (!empty($assignedUserId)) {
            return 0;
        }
        $sql = "SELECT COUNT(DISTINCT l.id) AS total_not_assigned 
                FROM leads l
                LEFT JOIN employees emp ON emp.user_id = l.assigned_id
                WHERE l.deleted_at IS NULL 
                  /* employee_status filter removed */
                  AND (l.assigned_id IS NULL OR l.assigned_id = 0)";
        if (!empty($teamId)) {
            $sql .= " AND l.team_id = " . (int)$teamId;
        }
        $res = $this->db->query($sql);
        return $res ? (int)($res->fetch_assoc()['total_not_assigned'] ?? 0) : 0;
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
                    $sqlQuery .= " AND created_at >= DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-26')";
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


    public function getTodayLeadsByUser(int $userId, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null)
    {
        $userId = (int) $userId;

        $sql = "
        SELECT 
            l.*,
            COALESCE(u_assigned_by.full_name, u_by.full_name, 'System') AS assigned_by_name,
            u_by.full_name AS creator_name,
            u_appr.full_name AS approved_by_name,
            p.name AS project_name,
            lpc.name AS project_category_name,
            lf.status AS last_follow_up_status,
            lf.lead_status AS last_follow_up_lead_status,
            lf.discussion_note AS last_follow_up_note,
            lf.next_follow_up_date,
            lf.follow_up_type,
            u_transfer.full_name AS transfer_from_name
        FROM leads AS l
        LEFT JOIN users AS u_assigned_by ON l.assigned_by = u_assigned_by.id
        LEFT JOIN users AS u_by ON l.created_by = u_by.id
        LEFT JOIN users AS u_appr ON l.approved_by = u_appr.id
        LEFT JOIN projects AS p ON l.project_id = p.id
        LEFT JOIN project_categories AS lpc ON l.project_category_id = lpc.id
        LEFT JOIN (
            SELECT t1.*
            FROM lead_follow_ups t1
            INNER JOIN (
                SELECT lead_id, MAX(id) AS max_id
                FROM lead_follow_ups
                GROUP BY lead_id
            ) t2 ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
        ) lf ON lf.lead_id = l.id
        LEFT JOIN users u_transfer ON lf.created_by = u_transfer.id
        WHERE l.assigned_id = {$userId}
          AND l.deleted_at IS NULL
          AND (
                (l.created_at >= CURDATE() AND l.created_at < CURDATE() + INTERVAL 1 DAY)
                OR
                (l.assigned_at IS NOT NULL AND l.assigned_at >= CURDATE() AND l.assigned_at < CURDATE() + INTERVAL 1 DAY)
              )
        ";

        $params = [];
        $types = "";

        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (l.assigned_at IS NOT NULL AND l.assigned_at != '0000-00-00 00:00:00' AND l.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((l.assigned_at IS NULL OR l.assigned_at = '0000-00-00 00:00:00') AND l.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
            )";
            $params[] = $hrs;
            $params[] = $hrs;
            $types .= "ii";
        }

        if (!empty($projectId)) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'l.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types .= $projClause['types'];
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

        $sql .= " ORDER BY l.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
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
    public function getAllMyLeads(int $userId, $year = null, $month = null, $lastAction = null, $lead_status = null, $only_missing_followup = false, $search = null, $followup_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $from_date = null, $to_date = null, $uncontacted = false, $limit = null, $offset = null, $date_type = 'lead_date')
    {
        $sql = "SELECT leads.*, 
        COALESCE(u_assigned_by.full_name, u_by.full_name, 'System') AS assigned_by_name,
        u_by.full_name AS creator_name,
        u_appr.full_name AS approved_by_name,
        lf.next_action,
        lf.follow_up_type,
        lf.status AS last_follow_up_status,
        lf.lead_status AS last_follow_up_lead_status,
        lf.interested_location_name AS last_follow_up_interested_location,
        lf.discussion_note AS last_follow_up_note,
        p.name AS project_name,
        lpc.name AS project_category_name,
        f.flat_name,
        lf.next_follow_up_date,
        u_transfer.full_name AS transfer_from_name
    FROM leads
    LEFT JOIN users AS u_assigned_by ON leads.assigned_by = u_assigned_by.id
    LEFT JOIN users AS u_by ON leads.created_by = u_by.id
    LEFT JOIN users AS u_appr ON leads.approved_by = u_appr.id
    LEFT JOIN (
        SELECT t1.*
        FROM lead_follow_ups t1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM lead_follow_ups
            WHERE deleted_at IS NULL
            GROUP BY lead_id
        ) t2 
        ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
        WHERE t1.deleted_at IS NULL
    ) lf ON lf.lead_id = leads.id
    LEFT JOIN users u_transfer ON lf.created_by = u_transfer.id
    LEFT JOIN projects AS p ON lf.project_id = p.id
    LEFT JOIN flats AS f ON lf.flat_id = f.id
    LEFT JOIN project_categories AS lpc ON leads.project_category_id = lpc.id
    WHERE (leads.assigned_id = ? OR EXISTS (SELECT 1 FROM lead_bookings lb_seller WHERE lb_seller.lead_id = leads.id AND lb_seller.sales_by_id = ? AND lb_seller.deleted_at IS NULL)) AND leads.deleted_at IS NULL";

        $params = [$userId, $userId];
        $types  = "ii";

        $isAssignedDate = in_array($date_type, ['assigned_date', 'assigned_at']);
        $dateCol = $isAssignedDate 
            ? "(CASE WHEN (leads.assigned_at IS NOT NULL AND leads.assigned_at > '1970-01-01 00:00:00') THEN leads.assigned_at ELSE leads.created_at END)" 
            : "leads.created_at";

        $applyLeadDateFilter = !in_array($filter_card, ['booked', 'total_booked']);

        if ($applyLeadDateFilter) {
            if (!empty($from_date) && !empty($to_date)) {
                $sql .= " AND DATE({$dateCol}) BETWEEN ? AND ?";
                $params[] = $from_date;
                $params[] = $to_date;
                $types .= "ss";
            } elseif (!empty($from_date)) {
                $sql .= " AND DATE({$dateCol}) >= ?";
                $params[] = $from_date;
                $types .= "s";
            } elseif (!empty($to_date)) {
                $sql .= " AND DATE({$dateCol}) <= ?";
                $params[] = $to_date;
                $types .= "s";
            }

            if (!empty($year)) {
                $sql .= " AND YEAR({$dateCol}) = ?";
                $params[] = (int)$year;
                $types .= "i";
            }

            if (!empty($month)) {
                $sql .= " AND MONTH({$dateCol}) = ?";
                $params[] = (int)$month;
                $types .= "i";
            }
        }

        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (leads.assigned_at IS NOT NULL AND leads.assigned_at != '0000-00-00 00:00:00' AND leads.assigned_at != '' AND leads.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((leads.assigned_at IS NULL OR leads.assigned_at = '0000-00-00 00:00:00' OR leads.assigned_at = '') AND leads.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
            )";
            $params[] = $hrs;
            $params[] = $hrs;
            $types .= "ii";
        }

        $contactedCond = "(LOWER(leads.last_lead_action) IN ('contacted', 'follow up', 'visited', 'booked') OR leads.call_status IN ('called', 'received') OR (leads.communication_type IS NOT NULL AND leads.communication_type != '' AND LOWER(leads.communication_type) != 'not select') OR lf.id IS NOT NULL OR EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = leads.id AND lb_sub.deleted_at IS NULL))";

        if (!empty($lastAction)) {
            if (strtolower($lastAction) === 'contacted') {
                $sql .= " AND " . $contactedCond;
            } elseif (strtolower($lastAction) === 'not_contacted') {
                $sql .= " AND NOT " . $contactedCond;
            } elseif (strtolower($lastAction) === 'visited') {
                $sql .= " AND (LOWER(leads.last_lead_action) = 'visited' OR lf.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf.follow_up_type) = 'visit' OR leads.lead_type IN ('Visit Completed', 'Visited'))";
            } else {
                $sql .= " AND LOWER(leads.last_lead_action) = LOWER(?)";
                $params[] = $lastAction;
                $types .= "s";
            }
        }
        if (!empty($lead_status)) {
            if ($lead_status === 'Pending') {
                $sql .= " AND (lf.lead_status = 'Pending' OR leads.lead_type = 'Pending' OR ((leads.lead_type IS NULL OR leads.lead_type = '') AND (lf.lead_status IS NULL OR lf.lead_status = '')) OR lf.status = 'pending')";
            } elseif ($lead_status === 'Qualification in Progress') {
                $sql .= " AND (lf.lead_status = 'Qualification in Progress' OR leads.lead_type = 'Qualification in Progress')";
            } elseif (in_array($lead_status, ['High Potential', 'Hot', 'High Prospect'])) {
                $sql .= " AND (lf.lead_status IN ('High Potential', 'Hot', 'High Prospect') OR leads.lead_type IN ('High Potential', 'Hot', 'High Prospect'))";
            } elseif (in_array($lead_status, ['Potential', 'Prospect', 'Warm'])) {
                $sql .= " AND (lf.lead_status IN ('Potential', 'Prospect', 'Warm') OR leads.lead_type IN ('Potential', 'Prospect', 'Warm'))";
            } elseif (in_array($lead_status, ['Visit Scheduled', 'Will Visit'])) {
                $sql .= " AND (lf.lead_status IN ('Visit Scheduled', 'Will Visit') OR leads.lead_type IN ('Visit Scheduled', 'Will Visit'))";
            } elseif (in_array($lead_status, ['Visit Completed', 'Visited'])) {
                $sql .= " AND (lf.lead_status IN ('Visit Completed', 'Visited') OR leads.lead_type IN ('Visit Completed', 'Visited'))";
            } elseif (in_array($lead_status, ['Invalid Lead', 'Irrelevant'])) {
                $sql .= " AND (lf.lead_status IN ('Invalid Lead', 'Irrelevant') OR leads.lead_type IN ('Invalid Lead', 'Irrelevant'))";
            } elseif (in_array($lead_status, ['Not Interested', 'Cold', "Don't Call Again"])) {
                $sql .= " AND (lf.lead_status IN ('Not Interested', 'Cold') OR leads.lead_type IN ('Not Interested', 'Cold'))";
            } else {
                $sql .= " AND (lf.lead_status = ? OR leads.lead_type = ?)";
                $params[] = $lead_status;
                $params[] = $lead_status;
                $types .= "ss";
            }
        }

        if ($only_missing_followup) {
            $sql .= " AND lf.next_follow_up_date IS NOT NULL
                      AND lf.next_follow_up_date > '1000-01-01'
                      AND DATE(lf.next_follow_up_date) < CURDATE()
                      AND lf.deleted_at IS NULL";
        }

        if ($uncontacted) {
            $sql .= " AND NOT " . $contactedCond;
        }

        if (!empty($projectId)) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'leads.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types .= $projClause['types'];
        }
        if (!empty($projectCategoryId)) {
            $sql .= " AND leads.project_category_id = ?";
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
                        $subConds[] = "leads.country = ?";
                        $subConds[] = "leads.city = ?";
                        $subConds[] = "leads.city LIKE ?";
                        $subConds[] = "leads.address LIKE ?";
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

        // Search Filter
        if (!empty($search)) {
            $sql .= " AND (leads.full_name LIKE ? OR leads.phone LIKE ? OR leads.phone_number LIKE ? OR leads.email LIKE ? OR leads.company_name LIKE ?)";
            $sParam = "%" . trim($search) . "%";
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $types .= "sssss";
        }

        // Next Follow-Up Date Filter
        if (!empty($followup_date)) {
            $sql .= " AND DATE(lf.next_follow_up_date) = ?";
            $params[] = trim($followup_date);
            $types .= "s";
        }

        // Card Quick Filter
        if (!empty($filter_card)) {
            if ($filter_card === 'contacted' || $filter_card === 'contacted_leads') {
                $sql .= " AND " . $contactedCond;
            } elseif ($filter_card === 'not_called' || $filter_card === 'not_contacted' || $filter_card === 'not_contacted_leads') {
                $sql .= " AND NOT " . $contactedCond;
            } elseif ($filter_card === 'booked' || $filter_card === 'total_booked') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) BETWEEN ? AND ? OR DATE(lb_sub.created_at) BETWEEN ? AND ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ssss";
                } elseif (!empty($from_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) >= ? OR DATE(lb_sub.created_at) >= ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $from_date;
                    $types .= "ss";
                } elseif (!empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) <= ? OR DATE(lb_sub.created_at) <= ?)
                    )";
                    $params[] = $to_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } elseif (!empty($year) || !empty($month)) {
                    $ySub = !empty($year) ? " AND (YEAR(lb_sub.booking_date) = " . (int)$year . " OR YEAR(lb_sub.created_at) = " . (int)$year . ")" : "";
                    $mSub = !empty($month) ? " AND (MONTH(lb_sub.booking_date) = " . (int)$month . " OR MONTH(lb_sub.created_at) = " . (int)$month . ")" : "";
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked')
                          $ySub $mSub
                    )";
                } else {
                    $sql .= " AND (LOWER(leads.last_lead_action) = 'booked' OR EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = leads.id AND lb_sub.deleted_at IS NULL AND lb_sub.booking_status IN ('completed', 'booked')))";
                }
            } elseif ($filter_card === 'upcoming_follow_ups') {
                $sql .= " AND lf.next_follow_up_date IS NOT NULL AND DATE(lf.next_follow_up_date) >= CURDATE()";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (leads.lead_type IN ('Invalid Lead', 'Irrelevant') OR lf.lead_status IN ('Invalid Lead', 'Irrelevant'))";
            } elseif ($filter_card === 'no_response' || $filter_card === 'no_response_leads') {
                $sql .= " AND (
                    CASE 
                        WHEN lf.lead_id IS NOT NULL THEN
                            (
                                lf.lead_status = 'No Response'
                                OR lf.follow_up_type IN ('Number Off', 'Wrong Number', 'Invalid Number')
                                OR LOWER(COALESCE(lf.follow_up_type, '')) LIKE '%number off%'
                                OR LOWER(COALESCE(lf.follow_up_type, '')) LIKE '%no response%'
                                OR LOWER(COALESCE(lf.lead_status, '')) LIKE '%no response%'
                                OR (COALESCE(lf.lead_status, '') = '' AND leads.lead_type = 'No Response')
                            )
                        ELSE
                            (
                                leads.lead_type = 'No Response'
                                OR leads.call_status = 'not_received'
                                OR LOWER(COALESCE(leads.last_lead_action, '')) IN ('no response', 'not received')
                            )
                    END
                )";
            } elseif (in_array($filter_card, ['interested_other_location', 'interested_in_other_location', 'other_location'])) {
                $sql .= " AND (leads.lead_type = 'Interested in Other Location' OR lf.lead_status = 'Interested in Other Location')";
            } elseif (in_array($filter_card, ['visited_leads', 'visited', 'visit_completed'])) {
                $sql .= " AND (
                    (lf.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf.follow_up_type) = 'visit')
                    OR leads.lead_type IN ('Visit Completed', 'Visited')
                    OR LOWER(leads.last_lead_action) = 'visited'
                )";
            } elseif (in_array($filter_card, ['visit_scheduled', 'visit_scheduled_leads'])) {
                $sql .= " AND (
                    (lf.lead_status IN ('Visit Scheduled', 'Will Visit') OR LOWER(lf.follow_up_type) LIKE '%schedule%')
                    OR leads.lead_type IN ('Visit Scheduled', 'Will Visit')
                    OR LOWER(leads.last_lead_action) LIKE '%schedule%'
                )";
            }
        }

        // Sort by next_follow_up_date ASC if viewing upcoming follow ups
        if ($filter_card === 'upcoming_follow_ups') {
            $sql .= " ORDER BY DATE(lf.next_follow_up_date) ASC, leads.id DESC";
        } else {
            $sql .= " ORDER BY leads.created_at DESC, leads.id DESC";
        }

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= "ii";
        }

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

    public function getAllMyLeadsCount(int $userId, $year = null, $month = null, $lastAction = null, $lead_status = null, $only_missing_followup = false, $search = null, $followup_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $from_date = null, $to_date = null, $uncontacted = false, $date_type = 'lead_date')
    {
        $sql = "SELECT COUNT(DISTINCT leads.id) as total_count
    FROM leads
    LEFT JOIN (
        SELECT t1.*
        FROM lead_follow_ups t1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM lead_follow_ups
            WHERE deleted_at IS NULL
            GROUP BY lead_id
        ) t2 
        ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
        WHERE t1.deleted_at IS NULL
    ) lf ON lf.lead_id = leads.id
    WHERE (leads.assigned_id = ? OR EXISTS (SELECT 1 FROM lead_bookings lb_seller WHERE lb_seller.lead_id = leads.id AND lb_seller.sales_by_id = ? AND lb_seller.deleted_at IS NULL)) AND leads.deleted_at IS NULL";

        $params = [$userId, $userId];
        $types  = "ii";

        $isAssignedDate = in_array($date_type, ['assigned_date', 'assigned_at']);
        $dateCol = $isAssignedDate 
            ? "(CASE WHEN (leads.assigned_at IS NOT NULL AND leads.assigned_at > '1970-01-01 00:00:00') THEN leads.assigned_at ELSE leads.created_at END)" 
            : "leads.created_at";

        $applyLeadDateFilter = !in_array($filter_card, ['booked', 'total_booked']);

        if ($applyLeadDateFilter) {
            if (!empty($from_date) && !empty($to_date)) {
                $sql .= " AND DATE({$dateCol}) BETWEEN ? AND ?";
                $params[] = $from_date;
                $params[] = $to_date;
                $types .= "ss";
            } elseif (!empty($from_date)) {
                $sql .= " AND DATE({$dateCol}) >= ?";
                $params[] = $from_date;
                $types .= "s";
            } elseif (!empty($to_date)) {
                $sql .= " AND DATE({$dateCol}) <= ?";
                $params[] = $to_date;
                $types .= "s";
            }

            if (!empty($year)) {
                $sql .= " AND YEAR({$dateCol}) = ?";
                $params[] = (int)$year;
                $types .= "i";
            }

            if (!empty($month)) {
                $sql .= " AND MONTH({$dateCol}) = ?";
                $params[] = (int)$month;
                $types .= "i";
            }
        }

        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (leads.assigned_at IS NOT NULL AND leads.assigned_at != '0000-00-00 00:00:00' AND leads.assigned_at != '' AND leads.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((leads.assigned_at IS NULL OR leads.assigned_at = '0000-00-00 00:00:00' OR leads.assigned_at = '') AND leads.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
            )";
            $params[] = $hrs;
            $params[] = $hrs;
            $types .= "ii";
        }

        $contactedCond = "(LOWER(leads.last_lead_action) IN ('contacted', 'follow up', 'visited', 'booked') OR leads.call_status IN ('called', 'received') OR (leads.communication_type IS NOT NULL AND leads.communication_type != '' AND LOWER(leads.communication_type) != 'not select') OR lf.id IS NOT NULL OR EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = leads.id AND lb_sub.deleted_at IS NULL))";

        if (!empty($lastAction)) {
            if (strtolower($lastAction) === 'contacted') {
                $sql .= " AND " . $contactedCond;
            } elseif (strtolower($lastAction) === 'not_contacted') {
                $sql .= " AND NOT " . $contactedCond;
            } elseif (strtolower($lastAction) === 'visited') {
                $sql .= " AND (LOWER(leads.last_lead_action) = 'visited' OR lf.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf.follow_up_type) = 'visit' OR leads.lead_type IN ('Visit Completed', 'Visited'))";
            } else {
                $sql .= " AND LOWER(leads.last_lead_action) = LOWER(?)";
                $params[] = $lastAction;
                $types .= "s";
            }
        }
        if (!empty($lead_status)) {
            if ($lead_status === 'Pending') {
                $sql .= " AND (lf.lead_status = 'Pending' OR leads.lead_type = 'Pending' OR ((leads.lead_type IS NULL OR leads.lead_type = '') AND (lf.lead_status IS NULL OR lf.lead_status = '')) OR lf.status = 'pending')";
            } elseif ($lead_status === 'Qualification in Progress') {
                $sql .= " AND (lf.lead_status = 'Qualification in Progress' OR leads.lead_type = 'Qualification in Progress')";
            } elseif (in_array($lead_status, ['High Potential', 'Hot', 'High Prospect'])) {
                $sql .= " AND (lf.lead_status IN ('High Potential', 'Hot', 'High Prospect') OR leads.lead_type IN ('High Potential', 'Hot', 'High Prospect'))";
            } elseif (in_array($lead_status, ['Potential', 'Prospect', 'Warm'])) {
                $sql .= " AND (lf.lead_status IN ('Potential', 'Prospect', 'Warm') OR leads.lead_type IN ('Potential', 'Prospect', 'Warm'))";
            } elseif (in_array($lead_status, ['Visit Scheduled', 'Will Visit'])) {
                $sql .= " AND (lf.lead_status IN ('Visit Scheduled', 'Will Visit') OR leads.lead_type IN ('Visit Scheduled', 'Will Visit'))";
            } elseif (in_array($lead_status, ['Visit Completed', 'Visited'])) {
                $sql .= " AND (lf.lead_status IN ('Visit Completed', 'Visited') OR leads.lead_type IN ('Visit Completed', 'Visited'))";
            } elseif (in_array($lead_status, ['Invalid Lead', 'Irrelevant'])) {
                $sql .= " AND (lf.lead_status IN ('Invalid Lead', 'Irrelevant') OR leads.lead_type IN ('Invalid Lead', 'Irrelevant'))";
            } elseif (in_array($lead_status, ['Not Interested', 'Cold', "Don't Call Again"])) {
                $sql .= " AND (lf.lead_status IN ('Not Interested', 'Cold') OR leads.lead_type IN ('Not Interested', 'Cold'))";
            } else {
                $sql .= " AND (lf.lead_status = ? OR leads.lead_type = ?)";
                $params[] = $lead_status;
                $params[] = $lead_status;
                $types .= "ss";
            }
        }

        if ($only_missing_followup) {
            $sql .= " AND lf.next_follow_up_date IS NOT NULL
                      AND lf.next_follow_up_date > '1000-01-01'
                      AND DATE(lf.next_follow_up_date) < CURDATE()
                      AND lf.deleted_at IS NULL";
        }

        if ($uncontacted) {
            $sql .= " AND NOT " . $contactedCond;
        }

        if (!empty($projectId)) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'leads.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types .= $projClause['types'];
        }
        if (!empty($projectCategoryId)) {
            $sql .= " AND leads.project_category_id = ?";
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
                        $subConds[] = "leads.country = ?";
                        $subConds[] = "leads.city = ?";
                        $subConds[] = "leads.city LIKE ?";
                        $subConds[] = "leads.address LIKE ?";
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

        if (!empty($search)) {
            $sql .= " AND (leads.full_name LIKE ? OR leads.phone LIKE ? OR leads.phone_number LIKE ? OR leads.email LIKE ? OR leads.company_name LIKE ?)";
            $sParam = "%" . trim($search) . "%";
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $types .= "sssss";
        }

        if (!empty($followup_date)) {
            $sql .= " AND DATE(lf.next_follow_up_date) = ?";
            $params[] = trim($followup_date);
            $types .= "s";
        }

        if (!empty($filter_card)) {
            if ($filter_card === 'contacted' || $filter_card === 'contacted_leads') {
                $sql .= " AND " . $contactedCond;
            } elseif ($filter_card === 'not_called' || $filter_card === 'not_contacted' || $filter_card === 'not_contacted_leads') {
                $sql .= " AND NOT " . $contactedCond;
            } elseif ($filter_card === 'booked' || $filter_card === 'total_booked') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) BETWEEN ? AND ? OR DATE(lb_sub.created_at) BETWEEN ? AND ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ssss";
                } elseif (!empty($from_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) >= ? OR DATE(lb_sub.created_at) >= ?)
                    )";
                    $params[] = $from_date;
                    $params[] = $from_date;
                    $types .= "ss";
                } elseif (!empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked') 
                          AND (DATE(lb_sub.booking_date) <= ? OR DATE(lb_sub.created_at) <= ?)
                    )";
                    $params[] = $to_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } elseif (!empty($year) || !empty($month)) {
                    $ySub = !empty($year) ? " AND (YEAR(lb_sub.booking_date) = " . (int)$year . " OR YEAR(lb_sub.created_at) = " . (int)$year . ")" : "";
                    $mSub = !empty($month) ? " AND (MONTH(lb_sub.booking_date) = " . (int)$month . " OR MONTH(lb_sub.created_at) = " . (int)$month . ")" : "";
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_bookings lb_sub 
                        WHERE lb_sub.lead_id = leads.id 
                          AND lb_sub.deleted_at IS NULL 
                          AND lb_sub.booking_status IN ('completed', 'booked')
                          $ySub $mSub
                    )";
                } else {
                    $sql .= " AND (LOWER(leads.last_lead_action) = 'booked' OR EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = leads.id AND lb_sub.deleted_at IS NULL AND lb_sub.booking_status IN ('completed', 'booked')))";
                }
            } elseif ($filter_card === 'upcoming_follow_ups') {
                $sql .= " AND lf.next_follow_up_date IS NOT NULL AND DATE(lf.next_follow_up_date) >= CURDATE()";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (leads.lead_type IN ('Invalid Lead', 'Irrelevant') OR lf.lead_status IN ('Invalid Lead', 'Irrelevant'))";
            } elseif ($filter_card === 'no_response' || $filter_card === 'no_response_leads') {
                $sql .= " AND (
                    CASE 
                        WHEN lf.lead_id IS NOT NULL THEN
                            (
                                lf.lead_status = 'No Response'
                                OR lf.follow_up_type IN ('Number Off', 'Wrong Number', 'Invalid Number')
                                OR LOWER(COALESCE(lf.follow_up_type, '')) LIKE '%number off%'
                                OR LOWER(COALESCE(lf.follow_up_type, '')) LIKE '%no response%'
                                OR LOWER(COALESCE(lf.lead_status, '')) LIKE '%no response%'
                                OR (COALESCE(lf.lead_status, '') = '' AND leads.lead_type = 'No Response')
                            )
                        ELSE
                            (
                                leads.lead_type = 'No Response'
                                OR leads.call_status = 'not_received'
                                OR LOWER(COALESCE(leads.last_lead_action, '')) IN ('no response', 'not received')
                            )
                    END
                )";
            } elseif (in_array($filter_card, ['interested_other_location', 'interested_in_other_location', 'other_location'])) {
                $sql .= " AND (leads.lead_type = 'Interested in Other Location' OR lf.lead_status = 'Interested in Other Location')";
            } elseif (in_array($filter_card, ['visited_leads', 'visited', 'visit_completed'])) {
                $sql .= " AND (
                    (lf.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf.follow_up_type) = 'visit')
                    OR leads.lead_type IN ('Visit Completed', 'Visited')
                    OR LOWER(leads.last_lead_action) = 'visited'
                )";
            } elseif (in_array($filter_card, ['visit_scheduled', 'visit_scheduled_leads'])) {
                $sql .= " AND (
                    (lf.lead_status IN ('Visit Scheduled', 'Will Visit') OR LOWER(lf.follow_up_type) LIKE '%schedule%')
                    OR leads.lead_type IN ('Visit Scheduled', 'Will Visit')
                    OR LOWER(leads.last_lead_action) LIKE '%schedule%'
                )";
            }
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) return 0;
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return (int)($row['total_count'] ?? 0);
    }

    public function getMyAssignedLeadsSummaryCards(int $userId, $year = null, $month = null, $lastAction = null, $lead_status = null, $only_missing_followup = false, $search = null, $followup_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $from_date = null, $to_date = null, $uncontacted = false, $date_type = 'lead_date')
    {
        $contactedCond = "(LOWER(leads.last_lead_action) IN ('contacted', 'follow up', 'visited', 'booked') OR leads.call_status IN ('called', 'received') OR (leads.communication_type IS NOT NULL AND leads.communication_type != '' AND LOWER(leads.communication_type) != 'not select') OR lf.id IS NOT NULL OR EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = leads.id AND lb_sub.deleted_at IS NULL))";

        $noResponseCond = "(
            CASE 
                WHEN lf.lead_id IS NOT NULL THEN
                    (
                        lf.lead_status = 'No Response'
                        OR lf.follow_up_type IN ('Number Off', 'Wrong Number', 'Invalid Number')
                        OR LOWER(COALESCE(lf.follow_up_type, '')) LIKE '%number off%'
                        OR LOWER(COALESCE(lf.follow_up_type, '')) LIKE '%no response%'
                        OR LOWER(COALESCE(lf.lead_status, '')) LIKE '%no response%'
                        OR (COALESCE(lf.lead_status, '') = '' AND leads.lead_type = 'No Response')
                    )
                ELSE
                    (
                        leads.lead_type = 'No Response'
                        OR leads.call_status = 'not_received'
                        OR LOWER(COALESCE(leads.last_lead_action, '')) IN ('no response', 'not received')
                    )
            END
        )";

        $sql = "SELECT 
            COUNT(DISTINCT leads.id) as total_leads,
            SUM(CASE WHEN {$contactedCond} THEN 1 ELSE 0 END) as contacted_leads,
            SUM(CASE WHEN NOT {$contactedCond} THEN 1 ELSE 0 END) as not_contacted_leads,
            SUM(CASE WHEN LOWER(leads.last_lead_action) = 'booked' THEN 1 ELSE 0 END) as booked_leads,
            SUM(CASE WHEN lf.next_follow_up_date IS NOT NULL AND DATE(lf.next_follow_up_date) >= CURDATE() THEN 1 ELSE 0 END) as upcoming_follow_ups,
            SUM(CASE WHEN lf.next_follow_up_date IS NOT NULL AND lf.next_follow_up_date > '1000-01-01' AND DATE(lf.next_follow_up_date) < CURDATE() AND lf.deleted_at IS NULL THEN 1 ELSE 0 END) as missing_follow_ups,
            SUM(CASE WHEN (leads.lead_type IN ('Invalid Lead', 'Irrelevant') OR lf.lead_status IN ('Invalid Lead', 'Irrelevant')) THEN 1 ELSE 0 END) as irrelevant_leads,
            SUM(CASE WHEN {$noResponseCond} THEN 1 ELSE 0 END) as no_response_leads,
            SUM(CASE WHEN (leads.lead_type = 'Interested in Other Location' OR lf.lead_status = 'Interested in Other Location') THEN 1 ELSE 0 END) as interested_other_location_leads
        FROM leads
        LEFT JOIN (
            SELECT t1.*
            FROM lead_follow_ups t1
            INNER JOIN (
                SELECT lead_id, MAX(id) AS max_id
                FROM lead_follow_ups
                WHERE deleted_at IS NULL
                GROUP BY lead_id
            ) t2 
            ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
        ) lf ON lf.lead_id = leads.id
        WHERE (leads.assigned_id = ? OR EXISTS (SELECT 1 FROM lead_bookings lb_seller WHERE lb_seller.lead_id = leads.id AND lb_seller.sales_by_id = ? AND lb_seller.deleted_at IS NULL)) AND leads.deleted_at IS NULL";

        $params = [$userId, $userId];
        $types  = "ii";

        $isAssignedDate = in_array($date_type, ['assigned_date', 'assigned_at']);
        $dateCol = $isAssignedDate 
            ? "(CASE WHEN (leads.assigned_at IS NOT NULL AND leads.assigned_at > '1970-01-01 00:00:00') THEN leads.assigned_at ELSE leads.created_at END)" 
            : "leads.created_at";

        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND DATE({$dateCol}) BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
            $types .= "ss";
        } elseif (!empty($from_date)) {
            $sql .= " AND DATE({$dateCol}) >= ?";
            $params[] = $from_date;
            $types .= "s";
        } elseif (!empty($to_date)) {
            $sql .= " AND DATE({$dateCol}) <= ?";
            $params[] = $to_date;
            $types .= "s";
        }

        if (!empty($year)) {
            $sql .= " AND YEAR({$dateCol}) = ?";
            $params[] = (int)$year;
            $types .= "i";
        }

        if (!empty($month)) {
            $sql .= " AND MONTH({$dateCol}) = ?";
            $params[] = (int)$month;
            $types .= "i";
        }

        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (leads.assigned_at IS NOT NULL AND leads.assigned_at != '0000-00-00 00:00:00' AND leads.assigned_at != '' AND leads.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((leads.assigned_at IS NULL OR leads.assigned_at = '0000-00-00 00:00:00' OR leads.assigned_at = '') AND leads.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
            )";
            $params[] = $hrs;
            $params[] = $hrs;
            $types .= "ii";
        }

        if ($uncontacted) {
            $sql .= " AND NOT " . $contactedCond;
        }

        if (!empty($lastAction)) {
            if (strtolower($lastAction) === 'contacted') {
                $sql .= " AND " . $contactedCond;
            } elseif (strtolower($lastAction) === 'not_contacted') {
                $sql .= " AND NOT " . $contactedCond;
            } else {
                $sql .= " AND LOWER(leads.last_lead_action) = LOWER(?)";
                $params[] = $lastAction;
                $types .= "s";
            }
        }
        if (!empty($lead_status)) {
            if ($lead_status === 'Pending') {
                $sql .= " AND (lf.lead_status = 'Pending' OR leads.lead_type = 'Pending' OR ((leads.lead_type IS NULL OR leads.lead_type = '') AND (lf.lead_status IS NULL OR lf.lead_status = '')) OR lf.status = 'pending')";
            } elseif ($lead_status === 'Qualification in Progress') {
                $sql .= " AND (lf.lead_status = 'Qualification in Progress' OR leads.lead_type = 'Qualification in Progress')";
            } elseif (in_array($lead_status, ['High Potential', 'Hot', 'High Prospect'])) {
                $sql .= " AND (lf.lead_status IN ('High Potential', 'Hot', 'High Prospect') OR leads.lead_type IN ('High Potential', 'Hot', 'High Prospect'))";
            } elseif (in_array($lead_status, ['Potential', 'Prospect', 'Warm'])) {
                $sql .= " AND (lf.lead_status IN ('Potential', 'Prospect', 'Warm') OR leads.lead_type IN ('Potential', 'Prospect', 'Warm'))";
            } elseif (in_array($lead_status, ['Visit Scheduled', 'Will Visit'])) {
                $sql .= " AND (lf.lead_status IN ('Visit Scheduled', 'Will Visit') OR leads.lead_type IN ('Visit Scheduled', 'Will Visit'))";
            } elseif (in_array($lead_status, ['Visit Completed', 'Visited'])) {
                $sql .= " AND (lf.lead_status IN ('Visit Completed', 'Visited') OR leads.lead_type IN ('Visit Completed', 'Visited'))";
            } elseif (in_array($lead_status, ['Invalid Lead', 'Irrelevant'])) {
                $sql .= " AND (lf.lead_status IN ('Invalid Lead', 'Irrelevant') OR leads.lead_type IN ('Invalid Lead', 'Irrelevant'))";
            } elseif (in_array($lead_status, ['Not Interested', 'Cold', "Don't Call Again"])) {
                $sql .= " AND (lf.lead_status IN ('Not Interested', 'Cold') OR leads.lead_type IN ('Not Interested', 'Cold'))";
            } else {
                $sql .= " AND (lf.lead_status = ? OR leads.lead_type = ?)";
                $params[] = $lead_status;
                $params[] = $lead_status;
                $types .= "ss";
            }
        }

        if (!empty($projectId)) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'leads.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types .= $projClause['types'];
        }
        if (!empty($projectCategoryId)) {
            $sql .= " AND leads.project_category_id = ?";
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
                        $subConds[] = "leads.country = ?";
                        $subConds[] = "leads.city = ?";
                        $subConds[] = "leads.city LIKE ?";
                        $subConds[] = "leads.address LIKE ?";
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

        if (!empty($search)) {
            $sql .= " AND (leads.full_name LIKE ? OR leads.phone LIKE ? OR leads.phone_number LIKE ? OR leads.email LIKE ? OR leads.company_name LIKE ?)";
            $sParam = "%" . trim($search) . "%";
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $types .= "sssss";
        }

        if (!empty($followup_date)) {
            $sql .= " AND DATE(lf.next_follow_up_date) = ?";
            $params[] = trim($followup_date);
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return ['total_leads' => 0, 'contacted_leads' => 0, 'not_contacted_leads' => 0, 'booked_leads' => 0, 'upcoming_follow_ups' => 0, 'missing_follow_ups' => 0];
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) return ['total_leads' => 0, 'contacted_leads' => 0, 'not_contacted_leads' => 0, 'booked_leads' => 0, 'upcoming_follow_ups' => 0, 'missing_follow_ups' => 0];
        
        $result = $stmt->get_result();
        $row = $result->fetch_assoc() ?: ['total_leads' => 0, 'contacted_leads' => 0, 'not_contacted_leads' => 0, 'booked_leads' => 0, 'upcoming_follow_ups' => 0, 'missing_follow_ups' => 0, 'irrelevant_leads' => 0, 'no_response_leads' => 0];
        $stmt->close();

        // Consistent booked count: leads assigned to user with confirmed booking in the selected booking period (matching lead-lists)
        $row['booked_leads'] = $this->getAllMyLeadsCount($userId, $year, $month, null, null, false, $search, null, 'booked', $projectId, $projectCategoryId, $country, $assignedHours, $from_date, $to_date, false, $date_type);

        return $row;
    }

    /**
     * Get Executive Dashboard Serial Matrix Metrics
     * Computes the 16 core serial metrics for executive dashboard
     */
    public function getExecutiveDashboardMetrics(
        int $userId, 
        int $year = null, 
        int $startMonth = null, 
        int $endMonth = null, 
        bool $hasFilter = false,
        string $customFromDate = null,
        string $customToDate = null
    ): array {
        if (!empty($customFromDate) && !empty($customToDate)) {
            $startDate = $customFromDate;
            $endDate = $customToDate;
            $year = (int)date('Y', strtotime($startDate));
            $startMonth = (int)date('m', strtotime($startDate));
            $endMonth = (int)date('m', strtotime($endDate));
        } else {
            $year = $year ?: (int)date('Y');
            $startMonth = $startMonth ?: (int)date('m');
            $endMonth = $endMonth ?: $startMonth;
            $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
            $endDate = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));
        }

        // Dynamic column support
        $hasAssignedAt       = $this->hasTableColumn('leads', 'assigned_at');
        $hasFirstContactedAt = $this->hasTableColumn('leads', 'first_contacted_at');
        $hasLastLeadAction   = $this->hasTableColumn('leads', 'last_lead_action');
        $hasCallStatus       = $this->hasTableColumn('leads', 'call_status');
        $hasCommType         = $this->hasTableColumn('leads', 'communication_type');
        $hasLeadType         = $this->hasTableColumn('leads', 'lead_type');
        $hasDeletedAt        = $this->hasTableColumn('leads', 'deleted_at');
        $fuHasDeletedAt      = $this->hasTableColumn('lead_follow_ups', 'deleted_at');
        $bkHasDeletedAt      = $this->hasTableColumn('lead_bookings', 'deleted_at');

        $assignedDateExpr = $hasAssignedAt 
            ? "(CASE WHEN (leads.assigned_at IS NOT NULL AND leads.assigned_at > '1970-01-01 00:00:00') THEN leads.assigned_at ELSE leads.created_at END)"
            : "leads.created_at";

        $assignedDateExprUnprefixed = $hasAssignedAt 
            ? "(CASE WHEN (assigned_at IS NOT NULL AND assigned_at > '1970-01-01 00:00:00') THEN assigned_at ELSE created_at END)"
            : "created_at";

        $leadsDelSql = $hasDeletedAt ? "AND leads.deleted_at IS NULL" : "";
        $lDelSql     = $hasDeletedAt ? "AND l.deleted_at IS NULL" : "";
        $fuDelCond   = $fuHasDeletedAt ? "AND lf.deleted_at IS NULL" : "";
        $fuDelWhere  = $fuHasDeletedAt ? "WHERE deleted_at IS NULL" : "";
        $bkDelCond   = $bkHasDeletedAt ? "AND lb.deleted_at IS NULL" : "";
        $bkDelWhere  = $bkHasDeletedAt ? "AND deleted_at IS NULL" : "";

        // Hide undistributed automated leads from sales users until approved (distribute = 1)
        $loggedInRole = $_SESSION['user']['role_id'] ?? 0;
        $autoFilter = "";
        if (!in_array((int)$loggedInRole, [1, 10, 11])) {
            [$hasAutomatedCol, $hasDistributeCol] = $this->getLeadAutomationColumnStatus();
            if ($hasAutomatedCol && $hasDistributeCol) {
                $autoFilter = " AND NOT (leads.is_automated = 1 AND leads.distribute = 0)";
            }
        }

        // Build contacted condition dynamically
        $orConds = [];
        if ($hasLastLeadAction) {
            $orConds[] = "LOWER(leads.last_lead_action) IN ('contacted', 'follow up', 'visited', 'booked')";
        }
        if ($hasCallStatus) {
            $orConds[] = "leads.call_status IN ('called', 'received')";
        }
        if ($hasCommType) {
            $orConds[] = "(leads.communication_type IS NOT NULL AND leads.communication_type != '' AND LOWER(leads.communication_type) != 'not select')";
        }
        $orConds[] = "EXISTS (SELECT 1 FROM lead_follow_ups lf WHERE lf.lead_id = leads.id {$fuDelCond})";
        $orConds[] = "EXISTS (SELECT 1 FROM lead_bookings lb WHERE lb.lead_id = leads.id {$bkDelCond})";
        $contactedCond = "(" . implode(" OR ", $orConds) . ")";

        // 1. All-time Leads Assigned
        $sql1 = "SELECT COUNT(*) as c FROM leads WHERE assigned_id = ? {$leadsDelSql} {$autoFilter}";
        $allTimeLeadsAssigned = 0;
        if ($stmt1 = $this->db->prepare($sql1)) {
            $stmt1->bind_param("i", $userId);
            $stmt1->execute();
            $allTimeLeadsAssigned = (int)($stmt1->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt1->close();
        }

        // 2. All-time Leads Contacted
        $sql2 = "SELECT COUNT(*) as c FROM leads WHERE assigned_id = ? {$leadsDelSql} {$autoFilter} AND {$contactedCond}";
        $allTimeLeadsContacted = 0;
        if ($stmt2 = $this->db->prepare($sql2)) {
            $stmt2->bind_param("i", $userId);
            $stmt2->execute();
            $allTimeLeadsContacted = (int)($stmt2->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt2->close();
        }

        $allTimeContactedPct = ($allTimeLeadsAssigned > 0) ? round(($allTimeLeadsContacted / $allTimeLeadsAssigned) * 100, 1) : 0;
        $allTimeNotContacted = max(0, $allTimeLeadsAssigned - $allTimeLeadsContacted);

        // Period Metrics (Assigned within date range)
        $sqlPeriodAssigned = "SELECT COUNT(*) as c FROM leads WHERE assigned_id = ? {$leadsDelSql} {$autoFilter} AND DATE(created_at) BETWEEN ? AND ?";
        $periodLeadsAssigned = 0;
        if ($stmtP1 = $this->db->prepare($sqlPeriodAssigned)) {
            $stmtP1->bind_param("iss", $userId, $startDate, $endDate);
            $stmtP1->execute();
            $periodLeadsAssigned = (int)($stmtP1->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtP1->close();
        }

        $sqlPeriodContacted = "SELECT COUNT(*) as c FROM leads WHERE assigned_id = ? {$leadsDelSql} {$autoFilter} AND DATE(created_at) BETWEEN ? AND ? AND {$contactedCond}";
        $periodLeadsContacted = 0;
        if ($stmtP2 = $this->db->prepare($sqlPeriodContacted)) {
            $stmtP2->bind_param("iss", $userId, $startDate, $endDate);
            $stmtP2->execute();
            $periodLeadsContacted = (int)($stmtP2->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtP2->close();
        }

        $periodNotContacted = max(0, $periodLeadsAssigned - $periodLeadsContacted);
        $periodContactedPct = ($periodLeadsAssigned > 0) ? round(($periodLeadsContacted / $periodLeadsAssigned) * 100, 1) : 0;

        // When filtered, active cards display period data for consistency with /my-all-assigned-leads
        if ($hasFilter) {
            $totalLeadsAssigned  = $periodLeadsAssigned;
            $totalLeadsContacted = $periodLeadsContacted;
            $totalNotContacted   = $periodNotContacted;
            $totalContactedPct   = $periodContactedPct;
        } else {
            $totalLeadsAssigned  = $allTimeLeadsAssigned;
            $totalLeadsContacted = $allTimeLeadsContacted;
            $totalNotContacted   = $allTimeNotContacted;
            $totalContactedPct   = $allTimeContactedPct;
        }

        // 5. Today Leads Assigned
        $sql5 = "SELECT COUNT(*) as c FROM leads WHERE assigned_id = ? {$leadsDelSql} {$autoFilter} AND DATE({$assignedDateExprUnprefixed}) = CURDATE()";
        $todayLeadsAssigned = 0;
        if ($stmt5 = $this->db->prepare($sql5)) {
            $stmt5->bind_param("i", $userId);
            $stmt5->execute();
            $todayLeadsAssigned = (int)($stmt5->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt5->close();
        }

        // 6. Today Leads Contacted
        $firstContactCond = "";
        if ($hasCallStatus) {
            if ($hasFirstContactedAt) {
                $firstContactCond = "OR (
                    leads.call_status IN ('called', 'received', 'contacted') 
                    AND (
                        (leads.first_contacted_at IS NOT NULL AND leads.first_contacted_at >= {$assignedDateExpr})
                        OR (leads.first_contacted_at IS NOT NULL AND DATE(leads.first_contacted_at) = CURDATE())
                    )
                )";
            } else {
                $firstContactCond = "OR (leads.call_status IN ('called', 'received', 'contacted') AND DATE(leads.updated_at) = CURDATE())";
            }
        }

        $todayContactedCond = "(
            EXISTS (
                SELECT 1 FROM lead_follow_ups lf 
                WHERE lf.lead_id = leads.id {$fuDelCond} 
                  AND (lf.created_by = ? OR lf.assigned_with = ? OR lf.created_at >= {$assignedDateExpr})
            )
            OR EXISTS (
                SELECT 1 FROM lead_bookings lb 
                WHERE lb.lead_id = leads.id {$bkDelCond} 
                  AND (lb.sales_by_id = ? OR lb.created_by = ? OR lb.created_at >= {$assignedDateExpr})
            )
            {$firstContactCond}
        )";
        $sql6 = "SELECT COUNT(*) as c FROM leads 
                 WHERE assigned_id = ? 
                   {$leadsDelSql} 
                   AND DATE({$assignedDateExprUnprefixed}) = CURDATE() 
                   AND {$todayContactedCond}";
        $todayLeadsContacted = 0;
        if ($stmt6 = $this->db->prepare($sql6)) {
            $stmt6->bind_param("iiiii", $userId, $userId, $userId, $userId, $userId);
            $stmt6->execute();
            $todayLeadsContacted = (int)($stmt6->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt6->close();
        }

        // 7. Today Not Contacted
        $todayNotContacted = max(0, $todayLeadsAssigned - $todayLeadsContacted);

        // 8. Total Follow-Up Complete
        $sql8 = "SELECT COUNT(*) as c FROM lead_follow_ups lf WHERE (lf.created_by = ? OR lf.assigned_with = ?) {$fuDelCond}";
        $totalFollowUpComplete = 0;
        if ($stmt8 = $this->db->prepare($sql8)) {
            $stmt8->bind_param("ii", $userId, $userId);
            $stmt8->execute();
            $totalFollowUpComplete = (int)($stmt8->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt8->close();
        }

        // 9. Total Follow Up Missing
        $sql9 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            INNER JOIN (
                SELECT t1.lead_id, t1.next_follow_up_date
                FROM lead_follow_ups t1
                INNER JOIN (
                    SELECT lead_id, MAX(id) AS max_id
                    FROM lead_follow_ups
                    {$fuDelWhere}
                    GROUP BY lead_id
                ) t2 ON t1.id = t2.max_id
                {$fuDelWhere}
            ) lf ON lf.lead_id = l.id
            WHERE l.assigned_id = ? 
              {$lDelSql}
              AND lf.next_follow_up_date IS NOT NULL
              AND lf.next_follow_up_date > '1000-01-01'
              AND DATE(lf.next_follow_up_date) < CURDATE()
        ";
        $allTimeFollowUpMissing = 0;
        if ($stmt9 = $this->db->prepare($sql9)) {
            $stmt9->bind_param("i", $userId);
            $stmt9->execute();
            $allTimeFollowUpMissing = (int)($stmt9->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt9->close();
        }

        $periodFollowUpMissing = 0;
        if ($hasFilter) {
            $sql9Period = $sql9 . " AND DATE(l.created_at) BETWEEN ? AND ?";
            if ($stmt9P = $this->db->prepare($sql9Period)) {
                $stmt9P->bind_param("iss", $userId, $startDate, $endDate);
                $stmt9P->execute();
                $periodFollowUpMissing = (int)($stmt9P->get_result()->fetch_assoc()['c'] ?? 0);
                $stmt9P->close();
                $totalFollowUpMissing = $periodFollowUpMissing;
            } else {
                $totalFollowUpMissing = $allTimeFollowUpMissing;
            }
        } else {
            $totalFollowUpMissing = $allTimeFollowUpMissing;
        }

        // 10. Today Follow Ups (Scheduled for today)
        $sql10 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            INNER JOIN (
                SELECT t1.lead_id, t1.next_follow_up_date
                FROM lead_follow_ups t1
                INNER JOIN (
                    SELECT lead_id, MAX(id) AS max_id
                    FROM lead_follow_ups
                    {$fuDelWhere}
                    GROUP BY lead_id
                ) t2 ON t1.id = t2.max_id
                {$fuDelWhere}
            ) lf ON lf.lead_id = l.id
            WHERE l.assigned_id = ? 
              {$lDelSql}
              AND lf.next_follow_up_date IS NOT NULL
              AND DATE(lf.next_follow_up_date) = CURDATE()
        ";
        $todayFollowUps = 0;
        if ($stmt10 = $this->db->prepare($sql10)) {
            $stmt10->bind_param("i", $userId);
            $stmt10->execute();
            $todayFollowUps = (int)($stmt10->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt10->close();
        }

        // 11. Today Follow Up Complete
        $sql11 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            WHERE l.assigned_id = ? 
              {$lDelSql}
              AND EXISTS (
                  SELECT 1 FROM lead_follow_ups f_sched 
                  WHERE f_sched.lead_id = l.id 
                    AND DATE(f_sched.next_follow_up_date) = CURDATE() 
                    " . ($fuHasDeletedAt ? "AND f_sched.deleted_at IS NULL" : "") . "
              )
              AND EXISTS (
                  SELECT 1 FROM lead_follow_ups f_done 
                  WHERE f_done.lead_id = l.id 
                    AND (DATE(f_done.created_at) = CURDATE() OR DATE(f_done.follow_up_date) = CURDATE())
                    " . ($fuHasDeletedAt ? "AND f_done.deleted_at IS NULL" : "") . "
              )
        ";
        $todayFollowUpComplete = 0;
        if ($stmt11 = $this->db->prepare($sql11)) {
            $stmt11->bind_param("i", $userId);
            $stmt11->execute();
            $todayFollowUpComplete = (int)($stmt11->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt11->close();
        }

        // 12. Remaining Today Follow Up
        $remainingTodayFollowUp = max(0, $todayFollowUps - $todayFollowUpComplete);

        // 13. Total Sales/Booked (Synchronized with CRM lead ownership)
        $sql13 = "SELECT COUNT(DISTINCT lb.id) as c 
                  FROM lead_bookings lb 
                  JOIN leads l ON l.id = lb.lead_id 
                  WHERE lb.deleted_at IS NULL AND l.deleted_at IS NULL 
                    AND (l.assigned_id = ? OR lb.sales_by_id = ? OR lb.created_by = ?) 
                    AND lb.booking_status IN ('completed', 'booked')";
        $totalSalesBookedUnits = 0;
        if ($stmt13 = $this->db->prepare($sql13)) {
            $stmt13->bind_param("iii", $userId, $userId, $userId);
            $stmt13->execute();
            $totalSalesBookedUnits = (int)($stmt13->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt13->close();
        }

        // 14. Target (from sales_targets)
        $monthlyBookingTarget = 0;
        $monthlySiteVisitTarget = 0;
        try {
            if (!empty($startDate) && !empty($endDate) && date('j', strtotime($startDate)) == 26 && date('j', strtotime($endDate)) == 25) {
                $targetStartMonth = date('Y-m-01', strtotime($endDate));
                $targetEndMonth = date('Y-m-01', strtotime($endDate));
            } else {
                $targetStartMonth = !empty($startDate) ? date('Y-m-01', strtotime($startDate)) : sprintf('%04d-%02d-01', $year, $startMonth);
                $targetEndMonth = !empty($endDate) ? date('Y-m-01', strtotime($endDate)) : sprintf('%04d-%02d-01', $year, $endMonth);
            }
            $sql14 = "SELECT SUM(booking_target) as booking_target, SUM(site_visit_target) as site_visit_target FROM sales_targets WHERE sales_user_id = ? AND month BETWEEN ? AND ?";
            if ($stmt14 = $this->db->prepare($sql14)) {
                $stmt14->bind_param("iss", $userId, $targetStartMonth, $targetEndMonth);
                $stmt14->execute();
                $targetRow = $stmt14->get_result()->fetch_assoc();
                $monthlyBookingTarget = (int)($targetRow['booking_target'] ?? 0);
                $monthlySiteVisitTarget = (int)($targetRow['site_visit_target'] ?? 0);
                $stmt14->close();
            }
        } catch (Throwable $e) {
            error_log("Lead::getExecutiveDashboardMetrics sales_targets error: " . $e->getMessage());
        }

        // 15. Period / Current Month Booking (Synchronized with 26th-to-25th reporting cycle)
        $curDay = (int)date('j');
        $curYear = (int)date('Y');
        $curMonth = (int)date('n');
        if ($curDay >= 26) {
            $cMonth = $curMonth + 1;
            $cYear = $curYear;
            if ($cMonth > 12) { $cMonth = 1; $cYear++; }
        } else {
            $cMonth = $curMonth;
            $cYear = $curYear;
        }
        $pMonth = $cMonth - 1;
        $pYear = $cYear;
        if ($pMonth < 1) { $pMonth = 12; $pYear--; }
        $cycleBkStart = sprintf('%04d-%02d-26', $pYear, $pMonth);
        $cycleBkEnd   = sprintf('%04d-%02d-25', $cYear, $cMonth);

        $bkStart = $hasFilter ? $startDate : $cycleBkStart;
        $bkEnd   = $hasFilter ? $endDate : $cycleBkEnd;

        $sql15 = "SELECT COUNT(DISTINCT lb.id) as total_bookings, COUNT(DISTINCT lb.lead_id) as booked_leads 
                  FROM lead_bookings lb 
                  JOIN leads l ON l.id = lb.lead_id 
                  WHERE lb.deleted_at IS NULL AND l.deleted_at IS NULL 
                    AND (l.assigned_id = ? OR lb.sales_by_id = ? OR lb.created_by = ?) 
                    AND lb.booking_status IN ('completed', 'booked') 
                    AND (DATE(lb.booking_date) BETWEEN ? AND ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) BETWEEN ? AND ?))";
        $currentMonthBooking = 0;
        $currentMonthBookedLeads = 0;
        if ($stmt15 = $this->db->prepare($sql15)) {
            $stmt15->bind_param("iiissss", $userId, $userId, $userId, $bkStart, $bkEnd, $bkStart, $bkEnd);
            $stmt15->execute();
            $bkRow15 = $stmt15->get_result()->fetch_assoc();
            $currentMonthBooking = (int)($bkRow15['total_bookings'] ?? 0);
            $currentMonthBookedLeads = (int)($bkRow15['booked_leads'] ?? 0);
            $stmt15->close();
        }

        $bookingAchievement = $monthlyBookingTarget > 0 ? round(($currentMonthBooking / $monthlyBookingTarget) * 100, 1) : 0;

        // 16. Total Visited Complete (All-time)
        $leadTypeCond = $hasLeadType ? "l.lead_type IN ('Visit Completed', 'Visited')" : "0=1";
        $lastActionCond = $hasLastLeadAction ? "l.last_lead_action = 'visited'" : "0=1";

        $sql16 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id {$fuDelCond}
            WHERE 1=1
              {$lDelSql}
              AND (
                  (l.assigned_id = ? AND ({$leadTypeCond} OR {$lastActionCond}))
                  OR ((l.assigned_id = ? OR lf.assigned_with = ? OR lf.created_by = ?) 
                      AND (lf.lead_status IN ('Visit Completed', 'Visited') OR lf.follow_up_type = 'visit') 
                      AND lf.status != 'canceled')
              )
        ";
        $totalVisitedComplete = 0;
        if ($stmt16 = $this->db->prepare($sql16)) {
            $stmt16->bind_param("iiii", $userId, $userId, $userId, $userId);
            $stmt16->execute();
            $totalVisitedComplete = (int)($stmt16->get_result()->fetch_assoc()['c'] ?? 0);
            $stmt16->close();
        }

        // 17. Period / This Month Visited (Synchronized with 26th-to-25th reporting cycle)
        $vStart = $hasFilter ? $startDate : $cycleBkStart;
        $vEnd   = $hasFilter ? $endDate : $cycleBkEnd;
        $sql17 = "
            SELECT COUNT(DISTINCT lf.id) as total_visits, COUNT(DISTINCT l.id) as visited_leads
            FROM leads l
            INNER JOIN lead_follow_ups lf ON lf.lead_id = l.id {$fuDelCond}
            WHERE 1=1
              {$lDelSql}
              AND (l.assigned_id = ? OR lf.assigned_with = ? OR lf.created_by = ?)
              AND (lf.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf.follow_up_type) = 'visit')
              AND lf.status != 'canceled'
              AND (DATE(lf.follow_up_date) BETWEEN ? AND ? OR (lf.follow_up_date IS NULL AND DATE(lf.created_at) BETWEEN ? AND ?))
        ";
        $thisMonthVisited = 0;
        $thisMonthVisitedLeads = 0;
        if ($stmt17 = $this->db->prepare($sql17)) {
            $stmt17->bind_param("iiissss", $userId, $userId, $userId, $vStart, $vEnd, $vStart, $vEnd);
            $stmt17->execute();
            $vRow17 = $stmt17->get_result()->fetch_assoc();
            $thisMonthVisited = (int)($vRow17['total_visits'] ?? 0);
            $thisMonthVisitedLeads = (int)($vRow17['visited_leads'] ?? 0);
            $stmt17->close();
        }

        $visitAchievement = $monthlySiteVisitTarget > 0 ? round(($thisMonthVisited / $monthlySiteVisitTarget) * 100, 1) : 0;

        return [
            'totalLeadsAssigned'     => $totalLeadsAssigned,
            'allTimeLeadsAssigned'   => $allTimeLeadsAssigned,
            'periodLeadsAssigned'    => $periodLeadsAssigned,
            'totalLeadsContacted'    => $totalLeadsContacted,
            'allTimeLeadsContacted'  => $allTimeLeadsContacted,
            'periodLeadsContacted'   => $periodLeadsContacted,
            'totalContactedPct'      => $totalContactedPct,
            'allTimeContactedPct'    => $allTimeContactedPct,
            'totalNotContacted'      => $totalNotContacted,
            'allTimeNotContacted'    => $allTimeNotContacted,
            'periodNotContacted'     => $periodNotContacted,
            'todayLeadsAssigned'     => $todayLeadsAssigned,
            'todayLeadsContacted'    => $todayLeadsContacted,
            'todayNotContacted'      => $todayNotContacted,
            'totalFollowUpComplete'  => $totalFollowUpComplete,
            'totalFollowUpMissing'   => $totalFollowUpMissing,
            'allTimeFollowUpMissing' => $allTimeFollowUpMissing,
            'periodFollowUpMissing'  => $periodFollowUpMissing,
            'todayFollowUps'         => $todayFollowUps,
            'todayFollowUpComplete'  => $todayFollowUpComplete,
            'remainingTodayFollowUp' => $remainingTodayFollowUp,
            'totalSalesBookedUnits'  => $totalSalesBookedUnits,
            'monthlyBookingTarget'   => $monthlyBookingTarget,
            'monthlySiteVisitTarget' => $monthlySiteVisitTarget,
            'currentMonthBooking'    => $currentMonthBooking,
            'currentMonthBookedLeads'=> $currentMonthBookedLeads,
            'bookingAchievement'     => $bookingAchievement,
            'totalVisitedComplete'   => $totalVisitedComplete,
            'thisMonthVisited'       => $thisMonthVisited,
            'thisMonthVisitedLeads'  => $thisMonthVisitedLeads,
            'visitAchievement'       => $visitAchievement,
            'cycleBkStart'           => $cycleBkStart,
            'cycleBkEnd'             => $cycleBkEnd,
            'hasFilter'              => $hasFilter,
            'filterStartDate'        => $startDate,
            'filterEndDate'          => $endDate,
        ];
    }

    /**
     * Fetch all active members (users and employees) for a team.
     *
     * @param int $teamId
     * @return array
     */
    public function getTeamMemberUsers(int|array $teamId): array
    {
        $teamIds = is_array($teamId)
            ? array_values(array_unique(array_filter(array_map('intval', $teamId), fn($id) => $id > 0)))
            : ((int)$teamId > 0 ? [(int)$teamId] : []);
        if (empty($teamIds)) {
            return [];
        }
        $teamIdsList = implode(',', $teamIds);

        $sql = "
            SELECT DISTINCT 
                u.id as user_id, 
                e.id as emp_id,
                e.employee_id,
                COALESCE(u.full_name, e.full_name) as full_name,
                COALESCE(e.is_team_leader, 0) as is_team_leader,
                COALESCE(e.is_sales_co_leader, 0) as is_sales_co_leader,
                u.role_id,
                r.name as role_name,
                d.name as designation_name
            FROM employees e
            LEFT JOIN users u ON (u.id = e.user_id OR (e.user_id IS NULL AND u.employee_id = e.id))
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN designations d ON d.id = e.designation_id
            WHERE (e.team_id IN ({$teamIdsList}) OR u.team_id IN ({$teamIdsList}))
              AND (e.employee_status IS NULL OR e.employee_status NOT IN ('resigned', 'terminated', 'inactive'))
            ORDER BY is_team_leader DESC, is_sales_co_leader DESC, full_name ASC
        ";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Fetch Team Overview Dashboard metrics.
     * If $selectedUserId is specified, returns individual executive dashboard metrics.
     * Otherwise, returns team aggregate metrics across all active team members.
     *
     * @param int $teamId
     * @param int|null $selectedUserId
     * @param int|null $year
     * @param int|null $startMonth
     * @param int|null $endMonth
     * @param bool $hasFilter
     * @param string|null $customFromDate
     * @param string|null $customToDate
     * @return array
     */
    public function getTeamDashboardMetrics(
        int|array $teamId, 
        ?int $selectedUserId = null,
        ?int $year = null, 
        ?int $startMonth = null, 
        ?int $endMonth = null, 
        bool $hasFilter = false,
        ?string $customFromDate = null,
        ?string $customToDate = null
    ): array {
        // When an individual member is selected, delegate to getExecutiveDashboardMetrics
        if (!empty($selectedUserId) && (int)$selectedUserId > 0) {
            return $this->getExecutiveDashboardMetrics(
                (int)$selectedUserId, 
                $year, 
                $startMonth, 
                $endMonth, 
                $hasFilter, 
                $customFromDate, 
                $customToDate
            );
        }

        // Date range normalization
        if (!empty($customFromDate) && !empty($customToDate)) {
            $startDate = $customFromDate;
            $endDate = $customToDate;
            $year = (int)date('Y', strtotime($startDate));
            $startMonth = (int)date('m', strtotime($startDate));
            $endMonth = (int)date('m', strtotime($endDate));
        } else {
            $year = $year ?: (int)date('Y');
            $startMonth = $startMonth ?: (int)date('m');
            $endMonth = $endMonth ?: $startMonth;
            $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
            $endDate = date('Y-m-t', strtotime(sprintf('%04d-%02d-01', $year, $endMonth)));
        }

        // Standard 26th-to-25th reporting cycle
        $curDay = (int)date('j');
        $curYear = (int)date('Y');
        $curMonth = (int)date('n');
        if ($curDay >= 26) {
            $cMonth = $curMonth + 1;
            $cYear = $curYear;
            if ($cMonth > 12) { $cMonth = 1; $cYear++; }
        } else {
            $cMonth = $curMonth;
            $cYear = $curYear;
        }
        $pMonth = $cMonth - 1;
        $pYear = $cYear;
        if ($pMonth < 1) { $pMonth = 12; $pYear--; }
        $cycleBkStart = sprintf('%04d-%02d-26', $pYear, $pMonth);
        $cycleBkEnd   = sprintf('%04d-%02d-25', $cYear, $cMonth);

        $bkStart = $hasFilter ? $startDate : $cycleBkStart;
        $bkEnd   = $hasFilter ? $endDate : $cycleBkEnd;

        // Fetch team members user IDs
        $teamIds = is_array($teamId)
            ? array_values(array_unique(array_filter(array_map('intval', $teamId), fn($id) => $id > 0)))
            : ((int)$teamId > 0 ? [(int)$teamId] : []);
        $teamIdsList = !empty($teamIds) ? implode(',', $teamIds) : '0';

        $members = $this->getTeamMemberUsers($teamIds);
        $memberIds = [];
        foreach ($members as $m) {
            if (!empty($m['user_id'])) {
                $memberIds[] = (int)$m['user_id'];
            }
        }
        $memberIdsList = !empty($memberIds) ? implode(',', $memberIds) : '0';

        // Team Lead Scope condition (consistent with Lead::countAllWithFilter)
        $leadScope = "((l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND (l.assigned_id IN ({$memberIdsList}) OR l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id IN ({$teamIdsList})))) OR ((l.assigned_id IS NULL OR l.assigned_id = 0) AND l.team_id IN ({$teamIdsList})))";

        // Hide undistributed automated leads from sales users until approved (distribute = 1)
        $loggedInRole = $_SESSION['user']['role_id'] ?? 0;
        $autoFilter = "";
        if (!in_array((int)$loggedInRole, [1, 10, 11])) {
            [$hasAutomatedCol, $hasDistributeCol] = $this->getLeadAutomationColumnStatus();
            if ($hasAutomatedCol && $hasDistributeCol) {
                $autoFilter = " AND NOT (l.is_automated = 1 AND l.distribute = 0)";
            }
        }

        // Hide leads that belong to Role 33 and 35's teams from other sales users (like Role 5 and 9)
        $roleIsolation = "";
        if (!in_array((int)$loggedInRole, [1, 3, 9, 32, 33, 34, 35])) {
            $roleIsolation = " AND (l.team_id IS NULL OR l.team_id = 0 OR l.team_id NOT IN (
                SELECT e.team_id FROM employees e JOIN users u ON u.id = e.user_id WHERE u.role_id IN (33, 35) AND e.team_id IS NOT NULL AND e.team_id > 0
            ))";
        }

        // Dynamic table column checks
        $hasAssignedAt       = $this->hasTableColumn('leads', 'assigned_at');
        $hasFirstContactedAt = $this->hasTableColumn('leads', 'first_contacted_at');
        $hasLastLeadAction   = $this->hasTableColumn('leads', 'last_lead_action');
        $hasCallStatus       = $this->hasTableColumn('leads', 'call_status');
        $hasCommType         = $this->hasTableColumn('leads', 'communication_type');
        $hasLeadType         = $this->hasTableColumn('leads', 'lead_type');
        $hasDeletedAt        = $this->hasTableColumn('leads', 'deleted_at');
        $fuHasDeletedAt      = $this->hasTableColumn('lead_follow_ups', 'deleted_at');
        $bkHasDeletedAt      = $this->hasTableColumn('lead_bookings', 'deleted_at');

        $assignedDateExpr = $hasAssignedAt 
            ? "(CASE WHEN (l.assigned_at IS NOT NULL AND l.assigned_at > '1970-01-01 00:00:00') THEN l.assigned_at ELSE l.created_at END)"
            : "l.created_at";

        $leadsDelSql = $hasDeletedAt ? "AND l.deleted_at IS NULL" : "";
        $fuDelCond   = $fuHasDeletedAt ? "AND lf.deleted_at IS NULL" : "";
        $fuDelWhere  = $fuHasDeletedAt ? "WHERE deleted_at IS NULL" : "";
        $bkDelCond   = $bkHasDeletedAt ? "AND lb.deleted_at IS NULL" : "";
        $bkDelWhere  = $bkHasDeletedAt ? "AND deleted_at IS NULL" : "";

        // Contacted condition (consistent with countAllWithFilter)
        $orConds = [];
        if ($hasLastLeadAction) {
            $orConds[] = "LOWER(l.last_lead_action) IN ('contacted', 'follow up', 'visited', 'booked')";
        }
        if ($hasCallStatus) {
            $orConds[] = "l.call_status IN ('called', 'received')";
        }
        if ($hasCommType) {
            $orConds[] = "(l.communication_type IS NOT NULL AND l.communication_type != '' AND LOWER(l.communication_type) != 'not select')";
        }
        $orConds[] = "EXISTS (SELECT 1 FROM lead_follow_ups lf WHERE lf.lead_id = l.id {$fuDelCond})";
        $orConds[] = "EXISTS (SELECT 1 FROM lead_bookings lb WHERE lb.lead_id = l.id {$bkDelCond})";
        $contactedCond = "(l.assigned_id IS NOT NULL AND l.assigned_id > 0 AND (" . implode(" OR ", $orConds) . "))";

        $fuDelCondNc = $fuHasDeletedAt ? "AND lf_nc.deleted_at IS NULL" : "";
        $bkDelCondNc = $bkHasDeletedAt ? "AND lb_nc.deleted_at IS NULL" : "";
        $notContactedCond = "(l.assigned_id IS NOT NULL AND l.assigned_id > 0
            AND (l.last_lead_action IS NULL OR l.last_lead_action NOT IN ('contacted', 'follow up', 'booked', 'visited'))
            AND (l.call_status IS NULL OR l.call_status NOT IN ('called', 'received'))
            AND (l.communication_type IS NULL OR l.communication_type = '' OR LOWER(l.communication_type) = 'not select')
            AND NOT EXISTS (SELECT 1 FROM lead_follow_ups lf_nc WHERE lf_nc.lead_id = l.id {$fuDelCondNc})
            AND NOT EXISTS (SELECT 1 FROM lead_bookings lb_nc WHERE lb_nc.lead_id = l.id {$bkDelCondNc}))";

        // 1. All-time Leads Assigned
        $sql1 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation}";
        $allTimeLeadsAssigned = 0;
        if ($res1 = $this->db->query($sql1)) {
            $allTimeLeadsAssigned = (int)($res1->fetch_assoc()['c'] ?? 0);
        }

        // 2. All-time Leads Contacted
        $sql2 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation} AND {$contactedCond}";
        $allTimeLeadsContacted = 0;
        if ($res2 = $this->db->query($sql2)) {
            $allTimeLeadsContacted = (int)($res2->fetch_assoc()['c'] ?? 0);
        }
        $allTimeContactedPct = ($allTimeLeadsAssigned > 0) ? round(($allTimeLeadsContacted / $allTimeLeadsAssigned) * 100, 1) : 0;

        // 3. All-time Leads Not Contacted
        $sql3 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation} AND {$notContactedCond}";
        $allTimeNotContacted = 0;
        if ($res3 = $this->db->query($sql3)) {
            $allTimeNotContacted = (int)($res3->fetch_assoc()['c'] ?? 0);
        }

        // Period Metrics
        $sqlP1 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation} AND DATE(l.created_at) BETWEEN ? AND ?";
        $periodLeadsAssigned = 0;
        if ($stmtP1 = $this->db->prepare($sqlP1)) {
            $stmtP1->bind_param("ss", $startDate, $endDate);
            $stmtP1->execute();
            $periodLeadsAssigned = (int)($stmtP1->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtP1->close();
        }

        $sqlP2 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation} AND DATE(l.created_at) BETWEEN ? AND ? AND {$contactedCond}";
        $periodLeadsContacted = 0;
        if ($stmtP2 = $this->db->prepare($sqlP2)) {
            $stmtP2->bind_param("ss", $startDate, $endDate);
            $stmtP2->execute();
            $periodLeadsContacted = (int)($stmtP2->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtP2->close();
        }

        $sqlP3 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation} AND DATE(l.created_at) BETWEEN ? AND ? AND {$notContactedCond}";
        $periodNotContacted = 0;
        if ($stmtP3 = $this->db->prepare($sqlP3)) {
            $stmtP3->bind_param("ss", $startDate, $endDate);
            $stmtP3->execute();
            $periodNotContacted = (int)($stmtP3->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtP3->close();
        }

        $periodContactedPct = ($periodLeadsAssigned > 0) ? round(($periodLeadsContacted / $periodLeadsAssigned) * 100, 1) : 0;

        if ($hasFilter) {
            $totalLeadsAssigned  = $periodLeadsAssigned;
            $totalLeadsContacted = $periodLeadsContacted;
            $totalNotContacted   = $periodNotContacted;
            $totalContactedPct   = $periodContactedPct;
        } else {
            $totalLeadsAssigned  = $allTimeLeadsAssigned;
            $totalLeadsContacted = $allTimeLeadsContacted;
            $totalNotContacted   = $allTimeNotContacted;
            $totalContactedPct   = $allTimeContactedPct;
        }

        // Today Leads Assigned
        $sql5 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation} AND DATE({$assignedDateExpr}) = CURDATE()";
        $todayLeadsAssigned = 0;
        if ($res5 = $this->db->query($sql5)) {
            $todayLeadsAssigned = (int)($res5->fetch_assoc()['c'] ?? 0);
        }

        // Today Leads Contacted
        $firstContactCond = "";
        if ($hasCallStatus) {
            if ($hasFirstContactedAt) {
                $firstContactCond = "OR (
                    l.call_status IN ('called', 'received', 'contacted') 
                    AND (
                        (l.first_contacted_at IS NOT NULL AND l.first_contacted_at >= {$assignedDateExpr})
                        OR (l.first_contacted_at IS NOT NULL AND DATE(l.first_contacted_at) = CURDATE())
                    )
                )";
            } else {
                $firstContactCond = "OR (l.call_status IN ('called', 'received', 'contacted') AND DATE(l.updated_at) = CURDATE())";
            }
        }
        $todayContactedCond = "(
            EXISTS (
                SELECT 1 FROM lead_follow_ups lf 
                WHERE lf.lead_id = l.id {$fuDelCond} 
                  AND (lf.created_by IN ({$memberIdsList}) OR lf.assigned_with IN ({$memberIdsList}) OR lf.created_at >= {$assignedDateExpr})
            )
            OR EXISTS (
                SELECT 1 FROM lead_bookings lb 
                WHERE lb.lead_id = l.id {$bkDelCond} 
                  AND (lb.sales_by_id IN ({$memberIdsList}) OR lb.created_by IN ({$memberIdsList}) OR lb.created_at >= {$assignedDateExpr})
            )
            {$firstContactCond}
        )";
        $sql6 = "SELECT COUNT(*) as c FROM leads l WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation} AND DATE({$assignedDateExpr}) = CURDATE() AND {$todayContactedCond}";
        $todayLeadsContacted = 0;
        if ($res6 = $this->db->query($sql6)) {
            $todayLeadsContacted = (int)($res6->fetch_assoc()['c'] ?? 0);
        }
        $todayNotContacted = max(0, $todayLeadsAssigned - $todayLeadsContacted);

        // Total Follow-up Complete
        $sql8 = "SELECT COUNT(*) as c FROM lead_follow_ups lf JOIN leads l ON l.id = lf.lead_id WHERE {$leadScope} {$leadsDelSql} {$fuDelCond} {$autoFilter} {$roleIsolation}";
        $totalFollowUpComplete = 0;
        if ($res8 = $this->db->query($sql8)) {
            $totalFollowUpComplete = (int)($res8->fetch_assoc()['c'] ?? 0);
        }

        // Follow-up Missing
        $sql9 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            INNER JOIN (
                SELECT t1.lead_id, t1.next_follow_up_date
                FROM lead_follow_ups t1
                INNER JOIN (
                    SELECT lead_id, MAX(id) AS max_id
                    FROM lead_follow_ups
                    {$fuDelWhere}
                    GROUP BY lead_id
                ) t2 ON t1.id = t2.max_id
                {$fuDelWhere}
            ) lf ON lf.lead_id = l.id
            WHERE {$leadScope}
              {$leadsDelSql}
              {$autoFilter}
              {$roleIsolation}
              AND lf.next_follow_up_date IS NOT NULL
              AND lf.next_follow_up_date > '1000-01-01'
              AND DATE(lf.next_follow_up_date) < CURDATE()
        ";
        $allTimeFollowUpMissing = 0;
        if ($res9 = $this->db->query($sql9)) {
            $allTimeFollowUpMissing = (int)($res9->fetch_assoc()['c'] ?? 0);
        }

        $periodFollowUpMissing = 0;
        if ($hasFilter) {
            $sql9Period = $sql9 . " AND DATE(l.created_at) BETWEEN ? AND ?";
            if ($stmt9P = $this->db->prepare($sql9Period)) {
                $stmt9P->bind_param("ss", $startDate, $endDate);
                $stmt9P->execute();
                $periodFollowUpMissing = (int)($stmt9P->get_result()->fetch_assoc()['c'] ?? 0);
                $stmt9P->close();
            }
            $totalFollowUpMissing = $periodFollowUpMissing;
        } else {
            $totalFollowUpMissing = $allTimeFollowUpMissing;
        }

        // Today Follow-ups Scheduled
        $sql10 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            INNER JOIN (
                SELECT t1.lead_id, t1.next_follow_up_date
                FROM lead_follow_ups t1
                INNER JOIN (
                    SELECT lead_id, MAX(id) AS max_id
                    FROM lead_follow_ups
                    {$fuDelWhere}
                    GROUP BY lead_id
                ) t2 ON t1.id = t2.max_id
                {$fuDelWhere}
            ) lf ON lf.lead_id = l.id
            WHERE {$leadScope}
              {$leadsDelSql}
              {$autoFilter}
              {$roleIsolation}
              AND lf.next_follow_up_date IS NOT NULL
              AND DATE(lf.next_follow_up_date) = CURDATE()
        ";
        $todayFollowUps = 0;
        if ($res10 = $this->db->query($sql10)) {
            $todayFollowUps = (int)($res10->fetch_assoc()['c'] ?? 0);
        }

        // Today Follow-up Complete
        $sql11 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            WHERE {$leadScope}
              {$leadsDelSql}
              {$autoFilter}
              {$roleIsolation}
              AND EXISTS (
                  SELECT 1 FROM lead_follow_ups f_sched 
                  WHERE f_sched.lead_id = l.id 
                    AND DATE(f_sched.next_follow_up_date) = CURDATE() 
                    " . ($fuHasDeletedAt ? "AND f_sched.deleted_at IS NULL" : "") . "
              )
              AND EXISTS (
                  SELECT 1 FROM lead_follow_ups f_done 
                  WHERE f_done.lead_id = l.id 
                    AND (DATE(f_done.created_at) = CURDATE() OR DATE(f_done.follow_up_date) = CURDATE())
                    " . ($fuHasDeletedAt ? "AND f_done.deleted_at IS NULL" : "") . "
              )
        ";
        $todayFollowUpComplete = 0;
        if ($res11 = $this->db->query($sql11)) {
            $todayFollowUpComplete = (int)($res11->fetch_assoc()['c'] ?? 0);
        }
        $remainingTodayFollowUp = max(0, $todayFollowUps - $todayFollowUpComplete);

        // Total Sales / Booked (Units)
        $sql13 = "SELECT COUNT(DISTINCT lb.id) as c 
                  FROM lead_bookings lb 
                  JOIN leads l ON l.id = lb.lead_id 
                  WHERE lb.deleted_at IS NULL {$leadsDelSql} {$autoFilter} {$roleIsolation}
                    AND ({$leadScope} OR lb.sales_by_id IN ({$memberIdsList}) OR lb.created_by IN ({$memberIdsList}))
                    AND lb.booking_status IN ('completed', 'booked')";
        $totalSalesBookedUnits = 0;
        if ($res13 = $this->db->query($sql13)) {
            $totalSalesBookedUnits = (int)($res13->fetch_assoc()['c'] ?? 0);
        }

        // Targets from sales_targets
        if (!empty($startDate) && !empty($endDate) && date('j', strtotime($startDate)) == 26 && date('j', strtotime($endDate)) == 25) {
            $targetStartMonth = date('Y-m-01', strtotime($endDate));
            $targetEndMonth = date('Y-m-01', strtotime($endDate));
        } else {
            $targetStartMonth = !empty($startDate) ? date('Y-m-01', strtotime($startDate)) : sprintf('%04d-%02d-01', $year, $startMonth);
            $targetEndMonth = !empty($endDate) ? date('Y-m-01', strtotime($endDate)) : sprintf('%04d-%02d-01', $year, $endMonth);
        }
        $monthlyBookingTarget = 0;
        $monthlySiteVisitTarget = 0;
        $sql14 = "SELECT SUM(booking_target) as booking_target, SUM(site_visit_target) as site_visit_target 
                  FROM sales_targets 
                  WHERE sales_user_id IN ({$memberIdsList}) AND month BETWEEN ? AND ?";
        if ($stmt14 = $this->db->prepare($sql14)) {
            $stmt14->bind_param("ss", $targetStartMonth, $targetEndMonth);
            $stmt14->execute();
            $tRow14 = $stmt14->get_result()->fetch_assoc();
            $monthlyBookingTarget = (int)($tRow14['booking_target'] ?? 0);
            $monthlySiteVisitTarget = (int)($tRow14['site_visit_target'] ?? 0);
            $stmt14->close();
        }

        // Period / Current Month Booking
        $sql15 = "SELECT COUNT(DISTINCT lb.id) as total_bookings, COUNT(DISTINCT lb.lead_id) as booked_leads 
                  FROM lead_bookings lb 
                  JOIN leads l ON l.id = lb.lead_id 
                  WHERE lb.deleted_at IS NULL {$leadsDelSql} {$autoFilter} {$roleIsolation}
                    AND ({$leadScope} OR lb.sales_by_id IN ({$memberIdsList}) OR lb.created_by IN ({$memberIdsList}))
                    AND lb.booking_status IN ('completed', 'booked') 
                    AND (DATE(lb.booking_date) BETWEEN ? AND ? OR (lb.booking_date IS NULL AND DATE(lb.created_at) BETWEEN ? AND ?))";
        $currentMonthBooking = 0;
        $currentMonthBookedLeads = 0;
        if ($stmt15 = $this->db->prepare($sql15)) {
            $stmt15->bind_param("ssss", $bkStart, $bkEnd, $bkStart, $bkEnd);
            $stmt15->execute();
            $bkRow15 = $stmt15->get_result()->fetch_assoc();
            $currentMonthBooking = (int)($bkRow15['total_bookings'] ?? 0);
            $currentMonthBookedLeads = (int)($bkRow15['booked_leads'] ?? 0);
            $stmt15->close();
        }
        $bookingAchievement = $monthlyBookingTarget > 0 ? round(($currentMonthBooking / $monthlyBookingTarget) * 100, 1) : 0;

        // Total Visited Complete (All-time)
        $leadTypeCond = $hasLeadType ? "l.lead_type IN ('Visit Completed', 'Visited')" : "0=1";
        $lastActionCond = $hasLastLeadAction ? "l.last_lead_action = 'visited'" : "0=1";
        $sql16 = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id {$fuDelCond}
            WHERE 1=1
              {$leadsDelSql}
              {$autoFilter}
              {$roleIsolation}
              AND (
                  ({$leadScope} AND ({$leadTypeCond} OR {$lastActionCond}))
                  OR (({$leadScope} OR lf.assigned_with IN ({$memberIdsList}) OR lf.created_by IN ({$memberIdsList})) 
                      AND (lf.lead_status IN ('Visit Completed', 'Visited') OR lf.follow_up_type = 'visit') 
                      AND lf.status != 'canceled')
              )
        ";
        $totalVisitedComplete = 0;
        if ($res16 = $this->db->query($sql16)) {
            $totalVisitedComplete = (int)($res16->fetch_assoc()['c'] ?? 0);
        }

        // Period / This Month Visited
        $vStart = $hasFilter ? $startDate : $cycleBkStart;
        $vEnd   = $hasFilter ? $endDate : $cycleBkEnd;
        $sql17 = "
            SELECT COUNT(DISTINCT lf.id) as total_visits, COUNT(DISTINCT l.id) as visited_leads
            FROM leads l
            INNER JOIN lead_follow_ups lf ON lf.lead_id = l.id {$fuDelCond}
            WHERE 1=1
              {$leadsDelSql}
              {$autoFilter}
              {$roleIsolation}
              AND ({$leadScope} OR lf.assigned_with IN ({$memberIdsList}) OR lf.created_by IN ({$memberIdsList}))
              AND (lf.lead_status IN ('Visit Completed', 'Visited') OR LOWER(lf.follow_up_type) = 'visit')
              AND lf.status != 'canceled'
              AND (DATE(lf.follow_up_date) BETWEEN ? AND ? OR (lf.follow_up_date IS NULL AND DATE(lf.created_at) BETWEEN ? AND ?))
        ";
        $thisMonthVisited = 0;
        $thisMonthVisitedLeads = 0;
        if ($stmt17 = $this->db->prepare($sql17)) {
            $stmt17->bind_param("ssss", $vStart, $vEnd, $vStart, $vEnd);
            $stmt17->execute();
            $vRow17 = $stmt17->get_result()->fetch_assoc();
            $thisMonthVisited = (int)($vRow17['total_visits'] ?? 0);
            $thisMonthVisitedLeads = (int)($vRow17['visited_leads'] ?? 0);
            $stmt17->close();
        }
        $visitAchievement = $monthlySiteVisitTarget > 0 ? round(($thisMonthVisited / $monthlySiteVisitTarget) * 100, 1) : 0;

        // Deals Lost
        $sqlLost = "
            SELECT COUNT(DISTINCT l.id) as c
            FROM leads l
            WHERE {$leadScope} {$leadsDelSql} {$autoFilter} {$roleIsolation}
              AND (l.lead_type IN ('Not Interested', 'Lost', 'Dropped') OR l.last_lead_action = 'lost')
              AND DATE(l.created_at) BETWEEN ? AND ?
        ";
        $dealsLost = 0;
        if ($stmtLost = $this->db->prepare($sqlLost)) {
            $stmtLost->bind_param("ss", $startDate, $endDate);
            $stmtLost->execute();
            $dealsLost = (int)($stmtLost->get_result()->fetch_assoc()['c'] ?? 0);
            $stmtLost->close();
        }

        return [
            'totalLeadsAssigned'     => $totalLeadsAssigned,
            'allTimeLeadsAssigned'   => $allTimeLeadsAssigned,
            'periodLeadsAssigned'    => $periodLeadsAssigned,
            'totalLeadsContacted'    => $totalLeadsContacted,
            'allTimeLeadsContacted'  => $allTimeLeadsContacted,
            'periodLeadsContacted'   => $periodLeadsContacted,
            'totalContactedPct'      => $totalContactedPct,
            'allTimeContactedPct'    => $allTimeContactedPct,
            'totalNotContacted'      => $totalNotContacted,
            'allTimeNotContacted'    => $allTimeNotContacted,
            'periodNotContacted'     => $periodNotContacted,
            'todayLeadsAssigned'     => $todayLeadsAssigned,
            'todayLeadsContacted'    => $todayLeadsContacted,
            'todayNotContacted'      => $todayNotContacted,
            'totalFollowUpComplete'  => $totalFollowUpComplete,
            'totalFollowUpMissing'   => $totalFollowUpMissing,
            'allTimeFollowUpMissing' => $allTimeFollowUpMissing,
            'periodFollowUpMissing'  => $periodFollowUpMissing,
            'todayFollowUps'         => $todayFollowUps,
            'todayFollowUpComplete'  => $todayFollowUpComplete,
            'remainingTodayFollowUp' => $remainingTodayFollowUp,
            'totalSalesBookedUnits'  => $totalSalesBookedUnits,
            'monthlyBookingTarget'   => $monthlyBookingTarget,
            'monthlySiteVisitTarget' => $monthlySiteVisitTarget,
            'currentMonthBooking'    => $currentMonthBooking,
            'currentMonthBookedLeads'=> $currentMonthBookedLeads,
            'bookingAchievement'     => $bookingAchievement,
            'totalVisitedComplete'   => $totalVisitedComplete,
            'thisMonthVisited'       => $thisMonthVisited,
            'thisMonthVisitedLeads'  => $thisMonthVisitedLeads,
            'visitAchievement'       => $visitAchievement,
            'dealsLost'              => $dealsLost,
            'cycleBkStart'           => $cycleBkStart,
            'cycleBkEnd'             => $cycleBkEnd,
            'hasFilter'              => $hasFilter,
            'filterStartDate'        => $startDate,
            'filterEndDate'          => $endDate,
        ];
    }

    /**
     * Get Sales and Leads Performance breakdown for each member of a team.
     * Deducts refunded bookings (crr status IN ('pending', 'approved', 'refunded') or lb.booking_status IN ('refunded', 'cancelled')).
     * Calculates Land Share Value and Payments collected.
     */
    public function getTeamMemberSalesPerformance(int|array $teamId, ?string $startDate = null, ?string $endDate = null, bool $hasFilter = false): array
    {
        $members = $this->getTeamMemberUsers($teamId);
        $loggedInRole = $_SESSION['user']['role_id'] ?? 0;
        $autoFilter = "";
        if (!in_array((int)$loggedInRole, [1, 10, 11])) {
            [$hasAutomatedCol, $hasDistributeCol] = $this->getLeadAutomationColumnStatus();
            if ($hasAutomatedCol && $hasDistributeCol) {
                $autoFilter = " AND NOT (l.is_automated = 1 AND l.distribute = 0)";
            }
        }

        $perfData = [];
        $teamTotals = [
            'period_leads'     => 0,
            'all_time_leads'   => 0,
            'gross_sales'      => 0,
            'refund_sales'     => 0,
            'net_sales'        => 0,
            'land_share_value' => 0,
            'total_price_value'=> 0,
            'land_share_paid'  => 0,
            'total_paid'       => 0,
        ];

        $dateBookingCond = "";
        if ($hasFilter && !empty($startDate) && !empty($endDate)) {
            $dateBookingCond = "AND (DATE(lb.booking_date) BETWEEN '{$startDate}' AND '{$endDate}' OR (lb.booking_date IS NULL AND DATE(lb.created_at) BETWEEN '{$startDate}' AND '{$endDate}'))";
        }

        foreach ($members as $m) {
            $uid = (int)($m['user_id'] ?? 0);
            if (!$uid) continue;

            // Leads assigned in period and all time
            $leadDateSql = ($hasFilter && !empty($startDate) && !empty($endDate)) ? "AND DATE(l.created_at) BETWEEN '{$startDate}' AND '{$endDate}'" : "";
            $periodLeads = (int)($this->db->query("SELECT COUNT(*) as c FROM leads l WHERE l.assigned_id = {$uid} AND l.deleted_at IS NULL {$autoFilter} {$leadDateSql}")->fetch_assoc()['c'] ?? 0);
            $allTimeLeads = (int)($this->db->query("SELECT COUNT(*) as c FROM leads l WHERE l.assigned_id = {$uid} AND l.deleted_at IS NULL {$autoFilter}")->fetch_assoc()['c'] ?? 0);

            // Bookings for this member
            $bkSql = "
                SELECT 
                    lb.id,
                    lb.booking_status,
                    lb.agreement_value,
                    COALESCE(NULLIF(f.land_share_value, 0), lb.agreement_value, 0) as land_share_val,
                    COALESCE(NULLIF(f.price_value, 0), lb.agreement_value, 0) as total_price_val,
                    CASE 
                        WHEN lb.booking_status IN ('refunded', 'cancelled') THEN 1
                        WHEN crr.id IS NOT NULL THEN 1
                        ELSE 0
                    END as is_refunded,
                    crr.refund_amount
                FROM lead_bookings lb
                JOIN leads l ON l.id = lb.lead_id
                LEFT JOIN flats f ON f.id = lb.flat_id
                LEFT JOIN client_refund_requests crr ON crr.booking_id = lb.id AND crr.status IN ('pending', 'approved', 'refunded')
                WHERE lb.deleted_at IS NULL
                  AND l.deleted_at IS NULL
                  {$autoFilter}
                  AND (lb.sales_by_id = {$uid} OR (lb.sales_by_id IS NULL AND l.assigned_id = {$uid}))
                  {$dateBookingCond}
            ";
            $bkRes = $this->db->query($bkSql);

            $grossSales = 0;
            $refundSales = 0;
            $netSales = 0;
            $memberLandShareValue = 0;
            $memberTotalPriceValue = 0;
            $validBkIds = [];

            if ($bkRes) {
                while ($bk = $bkRes->fetch_assoc()) {
                    $grossSales++;
                    if ((int)$bk['is_refunded'] === 1) {
                        $refundSales++;
                    } else {
                        $netSales++;
                        $memberLandShareValue += (float)$bk['land_share_val'];
                        $memberTotalPriceValue += (float)$bk['total_price_val'];
                        $validBkIds[] = (int)$bk['id'];
                    }
                }
            }

            $memberLandSharePaid = 0;
            $memberTotalPaid = 0;
            if (!empty($validBkIds)) {
                $bList = implode(',', $validBkIds);
                $paySql = "
                    SELECT 
                        SUM(CASE WHEN (lp.payment_status IN ('paid', 'partially_paid', 'pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) as total_paid,
                        SUM(CASE WHEN lp.payment_type IN ('booking_money', 'registration', 'land_share') AND (lp.payment_status IN ('paid', 'partially_paid', 'pending')) AND (lp.verification_status IS NULL OR lp.verification_status != 'rejected') THEN lp.amount ELSE 0 END) as land_share_paid
                    FROM lead_payments lp
                    WHERE lp.booking_id IN ($bList)
                      AND (lp.is_trashed = 0 OR lp.is_trashed IS NULL)
                ";
                $pRow = $this->db->query($paySql)->fetch_assoc();
                $memberTotalPaid = (float)($pRow['total_paid'] ?? 0);
                $memberLandSharePaid = (float)($pRow['land_share_paid'] ?? 0);
            }

            $leadBase = ($hasFilter && $periodLeads > 0) ? $periodLeads : $allTimeLeads;
            $conversionRate = $leadBase > 0 ? round(($netSales / $leadBase) * 100, 1) : 0;
            $collectionRate = $memberLandShareValue > 0 ? round(($memberLandSharePaid / $memberLandShareValue) * 100, 1) : 0;

            $perfData[] = [
                'user_id'          => $uid,
                'employee_id'      => $m['employee_id'] ?? '',
                'full_name'        => $m['full_name'],
                'role_name'        => $m['role_name'] ?? 'Executive',
                'designation'      => !empty($m['designation_name']) ? $m['designation_name'] : ($m['role_name'] ?? 'Sales Executive'),
                'designation_name' => !empty($m['designation_name']) ? $m['designation_name'] : ($m['role_name'] ?? 'Sales Executive'),
                'is_team_leader'   => (int)($m['is_team_leader'] ?? 0),
                'period_leads'     => $periodLeads,
                'all_time_leads'   => $allTimeLeads,
                'gross_sales'      => $grossSales,
                'refund_sales'     => $refundSales,
                'net_sales'        => $netSales,
                'conversion_rate'  => $conversionRate,
                'land_share_value' => $memberLandShareValue,
                'total_price_value'=> $memberTotalPriceValue,
                'land_share_paid'  => $memberLandSharePaid,
                'total_paid'       => $memberTotalPaid,
                'collection_rate'  => $collectionRate,
            ];

            $teamTotals['period_leads']     += $periodLeads;
            $teamTotals['all_time_leads']   += $allTimeLeads;
            $teamTotals['gross_sales']      += $grossSales;
            $teamTotals['refund_sales']     += $refundSales;
            $teamTotals['net_sales']        += $netSales;
            $teamTotals['land_share_value'] += $memberLandShareValue;
            $teamTotals['total_price_value']+= $memberTotalPriceValue;
            $teamTotals['land_share_paid']  += $memberLandSharePaid;
            $teamTotals['total_paid']       += $memberTotalPaid;
        }

        $teamLeadBase = ($hasFilter && $teamTotals['period_leads'] > 0) ? $teamTotals['period_leads'] : $teamTotals['all_time_leads'];
        $teamTotals['conversion_rate'] = $teamLeadBase > 0 ? round(($teamTotals['net_sales'] / $teamLeadBase) * 100, 1) : 0;
        $teamTotals['collection_rate'] = $teamTotals['land_share_value'] > 0 ? round(($teamTotals['land_share_paid'] / $teamTotals['land_share_value']) * 100, 1) : 0;

        return [
            'members' => $perfData,
            'totals'  => $teamTotals,
        ];
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
        $sql = "SELECT DISTINCT u.id, u.full_name, u.email, r.name as role_name, e.employee_id as employee_code
            FROM users u
            JOIN leads l ON l.assigned_id = u.id
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            WHERE l.deleted_at IS NULL
              AND (e.employee_status IS NULL OR (e.deleted_at IS NULL AND LOWER(TRIM(e.employee_status)) NOT IN ('resigned', 'inactive', 'terminated')))
              AND (e.employee_type IS NULL OR LOWER(TRIM(e.employee_type)) NOT IN ('resigned', 'inactive', 'terminated'))";

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

            // Total leads (filtered by selected year/month, or all-time if not specified)
            $leadWhere = ["assigned_id = ?", "deleted_at IS NULL"];
            $leadParams = [$userId];
            $leadTypes = "i";
            if ($year && $month) {
                $leadWhere[] = "((assigned_at IS NOT NULL AND YEAR(assigned_at) = ? AND MONTH(assigned_at) = ?) OR (assigned_at IS NULL AND YEAR(created_at) = ? AND MONTH(created_at) = ?))";
                $leadParams[] = $year;
                $leadParams[] = $month;
                $leadParams[] = $year;
                $leadParams[] = $month;
                $leadTypes .= "iiii";
            } elseif ($year) {
                $leadWhere[] = "((assigned_at IS NOT NULL AND YEAR(assigned_at) = ?) OR (assigned_at IS NULL AND YEAR(created_at) = ?))";
                $leadParams[] = $year;
                $leadParams[] = $year;
                $leadTypes .= "ii";
            } elseif ($month) {
                $leadWhere[] = "((assigned_at IS NOT NULL AND MONTH(assigned_at) = ?) OR (assigned_at IS NULL AND MONTH(created_at) = ?))";
                $leadParams[] = $month;
                $leadParams[] = $month;
                $leadTypes .= "ii";
            }
            $leadsSql = "SELECT COUNT(*) AS count FROM leads WHERE " . implode(" AND ", $leadWhere);
            $leadsStmt = $this->db->prepare($leadsSql);
            $leadsStmt->bind_param($leadTypes, ...$leadParams);
            $leadsStmt->execute();
            $totalLeads = $leadsStmt->get_result()->fetch_assoc()['count'] ?? 0;

            // Completed visits
            $visitWhere = ["assigned_with = ?", "follow_up_type = 'visit'", "status = 'completed'", "deleted_at IS NULL"];
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

            // Total bookings (paid/partially_paid, verified, filtered by sales_by_id)
            $bookingWhere = ["lb.sales_by_id = ?", "lb.deleted_at IS NULL", "lb.booking_status IN ('completed', 'booked')", "lp.payment_status IN ('paid', 'partially_paid')", "lp.verification_status = 'verified'"];
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
                     JOIN lead_payments lp ON lp.booking_id = lb.id AND lp.is_trashed = 0 AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND COALESCE(lp.is_emi_discount, 0) = 0
                     WHERE lb.sales_by_id = ? AND lb.booking_status IN ('completed', 'booked') AND lb.deleted_at IS NULL" .
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
                       JOIN lead_payments lp ON lp.booking_id = lb.id AND lp.is_trashed = 0 AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified' AND COALESCE(lp.is_emi_discount, 0) = 0
                       JOIN projects p ON lb.project_id = p.id
                       WHERE lb.deleted_at IS NULL AND lb.booking_status IN ('completed', 'booked') AND lb.sales_by_id = ?";
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

            // Average First Response Time (hours from assigned_at to first_contacted_at)
            $respWhere = ["assigned_id = ?", "assigned_at IS NOT NULL", "first_contacted_at IS NOT NULL", "deleted_at IS NULL"];
            $respParams = [$userId];
            $respTypes = "i";
            if ($year) {
                $respWhere[] = "YEAR(assigned_at) = ?";
                $respParams[] = $year;
                $respTypes .= "i";
            }
            if ($month) {
                $respWhere[] = "MONTH(assigned_at) = ?";
                $respParams[] = $month;
                $respTypes .= "i";
            }
            $respSql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, assigned_at, first_contacted_at)) AS avg_min FROM leads WHERE " . implode(" AND ", $respWhere);
            $respStmt = $this->db->prepare($respSql);
            $avgResponseHours = null;
            if ($respStmt) {
                $respStmt->bind_param($respTypes, ...$respParams);
                $respStmt->execute();
                $avgMin = $respStmt->get_result()->fetch_assoc()['avg_min'] ?? null;
                $respStmt->close();
                $avgResponseHours = ($avgMin !== null && $avgMin >= 0) ? round($avgMin / 60, 1) : null;
            }

            $kpiData[] = [
                'user' => $user,
                'total_leads' => (int) $totalLeads,
                'total_visits' => (int) $totalVisits,
                'total_bookings' => (int) $totalBookings,
                'total_money' => (float) $totalMoney,
                'conversion_rate' => $conversionRate,
                'avg_response_hours' => $avgResponseHours,
                'project_bookings' => $projectBookings
            ];
        }

        return $kpiData;
    }



    public function create($data)
    {
        $this->ensureLeadsEnumColumns();

        // Helper for nullable strings (empty string -> null)
        $nn = function ($key, $maxLen = null) use (&$data) {
            if (!isset($data[$key])) return null;
            $val = trim((string)$data[$key]);
            if ($val === '') return null;
            if ($maxLen !== null) {
                $val = mb_substr($val, 0, $maxLen, 'UTF-8');
            }
            return $val;
        };

        // Helper for nullable integer IDs (empty string -> null)
        $ni = function ($key) use (&$data) {
            if (!isset($data[$key])) return null;
            $val = trim((string)$data[$key]);
            return ($val !== '' && is_numeric($val)) ? (int)$val : null;
        };

        // Helper for nullable date values
        $nd = function ($key) use ($nn) {
            $val = $nn($key);
            if ($val === null || $val === '0000-00-00') return null;
            $ts = strtotime($val);
            return ($ts !== false && $ts > 0) ? date('Y-m-d', $ts) : null;
        };

        $primaryPhone = $nn('phone_number', 20) ?? $nn('phone', 20);
        $primaryEmail = $nn('email', 150);

        // Auto-assign duplicates to the first owner or team
        $firstAssignee = $this->findFirstAssignee($primaryPhone, $primaryEmail);
        if ($firstAssignee) {
            if (!empty($firstAssignee['team_id'])) {
                $data['team_id'] = (int)$firstAssignee['team_id'];
            }
            if (empty($data['assigned_id']) && !empty($firstAssignee['assigned_id'])) {
                $data['assigned_id'] = (int)$firstAssignee['assigned_id'];
            }
        }

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
        lead_name,
        father_name,
        mother_name,
        dob,
        permanent_address,
        nid_or_passport,
        phone_number,
        phone,
        alt_phone_number,
        email,
        alt_email,
        source_type_id,
        assigned_id,
        address,
        pref_location_id,
        designation,
        project_id,
        project_category_id,
        pref_flat_size,
        communication_type,
        tags,
        description,
        visibility,
        status,
        lead_quality,
        lead_type,
        company_name,
        company,
        team_id,
        created_by,
        country,
        2nd_lead_owner_name,
        2nd_owner_phone,
        2nd_owner_email,
        2nd_owner_address,
        2nd_owner_other_details ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            setError("Prepare failed: " . $this->db->error);
            return false;
        }

        // Assign variables (CSV columns first)
        $campaign_name = $nn('campaign_name', 255);
        $platform = $nn('platform', 100);
        $which_apartment_size_are_you_interested_in = $nn('which_apartment_size_are_you_interested_in', 100);
        $which_floor_are_you_for_shop = $nn('which_floor_are_you_for_shop', 50);
        $when_are_you_planning_to_buy = $nn('when_are_you_planning_to_buy', 200);
        $would_you_like_to_schedule_a_site_visit = $nn('would_you_like_to_schedule_a_site_visit', 200);
        $when_is_the_best_time_to_contact_you = $nn('when_is_the_best_time_to_contact_you', 50);
        $job_title = $nn('job_title', 255);
        $city = $nn('city', 100);

        // Existing fields
        $full_name = $nn('full_name', 255) ?? $nn('lead_name', 255);
        $lead_name = $full_name;
        $father_name = $nn('father_name', 100);
        $mother_name = $nn('mother_name', 100);
        $dob = $nd('dob');
        $permanent_address = $nn('permanent_address', 250);
        $nid_or_passport = $nn('nid_or_passport', 100);
        $phone_number = $primaryPhone;
        $phone = $primaryPhone;
        $alt_phone_number = $nn('alt_phone_number', 30);
        $email = $primaryEmail;
        $alt_email = $nn('alt_email', 255);
        $source_type_id = $ni('source_type_id');
        $assigned_id = $ni('assigned_id');
        $address = $nn('address', 255);
        $pref_location_id = $ni('pref_location_id');
        $designation = $nn('designation', 255);
        $project_id = $ni('project_id');
        $project_category_id = $ni('project_category_id');
        $pref_flat_size = $nn('pref_flat_size', 250) ?? $which_apartment_size_are_you_interested_in;

        // Communication Type (Enum-safe)
        $communication_type = $nn('communication_type');
        $validComm = ['WA Messaging', 'WA Conversion', 'Wa Call', 'Imo Massage', 'Imo Call', 'Phone Call', 'Phone Massage', 'Invalid Number', 'No Wa /No Imo', 'Number Off'];
        if (!empty($communication_type) && !in_array($communication_type, $validComm)) {
            $cleanComm = strtolower($communication_type);
            if (strpos($cleanComm, 'wa') !== false || strpos($cleanComm, 'what') !== false) {
                $communication_type = 'WA Messaging';
            } elseif (strpos($cleanComm, 'call') !== false || strpos($cleanComm, 'phone') !== false) {
                $communication_type = 'Phone Call';
            } elseif (strpos($cleanComm, 'imo') !== false) {
                $communication_type = 'Imo Massage';
            } else {
                $communication_type = null;
            }
        }

        $tags = $nn('tags');
        $description = $nn('description');

        $visibility = $nn('visibility') ?? 'public';
        if (!in_array($visibility, ['public', 'private', 'selected'])) {
            $visibility = 'public';
        }

        $status = $nn('status') ?? 'active';
        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }

        $lead_quality = $nn('lead_quality') ?? 'Pending';
        if (!in_array($lead_quality, ['Pending', 'Potential', 'Prospect', 'Not Interested'])) {
            $lead_quality = 'Pending';
        }

        $lead_type = $nn('lead_type');
        $validLeadTypes = ['No Response', 'Pending', 'Qualification in Progress', 'Potential', 'High Potential', 'Visit Scheduled', 'Visit Completed', 'Sold', 'Lead Transferred', 'Project Transferred', 'Not Interested', "Don't Call Again", 'Refund', 'Future', 'Invalid Lead', 'Prospect', 'High Prospect', 'Will Visit', 'Visited', 'Cold', 'Hot', 'Warm', 'Irrelevant'];
        if (!empty($lead_type) && !in_array($lead_type, $validLeadTypes)) {
            $lead_type = null;
        }

        $company_name = $nn('company_name', 255) ?? $nn('company', 255);
        $company = $company_name;
        $team_id = $ni('team_id');
        $created_by = $ni('created_by');
        $country = $nn('country', 200);
        $second_lead_owner_name = $nn('2nd_lead_owner_name', 255);
        $second_owner_phone = $nn('2nd_owner_phone', 50);
        $second_owner_email = $nn('2nd_owner_email', 255);
        $second_owner_address = $nn('2nd_owner_address');
        $second_owner_other_details = $nn('2nd_owner_other_details');

        // Bind parameters (46 columns)
        $stmt->bind_param(
            "sssssssssssssssssssssiisisiissssssssssiissssss",
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
            $lead_name,
            $father_name,
            $mother_name,
            $dob,
            $permanent_address,
            $nid_or_passport,
            $phone_number,
            $phone,
            $alt_phone_number,
            $email,
            $alt_email,
            $source_type_id,
            $assigned_id,
            $address,
            $pref_location_id,
            $designation,
            $project_id,
            $project_category_id,
            $pref_flat_size,
            $communication_type,
            $tags,
            $description,
            $visibility,
            $status,
            $lead_quality,
            $lead_type,
            $company_name,
            $company,
            $team_id,
            $created_by,
            $country,
            $second_lead_owner_name,
            $second_owner_phone,
            $second_owner_email,
            $second_owner_address,
            $second_owner_other_details
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
            u_appr.full_name AS approved_by_name,
            u_assigner.full_name AS assigned_by_name,
            l.name AS location_name,
            COUNT(f.id) AS follow_up_count

        FROM leads
        LEFT JOIN lead_sources s 
            ON leads.source_type_id = s.id
        LEFT JOIN lead_follow_ups f 
            ON leads.id = f.lead_id
        LEFT JOIN users u 
            ON leads.assigned_id = u.id
        LEFT JOIN users u_appr 
            ON leads.approved_by = u_appr.id
        LEFT JOIN users u_assigner 
            ON leads.assigned_by = u_assigner.id
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
    public function createFromCsvBulk(&$data)
    {
        // Auto-assign duplicates to the first owner or team
        $firstAssignee = $this->findFirstAssignee($data['phone'] ?? $data['phone_number'] ?? null, $data['email'] ?? null);
        if ($firstAssignee) {
            if (!empty($firstAssignee['team_id'])) {
                $data['team_id'] = (int)$firstAssignee['team_id'];
            }
            if (empty($data['assigned_id']) && !empty($firstAssignee['assigned_id'])) {
                $data['assigned_id'] = (int)$firstAssignee['assigned_id'];
            }
        }

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
        $full_name             = $v('full_name') !== null ? mb_substr($v('full_name'), 0, 255, 'UTF-8') : null;
        $job_title             = $v('job_title') !== null ? mb_substr($v('job_title'), 0, 255, 'UTF-8') : null;
        $company_name          = $v('company_name') !== null ? mb_substr($v('company_name'), 0, 255, 'UTF-8') : null;
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
        $this->ensureLeadsEnumColumns();

        $old = $this->find($id);
        if (!$old) {
            return false;
        }

        // Helper for nullable string values (empty string -> null or existing)
        $nn = function($key, $maxLen = null) use ($data, $old) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                $res = (isset($val) && trim((string)$val) !== '') ? trim((string)$val) : null;
            } else {
                $val = $old[$key] ?? null;
                $res = (isset($val) && trim((string)$val) !== '') ? trim((string)$val) : null;
            }
            if ($res !== null && $maxLen !== null) {
                $res = mb_substr($res, 0, $maxLen, 'UTF-8');
            }
            return $res;
        };

        // Helper for integer IDs (empty string -> null or existing)
        $ni = function($key) use ($data, $old) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                return (isset($val) && trim((string)$val) !== '' && is_numeric($val)) ? (int)$val : null;
            }
            return (isset($old[$key]) && trim((string)$old[$key]) !== '' && is_numeric($old[$key])) ? (int)$old[$key] : null;
        };

        // Helper for float/double values
        $nf = function($key) use ($data, $old) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                return (isset($val) && trim((string)$val) !== '' && is_numeric($val)) ? (float)$val : null;
            }
            return (isset($old[$key]) && trim((string)$old[$key]) !== '' && is_numeric($old[$key])) ? (float)$old[$key] : null;
        };

        // Helper for nullable date values
        $nd = function($key) use ($nn) {
            $val = $nn($key);
            if ($val === null || $val === '0000-00-00') return null;
            $ts = strtotime($val);
            return ($ts !== false && $ts > 0) ? date('Y-m-d', $ts) : null;
        };

        // Date fields
        $dob      = $nd('dob');
        $nomi_dob = $nd('nomi_dob');

        // Personal Info
        $full_name            = $nn('full_name', 255) ?? $nn('lead_name', 255);
        $lead_name            = $full_name;
        $lead_name_bn         = $nn('lead_name_bn', 250);
        $father_name          = $nn('father_name', 100);
        $father_name_bn       = $nn('father_name_bn', 250);
        $mother_name          = $nn('mother_name', 100);
        $mother_name_bn       = $nn('mother_name_bn', 250);
        $nationality          = $nn('nationality', 250) ?? 'Bangladeshi';

        // Religion (Enum-safe)
        $religion             = $nn('religion');
        $religionMap = ['Hindu' => 'Hinduism', 'Buddhist' => 'Buddhism', 'Christian' => 'Christianity'];
        if ($religion !== null && isset($religionMap[$religion])) {
            $religion = $religionMap[$religion];
        }
        if (!empty($religion) && !in_array($religion, ['Islam', 'Hinduism', 'Hindu', 'Buddhism', 'Buddhist', 'Christianity', 'Christian', 'Other'])) {
            $religion = null;
        }

        $residency_type       = $nn('residency_type');
        if (!empty($residency_type)) {
            $residency_type = in_array(strtolower($residency_type), ['nrb', 'expatriate']) ? 'expatriate' : 'local';
        } else {
            $residency_type = 'local';
        }
        $nid_or_passport      = $nn('nid_or_passport', 100);

        // Identity Type (Enum-safe)
        $identity_type        = $nn('identity_type');
        if (!empty($identity_type) && !in_array($identity_type, ['NID', 'Passport', 'Birth Certificate'])) {
            $identity_type = null;
        }

        // Contact & Address
        $phone                = $nn('phone', 30) ?? $nn('phone_number', 30);
        $phone_number         = $phone !== null ? mb_substr($phone, 0, 20, 'UTF-8') : null;
        $alt_phone_number     = $nn('alt_phone_number', 30);
        $email                = $nn('email', 150);
        $alt_email            = $nn('alt_email', 255);
        $address              = $nn('address', 255);
        $present_add_bn       = $nn('present_add_bn', 255);
        $permanent_address    = $nn('permanent_address', 250);
        $permanent_add_bn     = $nn('permanent_add_bn', 255);
        $city                 = $nn('city', 100);
        $country              = $nn('country', 200);

        // Professional Info
        $designation          = $nn('designation', 255);
        $profession_bn        = $nn('profession_bn', 250);
        $job_title            = $nn('job_title', 255);
        $company_name         = $nn('company_name', 255) ?? $nn('company', 255);
        $company              = $company_name;

        // Campaign & Source Meta
        $campaign_name        = $nn('campaign_name', 255);
        $platform             = $nn('platform', 100);
        $uploaded_from        = $nn('uploaded_from');
        if (empty($uploaded_from) || !in_array($uploaded_from, ['From Campaign', 'From Company csv', 'Manually added'])) {
            $uploaded_from = (!empty($old['uploaded_from']) && in_array($old['uploaded_from'], ['From Campaign', 'From Company csv', 'Manually added']))
                ? $old['uploaded_from']
                : 'From Campaign';
        }
        $source_type_id       = $ni('source_type_id');
        $assigned_id          = $ni('assigned_id');
        $team_id              = $ni('team_id');
        $project_id           = $ni('project_id');
        $project_category_id  = $ni('project_category_id');
        $pref_location_id     = $ni('pref_location_id');

        // Lead Type (Enum-safe)
        $lead_type            = $nn('lead_type');
        $validLeadTypes = ['No Response', 'Pending', 'Qualification in Progress', 'Potential', 'High Potential', 'Visit Scheduled', 'Visit Completed', 'Sold', 'Lead Transferred', 'Project Transferred', 'Not Interested', "Don't Call Again", 'Refund', 'Future', 'Invalid Lead', 'Prospect', 'High Prospect', 'Will Visit', 'Visited', 'Cold', 'Hot', 'Warm', 'Irrelevant'];
        if (!empty($lead_type) && !in_array($lead_type, $validLeadTypes)) {
            $lead_type = null;
        }

        $lead_quality         = $nn('lead_quality') ?? 'Pending';
        if (!in_array($lead_quality, ['Pending', 'Potential', 'Prospect', 'Not Interested'])) {
            $lead_quality = (!empty($old['lead_quality']) && in_array($old['lead_quality'], ['Pending', 'Potential', 'Prospect', 'Not Interested']))
                ? $old['lead_quality']
                : 'Pending';
        }

        $last_lead_action     = $nn('last_lead_action');
        if (empty($last_lead_action) || !in_array($last_lead_action, ['new', 'booked', 'follow up', 'visited', 'cancel', 'contacted'])) {
            $last_lead_action = (!empty($old['last_lead_action']) && in_array($old['last_lead_action'], ['new', 'booked', 'follow up', 'visited', 'cancel', 'contacted']))
                ? $old['last_lead_action']
                : 'new';
        }

        $visibility           = $nn('visibility') ?? 'public';
        if (!in_array($visibility, ['public', 'private', 'selected'])) {
            $visibility = 'public';
        }
        $status               = $nn('status') ?? 'active';
        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }
        $call_status          = $nn('call_status') ?? 'not_called';
        if (!in_array($call_status, ['not_called', 'called', 'received', 'not_received', 'busy', 'no_answer'])) {
            $call_status = 'not_called';
        }
        $call_note            = $nn('call_note');
        $value_amount         = $nf('value_amount');
        $currency             = $nn('currency') ?? 'BDT';
        if (!in_array($currency, ['USD', 'EURO', 'RUBLE', 'POUND', 'BDT'])) {
            $currency = 'BDT';
        }

        // Customer Requirements & Preferences
        $which_apt_size       = $nn('which_apartment_size_are_you_interested_in', 100);
        $which_floor_shop     = $nn('which_floor_are_you_for_shop', 50);
        $when_buy             = $nn('when_are_you_planning_to_buy', 200);
        $schedule_visit       = $nn('would_you_like_to_schedule_a_site_visit', 200);
        
        $best_time_contact    = $nn('when_is_the_best_time_to_contact_you', 50) ?? $nn('preferred_contact_time', 50);

        // preferred_contact_time is an ENUM ('morning', 'afternoon', 'evening', 'night')
        $preferred_contact = null;
        if (!empty($best_time_contact)) {
            $cleanContact = strtolower(trim($best_time_contact));
            if (in_array($cleanContact, ['morning', 'afternoon', 'evening', 'night'])) {
                $preferred_contact = $cleanContact;
            } elseif (strpos($cleanContact, 'morn') !== false) {
                $preferred_contact = 'morning';
            } elseif (strpos($cleanContact, 'after') !== false || strpos($cleanContact, 'noon') !== false) {
                $preferred_contact = 'afternoon';
            } elseif (strpos($cleanContact, 'even') !== false || strpos($cleanContact, '6 pm') !== false || strpos($cleanContact, '7 pm') !== false || strpos($cleanContact, '8 pm') !== false) {
                $preferred_contact = 'evening';
            } elseif (strpos($cleanContact, 'night') !== false) {
                $preferred_contact = 'night';
            }
        }

        $pref_flat_size       = $nn('pref_flat_size', 250) ?? $which_apt_size;

        // Communication Type (Enum-safe)
        $communication_type   = $nn('communication_type');
        $validComm = ['WA Messaging', 'WA Conversion', 'Wa Call', 'Imo Massage', 'Imo Call', 'Phone Call', 'Phone Massage', 'Invalid Number', 'No Wa /No Imo', 'Number Off'];
        if (!empty($communication_type) && !in_array($communication_type, $validComm)) {
            $cleanComm = strtolower(trim($communication_type));
            if (strpos($cleanComm, 'wa') !== false || strpos($cleanComm, 'what') !== false) {
                $communication_type = 'WA Messaging';
            } elseif (strpos($cleanComm, 'call') !== false || strpos($cleanComm, 'phone') !== false) {
                $communication_type = 'Phone Call';
            } elseif (strpos($cleanComm, 'imo') !== false) {
                $communication_type = 'Imo Massage';
            } else {
                $communication_type = null;
            }
        }

        $tags                 = $nn('tags');
        $description          = $nn('description');

        // Nominee Info
        $nominee_name         = $nn('nominee_name', 250);
        $relation_with_lead   = $nn('relation_with_lead', 200);
        $nomi_father_name     = $nn('nomi_father_name', 250);
        $nomi_mother_name     = $nn('nomi_mother_name', 250);
        $nomi_profession      = $nn('nomi_profession', 250);
        $nomi_phone           = $nn('nomi_phone', 100);
        $nomi_email           = $nn('nomi_email', 200);
        $nomi_nid_passport    = $nn('nomi_nid_passport', 200);
        $nomi_present_add     = $nn('nomi_present_add', 200);
        $nomi_permanent_address = $nn('nomi_permanent_address', 255);

        // 2nd Nominee Info
        $nominee_name_2         = $nn('2nd_nominee_name', 255);
        $relation_with_lead_2   = $nn('2nd_relation_with_lead', 255);
        $nomi_father_name_2     = $nn('2nd_nomi_father_name', 255);
        $nomi_mother_name_2     = $nn('2nd_nomi_mother_name', 255);
        $nomi_profession_2      = $nn('2nd_nomi_profession', 255);
        $nomi_dob_2             = $nd('2nd_nomi_dob');
        $nomi_phone_2           = $nn('2nd_nomi_phone', 50);
        $nomi_email_2           = $nn('2nd_nomi_email', 255);
        $nomi_nid_passport_2    = $nn('2nd_nomi_nid_passport', 100);
        $nomi_present_add_2     = $nn('2nd_nomi_present_add');
        $nomi_permanent_address_2 = $nn('2nd_nomi_permanent_address');

        // Image Upload (fallback old)
        $lead_image = !empty($_FILES['lead_image']['name'])
            ? uploadImage('lead_image', 'leads', 'fixed')
            : $old['lead_image'];

        $nomi_image = !empty($_FILES['nomi_image']['name'])
            ? uploadImage('nomi_image', 'leads', 'fixed')
            : $old['nomi_image'];

        $nomi_image_2 = !empty($_FILES['2nd_nomi_image']['name'])
            ? uploadImage('2nd_nomi_image', 'leads', 'fixed')
            : $old['2nd_nomi_image'];

        // Build dynamic UPDATE query for exact robustness
        $fields = [
            'full_name' => $full_name,
            'lead_name' => $lead_name,
            'lead_name_bn' => $lead_name_bn,
            'father_name' => $father_name,
            'father_name_bn' => $father_name_bn,
            'mother_name' => $mother_name,
            'mother_name_bn' => $mother_name_bn,
            'dob' => $dob,
            'permanent_address' => $permanent_address,
            'permanent_add_bn' => $permanent_add_bn,
            'present_add_bn' => $present_add_bn,
            'address' => $address,
            'nid_or_passport' => $nid_or_passport,
            'identity_type' => $identity_type,
            'phone' => $phone,
            'phone_number' => $phone_number,
            'alt_phone_number' => $alt_phone_number,
            'email' => $email,
            'alt_email' => $alt_email,
            'designation' => $designation,
            'profession_bn' => $profession_bn,
            'job_title' => $job_title,
            'company_name' => $company_name,
            'company' => $company,
            'nationality' => $nationality,
            'religion' => $religion,
            'residency_type' => $residency_type,
            'city' => $city,
            'country' => $country,
            'campaign_name' => $campaign_name,
            'platform' => $platform,
            'uploaded_from' => $uploaded_from,
            'source_type_id' => $source_type_id,
            'assigned_id' => $assigned_id,
            'team_id' => $team_id,
            'project_id' => $project_id,
            'project_category_id' => $project_category_id,
            'pref_location_id' => $pref_location_id,
            'lead_type' => $lead_type,
            'lead_quality' => $lead_quality,
            'last_lead_action' => $last_lead_action,
            'visibility' => $visibility,
            'status' => $status,
            'call_status' => $call_status,
            'call_note' => $call_note,
            'value_amount' => $value_amount,
            'currency' => $currency,
            'which_apartment_size_are_you_interested_in' => $which_apt_size,
            'which_floor_are_you_for_shop' => $which_floor_shop,
            'when_are_you_planning_to_buy' => $when_buy,
            'would_you_like_to_schedule_a_site_visit' => $schedule_visit,
            'when_is_the_best_time_to_contact_you' => $best_time_contact,
            'preferred_contact_time' => $preferred_contact,
            'pref_flat_size' => $pref_flat_size,
            'communication_type' => $communication_type,
            'tags' => $tags,
            'description' => $description,
            'lead_image' => $lead_image,
            'nominee_name' => $nominee_name,
            'relation_with_lead' => $relation_with_lead,
            'nomi_father_name' => $nomi_father_name,
            'nomi_mother_name' => $nomi_mother_name,
            'nomi_profession' => $nomi_profession,
            'nomi_dob' => $nomi_dob,
            'nomi_phone' => $nomi_phone,
            'nomi_email' => $nomi_email,
            'nomi_nid_passport' => $nomi_nid_passport,
            'nomi_present_add' => $nomi_present_add,
            'nomi_permanent_address' => $nomi_permanent_address,
            'nomi_image' => $nomi_image,
            '2nd_nominee_name' => $nominee_name_2,
            '2nd_relation_with_lead' => $relation_with_lead_2,
            '2nd_nomi_father_name' => $nomi_father_name_2,
            '2nd_nomi_mother_name' => $nomi_mother_name_2,
            '2nd_nomi_profession' => $nomi_profession_2,
            '2nd_nomi_dob' => $nomi_dob_2,
            '2nd_nomi_phone' => $nomi_phone_2,
            '2nd_nomi_email' => $nomi_email_2,
            '2nd_nomi_nid_passport' => $nomi_nid_passport_2,
            '2nd_nomi_present_add' => $nomi_present_add_2,
            '2nd_nomi_permanent_address' => $nomi_permanent_address_2,
            '2nd_nomi_image' => $nomi_image_2,
            '2nd_lead_owner_name' => $nn('2nd_lead_owner_name', 255),
            '2nd_owner_phone' => $nn('2nd_owner_phone', 50),
            '2nd_owner_email' => $nn('2nd_owner_email', 255),
            '2nd_owner_address' => $nn('2nd_owner_address'),
            '2nd_owner_other_details' => $nn('2nd_owner_other_details'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $setParts = [];
        $params   = [];
        $types    = "";

        foreach ($fields as $col => $val) {
            $setParts[] = "`{$col}` = ?";
            if (is_int($val)) {
                $types .= "i";
            } elseif (is_float($val)) {
                $types .= "d";
            } else {
                $types .= "s";
            }
            $params[] = $val;
        }

        $params[] = (int)$id;
        $types   .= "i";

        $sql = "UPDATE leads SET " . implode(", ", $setParts) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Update failed: " . $stmt->error);
        }

        return true;
    }
    public function countLeadsByMonth($year, $month)
    {
        $startDate = date('Y-m-26', strtotime("$year-$month-01 -1 month"));
        $endDate = date('Y-m-25 23:59:59', strtotime("$year-$month-01"));
        
        $stmt = $this->db->prepare("
        SELECT COUNT(*) AS total
        FROM leads
        WHERE created_at >= ? AND created_at <= ?
    ");
        $stmt->bind_param("ss", $startDate, $endDate);
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

        // Total Leads (Active, not deleted, From Campaign only)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE status = 'active' AND deleted_at IS NULL AND uploaded_from = 'From Campaign'");
        $stmt2 = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE created_at >= CURDATE() AND deleted_at IS NULL AND uploaded_from = 'From Campaign'");
        $stmt->execute();
        $stats['total_leads'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
        $stmt2->execute();
        $stats['today_uploads'] = $stmt2->get_result()->fetch_assoc()['total'] ?? 0;

        // Uploaded This Week (Leads created in the last 7 days, From Campaign)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND deleted_at IS NULL AND uploaded_from = 'From Campaign'");
        $stmt->execute();
        $stats['leads_this_week'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Failed / Errors (Inactive or Cancelled leads, From Campaign)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE (status = 'inactive' OR last_lead_action = 'cancel') AND deleted_at IS NULL AND uploaded_from = 'From Campaign'");
        $stmt->execute();
        $stats['failed_leads'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Total Uploads (From Campaign)
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM leads WHERE deleted_at IS NULL AND uploaded_from = 'From Campaign'");
        $stmt->execute();
        $stats['total_uploads'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        return $stats;
    }
    public function getLeadsByCreatedByLast7Days(int $userId)
    {
        return $this->getMarketingLeadsFlexible($userId, null, '7days', 'DESC');
    }

    /**
     * Standard platform groupings and their aliases (case-insensitive)
     */
    public static function getStandardPlatformGroups(): array
    {
        return [
            'Facebook Form Lead' => [
                'facebook', 'fb', 'fb(lead form)', 'lead form', 'leadform', 'leadfrom', 'meta form', 'meta inform'
            ],
            'Meta Inbox' => [
                'meta inbox', 'meta'
            ],
            'Instagram(IG)' => [
                'instagram', 'ig', 'instagram(ig)'
            ],
            'Google Form' => [
                'google form', 'google', 'googleform'
            ],
            'WhatsApp' => [
                'whatsapp', 'wa', 'whats app', 'whatsappp', 'whataapp', 'whatsapp lead'
            ],
            'Brochure Leads' => [
                'brochure leads', 'brocure leads', 'brochure lead', 'brocure lead', 'brochure'
            ],
        ];
    }

    /**
     * Get all raw database values / aliases for a given platform name or category
     */
    public static function getPlatformAliases(string $platform): array
    {
        $platform = trim($platform);
        if ($platform === '') return [];

        $groups = self::getStandardPlatformGroups();
        $targetLower = strtolower($platform);

        // If the platform matches a standard group name directly
        foreach ($groups as $groupName => $aliases) {
            if (strtolower($groupName) === $targetLower) {
                $all = array_unique(array_merge([$groupName], $aliases));
                return array_values($all);
            }
        }

        // If the platform matches any alias within a group
        foreach ($groups as $groupName => $aliases) {
            foreach ($aliases as $alias) {
                if (strtolower($alias) === $targetLower) {
                    $all = array_unique(array_merge([$groupName], $aliases));
                    return array_values($all);
                }
            }
        }

        // If not found in any group, return just the platform itself
        return [$platform];
    }

    /**
     * Resolve raw platform string to canonical platform name
     */
    public static function resolveCanonicalPlatform(?string $rawPlatform): string
    {
        if (empty($rawPlatform)) return 'N/A';
        $targetLower = strtolower(trim($rawPlatform));

        $groups = self::getStandardPlatformGroups();
        foreach ($groups as $groupName => $aliases) {
            if (strtolower($groupName) === $targetLower) {
                return $groupName;
            }
            foreach ($aliases as $alias) {
                if (strtolower($alias) === $targetLower) {
                    return $groupName;
                }
            }
        }

        return $rawPlatform;
    }

    public function getDistinctMarketingPlatforms()
    {
        return array_keys(self::getStandardPlatformGroups());
    }

    /**
     * Count marketing leads for AJAX pagination (mirrors getMarketingLeadsFlexible filters)
     */
    public function countMarketingLeadsFlexible($teamId = null, $dateFilter = 'all', $search = null, $fromDate = '', $toDate = '', $duplicatesOnly = false, $projectCategoryId = null, $projectId = null, $platform = null, $sourceTypeId = null)
    {
        $sql = "SELECT COUNT(*) AS total FROM leads l WHERE l.deleted_at IS NULL AND l.uploaded_from = 'From Campaign'";

        // DB-level duplicate filter
        if ($duplicatesOnly) {
            $sql .= "
              AND (
                (l.phone_number IS NOT NULL AND l.phone_number != '' AND l.phone_number IN (
                    SELECT phone_number FROM leads
                    WHERE deleted_at IS NULL AND phone_number IS NOT NULL AND phone_number != ''
                      AND uploaded_from = 'From Campaign'
                    GROUP BY phone_number HAVING COUNT(*) > 1
                ))
                OR
                (l.email IS NOT NULL AND l.email != '' AND l.email IN (
                    SELECT email FROM leads
                    WHERE deleted_at IS NULL AND email IS NOT NULL AND email != ''
                      AND uploaded_from = 'From Campaign'
                    GROUP BY email HAVING COUNT(*) > 1
                ))
              )";
        }

        $params = [];
        $types  = "";

        if ($sourceTypeId) {
            $sql .= " AND l.source_type_id = ?";
            $params[] = (int)$sourceTypeId;
            $types   .= "i";
        }

        if ($teamId) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types   .= "i";
        }

        if ($projectCategoryId) {
            $sql .= " AND l.project_category_id = ?";
            $params[] = $projectCategoryId;
            $types   .= "i";
        }

        if ($projectId) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'l.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types   .= $projClause['types'];
        }

        if (!empty($platform)) {
            $aliases = self::getPlatformAliases($platform);
            if (!empty($aliases)) {
                $placeholders = implode(',', array_fill(0, count($aliases), '?'));
                $sql .= " AND LOWER(TRIM(l.platform)) IN ($placeholders)";
                foreach ($aliases as $alias) {
                    $params[] = strtolower(trim($alias));
                    $types   .= "s";
                }
            } else {
                $sql .= " AND l.platform = ?";
                $params[] = $platform;
                $types   .= "s";
            }
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $sql .= " AND DATE(l.created_at) BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
            $types   .= "ss";
        } elseif (!empty($fromDate)) {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $fromDate;
            $types   .= "s";
        } elseif (!empty($toDate)) {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $toDate;
            $types   .= "s";
        } elseif ($dateFilter === 'today') {
            $sql .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($dateFilter === 'old') {
            $sql .= " AND DATE(l.created_at) < CURDATE()";
        } elseif ($dateFilter === '7days') {
            $sql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
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
    public function getMarketingLeadsPaginated($teamId = null, $dateFilter = 'all', $sortOrder = 'DESC', $limit = 10, $offset = 0, $search = null, $fromDate = '', $toDate = '', $duplicatesOnly = false, $projectCategoryId = null, $projectId = null, $platform = null, $sourceTypeId = null)
    {
        $sql = "
        SELECT
            l.id, l.full_name, l.phone_number, l.email, l.job_title, l.designation,
            l.company, l.company_name, l.when_is_the_best_time_to_contact_you,
            l.platform, l.campaign_name, l.lead_type, l.call_note,
            l.created_at, l.assigned_id, l.country, l.address, l.city,
            l.source_type_id,
            ls.name  AS source_name,
            p.name   AS project_name,
            lpc.name AS project_category_name,
            t.name   AS team_name,
            au.full_name AS assigned_user_name,
            cu.full_name AS uploaded_by_name,
            (SELECT u2.full_name
             FROM users u2
             INNER JOIN employees e2 ON u2.id = e2.user_id
             WHERE e2.team_id = l.team_id AND u2.role_id IN (5, 9)
             ORDER BY u2.role_id ASC
             LIMIT 1) AS sales_team_lead_name,
            -- Follow-up count
            COALESCE((
                SELECT COUNT(*) FROM lead_follow_ups lfc
                WHERE lfc.lead_id = l.id AND lfc.deleted_at IS NULL
            ), 0) AS follow_up_count,
            -- Contact status (4 conditions)
            CASE
                WHEN (
                    EXISTS (SELECT 1 FROM lead_follow_ups lfe WHERE lfe.lead_id = l.id AND lfe.deleted_at IS NULL)
                    OR (l.lead_type IS NOT NULL AND l.lead_type != '')
                    OR (l.call_note IS NOT NULL AND TRIM(l.call_note) != '')
                    OR (l.call_status IN ('called', 'received'))
                ) THEN 'Contacted'
                ELSE 'Not Contacted'
            END AS contact_status,
            -- Last follow-up type
            lfu.follow_up_type AS last_follow_up_type
        FROM leads l
        LEFT JOIN lead_sources ls ON l.source_type_id = ls.id
        LEFT JOIN projects     p  ON l.project_id = p.id
        LEFT JOIN project_categories lpc ON l.project_category_id = lpc.id
        LEFT JOIN teams        t  ON l.team_id    = t.id
        LEFT JOIN users        au ON l.assigned_id = au.id
        LEFT JOIN users        cu ON l.created_by = cu.id
        LEFT JOIN (
            SELECT lf1.lead_id, lf1.follow_up_type
            FROM lead_follow_ups lf1
            WHERE lf1.id = (
                SELECT MAX(lf2.id) FROM lead_follow_ups lf2
                WHERE lf2.lead_id = lf1.lead_id AND lf2.deleted_at IS NULL
            ) AND lf1.deleted_at IS NULL
        ) lfu ON lfu.lead_id = l.id
        WHERE l.deleted_at IS NULL
          AND l.uploaded_from = 'From Campaign'
        ";

        $params = [];
        $types  = "";

        // DB-level duplicate filter
        if ($duplicatesOnly) {
            $sql .= "
              AND (
                (l.phone_number IS NOT NULL AND l.phone_number != '' AND l.phone_number IN (
                    SELECT phone_number FROM leads
                    WHERE deleted_at IS NULL AND phone_number IS NOT NULL AND phone_number != ''
                      AND uploaded_from = 'From Campaign'
                    GROUP BY phone_number HAVING COUNT(*) > 1
                ))
                OR
                (l.email IS NOT NULL AND l.email != '' AND l.email IN (
                    SELECT email FROM leads
                    WHERE deleted_at IS NULL AND email IS NOT NULL AND email != ''
                      AND uploaded_from = 'From Campaign'
                    GROUP BY email HAVING COUNT(*) > 1
                ))
              )";
        }

        if ($sourceTypeId) {
            $sql .= " AND l.source_type_id = ?";
            $params[] = (int)$sourceTypeId;
            $types   .= "i";
        }

        if ($teamId) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types   .= "i";
        }

        if ($projectCategoryId) {
            $sql .= " AND l.project_category_id = ?";
            $params[] = $projectCategoryId;
            $types   .= "i";
        }

        if ($projectId) {
            $projClause = $this->buildProjectFilterClause((int)$projectId, 'l.');
            $sql .= $projClause['sql'];
            foreach ($projClause['params'] as $pVal) {
                $params[] = $pVal;
            }
            $types   .= $projClause['types'];
        }

        if (!empty($platform)) {
            $aliases = self::getPlatformAliases($platform);
            if (!empty($aliases)) {
                $placeholders = implode(',', array_fill(0, count($aliases), '?'));
                $sql .= " AND LOWER(TRIM(l.platform)) IN ($placeholders)";
                foreach ($aliases as $alias) {
                    $params[] = strtolower(trim($alias));
                    $types   .= "s";
                }
            } else {
                $sql .= " AND l.platform = ?";
                $params[] = $platform;
                $types   .= "s";
            }
        }

        if (!empty($fromDate) && !empty($toDate)) {
            $sql .= " AND DATE(l.created_at) BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
            $types   .= "ss";
        } elseif (!empty($fromDate)) {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $fromDate;
            $types   .= "s";
        } elseif (!empty($toDate)) {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $toDate;
            $types   .= "s";
        } elseif ($dateFilter === 'today') {
            $sql .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($dateFilter === 'old') {
            $sql .= " AND DATE(l.created_at) < CURDATE()";
        } elseif ($dateFilter === '7days') {
            $sql .= " AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
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
            lpc.name AS project_category_name,
            t.name AS team_name,
             (SELECT u.full_name 
              FROM users u 
              INNER JOIN employees e ON u.id = e.user_id 
              WHERE e.team_id = l.team_id AND u.role_id IN (5, 9) 
              ORDER BY u.role_id ASC
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
        LEFT JOIN project_categories lpc ON l.project_category_id = lpc.id
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
              AND uploaded_from = 'From Campaign'
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

    public function findFirstAssignee($phoneNumber, $email)
    {
        $phoneNumber = trim($phoneNumber ?? '');
        $email = trim($email ?? '');
        
        if (empty($phoneNumber) && empty($email)) {
            return null;
        }
        
        $sql = "
            SELECT l.assigned_id, l.team_id, l.created_at
            FROM leads l
            WHERE l.deleted_at IS NULL 
              AND (l.assigned_id IS NOT NULL OR l.team_id IS NOT NULL)
        ";
        
        $params = [];
        $types = "";
        
        if (!empty($phoneNumber) && !empty($email)) {
            $digits = preg_replace('/\D/', '', $phoneNumber);
            if (strlen($digits) >= 10) {
                $suffix = substr($digits, -10);
                $sql .= " AND (RIGHT(REGEXP_REPLACE(l.phone_number, '[^0-9]', ''), 10) = ? OR RIGHT(REGEXP_REPLACE(l.phone, '[^0-9]', ''), 10) = ? OR l.email = ?)";
                $params[] = $suffix;
                $params[] = $suffix;
                $params[] = $email;
                $types .= "sss";
            } else {
                $sql .= " AND (l.phone_number = ? OR l.phone = ? OR l.email = ?)";
                $params[] = $phoneNumber;
                $params[] = $phoneNumber;
                $params[] = $email;
                $types .= "sss";
            }
        } elseif (!empty($phoneNumber)) {
            $digits = preg_replace('/\D/', '', $phoneNumber);
            if (strlen($digits) >= 10) {
                $suffix = substr($digits, -10);
                $sql .= " AND (RIGHT(REGEXP_REPLACE(l.phone_number, '[^0-9]', ''), 10) = ? OR RIGHT(REGEXP_REPLACE(l.phone, '[^0-9]', ''), 10) = ?)";
                $params[] = $suffix;
                $params[] = $suffix;
                $types .= "ss";
            } else {
                $sql .= " AND (l.phone_number = ? OR l.phone = ?)";
                $params[] = $phoneNumber;
                $params[] = $phoneNumber;
                $types .= "ss";
            }
        } else {
            $sql .= " AND l.email = ?";
            $params[] = $email;
            $types .= "s";
        }
        
        $sql .= " ORDER BY l.created_at ASC, l.id ASC LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            return null;
        }
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    public function getDistinctCountries()
    {
        $countries = [];

        // 1. Fetch from country column
        $res1 = $this->db->query("SELECT DISTINCT country FROM leads WHERE country IS NOT NULL AND TRIM(country) != ''");
        if ($res1) {
            while ($row = $res1->fetch_assoc()) {
                $countries[] = trim($row['country']);
            }
        }

        // 2. Fetch from city column
        $knownCountries = [
            'Bangladesh', 'Saudi Arabia', 'UAE', 'United Arab Emirates', 'Qatar', 
            'Oman', 'Kuwait', 'Bahrain', 'Singapore', 'Malaysia', 'USA', 'United States', 
            'UK', 'United Kingdom', 'Canada', 'Australia', 'Italy', 'Portugal', 'France', 'Spain'
        ];

        $res2 = $this->db->query("SELECT DISTINCT city FROM leads WHERE city IS NOT NULL AND TRIM(city) != ''");
        if ($res2) {
            while ($row = $res2->fetch_assoc()) {
                $cityVal = trim($row['city']);
                foreach ($knownCountries as $kc) {
                    if (stripos($cityVal, $kc) !== false) {
                        $stdCountry = $kc;
                        if (in_array(strtolower($kc), ['uae', 'united arab emirates'])) {
                            $stdCountry = 'UAE';
                        } elseif (in_array(strtolower($kc), ['usa', 'united states'])) {
                            $stdCountry = 'USA';
                        } elseif (in_array(strtolower($kc), ['uk', 'united kingdom'])) {
                            $stdCountry = 'UK';
                        }
                        $countries[] = $stdCountry;
                    }
                }
            }
        }

        $countries = array_filter(array_unique($countries));
        sort($countries);
        return $countries;
    }

    public function getDuplicateLeadsForUser(int $userId)
    {
        $userId = (int) $userId;
        $sql = "
            SELECT 
                l.id,
                l.full_name,
                l.phone_number,
                l.phone,
                l.email,
                l.created_at,
                l.assigned_at,
                l.last_lead_action,
                l.lead_type,
                p.name AS project_name
            FROM leads l
            LEFT JOIN projects p ON l.project_id = p.id
            WHERE l.assigned_id = ?
              AND l.deleted_at IS NULL
            ORDER BY l.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $allLeads = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        // 1. Build counts of phone numbers and emails
        $phonesCount = [];
        $emailsCount = [];

        foreach ($allLeads as $lead) {
            $phone1 = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? '');
            $phone2 = preg_replace('/[^0-9]/', '', $lead['phone'] ?? '');
            $email = trim(strtolower($lead['email'] ?? ''));

            if ($phone1 !== '') {
                $phonesCount[$phone1] = ($phonesCount[$phone1] ?? 0) + 1;
            }
            if ($phone2 !== '' && $phone2 !== $phone1) {
                $phonesCount[$phone2] = ($phonesCount[$phone2] ?? 0) + 1;
            }
            if ($email !== '') {
                $emailsCount[$email] = ($emailsCount[$email] ?? 0) + 1;
            }
        }

        // 2. Filter list in memory to keep only those that have duplicate values
        $duplicates = [];
        foreach ($allLeads as $lead) {
            $phone1 = preg_replace('/[^0-9]/', '', $lead['phone_number'] ?? '');
            $phone2 = preg_replace('/[^0-9]/', '', $lead['phone'] ?? '');
            $email = trim(strtolower($lead['email'] ?? ''));

            $isDup = false;
            if ($phone1 !== '' && ($phonesCount[$phone1] ?? 0) > 1) {
                $isDup = true;
            }
            if ($phone2 !== '' && ($phonesCount[$phone2] ?? 0) > 1) {
                $isDup = true;
            }
            if ($email !== '' && ($emailsCount[$email] ?? 0) > 1) {
                $isDup = true;
            }

            if ($isDup) {
                $duplicates[] = $lead;
            }
        }

        return $duplicates;
    }

    public function getUploadReportStats($filters = [])
    {
        $created_by = !empty($filters['created_by']) ? (int)$filters['created_by'] : null;
        $project_category_id = !empty($filters['project_category_id']) ? (int)$filters['project_category_id'] : null;
        $project_id = !empty($filters['project_id']) ? (int)$filters['project_id'] : null;
        $platform = !empty($filters['platform']) ? $filters['platform'] : null;
        $date_filter = $filters['date_filter'] ?? 'today';
        $month = !empty($filters['month']) ? $filters['month'] : date('Y-m');
        $year = !empty($filters['year']) ? $filters['year'] : date('Y');

        $where = "l.deleted_at IS NULL AND l.uploaded_from = 'From Campaign'";
        $params = [];
        $types = "";

        if ($created_by) {
            $where .= " AND l.created_by = ?";
            $params[] = $created_by;
            $types .= "i";
        }
        if ($project_category_id) {
            $where .= " AND l.project_category_id = ?";
            $params[] = $project_category_id;
            $types .= "i";
        }
        if ($project_id) {
            $where .= " AND l.project_id = ?";
            $params[] = $project_id;
            $types .= "i";
        }
        if ($platform) {
            $where .= " AND l.platform = ?";
            $params[] = $platform;
            $types .= "s";
        }

        if ($date_filter === 'today') {
            $where .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($date_filter === 'this_month') {
            $where .= " AND DATE_FORMAT(l.created_at, '%Y-%m') = ?";
            $params[] = date('Y-m');
            $types .= "s";
        } elseif ($date_filter === 'this_year') {
            $where .= " AND YEAR(l.created_at) = ?";
            $params[] = date('Y');
            $types .= "s";
        } elseif ($date_filter === 'date_range') {
            $where .= " AND DATE(l.created_at) BETWEEN ? AND ?";
            $params[] = !empty($filters['from_date']) ? $filters['from_date'] : date('Y-m-d');
            $params[] = !empty($filters['to_date']) ? $filters['to_date'] : date('Y-m-d');
            $types .= "ss";
        } elseif ($date_filter === 'custom_month') {
            $where .= " AND DATE_FORMAT(l.created_at, '%Y-%m') = ?";
            $month_val = !empty($filters['specific_month']) ? str_pad($filters['specific_month'], 2, '0', STR_PAD_LEFT) : date('m');
            $year_val = !empty($filters['specific_month_year']) ? $filters['specific_month_year'] : date('Y');
            $params[] = $year_val . '-' . $month_val;
            $types .= "s";
        } elseif ($date_filter === 'custom_year') {
            $where .= " AND YEAR(l.created_at) = ?";
            $params[] = !empty($filters['year']) ? $filters['year'] : date('Y');
            $types .= "s";
        } elseif ($date_filter === 'all') {
            // no extra date filter
        }

        $baseSql = "FROM leads l
                    LEFT JOIN projects p ON l.project_id = p.id
                    LEFT JOIN project_categories pc ON l.project_category_id = pc.id
                    WHERE $where";

        $results = [];

        // 1. Total count
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total $baseSql");
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results['total'] = $stmt->get_result()->fetch_assoc()['total'];

        // 2. By Project Category
        $stmt = $this->db->prepare("SELECT pc.name, COUNT(*) AS total $baseSql GROUP BY l.project_category_id ORDER BY total DESC");
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results['by_category'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 3. By Project
        $stmt = $this->db->prepare("SELECT p.name, COUNT(*) AS total $baseSql GROUP BY l.project_id ORDER BY total DESC");
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results['by_project'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 4. By Platform
        $stmt = $this->db->prepare("SELECT l.platform AS name, COUNT(*) AS total $baseSql GROUP BY l.platform ORDER BY total DESC");
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results['by_platform'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 5. By Campaign
        $stmt = $this->db->prepare("SELECT l.campaign_name AS name, COUNT(*) AS total $baseSql GROUP BY l.campaign_name ORDER BY total DESC");
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results['by_campaign'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 6. Time Breakdown
        if ($date_filter === 'custom_month' || $date_filter === 'this_month') {
            $stmt = $this->db->prepare("SELECT DATE(l.created_at) AS date_label, COUNT(*) AS total $baseSql GROUP BY DATE(l.created_at) ORDER BY date_label ASC");
            if (!empty($params)) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $results['time_breakdown'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } elseif ($date_filter === 'custom_year' || $date_filter === 'this_year') {
            $stmt = $this->db->prepare("SELECT DATE_FORMAT(l.created_at, '%Y-%m') AS date_label, COUNT(*) AS total $baseSql GROUP BY DATE_FORMAT(l.created_at, '%Y-%m') ORDER BY date_label ASC");
            if (!empty($params)) $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $results['time_breakdown'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
             $results['time_breakdown'] = [];
        }

        // 7. Actual Lead List
        $stmt = $this->db->prepare("SELECT l.id, l.full_name, l.phone_number, l.email, l.platform, l.campaign_name, l.created_at, p.name AS project_name, pc.name AS category_name $baseSql ORDER BY l.id DESC");
        if (!empty($params)) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $results['leads'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $results;
    }

    /**
     * Date-wise breakdown report for All Platform Leads
     */
    public function getPlatformLeadsDateWiseReport($filters = [])
    {
        $fromDate = !empty($filters['from_date']) ? $filters['from_date'] : date('Y-m-d');
        $toDate   = !empty($filters['to_date'])   ? $filters['to_date']   : date('Y-m-d');
        $projectId = !empty($filters['project_id']) ? (int)$filters['project_id'] : null;
        $projectCategoryId = !empty($filters['project_category_id']) ? (int)$filters['project_category_id'] : null;
        $teamId = !empty($filters['team_id']) ? (int)$filters['team_id'] : null;

        $groups = self::getStandardPlatformGroups();
        $fbAliases = array_map(fn($a) => "'" . $this->db->real_escape_string(strtolower(trim($a))) . "'", $groups['Facebook Form Lead']);
        $waAliases = array_map(fn($a) => "'" . $this->db->real_escape_string(strtolower(trim($a))) . "'", $groups['WhatsApp']);
        $miAliases = array_map(fn($a) => "'" . $this->db->real_escape_string(strtolower(trim($a))) . "'", $groups['Meta Inbox']);
        $gfAliases = array_map(fn($a) => "'" . $this->db->real_escape_string(strtolower(trim($a))) . "'", $groups['Google Form']);
        $blAliases = array_map(fn($a) => "'" . $this->db->real_escape_string(strtolower(trim($a))) . "'", $groups['Brochure Leads']);

        $allStandardAliases = array_merge($fbAliases, $waAliases, $miAliases, $gfAliases, $blAliases);

        $fbIn = implode(',', $fbAliases);
        $waIn = implode(',', $waAliases);
        $miIn = implode(',', $miAliases);
        $gfIn = implode(',', $gfAliases);
        $blIn = implode(',', $blAliases);
        $allIn = implode(',', $allStandardAliases);

        $sql = "
            SELECT 
                DATE(l.created_at) AS report_date,
                COALESCE(pc.name, 'Common') AS category_name,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ($fbIn) THEN 1 ELSE 0 END) AS fb_count,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ($waIn) THEN 1 ELSE 0 END) AS wa_count,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ($miIn) THEN 1 ELSE 0 END) AS mi_count,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ($gfIn) THEN 1 ELSE 0 END) AS gf_count,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(l.platform, ''))) IN ($blIn) THEN 1 ELSE 0 END) AS bl_count,
                SUM(CASE WHEN LOWER(TRIM(COALESCE(l.platform, ''))) NOT IN ($allIn) THEN 1 ELSE 0 END) AS others_count,
                COUNT(*) AS total_count
            FROM leads l
            LEFT JOIN project_categories pc ON l.project_category_id = pc.id
            WHERE l.deleted_at IS NULL AND l.uploaded_from = 'From Campaign'
        ";

        $params = [];
        $types  = "";

        if (!empty($fromDate) && !empty($toDate)) {
            $sql .= " AND DATE(l.created_at) BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
            $types   .= "ss";
        } elseif (!empty($fromDate)) {
            $sql .= " AND DATE(l.created_at) >= ?";
            $params[] = $fromDate;
            $types   .= "s";
        } elseif (!empty($toDate)) {
            $sql .= " AND DATE(l.created_at) <= ?";
            $params[] = $toDate;
            $types   .= "s";
        }

        if ($projectId) {
            $sql .= " AND l.project_id = ?";
            $params[] = $projectId;
            $types   .= "i";
        }

        if ($projectCategoryId) {
            $sql .= " AND l.project_category_id = ?";
            $params[] = $projectCategoryId;
            $types   .= "i";
        }

        if ($teamId) {
            $sql .= " AND l.team_id = ?";
            $params[] = $teamId;
            $types   .= "i";
        }

        $sql .= " GROUP BY DATE(l.created_at), l.project_category_id ORDER BY report_date DESC, category_name ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return ['rows' => [], 'totals' => []];

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // Compute Grand Totals across all dates
        $totals = [
            'fb_count'     => 0,
            'wa_count'     => 0,
            'mi_count'     => 0,
            'gf_count'     => 0,
            'bl_count'     => 0,
            'others_count' => 0,
            'total_count'  => 0,
        ];

        foreach ($rows as $r) {
            $totals['fb_count']     += (int)$r['fb_count'];
            $totals['wa_count']     += (int)$r['wa_count'];
            $totals['mi_count']     += (int)$r['mi_count'];
            $totals['gf_count']     += (int)$r['gf_count'];
            $totals['bl_count']     += (int)$r['bl_count'];
            $totals['others_count'] += (int)$r['others_count'];
            $totals['total_count']  += (int)$r['total_count'];
        }

        return [
            'rows'   => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * 
     * Get Team Lead Upload Report
     * Summarizes leads uploaded by role 11 executives grouped by member and upload date,
     * including sessions, first/last upload time, and KPI dashboard metrics.
     */
    public function getTeamLeadUploadReport($filters = [])
    {
        $datePreset = $filters['date_preset'] ?? 'today';
        $fromDate   = !empty($filters['from_date']) ? trim($filters['from_date']) : '';
        $toDate     = !empty($filters['to_date']) ? trim($filters['to_date']) : '';
        $memberId   = !empty($filters['member_id']) ? (int)$filters['member_id'] : null;
        $teamId     = !empty($filters['team_id']) ? (int)$filters['team_id'] : null;
        $search     = !empty($filters['search']) ? trim($filters['search']) : '';

        $where = "l.deleted_at IS NULL";
        $params = [];
        $types  = "";

        // If specific member selected, filter by that; else filter by role 11 (primary or secondary)
        if ($memberId) {
            $where .= " AND l.created_by = ?";
            $params[] = $memberId;
            $types .= "i";
        } else {
            $where .= " AND (u.role_id = 11 OR u.secondary_role_id = 11)";
        }

        // Team filter
        if ($teamId) {
            $where .= " AND u.team_id = ?";
            $params[] = $teamId;
            $types .= "i";
        }

        // Search keyword
        if ($search !== '') {
            $where .= " AND (u.full_name LIKE ? OR e.employee_id LIKE ? OR u.employee_id LIKE ? OR t.name LIKE ?)";
            $sParam = "%" . $search . "%";
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $params[] = $sParam;
            $types .= "ssss";
        }

        // Date Preset Filters
        if ($datePreset === 'today') {
            $where .= " AND DATE(l.created_at) = CURDATE()";
        } elseif ($datePreset === 'yesterday') {
            $where .= " AND DATE(l.created_at) = SUBDATE(CURDATE(), 1)";
        } elseif ($datePreset === 'last_7_days') {
            $where .= " AND DATE(l.created_at) >= SUBDATE(CURDATE(), 6) AND DATE(l.created_at) <= CURDATE()";
        } elseif ($datePreset === 'this_month') {
            $where .= " AND DATE_FORMAT(l.created_at, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        } elseif ($datePreset === 'last_month') {
            $where .= " AND DATE_FORMAT(l.created_at, '%Y-%m') = DATE_FORMAT(SUBDATE(CURDATE(), INTERVAL 1 MONTH), '%Y-%m')";
        } elseif ($datePreset === 'custom' || $datePreset === 'date_range' || (!empty($fromDate) && !empty($toDate))) {
            if (!empty($fromDate) && !empty($toDate)) {
                $where .= " AND DATE(l.created_at) BETWEEN ? AND ?";
                $params[] = $fromDate;
                $params[] = $toDate;
                $types .= "ss";
            } elseif (!empty($fromDate)) {
                $where .= " AND DATE(l.created_at) >= ?";
                $params[] = $fromDate;
                $types .= "s";
            } elseif (!empty($toDate)) {
                $where .= " AND DATE(l.created_at) <= ?";
                $params[] = $toDate;
                $types .= "s";
            }
        } elseif ($datePreset === 'all') {
            // all time
        } else {
            // Default to today
            $where .= " AND DATE(l.created_at) = CURDATE()";
        }

        $sql = "
            SELECT 
                l.id as lead_id,
                l.created_by,
                u.full_name as member_name,
                COALESCE(e.employee_id, u.employee_id) as emp_id,
                COALESCE(t.name, 'Lead Management') as team_name,
                d.name as designation,
                DATE(l.created_at) as upload_date,
                l.created_at
            FROM leads l
            JOIN users u ON l.created_by = u.id
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN designations d ON (e.designation_id = d.id OR u.designation_id = d.id)
            LEFT JOIN teams t ON u.team_id = t.id
            WHERE $where
            ORDER BY upload_date DESC, member_name ASC, l.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['rows' => [], 'kpi' => ['total_leads' => 0, 'total_sessions' => 0, 'active_uploaders' => 0, 'last_upload' => '—']];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $rowsByMemberDate = [];
        $totalLeads = 0;
        $distinctUploaders = [];
        $allTimestamps = [];

        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $totalLeads++;
                $distinctUploaders[$row['created_by']] = true;
                $allTimestamps[] = strtotime($row['created_at']);

                $key = $row['created_by'] . '_' . $row['upload_date'];
                if (!isset($rowsByMemberDate[$key])) {
                    $rowsByMemberDate[$key] = [
                        'created_by'   => $row['created_by'],
                        'member_name'  => $row['member_name'],
                        'employee_id'  => $row['emp_id'] ?: '—',
                        'team_name'    => $row['team_name'],
                        'designation'  => $row['designation'] ?: 'Executive',
                        'upload_date'  => $row['upload_date'],
                        'timestamps'   => [],
                    ];
                }
                $rowsByMemberDate[$key]['timestamps'][] = strtotime($row['created_at']);
            }
        }

        $summaryRows = [];
        $totalSessionsAll = 0;

        foreach ($rowsByMemberDate as $key => $item) {
            $timestamps = $item['timestamps'];
            $leadsCount = count($timestamps);

            // Calculate sessions (gap > 5 minutes = new session)
            $sessions = 1;
            $prev = $timestamps[0];
            foreach ($timestamps as $t) {
                if (($t - $prev) > 300) {
                    $sessions++;
                }
                $prev = $t;
            }
            $totalSessionsAll += $sessions;

            $firstUploadTime = date('h:i A', $timestamps[0]);
            $lastUploadTime  = date('h:i A', end($timestamps));

            $summaryRows[] = [
                'created_by'   => $item['created_by'],
                'team_name'    => $item['team_name'],
                'member_name'  => $item['member_name'],
                'employee_id'  => $item['employee_id'],
                'designation'  => $item['designation'],
                'upload_date'  => $item['upload_date'],
                'total_leads'  => $leadsCount,
                'sessions'     => $sessions,
                'first_upload' => $firstUploadTime,
                'last_upload'  => $lastUploadTime,
            ];
        }

        $lastUploadFormatted = !empty($allTimestamps) ? date('h:i A', max($allTimestamps)) : '—';
        $activeUploadersCount = count($distinctUploaders);

        return [
            'rows' => $summaryRows,
            'kpi'  => [
                'total_leads'      => $totalLeads,
                'total_sessions'   => $totalSessionsAll,
                'active_uploaders' => $activeUploadersCount,
                'last_upload'      => $lastUploadFormatted,
            ],
        ];
    }

    /**
     * Get list of all role 11 executives for filtering
     */
    public function getTeamLeadReportExecutives()
    {
        $sql = "
            SELECT DISTINCT u.id, u.full_name, COALESCE(e.employee_id, u.employee_id) AS employee_id, d.name AS designation, COALESCE(t.name, 'Lead Management') AS team_name
            FROM users u
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN designations d ON (e.designation_id = d.id OR u.designation_id = d.id)
            LEFT JOIN teams t ON u.team_id = t.id
            WHERE u.role_id = 11 OR u.secondary_role_id = 11
            ORDER BY u.full_name ASC
        ";
        $res = $this->db->query($sql);
        $executives = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $executives[] = $r;
            }
        }
        return $executives;
    }

    /**
     * Get detailed leads list uploaded by a member on a specific date
     */
    public function getTeamLeadReportLeads($memberId, $date)
    {
        $stmt = $this->db->prepare("
            SELECT l.id, l.full_name, l.phone_number, l.campaign_name, l.platform, l.uploaded_from, l.created_at, p.name as project_name
            FROM leads l
            LEFT JOIN projects p ON l.project_id = p.id
            WHERE l.created_by = ? AND DATE(l.created_at) = ? AND l.deleted_at IS NULL
            ORDER BY l.created_at DESC
            LIMIT 500
        ");
        if (!$stmt) return [];
        $stmt->bind_param("is", $memberId, $date);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get pending delete requests for a list of lead IDs
     */
    public function getPendingDeleteRequestsByLeadIds(array $leadIds): array
    {
        if (empty($leadIds)) return [];
        $idsStr = implode(',', array_map('intval', $leadIds));
        $res = $this->db->query("SELECT * FROM lead_delete_requests WHERE lead_id IN ($idsStr) AND status = 'pending'");
        $data = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[$row['lead_id']] = $row;
            }
        }
        return $data;
    }

    /**
     * Automatically sync active (unbooked) leads to the user's new team_id when their team changes.
     * Excludes soft-deleted leads and leads with booked/sold status.
     *
     * @param int|array $userIds User ID or array of User IDs
     * @param int $newTeamId The new team_id to assign
     * @return int Number of updated leads
     */
    public function syncActiveLeadsTeamByUser($userIds, int $newTeamId): int
    {
        if ($newTeamId <= 0) {
            return 0;
        }
        if (!is_array($userIds)) {
            $userIds = [(int)$userIds];
        }
        $userIds = array_values(array_filter(array_map('intval', $userIds), fn($id) => $id > 0));
        if (empty($userIds)) {
            return 0;
        }
        $inUsers = implode(',', $userIds);

        $sql = "UPDATE leads l
                SET l.team_id = ?
                WHERE l.assigned_id IN ($inUsers)
                  AND l.deleted_at IS NULL
                  AND (l.last_lead_action IS NULL OR LOWER(l.last_lead_action) != 'booked')
                  AND NOT EXISTS (
                      SELECT 1 FROM lead_bookings lb 
                      WHERE lb.lead_id = l.id 
                        AND lb.deleted_at IS NULL 
                        AND lb.booking_status IN ('booked', 'completed')
                  )";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $newTeamId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return max(0, $affected);
        }
        return 0;
    }

    /**
     * Automatically sync active (unbooked) leads to the new team_id by employee IDs.
     *
     * @param array $employeeIds
     * @param int $newTeamId
     * @return int
     */
    public function syncActiveLeadsTeamByEmployeeIds(array $employeeIds, int $newTeamId): int
    {
        if ($newTeamId <= 0 || empty($employeeIds)) {
            return 0;
        }
        $empIds = array_values(array_filter(array_map('intval', $employeeIds), fn($id) => $id > 0));
        if (empty($empIds)) {
            return 0;
        }
        $inEmps = implode(',', $empIds);

        $sql = "UPDATE leads l
                INNER JOIN employees e ON (l.assigned_id = e.user_id OR (e.user_id IS NULL AND l.assigned_id = e.id))
                SET l.team_id = ?
                WHERE e.id IN ($inEmps)
                  AND l.deleted_at IS NULL
                  AND (l.last_lead_action IS NULL OR LOWER(l.last_lead_action) != 'booked')
                  AND NOT EXISTS (
                      SELECT 1 FROM lead_bookings lb 
                      WHERE lb.lead_id = l.id 
                        AND lb.deleted_at IS NULL 
                        AND lb.booking_status IN ('booked', 'completed')
                  )";

        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $newTeamId);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            return max(0, $affected);
        }
        return 0;
    }
}

