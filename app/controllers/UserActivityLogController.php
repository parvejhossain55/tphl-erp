<?php

class UserActivityLogController extends Controller
{
    protected $db;
    protected $kpiService;

    public function __construct($db)
    {
        $this->db = $db;
        require_once __DIR__ . '/../services/EmployeeKPIService.php';
        $this->kpiService = new EmployeeKPIService($db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Only role_id 1 (Admin), 3 (Super Admin / Management), 33, 34, 35 can access
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 33, 34, 35])) {
            header("Location: " . $GLOBALS['base_url'] . "/home");
            exit;
        }

        // ── Default Date Range (26th of previous/current month to 25th of current/next month) ──────────────────────────────
        if ((int)date('d') > 25) {
            $defaultFrom = date('Y-m-26');
            $defaultTo = date('Y-m-25', strtotime('first day of +1 month'));
        } else {
            $defaultFrom = date('Y-m-26', strtotime('first day of -1 month'));
            $defaultTo = date('Y-m-25');
        }

        // ── Filters from GET ──────────────────────────────────────────────────
        $filterDeptId = isset($_GET['department_id']) && $_GET['department_id'] !== ''
            ? (int)$_GET['department_id'] : null;

        $filterUserId = isset($_GET['user_id']) && $_GET['user_id'] !== ''
            ? (int)$_GET['user_id'] : null;

        $filterDateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== ''
            ? $_GET['date_from'] : $defaultFrom;

        $filterDateTo = isset($_GET['date_to']) && $_GET['date_to'] !== ''
            ? $_GET['date_to'] : $defaultTo;

        // ── Fetch all departments for filter dropdown ──────────────────────────
        $deptResult = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        $departments = $deptResult ? $deptResult->fetch_all(MYSQLI_ASSOC) : [];

        // ── Fetch all users for Employee search dropdown ───────────────────────
        $userQuery = "
            SELECT u.id, u.full_name, u.email, dept.name as department_name, e.employee_id
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
            LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
            WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated'))
        ";
        if ($filterDeptId !== null) {
            $userQuery .= " AND dept.id = ?";
            $userQuery .= " ORDER BY u.full_name ASC";
            $stmtU = $this->db->prepare($userQuery);
            $stmtU->bind_param('i', $filterDeptId);
            $stmtU->execute();
            $userResult = $stmtU->get_result();
            $allUsers = $userResult ? $userResult->fetch_all(MYSQLI_ASSOC) : [];
            $stmtU->close();
        } else {
            $userQuery .= " ORDER BY u.full_name ASC";
            $userResult = $this->db->query($userQuery);
            $allUsers = $userResult ? $userResult->fetch_all(MYSQLI_ASSOC) : [];
        }

        // ── 1. Total ERP Users Base Query (Active Employees) ───────────────────
        $baseUsersSql = "
            SELECT 
                u.id, u.full_name, u.email, e.employee_id,
                dept.id AS department_id, dept.name AS department_name, 
                des.name AS designation_name,
                MAX(ual.login_time) as last_login_ever
            FROM users u
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
            LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
            LEFT JOIN user_activity_logs ual ON ual.user_id = u.id
            WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated'))
        ";
        $usersParams = [];
        $usersParamTypes = '';

        if ($filterDeptId !== null) {
            $baseUsersSql .= " AND dept.id = ?";
            $usersParams[] = $filterDeptId;
            $usersParamTypes .= 'i';
        }
        if ($filterUserId !== null) {
            $baseUsersSql .= " AND u.id = ?";
            $usersParams[] = $filterUserId;
            $usersParamTypes .= 'i';
        }
        $baseUsersSql .= " GROUP BY u.id, u.full_name, u.email, e.employee_id, dept.id, dept.name, des.name";

        $stmtUsers = $this->db->prepare($baseUsersSql);
        if (!empty($usersParams)) {
            if (count($usersParams) === 2) {
                $stmtUsers->bind_param($usersParamTypes, $usersParams[0], $usersParams[1]);
            } else {
                $stmtUsers->bind_param($usersParamTypes, $usersParams[0]);
            }
        }
        $stmtUsers->execute();
        $targetUsers = $stmtUsers->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtUsers->close();
        
        $totalUsersCount = count($targetUsers);
        $targetUserIds = array_column($targetUsers, 'id');

        // ── 2. Fetch Activity Logs for Target Users within Date Range ──────────
        $logs = [];
        if (!empty($targetUserIds)) {
            $placeholders = implode(',', array_fill(0, count($targetUserIds), '?'));
            $logsSql = "
                SELECT 
                    ual.user_id,
                    DATE(ual.login_time) as log_date,
                    ual.login_time,
                    ual.last_active_time,
                    TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time) as active_minutes
                FROM user_activity_logs ual
                WHERE DATE(ual.login_time) >= ? AND DATE(ual.login_time) <= ?
                AND ual.user_id IN ($placeholders)
            ";
            
            $logsParams = array_merge([$filterDateFrom, $filterDateTo . ' 23:59:59'], $targetUserIds);
            $logsParamTypes = 'ss' . str_repeat('i', count($targetUserIds));
            
            $stmtLogs = $this->db->prepare($logsSql);
            $stmtLogs->bind_param($logsParamTypes, ...$logsParams);
            $stmtLogs->execute();
            $logs = $stmtLogs->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtLogs->close();
        }

        // ── 3. Process Data for Dashboard Metrics ─────────────────────────────
        
        $workingDaysCount = 1;
        $start = new DateTime($filterDateFrom);
        $end = new DateTime($filterDateTo);
        $interval = $start->diff($end);
        $workingDaysCount = max(1, $interval->days + 1);

        $userDailyStats = []; // user_id => [date => [total_mins, first_login, last_logout, sessions]]
        $hourlyActiveRaw = array_fill(8, 13, 0); // 8 AM to 8 PM (20)
        
        foreach ($logs as $log) {
            $uid = $log['user_id'];
            $date = $log['log_date'];
            $mins = max(0, (int)$log['active_minutes']);
            
            if (!isset($userDailyStats[$uid])) {
                $userDailyStats[$uid] = [];
            }
            if (!isset($userDailyStats[$uid][$date])) {
                $userDailyStats[$uid][$date] = [
                    'total_mins' => 0,
                    'first_login' => $log['login_time'],
                    'last_logout' => $log['last_active_time'] ?: $log['login_time'],
                    'sessions' => 0
                ];
            }
            
            $userDailyStats[$uid][$date]['total_mins'] += $mins;
            $userDailyStats[$uid][$date]['sessions'] += 1;
            
            if ($log['login_time'] < $userDailyStats[$uid][$date]['first_login']) {
                $userDailyStats[$uid][$date]['first_login'] = $log['login_time'];
            }
            if ($log['last_active_time'] > $userDailyStats[$uid][$date]['last_logout']) {
                $userDailyStats[$uid][$date]['last_logout'] = $log['last_active_time'];
            }

            // Hourly Active Calculation (simplified overlap check)
            $logStartTS = strtotime($log['login_time']);
            $logEndTS = strtotime($log['last_active_time'] ?: $log['login_time']);
            
            for ($h = 8; $h <= 20; $h++) {
                $hourStartTS = strtotime(date("Y-m-d $h:00:00", $logStartTS));
                $hourEndTS = strtotime(date("Y-m-d $h:59:59", $logStartTS));
                
                if ($logStartTS <= $hourEndTS && $logEndTS >= $hourStartTS) {
                    if (!is_array($hourlyActiveRaw[$h])) {
                        $hourlyActiveRaw[$h] = [];
                    }
                    $hourlyActiveRaw[$h][$uid] = true;
                }
            }
        }

        // Finalize hourly active users count
        $hourlyChartLabels = [];
        $hourlyChartData = [];
        for ($h = 8; $h <= 20; $h++) {
            $amPm = $h >= 12 ? ($h == 12 ? 12 : $h - 12) . ' PM' : $h . ' AM';
            $hourlyChartLabels[] = $amPm;
            $hourlyChartData[] = is_array($hourlyActiveRaw[$h]) ? count($hourlyActiveRaw[$h]) : 0;
        }

        // Calculate Aggregate Metrics
        $totalActiveUsersInPeriod = count($userDailyStats);
        $totalMinsAll = 0;
        $meetingStandardCount = 0;
        $belowStandardCount = 0;
        
        $usageHealth = [
            'Healthy' => 0,
            'Moderate' => 0,
            'Low Usage' => 0,
            'No Usage' => $totalUsersCount - $totalActiveUsersInPeriod
        ];

        $personWiseData = [];
        $departmentStats = []; // dept_id => [total_users, active_users]

        foreach ($targetUsers as $u) {
            $uid = $u['id'];
            $deptId = $u['department_id'] ?: 0;
            
            if (!isset($departmentStats[$deptId])) {
                $departmentStats[$deptId] = [
                    'name' => $u['department_name'] ?: 'Unknown',
                    'total_users' => 0,
                    'active_users' => 0
                ];
            }
            $departmentStats[$deptId]['total_users']++;

            $daysUsed = 0;
            $totalMins = 0;
            $standardMetDays = 0;
            $avgLoginSum = 0; 
            $avgLogoutSum = 0;
            
            if (isset($userDailyStats[$uid])) {
                $departmentStats[$deptId]['active_users']++;
                $daysUsed = count($userDailyStats[$uid]);
                
                foreach ($userDailyStats[$uid] as $date => $stats) {
                    $totalMins += $stats['total_mins'];
                    if ($stats['total_mins'] >= 240) { // 4 hours standard
                        $standardMetDays++;
                    }
                    
                    $loginTimeSec = strtotime(date('H:i:s', strtotime($stats['first_login']))) - strtotime('TODAY');
                    $logoutTimeSec = strtotime(date('H:i:s', strtotime($stats['last_logout']))) - strtotime('TODAY');
                    
                    $avgLoginSum += $loginTimeSec;
                    $avgLogoutSum += $logoutTimeSec;
                }
                
                $totalMinsAll += $totalMins;
            }

            $avgMinsPerDay = $daysUsed > 0 ? intdiv($totalMins, $daysUsed) : 0;
            $avgMinsPerPeriodDay = intdiv($totalMins, $workingDaysCount);

            if ($avgMinsPerPeriodDay >= 240) {
                $usageHealth['Healthy']++;
                $healthStatus = 'Healthy';
            } elseif ($avgMinsPerPeriodDay >= 120) {
                $usageHealth['Moderate']++;
                $healthStatus = 'Moderate';
            } elseif ($avgMinsPerPeriodDay > 0) {
                $usageHealth['Low Usage']++;
                $healthStatus = 'Low Usage';
            } else {
                $healthStatus = 'No Usage';
            }

            if ($avgMinsPerPeriodDay >= 240) {
                $meetingStandardCount++;
            } elseif ($avgMinsPerPeriodDay > 0) {
                $belowStandardCount++;
            }

            $avgLoginFormatted = '—';
            $avgLogoutFormatted = '—';
            if ($daysUsed > 0) {
                $avgLoginTime = (int)($avgLoginSum / $daysUsed);
                $avgLogoutTime = (int)($avgLogoutSum / $daysUsed);
                $avgLoginFormatted = date('h:i A', strtotime('TODAY') + $avgLoginTime);
                $avgLogoutFormatted = date('h:i A', strtotime('TODAY') + $avgLogoutTime);
            }

            $expectedDailyMins = 540; // 9 hours
            $idleTimePerDay = $avgMinsPerDay > 0 ? max(0, $expectedDailyMins - $avgMinsPerDay) : 0;
            $idlePercentage = $expectedDailyMins > 0 ? round(($idleTimePerDay / $expectedDailyMins) * 100) : 0;

            $lastActiveStr = '—';
            if ($u['last_login_ever']) {
                $diff = time() - strtotime($u['last_login_ever']);
                if ($diff < 3600) {
                    $lastActiveStr = max(1, floor($diff / 60)) . ' min ago';
                } elseif ($diff < 86400) {
                    $lastActiveStr = floor($diff / 3600) . ' hours ago';
                } else {
                    $lastActiveStr = floor($diff / 86400) . ' days ago';
                }
            }

            // Get initials for avatar
            $words = explode(" ", $u['full_name']);
            $initials = "";
            foreach ($words as $w) {
                if (mb_strlen($initials) >= 2) break;
                $initials .= mb_substr($w, 0, 1);
            }
            $initials = strtoupper($initials);

            $displayName = $u['full_name'] . (!empty($u['employee_id']) ? ' (' . $u['employee_id'] . ')' : '');
            
            $personWiseData[] = [
                'user_id' => $uid,
                'full_name' => $displayName,
                'initials' => $initials,
                'department' => $u['department_name'] ?: '—',
                'days_used' => $daysUsed,
                'working_days' => $workingDaysCount,
                'avg_login' => $avgLoginFormatted,
                'avg_logout' => $avgLogoutFormatted,
                'active_time_day' => $avgMinsPerDay,
                'idle_percentage' => $idlePercentage,
                'standard_met_days' => $standardMetDays,
                'usage_status' => $healthStatus,
                'last_active' => $lastActiveStr,
                'last_login_ever' => $u['last_login_ever']
            ];
        }

        usort($personWiseData, function($a, $b) {
            if ($a['days_used'] == $b['days_used']) {
                return strcmp($a['full_name'], $b['full_name']);
            }
            return $b['days_used'] - $a['days_used'];
        });

        $attentionRequired = [];
        foreach ($personWiseData as $p) {
            $issue = '';
            $status = '';
            if (!$p['last_login_ever']) {
                $issue = "Never logged in";
                $status = "Critical";
            } else {
                $diffDays = floor((time() - strtotime($p['last_login_ever'])) / 86400);
                if ($diffDays >= 3) {
                    $issue = "No login for $diffDays days";
                    $status = "Critical";
                } elseif ($p['usage_status'] === 'Low Usage') {
                    $activeH = intdiv($p['active_time_day'], 60);
                    $activeM = $p['active_time_day'] % 60;
                    $issue = "Avg. {$activeH}h {$activeM}m/day";
                    $status = "Low Usage";
                } elseif ($p['idle_percentage'] > 60) {
                    $issue = "Idle sessions {$p['idle_percentage']}%";
                    $status = "Review";
                }
            }
            if ($issue) {
                $attentionRequired[] = array_merge($p, ['issue' => $issue, 'attn_status' => $status]);
                if (count($attentionRequired) >= 5) break; 
            }
        }

        $avgProductiveMins = $totalActiveUsersInPeriod > 0 ? (int)($totalMinsAll / $totalActiveUsersInPeriod / $workingDaysCount) : 0;
        $adoptionScore = $totalUsersCount > 0 ? round(($totalActiveUsersInPeriod / $totalUsersCount) * 100) : 0;

        $deptAdoptionRates = [];
        foreach ($departmentStats as $stat) {
            if ($stat['name'] == 'Unknown') continue;
            $rate = $stat['total_users'] > 0 ? round(($stat['active_users'] / $stat['total_users']) * 100) : 0;
            $deptAdoptionRates[] = [
                'name' => $stat['name'],
                'rate' => $rate
            ];
        }
        usort($deptAdoptionRates, function($a, $b) { return $b['rate'] <=> $a['rate']; });

        // ── 4. Fetch Sales Team Performance Data ──────────────────────────────
        $activeTab = $_GET['tab'] ?? (($filterDeptId === 1) ? 'sales' : 'adoption');

        $salesUsersSql = "
            SELECT u.id, u.full_name, u.email, r.name as role_name, u.role_id,
                   dept.id as department_id, dept.name as department_name, 
                   des.name as designation_name, e.employee_id
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN employees e ON e.user_id = u.id
            LEFT JOIN designations des ON des.id = COALESCE(e.designation_id, u.designation_id)
            LEFT JOIN departments dept ON dept.id = COALESCE(e.department_id, des.department_id)
            WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('Inactive', 'Resigned', 'Terminated'))
              AND (u.role_id IN (5, 8, 9) OR dept.id = 1)
        ";
        $salesUsersParams = [];
        $salesUsersParamTypes = '';

        if ($filterDeptId !== null) {
            $salesUsersSql .= " AND dept.id = ?";
            $salesUsersParams[] = $filterDeptId;
            $salesUsersParamTypes .= 'i';
        }
        if ($filterUserId !== null) {
            $salesUsersSql .= " AND u.id = ?";
            $salesUsersParams[] = $filterUserId;
            $salesUsersParamTypes .= 'i';
        }
        $salesUsersSql .= " GROUP BY u.id, u.full_name, u.email, r.name, u.role_id, dept.id, dept.name, des.name, e.employee_id ORDER BY u.role_id ASC, u.full_name ASC";

        $stmtSalesUsers = $this->db->prepare($salesUsersSql);
        if (!empty($salesUsersParams)) {
            $stmtSalesUsers->bind_param($salesUsersParamTypes, ...$salesUsersParams);
        }
        $stmtSalesUsers->execute();
        $salesUsers = $stmtSalesUsers->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtSalesUsers->close();

        $salesTeamPerformance = [];
        $salesTotals = [
            'total_sales_users'     => count($salesUsers),
            'total_assigned_leads'  => 0,
            'total_followups'       => 0,
            'total_visited'         => 0,
            'total_hot'             => 0,
            'total_cold'            => 0,
            'total_no_followup'     => 0,
            'total_booked'          => 0,
            'total_land_share_value'=> 0.0
        ];

        if (!empty($salesUsers)) {
            $salesUserIds = array_column($salesUsers, 'id');
            $sIdPlaceholders = implode(',', array_fill(0, count($salesUserIds), '?'));
            $sTypes = str_repeat('i', count($salesUserIds));

            $this->db->query("SET SESSION group_concat_max_len = 1000000");

            // 1. Leads counts & status breakdown
            $sLeadsSql = "
                SELECT 
                    l.assigned_id,
                    COUNT(l.id) as assigned_leads_count,
                    GROUP_CONCAT(CONCAT('#', l.id, ' ', IFNULL(l.full_name, 'Unknown')) SEPARATOR ', ') as assigned_leads_names,
                    SUM(CASE WHEN l.lead_type = 'Visited' THEN 1 ELSE 0 END) as visited_count,
                    GROUP_CONCAT(CASE WHEN l.lead_type = 'Visited' THEN CONCAT('#', l.id, ' ', IFNULL(l.full_name, 'Unknown')) ELSE NULL END SEPARATOR ', ') as visited_names,
                    SUM(CASE WHEN l.lead_type = 'Hot' THEN 1 ELSE 0 END) as hot_count,
                    SUM(CASE WHEN l.lead_type = 'Cold' THEN 1 ELSE 0 END) as cold_count,
                    MAX(l.updated_at) as last_status_update
                FROM leads l
                WHERE l.assigned_id IN ($sIdPlaceholders)
                  AND (DATE(l.assigned_at) BETWEEN ? AND ? OR (l.assigned_at IS NULL AND DATE(l.created_at) BETWEEN ? AND ?))
                GROUP BY l.assigned_id
            ";
            $stmtSL = $this->db->prepare($sLeadsSql);
            $slParams = array_merge($salesUserIds, [$filterDateFrom, $filterDateTo, $filterDateFrom, $filterDateTo]);
            $stmtSL->bind_param($sTypes . 'ssss', ...$slParams);
            $stmtSL->execute();
            $sLeadsRes = $stmtSL->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtSL->close();
            $sLeadsMap = [];
            foreach ($sLeadsRes as $sl) {
                $sLeadsMap[$sl['assigned_id']] = $sl;
            }

            // 2. Follow-ups made in date range
            $sFuSql = "
                SELECT 
                    lfu.created_by,
                    COUNT(lfu.id) as total_followups
                FROM lead_follow_ups lfu
                WHERE lfu.created_by IN ($sIdPlaceholders)
                  AND (DATE(lfu.created_at) BETWEEN ? AND ? OR lfu.follow_up_date BETWEEN ? AND ?)
                GROUP BY lfu.created_by
            ";
            $stmtSFu = $this->db->prepare($sFuSql);
            $sfuParams = array_merge($salesUserIds, [$filterDateFrom, $filterDateTo, $filterDateFrom, $filterDateTo]);
            $stmtSFu->bind_param($sTypes . 'ssss', ...$sfuParams);
            $stmtSFu->execute();
            $sFuRes = $stmtSFu->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtSFu->close();
            $sFuMap = [];
            foreach ($sFuRes as $sfu) {
                $sFuMap[$sfu['created_by']] = (int)$sfu['total_followups'];
            }

            // 3. Contacted leads count (leads that have at least 1 follow up in date range)
            $sContactedSql = "
                SELECT l.assigned_id, 
                       COUNT(DISTINCT l.id) as contacted_leads_count,
                       GROUP_CONCAT(DISTINCT CONCAT('#', l.id, ' ', IFNULL(l.full_name, 'Unknown')) SEPARATOR ', ') as contacted_leads_names
                FROM leads l
                JOIN lead_follow_ups lfu ON lfu.lead_id = l.id
                WHERE l.assigned_id IN ($sIdPlaceholders)
                  AND (DATE(l.assigned_at) BETWEEN ? AND ? OR (l.assigned_at IS NULL AND DATE(l.created_at) BETWEEN ? AND ?))
                  AND (DATE(lfu.created_at) BETWEEN ? AND ? OR lfu.follow_up_date BETWEEN ? AND ?)
                GROUP BY l.assigned_id
            ";
            $stmtSCont = $this->db->prepare($sContactedSql);
            $sContParams = array_merge($salesUserIds, [$filterDateFrom, $filterDateTo, $filterDateFrom, $filterDateTo, $filterDateFrom, $filterDateTo, $filterDateFrom, $filterDateTo]);
            $stmtSCont->bind_param($sTypes . 'ssssssss', ...$sContParams);
            $stmtSCont->execute();
            $sContRes = $stmtSCont->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtSCont->close();
            $sContMap = [];
            foreach ($sContRes as $sc) {
                $sContMap[$sc['assigned_id']] = $sc;
            }

            // 4. Bookings in date range by lead_bookings.booking_date
            $sBookingsSql = "
                SELECT 
                    lb.sales_by_id,
                    COUNT(DISTINCT lb.id) as total_booked,
                    GROUP_CONCAT(DISTINCT CONCAT('#', l.id, ' ', IFNULL(l.full_name, 'Unknown')) SEPARATOR ', ') as booked_names,
                    COALESCE(SUM(f.land_share_value), 0) as total_land_share_value
                FROM lead_bookings lb
                JOIN flats f ON f.id = lb.flat_id
                LEFT JOIN leads l ON l.id = lb.lead_id
                WHERE lb.booking_date BETWEEN ? AND ?
                  AND lb.deleted_at IS NULL
                  AND lb.sales_by_id IN ($sIdPlaceholders)
                GROUP BY lb.sales_by_id
            ";
            $stmtSBook = $this->db->prepare($sBookingsSql);
            $sBookParams = array_merge([$filterDateFrom, $filterDateTo], $salesUserIds);
            $stmtSBook->bind_param('ss' . $sTypes, ...$sBookParams);
            $stmtSBook->execute();
            $sBookRes = $stmtSBook->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtSBook->close();
            $sBookMap = [];
            foreach ($sBookRes as $sb) {
                $sBookMap[$sb['sales_by_id']] = $sb;
            }

            // 5. Fetch Tipsoi attendance data for the period to calculate HR KPI
            require_once __DIR__ . '/../services/ExternalApiService.php';
            $extApi = new ExternalApiService($this->db);
            
            $oldTimezone = date_default_timezone_get();
            date_default_timezone_set('Asia/Dhaka');
            $fromTime = strtotime($filterDateFrom . ' 00:00:00') * 1000;
            $toTime = strtotime($filterDateTo . ' 23:59:59') * 1000;
            date_default_timezone_set($oldTimezone);
            
            $tipsoiData = $extApi->getAttendanceLogs($fromTime, $toTime) ?: [];
            $tipsoiStatsByOfficeId = [];
            foreach ($tipsoiData as $emp) {
                $officeId = trim((string)($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? ''));
                if (!$officeId) continue;
                $officeIdNorm = preg_match('/^\d+$/', $officeId) ? (string)((int)$officeId) : $officeId;
                
                $leaveCount = 0; $lateCount = 0;
                $shiftStartStr = $emp['shiftStartTime'] ?? '09:30 AM';
                
                foreach ($emp['attendance'] ?? [] as $ts => $dayAtt) {
                    if (!empty($dayAtt['isLeave'])) {
                        $leaveCount++;
                    } elseif (!empty($dayAtt['isPresent'])) {
                        $firstLog = trim($dayAtt['firstLoggedTime'] ?? '');
                        if ($firstLog && $firstLog !== '-') {
                            $checkInTime = strtotime($firstLog);
                            $shiftStartTime = strtotime($shiftStartStr);
                            if ($checkInTime && $shiftStartTime) {
                                if ($checkInTime > ($shiftStartTime + (10 * 60))) {
                                    $lateCount++;
                                }
                            }
                        }
                    }
                }
                $tipsoiStatsByOfficeId[$officeIdNorm] = ['leave_count' => $leaveCount, 'late_count' => $lateCount];
            }

            // 6. Assemble sales performance data
            foreach ($salesUsers as $su) {
                $uid = $su['id'];
                
                $empId = trim((string)($su['employee_id'] ?? ''));
                $empIdNorm = preg_match('/^\d+$/', $empId) ? (string)((int)$empId) : $empId;
                $tStat = $tipsoiStatsByOfficeId[$empIdNorm] ?? ['leave_count' => 0, 'late_count' => 0];

                $lData = $sLeadsMap[$uid] ?? [];
                $assignedCount = (int)($lData['assigned_leads_count'] ?? 0);
                $contactedCount = (int)($sContMap[$uid]['contacted_leads_count'] ?? 0);
                $noFollowUpCount = max(0, $assignedCount - $contactedCount);
                $bData = $sBookMap[$uid] ?? [];
                $bookedCount = (int)($bData['total_booked'] ?? 0);
                $landShareVal = (float)($bData['total_land_share_value'] ?? 0);
                $fuCount = (int)($sFuMap[$uid] ?? 0);
                $visCount = (int)($lData['visited_count'] ?? 0);
                $hotCount = (int)($lData['hot_count'] ?? 0);
                $coldCount = (int)($lData['cold_count'] ?? 0);
                $lastUpdate = $lData['last_status_update'] ?? null;

                $words = explode(" ", $su['full_name']);
                $initials = "";
                foreach ($words as $w) {
                    if (mb_strlen($initials) >= 2) break;
                    $initials .= mb_substr($w, 0, 1);
                }
                $initials = strtoupper($initials);

                $displayName = $su['full_name'] . (!empty($su['employee_id']) ? ' (' . $su['employee_id'] . ')' : '');

                $salesTeamPerformance[] = [
                    'user_id'                => $uid,
                    'full_name'              => $displayName,
                    'raw_name'               => $su['full_name'],
                    'employee_id'            => $su['employee_id'] ?? '',
                    'initials'               => $initials,
                    'role_name'              => $su['role_name'] ?: ($su['designation_name'] ?: 'Sales'),
                    'department_name'        => $su['department_name'] ?: 'Sales',
                    'assigned_leads'         => $assignedCount,
                    'assigned_leads_names'   => $lData['assigned_leads_names'] ?? '',
                    'total_followups'        => $fuCount,
                    'contacted_names'        => $sContMap[$uid]['contacted_leads_names'] ?? '',
                    'visited'                => $visCount,
                    'visited_names'          => $lData['visited_names'] ?? '',
                    'hot'                    => $hotCount,
                    'cold'                   => $coldCount,
                    'no_followup_leads'      => $noFollowUpCount,
                    'total_booked'           => $bookedCount,
                    'booked_names'           => $bData['booked_names'] ?? '',
                    'total_land_share_value' => $landShareVal,
                    'last_status_update'     => $lastUpdate,
                    'kpi'                    => $this->kpiService->calculateSalesKPI($uid, null, $tStat, $filterDateFrom, $filterDateTo)
                ];

                $salesTotals['total_assigned_leads'] += $assignedCount;
                $salesTotals['total_followups'] += $fuCount;
                $salesTotals['total_visited'] += $visCount;
                $salesTotals['total_hot'] += $hotCount;
                $salesTotals['total_cold'] += $coldCount;
                $salesTotals['total_no_followup'] += $noFollowUpCount;
                $salesTotals['total_booked'] += $bookedCount;
                $salesTotals['total_land_share_value'] += $landShareVal;
            }

            // Calculate Team Average KPI
            $teamKpi = [
                'revenue_generation' => 0, 'target_vs_achievement' => 0, 'unit_sold' => 0,
                'highest_visit' => 0, 'sales_cvr' => 0, 'leads_connected' => 0,
                'data_quality' => 0, 'hr' => 0, 'total_score' => 0
            ];
            if (count($salesTeamPerformance) > 0) {
                foreach ($salesTeamPerformance as $s) {
                    $teamKpi['revenue_generation'] += $s['kpi']['revenue_generation']['score'];
                    $teamKpi['target_vs_achievement'] += $s['kpi']['target_vs_achievement']['score'];
                    $teamKpi['unit_sold'] += $s['kpi']['unit_sold']['score'];
                    $teamKpi['highest_visit'] += $s['kpi']['highest_visit']['score'];
                    $teamKpi['sales_cvr'] += $s['kpi']['sales_cvr']['score'];
                    $teamKpi['leads_connected'] += $s['kpi']['leads_connected']['score'];
                    $teamKpi['data_quality'] += $s['kpi']['data_quality']['score'];
                    $teamKpi['hr'] += $s['kpi']['hr']['score'];
                    $teamKpi['total_score'] += $s['kpi']['total_score'];
                }
                $c = count($salesTeamPerformance);
                foreach ($teamKpi as $k => $v) {
                    $teamKpi[$k] = round($v / $c, 2);
                }
            }
            $salesTotals['team_kpi'] = $teamKpi;

            usort($salesTeamPerformance, function($a, $b) {
                if ($a['total_booked'] != $b['total_booked']) {
                    return $b['total_booked'] - $a['total_booked'];
                }
                return $b['assigned_leads'] - $a['assigned_leads'];
            });
        }

        // ── Export Trigger ────────────────────────────────────────────────────
        if (isset($_GET['export'])) {
            $exportType = $_GET['export'];
            $exportTarget = $_GET['export_target'] ?? ($activeTab === 'sales' ? 'sales' : 'adoption');
            if ($exportTarget === 'sales') {
                if ($exportType === 'excel') {
                    $this->exportSalesExcel($salesTeamPerformance, $filterDateFrom, $filterDateTo, $salesTotals);
                } elseif ($exportType === 'pdf') {
                    $this->exportSalesPdf($salesTeamPerformance, $filterDateFrom, $filterDateTo, $salesTotals);
                }
            } else {
                if ($exportType === 'excel') {
                    $this->exportExcel($personWiseData, $filterDateFrom, $filterDateTo);
                } elseif ($exportType === 'pdf') {
                    $this->exportPdf($personWiseData, $filterDateFrom, $filterDateTo);
                }
            }
        }

        $data = [
            "current_route"         => "user-activity-log",
            "page_title"            => "ERP Usage & Adoption",
            "departments"           => $departments,
            "allUsers"              => $allUsers,
            
            "activeTab"             => $activeTab,
            "filterDeptId"          => $filterDeptId,
            "filterUserId"          => $filterUserId,
            "filterDateFrom"        => $filterDateFrom,
            "filterDateTo"          => $filterDateTo,
            
            "totalUsers"            => $totalUsersCount,
            "activeToday"           => $totalActiveUsersInPeriod,
            "adoptionScore"         => $adoptionScore,
            "meetingStandard"       => $meetingStandardCount,
            "belowStandard"         => $belowStandardCount,
            "noUsageToday"          => $usageHealth['No Usage'],
            "avgProductiveMins"     => $avgProductiveMins,
            
            "hourlyLabels"          => json_encode($hourlyChartLabels),
            "hourlyData"            => json_encode($hourlyChartData),
            "usageHealthData"       => json_encode([
                $usageHealth['Healthy'],
                $usageHealth['Moderate'],
                $usageHealth['Low Usage'],
                $usageHealth['No Usage']
            ]),
            
            "deptAdoptionRates"     => $deptAdoptionRates,
            "attentionRequired"     => $attentionRequired,
            "personWiseData"        => $personWiseData,

            "salesTeamPerformance"  => $salesTeamPerformance,
            "salesTotals"           => $salesTotals
        ];

        $this->view("hrm/user-activity-log", $data);
    }

    public function employeesByDept()
    {
        header('Content-Type: application/json');

        $deptId = isset($_GET['dept_id']) && $_GET['dept_id'] !== ''
            ? (int)$_GET['dept_id'] : null;

        if ($deptId === null) {
            echo json_encode([]);
            exit;
        }

        $sql = "
            SELECT DISTINCT u.id, u.full_name, u.email, e.employee_id
            FROM users u
            LEFT JOIN designations des ON des.id = u.designation_id
            LEFT JOIN departments dept ON dept.id = des.department_id
            LEFT JOIN employees e  ON e.id = u.employee_id
            WHERE des.department_id = ?
            ORDER BY u.full_name ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $deptId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        echo json_encode($result);
        exit;
    }

    public function employeeSummary()
    {
        header('Content-Type: application/json');

        $userId = isset($_GET['user_id']) && $_GET['user_id'] !== ''
            ? (int)$_GET['user_id'] : null;

        if ($userId === null) {
            echo json_encode(['error' => 'user_id required']);
            exit;
        }

        $dateFrom = isset($_GET['date_from']) && $_GET['date_from'] !== ''
            ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) && $_GET['date_to'] !== ''
            ? $_GET['date_to'] : null;

        $sqlSummary = "
            SELECT
                u.full_name,
                u.email,
                COALESCE(dept.name, 'Unknown')  AS department_name,
                COALESCE(des.name,  '—')         AS designation_name,
                COUNT(ual.id)                    AS total_sessions,
                COALESCE(SUM(
                    TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time)
                ), 0)                            AS total_minutes
            FROM user_activity_logs ual
            JOIN users u ON u.id = ual.user_id
            LEFT JOIN designations des  ON des.id  = u.designation_id
            LEFT JOIN departments  dept ON dept.id = des.department_id
            WHERE ual.user_id = ?
        ";

        $params     = [$userId];
        $paramTypes = 'i';

        if ($dateFrom) {
            $sqlSummary .= " AND DATE(ual.login_time) >= ?";
            $params[]    = $dateFrom;
            $paramTypes .= 's';
        }
        if ($dateTo) {
            $sqlSummary .= " AND DATE(ual.login_time) <= ?";
            $params[]    = $dateTo;
            $paramTypes .= 's';
        }

        $sqlSummary .= " GROUP BY u.id, u.full_name, u.email, dept.name, des.name";

        $stmt = $this->db->prepare($sqlSummary);
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$summary) {
            echo json_encode(['error' => 'No data found']);
            exit;
        }

        $sqlDaily = "
            SELECT
                DATE(ual.login_time)             AS log_date,
                COUNT(ual.id)                    AS sessions,
                MIN(ual.login_time)              AS first_login,
                MAX(ual.last_active_time)        AS last_active,
                COALESCE(SUM(
                    TIMESTAMPDIFF(MINUTE, ual.login_time, ual.last_active_time)
                ), 0)                            AS active_minutes
            FROM user_activity_logs ual
            WHERE ual.user_id = ?
        ";

        $params2     = [$userId];
        $paramTypes2 = 'i';

        if ($dateFrom) {
            $sqlDaily   .= " AND DATE(ual.login_time) >= ?";
            $params2[]   = $dateFrom;
            $paramTypes2 .= 's';
        }
        if ($dateTo) {
            $sqlDaily   .= " AND DATE(ual.login_time) <= ?";
            $params2[]   = $dateTo;
            $paramTypes2 .= 's';
        }

        $sqlDaily .= " GROUP BY DATE(ual.login_time) ORDER BY log_date DESC";

        $stmt2 = $this->db->prepare($sqlDaily);
        $stmt2->bind_param($paramTypes2, ...$params2);
        $stmt2->execute();
        $daily = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();

        $totalMins = (int)$summary['total_minutes'];
        $sessions  = (int)$summary['total_sessions'];
        
        $startStr = $dateFrom ? $dateFrom . ' 00:00:00' : '1970-01-01 00:00:00';
        $endStr = $dateTo ? $dateTo . ' 23:59:59' : '2099-12-31 23:59:59';
        
        // 1. Follow ups
        $qFollowUps = "SELECT COUNT(*) as cnt FROM lead_follow_ups WHERE created_by = ? AND created_at BETWEEN ? AND ?";
        $stmt_f = $this->db->prepare($qFollowUps);
        if($stmt_f) {
            $stmt_f->bind_param('iss', $userId, $startStr, $endStr);
            $stmt_f->execute();
            $followUps = $stmt_f->get_result()->fetch_assoc()['cnt'];
            $stmt_f->close();
        } else { $followUps = 0; }

        // 2. Bookings
        $qBookings = "SELECT COUNT(*) as cnt FROM lead_bookings WHERE created_by = ? AND created_at BETWEEN ? AND ?";
        $stmt_b = $this->db->prepare($qBookings);
        if($stmt_b) {
            $stmt_b->bind_param('iss', $userId, $startStr, $endStr);
            $stmt_b->execute();
            $bookings = $stmt_b->get_result()->fetch_assoc()['cnt'];
            $stmt_b->close();
        } else { $bookings = 0; }

        // 3. New Leads
        $qLeads = "SELECT COUNT(*) as cnt FROM leads WHERE created_by = ? AND created_at BETWEEN ? AND ?";
        $stmt_l = $this->db->prepare($qLeads);
        if($stmt_l) {
            $stmt_l->bind_param('iss', $userId, $startStr, $endStr);
            $stmt_l->execute();
            $leads = $stmt_l->get_result()->fetch_assoc()['cnt'];
            $stmt_l->close();
        } else { $leads = 0; }
        
        // 4. Fund Requisitions
        $qFund = "SELECT COUNT(*) as cnt FROM fund_requisitions WHERE created_by = ? AND created_at BETWEEN ? AND ?";
        $stmt_r = $this->db->prepare($qFund);
        if($stmt_r) {
            $stmt_r->bind_param('iss', $userId, $startStr, $endStr);
            $stmt_r->execute();
            $fundReqs = $stmt_r->get_result()->fetch_assoc()['cnt'];
            $stmt_r->close();
        } else { $fundReqs = 0; }

        echo json_encode([
            'employee' => [
                'full_name'        => $summary['full_name'],
                'email'            => $summary['email'],
                'department_name'  => $summary['department_name'],
                'designation_name' => $summary['designation_name'],
            ],
            'activities' => [
                'new_leads' => $leads,
                'follow_ups' => $followUps,
                'bookings' => $bookings,
                'fund_requisitions' => $fundReqs
            ],
            'total_sessions' => $sessions,
            'total_minutes'  => $totalMins,
            'total_hours'    => intdiv($totalMins, 60),
            'total_mins_rem' => $totalMins % 60,
            'avg_minutes'    => $sessions > 0 ? intdiv($totalMins, $sessions) : 0,
            'daily'          => $daily,
        ]);
        exit;
    }

    private function exportExcel($personWiseData, $dateFrom, $dateTo)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['SL', 'Employee Name', 'Department', 'Days Used', 'Avg Login', 'Avg Logout', 'Avg Active Time/Day', 'Standard Met', 'Status', 'Last Active'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        $rowNum = 2;
        $serial = 1;
        foreach ($personWiseData as $p) {
            $activeH = intdiv($p['active_time_day'], 60);
            $activeM = $p['active_time_day'] % 60;
            $durationStr = $activeH > 0 ? "{$activeH}h {$activeM}m" : "{$activeM}m";
            $lastActiveDate = $p['last_login_ever'] ? date('d M Y, h:i A', strtotime($p['last_login_ever'])) : '-';

            $sheet->setCellValue('A' . $rowNum, $serial++);
            $sheet->setCellValue('B' . $rowNum, $p['full_name']);
            $sheet->setCellValue('C' . $rowNum, $p['department']);
            $sheet->setCellValue('D' . $rowNum, $p['days_used'] . '/' . $p['working_days']);
            $sheet->setCellValue('E' . $rowNum, $p['avg_login']);
            $sheet->setCellValue('F' . $rowNum, $p['avg_logout']);
            $sheet->setCellValue('G' . $rowNum, $durationStr);
            $sheet->setCellValue('H' . $rowNum, $p['standard_met_days'] . ' days');
            $sheet->setCellValue('I' . $rowNum, $p['usage_status']);
            $sheet->setCellValue('J' . $rowNum, $lastActiveDate);
            $rowNum++;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="ERP_Adoption_Report_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportPdf($personWiseData, $dateFrom, $dateTo)
    {
        $dompdf = new \Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
        
        $html = '<html><head><style>
            body { font-family: sans-serif; font-size: 11px; color: #333; }
            .header { text-align: center; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 10px; }
            th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
            th { background-color: #1F4E79; color: white; }
        </style></head><body>
            <div class="header">
                <h2>ERP Usage & Adoption Report</h2>
                <p>Period: ' . $dateFrom . ' to ' . $dateTo . '</p>
            </div>
            <table>
                <thead><tr>
                    <th>SL</th><th>Employee</th><th>Department</th><th>Days Used</th>
                    <th>Avg Login</th><th>Avg Logout</th><th>Active Time/Day</th><th>Status</th><th>Last Active</th>
                </tr></thead><tbody>';

        $serial = 1;
        foreach ($personWiseData as $p) {
            $activeH = intdiv($p['active_time_day'], 60);
            $activeM = $p['active_time_day'] % 60;
            $durationStr = $activeH > 0 ? "{$activeH}h {$activeM}m" : "{$activeM}m";
            $lastActiveDate = $p['last_login_ever'] ? date('d M Y, h:i A', strtotime($p['last_login_ever'])) : '-';
            
            $html .= '<tr>
                <td>' . $serial++ . '</td>
                <td>' . htmlspecialchars($p['full_name']) . '</td>
                <td>' . htmlspecialchars($p['department']) . '</td>
                <td>' . $p['days_used'] . '/' . $p['working_days'] . '</td>
                <td>' . $p['avg_login'] . '</td>
                <td>' . $p['avg_logout'] . '</td>
                <td>' . $durationStr . '</td>
                <td>' . $p['usage_status'] . '</td>
                <td>' . $lastActiveDate . '</td>
            </tr>';
        }
        $html .= '</tbody></table></body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("ERP_Adoption_Report_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
        exit;
    }

    private function exportSalesExcel($salesData, $dateFrom, $dateTo, $totals)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Sales Team Performance');

        // Title styling
        $sheet->setCellValue('A1', 'TPHL - Sales Team Performance & Lead Activity Report');
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('111827'));
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'Period: ' . $dateFrom . ' to ' . $dateTo . ' | Generated: ' . date('d M Y, h:i A'));
        $sheet->mergeCells('A2:L2');
        $sheet->getStyle('A2')->getFont()->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('4B5563'));
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E3A8A']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ];

        $headers = [
            'SL', 'Employee Name', 'Role', 'Assigned Leads', 
            'Follow-ups Done', 'Visited', 'Hot', 'Cold', 
            'No Follow-up (Untouched)', 'Total Booked', 'Land Share Value (BDT)', 'Last Status Update'
        ];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '4', $header);
            $col++;
        }
        $sheet->getStyle('A4:L4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(28);

        $rowNum = 5;
        $serial = 1;
        foreach ($salesData as $s) {
            $lastUp = $s['last_status_update'] ? date('d M Y, h:i A', strtotime($s['last_status_update'])) : '—';

            $sheet->setCellValue('A' . $rowNum, $serial++);
            $sheet->setCellValue('B' . $rowNum, $s['full_name']);
            $sheet->setCellValue('C' . $rowNum, $s['role_name']);
            $sheet->setCellValue('D' . $rowNum, $s['assigned_leads']);
            $sheet->setCellValue('E' . $rowNum, $s['total_followups']);
            $sheet->setCellValue('F' . $rowNum, $s['visited']);
            $sheet->setCellValue('G' . $rowNum, $s['hot']);
            $sheet->setCellValue('H' . $rowNum, $s['cold']);
            $sheet->setCellValue('I' . $rowNum, $s['no_followup_leads']);
            $sheet->setCellValue('J' . $rowNum, $s['total_booked']);
            $sheet->setCellValue('K' . $rowNum, $s['total_land_share_value']);
            $sheet->setCellValue('L' . $rowNum, $lastUp);

            $sheet->getStyle('D' . $rowNum . ':J' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('K' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('K' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $rowNum++;
        }

        // Summary row
        $totalStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '111827']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'borders' => [
                'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['rgb' => '111827']],
                'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE, 'color' => ['rgb' => '111827']],
            ]
        ];
        $sheet->setCellValue('A' . $rowNum, 'TOTAL');
        $sheet->mergeCells('A' . $rowNum . ':C' . $rowNum);
        $sheet->getStyle('A' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setCellValue('D' . $rowNum, $totals['total_assigned_leads']);
        $sheet->setCellValue('E' . $rowNum, $totals['total_followups']);
        $sheet->setCellValue('F' . $rowNum, $totals['total_visited']);
        $sheet->setCellValue('G' . $rowNum, $totals['total_hot']);
        $sheet->setCellValue('H' . $rowNum, $totals['total_cold']);
        $sheet->setCellValue('I' . $rowNum, $totals['total_no_followup']);
        $sheet->setCellValue('J' . $rowNum, $totals['total_booked']);
        $sheet->setCellValue('K' . $rowNum, $totals['total_land_share_value']);
        $sheet->setCellValue('L' . $rowNum, '—');
        $sheet->getStyle('D' . $rowNum . ':J' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('K' . $rowNum)->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('K' . $rowNum)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle('A' . $rowNum . ':L' . $rowNum)->applyFromArray($totalStyle);

        foreach (range('A', 'L') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Sales_Team_Report_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    private function exportSalesPdf($salesData, $dateFrom, $dateTo, $totals)
    {
        $dompdf = new \Dompdf\Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #1f2937; margin: 15px; }
            .header { text-align: center; margin-bottom: 15px; border-bottom: 2px solid #1E3A8A; padding-bottom: 10px; }
            .title { font-size: 16px; font-weight: bold; color: #1E3A8A; margin: 0; }
            .subtitle { font-size: 10px; color: #6b7280; margin-top: 4px; }
            .summary-cards { margin-bottom: 15px; width: 100%; }
            .summary-cards td { background: #f8fafc; border: 1px solid #e2e8f0; padding: 6px 10px; text-align: center; border-radius: 4px; }
            .card-label { font-size: 8px; color: #64748b; text-transform: uppercase; font-weight: bold; }
            .card-val { font-size: 13px; font-weight: bold; color: #0f172a; margin-top: 2px; }
            table.report-table { width: 100%; border-collapse: collapse; margin-top: 5px; }
            table.report-table th, table.report-table td { border: 1px solid #cbd5e1; padding: 5px 6px; text-align: left; }
            table.report-table th { background-color: #1E3A8A; color: #ffffff; font-size: 9px; font-weight: bold; text-align: center; }
            table.report-table tr:nth-child(even) { background-color: #f8fafc; }
            .num-cell { text-align: center; }
            .val-cell { text-align: right; font-weight: bold; }
            .total-row { background-color: #e2e8f0 !important; font-weight: bold; }
            .badge-hot { color: #dc2626; font-weight: bold; }
            .badge-vis { color: #16a34a; font-weight: bold; }
            .badge-cold { color: #0284c7; font-weight: bold; }
            .badge-un { color: #ea580c; font-weight: bold; }
        </style></head><body>
            <div class="header">
                <div class="title">TPHL - Sales Team Performance & Lead Activity Report</div>
                <div class="subtitle">Period: ' . htmlspecialchars($dateFrom) . ' to ' . htmlspecialchars($dateTo) . ' | Generated on: ' . date('d M Y, h:i A') . '</div>
            </div>

            <table class="summary-cards">
                <tr>
                    <td><div class="card-label">Assigned Leads</div><div class="card-val">' . number_format($totals['total_assigned_leads']) . '</div></td>
                    <td><div class="card-label">Follow-ups Done</div><div class="card-val">' . number_format($totals['total_followups']) . '</div></td>
                    <td><div class="card-label">Visited</div><div class="card-val" style="color:#16a34a;">' . number_format($totals['total_visited']) . '</div></td>
                    <td><div class="card-label">Hot Leads</div><div class="card-val" style="color:#dc2626;">' . number_format($totals['total_hot']) . '</div></td>
                    <td><div class="card-label">No Follow-up</div><div class="card-val" style="color:#ea580c;">' . number_format($totals['total_no_followup']) . '</div></td>
                    <td><div class="card-label">Total Booked</div><div class="card-val" style="color:#1e40af;">' . number_format($totals['total_booked']) . '</div></td>
                    <td><div class="card-label">Land Share Value</div><div class="card-val" style="color:#047857;">Tk ' . number_format($totals['total_land_share_value'], 2) . '</div></td>
                </tr>
            </table>

            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 25px;">SL</th>
                        <th>Employee</th>
                        <th>Role</th>
                        <th>Assigned</th>
                        <th>Follow-ups</th>
                        <th>Visited</th>
                        <th>Hot</th>
                        <th>Cold</th>
                        <th>No Contact</th>
                        <th>Booked</th>
                        <th>Land Share Value (Tk)</th>
                        <th>Last Update</th>
                    </tr>
                </thead>
                <tbody>';

        $serial = 1;
        foreach ($salesData as $s) {
            $lastUp = $s['last_status_update'] ? date('d M Y', strtotime($s['last_status_update'])) : '—';
            $html .= '<tr>
                <td class="num-cell">' . $serial++ . '</td>
                <td><strong>' . htmlspecialchars($s['full_name']) . '</strong></td>
                <td>' . htmlspecialchars($s['role_name']) . '</td>
                <td class="num-cell">' . $s['assigned_leads'] . '</td>
                <td class="num-cell">' . $s['total_followups'] . '</td>
                <td class="num-cell badge-vis">' . $s['visited'] . '</td>
                <td class="num-cell badge-hot">' . $s['hot'] . '</td>
                <td class="num-cell badge-cold">' . $s['cold'] . '</td>
                <td class="num-cell badge-un">' . $s['no_followup_leads'] . '</td>
                <td class="num-cell" style="font-weight:bold; color:#1e40af;">' . $s['total_booked'] . '</td>
                <td class="val-cell">' . number_format($s['total_land_share_value'], 2) . '</td>
                <td style="font-size: 8px;">' . $lastUp . '</td>
            </tr>';
        }

        $html .= '<tr class="total-row">
            <td colspan="3" style="text-align:center;">TOTAL</td>
            <td class="num-cell">' . number_format($totals['total_assigned_leads']) . '</td>
            <td class="num-cell">' . number_format($totals['total_followups']) . '</td>
            <td class="num-cell badge-vis">' . number_format($totals['total_visited']) . '</td>
            <td class="num-cell badge-hot">' . number_format($totals['total_hot']) . '</td>
            <td class="num-cell badge-cold">' . number_format($totals['total_cold']) . '</td>
            <td class="num-cell badge-un">' . number_format($totals['total_no_followup']) . '</td>
            <td class="num-cell" style="color:#1e40af;">' . number_format($totals['total_booked']) . '</td>
            <td class="val-cell" style="color:#047857;">' . number_format($totals['total_land_share_value'], 2) . '</td>
            <td>—</td>
        </tr>';

        $html .= '</tbody></table></body></html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("Sales_Team_Report_" . date('Ymd_His') . ".pdf", ["Attachment" => true]);
        exit;
    }
}
