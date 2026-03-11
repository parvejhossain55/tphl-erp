<?php

class EmployeeDashboardController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $taskModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
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

        // 1 Get logged-in user
        $loggedUserId = (int) $_SESSION['user']['id'];

        // 2 Get user record from users table
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $loggedUserId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            echo "User not found";
            return;
        }

        // 3 Get employee data using employee_id from users table
        $employeeId = (int) $user['employee_id'];
        $stmt = $this->db->prepare("SELECT * FROM employees WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $employee = $stmt->get_result()->fetch_assoc();
        if (!$employee) {
            echo "Employee not found";
            return;
        }

        // 4 Notifications
        $notifications = $this->employeeModel->taskNotification();

        // 5 Pending tasks for this employee
        $stmt = $this->db->prepare("
    SELECT t.*
    FROM tasks t
    WHERE t.user_id = ?
      AND t.status != 'completed'
    ORDER BY t.deadline ASC
");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $pendingTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 6 Pass data to view
        $this->view("employee-dashboard/emp-dashboard", [
            "current_route" => "employee-dashboard",
            "page_title"    => "Employee Dashboard",
            "employee"      => $employee,
            "notifications" => $notifications,
            "pendingTasks"  => $pendingTasks,
        ]);
    }


    public function updateTaskStatus()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_POST['task_id'])) {
            echo "Invalid request";
            return;
        }

        $taskId = (int)$_POST['task_id'];

        $success = $this->taskModel->acceptTask($taskId);

        if ($success) {
            header("Location: " . $GLOBALS['base_url'] . "/employee-dashboard");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to update task</h3>";
        }
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

    public function myAttendance(){
        $this->view('employee-modules/my-attendance',[
            'current_route' => 'my-attendance'
        ]);
    }
    public function myLeaves(){
        $this->view('employee-modules/my-leaves',[
            'current_route' => 'my-leaves'
        ]);
    }
    public function myProfile(){
        $this->view('employee-modules/my-profile',[
            'current_route' => 'my-leaves'
        ]);
    }
}
