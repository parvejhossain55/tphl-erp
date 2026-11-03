<?php

class UserDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $taskModel;
    protected $followUpModel;
    protected $bookingModel;
    protected $userModel;
    protected $salesTargetModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->userModel = new User($this->db);
        $this->salesTargetModel = new SalesTarget($this->db);
        $this->taskModel = new Task($this->db);
        $this->followUpModel = new FollowUp($this->db);
        $this->bookingModel = new BookingLead($this->db);
    }

    public function todayLead()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            echo "User not logged in";
            return;
        }
        $bookingCount = $this->bookingModel->getBookingCountByLead();
        // $todayFollowUp = (new FollowUp($this->db))->getTodayFollowUpCountByLead();


        $todayFollowUp = (new FollowUp($this->db))->getTodayFollowUpCountByLead();

        $loggedUserId = (int) $_SESSION['user']['id'];
        $userRoleId = $_SESSION['user']['role_id'] ?? null;
        $userTeamId = $_SESSION['user']['team_id'] ?? null;
        
        $assignedUsers = [];
        if (in_array((int)$userRoleId, [5, 8])) {
            if ($userTeamId) {
                $assignedUsers = $this->userModel->getUsersByTeamAndRole($userTeamId);
            } else {
                $assignedUsers = $this->userModel->getAllRoleUser();
            }
        } else {
            $assignedUsers = $this->userModel->getAllRoleUser();
        }

        $filterProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $filterCategoryId = isset($_GET['project_category_id']) && $_GET['project_category_id'] !== '' ? (int)$_GET['project_category_id'] : null;
        $filterCountry = !empty($_GET['filter_country']) ? $_GET['filter_country'] : null;
        if (is_array($filterCountry)) {
            $filterCountry = array_values(array_filter($filterCountry, fn($val) => trim($val) !== ''));
            if (empty($filterCountry)) $filterCountry = null;
        } elseif (is_string($filterCountry) && trim($filterCountry) === '') {
            $filterCountry = null;
        }

        $assignedHours = $_GET['assigned_hours'] ?? null;

        // only assigned today leads for logged user
        $todayLeads = $this->leadModel->getTodayLeadsByUser($loggedUserId, $filterProjectId, $filterCategoryId, $filterCountry, $assignedHours);

        // Fetch duplicate leads for the logged in user
        $userDuplicates = $this->leadModel->getDuplicateLeadsForUser($loggedUserId);
        $phoneMap = [];
        $emailMap = [];
        foreach ($userDuplicates as $row) {
            $phone1 = preg_replace('/[^0-9]/', '', $row['phone_number'] ?? '');
            $phone2 = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');
            $email = trim(strtolower($row['email'] ?? ''));
            
            $dupInfo = [
                'id' => $row['id'],
                'full_name' => $row['full_name'],
                'created_at' => $row['created_at'] ? date('d F, Y', strtotime($row['created_at'])) : '',
                'project_name' => $row['project_name'] ?? 'N/A',
                'last_lead_action' => $row['last_lead_action'] ?? 'N/A',
                'lead_type' => $row['lead_type'] ?? 'N/A'
            ];

            if ($phone1 !== '') {
                if (!isset($phoneMap[$phone1])) {
                    $phoneMap[$phone1] = [];
                }
                $exists = false;
                foreach ($phoneMap[$phone1] as $item) {
                    if ($item['id'] == $row['id']) { $exists = true; break; }
                }
                if (!$exists) {
                    $phoneMap[$phone1][] = $dupInfo;
                }
            }
            if ($phone2 !== '' && $phone2 !== $phone1) {
                if (!isset($phoneMap[$phone2])) {
                    $phoneMap[$phone2] = [];
                }
                $exists = false;
                foreach ($phoneMap[$phone2] as $item) {
                    if ($item['id'] == $row['id']) { $exists = true; break; }
                }
                if (!$exists) {
                    $phoneMap[$phone2][] = $dupInfo;
                }
            }
            if ($email !== '') {
                if (!isset($emailMap[$email])) {
                    $emailMap[$email] = [];
                }
                $exists = false;
                foreach ($emailMap[$email] as $item) {
                    if ($item['id'] == $row['id']) { $exists = true; break; }
                }
                if (!$exists) {
                    $emailMap[$email][] = $dupInfo;
                }
            }
        }

        $myAllLeads = $this->leadModel->getAllMyLeads($loggedUserId);
        $followUpCounts = (new FollowUp($this->db))->getFollowUpCountByLead();
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        $projectCategoryModel = new ProjectCategory($this->db);
        $projectCategories = $projectCategoryModel->getAll();

        $countries = $this->leadModel->getDistinctCountries();

        $leadIds = !empty($todayLeads) ? array_column($todayLeads, 'id') : [];
        $followUpHistories = [];
        $leadBookings = [];
        if (!empty($leadIds)) {
            $idsStr = implode(',', array_map('intval', $leadIds));
            $fuQuery = $this->db->query("SELECT fu.*, l.full_name, p.name AS project_name, f.flat_name, u_fu.full_name AS follow_up_by_name 
                                         FROM lead_follow_ups fu 
                                         LEFT JOIN leads l ON fu.lead_id = l.id 
                                         LEFT JOIN projects p ON fu.project_id = p.id 
                                         LEFT JOIN flats f ON fu.flat_id = f.id 
                                         LEFT JOIN users u_fu ON fu.created_by = u_fu.id 
                                         WHERE fu.lead_id IN ($idsStr) ORDER BY fu.follow_up_date DESC, fu.id DESC");
            if ($fuQuery) {
                while($row = $fuQuery->fetch_assoc()) {
                    $followUpHistories[$row['lead_id']][] = $row;
                }
            }

            $bkQuery = $this->db->query("SELECT * FROM lead_bookings WHERE lead_id IN ($idsStr) AND deleted_at IS NULL");
            if ($bkQuery) {
                while($row = $bkQuery->fetch_assoc()) {
                    $leadBookings[$row['lead_id']][] = $row;
                }
            }
        }

        $filterCard = $_GET['filter_card'] ?? null;
        $deleteRequests = !empty($leadIds) ? $this->leadModel->getPendingDeleteRequestsByLeadIds($leadIds) : [];
        $transferableUsers = $this->userModel->getSalesTransferableUsers();

        $this->view("sales-executive/today-leads", [
            "current_route" => "today-leads",
            "page_title"    => "Leads Today Assign",
            "todayLeads"    => $todayLeads,
            "followUpCounts" => $followUpCounts,
            "followUpHistories" => $followUpHistories,
            "leadBookings"  => $leadBookings,
            "filterCard"    => $filterCard,
            "deleteRequests" => $deleteRequests,
            "bookingCount"    => $bookingCount,
            "todayFollowUp"    => $todayFollowUp,
            "phoneMap"        => $phoneMap,
            "emailMap"        => $emailMap,
            "projects"        => $projects,
            "projectCategories" => $projectCategories,
            "countries"       => $countries,
            "selectedCountry" => $filterCountry,
            "assignedUsers"   => $assignedUsers,
            "teamMembers"     => $assignedUsers,
            "transferableUsers" => $transferableUsers,
        ]);
    }


    public function dashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            echo "User not logged in";
            return;
        }

        $loggedUserId = (int) $_SESSION['user']['id'];

        // only assigned today leads for logged user
        $todayLeads = $this->leadModel->getTodayLeadsByUser($loggedUserId);
        $todayLeadsCount = count($todayLeads);
        $pendingTasks = $this->taskModel->getPendingTaskCountByUser($loggedUserId);

        $myTotalShareSold = $this->bookingModel->getMyTotalFlatSales($loggedUserId);
        $getAllLeads = $this->leadModel->getAllLeadsByUser($loggedUserId);
        $getAllLeadsCount = count($getAllLeads);
        $bookingCount   = $this->bookingModel->getBookingCountByLead();
        // $todayFollowUp  = (new FollowUp($this->db))->getTodayFollowUpCountByLead();
        $todayFollowUp  = $this->followUpModel->getTodayFollowUpCountLoginUser($loggedUserId);
        $followUpOverdue  = $this->followUpModel->getOverdueFollowUpCountLoginUser($loggedUserId);
        $missingFollowUpCount = $this->followUpModel->getMissingFollowUpCount($loggedUserId);

        $todayFollowUpComplete = $this->followUpModel->getTodayFollowUpCompleteCount($loggedUserId);

        // Filter preset and date handling
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
                $startMonth = 1;
                $endMonth = 12;
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
            // Default when loading /my-dashboard without parameters: 26th of previous month to 25th of current month
            $hasFilter = true;
            $activePreset = 'this_month';
            $fromDate = $defaultCycleStart;
            $toDate = $defaultCycleEnd;
            $selectedYear = $cycleYear;
            $selectedStartMonth = $cycleMonth;
            $selectedEndMonth = $cycleMonth;
            $titlePeriod = 'This Month (' . date('d M', strtotime($fromDate)) . ' - ' . date('d M, Y', strtotime($toDate)) . ')';
        }

        // Fetch range stats & dashboard metrics
        if ($hasFilter) {
            $stats = $this->salesTargetModel->getRangeStats($loggedUserId, $selectedYear, $selectedStartMonth, $selectedEndMonth, $fromDate, $toDate);
            $matrixMetrics = $this->leadModel->getExecutiveDashboardMetrics($loggedUserId, $selectedYear, $selectedStartMonth, $selectedEndMonth, true, $fromDate, $toDate);
        } else {
            $stats = $this->salesTargetModel->getRangeStats($loggedUserId, null, null, null, null, null);
            $matrixMetrics = $this->leadModel->getExecutiveDashboardMetrics($loggedUserId, null, null, null, false, null, null);
        }

        $monthlySiteVisitTarget = $stats['site_visit_target'];
        $monthlyBookingTarget = $stats['booking_target'];
        $monthlyBookingValueTarget = $stats['booking_value_target'];
        $monthlyVisitComplete = $stats['visit_completed'];
        $monthlyBookingComplete = $stats['booking_completed'];

        // Calculate achievement %
        $visitAchievement = $monthlySiteVisitTarget > 0 ? round(($monthlyVisitComplete / $monthlySiteVisitTarget) * 100, 2) : 0;
        $bookingAchievement = $monthlyBookingTarget > 0 ? round(($monthlyBookingComplete / $monthlyBookingTarget) * 100, 2) : 0;

        // Deals Closed & Won
        $dealsClosed = $stats['deals_closed'];
        $dealsClosedAll = $this->salesTargetModel->getDealsClosedAllTime($loggedUserId);
        $dealsWon = $stats['deals_won'];
        $dealsWin = $dealsWon;
        $dealsWinAll = $this->salesTargetModel->getDealsClosedAllTime($loggedUserId);
        $dealsLost = $stats['deals_lost'];
        $dealsLostAll = $this->salesTargetModel->getAllDealsLost($loggedUserId);

        $viewData = array_merge([
            "current_route"   => "my-dashboard",
            "page_title"      => "my-dashboard",
            "todayLeads"      => $todayLeads,
            "bookingCount"    => $bookingCount,
            "todayFollowUp"   => $todayFollowUp,
            "todayFollowUpComplete"    => $todayFollowUpComplete,
            "todayLeadsCount"   => $todayLeadsCount,
            "pendingTasks"   => $pendingTasks,
            "myTotalShareSold"   => $myTotalShareSold,
            "followUpOverdue"   => $followUpOverdue,
            "missingFollowUpCount" => $missingFollowUpCount,
            "getAllLeadsCount"    => $getAllLeadsCount,
            "monthlySiteVisitTarget"    => $monthlySiteVisitTarget,
            "monthlyBookingComplete"    => $monthlyBookingComplete,
            "monthlyBookingTarget"    => $monthlyBookingTarget,
            "visitAchievement"    => $visitAchievement,
            "bookingAchievement"    => $bookingAchievement,
            "dealsClosed"    => $dealsClosed,
            "dealsClosedAll"    => $dealsClosedAll,
            "dealsWon"       => $dealsWon,
            "dealsWin"       => $dealsWin,
            "dealsWinAll"    => $dealsWinAll,
            "dealsLost"      => $dealsLost,
            "dealsLostAll"   => $dealsLostAll,
            "monthlyVisitComplete"    => $monthlyVisitComplete,
            "selectedYear"            => $selectedYear,
            "selectedStartMonth"      => $selectedStartMonth,
            "selectedEndMonth"        => $selectedEndMonth,
            "filterType"              => $filterType,
            "hasFilter"               => $hasFilter,
            "activePreset"            => $activePreset,
            "fromDate"                => $fromDate,
            "toDate"                  => $toDate,
            "titlePeriod"             => $titlePeriod,
            "cycleBkStart"            => $defaultCycleStart,
            "cycleBkEnd"              => $defaultCycleEnd,
            "matrixMetrics"           => $matrixMetrics,
        ], $matrixMetrics);

        $this->view("sales-executive/my-dashboard", $viewData);
    }
    // today follow up kpi
    public function updateFollowUpStatusNote()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user']['id'] ?? null;

        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $followUpId  = (int) ($_POST['id'] ?? 0);
        $leadId      = (int) ($_POST['lead_id'] ?? 0);
        $status      = $_POST['status'] ?? null;
        $nextAction  = $_POST['next_action'] ?? null;
        $note        = trim($_POST['discussion_note'] ?? '');

        if (!$followUpId && !$leadId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing follow-up or lead ID']);
            exit;
        }

        // If no followUpId but we have leadId, create a new follow-up first
        if (!$followUpId && $leadId) {
            $createStmt = $this->db->prepare("
                INSERT INTO lead_follow_ups 
                (lead_id, follow_up_date, status, discussion_note, next_action, created_by)
                VALUES (?, CURDATE(), ?, ?, ?, ?)
            ");

            if (!$createStmt) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create follow-up: ' . $this->db->error]);
                exit;
            }

            $defaultStatus = $status ?? 'pending';
            $createStmt->bind_param("isssi", $leadId, $defaultStatus, $note, $nextAction, $userId);

            if (!$createStmt->execute()) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create follow-up: ' . $createStmt->error]);
                exit;
            }

            $followUpId = $this->db->insert_id;
            $createStmt->close();

            // Auto-set first_contacted_at on lead if not set yet
            $touchStmt = $this->db->prepare("UPDATE leads SET first_contacted_at = COALESCE(first_contacted_at, NOW()) WHERE id = ?");
            if ($touchStmt) {
                $touchStmt->bind_param("i", $leadId);
                $touchStmt->execute();
                $touchStmt->close();
            }
        }

        //  Dynamic update
        $fields = [];
        $params = [];
        $types  = "";

        if ($status !== null) {
            $fields[] = "status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if ($note !== '') {
            $fields[] = "discussion_note = ?";
            $params[] = $note;
            $types .= "s";
        }

        if ($nextAction !== null) {
            $fields[] = "next_action = ?";
            $params[] = $nextAction;
            $types .= "s";

            //  AUTO SET follow_up_type
            if ($nextAction === 'site visit') {
                $fields[] = "follow_up_type = 'visit'";
            }
        }

        if (empty($fields)) {
            echo json_encode(['error' => 'Nothing to update']);
            exit;
        }

        $sql = "UPDATE lead_follow_ups SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = ?";
        $params[] = $followUpId;
        $types .= "i";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            // ==============================
            // AUTO UPDATE LEADS TABLE STATUS
            // ==============================
            $leadAction = null;

            // Get follow-up type for the current follow-up
            $getFollowUp = $this->db->prepare("SELECT follow_up_type FROM lead_follow_ups WHERE id = ?");
            $getFollowUp->bind_param("i", $followUpId);
            $getFollowUp->execute();
            $followUpResult = $getFollowUp->get_result();
            $followUpData = $followUpResult->fetch_assoc();
            $followUpType = $followUpData['follow_up_type'] ?? null;
            $getFollowUp->close();

            // Determine lead action based on follow-up type and status
            if ($followUpType === 'visit' && $status === 'completed') {
                $leadAction = 'visited';
            } elseif ($status === 'pending') {
                $leadAction = 'follow up';
            } elseif ($status === 'canceled' || $status === 'canceld') {
                $leadAction = 'cancel';
            }

            // Update leads table if action determined
            if ($leadAction !== null) {
                $updateLead = $this->db->prepare("
                    UPDATE leads 
                    SET last_lead_action = ?
                    WHERE id = ?
                ");

                if ($updateLead) {
                    $updateLead->bind_param(
                        "si",
                        $leadAction,
                        $leadId
                    );
                    $updateLead->execute();
                    $updateLead->close();
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Follow-up updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Update failed']);
        }
    }

    public function updateCallStatus()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $id = (int)($_POST['lead_id'] ?? 0);
        $status = $_POST['call_status'] ?? null;

        if ($id && $status) {
            $stmt = $this->db->prepare("UPDATE leads SET call_status = ?, first_contacted_at = COALESCE(first_contacted_at, NOW()) WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();

            $logStmt = $this->db->prepare("INSERT INTO lead_call_logs (lead_id, user_id, call_status) VALUES (?, ?, ?)");
            if ($logStmt) {
                $logStmt->bind_param("iis", $id, $userId, $status);
                $logStmt->execute();
                $logStmt->close();
            }
        }
    }

    public function updateCommunicationStatus()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $id = (int)($_POST['lead_id'] ?? 0);
        $status = $_POST['communication_type'] ?? null;

        if ($id && $status) {
            $stmt = $this->db->prepare("UPDATE leads SET communication_type = ?, first_contacted_at = COALESCE(first_contacted_at, NOW()) WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $stmt->close();

            $logStmt = $this->db->prepare("INSERT INTO lead_call_logs (lead_id, user_id, communication_type) VALUES (?, ?, ?)");
            if ($logStmt) {
                $logStmt->bind_param("iis", $id, $userId, $status);
                $logStmt->execute();
                $logStmt->close();
            }
        }
    }

    public function updateLeadStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
        }

        if (!isset($_POST['lead_id'], $_POST['lead_status'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing data']);
            exit;
        }

        $id = (int) $_POST['lead_id'];
        $status = $_POST['lead_status'];
        $dropReason = !empty($_POST['drop_reason']) ? trim($_POST['drop_reason']) : null;
        $dropNote   = !empty($_POST['drop_note']) ? trim($_POST['drop_note']) : null;

        if ($dropReason !== null || $dropNote !== null) {
            $stmt = $this->db->prepare("UPDATE leads SET lead_type = ?, drop_reason = ?, drop_note = ? WHERE id = ?");
            $stmt->bind_param("sssi", $status, $dropReason, $dropNote, $id);
        } else {
            $stmt = $this->db->prepare("UPDATE leads SET lead_type = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $id);
        }

        if ($stmt && $stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Lead status updated'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Update failed']);
        }

        if ($stmt) $stmt->close();
    }


    public function updateCallNote()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $id = (int)($_POST['lead_id'] ?? 0);
        $note = trim($_POST['call_note'] ?? '');

        header('Content-Type: application/json');
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing lead ID']);
            exit;
        }

        $stmt = $this->db->prepare("UPDATE leads SET call_note = ?, first_contacted_at = COALESCE(first_contacted_at, NOW()) WHERE id = ?");
        $stmt->bind_param("si", $note, $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            if ($note !== '') {
                $logStmt = $this->db->prepare("INSERT INTO lead_call_logs (lead_id, user_id, call_note) VALUES (?, ?, ?)");
                if ($logStmt) {
                    $logStmt->bind_param("iis", $id, $userId, $note);
                    $logStmt->execute();
                    $logStmt->close();
                }
            }
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $this->db->error]);
        }
        exit;
    }

    public function todayFollowUp()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user']['id'] ?? null;

        $filterProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $filterCategoryId = isset($_GET['project_category_id']) && $_GET['project_category_id'] !== '' ? (int)$_GET['project_category_id'] : null;
        $filterCountry = !empty($_GET['filter_country']) ? $_GET['filter_country'] : null;
        if (is_array($filterCountry)) {
            $filterCountry = array_values(array_filter($filterCountry, fn($val) => trim($val) !== ''));
            if (empty($filterCountry)) $filterCountry = null;
        } elseif (is_string($filterCountry) && trim($filterCountry) === '') {
            $filterCountry = null;
        }

        $countries = $this->leadModel->getDistinctCountries();
        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        $projectCategoryModel = new ProjectCategory($this->db);
        $projectCategories = $projectCategoryModel->getAll();

        $todayFollowUpLeads = [];
        if ($userId) {
            $todayFollowUpLeads = $this->followUpModel
                ->getTodayFollowUpLoginUser((int)$userId, $filterProjectId, $filterCategoryId, $filterCountry);
        }

        $leadIds = !empty($todayFollowUpLeads) ? array_column($todayFollowUpLeads, 'lead_id') : [];
        $followUpCounts = !empty($leadIds) ? $this->followUpModel->getFollowUpCountByLeadIds($leadIds) : [];
        $followUpHistories = [];
        if (!empty($leadIds)) {
            $idsStr = implode(',', array_map('intval', $leadIds));
            $fuQuery = $this->db->query("SELECT fu.*, l.full_name, p.name AS project_name, f.flat_name, u_fu.full_name AS follow_up_by_name 
                                         FROM lead_follow_ups fu 
                                         LEFT JOIN leads l ON fu.lead_id = l.id 
                                         LEFT JOIN projects p ON fu.project_id = p.id 
                                         LEFT JOIN flats f ON fu.flat_id = f.id 
                                         LEFT JOIN users u_fu ON fu.created_by = u_fu.id 
                                         WHERE fu.lead_id IN ($idsStr) ORDER BY fu.follow_up_date DESC, fu.id DESC");
            if ($fuQuery) {
                while($row = $fuQuery->fetch_assoc()) {
                    $followUpHistories[$row['lead_id']][] = $row;
                }
            }
        }

        $this->view("sales-executive/today-followup", [
            "current_route"       => "today-follow-up",
            "page_title"          => "Today Follow Up",
            "todayFollowUpLeads"  => $todayFollowUpLeads,
            "countries"           => $countries,
            "selectedCountry"     => $filterCountry,
            "projects"            => $projects,
            "projectCategories"   => $projectCategories,
            "followUpCounts"      => $followUpCounts,
            "followUpHistories"   => $followUpHistories,
        ]);
    }

    public function myOnboardProject()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $loggedInUser   = $_SESSION['user'] ?? [];
        $loggedInUserId = (int)($loggedInUser['id'] ?? 0);
        $loggedInRoleId = (int)($loggedInUser['role_id'] ?? 0);

        // Allow Admins, Heads, and Managers to view a specific user's onboarding projects if requested
        $targetUserId = (!empty($_GET['user_id']) || !empty($_GET['assigned_user']))
            ? (int)($_GET['user_id'] ?? $_GET['assigned_user'])
            : $loggedInUserId;

        if (!in_array($loggedInRoleId, [1, 3, 5, 9, 32, 33, 34, 35]) && $targetUserId !== $loggedInUserId) {
            $targetUserId = $loggedInUserId;
        }

        $targetUserName = null;
        if ($targetUserId !== $loggedInUserId && $targetUserId > 0) {
            $targetUser = $this->userModel->getById($targetUserId);
            $targetUserName = $targetUser['full_name'] ?? null;
        }

        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;

        $myOnboardProjects = $this->bookingModel
            ->getAllMyOnboardProject($targetUserId, $year, $month);

        // Compute summary metrics for header cards
        $totalUnitsSold = count($myOnboardProjects);
        $clientKeys = [];
        $totalPriceValue = 0;
        $totalBookingMoney = 0;
        $totalPaidAmount = 0;

        foreach ($myOnboardProjects as $p) {
            $clientKey = !empty($p['lead_id']) 
                ? 'lead_' . $p['lead_id'] 
                : (!empty($p['phone']) ? 'phone_' . $p['phone'] : 'name_' . ($p['full_name'] ?? uniqid()));
            $clientKeys[$clientKey] = true;

            $totalPriceValue   += (float)($p['price_value'] ?? 0);
            $totalBookingMoney += (float)($p['booking_money'] ?? 0);
            $totalPaidAmount   += (float)($p['amount'] ?? 0);
        }

        $totalClients = count($clientKeys);

        $this->view('sales-executive/my-onboard-projects', [
            'projects'          => $myOnboardProjects,
            'totalClients'      => $totalClients,
            'totalUnitsSold'    => $totalUnitsSold,
            'totalPriceValue'   => $totalPriceValue,
            'totalBookingMoney' => $totalBookingMoney,
            'totalPaidAmount'   => $totalPaidAmount,
            'targetUserId'      => $targetUserId,
            'targetUserName'    => $targetUserName,
            'selectedYear'      => $year,
            'selectedMonth'     => $month,
            'current_page'      => 'my-onboarding-projects',
        ]);
    }

    public function myAssignedLeads()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId     = $_SESSION['user']['id']     ?? 0;
        $userTeamId = $_SESSION['user']['team_id'] ?? null;
        $year                 = $_GET['year']          ?? null;
        $month                = $_GET['month']         ?? null;
        $lastAction           = $_GET['last_action']   ?? null;
        $lead_status          = $_GET['lead_status']   ?? null;
        $search               = trim($_GET['search'] ?? '');
        $followup_date        = $_GET['followup_date'] ?? null;
        $filterCard           = $_GET['filter_card']   ?? null;
        $assignedHours        = $_GET['assigned_hours'] ?? null;
        $from_date            = $_GET['from_date'] ?? null;
        $to_date              = $_GET['to_date'] ?? null;
        $date_type            = $_GET['date_type'] ?? 'lead_date';
        $uncontacted          = isset($_GET['uncontacted']) ? true : false;

        // Parameter normalization across lead-lists & my-all-assigned-leads
        if ($filterCard === 'total_booked') {
            $filterCard = 'booked';
        }
        if ($date_type === 'created_at') {
            $date_type = 'lead_date';
        } elseif ($date_type === 'assigned_at') {
            $date_type = 'assigned_date';
        }

        $onlyMissingFollowup = isset($_GET['filter']) && $_GET['filter'] === 'missing_followup';

        $filterProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $filterCategoryId = isset($_GET['project_category_id']) && $_GET['project_category_id'] !== '' ? (int)$_GET['project_category_id'] : null;

        $filterCountry = !empty($_GET['filter_country']) ? $_GET['filter_country'] : null;
        if (is_array($filterCountry)) {
            $filterCountry = array_values(array_filter($filterCountry, fn($val) => trim($val) !== ''));
            if (empty($filterCountry)) $filterCountry = null;
        } elseif (is_string($filterCountry) && trim($filterCountry) === '') {
            $filterCountry = null;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 25;
        if ($limit < 10 && $limit !== -1) $limit = 25;
        
        $sqlLimit = ($limit === -1) ? 999999999 : $limit;
        
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;
        $offset = ($currentPage - 1) * $sqlLimit;

        $totalTableLeads = $this->leadModel->getAllMyLeadsCount($userId, $year, $month, $lastAction, $lead_status, $onlyMissingFollowup, $search, $followup_date, $filterCard, $filterProjectId, $filterCategoryId, $filterCountry, $assignedHours, $from_date, $to_date, $uncontacted, $date_type);
        $totalPages = ceil($totalTableLeads / $sqlLimit);

        // Build pagination route query string (excluding 'page' and routing 'url')
        $queryArr = $_GET;
        unset($queryArr['page'], $queryArr['url']);
        $queryString = http_build_query($queryArr);
        $paginationRoute = $GLOBALS['base_url'] . '/my-all-assigned-leads' . ($queryString ? '?' . $queryString : '');

        // Fetch assigned leads for the user (PAGINATED)
        $myAssignedClients = $this->leadModel
            ->getAllMyLeads($userId, $year, $month, $lastAction, $lead_status, $onlyMissingFollowup, $search, $followup_date, $filterCard, $filterProjectId, $filterCategoryId, $filterCountry, $assignedHours, $from_date, $to_date, $uncontacted, $sqlLimit, $offset, $date_type);

        // Fetch specific booking & follow-up counts ONLY for the user's leads (FAST!)
        $leadIds = !empty($myAssignedClients) ? array_column($myAssignedClients, 'id') : [];
        $bookingCount   = !empty($leadIds) ? $this->bookingModel->getBookingCountByLeadIds($leadIds) : [];
        $followUpModel  = new FollowUp($this->db);
        $followUpCounts = !empty($leadIds) ? $followUpModel->getFollowUpCountByLeadIds($leadIds) : [];

        $followUpHistories = [];
        if (!empty($leadIds)) {
            $idsStr = implode(',', array_map('intval', $leadIds));
            $fuQuery = $this->db->query("SELECT fu.*, l.full_name, p.name AS project_name, f.flat_name 
                                         FROM lead_follow_ups fu 
                                         LEFT JOIN leads l ON fu.lead_id = l.id 
                                         LEFT JOIN projects p ON fu.project_id = p.id 
                                         LEFT JOIN flats f ON fu.flat_id = f.id 
                                         WHERE fu.lead_id IN ($idsStr) AND fu.deleted_at IS NULL ORDER BY fu.follow_up_date DESC, fu.id DESC");
            if ($fuQuery) {
                while($row = $fuQuery->fetch_assoc()) {
                    $followUpHistories[$row['lead_id']][] = $row;
                }
            }
        }

        // Fetch pending delete requests for user's leads
        $deleteRequests = !empty($leadIds) ? $this->leadModel->getPendingDeleteRequestsByLeadIds($leadIds) : [];

        // Calculate card statistics efficiently
        $summaryCards = $this->leadModel->getMyAssignedLeadsSummaryCards($userId, $year, $month, $lastAction, $lead_status, $onlyMissingFollowup, $search, $followup_date, $filterCard, $filterProjectId, $filterCategoryId, $filterCountry, $assignedHours, $from_date, $to_date, $uncontacted, $date_type);
        $totalLeadsCount        = (int)($summaryCards['total_leads'] ?? 0);
        $contactedLeadsCount    = (int)($summaryCards['contacted_leads'] ?? 0);
        $notContactedLeadsCount = (int)($summaryCards['not_contacted_leads'] ?? 0);
        $bookedLeadsCount       = (int)($summaryCards['booked_leads'] ?? 0);
        $totalBookingsCount     = $this->leadModel->countBookingsWithFilter($year, $month, null, null, null, $userId, $from_date, $to_date, $filterProjectId, $filterCategoryId, $filterCountry, $date_type);
        $upcomingFollowUpsCount = (int)($summaryCards['upcoming_follow_ups'] ?? 0);
        $missingFollowUpCount   = (int)($summaryCards['missing_follow_ups'] ?? 0);
        $irrelevantLeadsCount   = (int)($summaryCards['irrelevant_leads'] ?? 0);
        $noResponseLeadsCount   = (int)($summaryCards['no_response_leads'] ?? 0);

        // ── Duplicate Leads Detection (Optimized JOIN Query) ───────────────────
        $duplicateGroups = [];
        $duplicateCount  = 0;

        if ($userId) {
            $teamIdForQuery = $userTeamId ?: 0;
            $dupSql = "
                SELECT * FROM (
                    SELECT DISTINCT
                        l.id,
                        l.full_name,
                        l.phone_number,
                        l.email,
                        l.team_id,
                        t.name        AS team_name,
                        proj.name     AS project_name,
                        l.campaign_name,
                        l.assigned_id,
                        u.full_name   AS assigned_to_name,
                        l.updated_at  AS assigned_date,
                        IF(l.team_id = ?, 1, 0) AS is_mine
                    FROM leads my_leads
                    INNER JOIN leads l ON my_leads.phone_number != '' AND l.phone_number = my_leads.phone_number
                    LEFT JOIN users u ON l.assigned_id = u.id
                    LEFT JOIN employees emp ON emp.user_id = u.id
                    LEFT JOIN teams t ON l.team_id = t.id
                    LEFT JOIN projects proj ON l.project_id = proj.id
                    WHERE my_leads.assigned_id = ?
                      AND my_leads.deleted_at IS NULL
                      AND l.deleted_at IS NULL
                      AND l.assigned_id IS NOT NULL
                      AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))
                    
                    UNION
                    
                    SELECT DISTINCT
                        l.id,
                        l.full_name,
                        l.phone_number,
                        l.email,
                        l.team_id,
                        t.name        AS team_name,
                        proj.name     AS project_name,
                        l.campaign_name,
                        l.assigned_id,
                        u.full_name   AS assigned_to_name,
                        l.updated_at  AS assigned_date,
                        IF(l.team_id = ?, 1, 0) AS is_mine
                    FROM leads my_leads
                    INNER JOIN leads l ON my_leads.email != '' AND l.email = my_leads.email
                    LEFT JOIN users u ON l.assigned_id = u.id
                    LEFT JOIN employees emp ON emp.user_id = u.id
                    LEFT JOIN teams t ON l.team_id = t.id
                    LEFT JOIN projects proj ON l.project_id = proj.id
                    WHERE my_leads.assigned_id = ?
                      AND my_leads.deleted_at IS NULL
                      AND l.deleted_at IS NULL
                      AND l.assigned_id IS NOT NULL
                      AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))
                ) AS combined_results
                ORDER BY COALESCE(phone_number, email), is_mine DESC, assigned_date DESC
            ";

            $dupStmt = $this->db->prepare($dupSql);
            if ($dupStmt) {
                $dupStmt->bind_param('iiii', $teamIdForQuery, $userId, $teamIdForQuery, $userId);
                $dupStmt->execute();
                $dupResult = $dupStmt->get_result();
                $allDups   = [];
                while ($row = $dupResult->fetch_assoc()) {
                    $allDups[] = $row;
                }
                $dupStmt->close();

                // Group by phone_number or email
                $grouped = [];
                foreach ($allDups as $row) {
                    $key = !empty($row['phone_number'])
                        ? 'p:' . $row['phone_number']
                        : (!empty($row['email']) ? 'e:' . $row['email'] : 'id:' . $row['id']);
                    $grouped[$key][] = $row;
                }

                // Only keep groups with >1 unique assigned_id (real duplicate)
                foreach ($grouped as $key => $rows) {
                    $uniqueAssignees = array_unique(array_column($rows, 'assigned_id'));
                    if (count($uniqueAssignees) > 1) {
                        $duplicateGroups[] = [
                            'key'       => $key,
                            'label'     => $rows[0]['phone_number'] ?: ($rows[0]['email'] ?? ''),
                            'name'      => $rows[0]['full_name'],
                            'team_name' => $rows[0]['team_name'],
                            'rows'      => $rows,
                        ];
                        $duplicateCount++;
                    }
                }
            }
        }
        $userDuplicates = $this->leadModel->getDuplicateLeadsForUser($userId);
        $phoneMap = [];
        $emailMap = [];
        foreach ($userDuplicates as $row) {
            $phone1 = preg_replace('/[^0-9]/', '', $row['phone_number'] ?? '');
            $phone2 = preg_replace('/[^0-9]/', '', $row['phone'] ?? '');
            $email = trim(strtolower($row['email'] ?? ''));
            
            $dupInfo = [
                'id' => $row['id'],
                'full_name' => $row['full_name'],
                'created_at' => $row['created_at'] ? date('d F, Y', strtotime($row['created_at'])) : '',
                'project_name' => $row['project_name'] ?? 'N/A',
                'last_lead_action' => $row['last_lead_action'] ?? 'N/A',
                'lead_type' => $row['lead_type'] ?? 'N/A'
            ];

            if ($phone1 !== '') {
                if (!isset($phoneMap[$phone1])) {
                    $phoneMap[$phone1] = [];
                }
                $exists = false;
                foreach ($phoneMap[$phone1] as $item) {
                    if ($item['id'] == $row['id']) { $exists = true; break; }
                }
                if (!$exists) {
                    $phoneMap[$phone1][] = $dupInfo;
                }
            }
            if ($phone2 !== '' && $phone2 !== $phone1) {
                if (!isset($phoneMap[$phone2])) {
                    $phoneMap[$phone2] = [];
                }
                $exists = false;
                foreach ($phoneMap[$phone2] as $item) {
                    if ($item['id'] == $row['id']) { $exists = true; break; }
                }
                if (!$exists) {
                    $phoneMap[$phone2][] = $dupInfo;
                }
            }
            if ($email !== '') {
                if (!isset($emailMap[$email])) {
                    $emailMap[$email] = [];
                }
                $exists = false;
                foreach ($emailMap[$email] as $item) {
                    if ($item['id'] == $row['id']) { $exists = true; break; }
                }
                if (!$exists) {
                    $emailMap[$email][] = $dupInfo;
                }
            }
        }

        $projectModel = new Project($this->db);
        $projects = $projectModel->getAll();
        $projectCategoryModel = new ProjectCategory($this->db);
        $projectCategories = $projectCategoryModel->getAll();
        $countries = $this->leadModel->getDistinctCountries();

        // Fetch teams for re-assignment
        $teamModel = new Team($this->db);
        $teams = $teamModel->getAll();

        $userModel = new User($this->db);
        $teamId = $_SESSION['user']['team_id'] ?? null;
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if ($userRoleId === 9 || in_array($userRoleId, [1, 3, 32, 33])) {
            $teamMembers = $userModel->getAllRoleUser();
        } elseif (!empty($teamId)) {
            $teamMembers = $userModel->getUsersByTeamAndRole($teamId);
            if (empty($teamMembers)) {
                $teamMembers = $userModel->getAllRoleUser();
            }
        } else {
            $teamMembers = $userModel->getAllRoleUser();
        }

        $transferableUsers = $userModel->getSalesTransferableUsers();

        $this->view('sales-executive/my-assigned-leads', [
            'page_title'          => 'My Assigned Leads',
            'myAllleads'          => $myAssignedClients,
            'current_page'        => 'my-all-assigned-leads',
            'bookingCount'        => $bookingCount,
            'followUpCounts'      => $followUpCounts,
            'followUpHistories'   => $followUpHistories,
            'selectedAction'      => $lastAction,
            'selectedLeadStatus'  => $lead_status,
            'deleteRequests'      => $deleteRequests,
            'duplicateGroups'     => $duplicateGroups,
            'duplicateCount'      => $duplicateCount,
            'missingFollowUpCount'=> $missingFollowUpCount,
            'projects'            => $projects,
            'projectCategories'   => $projectCategories,
            'countries'           => $countries,
            'selectedCountry'     => $filterCountry,
            'isFilterMissing'     => $onlyMissingFollowup,
            'phoneMap'            => $phoneMap,
            'emailMap'            => $emailMap,
            'teams'               => $teams,
            'teamMembers'         => $teamMembers,
            'transferableUsers'   => $transferableUsers,
            'totalLeadsCount'        => $totalLeadsCount,
            'contactedLeadsCount'    => $contactedLeadsCount,
            'notContactedLeadsCount' => $notContactedLeadsCount,
            'notCalledLeadsCount'    => $notContactedLeadsCount,
            'bookedLeadsCount'       => $bookedLeadsCount,
            'totalBookingsCount'     => $totalBookingsCount,
            'upcomingFollowUpsCount' => $upcomingFollowUpsCount,
            'irrelevantLeadsCount'   => $irrelevantLeadsCount,
            'noResponseLeadsCount'   => $noResponseLeadsCount,
            'search'              => $search,
            'followup_date'       => $followup_date,
            'filterCard'          => $filterCard,
            'currentPage'         => $currentPage,
            'totalPages'          => $totalPages,
            'paginationRoute'     => $paginationRoute,
            'limit'               => $limit,
            'totalTableLeads'     => $totalTableLeads,
            'offset'              => $offset,
            'date_type'           => $date_type,
            'from_date'           => $from_date,
            'to_date'             => $to_date,
            'uncontacted'         => $uncontacted,
            'selectedYear'        => $year,
            'selectedMonth'       => $month,
            'assignedHours'       => $assignedHours,
            'page_script'         => ''
        ]);
    }
    public function myUpcomingFollowLeads()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user']['id'] ?? null;


        $upcomingLeads = [];
        if ($userId) {
            $upcomingLeads = $this->followUpModel
                ->upcomingFollowUp($userId);
        }
        $this->view("sales-executive/upcoming-follow-ups", [
            "current_route"       => "upcoming-follow-up",
            "page_title"          => "Upcoming Follow Up",
            "upcomingLeads"  => $upcomingLeads,
        ]);
    }
}
