<?php

class Lead extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->ensureDeleteRequestsTable();
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
            $sqlQuery .= " AND l.team_id = ?";
            $params[] = $actualTeamId;
            $types .= "i";
        }
        
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

    public function countAllWithFilter($year = null, $month = null, $lastAction = null, $roleId = null, $teamId = null, $assigned = null, $assigned_user = null, $date = null, $search = null, $from_date = null, $to_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null)
    {
        $sql = "SELECT COUNT(l.id) AS total_count FROM leads AS l 
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

        // Hide undistributed leads from sales users until approved (distribute = 1)
        if (!in_array((int)$loggedInRole, [1, 10, 11])) {
            $sql .= " AND (l.distribute = 1 OR l.distribute IS NULL)";
        }

        // TEAM BASED FILTER
        if (!empty($teamId)) {
            if (isset($_SESSION['user']['role_id']) && !in_array($_SESSION['user']['role_id'], [1, 3, 9, 32, 33, 34, 35])) {
                $loggedInUserId = $_SESSION['user']['id'] ?? 0;
                $sql .= " AND (l.team_id = ? OR l.assigned_id = ? OR l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id = ?))";
                $params[] = $teamId;
                $params[] = $loggedInUserId;
                $params[] = $teamId;
                $types .= "iii";
            } else {
                $sql .= " AND l.team_id = ?";
                $params[] = $teamId;
                $types .= "i";
            }
        }

        // Determine if we should apply created_at date filter
        $applyCreatedDateFilter = true;
        if (in_array($filter_card, ['total_booked', 'today_follow_up', 'pending_follow_ups'])) {
            $applyCreatedDateFilter = false;
        }

        // Date range filter
        if ($applyCreatedDateFilter) {
            $dateCol = (!empty($assigned_user) || (isset($assigned) && $assigned === 'assigned')) ? 'COALESCE(NULLIF(l.assigned_at, \'0000-00-00 00:00:00\'), l.created_at)' : 'l.created_at';
            
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

        // Last action filter
        if (!empty($lastAction)) {
            if (strtolower($lastAction) === 'irrelevant') {
                $sql .= " AND (l.lead_type = 'Irrelevant' OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status = 'Irrelevant' AND lf_sub.deleted_at IS NULL))";
            } else {
                $sql .= " AND l.last_lead_action = ?";
                $params[] = $lastAction;
                $types .= "s";
            }
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
            $sql .= " AND (l.full_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sss";
        }

        // Quick Filter Card
        if (!empty($filter_card)) {
            if ($filter_card === 'total_booked') {
                $sql .= " AND l.last_lead_action = 'booked'";
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = l.id AND lb_sub.deleted_at IS NULL AND DATE(lb_sub.booking_date) BETWEEN ? AND ?)";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                }
            } elseif ($filter_card === 'today_follow_up') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
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
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                    )";
                }
            } elseif ($filter_card === 'pending_follow_ups') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.status = 'pending'
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                          AND lf_sub.status = 'pending'
                    )";
                }
            } elseif ($filter_card === 'hot_leads') {
                $sql .= " AND l.lead_type = 'Hot'";
            } elseif ($filter_card === 'contacted_leads') {
                $sql .= " AND (
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
            } elseif ($filter_card === 'not_contacted_leads') {
                $sql .= " AND (l.last_lead_action IS NULL OR l.last_lead_action NOT IN ('contacted', 'follow up', 'booked', 'visited'))
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
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0";
            } elseif ($filter_card === 'not_assigned_leads') {
                $sql .= " AND (l.assigned_id IS NULL OR l.assigned_id = 0)";
            } elseif ($filter_card === 'has_follow_up') {
                $sql .= " AND EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL)";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (l.lead_type = 'Irrelevant' OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status = 'Irrelevant' AND lf_sub.deleted_at IS NULL))";
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
            $sql .= " AND (l.project_category_id = ?" . (!empty($projectId) ? " OR l.project_id = ?" : "") . ")";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
            if (!empty($projectId)) {
                $params[] = (int)$projectId;
                $types .= "i";
            }
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

    public function getAllWithFilter($year = null, $month = null, $lastAction = null, $roleId = null, $teamId = null, $assigned = null, $assigned_user = null, $date = null, $limit = null, $offset = null, $search = null, $from_date = null, $to_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null)
    {
        // Optimized query: server e large dataset (3000+) handle korte
        // Correlated subquery replace kora hoyeche simpler LEFT JOIN diye
        $sql = "SELECT 
        l.*,
        u.full_name AS agent_name,
        COALESCE(u_assigned_by.full_name, u_by.full_name, 'System') AS assigned_by_name,
        u_by.full_name AS creator_name,
        COALESCE(p.name, lp.name) AS project_name,
        lpc.name AS project_category_name,
        lf.next_action,
        lf.follow_up_type,
        lf.next_follow_up_date,
        lf.discussion_note,
        ldr.delete_request_reason,
        ldr.delete_requested_at,
        ldr.delete_requested_by_name
    FROM leads AS l
    LEFT JOIN users AS u ON l.assigned_id = u.id
    LEFT JOIN users AS u_assigned_by ON l.assigned_by = u_assigned_by.id
    LEFT JOIN users AS u_by ON l.created_by = u_by.id
    LEFT JOIN employees AS emp ON emp.user_id = l.assigned_id
    LEFT JOIN (
        SELECT lf1.lead_id, lf1.next_action, lf1.follow_up_type, lf1.next_follow_up_date, lf1.discussion_note
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

        // Hide undistributed leads from sales users until approved (distribute = 1)
        if (!in_array((int)$loggedInRole, [1, 10, 11])) {
            $sql .= " AND (l.distribute = 1 OR l.distribute IS NULL)";
        }

        // TEAM BASED FILTER
        if (!empty($teamId)) {
            if (isset($_SESSION['user']['role_id']) && !in_array($_SESSION['user']['role_id'], [1, 3, 9, 32, 33, 34, 35])) {
                $loggedInUserId = $_SESSION['user']['id'] ?? 0;
                $sql .= " AND (l.team_id = ? OR l.assigned_id = ? OR l.assigned_id IN (SELECT u_tm.id FROM users u_tm WHERE u_tm.team_id = ?))";
                $params[] = $teamId;
                $params[] = $loggedInUserId;
                $params[] = $teamId;
                $types .= "iii";
            } else {
                $sql .= " AND l.team_id = ?";
                $params[] = $teamId;
                $types .= "i";
            }
        }

        // Determine if we should apply created_at date filter
        $applyCreatedDateFilter = true;
        if (in_array($filter_card, ['total_booked', 'today_follow_up', 'pending_follow_ups'])) {
            $applyCreatedDateFilter = false;
        }

        // Date range filter
        if ($applyCreatedDateFilter) {
            $dateCol = (!empty($assigned_user) || (isset($assigned) && $assigned === 'assigned')) ? 'COALESCE(NULLIF(l.assigned_at, \'0000-00-00 00:00:00\'), l.created_at)' : 'l.created_at';

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
            if (strtolower($lastAction) === 'irrelevant') {
                $sql .= " AND (l.lead_type = 'Irrelevant' OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status = 'Irrelevant' AND lf_sub.deleted_at IS NULL))";
            } else {
                $sql .= " AND l.last_lead_action = ?";
                $params[] = $lastAction;
                $types .= "s";
            }
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
            $sql .= " AND (l.full_name LIKE ? OR l.phone LIKE ? OR l.phone_number LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "sss";
        }

        // Quick Filter Card
        if (!empty($filter_card)) {
            if ($filter_card === 'total_booked') {
                $sql .= " AND l.last_lead_action = 'booked'";
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = l.id AND lb_sub.deleted_at IS NULL AND DATE(lb_sub.booking_date) BETWEEN ? AND ?)";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                }
            } elseif ($filter_card === 'today_follow_up') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
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
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                    )";
                }
            } elseif ($filter_card === 'pending_follow_ups') {
                if (!empty($from_date) && !empty($to_date)) {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.status = 'pending'
                          AND DATE(lf_sub.next_follow_up_date) BETWEEN ? AND ?
                    )";
                    $params[] = $from_date;
                    $params[] = $to_date;
                    $types .= "ss";
                } else {
                    $sql .= " AND EXISTS (
                        SELECT 1 FROM lead_follow_ups lf_sub
                        WHERE lf_sub.lead_id = l.id
                          AND lf_sub.deleted_at IS NULL
                          AND lf_sub.next_follow_up_date >= CURDATE()
                          AND lf_sub.next_follow_up_date < CURDATE() + INTERVAL 1 DAY
                          AND lf_sub.status = 'pending'
                    )";
                }
            } elseif ($filter_card === 'hot_leads') {
                $sql .= " AND l.lead_type = 'Hot'";
            } elseif ($filter_card === 'contacted_leads') {
                $sql .= " AND (
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
            } elseif ($filter_card === 'not_contacted_leads') {
                $sql .= " AND (l.last_lead_action IS NULL OR l.last_lead_action NOT IN ('contacted', 'follow up', 'booked', 'visited'))
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
                $sql .= " AND l.assigned_id IS NOT NULL AND l.assigned_id > 0";
            } elseif ($filter_card === 'not_assigned_leads') {
                $sql .= " AND (l.assigned_id IS NULL OR l.assigned_id = 0)";
            } elseif ($filter_card === 'has_follow_up') {
                $sql .= " AND EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.deleted_at IS NULL)";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (l.lead_type = 'Irrelevant' OR EXISTS (SELECT 1 FROM lead_follow_ups lf_sub WHERE lf_sub.lead_id = l.id AND lf_sub.lead_status = 'Irrelevant' AND lf_sub.deleted_at IS NULL))";
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
            $sql .= " AND (l.project_category_id = ?" . (!empty($projectId) ? " OR l.project_id = ?" : "") . ")";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
            if (!empty($projectId)) {
                $params[] = (int)$projectId;
                $types .= "i";
            }
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
                WHERE l.deleted_at IS NULL AND l.status = 'active' AND l.last_lead_action != 'cancel' AND l.lead_type = 'Hot'
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
            $sql .= " AND (l.project_category_id = ?" . (!empty($projectId) ? " OR l.project_id = ?" : "") . ")";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
            if (!empty($projectId)) {
                $params[] = (int)$projectId;
                $types .= "i";
            }
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
    public function getAllMyLeads(int $userId, $year = null, $month = null, $lastAction = null, $lead_status = null, $only_missing_followup = false, $search = null, $followup_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $from_date = null, $to_date = null, $uncontacted = false, $limit = null, $offset = null)
    {
        $sql = "SELECT leads.*, 
        COALESCE(u_assigned_by.full_name, u_by.full_name, 'System') AS assigned_by_name,
        u_by.full_name AS creator_name,
        lf.next_action,
        lf.follow_up_type,
        lf.status AS last_follow_up_status,
        lf.lead_status AS last_follow_up_lead_status,
        lf.discussion_note AS last_follow_up_note,
        p.name AS project_name,
        lpc.name AS project_category_name,
        f.flat_name,
        lf.next_follow_up_date,
        u_transfer.full_name AS transfer_from_name
    FROM leads
    LEFT JOIN users AS u_assigned_by ON leads.assigned_by = u_assigned_by.id
    LEFT JOIN users AS u_by ON leads.created_by = u_by.id
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
    WHERE leads.assigned_id = ? AND leads.deleted_at IS NULL";

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

        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (leads.assigned_at IS NOT NULL AND leads.assigned_at != '0000-00-00 00:00:00' AND leads.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((leads.assigned_at IS NULL OR leads.assigned_at = '0000-00-00 00:00:00') AND leads.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
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
            } else {
                $sql .= " AND leads.last_lead_action = ?";
                $params[] = $lastAction;
                $types .= "s";
            }
        }
        if (!empty($lead_status)) {
            $sql .= " AND (lf.lead_status = ? OR leads.lead_type = ?)";
            $params[] = $lead_status;
            $params[] = $lead_status;
            $types .= "ss";
        }

        if ($only_missing_followup) {
            $sql .= " AND lf.next_follow_up_date IS NOT NULL
                      AND lf.next_follow_up_date > '1000-01-01'
                      AND DATE(lf.next_follow_up_date) <= CURDATE()
                      AND lf.deleted_at IS NULL";
        }

        if ($uncontacted) {
            $sql .= " AND (SELECT COUNT(*) FROM lead_follow_ups WHERE lead_id = leads.id) = 0";
        }
        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
            $types .= "ss";
        } elseif (!empty($from_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) >= ?";
            $params[] = $from_date;
            $types .= "s";
        } elseif (!empty($to_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) <= ?";
            $params[] = $to_date;
            $types .= "s";
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
            $sql .= " AND (leads.project_category_id = ?" . (!empty($projectId) ? " OR leads.project_id = ?" : "") . ")";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
            if (!empty($projectId)) {
                $params[] = (int)$projectId;
                $types .= "i";
            }
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
            if ($filter_card === 'contacted') {
                $sql .= " AND " . $contactedCond;
            } elseif ($filter_card === 'not_called' || $filter_card === 'not_contacted') {
                $sql .= " AND NOT " . $contactedCond;
            } elseif ($filter_card === 'booked') {
                $sql .= " AND LOWER(leads.last_lead_action) = 'booked'";
            } elseif ($filter_card === 'upcoming_follow_ups') {
                $sql .= " AND lf.next_follow_up_date IS NOT NULL AND DATE(lf.next_follow_up_date) >= CURDATE()";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (leads.lead_type = 'Irrelevant' OR lf.lead_status = 'Irrelevant')";
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

    public function getAllMyLeadsCount(int $userId, $year = null, $month = null, $lastAction = null, $lead_status = null, $only_missing_followup = false, $search = null, $followup_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $from_date = null, $to_date = null, $uncontacted = false)
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
    WHERE leads.assigned_id = ? AND leads.deleted_at IS NULL";

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

        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (leads.assigned_at IS NOT NULL AND leads.assigned_at != '0000-00-00 00:00:00' AND leads.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((leads.assigned_at IS NULL OR leads.assigned_at = '0000-00-00 00:00:00') AND leads.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
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
            } else {
                $sql .= " AND leads.last_lead_action = ?";
                $params[] = $lastAction;
                $types .= "s";
            }
        }
        if (!empty($lead_status)) {
            $sql .= " AND (lf.lead_status = ? OR leads.lead_type = ?)";
            $params[] = $lead_status;
            $params[] = $lead_status;
            $types .= "ss";
        }

        if ($only_missing_followup) {
            $sql .= " AND lf.next_follow_up_date IS NOT NULL
                      AND lf.next_follow_up_date > '1000-01-01'
                      AND DATE(lf.next_follow_up_date) <= CURDATE()
                      AND lf.deleted_at IS NULL";
        }

        if ($uncontacted) {
            $sql .= " AND (SELECT COUNT(*) FROM lead_follow_ups WHERE lead_id = leads.id) = 0";
        }
        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
            $types .= "ss";
        } elseif (!empty($from_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) >= ?";
            $params[] = $from_date;
            $types .= "s";
        } elseif (!empty($to_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) <= ?";
            $params[] = $to_date;
            $types .= "s";
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
            $sql .= " AND (leads.project_category_id = ?" . (!empty($projectId) ? " OR leads.project_id = ?" : "") . ")";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
            if (!empty($projectId)) {
                $params[] = (int)$projectId;
                $types .= "i";
            }
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
            if ($filter_card === 'contacted') {
                $sql .= " AND " . $contactedCond;
            } elseif ($filter_card === 'not_called' || $filter_card === 'not_contacted') {
                $sql .= " AND NOT " . $contactedCond;
            } elseif ($filter_card === 'booked') {
                $sql .= " AND LOWER(leads.last_lead_action) = 'booked'";
            } elseif ($filter_card === 'upcoming_follow_ups') {
                $sql .= " AND lf.next_follow_up_date IS NOT NULL AND DATE(lf.next_follow_up_date) >= CURDATE()";
            } elseif ($filter_card === 'irrelevant_leads' || $filter_card === 'irrelevant') {
                $sql .= " AND (leads.lead_type = 'Irrelevant' OR lf.lead_status = 'Irrelevant')";
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

    public function getMyAssignedLeadsSummaryCards(int $userId, $year = null, $month = null, $lastAction = null, $lead_status = null, $only_missing_followup = false, $search = null, $followup_date = null, $filter_card = null, $projectId = null, $projectCategoryId = null, $country = null, $assignedHours = null, $from_date = null, $to_date = null, $uncontacted = false)
    {
        $contactedCond = "(LOWER(leads.last_lead_action) IN ('contacted', 'follow up', 'visited', 'booked') OR leads.call_status IN ('called', 'received') OR (leads.communication_type IS NOT NULL AND leads.communication_type != '' AND LOWER(leads.communication_type) != 'not select') OR lf.id IS NOT NULL OR EXISTS (SELECT 1 FROM lead_bookings lb_sub WHERE lb_sub.lead_id = leads.id AND lb_sub.deleted_at IS NULL))";

        $sql = "SELECT 
            COUNT(DISTINCT leads.id) as total_leads,
            SUM(CASE WHEN {$contactedCond} THEN 1 ELSE 0 END) as contacted_leads,
            SUM(CASE WHEN NOT {$contactedCond} THEN 1 ELSE 0 END) as not_contacted_leads,
            SUM(CASE WHEN LOWER(leads.last_lead_action) = 'booked' THEN 1 ELSE 0 END) as booked_leads,
            SUM(CASE WHEN lf.next_follow_up_date IS NOT NULL AND DATE(lf.next_follow_up_date) >= CURDATE() THEN 1 ELSE 0 END) as upcoming_follow_ups,
            SUM(CASE WHEN lf.next_follow_up_date IS NOT NULL AND lf.next_follow_up_date > '1000-01-01' AND DATE(lf.next_follow_up_date) <= CURDATE() AND lf.deleted_at IS NULL THEN 1 ELSE 0 END) as missing_follow_ups,
            SUM(CASE WHEN (leads.lead_type = 'Irrelevant' OR lf.lead_status = 'Irrelevant') THEN 1 ELSE 0 END) as irrelevant_leads
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
        WHERE leads.assigned_id = ? AND leads.deleted_at IS NULL";

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

        if (!empty($assignedHours)) {
            $hrs = (int)$assignedHours;
            $sql .= " AND (
                (leads.assigned_at IS NOT NULL AND leads.assigned_at != '0000-00-00 00:00:00' AND leads.assigned_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
                OR
                ((leads.assigned_at IS NULL OR leads.assigned_at = '0000-00-00 00:00:00') AND leads.updated_at >= DATE_SUB(NOW(), INTERVAL ? HOUR))
            )";
            $params[] = $hrs;
            $params[] = $hrs;
            $types .= "ii";
        }

        if ($uncontacted) {
            $sql .= " AND (SELECT COUNT(*) FROM lead_follow_ups WHERE lead_id = leads.id) = 0";
        }

        if (!empty($from_date) && !empty($to_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) BETWEEN ? AND ?";
            $params[] = $from_date;
            $params[] = $to_date;
            $types .= "ss";
        } elseif (!empty($from_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) >= ?";
            $params[] = $from_date;
            $types .= "s";
        } elseif (!empty($to_date)) {
            $sql .= " AND DATE(COALESCE(NULLIF(leads.assigned_at, '0000-00-00 00:00:00'), leads.created_at)) <= ?";
            $params[] = $to_date;
            $types .= "s";
        }

        if (!empty($lastAction)) {
            if (strtolower($lastAction) === 'contacted') {
                $sql .= " AND " . $contactedCond;
            } elseif (strtolower($lastAction) === 'not_contacted') {
                $sql .= " AND NOT " . $contactedCond;
            } else {
                $sql .= " AND leads.last_lead_action = ?";
                $params[] = $lastAction;
                $types .= "s";
            }
        }
        if (!empty($lead_status)) {
            $sql .= " AND (lf.lead_status = ? OR leads.lead_type = ?)";
            $params[] = $lead_status;
            $params[] = $lead_status;
            $types .= "ss";
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
            $sql .= " AND (leads.project_category_id = ?" . (!empty($projectId) ? " OR leads.project_id = ?" : "") . ")";
            $params[] = (int)$projectCategoryId;
            $types .= "i";
            if (!empty($projectId)) {
                $params[] = (int)$projectId;
                $types .= "i";
            }
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
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ?: ['total_leads' => 0, 'contacted_leads' => 0, 'not_contacted_leads' => 0, 'booked_leads' => 0, 'upcoming_follow_ups' => 0, 'missing_follow_ups' => 0];
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
                     JOIN lead_payments lp ON lp.booking_id = lb.id AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified'
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
                       JOIN lead_payments lp ON lp.booking_id = lb.id AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified'
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
        // Auto-assign duplicates to the first owner or team
        $firstAssignee = $this->findFirstAssignee($data['phone_number'] ?? $data['phone'] ?? null, $data['email'] ?? null);
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
        father_name,
        mother_name,
        dob,
        permanent_address,
        nid_or_passport,
        phone_number,
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
        team_id,
        created_by,
        country,
        2nd_lead_owner_name,
        2nd_owner_phone,
        2nd_owner_email,
        2nd_owner_address,
        2nd_owner_other_details ) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

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
        $job_title = isset($data['job_title']) ? mb_substr((string)$data['job_title'], 0, 255, 'UTF-8') : null;
        $city = $data['city'] ?? null;

        // Existing fields
        $full_name = isset($data['full_name']) ? mb_substr((string)$data['full_name'], 0, 255, 'UTF-8') : null;
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
        $alt_email = $data['alt_email'] ?? null;
        $source_type_id = $data['source_type_id'] ?? null;
        $assigned_id = $data['assigned_id'] ?? null;
        $address = $data['address'] ?? null;
        $pref_location_id = $data['pref_location_id'] ?? null;
        $designation = $data['designation'] ?? null;
        $project_id = $data['project_id'] ?? null;
        $project_category_id = $data['project_category_id'] ?? null;
        $pref_flat_size = $data['pref_flat_size'] ?? null;
        $communication_type = $data['communication_type'] ?? null;
        $tags = $data['tags'] ?? null;
        $description = $data['description'] ?? null;
        $visibility = $data['visibility'] ?? 'public';
        $status = $data['status'] ?? 'active';
        $lead_quality = $data['lead_quality'] ?? 'Pending';
        if (!in_array($lead_quality, ['Pending', 'Prospect', 'Not Interested'])) {
            $lead_quality = 'Pending';
        }
        $lead_type = (!empty($data['lead_type']) && trim((string)$data['lead_type']) !== '') ? trim((string)$data['lead_type']) : null;
        $company_name = isset($data['company_name']) ? mb_substr((string)$data['company_name'], 0, 255, 'UTF-8') : null;
        $team_id = $data['team_id'] ?? null;
        $created_by = $data['created_by'] ?? null;
        $country = $data['country'] ?? null;
        $second_lead_owner_name = $data['2nd_lead_owner_name'] ?? null;
        $second_owner_phone = $data['2nd_owner_phone'] ?? null;
        $second_owner_email = $data['2nd_owner_email'] ?? null;
        $second_owner_address = $data['2nd_owner_address'] ?? null;
        $second_owner_other_details = $data['2nd_owner_other_details'] ?? null;

        // Bind parameters (CSV first, then existing)
        $stmt->bind_param(
            "sssssssssssssssssssiisisiisssssssssiissssss",
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
        $old = $this->find($id);
        if (!$old) {
            return false;
        }

        // Helper for nullable string values (empty string -> null or existing)
        $nn = function($key) use ($data, $old) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                return (isset($val) && trim((string)$val) !== '') ? trim((string)$val) : null;
            }
            return $old[$key] ?? null;
        };

        // Helper for integer IDs (empty string -> null or existing)
        $ni = function($key) use ($data, $old) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                return (isset($val) && trim((string)$val) !== '' && is_numeric($val)) ? (int)$val : null;
            }
            return isset($old[$key]) && is_numeric($old[$key]) ? (int)$old[$key] : null;
        };

        // Helper for float/double values
        $nf = function($key) use ($data, $old) {
            if (array_key_exists($key, $data)) {
                $val = $data[$key];
                return (isset($val) && trim((string)$val) !== '' && is_numeric($val)) ? (float)$val : null;
            }
            return isset($old[$key]) && is_numeric($old[$key]) ? (float)$old[$key] : null;
        };

        // Date fields
        $dob      = $nn('dob');
        $nomi_dob = $nn('nomi_dob');

        // Personal Info
        $full_name            = $nn('full_name') ?? $nn('lead_name');
        $lead_name            = $full_name;
        $lead_name_bn         = $nn('lead_name_bn');
        $father_name          = $nn('father_name');
        $father_name_bn       = $nn('father_name_bn');
        $mother_name          = $nn('mother_name');
        $mother_name_bn       = $nn('mother_name_bn');
        $nationality          = $nn('nationality') ?? 'Bangladeshi';
        // Religion (Enum-safe)
        $religion             = $nn('religion');
        if (!empty($religion) && !in_array($religion, ['Islam', 'Hinduism', 'Buddhism', 'Christianity', 'Other'])) {
            $religion = null;
        }

        $residency_type       = $nn('residency_type');
        if (!empty($residency_type)) {
            $residency_type = in_array(strtolower($residency_type), ['nrb', 'expatriate']) ? 'expatriate' : 'local';
        } else {
            $residency_type = 'local';
        }
        $nid_or_passport      = $nn('nid_or_passport');

        // Identity Type (Enum-safe)
        $identity_type        = $nn('identity_type');
        if (!empty($identity_type) && !in_array($identity_type, ['NID', 'Passport'])) {
            $identity_type = null;
        }

        // Contact & Address
        $phone                = $nn('phone') ?? $nn('phone_number');
        $phone_number         = $phone;
        $alt_phone_number     = $nn('alt_phone_number');
        $email                = $nn('email');
        $alt_email            = $nn('alt_email');
        $address              = $nn('address');
        $present_add_bn       = $nn('present_add_bn');
        $permanent_address    = $nn('permanent_address');
        $permanent_add_bn     = $nn('permanent_add_bn');
        $city                 = $nn('city');
        $country              = $nn('country');

        // Professional Info
        $designation          = $nn('designation');
        $profession_bn        = $nn('profession_bn');
        $job_title            = $nn('job_title');
        $company_name         = $nn('company_name') ?? $nn('company');
        $company              = $company_name;

        // Campaign & Source Meta
        $campaign_name        = $nn('campaign_name');
        $platform             = $nn('platform');
        $uploaded_from        = $nn('uploaded_from');
        if (!empty($uploaded_from) && !in_array($uploaded_from, ['From Campaign', 'From Company csv', 'Manually added', ''])) {
            $uploaded_from = 'From Campaign';
        }
        $source_type_id       = $ni('source_type_id');
        $assigned_id          = $ni('assigned_id');
        $team_id              = $ni('team_id');
        $project_id           = $ni('project_id');
        $project_category_id  = $ni('project_category_id');
        $pref_location_id     = $ni('pref_location_id');

        // Lead Type (Enum-safe)
        $lead_type            = $nn('lead_type');
        $validLeadTypes = ['No Response', 'Pending', 'Prospect', 'High Prospect', 'Will Visit', 'Visited', 'Sold', 'Lead Transferred', 'Project Transferred', 'Not Interested', "Don't Call Again", 'Refund', 'Future', 'Cold', 'Hot', 'Warm', 'Irrelevant'];
        if (!empty($lead_type) && !in_array($lead_type, $validLeadTypes)) {
            $lead_type = null;
        }

        $lead_quality         = $nn('lead_quality');
        if (!empty($lead_quality) && !in_array($lead_quality, ['Pending', 'Prospect', 'Not Interested'])) {
            $lead_quality = 'Pending';
        }
        $visibility           = $nn('visibility') ?? 'public';
        if (!in_array($visibility, ['public', 'private', 'selected'])) {
            $visibility = 'public';
        }
        $status               = $nn('status') ?? 'active';
        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }
        $call_status          = $nn('call_status');
        if (!empty($call_status) && !in_array($call_status, ['not_called', 'called', 'received', 'not_received'])) {
            $call_status = 'not_called';
        }
        $call_note            = $nn('call_note');
        $value_amount         = $nf('value_amount');
        $currency             = $nn('currency') ?? 'BDT';
        if (!in_array($currency, ['USD', 'EURO', 'RUBLE', 'POUND', 'BDT'])) {
            $currency = 'BDT';
        }

        // Customer Requirements & Preferences
        $which_apt_size       = $nn('which_apartment_size_are_you_interested_in');
        $which_floor_shop     = $nn('which_floor_are_you_for_shop');
        $when_buy             = $nn('when_are_you_planning_to_buy');
        $schedule_visit       = $nn('would_you_like_to_schedule_a_site_visit');
        
        $best_time_contact    = $nn('when_is_the_best_time_to_contact_you') ?? $nn('preferred_contact_time');
        if ($best_time_contact !== null && mb_strlen($best_time_contact) > 50) {
            $best_time_contact = mb_substr($best_time_contact, 0, 50);
        }

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

        $pref_flat_size       = $nn('pref_flat_size') ?? $which_apt_size;

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
        $nominee_name         = $nn('nominee_name');
        $relation_with_lead   = $nn('relation_with_lead');
        $nomi_father_name     = $nn('nomi_father_name');
        $nomi_mother_name     = $nn('nomi_mother_name');
        $nomi_profession      = $nn('nomi_profession');
        $nomi_phone           = $nn('nomi_phone');
        $nomi_email           = $nn('nomi_email');
        $nomi_nid_passport    = $nn('nomi_nid_passport');
        $nomi_present_add     = $nn('nomi_present_add');
        $nomi_permanent_address = $nn('nomi_permanent_address');

        // 2nd Nominee Info
        $nominee_name_2         = $nn('2nd_nominee_name');
        $relation_with_lead_2   = $nn('2nd_relation_with_lead');
        $nomi_father_name_2     = $nn('2nd_nomi_father_name');
        $nomi_mother_name_2     = $nn('2nd_nomi_mother_name');
        $nomi_profession_2      = $nn('2nd_nomi_profession');
        $nomi_dob_2             = $nn('2nd_nomi_dob');
        $nomi_phone_2           = $nn('2nd_nomi_phone');
        $nomi_email_2           = $nn('2nd_nomi_email');
        $nomi_nid_passport_2    = $nn('2nd_nomi_nid_passport');
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
            '2nd_lead_owner_name' => $nn('2nd_lead_owner_name'),
            '2nd_owner_phone' => $nn('2nd_owner_phone'),
            '2nd_owner_email' => $nn('2nd_owner_email'),
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
}

