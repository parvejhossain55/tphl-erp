<?php

class UserDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $taskModel;
    protected $followUpModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->taskModel = new Task($this->db);
        $this->followUpModel = new FollowUp($this->db);
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

    $loggedUserId = (int) $_SESSION['user']['id'];

    // only assigned today leads for logged user
    $todayLeads = $this->leadModel->getTodayLeadsByUser($loggedUserId);

    $this->view("sales-executive/user-dashboard", [
        "current_route" => "user-dashboard",
        "page_title"    => "User Dashboard",
        "todayLeads"    => $todayLeads,
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
        "current_route"       => "user-dashboard",
        "page_title"          => "User Dashboard",
        "todayFollowUpLeads"  => $todayFollowUpLeads,
    ]);
}



    }

