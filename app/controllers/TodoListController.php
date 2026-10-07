<?php
class TodoListController extends Controller
{
    private $db;
    private $userId;

    public function __construct($db)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['user']['id'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit();
        }
        $this->db = $db;
        $this->userId = (int)$_SESSION['user']['id'];
    }

    public function index()
    {
        // Fetch projects for sidebar
        $projRes = $this->db->query("SELECT * FROM todo_projects WHERE user_id = {$this->userId} ORDER BY is_inbox DESC, name ASC");
        $projects = [];
        $inboxId = 0;
        
        if ($projRes) {
            while ($p = $projRes->fetch_assoc()) {
                $projects[] = $p;
                if ($p['is_inbox'] == 1) {
                    $inboxId = $p['id'];
                }
            }
        }

        // Create default Inbox if not exists
        if (empty($projects)) {
            $this->db->query("INSERT INTO todo_projects (user_id, name, color_code, is_inbox) VALUES ({$this->userId}, 'Inbox', '#246fe0', 1)");
            $inboxId = $this->db->insert_id;
            $projects[] = [
                'id' => $inboxId,
                'user_id' => $this->userId,
                'name' => 'Inbox',
                'color_code' => '#246fe0',
                'is_inbox' => 1
            ];
        }

        $viewType = $_GET['view'] ?? 'inbox';
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        
        $where = "t.user_id = {$this->userId}";
        $title = 'Inbox';
        
        if ($viewType === 'completed') {
            $where .= " AND t.status = 'completed'";
            $title = 'Completed Tasks';
        } else {
            // We now show both pending and completed tasks in the views so the user can see what they marked
            if ($viewType === 'today') {
                $where .= " AND DATE(t.due_date) <= CURDATE()";
                $title = 'Today';
            } elseif ($viewType === 'upcoming') {
                $where .= " AND DATE(t.due_date) > CURDATE()";
                $title = 'Upcoming';
            } elseif ($viewType === 'project' && $projectId > 0) {
                $where .= " AND t.todo_project_id = $projectId";
                $pNameRes = $this->db->query("SELECT name FROM todo_projects WHERE id = $projectId");
                if ($pNameRes && $pName = $pNameRes->fetch_assoc()) {
                    $title = $pName['name'];
                }
            }
        }

        // Pagination
        $limit = 20;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $limit;
        
        $countQueryTotal = "SELECT COUNT(*) as total FROM todo_tasks t WHERE $where";
        $countResTotal = $this->db->query($countQueryTotal);
        $totalRows = $countResTotal ? $countResTotal->fetch_assoc()['total'] : 0;
        $totalPages = ceil($totalRows / $limit);

        // Fetch tasks
        $taskQuery = "
            SELECT t.*, p.name as project_name, p.color_code as project_color,
                   tr.repeat_type, r.max_reminders
            FROM todo_tasks t 
            LEFT JOIN todo_projects p ON t.todo_project_id = p.id 
            LEFT JOIN todo_task_recurrences tr ON t.id = tr.todo_task_id
            LEFT JOIN todo_task_reminders r ON t.id = r.todo_task_id
            WHERE $where 
            ORDER BY (t.status = 'completed') ASC, t.due_date ASC, t.priority ASC, t.created_at DESC
            LIMIT $limit OFFSET $offset
        ";
        $taskRes = $this->db->query($taskQuery);
        $tasks = [];
        if ($taskRes) {
            while ($t = $taskRes->fetch_assoc()) {
                $tasks[] = $t;
            }
        }

        // Calculate Counts
        $countQuery = "
            SELECT 
                SUM(CASE WHEN todo_project_id = $inboxId THEN 1 ELSE 0 END) as inbox_count,
                SUM(CASE WHEN DATE(due_date) <= CURDATE() THEN 1 ELSE 0 END) as today_count,
                SUM(CASE WHEN DATE(due_date) > CURDATE() THEN 1 ELSE 0 END) as upcoming_count
            FROM todo_tasks 
            WHERE user_id = {$this->userId} AND status = 'pending'
        ";
        $countRes = $this->db->query($countQuery);
        $counts = ['inbox_count' => 0, 'today_count' => 0, 'upcoming_count' => 0];
        if ($countRes && $c = $countRes->fetch_assoc()) {
            $counts = $c;
        }

        // Fetch Labels
        $labels = [];
        $labelRes = $this->db->query("SELECT * FROM todo_labels WHERE user_id = {$this->userId} ORDER BY name ASC");
        if ($labelRes) {
            while ($l = $labelRes->fetch_assoc()) {
                $labels[] = $l;
            }
        }

        $data = [
            'projects' => $projects,
            'tasks' => $tasks,
            'inboxId' => $inboxId,
            'viewType' => $viewType,
            'pageTitle' => $title,
            'projectId' => $projectId,
            'counts' => $counts,
            'labels' => $labels,
            'totalPages' => $totalPages,
            'currentPage' => $page
        ];

        $this->view('todo/index', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = $this->db->real_escape_string($_POST['title'] ?? '');
            if (!empty($title)) {
                $description = $this->db->real_escape_string($_POST['description'] ?? '');
                $location = $this->db->real_escape_string($_POST['location'] ?? '');
                $projectId = (int)$_POST['todo_project_id'];
                $priority = (int)($_POST['priority'] ?? 4);
                $dueDate = !empty($_POST['due_date']) ? "'" . $this->db->real_escape_string($_POST['due_date']) . "'" : "NULL";
                
                // Handle Attachment
                $attachmentPath = "NULL";
                if (!empty($_FILES['attachment']['name'])) {
                    $uploadDir = __DIR__ . '/../../public/uploads/todo/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }
                    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['attachment']['name']));
                    $targetPath = $uploadDir . $fileName;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                        $attachmentPath = "'" . $this->db->real_escape_string('public/uploads/todo/' . $fileName) . "'";
                    }
                }
                
                $query = "INSERT INTO todo_tasks (user_id, todo_project_id, title, description, priority, due_date, location, attachment) 
                          VALUES ({$this->userId}, $projectId, '$title', '$description', $priority, $dueDate, '$location', $attachmentPath)";
                
                if ($this->db->query($query)) {
                    $taskId = $this->db->insert_id;
                    
                    // Handle Labels
                    if (!empty($_POST['labels']) && is_array($_POST['labels'])) {
                        foreach ($_POST['labels'] as $lblId) {
                            $lId = (int)$lblId;
                            if ($lId > 0) {
                                $this->db->query("INSERT IGNORE INTO todo_task_labels (todo_task_id, todo_label_id) VALUES ($taskId, $lId)");
                            }
                        }
                    }
                    
                    // Handle Recurrence
                    if (!empty($_POST['repeat_type'])) {
                        $repeatType = $this->db->real_escape_string($_POST['repeat_type']);
                        $this->db->query("INSERT INTO todo_task_recurrences (todo_task_id, repeat_type) VALUES ($taskId, '$repeatType')");
                    }
                    
                    // Handle Reminders
                    if (!empty($_POST['max_reminders']) && (int)$_POST['max_reminders'] > 0) {
                        $max = (int)$_POST['max_reminders'];
                        $triggerTime = !empty($_POST['due_date']) ? "'" . $this->db->real_escape_string($_POST['due_date']) . " 09:00:00'" : "NOW()";
                        $this->db->query("INSERT INTO todo_task_reminders (todo_task_id, trigger_time, max_reminders) VALUES ($taskId, $triggerTime, $max)");
                    }
                }
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/todo");
        exit();
    }

    public function complete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
            $taskId = (int)$_POST['task_id'];
            
            // Fetch task details
            $taskRes = $this->db->query("SELECT * FROM todo_tasks WHERE id = $taskId AND user_id = {$this->userId}");
            if ($taskRes && $task = $taskRes->fetch_assoc()) {
                
                // Check recurrence
                $recRes = $this->db->query("SELECT * FROM todo_task_recurrences WHERE todo_task_id = $taskId");
                if ($recRes && $rec = $recRes->fetch_assoc()) {
                    $nextDueDate = null;
                    if ($task['due_date']) {
                        $currentDate = new DateTime($task['due_date']);
                        if ($rec['repeat_type'] === 'daily') {
                            $currentDate->modify('+1 day');
                        } elseif ($rec['repeat_type'] === 'weekly') {
                            $currentDate->modify('+1 week');
                        } elseif ($rec['repeat_type'] === 'monthly') {
                            $currentDate->modify('+1 month');
                        }
                        $nextDueDate = "'" . $currentDate->format('Y-m-d H:i:s') . "'";
                    }
                    
                    // Create duplicate task for next occurrence
                    $title = $this->db->real_escape_string($task['title']);
                    $desc = $this->db->real_escape_string($task['description'] ?? '');
                    $projectId = $task['todo_project_id'] ? $task['todo_project_id'] : "NULL";
                    $priority = (int)$task['priority'];
                    
                    $this->db->query("INSERT INTO todo_tasks (user_id, todo_project_id, title, description, priority, due_date) 
                                      VALUES ({$this->userId}, $projectId, '$title', '$desc', $priority, " . ($nextDueDate ?? 'NULL') . ")");
                    $newTaskId = $this->db->insert_id;
                    
                    // Copy recurrence
                    $this->db->query("INSERT INTO todo_task_recurrences (todo_task_id, repeat_type) VALUES ($newTaskId, '{$rec['repeat_type']}')");
                }
                
                // Mark current task completed
                $this->db->query("UPDATE todo_tasks SET status = 'completed' WHERE id = $taskId");
                
                // Deactivate reminders
                $this->db->query("UPDATE todo_task_reminders SET is_active = 0 WHERE todo_task_id = $taskId");
            }
        }
        
        $view = $_POST['redirect_view'] ?? 'inbox';
        header("Location: " . $GLOBALS['base_url'] . "/todo?view=" . urlencode($view));
        exit();
    }

    public function delete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
            $taskId = (int)$_POST['task_id'];
            $deleteFuture = isset($_POST['delete_future']) ? true : false;
            
            // Check if it's the user's task
            $taskRes = $this->db->query("SELECT * FROM todo_tasks WHERE id = $taskId AND user_id = {$this->userId}");
            if ($taskRes && $task = $taskRes->fetch_assoc()) {
                if (!$deleteFuture) {
                    // Generate next occurrence before deleting current one
                    $recRes = $this->db->query("SELECT * FROM todo_task_recurrences WHERE todo_task_id = $taskId");
                    if ($recRes && $rec = $recRes->fetch_assoc()) {
                        $nextDueDate = null;
                        if ($task['due_date']) {
                            $currentDate = new DateTime($task['due_date']);
                            if ($rec['repeat_type'] === 'daily') {
                                $currentDate->modify('+1 day');
                            } elseif ($rec['repeat_type'] === 'weekly') {
                                $currentDate->modify('+1 week');
                            } elseif ($rec['repeat_type'] === 'monthly') {
                                $currentDate->modify('+1 month');
                            }
                            $nextDueDate = "'" . $currentDate->format('Y-m-d H:i:s') . "'";
                        }
                        
                        $title = $this->db->real_escape_string($task['title']);
                        $desc = $this->db->real_escape_string($task['description'] ?? '');
                        $projectId = $task['todo_project_id'] ? $task['todo_project_id'] : "NULL";
                        $priority = (int)$task['priority'];
                        
                        $this->db->query("INSERT INTO todo_tasks (user_id, todo_project_id, title, description, priority, due_date) 
                                          VALUES ({$this->userId}, $projectId, '$title', '$desc', $priority, " . ($nextDueDate ?? 'NULL') . ")");
                        $newTaskId = $this->db->insert_id;
                        $this->db->query("INSERT INTO todo_task_recurrences (todo_task_id, repeat_type) VALUES ($newTaskId, '{$rec['repeat_type']}')");
                    }
                }
                
                // Now delete the task
                $this->db->query("DELETE FROM todo_tasks WHERE id = $taskId");
            }
        }
        
        $view = $_POST['redirect_view'] ?? 'inbox';
        header("Location: " . $GLOBALS['base_url'] . "/todo?view=" . urlencode($view));
        exit();
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
            $taskId = (int)$_POST['task_id'];
            $title = $this->db->real_escape_string($_POST['title'] ?? '');
            if (!empty($title)) {
                $description = $this->db->real_escape_string($_POST['description'] ?? '');
                $location = $this->db->real_escape_string($_POST['location'] ?? '');
                $priority = (int)($_POST['priority'] ?? 4);
                $dueDate = !empty($_POST['due_date']) ? "'" . $this->db->real_escape_string($_POST['due_date']) . "'" : "NULL";
                
                // Check if user owns task
                $taskRes = $this->db->query("SELECT * FROM todo_tasks WHERE id = $taskId AND user_id = {$this->userId}");
                if ($taskRes && $taskRes->num_rows > 0) {
                    
                    $attachmentQuery = "";
                    if (!empty($_FILES['attachment']['name'])) {
                        $uploadDir = __DIR__ . '/../../public/uploads/todo/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['attachment']['name']));
                        $targetPath = $uploadDir . $fileName;
                        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                            $attachmentPath = "'" . $this->db->real_escape_string('public/uploads/todo/' . $fileName) . "'";
                            $attachmentQuery = ", attachment = $attachmentPath";
                        }
                    }
                    
                    $this->db->query("UPDATE todo_tasks SET title = '$title', description = '$description', priority = $priority, due_date = $dueDate, location = '$location' $attachmentQuery WHERE id = $taskId");
                    
                    // Update recurrence
                    if (!empty($_POST['repeat_type'])) {
                        $repeatType = $this->db->real_escape_string($_POST['repeat_type']);
                        $this->db->query("INSERT INTO todo_task_recurrences (todo_task_id, repeat_type) VALUES ($taskId, '$repeatType') ON DUPLICATE KEY UPDATE repeat_type = '$repeatType'");
                    } else {
                        $this->db->query("DELETE FROM todo_task_recurrences WHERE todo_task_id = $taskId");
                    }
                    
                    // Update Reminders
                    if (isset($_POST['max_reminders'])) {
                        $max = (int)$_POST['max_reminders'];
                        if ($max > 0) {
                            $triggerTime = !empty($_POST['due_date']) ? "'" . $this->db->real_escape_string($_POST['due_date']) . " 09:00:00'" : "NOW()";
                            $this->db->query("DELETE FROM todo_task_reminders WHERE todo_task_id = $taskId");
                            $this->db->query("INSERT INTO todo_task_reminders (todo_task_id, trigger_time, max_reminders) VALUES ($taskId, $triggerTime, $max)");
                        } else {
                            $this->db->query("DELETE FROM todo_task_reminders WHERE todo_task_id = $taskId");
                        }
                    }
                }
            }
        }
        $view = $_POST['redirect_view'] ?? 'inbox';
        header("Location: " . $GLOBALS['base_url'] . "/todo?view=" . urlencode($view));
        exit();
    }

    public function cron()
    {
        // 1. Find all active reminders where trigger_time <= NOW()
        $query = "
            SELECT r.id as reminder_id, r.max_reminders, r.reminders_sent_count, r.trigger_time,
                   t.id as task_id, t.title, t.user_id, t.due_date,
                   u.employee_id, e.email, e.name as employee_name
            FROM todo_task_reminders r
            JOIN todo_tasks t ON r.todo_task_id = t.id
            JOIN users u ON t.user_id = u.id
            JOIN employees e ON u.employee_id = e.id
            WHERE r.is_active = 1 
              AND r.trigger_time <= NOW() 
              AND r.reminders_sent_count < r.max_reminders
              AND t.status = 'pending'
        ";
        
        $res = $this->db->query($query);
        if ($res && $res->num_rows > 0) {
            require_once __DIR__ . '/../core/TodoMailService.php';
            $mailService = new TodoMailService($this->db);

            while ($row = $res->fetch_assoc()) {
                // Send Email
                $subject = "Task Reminder: " . $row['title'];
                $body = "Hello " . $row['employee_name'] . ",<br><br>";
                $body .= "This is a reminder for your task: <strong>" . $row['title'] . "</strong>.<br>";
                if ($row['due_date']) {
                    $body .= "Due Date: " . date('d M, Y h:i A', strtotime($row['due_date'])) . "<br>";
                }
                $body .= "<br>Please check your ERP Todo List.";
                
                if (!empty($row['email'])) {
                    $mailService->send($row['email'], $subject, $body);
                }

                // Insert into ERP notifications table
                $msg = "Reminder for task: " . $this->db->real_escape_string($row['title']);
                $this->db->query("INSERT INTO notifications (user_id, message) VALUES ({$row['user_id']}, '$msg')");

                // Update Reminder Logic
                $newSentCount = $row['reminders_sent_count'] + 1;
                $maxReminders = (int)$row['max_reminders'];
                
                if ($newSentCount >= $maxReminders) {
                    $this->db->query("UPDATE todo_task_reminders SET reminders_sent_count = $newSentCount, is_active = 0 WHERE id = {$row['reminder_id']}");
                } else {
                    // Divide 24 hours by max_reminders
                    $intervalHours = 24 / $maxReminders;
                    $intervalMinutes = round($intervalHours * 60);
                    $this->db->query("UPDATE todo_task_reminders SET reminders_sent_count = $newSentCount, trigger_time = DATE_ADD(trigger_time, INTERVAL $intervalMinutes MINUTE) WHERE id = {$row['reminder_id']}");
                }
            }
        }
        
        echo "Cron Executed Successfully at " . date('Y-m-d H:i:s');
    }

    public function testEmail()
    {
        require_once __DIR__ . '/../core/TodoMailService.php';
        $mailService = new TodoMailService($this->db);
        
        $body = "
        <div style='font-family: Arial, sans-serif; padding: 20px;'>
            <h2 style='color: #d1453b;'>Test Task Reminder</h2>
            <p>Hello Farzana,</p>
            <p>This is a test reminder from the new Todo List module to verify the email functionality.</p>
            <p>If you received this, the queue and SMTP configuration are working correctly!</p>
        </div>";

        if ($mailService->send('farzana.tphl13@gmail.com', 'Test Task Reminder', $body)) {
            echo "Test email queued for farzana.tphl13@gmail.com! Background processor triggered.";
        } else {
            echo "Failed to queue test email.";
        }
    }
}
