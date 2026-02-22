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

    public function index()
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

        // only assigned today leads for logged user
        $todayLeads = $this->leadModel->getTodayLeadsByUser($loggedUserId);

        $myAllLeads = $this->leadModel->getAllMyLeads($loggedUserId);

        $this->view("sales-executive/today-leads", [
            "current_route" => "today-leads",
            "page_title"    => "Leads Today Assign",
            "todayLeads"    => $todayLeads,

            "bookingCount"    => $bookingCount,
            "todayFollowUp"    => $todayFollowUp,


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

        $todayFollowUpComplete = $this->followUpModel->getTodayFollowUpCompleteCount($loggedUserId);
        // executive target from user model
        // Get targets
        $month = date('Y-m'); // current month e.g. 2026-02
        $target = $this->salesTargetModel->getTarget($loggedUserId, $month);
        $monthlySiteVisitTarget = $target['site_visit_target'] ?? 0;
        $monthlyBookingTarget = $target['booking_target'] ?? 0;
        $monthlyBookingValueTarget = $target['booking_value_target'] ?? 0.00;

        // Get completions
        $monthlyVisitComplete = $this->salesTargetModel->getMonthlyVisitCompleted($loggedUserId, $month);
        $monthlyBookingComplete = $this->salesTargetModel->getMonthlyBookingCompleted($loggedUserId, $month);

        // Calculate achievement %
        $visitAchievement = $monthlySiteVisitTarget > 0 ? round(($monthlyVisitComplete / $monthlySiteVisitTarget) * 100, 2) : 0;
        $bookingAchievement = $monthlyBookingTarget > 0 ? round(($monthlyBookingComplete / $monthlyBookingTarget) * 100, 2) : 0;

        // Deals Closed & Won
        $dealsClosed = $this->salesTargetModel->getDealsClosed($loggedUserId, $month);
        $dealsClosedAll = $this->salesTargetModel->getDealsClosedAllTime($loggedUserId);
        $dealsWin = $this->salesTargetModel->getDealsWon($loggedUserId, $month);
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

        ]);
    }
    // today follow up 
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

        $followUpId = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? null;
        $next_action = $_POST['next_action'] ?? null;
        $note = trim($_POST['discussion_note'] ?? '');

        if (!$followUpId || !$status) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }

        $stmt = $this->db->prepare("
        UPDATE lead_follow_ups
        SET status = ?, discussion_note = ?, next_action = ?, updated_at = NOW()
        WHERE id = ?
    ");
        $stmt->bind_param("sssi", $status, $note, $next_action, $followUpId);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Follow-up updated successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Update failed']);
        }
    }


    public function updateCallStatus()
    {
        $id = $_POST['lead_id'];
        $status = $_POST['call_status'];

        $this->db->query(
            "UPDATE leads SET call_status='$status' WHERE id=$id"
        );
    }


    public function updateCallNote()
    {
        $id = $_POST['lead_id'];
        $note = $this->db->real_escape_string($_POST['call_note']);

        $this->db->query(
            "UPDATE leads SET call_note='$note' WHERE id=$id"
        );
    }

    public function todayFollowUp()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user']['id'] ?? null;

        $todayFollowUpLeads = [];
        if ($userId) {
            $todayFollowUpLeads = $this->followUpModel
                ->getTodayFollowUpLoginUser((int)$userId);
        }

        $this->view("sales-executive/today-followup", [
            "current_route"       => "today-follow-up",
            "page_title"          => "Today Follow Up",
            "todayFollowUpLeads"  => $todayFollowUpLeads,
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

        $userId = $_SESSION['user']['id'] ?? 0;
        $bookingCount = $this->bookingModel->getBookingCountByLead();
        $followUpCounts = (new FollowUp($this->db))->getFollowUpCountByLead();
        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;
        $lastAction = $_GET['last_action'] ?? null;
        $myAssignedClients = $this->leadModel
            ->getAllMyLeads($userId, $year, $month);

        $this->view('sales-executive/my-assigned-leads', [
            'page_title' => 'My Assigned Leads',
            'myAllleads' => $myAssignedClients,
            'current_page' => 'my-all-assigned-leads',
            'bookingCount' => $bookingCount,
            'followUpCounts' => $followUpCounts,
            'selectedAction' => $lastAction,
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
