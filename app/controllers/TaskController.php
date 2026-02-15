<?php
class TaskController extends Controller
{
    protected $db;
    protected $taskModel;
    protected $userModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->userModel = new User($this->db);
        $this->taskModel = new Task($this->db);
        $this->userModel = new User($this->db);
    }

    // Employee List Page
    public function index()
    {
        $searchTerm = $_GET['q'] ?? '';

        $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $perPage = 10;
        $offset = ($currentPage - 1) * $perPage;

        // Total employee count with filters
        $totalTasks = $this->taskModel->countAll($searchTerm,);
        $totalPages = ceil($totalTasks / $perPage);

        $tasks = $this->taskModel->getPaginated($perPage, $offset, $searchTerm);
        // Get paginated users with search & department filter
        $users = $this->userModel->getAll();

        $this->view("tasks/task-lists", [
            "current_route"   => "task-lists",
            "page_title"      => "Task List",
            "currentPage"     => $currentPage,
            "totalPages"      => $totalPages,
            "users"       => $users,
            "searchTerm"      => $searchTerm,
            "tasks"                => $tasks,

        ]);
    }

    // Create Employee Page
    public function create()
    {

        // Get paginated users with search & department filter
        $users = $this->userModel->getAll();


        $this->view("tasks/create-task", [
            "current_route"   => "create-task",
            "active"          => "create-task",
            "page_title"      => "Add task",
            "show_breadcrumb" => true,
            "users"     => $users,
        ]);
    }

    // Handle form submission
    public function store()
    {
        $data = [
            'title'            => trim($_POST['title'] ?? ''),
            'user_id'      => (int) ($_POST['user_id'] ?? 0),
            'description'      => trim($_POST['description'] ?? ''),
            'start_date'       => $_POST['start_date'] ?? null,
            'deadline'         => $_POST['deadline'] ?? null,
            'employee_accept'  => (int) ($_POST['employee_accept'] ?? 0),
            'status'           => $_POST['status'] ?? 'pending',
            'progress'         => (int) ($_POST['progress'] ?? 0),
            'priority'         => $_POST['priority'] ?? 'medium',
            'assigned_by'      => $_SESSION['user']['id'] ?? null,
        ];


        if ($this->taskModel->create($data)) {
            $taskId = $this->db->insert_id; // নতুন task id
            $message = "New task assigned: " . $data['title'];

            // Notification insert
            $stmt = $this->db->prepare(
                "INSERT INTO notifications (user_id, task_id, assigned_by, message) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("iiis", $data['user_id'], $taskId, $data['assigned_by'], $message);
            $stmt->execute();

            header("Location: {$GLOBALS['base_url']}/task-lists");
            exit;
        }


        if ($this->taskModel->create($data)) {
            $taskId = $this->db->insert_id;
            $message = "New Task Assigned for You" . $data["title"];

            $stmt = $this->db->prepare(
                "INSERT INTO notifications(user_id,task_id, assigned_by, message) VALUES(?,?,?,?) "
            );
            $stmt->bind_param("");
            $stmt->execute();
            header("Location: {$GLOBALS['base_url']}/task-lists");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to create task</h3>";
        }
    }
    // Edit Employee Page
    public function edit($id)
    {
        $task = $this->taskModel->find($id);
        $users = $this->userModel->getAll();

        $this->view("tasks/edit-task", [
            "current_route"   => "edit-task",
            "page_title"      => "Edit task",
            "show_breadcrumb" => true,
            "task"            => $task,
            "users"       => $users,
        ]);
    }

    public function update($id)
    {
        $data = [
            'title'            => trim($_POST['title'] ?? ''),
            'user_id'      => (int) ($_POST['user_id'] ?? 0),
            'description'      => trim($_POST['description'] ?? ''),
            'start_date'       => $_POST['start_date'] ?? null,
            'deadline'         => $_POST['deadline'] ?? null,
            'employee_accept'  => (int) ($_POST['employee_accept'] ?? 0),
            'status'           => $_POST['status'] ?? 'pending',
            'progress'         => (int) ($_POST['progress'] ?? 0),
            'priority'         => $_POST['priority'] ?? 'medium',
            'assigned_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if ($this->taskModel->update($id, $data)) {
            header("Location: {$GLOBALS['base_url']}/task-lists");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to update task</h3>";
        }
    }


    // Delete Employee
    public function delete($id)
    {
        if ($this->userModel->delete($id)) {
            header("Location: {$GLOBALS['base_url']}/task-lists");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to delete task</h3>";
        }
    }

    public function show($id)
    {
        $task = $this->taskModel->find($id);

        $this->view("tasks/show-task", [
            "current_route" => "show-task",
            "task" => $task,
        ]);
    }

    public function taskDashboard()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            echo "User not logged in";
            return;
        }

        $loggedUserId = (int) $_SESSION['user']['id'];

        // 1 User info
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $loggedUserId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        if (!$user) {
            echo "User not found";
            return;
        }

        $userId = (int)$user['id'];

        // 2 Notifications
        $notifications = $this->taskModel->taskNotification();
// 1. Get count of today's tasks (for card)
    $stmt = $this->db->prepare("
        SELECT COUNT(*) AS today_task_count
        FROM tasks
        WHERE user_id = ?
          AND status != 'completed'
          AND DATE(deadline) = CURDATE()
    ");
    $stmt->bind_param("i", $loggedUserId);
    $stmt->execute();
    $todayTaskCount = $stmt->get_result()->fetch_assoc()['today_task_count'] ?? 0;
        // 3 Pending tasks for this user
        $stmt = $this->db->prepare("
    SELECT 
        t.*,

        -- Assigned By Info
        u.full_name AS assigned_by_name,
        u.email AS assigned_by_email,
        u.photo AS assigned_by_photo,
        des_assign.name AS assigned_by_designation

    FROM tasks t

    JOIN users u 
        ON t.assigned_by = u.id

    LEFT JOIN designations des_assign 
        ON u.designation_id = des_assign.id

    WHERE t.user_id = ?
      AND t.status != 'completed'   -- only incomplete tasks

    ORDER BY t.deadline ASC
");


        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $pendingTasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 4 Pass all data to view
        $this->view("tasks/task-dashboard", [
            "current_route" => "task-dashboard",
            "page_title"    => "Task Dashboard",
            "user"      => $user,
            "notifications" => $notifications,
            "pendingTasks"  => $pendingTasks,
            "todayTaskCount"  => $todayTaskCount,
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
            header("Location: " . $GLOBALS['base_url'] . "/task-dashboard");
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
            header("Location: " . $GLOBALS['base_url'] . "/task-dashboard");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to update progress</h3>";
        }
    }
}
