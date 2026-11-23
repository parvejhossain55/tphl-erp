<?php

class TeamDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $taskModel;
    protected $followUpModel;
    protected $bookingModel;
    protected $userModel;
    protected $teamModel;
    protected $salesTargetModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->userModel = new User($this->db);
        $this->teamModel = new Team($this->db);
        $this->salesTargetModel = new SalesTarget($this->db);
        $this->taskModel = new Task($this->db);
        $this->followUpModel = new FollowUp($this->db);
        $this->bookingModel = new BookingLead($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }

        $loggedUserId    = (int)$_SESSION['user']['id'];
        $roleId          = (int)($_SESSION['user']['role_id'] ?? 0);
        $sessionTeamId   = !empty($_SESSION['user']['team_id']) ? (int)$_SESSION['user']['team_id'] : 0;
        $isTeamLeader    = !empty($_SESSION['user']['is_team_leader']) && (int)$_SESSION['user']['is_team_leader'] === 1;
        $isSalesCoLeader = !empty($_SESSION['user']['is_sales_co_leader']) && (int)$_SESSION['user']['is_sales_co_leader'] === 1;

        // Ensure sessionTeamId from employees table if not present in session
        if (empty($sessionTeamId)) {
            $empCheck = $this->db->query("SELECT team_id, is_team_leader, is_sales_co_leader FROM employees WHERE user_id = {$loggedUserId} AND team_id > 0 LIMIT 1");
            if ($empCheck && $empRow = $empCheck->fetch_assoc()) {
                $sessionTeamId = (int)$empRow['team_id'];
                if (!empty($empRow['is_team_leader'])) {
                    $isTeamLeader = true;
                }
                if (!empty($empRow['is_sales_co_leader'])) {
                    $isSalesCoLeader = true;
                }
            }
        }

        // Team switching permission:
        // Sales Team Head / Head of Sales (Role 9), SEAL CEO (35), Chief Officer (33), Management (1, 3, 32, 34, 38) -> all teams
        // Sales Manager (new role via isSalesManager()) -> can switch ONLY among their assigned teams
        // Sales Team Leader (Role 5), Sales Executive (Role 8), and normal team leaders -> locked to own team
        $isSalesManagerUser = function_exists('isSalesManager') && isSalesManager();
        $managedTeamIds = $isSalesManagerUser ? getManagedTeamIds($this->db, $loggedUserId) : [];
        $isGlobalTeamViewer = in_array($roleId, [1, 3, 9, 32, 33, 34, 35, 38]) && !$isSalesManagerUser;
        $canSwitchTeam = $isGlobalTeamViewer || ($isSalesManagerUser && count($managedTeamIds) > 0);
        $isManagement  = $isGlobalTeamViewer;

        // Access check
        $canAccess = $canSwitchTeam || $isSalesManagerUser || in_array($roleId, [5, 8]) || $isTeamLeader || $isSalesCoLeader || $sessionTeamId > 0;
        if (!$canAccess) {
            header("Location: " . $GLOBALS['base_url'] . "/my-dashboard");
            exit;
        }

        // Fetch all active teams (filtered to managed teams for Sales Manager)
        $allTeams = $this->teamModel->getAll();
        $requestedManagerId = ($isGlobalTeamViewer && !empty($_GET['sales_manager_id'])) ? (int)$_GET['sales_manager_id'] : 0;
        $salesManagers = $isGlobalTeamViewer ? getSalesManagersWithTeams($this->db) : [];
        if ($isSalesManagerUser && !$isGlobalTeamViewer) {
            $allTeams = array_values(array_filter($allTeams, function ($t) use ($managedTeamIds) {
                return in_array((int)($t['id'] ?? 0), $managedTeamIds, true);
            }));
        }

        // Resolve active Team ID(s)
        $requestedTeamRaw = $_GET['team_id'] ?? null;
        $requestedTeamId  = ($requestedTeamRaw !== null && $requestedTeamRaw !== '' && $requestedTeamRaw !== 'all') ? (int)$requestedTeamRaw : 0;
        $isAllTeamsMode   = false;
        $activeTeamIds    = [];

        if ($isGlobalTeamViewer) {
            if ($requestedManagerId > 0) {
                $mgrTeamIds = getManagedTeamIds($this->db, $requestedManagerId);
                $allTeams = array_values(array_filter($allTeams, function ($t) use ($mgrTeamIds) {
                    return in_array((int)($t['id'] ?? 0), $mgrTeamIds, true);
                }));
                if ($requestedTeamId > 0 && in_array($requestedTeamId, $mgrTeamIds, true)) {
                    $teamId = $requestedTeamId;
                    $activeTeamIds = [$teamId];
                } else {
                    $isAllTeamsMode = true;
                    $teamId = 0;
                    $activeTeamIds = !empty($mgrTeamIds) ? $mgrTeamIds : [-1];
                }
            } elseif ($requestedTeamId > 0) {
                $teamId = $requestedTeamId;
                $activeTeamIds = [$teamId];
            } else {
                // Default for Head of Sales & Global Viewers: All Teams
                $isAllTeamsMode = true;
                $teamId = 0;
                $activeTeamIds = array_values(array_unique(array_filter(array_map(fn($t) => (int)($t['id'] ?? 0), $allTeams))));
            }
        } elseif ($isSalesManagerUser) {
            if ($requestedTeamId > 0 && in_array($requestedTeamId, $managedTeamIds, true)) {
                $teamId = $requestedTeamId;
                $activeTeamIds = [$teamId];
            } else {
                // Default for Sales Manager: All Assigned Teams combined
                $isAllTeamsMode = true;
                $teamId = 0;
                $activeTeamIds = $managedTeamIds;
            }
        } else {
            // Sales team leaders (5), team leaders, etc. are strictly locked to their own team
            if ($sessionTeamId > 0) {
                $teamId = $sessionTeamId;
            } else {
                $teamId = !empty($allTeams[0]['id']) ? (int)$allTeams[0]['id'] : 1;
            }
            $activeTeamIds = [$teamId];
            $allTeams = array_values(array_filter($allTeams, fn($t) => (int)($t['id'] ?? 0) === $teamId));
        }

        $showAllTeamsBtn = ($isGlobalTeamViewer || $isSalesManagerUser);
        $teamScopeParam  = $isAllTeamsMode ? $activeTeamIds : $teamId;

        // Fetch Team Details
        $team = null;
        if ($isAllTeamsMode) {
            $teamName = $isSalesManagerUser
                ? 'All Assigned Teams (' . count($activeTeamIds) . ')'
                : 'All Sales Teams (' . count($activeTeamIds) . ')';
        } else {
            $teamStmt = $this->db->prepare("SELECT * FROM teams WHERE id = ? LIMIT 1");
            if ($teamStmt) {
                $teamStmt->bind_param("i", $teamId);
                $teamStmt->execute();
                $team = $teamStmt->get_result()->fetch_assoc();
                $teamStmt->close();
            }
            $teamName = $team['name'] ?? ('Team #' . $teamId);
        }

        // Fetch active team members
        $teamMembers = $this->leadModel->getTeamMemberUsers($teamScopeParam);

        // Determine Team Leader
        $teamLeader = null;
        if ($isAllTeamsMode) {
            $teamLeader = [
                'full_name' => $isSalesManagerUser ? ($_SESSION['user']['full_name'] ?? 'Sales Manager') : 'Head of Sales / All Leaders',
                'role_name' => $isSalesManagerUser ? 'Sales Manager' : 'Overall Sales',
            ];
        } else {
            foreach ($teamMembers as $m) {
                if (!empty($m['is_team_leader']) && (int)$m['is_team_leader'] === 1) {
                    $teamLeader = $m;
                    break;
                }
            }
            if (!$teamLeader) {
                // Look for managerial / leadership role
                foreach ($teamMembers as $m) {
                    if (in_array((int)($m['role_id'] ?? 0), [35, 33, 34, 9, 5])) {
                        $teamLeader = $m;
                        break;
                    }
                }
            }
            if (!$teamLeader && !empty($teamMembers[0])) {
                $teamLeader = $teamMembers[0];
            }
        }

        // Selected Member filter (optional executive drill-down)
        $selectedMemberId = isset($_GET['member_id']) && $_GET['member_id'] !== '' 
            ? (int)$_GET['member_id'] 
            : (isset($_GET['assigned_user']) && $_GET['assigned_user'] !== '' ? (int)$_GET['assigned_user'] : null);

        $selectedMember = null;
        if ($selectedMemberId > 0) {
            foreach ($teamMembers as $m) {
                if ((int)$m['user_id'] === $selectedMemberId) {
                    $selectedMember = $m;
                    break;
                }
            }
            if (!$selectedMember) {
                // Fetch user info directly if not found in list
                $uRow = $this->db->query("SELECT id as user_id, full_name, role_id FROM users WHERE id = {$selectedMemberId} LIMIT 1")->fetch_assoc();
                if ($uRow) {
                    $selectedMember = $uRow;
                }
            }
        }

        // Preset and Date Handling (matching my-dashboard)
        $preset = $_GET['preset'] ?? $_GET['period'] ?? null;
        $getFilterType = $_GET['filter_type'] ?? null;
        $getFromDate = !empty($_GET['from_date']) ? trim($_GET['from_date']) : null;
        $getToDate = !empty($_GET['to_date']) ? trim($_GET['to_date']) : null;

        $monthsList = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Standard reporting cycle: 26th of previous month to 25th of current month
        $curDay = (int)date('j');
        $curYear = (int)date('Y');
        $curMonth = (int)date('n');
        if ($curDay >= 26) {
            $cycleMonth = $curMonth + 1;
            $cycleYear = $curYear;
            if ($cycleMonth > 12) { $cycleMonth = 1; $cycleYear++; }
        } else {
            $cycleMonth = $curMonth;
            $cycleYear = $curYear;
        }
        $prevMonth = $cycleMonth - 1;
        $prevYear = $cycleYear;
        if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
        $defaultCycleStart = sprintf('%04d-%02d-26', $prevYear, $prevMonth);
        $defaultCycleEnd   = sprintf('%04d-%02d-25', $cycleYear, $cycleMonth);

        $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $cycleYear;
        $selectedStartMonth = isset($_GET['start_month']) ? (int)$_GET['start_month'] : $cycleMonth;
        $selectedEndMonth = isset($_GET['end_month']) ? (int)$_GET['end_month'] : $cycleMonth;
        $filterType = $getFilterType ?? 'monthly';

        $hasFilter = true;
        $activePreset = 'this_month';
        $fromDate = $defaultCycleStart;
        $toDate = $defaultCycleEnd;
        $titlePeriod = 'This Month (' . date('d M', strtotime($fromDate)) . ' - ' . date('d M, Y', strtotime($toDate)) . ')';

        if ($preset === 'today' || $preset === 'daily') {
            $hasFilter = true;
            $activePreset = 'today';
            $fromDate = date('Y-m-d');
            $toDate = date('Y-m-d');
            $titlePeriod = 'Today (' . date('d M, Y') . ')';
        } elseif ($preset === 'yesterday') {
            $hasFilter = true;
            $activePreset = 'yesterday';
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $fromDate = $yesterday;
            $toDate = $yesterday;
            $titlePeriod = 'Yesterday (' . date('d M, Y', strtotime($yesterday)) . ')';
        } elseif ($preset === 'this_month' || ($preset === 'monthly' && empty($getFilterType) && empty($getFromDate))) {
            $hasFilter = true;
            $activePreset = 'this_month';
            $fromDate = $defaultCycleStart;
            $toDate = $defaultCycleEnd;
            $selectedYear = $cycleYear;
            $selectedStartMonth = $cycleMonth;
            $selectedEndMonth = $cycleMonth;
            $titlePeriod = 'This Month (' . date('d M', strtotime($fromDate)) . ' - ' . date('d M, Y', strtotime($toDate)) . ')';
        } elseif ($preset === 'all_time' || $preset === 'all_over') {
            $hasFilter = false;
            $activePreset = 'all_time';
            $fromDate = null;
            $toDate = null;
            $titlePeriod = 'All Time';
        } elseif (!empty($getFromDate) && !empty($getToDate)) {
            $hasFilter = true;
            $fromDate = $getFromDate;
            $toDate = $getToDate;
            if ($fromDate === date('Y-m-d') && $toDate === date('Y-m-d')) {
                $activePreset = 'today';
                $titlePeriod = 'Today (' . date('d M, Y') . ')';
            } elseif ($fromDate === date('Y-m-d', strtotime('-1 day')) && $toDate === date('Y-m-d', strtotime('-1 day'))) {
                $activePreset = 'yesterday';
                $titlePeriod = 'Yesterday (' . date('d M, Y', strtotime($fromDate)) . ')';
            } elseif ($fromDate === $defaultCycleStart && $toDate === $defaultCycleEnd) {
                $activePreset = 'this_month';
                $selectedYear = $cycleYear;
                $selectedStartMonth = $cycleMonth;
                $selectedEndMonth = $cycleMonth;
                $titlePeriod = 'This Month (' . date('d M', strtotime($fromDate)) . ' - ' . date('d M, Y', strtotime($toDate)) . ')';
            } else {
                $activePreset = 'custom';
                $titlePeriod = date('d M, Y', strtotime($fromDate)) . ' - ' . date('d M, Y', strtotime($toDate));
            }
        } elseif (isset($_GET['filter_type']) || (isset($_GET['start_month']) && isset($_GET['end_month']))) {
            $hasFilter = true;
            if ($filterType === 'yearly') {
                $activePreset = 'yearly';
                $fromDate = sprintf('%04d-01-01', $selectedYear);
                $toDate = sprintf('%04d-12-31', $selectedYear);
                $titlePeriod = 'Year ' . $selectedYear;
            } else {
                $activePreset = 'monthly';
                $startMonth = $selectedStartMonth;
                $endMonth = $selectedEndMonth;
                $cycleStart = (new SalesTarget($this->db))->getCycleDateRange($selectedYear, $startMonth);
                $cycleEnd = (new SalesTarget($this->db))->getCycleDateRange($selectedYear, $endMonth);
                $fromDate = date('Y-m-d', strtotime($cycleStart['start']));
                $toDate = date('Y-m-d', strtotime($cycleEnd['end']));
                if ($startMonth === $endMonth) {
                    $titlePeriod = ($monthsList[$startMonth] ?? date('F')) . ' ' . $selectedYear . ' (' . date('d M', strtotime($fromDate)) . ' - ' . date('d M, Y', strtotime($toDate)) . ')';
                } else {
                    $titlePeriod = ($monthsList[$startMonth] ?? '') . ' - ' . ($monthsList[$endMonth] ?? '') . ' ' . $selectedYear;
                }
            }
        } else {
            $hasFilter = true;
            $activePreset = 'this_month';
            $fromDate = $defaultCycleStart;
            $toDate = $defaultCycleEnd;
            $selectedYear = $cycleYear;
            $selectedStartMonth = $cycleMonth;
            $selectedEndMonth = $cycleMonth;
            $titlePeriod = 'This Month (' . date('d M', strtotime($fromDate)) . ' - ' . date('d M, Y', strtotime($toDate)) . ')';
        }

        // Fetch Metrics: Individual member vs Team Aggregate
        $memberUserIds = array_values(array_filter(array_map('intval', array_column($teamMembers, 'user_id'))));

        if ($selectedMemberId > 0) {
            // Individual Executive Mode
            if ($hasFilter) {
                $stats = $this->salesTargetModel->getRangeStats($selectedMemberId, $selectedYear, $selectedStartMonth, $selectedEndMonth, $fromDate, $toDate);
                $matrixMetrics = $this->leadModel->getExecutiveDashboardMetrics($selectedMemberId, $selectedYear, $selectedStartMonth, $selectedEndMonth, true, $fromDate, $toDate);
            } else {
                $stats = $this->salesTargetModel->getRangeStats($selectedMemberId, null, null, null, null, null);
                $matrixMetrics = $this->leadModel->getExecutiveDashboardMetrics($selectedMemberId, null, null, null, false, null, null);
            }
        } else {
            // Whole Team / All Teams Aggregate Mode
            if ($hasFilter) {
                $stats = $this->salesTargetModel->getTeamRangeStats($teamScopeParam, $memberUserIds, $selectedYear, $selectedStartMonth, $selectedEndMonth, $fromDate, $toDate);
                $matrixMetrics = $this->leadModel->getTeamDashboardMetrics($teamScopeParam, null, $selectedYear, $selectedStartMonth, $selectedEndMonth, true, $fromDate, $toDate);
            } else {
                $stats = $this->salesTargetModel->getTeamRangeStats($teamScopeParam, $memberUserIds, null, null, null, null, null);
                $matrixMetrics = $this->leadModel->getTeamDashboardMetrics($teamScopeParam, null, null, null, null, false, null, null);
            }
        }

        $monthlySiteVisitTarget   = $stats['site_visit_target'] ?? 0;
        $monthlyBookingTarget     = $stats['booking_target'] ?? 0;
        $monthlyBookingValueTarget= $stats['booking_value_target'] ?? 0;
        $monthlyVisitComplete     = $stats['visit_completed'] ?? 0;
        $monthlyBookingComplete   = $stats['booking_completed'] ?? 0;

        $visitAchievement = $monthlySiteVisitTarget > 0 ? round(($monthlyVisitComplete / $monthlySiteVisitTarget) * 100, 1) : 0;
        $bookingAchievement = $monthlyBookingTarget > 0 ? round(($monthlyBookingComplete / $monthlyBookingTarget) * 100, 1) : 0;

        $dealsClosed = $stats['deals_closed'] ?? 0;
        $dealsWon    = $stats['deals_won'] ?? 0;
        $dealsLost   = $stats['deals_lost'] ?? 0;

        // Team member sales & leads performance breakdown (deducting refunds)
        $memberSalesPerformance = $this->leadModel->getTeamMemberSalesPerformance($teamScopeParam, $fromDate, $toDate, $hasFilter);

        // Build lead query string for card links
        $leadQueryDate = '';
        if (!empty($fromDate) && !empty($toDate)) {
            $leadQueryDate = "from_date={$fromDate}&to_date={$toDate}&date_type=lead_date";
        }

        $viewData = array_merge([
            "current_route"             => "team-dashboard",
            "page_title"                => "Team Overview Dashboard",
            "isGlobalTeamViewer"        => $isGlobalTeamViewer,
            "salesManagers"             => $salesManagers,
            "selectedSalesManagerId"    => $requestedManagerId,
            "teamId"                    => $teamId,
            "team"                      => $team,
            "teamName"                  => $teamName,
            "teamLeader"                => $teamLeader,
            "teamMembers"               => $teamMembers,
            "allTeams"                  => $allTeams,
            "isManagement"              => $isManagement,
            "canSwitchTeam"             => $canSwitchTeam,
            "isAllTeamsMode"            => $isAllTeamsMode,
            "showAllTeamsBtn"           => $showAllTeamsBtn,
            "selectedMemberId"          => $selectedMemberId,
            "selectedMember"            => $selectedMember,
            "monthlySiteVisitTarget"    => $monthlySiteVisitTarget,
            "monthlyBookingComplete"    => $monthlyBookingComplete,
            "monthlyBookingTarget"      => $monthlyBookingTarget,
            "monthlyBookingValueTarget" => $monthlyBookingValueTarget,
            "visitAchievement"          => $visitAchievement,
            "bookingAchievement"        => $bookingAchievement,
            "dealsClosed"               => $dealsClosed,
            "dealsWon"                  => $dealsWon,
            "dealsLost"                 => $dealsLost,
            "monthlyVisitComplete"      => $monthlyVisitComplete,
            "selectedYear"              => $selectedYear,
            "selectedStartMonth"        => $selectedStartMonth,
            "selectedEndMonth"          => $selectedEndMonth,
            "filterType"                => $filterType,
            "hasFilter"                 => $hasFilter,
            "activePreset"              => $activePreset,
            "fromDate"                  => $fromDate,
            "toDate"                    => $toDate,
            "titlePeriod"               => $titlePeriod,
            "cycleBkStart"              => $defaultCycleStart,
            "cycleBkEnd"                => $defaultCycleEnd,
            "leadQueryDate"             => $leadQueryDate,
            "monthsList"                => $monthsList,
            "matrixMetrics"             => $matrixMetrics,
            "memberSalesPerformance"    => $memberSalesPerformance,
        ], $matrixMetrics);

        $this->view("sales-developments/teams/team-dashboard", $viewData);
    }
}

