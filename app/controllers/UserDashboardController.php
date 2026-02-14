<?php

class UserDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $taskModel;
    protected $followUpModel;
    protected $bookingModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
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
        $todayFollowUp = (new FollowUp($this->db))->getTodayFollowUpCountByLead();
        $followUpOverDue =

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
        $todayTask = $this->taskModel->getTodayTaskCountByUser($loggedUserId);
        $myTotalShareSold = $this->bookingModel->getMyTotalFlatSales($loggedUserId);

        $bookingCount   = $this->bookingModel->getBookingCountByLead();
        // $todayFollowUp  = (new FollowUp($this->db))->getTodayFollowUpCountByLead();
        $todayFollowUp  = $this->followUpModel->getTodayFollowUpCountByUser($loggedUserId);

        $this->view("sales-executive/my-dashboard", [
            "current_route"   => "my-dashboard",
            "page_title"      => "my-dashboard",
            "todayLeads"      => $todayLeads,
            "bookingCount"    => $bookingCount,
            "todayFollowUp"   => $todayFollowUp,
            "todayLeadsCount"   => $todayLeadsCount,
            "todayTask"   => $todayTask,
            "myTotalShareSold"   => $myTotalShareSold,

        ]);
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

        $year  = $_GET['year']  ?? null;
        $month = $_GET['month'] ?? null;

        $myAssignedClients = $this->leadModel
            ->getAllMyLeads($userId, $year, $month);

        $this->view('sales-executive/my-assigned-leads', [
            'myAllleads' => $myAssignedClients,
            'current_page' => 'my-all-assigned-leads',
        ]);
    }
}
