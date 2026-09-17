<?php

class UserActivityLogController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Only role_id 1 (Admin) and 3 (Super Admin / Management) can access
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3])) {
            header("Location: " . $GLOBALS['base_url'] . "/home");
            exit;
        }

        // ── Default Date Range (Current Month) ──────────────────────────────
        $defaultFrom = date('Y-m-01');
        $defaultTo = date('Y-m-d');

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

        // ── Export Trigger ────────────────────────────────────────────────────
        if (isset($_GET['export'])) {
            $exportType = $_GET['export'];
            if ($exportType === 'excel') {
                $this->exportExcel($personWiseData, $filterDateFrom, $filterDateTo);
            } elseif ($exportType === 'pdf') {
                $this->exportPdf($personWiseData, $filterDateFrom, $filterDateTo);
            }
        }

        $data = [
            "current_route"    => "user-activity-log",
            "page_title"       => "ERP Usage & Adoption",
            "departments"      => $departments,
            "allUsers"         => $allUsers,
            
            "filterDeptId"     => $filterDeptId,
            "filterUserId"     => $filterUserId,
            "filterDateFrom"   => $filterDateFrom,
            "filterDateTo"     => $filterDateTo,
            
            "totalUsers"       => $totalUsersCount,
            "activeToday"      => $totalActiveUsersInPeriod,
            "adoptionScore"    => $adoptionScore,
            "meetingStandard"  => $meetingStandardCount,
            "belowStandard"    => $belowStandardCount,
            "noUsageToday"     => $usageHealth['No Usage'],
            "avgProductiveMins"=> $avgProductiveMins,
            
            "hourlyLabels"     => json_encode($hourlyChartLabels),
            "hourlyData"       => json_encode($hourlyChartData),
            "usageHealthData"  => json_encode([
                $usageHealth['Healthy'],
                $usageHealth['Moderate'],
                $usageHealth['Low Usage'],
                $usageHealth['No Usage']
            ]),
            
            "deptAdoptionRates"=> $deptAdoptionRates,
            "attentionRequired"=> $attentionRequired,
            "personWiseData"   => $personWiseData
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
}
