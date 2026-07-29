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

        $filterProjectId = isset($_GET['project_id']) && $_GET['project_id'] !== '' ? (int)$_GET['project_id'] : null;
        $filterCategoryId = isset($_GET['project_category_id']) && $_GET['project_category_id'] !== '' ? (int)$_GET['project_category_id'] : null;
        $filterCountry = !empty($_GET['filter_country']) ? $_GET['filter_country'] : null;
        if (is_array($filterCountry)) {
            $filterCountry = array_values(array_filter($filterCountry, fn($val) => trim($val) !== ''));
            if (empty($filterCountry)) $filterCountry = null;
        } elseif (is_string($filterCountry) && trim($filterCountry) === '') {
            $filterCountry = null;
        }

        // only assigned today leads for logged user
        $todayLeads = $this->leadModel->getTodayLeadsByUser($loggedUserId, $filterProjectId, $filterCategoryId, $filterCountry);

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

        $this->view("sales-executive/today-leads", [
            "current_route" => "today-leads",
            "page_title"    => "Leads Today Assign",
            "todayLeads"    => $todayLeads,
            "followUpCounts" => $followUpCounts,
            "bookingCount"    => $bookingCount,
            "todayFollowUp"    => $todayFollowUp,
            "phoneMap"        => $phoneMap,
            "emailMap"        => $emailMap,
            "projects"        => $projects,
            "projectCategories" => $projectCategories,
            "countries"       => $countries,
            "selectedCountry" => $filterCountry,
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
        // executive target from user model
        // Get targets
        $filterType = $_GET['filter_type'] ?? 'monthly';
        $selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $selectedStartMonth = isset($_GET['start_month']) ? (int)$_GET['start_month'] : (int)date('m');
        $selectedEndMonth = isset($_GET['end_month']) ? (int)$_GET['end_month'] : (int)date('m');

        if ($filterType === 'yearly') {
            $startMonth = 1;
            $endMonth = 12;
        } else {
            $startMonth = $selectedStartMonth;
            $endMonth = $selectedEndMonth;
        }

        // Fetch range stats
        $stats = $this->salesTargetModel->getRangeStats($loggedUserId, $selectedYear, $startMonth, $endMonth);

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
        $dealsWin = $stats['deals_won'];
        $dealsWinAll = $this->salesTargetModel->getDealsClosedAllTime($loggedUserId);
        $dealsLostAll = $this->salesTargetModel->getAllDealsLost($loggedUserId);



        $this->view("sales-executive/my-dashboard", [
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
            "dealsWin"    => $dealsWin,
            "dealsWinAll"    => $dealsWinAll,
            "dealsLostAll"    => $dealsLostAll,
            "monthlyVisitComplete"    => $monthlyVisitComplete,
            "selectedYear"            => $selectedYear,
            "selectedStartMonth"      => $selectedStartMonth,
            "selectedEndMonth"        => $selectedEndMonth,
            "filterType"              => $filterType,
        ]);
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
        $id = $_POST['lead_id'];
        $status = $_POST['call_status'];
        $stmt = $this->db->prepare("UPDATE leads SET call_status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    }
    public function updateCommunicationStatus()
    {
        $id = $_POST['lead_id'];
        $status = $_POST['communication_type'];
        $stmt = $this->db->prepare("UPDATE leads SET communication_type=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
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

        $stmt = $this->db->prepare("UPDATE leads SET lead_type=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Lead status updated'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Update failed']);
        }

        $stmt->close();
    }


    public function updateCallNote()
    {
        $id = (int)$_POST['lead_id'];
        $note = $this->db->real_escape_string($_POST['call_note']);

        $result = $this->db->query(
            "UPDATE leads SET call_note='$note' WHERE id=$id"
        );
        
        header('Content-Type: application/json');
        if ($result) {
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

        $this->view("sales-executive/today-followup", [
            "current_route"       => "today-follow-up",
            "page_title"          => "Today Follow Up",
            "todayFollowUpLeads"  => $todayFollowUpLeads,
            "countries"           => $countries,
            "selectedCountry"     => $filterCountry,
            "projects"            => $projects,
            "projectCategories"   => $projectCategories,
        ]);
    }

    public function myOnboardProject()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user']['id'] ?? 0;

        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;

        $myOnboardProjects = $this->bookingModel
            ->getAllMyOnboardProject($userId, $year, $month);

        $this->view('sales-executive/my-onboard-projects', [
            'projects' => $myOnboardProjects,

            'current_page' => 'my-onboarding-projects',
        ]);
    }

    public function myAssignedLeads()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId     = $_SESSION['user']['id']     ?? 0;
        $userTeamId = $_SESSION['user']['team_id'] ?? null;
        $bookingCount   = $this->bookingModel->getBookingCountByLead();
        $followUpModel  = new FollowUp($this->db);
        $followUpCounts = $followUpModel->getFollowUpCountByLead();
        $year                 = $_GET['year']          ?? null;
        $month                = $_GET['month']         ?? null;
        $lastAction           = $_GET['last_action']   ?? null;
        $call_status          = $_GET['call_status']   ?? null;
        $search               = trim($_GET['search'] ?? '');
        $followup_date        = $_GET['followup_date'] ?? null;
        $filterCard           = $_GET['filter_card']   ?? null;

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

        // Calculate card statistics from unfiltered assigned leads for the user
        $unfilteredAssignedLeads = $this->leadModel->getAllMyLeads($userId, $year, $month, null, null, false, '', null, null, $filterProjectId, $filterCategoryId, $filterCountry);

        $totalLeadsCount        = count($unfilteredAssignedLeads);
        $contactedLeadsCount    = 0;
        $notContactedLeadsCount = 0;
        $bookedLeadsCount       = 0;

        foreach ($unfilteredAssignedLeads as $l) {
            $act      = strtolower(trim($l['last_lead_action'] ?? ''));
            $cStat    = strtolower(trim($l['call_status'] ?? ''));
            $commType = strtolower(trim($l['communication_type'] ?? ''));
            $fStat    = $l['last_follow_up_status'] ?? null;

            $hasComm  = (!empty($commType) && $commType !== 'not select');

            $isContacted = in_array($act, ['contacted', 'follow up', 'visited', 'booked']) 
                        || in_array($cStat, ['called', 'received']) 
                        || !empty($fStat) 
                        || $hasComm;

            if ($isContacted) {
                $contactedLeadsCount++;
            } else {
                $notContactedLeadsCount++;
            }

            if ($act === 'booked') {
                $bookedLeadsCount++;
            }
        }

        $myAssignedClients = $this->leadModel
            ->getAllMyLeads($userId, $year, $month, $lastAction, $call_status, $onlyMissingFollowup, $search, $followup_date, $filterCard, $filterProjectId, $filterCategoryId, $filterCountry);

        $missingFollowUpCount = $followUpModel->getMissingFollowUpCount($userId);

        // ── Duplicate Leads Detection ──────────────────────────────────────────
        // Pool: ALL leads in my team that are assigned (any member)
        // Find: same phone/email appearing in OTHER assigned_ids (any team)
        // Show: all rows with that phone/email so full picture is visible
        $duplicateGroups = [];
        $duplicateCount  = 0;

        if ($userId) {
            $teamIdForQuery = $userTeamId ?: 0;
            $dupSql = "
                SELECT
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
                FROM leads l
                LEFT JOIN users u ON l.assigned_id = u.id
                LEFT JOIN employees emp ON emp.user_id = u.id
                LEFT JOIN teams t ON l.team_id = t.id
                LEFT JOIN projects proj ON l.project_id = proj.id
                WHERE l.deleted_at IS NULL
                  AND l.assigned_id IS NOT NULL
                  AND (emp.employee_status IS NULL OR emp.employee_status NOT IN ('resigned', 'inactive', 'terminated'))
                  AND (
                      (l.phone_number IS NOT NULL AND l.phone_number != '' AND l.phone_number IN (
                          SELECT phone_number FROM leads
                          WHERE deleted_at IS NULL
                            AND assigned_id = ?
                            AND phone_number IS NOT NULL AND phone_number != ''
                      ))
                      OR
                      (l.email IS NOT NULL AND l.email != '' AND l.email IN (
                          SELECT email FROM leads
                          WHERE deleted_at IS NULL
                            AND assigned_id = ?
                            AND email IS NOT NULL AND email != ''
                      ))
                  )
                ORDER BY COALESCE(l.phone_number, l.email), is_mine DESC, l.updated_at DESC
            ";

            $dupStmt = $this->db->prepare($dupSql);
            if ($dupStmt) {
                $dupStmt->bind_param('iii', $teamIdForQuery, $userId, $userId);
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

        $this->view('sales-executive/my-assigned-leads', [
            'page_title'          => 'My Assigned Leads',
            'myAllleads'          => $myAssignedClients,
            'current_page'        => 'my-all-assigned-leads',
            'bookingCount'        => $bookingCount,
            'followUpCounts'      => $followUpCounts,
            'selectedAction'      => $lastAction,
            'selectedCallStatus'  => $call_status,
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
            'totalLeadsCount'        => $totalLeadsCount,
            'contactedLeadsCount'    => $contactedLeadsCount,
            'notContactedLeadsCount' => $notContactedLeadsCount,
            'notCalledLeadsCount'    => $notContactedLeadsCount,
            'bookedLeadsCount'       => $bookedLeadsCount,
            'search'              => $search,
            'followup_date'       => $followup_date,
            'filterCard'          => $filterCard,
            'page_script'         => "
                const urlParams = new URLSearchParams(window.location.search);
                const pageFromUrl = parseInt(urlParams.get('page') || '1', 10);
                const pageLength = 25;
                const displayStart = (pageFromUrl > 1) ? (pageFromUrl - 1) * pageLength : 0;

                $('#assignedLeadsTable').DataTable().destroy();
                const table = $('#assignedLeadsTable').DataTable({
                    pageLength : pageLength,
                    lengthMenu : [[25, 50, 100, -1], [25, 50, 100, 'All']],
                    order      : [],
                    displayStart : displayStart,
                    drawCallback: function () {
                        if (!window.hasScrolledToTarget) {
                            const hash = window.location.hash;
                            if (hash && hash.startsWith('#lead-')) {
                                const targetEl = $(hash);
                                if (targetEl.length > 0) {
                                    window.hasScrolledToTarget = true;
                                    $('html, body').animate({
                                        scrollTop: targetEl.offset().top - 100
                                    }, 500);
                                }
                            }
                        }
                    }
                });

                table.on('page.dt', function () {
                    const info = table.page.info();
                    const page = info.page + 1;
                    const url = new URL(window.location.href);
                    url.searchParams.set('page', page);
                    window.history.replaceState(null, '', url.toString());
                });
            ",
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
