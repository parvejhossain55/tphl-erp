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
        // Auto-process any pending reminders that reached their trigger time
        $this->processPendingReminders();

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
                   tr.repeat_type, tr.repeat_until, r.max_reminders, r.trigger_time as reminder_trigger_time
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
                    
                    // Handle Recurrence & Date Range (From Date to To Date)
                    if (!empty($_POST['repeat_type'])) {
                        $repeatType = $this->db->real_escape_string($_POST['repeat_type']);
                        $repeatUntil = !empty($_POST['repeat_until']) ? "'" . $this->db->real_escape_string($_POST['repeat_until']) . "'" : "NULL";
                        $this->db->query("INSERT INTO todo_task_recurrences (todo_task_id, repeat_type, repeat_until) VALUES ($taskId, '$repeatType', $repeatUntil)");
                    }
                    
                    // Handle Reminders (Custom Date/Time or default to Due Date 09:00 AM)
                    if (!empty($_POST['max_reminders']) && (int)$_POST['max_reminders'] > 0) {
                        $max = (int)$_POST['max_reminders'];
                        if (!empty($_POST['reminder_date_time'])) {
                            $triggerTime = "'" . date('Y-m-d H:i:s', strtotime($_POST['reminder_date_time'])) . "'";
                        } elseif (!empty($_POST['due_date'])) {
                            $triggerTime = "'" . $this->db->real_escape_string($_POST['due_date']) . " 09:00:00'";
                        } else {
                            $triggerTime = "NOW()";
                        }
                        $this->db->query("INSERT INTO todo_task_reminders (todo_task_id, trigger_time, max_reminders, reminders_sent_count, is_active) 
                                          VALUES ($taskId, $triggerTime, $max, 0, 1)");
                    }
                }
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/todo");
        exit();
    }

    private function createNextRecurrenceOccurrence($taskId, $task, $rec)
    {
        $nextDueDate = null;
        $triggerDate = 'NOW()';
        if (!empty($task['due_date'])) {
            $dueDateObj = new DateTime($task['due_date']);
            $now = new DateTime('today');

            if ($rec['repeat_type'] === 'daily' || $rec['repeat_type'] === 'custom') {
                $dueDateObj->modify('+1 day');
                if ($dueDateObj < $now) {
                    $dueDateObj = new DateTime('tomorrow');
                }
            } elseif ($rec['repeat_type'] === 'weekly') {
                $dueDateObj->modify('+1 week');
                while ($dueDateObj < $now) {
                    $dueDateObj->modify('+1 week');
                }
            } elseif ($rec['repeat_type'] === 'monthly') {
                $dueDateObj->modify('+1 month');
                while ($dueDateObj < $now) {
                    $dueDateObj->modify('+1 month');
                }
            }

            // Check if repeat_until is reached
            if (!empty($rec['repeat_until'])) {
                $untilDate = new DateTime($rec['repeat_until'] . ' 23:59:59');
                if ($dueDateObj > $untilDate) {
                    return null; // Stop repeating as end date reached
                }
            }

            $formattedDate = $dueDateObj->format('Y-m-d H:i:s');
            $nextDueDate = "'" . $formattedDate . "'";
            $triggerDate = "'" . $dueDateObj->format('Y-m-d 09:00:00') . "'";
        }

        $title = $this->db->real_escape_string($task['title']);
        $desc = $this->db->real_escape_string($task['description'] ?? '');
        $location = $this->db->real_escape_string($task['location'] ?? '');
        $projectId = !empty($task['todo_project_id']) ? (int)$task['todo_project_id'] : "NULL";
        $priority = (int)$task['priority'];
        $attachment = !empty($task['attachment']) ? "'" . $this->db->real_escape_string($task['attachment']) . "'" : "NULL";

        $insertTaskSql = "INSERT INTO todo_tasks (user_id, todo_project_id, title, description, priority, due_date, location, attachment) 
                          VALUES ({$this->userId}, $projectId, '$title', '$desc', $priority, " . ($nextDueDate ?? 'NULL') . ", '$location', $attachment)";
        
        if ($this->db->query($insertTaskSql)) {
            $newTaskId = $this->db->insert_id;

            // 1. Copy Recurrence with repeat_until
            $repeatType = $this->db->real_escape_string($rec['repeat_type']);
            $repeatUntil = !empty($rec['repeat_until']) ? "'" . $this->db->real_escape_string($rec['repeat_until']) . "'" : "NULL";
            $this->db->query("INSERT INTO todo_task_recurrences (todo_task_id, repeat_type, repeat_until) VALUES ($newTaskId, '$repeatType', $repeatUntil)");

            // 2. Copy Reminders (carry over max_reminders with fresh counter)
            $remRes = $this->db->query("SELECT * FROM todo_task_reminders WHERE todo_task_id = $taskId LIMIT 1");
            if ($remRes && $rem = $remRes->fetch_assoc()) {
                $maxReminders = (int)$rem['max_reminders'];
                if ($maxReminders > 0) {
                    $this->db->query("INSERT INTO todo_task_reminders (todo_task_id, trigger_time, max_reminders, reminders_sent_count, is_active) 
                                      VALUES ($newTaskId, $triggerDate, $maxReminders, 0, 1)");
                }
            }

            // 3. Copy Labels
            $lblRes = $this->db->query("SELECT todo_label_id FROM todo_task_labels WHERE todo_task_id = $taskId");
            if ($lblRes) {
                while ($lbl = $lblRes->fetch_assoc()) {
                    $lId = (int)$lbl['todo_label_id'];
                    $this->db->query("INSERT IGNORE INTO todo_task_labels (todo_task_id, todo_label_id) VALUES ($newTaskId, $lId)");
                }
            }

            return $newTaskId;
        }

        return null;
    }

    public function complete()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])) {
            $taskId = (int)$_POST['task_id'];
            
            // Fetch task details
            $taskRes = $this->db->query("SELECT * FROM todo_tasks WHERE id = $taskId AND user_id = {$this->userId}");
            if ($taskRes && $task = $taskRes->fetch_assoc()) {
                
                // Check recurrence and spawn next occurrence
                $recRes = $this->db->query("SELECT * FROM todo_task_recurrences WHERE todo_task_id = $taskId");
                if ($recRes && $rec = $recRes->fetch_assoc()) {
                    $this->createNextRecurrenceOccurrence($taskId, $task, $rec);
                }
                
                // Mark current task completed
                $this->db->query("UPDATE todo_tasks SET status = 'completed' WHERE id = $taskId");
                
                // Deactivate reminders for completed task
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
                        $this->createNextRecurrenceOccurrence($taskId, $task, $rec);
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
                    
                    // Update recurrence & repeat_until
                    if (!empty($_POST['repeat_type'])) {
                        $repeatType = $this->db->real_escape_string($_POST['repeat_type']);
                        $repeatUntil = !empty($_POST['repeat_until']) ? "'" . $this->db->real_escape_string($_POST['repeat_until']) . "'" : "NULL";
                        $this->db->query("INSERT INTO todo_task_recurrences (todo_task_id, repeat_type, repeat_until) VALUES ($taskId, '$repeatType', $repeatUntil) ON DUPLICATE KEY UPDATE repeat_type = '$repeatType', repeat_until = $repeatUntil");
                    } else {
                        $this->db->query("DELETE FROM todo_task_recurrences WHERE todo_task_id = $taskId");
                    }
                    
                    // Update Reminders (Custom Date/Time or default to Due Date 09:00 AM)
                    if (isset($_POST['max_reminders'])) {
                        $max = (int)$_POST['max_reminders'];
                        if ($max > 0) {
                            if (!empty($_POST['reminder_date_time'])) {
                                $triggerTime = "'" . date('Y-m-d H:i:s', strtotime($_POST['reminder_date_time'])) . "'";
                            } elseif (!empty($_POST['due_date'])) {
                                $triggerTime = "'" . $this->db->real_escape_string($_POST['due_date']) . " 09:00:00'";
                            } else {
                                $triggerTime = "NOW()";
                            }
                            $this->db->query("DELETE FROM todo_task_reminders WHERE todo_task_id = $taskId");
                            $this->db->query("INSERT INTO todo_task_reminders (todo_task_id, trigger_time, max_reminders, reminders_sent_count, is_active) VALUES ($taskId, $triggerTime, $max, 0, 1)");
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
        $processed = $this->processPendingReminders();
        echo "Cron Executed Successfully at " . date('Y-m-d H:i:s') . ". Processed {$processed} reminders.";
    }

    private function processPendingReminders(): int
    {
        // Find all active reminders where trigger_time <= NOW()
        $query = "
            SELECT r.id as reminder_id, r.max_reminders, r.reminders_sent_count, r.trigger_time,
                   t.id as task_id, t.title, t.description, t.priority, t.location, t.user_id, t.due_date,
                   u.email as user_email, u.full_name as user_full_name,
                   e.email as employee_email, e.full_name as employee_name
            FROM todo_task_reminders r
            JOIN todo_tasks t ON r.todo_task_id = t.id
            JOIN users u ON t.user_id = u.id
            LEFT JOIN employees e ON u.employee_id = e.id
            WHERE r.is_active = 1 
              AND r.trigger_time <= NOW() 
              AND r.reminders_sent_count < r.max_reminders
              AND t.status = 'pending'
        ";
        
        $res = $this->db->query($query);
        $count = 0;
        if ($res && $res->num_rows > 0) {
            require_once __DIR__ . '/../core/TodoMailService.php';
            $mailService = new TodoMailService($this->db);

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
            $base   = rtrim($GLOBALS['base_url'] ?? '/TPHL-ERP', '/');
            $todoLink = $scheme . '://' . $host . $base . '/todo';

            while ($row = $res->fetch_assoc()) {
                $recipientEmail = !empty($row['employee_email']) ? $row['employee_email'] : $row['user_email'];
                $recipientName = !empty($row['employee_name']) ? $row['employee_name'] : (!empty($row['user_full_name']) ? $row['user_full_name'] : 'User');
                $newSentCount = (int)$row['reminders_sent_count'] + 1;
                $maxReminders = (int)$row['max_reminders'];

                // Send Professional Email
                $subject = "Task Reminder ({$newSentCount}/{$maxReminders}): " . $row['title'];
                
                $body = "
                <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; padding: 35px 15px;'>
                    <div style='max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                        <div style='background: linear-gradient(135deg, #16a34a, #15803d); padding: 22px 28px;'>
                            <div style='font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.8); margin-bottom: 4px;'>TPHL ERP &middot; Task Management</div>
                            <h2 style='margin: 0; color: #ffffff; font-size: 20px; font-weight: 700;'>Task Reminder Notification</h2>
                        </div>
                        <div style='padding: 28px;'>
                            <p style='color: #334155; font-size: 15px; margin: 0 0 14px;'>Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
                            <p style='color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px;'>This is a scheduled reminder (<strong>Reminder {$newSentCount} of {$maxReminders}</strong>) for your pending task:</p>
                            
                            <div style='background-color: #f1f5f9; border-left: 4px solid #16a34a; border-radius: 6px; padding: 18px 20px; margin-bottom: 24px;'>
                                <div style='font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 8px;'>" . htmlspecialchars($row['title']) . "</div>";
                                if (!empty($row['description'])) {
                                    $body .= "<div style='font-size: 13.5px; color: #475569; margin-bottom: 10px;'>" . nl2br(htmlspecialchars($row['description'])) . "</div>";
                                }
                                $body .= "<div style='font-size: 12.5px; color: #64748b;'>";
                                if (!empty($row['due_date'])) {
                                    $body .= "<span style='margin-right: 15px;'>📅 <strong>Due:</strong> " . date('d M Y', strtotime($row['due_date'])) . "</span>";
                                }
                                if (!empty($row['location'])) {
                                    $body .= "<span>📍 <strong>Location:</strong> " . htmlspecialchars($row['location']) . "</span>";
                                }
                                $body .= "</div>
                            </div>
                            
                            <div style='text-align: center; margin-top: 25px; margin-bottom: 10px;'>
                                <a href='{$todoLink}' style='background-color: #16a34a; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>View Task in Todo List &rarr;</a>
                            </div>
                        </div>
                        <div style='background-color: #f8fafc; padding: 14px 28px; text-align: center; border-top: 1px solid #f1f5f9;'>
                            <p style='margin: 0; font-size: 11.5px; color: #94a3b8;'>This is an automated notification from TPHL ERP. Please do not reply directly.</p>
                        </div>
                    </div>
                </div>";
                
                if (!empty($recipientEmail)) {
                    $mailService->send($recipientEmail, $subject, $body);
                }

                // Insert into ERP notifications table
                $msg = "Reminder for task: " . $this->db->real_escape_string($row['title']);
                $this->db->query("INSERT INTO notifications (user_id, message) VALUES ({$row['user_id']}, '$msg')");

                // Update Reminder Logic
                if ($newSentCount >= $maxReminders) {
                    $this->db->query("UPDATE todo_task_reminders SET reminders_sent_count = $newSentCount, is_active = 0 WHERE id = {$row['reminder_id']}");
                } else {
                    $intervalHours = 24 / $maxReminders;
                    $intervalMinutes = round($intervalHours * 60);
                    $this->db->query("UPDATE todo_task_reminders SET reminders_sent_count = $newSentCount, trigger_time = DATE_ADD(trigger_time, INTERVAL $intervalMinutes MINUTE) WHERE id = {$row['reminder_id']}");
                }
                $count++;
            }
        }
        return $count;
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
