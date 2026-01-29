<?php

class UserDashboardController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $taskModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->taskModel = new Task($this->db);
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

        $todayLeads = $this->leadModel->getTodayLeads();

        
        // 4 Pass all data to view
        $this->view("users/user-dashboard", [
            "current_route" => "user-dashboard",
            "page_title"    => "User Dashboard",
            "todayLeads" => $todayLeads,
           
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





    public function updateTaskProgress()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_POST['task_id'], $_POST['progress'])) {
            echo "Invalid request";
            return;
        }

        $taskId = (int)$_POST['task_id'];
        $progress = (int)$_POST['progress'];

        $success = $this->taskModel->updateProgress($taskId, $progress);

        if ($success) {
            header("Location: " . $GLOBALS['base_url'] . "/employee-dashboard");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to update progress</h3>";
        }
    }
}
