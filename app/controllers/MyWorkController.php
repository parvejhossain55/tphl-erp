<?php

class MyWorkController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = $GLOBALS['db'] ?? null;
    }

    /**
     * Single-Page Workspace: handles both initial page load and seamless AJAX updates
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            if (!empty($_GET['ajax'])) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/login");
            exit;
        }

        $userId = (int) $_SESSION['user']['id'];
        $user = $_SESSION['user'] ?? [];
        $baseUrl = $GLOBALS['base_url'] ?? '';

        // Active Scope Tab
        $validTabs = ['all', 'assigned_to_me', 'assigned_by_me', 'following', 'reviewer', 'realestate_project', 'self_initiated', 'recurring'];
        $tab = $_GET['tab'] ?? 'all';
        if (!in_array($tab, $validTabs, true)) {
            $tab = 'all';
        }

        // Search & Filters
        $search = trim($_GET['search'] ?? '');
        $priorityFilter = trim($_GET['priority'] ?? '');
        $statusFilter = trim($_GET['status'] ?? '');
        $metricFilter = trim($_GET['metric'] ?? ''); // overdue, due_today, next7, review

        // When a metric card is clicked, it dictates the status filter (reset dropdown conflict)
        if (!empty($metricFilter)) {
            $statusFilter = '';
        }

        // Scope per tab. mentioned_user_ids (reviewer) / cc_user_ids are stored as JSON ("[12,15]"); strip brackets so FIND_IN_SET works (also for legacy CSV).
        $mentionSql = "FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(t.mentioned_user_ids,'[',''),']',''),' ',''))";
        $ccSql = "FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(t.cc_user_ids,'[',''),']',''),' ',''))";
        $collabSql = "FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(t.collaborator_user_ids,'[',''),']',''),' ',''))";
        // "Assigned to me" = I am the owner or a collaborator
        $assignedToMeSql = "(t.user_id = ? OR {$collabSql})";
        $allScopeSql = "(t.user_id = ? OR t.assigned_by = ? OR {$collabSql} OR {$mentionSql} OR {$ccSql})";
        $allScopeParams = array_fill(0, 5, $userId);
        if ($tab === 'all') {
            $scopeSql = $allScopeSql;
            $scopeParams = $allScopeParams;
        } elseif ($tab === 'following') {
            $scopeSql = $ccSql;
            $scopeParams = [$userId];
        } elseif ($tab === 'reviewer') {
            $scopeSql = $mentionSql;
            $scopeParams = [$userId];
        } elseif ($tab === 'realestate_project') {
            +
+            $scopeParams = [$userId];
            $scopeParams = [$userId];
        } elseif ($tab === 'self_initiated') {
            $scopeSql = "(t.user_id = ? AND t.assigned_by = ?)";
            $scopeParams = [$userId, $userId];
        } elseif ($tab === 'recurring') {
            +
+            $scopeParams = [$userId];
            $scopeParams = [$userId];
        } elseif ($tab === 'assigned_by_me') {
            $scopeSql = "(t.assigned_by = ? AND t.user_id != ?)";
            $scopeParams = [$userId, $userId];
        } else {
+
+            $scopeParams = [$userId];
            $scopeParams = [$userId];
        }
        $scopeTypes = str_repeat('i', count($scopeParams));

        // Metric cards: counted inside the active tab's scope
        $metricCond = [
            'overdue'   => "t.status != 'completed' AND t.deadline IS NOT NULL AND t.deadline < NOW()",
            'due_today' => "t.status != 'completed' AND t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE()",
            'next7'     => "t.status != 'completed' AND t.deadline IS NOT NULL AND DATE(t.deadline) > CURDATE() AND DATE(t.deadline) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)",
            'review'    => "t.status != 'completed' AND (t.progress >= 80 OR t.employee_accept = 1)",
        ];
        $countMetric = function (string $cond) use ($scopeSql, $scopeParams, $scopeTypes): int {
            $st = $this->db->prepare("SELECT COUNT(*) FROM tasks t WHERE {$scopeSql} AND {$cond}");
            $st->bind_param($scopeTypes, ...$scopeParams);
            $st->execute();
            return (int) $st->get_result()->fetch_row()[0];
        };
        $overdueCount = $countMetric($metricCond['overdue']);
        $dueTodayCount = $countMetric($metricCond['due_today']);
        $next7DaysCount = $countMetric($metricCond['next7']);
        $waitingReviewCount = $countMetric($metricCond['review']);

        $stmtTotals = $this->db->prepare("
            SELECT 
                SUM(CASE WHEN t.status != 'completed' THEN 1 ELSE 0 END) AS open_count,
                SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) AS completed_count
            FROM tasks t
            WHERE {$scopeSql}
        ");
        $stmtTotals->bind_param($scopeTypes, ...$scopeParams);
        $stmtTotals->execute();
        $totals = $stmtTotals->get_result()->fetch_assoc();
        $openTasksCount = (int) ($totals['open_count'] ?? 0);
        $completedTasksCount = (int) ($totals['completed_count'] ?? 0);

        // 5b. Summary cards (scope = active tab)
        $stmtAll = $this->db->prepare("
            SELECT 
                COUNT(*) AS total,
                COALESCE(SUM(t.status = 'in_progress'), 0) AS in_progress,
                COALESCE(SUM(t.status = 'completed'), 0) AS completed,
                COALESCE(SUM(t.status != 'completed' AND t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE()), 0) AS deadline_today,
                COALESCE(SUM(t.status != 'completed' AND t.deadline IS NOT NULL AND t.deadline < NOW()), 0) AS deadline_missed
            FROM tasks t
            WHERE {$scopeSql}
        ");
        $stmtAll->bind_param($scopeTypes, ...$scopeParams);
        $stmtAll->execute();
        $allStats = array_map('intval', $stmtAll->get_result()->fetch_assoc());
        $allStats['completion_rate'] = $allStats['total'] > 0 ? (int) round($allStats['completed'] * 100 / $allStats['total']) : 0;

        // 6. Build Query for Tasks List
        $where = [];
        $params = [];
        $types = "";

        if (isset($metricCond[$metricFilter])) {
            // Card filter: matches exactly what was counted (same tab scope)
            $where[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
            $types .= $scopeTypes;
            $where[] = $metricCond[$metricFilter];
        } elseif (in_array($metricFilter, ['all_total', 'all_in_progress', 'all_completed', 'all_deadline_today', 'all_deadline_missed'], true)) {
            // Summary cards: same scope as the card counts
            $where[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
            $types .= $scopeTypes;
            $metricSql = [
                'all_in_progress'    => "t.status = 'in_progress'",
                'all_completed'      => "t.status = 'completed'",
                'all_deadline_today' => "t.status != 'completed' AND t.deadline IS NOT NULL AND DATE(t.deadline) = CURDATE()",
                'all_deadline_missed' => "t.status != 'completed' AND t.deadline IS NOT NULL AND t.deadline < NOW()",
            ];
            if (isset($metricSql[$metricFilter])) {
                $where[] = $metricSql[$metricFilter];
            }
        } else {
            // Standard Tab Scope
            $where[] = $scopeSql;
            $params = array_merge($params, $scopeParams);
            $types .= $scopeTypes;
        }

        // Search
        if ($search !== '') {
            if (is_numeric($search)) {
                $where[] = "(t.id = ? OR t.title LIKE ?)";
                $params[] = (int) $search;
                $params[] = '%' . $search . '%';
                $types .= "is";
            } else {
                $where[] = "t.title LIKE ?";
                $params[] = '%' . $search . '%';
                $types .= "s";
            }
        }

        // Priority filter
        if ($priorityFilter !== '' && in_array($priorityFilter, ['low', 'medium', 'high', 'urgent'], true)) {
            $where[] = "t.priority = ?";
            $params[] = $priorityFilter;
            $types .= "s";
        }

        // Status filter (only applied if not overridden by a metric card)
        if ($statusFilter !== '') {
            if ($statusFilter === 'open') {
                $where[] = "t.status != 'completed'";
            } elseif ($statusFilter === 'overdue') {
                $where[] = "t.status != 'completed' AND t.deadline IS NOT NULL AND t.deadline < NOW()";
            } elseif (in_array($statusFilter, ['pending', 'in_progress', 'completed', 'expired'], true)) {
                $where[] = "t.status = ?";
                $params[] = $statusFilter;
                $types .= "s";
            }
        }

        $whereSql = implode(" AND ", $where);

        $sql = "
            SELECT 
                t.*,
                tp.name AS project_name,
                tp.color_code AS project_color,
                u_assigned.full_name AS assignee_name,
                u_assigned.photo AS assignee_photo,
                des_assigned.name AS assignee_designation,
                u_by.full_name AS assigned_by_name,
                u_by.photo AS assigned_by_photo,
                des_by.name AS assigned_by_designation
            FROM tasks t
            LEFT JOIN todo_projects tp ON t.todo_project_id = tp.id
            LEFT JOIN users u_assigned ON t.user_id = u_assigned.id
            LEFT JOIN designations des_assigned ON u_assigned.designation_id = des_assigned.id
            LEFT JOIN users u_by ON t.assigned_by = u_by.id
            LEFT JOIN designations des_by ON u_by.designation_id = des_by.id
            WHERE {$whereSql}
            ORDER BY 
                CASE 
                    WHEN t.status != 'completed' AND t.deadline IS NOT NULL AND t.deadline < NOW() THEN 1
                    WHEN t.status != 'completed' AND DATE(t.deadline) = CURDATE() THEN 2
                    WHEN t.status != 'completed' THEN 3
                    ELSE 4
                END,
                t.deadline ASC,
                t.id DESC
            LIMIT 50
        ";

        $stmtTasks = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmtTasks->bind_param($types, ...$params);
        }
        $stmtTasks->execute();
        $tasks = $stmtTasks->get_result()->fetch_all(MYSQLI_ASSOC);

        // 7. Focus Panel: Needs Review (Tasks where the logged-in user is a Reviewer and task is completed, latest completed first)
        $stmtReviewList = $this->db->prepare("
            SELECT 
                t.*,
                u.full_name AS employee_name,
                u.photo AS employee_photo,
                des.name AS employee_designation
            FROM tasks t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN designations des ON u.designation_id = des.id
            WHERE FIND_IN_SET(?, REPLACE(REPLACE(REPLACE(t.mentioned_user_ids, '[', ''), ']', ''), ' ', ''))
              AND t.status = 'completed'
            ORDER BY COALESCE(t.updated_at, t.created_at) DESC
            LIMIT 2
        ");
        $stmtReviewList->bind_param("i", $userId);
        $stmtReviewList->execute();
        $reviewTasks = $stmtReviewList->get_result()->fetch_all(MYSQLI_ASSOC);

        // 8. Focus Panel: Blocked or Attention
        $stmtBlocked = $this->db->prepare("
            SELECT 
                t.*,
                u_by.full_name AS assigned_by_name,
                des_by.name AS assigned_by_designation
            FROM tasks t
            LEFT JOIN users u_by ON t.assigned_by = u_by.id
            LEFT JOIN designations des_by ON u_by.designation_id = des_by.id
            WHERE t.user_id = ?
              AND t.status != 'completed'
            ORDER BY 
                (CASE WHEN t.remarks LIKE '%block%' OR t.priority = 'urgent' THEN 1 ELSE 2 END),
                (CASE WHEN t.deadline < NOW() THEN 1 ELSE 2 END),
                t.deadline ASC
            LIMIT 1
        ");
        $stmtBlocked->bind_param("i", $userId);
        $stmtBlocked->execute();
        $blockedTask = $stmtBlocked->get_result()->fetch_assoc();

        // If AJAX request: Return JSON response without reloading page
        $isAjax = !empty($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
        if ($isAjax) {
            header('Content-Type: application/json');
            $tableHtml = $this->renderTaskRowsHtml($tasks, $tab, $userId, $baseUrl);
            echo json_encode([
                'status' => 'success',
                'all_stats' => $allStats,
                'counts' => [
                    'overdue'        => $overdueCount,
                    'due_today'      => $dueTodayCount,
                    'next7'          => $next7DaysCount,
                    'waiting_review' => $waitingReviewCount,
                    'open'           => $openTasksCount,
                    'completed'      => $completedTasksCount,
                    'showing'        => count($tasks)
                ],
                'table_html' => $tableHtml,
                'footer_info' => 'Showing ' . count($tasks) . ' task' . (count($tasks) === 1 ? '' : 's') . ' · Sorted by deadline'
            ]);
            exit;
        }

        // Fetch dropdown data for "Create task" slide drawer
        $usersList = [];
        $resUsers = $this->db->query("
            SELECT
                u.id, 
                u.full_name,
                des.name AS designation
            FROM users AS u
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN designations des ON u.designation_id = des.id
            WHERE (e.employee_status IS NULL OR (e.deleted_at IS NULL AND LOWER(TRIM(e.employee_status)) NOT IN ('resigned', 'inactive', 'terminated')))
              AND (e.employee_type IS NULL OR LOWER(TRIM(e.employee_type)) NOT IN ('resigned', 'inactive', 'terminated'))
            ORDER BY u.full_name ASC
        ");
        if ($resUsers) {
            $usersList = $resUsers->fetch_all(MYSQLI_ASSOC);
        }

        $todoProjectsList = [];
        $resTodoProj = $this->db->query("SELECT id, name FROM todo_projects ORDER BY name ASC");
        if ($resTodoProj) {
            $todoProjectsList = $resTodoProj->fetch_all(MYSQLI_ASSOC);
        }

        $departmentsList = [];
        $resDept = $this->db->query("SELECT id, name FROM departments WHERE status = 1 ORDER BY name ASC");
        if ($resDept) {
            $departmentsList = $resDept->fetch_all(MYSQLI_ASSOC);
        }

        $realEstateProjectsList = [];
        $resProj = $this->db->query("SELECT id, name FROM projects ORDER BY name ASC");
        if ($resProj) {
            $realEstateProjectsList = $resProj->fetch_all(MYSQLI_ASSOC);
        }

        // Full Page View
        $this->view("my-work/index", [
            "current_route"          => "my-work-dashboard",
            "page_title"             => "My Work - Personal Workspace",
            "user"                   => $user,
            "userId"                 => $userId,
            "tab"                    => $tab,
            "search"                 => $search,
            "priorityFilter"         => $priorityFilter,
            "statusFilter"           => $statusFilter,
            "metricFilter"           => $metricFilter,
            "overdueCount"           => $overdueCount,
            "allStats"               => $allStats,
            "dueTodayCount"          => $dueTodayCount,
            "next7DaysCount"         => $next7DaysCount,
            "waitingReviewCount"     => $waitingReviewCount,
            "openTasksCount"         => $openTasksCount,
            "completedTasksCount"    => $completedTasksCount,
            "tasks"                  => $tasks,
            "reviewTasks"            => $reviewTasks,
            "blockedTask"            => $blockedTask,
            "usersList"              => $usersList,
            "todoProjectsList"       => $todoProjectsList,
            "departmentsList"        => $departmentsList,
            "realEstateProjectsList" => $realEstateProjectsList
        ]);
    }

    /**
     * Helper to render task rows HTML for seamless AJAX
     */
    private function renderTaskRowsHtml($tasks, $tab, $userId, $baseUrl)
    {
        if (empty($tasks)) {
            return '<tr>
                <td colspan="6">
                    <div class="mw-empty-state">
                        <i class="fa-regular fa-clipboard d-block"></i>
                        <div>No tasks found for this view.</div>
                    </div>
                </td>
            </tr>';
        }

        $html = '';
        foreach ($tasks as $index => $t) {
            $taskId = (int) $t['id'];
            $isCompleted = ($t['status'] === 'completed');
            $isBlocked = (!empty($t['remarks']) && stripos($t['remarks'], 'block') !== false) || stripos($t['title'], 'block') !== false;

            $isOverdue = false;
            $duePrimary = 'No deadline';
            $dueSub = '';

            if (!empty($t['deadline'])) {
                $deadlineTime = strtotime($t['deadline']);
                $now = time();

                if (!$isCompleted && $deadlineTime < $now) {
                    $isOverdue = true;
                    $diffDays = (int) ceil(($now - $deadlineTime) / 86400);
                    $duePrimary = date('j M', $deadlineTime);
                    $dueSub = ($diffDays <= 1) ? '1 day overdue' : "{$diffDays} days overdue";
                } elseif (date('Y-m-d', $deadlineTime) === date('Y-m-d')) {
                    $duePrimary = date('j M', $deadlineTime);
                    $dueSub = 'Today · ' . date('g:i A', $deadlineTime);
                } else {
                    $duePrimary = date('j M', $deadlineTime);
                    $dueSub = date('D · g:i A', $deadlineTime);
                }
            }

            $statusClass = 'todo';
            $statusLabel = 'To do';
            if ($t['status'] === 'in_progress') {
                $statusClass = 'in-progress';
                $statusLabel = 'In progress';
            } elseif ($t['status'] === 'completed') {
                $statusClass = 'completed';
                $statusLabel = 'Completed';
            } elseif ($t['status'] === 'expired') {
                $statusClass = 'expired';
                $statusLabel = 'Expired';
            }
            if (!$isCompleted && (int)$t['progress'] >= 80) {
                $statusClass = 'in-review';
                $statusLabel = 'In review';
            }

            if ($tab === 'assigned_by_me') {
                $nextActionName = !empty($t['assignee_name']) ? htmlspecialchars($t['assignee_name']) : 'Assignee';
                $nextActionRole = !empty($t['assignee_designation']) ? ' · ' . htmlspecialchars($t['assignee_designation']) : '';
            } else {
                if ((int)$t['assigned_by'] === (int)$userId) {
                    $nextActionName = 'You';
                    $nextActionRole = '';
                } else {
                    $nextActionName = !empty($t['assigned_by_name']) ? htmlspecialchars($t['assigned_by_name']) : 'You';
                    $nextActionRole = !empty($t['assigned_by_designation']) ? ' · ' . htmlspecialchars($t['assigned_by_designation']) : '';
                }
            }

            $isHighlighted = ($index === 1 && $isBlocked) || ($index === 0 && $isOverdue);
            $rowClass = $isHighlighted ? 'row-selected' : '';
            $checkedAttr = $isCompleted ? 'checked' : '';
            $blockedBadge = $isBlocked ? '<span class="mw-badge-blocked">Blocked</span>' : '';
            $titleSafe = htmlspecialchars($t['title']);
            $projectSafe = htmlspecialchars($t['project_name'] ?? 'General');
            $createdMonth = date('F', strtotime($t['created_at']));
            $prioritySafe = htmlspecialchars(ucfirst($t['priority'] ?? 'Normal'));
            $priorityClass = ($t['priority'] === 'urgent') ? 'urgent' : '';
            $dueClass = $isOverdue ? 'overdue' : '';
            $dueSubHtml = !empty($dueSub) ? "<div class=\"mw-due-sub {$dueClass}\">{$dueSub}</div>" : '';

            $isOwner = ((int)$t['assigned_by'] === (int)$userId);
            $actionButtonsHtml = '';
            if ($isOwner) {
                $actionButtonsHtml = "
                    <button type=\"button\" class=\"mw-action-btn edit\" onclick=\"openEditTaskDrawer({$taskId}, event)\" title=\"Edit task\">
                        <i class=\"fa-solid fa-pen\"></i>
                    </button>
                    <button type=\"button\" class=\"mw-action-btn delete\" onclick=\"deleteMyWorkTask({$taskId}, this)\" title=\"Delete task\">
                        <i class=\"fa-solid fa-trash-can\"></i>
                    </button>
                ";
            }

            $html .= "<tr class=\"{$rowClass}\" data-task-id=\"{$taskId}\">
                <td>
                    <div class=\"d-flex align-items-start\">
                        <input type=\"checkbox\" class=\"mw-task-check\" {$checkedAttr} disabled>
                        <div>
                            <a href=\"javascript:void(0);\" onclick=\"openTaskDetail({$taskId}); return false;\" class=\"mw-task-name\">
                                {$titleSafe}
                            </a>
                            {$blockedBadge}
                            <div class=\"mw-task-sub\">
                                T-{$taskId} · {$projectSafe} · {$createdMonth}
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class=\"mw-priority-text {$priorityClass}\">
                        {$prioritySafe}
                    </span>
                </td>
                <td>
                    <span class=\"mw-status-pill {$statusClass}\">
                        {$statusLabel}
                    </span>
                </td>
                <td>
                    <div class=\"mw-due-date {$dueClass}\">
                        {$duePrimary}
                    </div>
                    {$dueSubHtml}
                </td>
                <td>
                    <span class=\"mw-assignee\">
                        {$nextActionName}{$nextActionRole}
                    </span>
                </td>
                <td style=\"text-align: right;\">
                    <div class=\"mw-actions-group\">
                        {$actionButtonsHtml}
                    </div>
                </td>
            </tr>";
        }

        return $html;
    }

    /**
     * Single-Page Workspace: delete task with permissions check
     */
    public function deleteTask($id)
    {
        $id = (int)$id;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $isAjax = !empty($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (!$userId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/login");
            exit;
        }

        $taskModel = new Task($this->db);
        $task = $taskModel->find($id);

        if (!$task) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Task not found']);
                exit;
            }
            if (function_exists('setError')) setError("Task not found.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        // Permission check: Task creator (assigned_by), assignee (user_id), or Admin/Management (roles 1, 3, 33)
        $isOwner = ((int)$task['assigned_by'] === $userId);
        if (!$isOwner && !in_array($roleId, [1, 3, 33], true)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => "Only the task owner can delete this task."]);
                exit;
            }
            if (function_exists('setError')) setError("Only the task owner can delete this task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        // My Work isolated full cascade delete: removes task and all related data without touching legacy Task model
        $deleteSuccess = false;
        $this->db->begin_transaction();
        try {
            // 1. Delete associated task activities & comments (My Work timeline)
            $stAct = $this->db->prepare("DELETE FROM task_activities WHERE task_id = ?");
            if ($stAct) {
                $stAct->bind_param("i", $id);
                $stAct->execute();
                $stAct->close();
            }

            // 2. Delete associated notifications
            $stNotif = $this->db->prepare("DELETE FROM notifications WHERE task_id = ?");
            if ($stNotif) {
                $stNotif->bind_param("i", $id);
                $stNotif->execute();
                $stNotif->close();
            }

            // 3. Delete attachment file from disk if exists
            if (!empty($task['attachment_image'])) {
                $filePath = __DIR__ . '/../../public/uploads/tasks/' . $task['attachment_image'];
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }

            // 4. Delete the task record itself
            $stTask = $this->db->prepare("DELETE FROM tasks WHERE id = ?");
            if ($stTask) {
                $stTask->bind_param("i", $id);
                $stTask->execute();
                $stTask->close();
            }

            $this->db->commit();
            $deleteSuccess = true;
        } catch (\Throwable $e) {
            $this->db->rollback();
            $deleteSuccess = false;
        }

        if ($deleteSuccess) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'success', 'message' => 'Task has been deleted successfully.']);
                exit;
            }
            if (function_exists('setSuccess')) setSuccess("Task has been deleted successfully.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete task.']);
                exit;
            }
            if (function_exists('setError')) setError("Failed to delete task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }
    }

    /**
     * Single-Page Workspace: store a new task from the slide drawer
     */
    public function storeTask()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $isAjax = !empty($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (!$userId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/login");
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        // user_id = the person the task is assigned to (defaults to the creator); assigned_by = creator (Owner)
        $ownerId = (int)($_POST['assigned_to_id'] ?? 0) ?: $userId;

        if (empty($title)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Task title is required.']);
                exit;
            }
            if (function_exists('setError')) setError("Task title is required.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high', 'urgent'], true) ? $_POST['priority'] : 'medium';
        $expectedOutput = trim($_POST['expected_output'] ?? '');
        // Acceptance checklist: one or many entries (description[]); several are stored as an HTML list
        $descItems = array_values(array_filter(array_map('trim', (array)($_POST['description'] ?? [])), 'strlen'));
        $description = count($descItems) > 1
            ? '<ul>' . implode('', array_map(fn($d) => '<li>' . htmlspecialchars($d) . '</li>', $descItems)) . '</ul>'
            : ($descItems[0] ?? '');
        $todoProjectId = !empty($_POST['todo_project_id']) ? (int)$_POST['todo_project_id'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;

        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $assignedById = $userId;
        $recurrenceType = in_array($_POST['recurrence_type'] ?? '', ['daily', 'weekly', 'monthly'], true) ? $_POST['recurrence_type'] : null;
        $isRecurring = !empty($_POST['is_recurring']) ? 1 : 0;
        if ($isRecurring && $recurrenceType === null) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Please select a repeat type for the recurring task.']);
                exit;
            }
            if (function_exists('setError')) setError("Please select a repeat type for the recurring task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }
        if (!$isRecurring) { $recurrenceType = null; }
        $recurrenceEnd = ($isRecurring && !empty($_POST['recurrence_end_date'])) ? $_POST['recurrence_end_date'] : null;

        $reviewerId = !empty($_POST['reviewer_id']) ? (int)$_POST['reviewer_id'] : null;
        $toIdList = fn($v) => array_values(array_unique(array_filter(array_map('intval', (array)$v))));
        $ccIds = $toIdList($_POST['cc_ids'] ?? []);
        $collaboratorIds = $toIdList($_POST['collaborator_ids'] ?? []);

        $mentionedUserIds = [];
        if ($reviewerId) {
            $mentionedUserIds[] = $reviewerId;
        }
        $mentionedJson = !empty($mentionedUserIds) ? json_encode($mentionedUserIds) : null;
        $ccJson = !empty($ccIds) ? json_encode($ccIds) : null;
        $collaboratorJson = !empty($collaboratorIds) ? json_encode($collaboratorIds) : null;

        // Attachment handling
        $attachmentImage = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            if (@move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $attachmentImage = $fileName;
            }
        }

        $checklistData = null;
        if (!empty($descItems)) {
            $clArr = [];
            foreach ($descItems as $idx => $item) {
                $clArr[] = [
                    'id' => $idx + 1,
                    'text' => $item,
                    'completed' => false
                ];
            }
            $checklistData = json_encode($clArr, JSON_UNESCAPED_UNICODE);
        }

        $stmt = $this->db->prepare("
            INSERT INTO tasks (
                title, expected_output, user_id, assigned_by, todo_project_id,
                description, checklist_data, start_date, deadline, priority,
                status, progress, employee_accept, mentioned_user_ids,
                cc_user_ids, attachment_image, project_id,
                is_recurring, recurrence_type, recurrence_end_date, collaborator_user_ids, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "ssiiissssssssiisss",
            $title,
            $expectedOutput,
            $ownerId,
            $assignedById,
            $todoProjectId,
            $description,
            $checklistData,
            $startDate,
            $deadline,
            $priority,
            $mentionedJson,
            $ccJson,
            $attachmentImage,
            $projectId,
            $isRecurring,
            $recurrenceType,
            $recurrenceEnd,
            $collaboratorJson
        );

        if ($stmt->execute()) {
            $newTaskId = $stmt->insert_id;
            $stmt->close();

            // Log creation activity
            $this->logTaskActivity($newTaskId, $userId, 'system', "Task created and assigned to " . ($ownerId === $userId ? "self" : "user #{$ownerId}"));

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task created and assigned successfully.',
                    'task_id' => $newTaskId
                ]);
                exit;
            }
            if (function_exists('setSuccess')) setSuccess("Task created successfully.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        } else {
            $err = $stmt->error;
            $stmt->close();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Failed to create task: ' . $err]);
                exit;
            }
            if (function_exists('setError')) setError("Failed to create task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }
    }

    /**
     * JSON detail for the task detail drawer (only for tasks related to the logged-in user)
     */
    public function taskDetail($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $id = (int)$id;
        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $st = $this->db->prepare("
            SELECT t.*, tp.name AS project_name,
                   u_o.full_name AS owner_name, u_b.full_name AS assigned_by_name
            FROM tasks t
            LEFT JOIN todo_projects tp ON t.todo_project_id = tp.id
            LEFT JOIN users u_o ON t.user_id = u_o.id
            LEFT JOIN users u_b ON t.assigned_by = u_b.id
            WHERE t.id = ?
        ");
        $st->bind_param("i", $id);
        $st->execute();
        $t = $st->get_result()->fetch_assoc();

        $ids = fn($v) => array_map('intval', preg_match_all('/\d+/', (string)$v, $m) ? $m[0] : []);
        $reviewerIds = $t ? $ids($t['mentioned_user_ids'] ?? '') : [];
        $ccIds = $t ? $ids($t['cc_user_ids'] ?? '') : [];
        $collabIds = $t ? $ids($t['collaborator_user_ids'] ?? '') : [];

        $related = $t && (
            (int)$t['user_id'] === $userId || (int)$t['assigned_by'] === $userId ||
            in_array($userId, $reviewerIds, true) || in_array($userId, $ccIds, true) || in_array($userId, $collabIds, true)
        );
        if (!$related) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
            exit;
        }

        $base = $GLOBALS['base_url'] ?? '';

        $peopleMap = [];
        $ownerId = (int)$t['assigned_by'];
        $assigneeId = (int)$t['user_id'];
        $allIds = array_values(array_unique(array_filter(array_merge([$ownerId, $assigneeId], $reviewerIds, $ccIds, $collabIds))));
        if ($allIds) {
            $in = implode(',', $allIds); // ints only
            $res = $this->db->query("
                SELECT u.id, u.full_name, u.photo AS user_photo, e.photo AS emp_photo
                FROM users u
                LEFT JOIN employees e ON e.user_id = u.id
                WHERE u.id IN ({$in})
            ");
            while ($res && ($r = $res->fetch_assoc())) {
                $uid = (int)$r['id'];
                $photo = null;
                if (!empty($r['emp_photo']) && file_exists(dirname(__DIR__, 2) . '/public/uploads/employees/' . $r['emp_photo'])) {
                    $photo = $base . '/public/uploads/employees/' . rawurlencode($r['emp_photo']);
                } elseif (!empty($r['user_photo']) && file_exists(dirname(__DIR__, 2) . '/public/uploads/users/' . $r['user_photo'])) {
                    $photo = $base . '/public/uploads/users/' . rawurlencode($r['user_photo']);
                }
                $peopleMap[$uid] = [
                    'id' => $uid,
                    'name' => $r['full_name'] ?: 'User',
                    'photo' => $photo
                ];
            }
        }
        $personObj = fn($id, $fallbackName = '—') => $peopleMap[$id] ?? ['id' => $id, 'name' => $fallbackName, 'photo' => null];
        $peopleList = fn(array $list) => array_values(array_filter(array_map(fn($i) => $peopleMap[$i] ?? null, $list)));

        $isCompleted = $t['status'] === 'completed';
        $statusLabel = ['pending' => 'To do', 'in_progress' => 'In progress', 'completed' => 'Completed', 'expired' => 'Expired'][$t['status']] ?? 'To do';
        $statusClass = $t['status'];
        if (!$isCompleted && (int)$t['progress'] >= 80) {
            $statusLabel = 'In review';
            $statusClass = 'in_review';
        }

        $deadlineText = null;
        $overdueText = null;
        if (!empty($t['deadline'])) {
            $dl = strtotime($t['deadline']);
            $deadlineText = date('j M Y', $dl);
            if (!$isCompleted && $dl < time()) {
                $days = (int) ceil((time() - $dl) / 86400);
                $overdueText = $days <= 1 ? '1 day overdue' : "{$days} days overdue";
            }
        }

        $blocked = !empty($t['remarks']) && stripos($t['remarks'], 'block') !== false;
        $base = $GLOBALS['base_url'] ?? '';

        // Build structured checklist items from checklist_data or parse from description
        $checklistItems = [];
        $rawChecklist = $t['checklist_data'] ?? '';
        if ($rawChecklist) {
            $decoded = json_decode($rawChecklist, true);
            if (is_array($decoded)) {
                $checklistItems = $decoded;
            }
        }
        if (empty($checklistItems)) {
            // Fallback: parse <li> from description for tasks created before checklist_data column
            $descRaw = (string)($t['description'] ?? '');
            if (preg_match_all('/<li>(.*?)<\/li>/is', $descRaw, $matches)) {
                foreach ($matches[1] as $idx => $m) {
                    $itemText = trim(html_entity_decode(strip_tags($m)));
                    if ($itemText !== '') {
                        $checklistItems[] = [
                            'id' => $idx + 1,
                            'text' => $itemText,
                            'completed' => ($t['status'] === 'completed')
                        ];
                    }
                }
            } elseif (trim(strip_tags($descRaw)) !== '') {
                $checklistItems[] = [
                    'id' => 1,
                    'text' => trim(html_entity_decode(strip_tags($descRaw))),
                    'completed' => ($t['status'] === 'completed')
                ];
            }
        }

        $canCheckChecklist = ((int)$t['user_id'] === $userId || in_array($userId, $collabIds, true) || (int)$t['assigned_by'] === $userId || in_array((int)($_SESSION['user']['role_id'] ?? 0), [1, 3, 33], true));

        // Fetch recent activities for this task
        $activities = [];
        $actStmt = $this->db->prepare("
            SELECT a.id, a.type, a.activity, a.created_at, u.id AS user_id, u.full_name, u.photo AS user_photo, e.photo AS emp_photo
            FROM task_activities a
            LEFT JOIN users u ON a.user_id = u.id
            LEFT JOIN employees e ON e.user_id = u.id
            WHERE a.task_id = ?
            ORDER BY a.id ASC
            LIMIT 50
        ");
        $actStmt->bind_param("i", $id);
        $actStmt->execute();
        $actRes = $actStmt->get_result();
        while ($ar = $actRes->fetch_assoc()) {
            $userPhoto = null;
            if (!empty($ar['emp_photo']) && file_exists(dirname(__DIR__, 2) . '/public/uploads/employees/' . $ar['emp_photo'])) {
                $userPhoto = $base . '/public/uploads/employees/' . rawurlencode($ar['emp_photo']);
            } elseif (!empty($ar['user_photo']) && file_exists(dirname(__DIR__, 2) . '/public/uploads/users/' . $ar['user_photo'])) {
                $userPhoto = $base . '/public/uploads/users/' . rawurlencode($ar['user_photo']);
            } else {
                // Default avatar photo
                $userPhoto = $base . '/public/assets/images/avatar/avatar.png';
            }

            $activities[] = [
                'id' => (int)$ar['id'],
                'type' => $ar['type'],
                'activity' => $ar['activity'],
                'created_at' => date('d M Y, h:i A', strtotime($ar['created_at'])),
                'user_id' => (int)$ar['user_id'],
                'user_name' => $ar['full_name'] ?: 'User',
                'user_photo' => $userPhoto,
            ];
        }
        $actStmt->close();

        echo json_encode([
            'status' => 'success',
            'task' => [
                'id' => (int)$t['id'],
                'code' => 'T-' . (int)$t['id'],
                'project' => $t['project_name'] ?: 'General',
                'title' => $t['title'],
                'status' => $t['status'],
                'is_completed' => $isCompleted,
                'is_reviewer' => in_array($userId, $reviewerIds, true),
                'can_complete' => (!$isCompleted && $canCheckChecklist),
                'can_check' => $canCheckChecklist,
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'priority' => ucfirst($t['priority'] ?? 'medium'),
                'deadline' => $deadlineText,
                'overdue' => $overdueText,
                'owner' => $t['assigned_by_name'] ?: '—',
                'assigned_to' => $t['owner_name'] ?: '—',
                'owner_user' => $personObj($ownerId, $t['assigned_by_name'] ?: '—'),
                'assigned_user' => $personObj($assigneeId, $t['owner_name'] ?: '—'),
                'reviewers' => $peopleList($reviewerIds),
                'collaborators' => $peopleList($collabIds),
                'cc' => $peopleList($ccIds),
                'expected_output' => $t['expected_output'] ?? '',
                'description_html' => strip_tags((string)$t['description'], '<p><br><ul><ol><li><strong><b><em><i>'),
                'checklist' => $checklistItems,
                'activities' => $activities,
                'remarks' => $t['remarks'],
                'blocked' => $blocked,
                'attachment_name' => $t['attachment_image'] ?: null,
                'attachment_url' => !empty($t['attachment_image']) ? $base . '/public/uploads/tasks/' . rawurlencode($t['attachment_image']) : null,
                'show_url' => $base . '/show-task/' . (int)$t['id'],
            ],
        ]);
        exit;
    }

    /**
     * Fetch raw data for the edit drawer (only owner can edit)
     */
    public function taskEditData($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $id = (int)$id;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $st = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $st->bind_param("i", $id);
        $st->execute();
        $t = $st->get_result()->fetch_assoc();

        if (!$t) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
            exit;
        }

        $isOwner = ((int)$t['assigned_by'] === $userId);
        if (!$isOwner && !in_array($roleId, [1, 3, 33], true)) {
            echo json_encode(['status' => 'error', 'message' => "Only the task owner can edit this task."]);
            exit;
        }

        // Parse expected output items from HTML <ul><li> or single text
        $descRaw = (string)($t['description'] ?? '');
        $descItems = [];
        if (preg_match_all('/<li>(.*?)<\/li>/is', $descRaw, $matches)) {
            foreach ($matches[1] as $item) {
                $itemClean = trim(html_entity_decode(strip_tags($item)));
                if ($itemClean !== '') $descItems[] = $itemClean;
            }
        }
        if (empty($descItems) && trim(strip_tags($descRaw)) !== '') {
            $descItems[] = trim(html_entity_decode(strip_tags($descRaw)));
        }

        $ids = fn($v) => array_map('intval', preg_match_all('/\d+/', (string)$v, $m) ? $m[0] : []);
        $reviewerIds = $ids($t['mentioned_user_ids'] ?? '');
        $ccIds = $ids($t['cc_user_ids'] ?? '');
        $collabIds = $ids($t['collaborator_user_ids'] ?? '');

        $deadlineDate = !empty($t['deadline']) ? date('Y-m-d', strtotime($t['deadline'])) : '';
        $startDate = !empty($t['start_date']) ? date('Y-m-d', strtotime($t['start_date'])) : '';
        $recurrenceEnd = !empty($t['recurrence_end_date']) ? date('Y-m-d', strtotime($t['recurrence_end_date'])) : '';

        echo json_encode([
            'status' => 'success',
            'task' => [
                'id' => (int)$t['id'],
                'title' => $t['title'],
                'assigned_to_id' => (int)$t['user_id'],
                'deadline' => $deadlineDate,
                'priority' => $t['priority'] ?? 'medium',
                'project_id' => !empty($t['project_id']) ? (int)$t['project_id'] : '',
                'is_recurring' => (int)($t['is_recurring'] ?? 0),
                'recurrence_type' => $t['recurrence_type'] ?? '',
                'recurrence_end_date' => $recurrenceEnd,
                'expected_output' => $t['expected_output'] ?? '',
                'descriptions' => $descItems,
                'reviewer_id' => !empty($reviewerIds[0]) ? (int)$reviewerIds[0] : null,
                'cc_ids' => $ccIds,
                'collaborator_ids' => $collabIds,
                'start_date' => $startDate,
            ]
        ]);
        exit;
    }

    /**
     * Update task via drawer (AJAX) - only owner can update
     */
    public function updateTask($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $id = (int)$id;
        $isAjax = !empty($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (!$userId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/login");
            exit;
        }

        $stCheck = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $stCheck->bind_param("i", $id);
        $stCheck->execute();
        $existingTask = $stCheck->get_result()->fetch_assoc();

        if (!$existingTask) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
                exit;
            }
            if (function_exists('setError')) setError("Task not found.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        $isOwner = ((int)$existingTask['assigned_by'] === $userId);
        if (!$isOwner && !in_array($roleId, [1, 3, 33], true)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => "Only the task owner can edit this task."]);
                exit;
            }
            if (function_exists('setError')) setError("Only the task owner can edit this task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        $title = trim($_POST['title'] ?? '');
        $assignedToId = (int)($_POST['assigned_to_id'] ?? 0) ?: (int)$existingTask['user_id'];

        if (empty($title)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Task title is required.']);
                exit;
            }
            if (function_exists('setError')) setError("Task title is required.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;
        $priority = in_array($_POST['priority'] ?? '', ['low', 'medium', 'high', 'urgent'], true) ? $_POST['priority'] : 'medium';
        $expectedOutput = trim($_POST['expected_output'] ?? '');
        $descItems = array_values(array_filter(array_map('trim', (array)($_POST['description'] ?? [])), 'strlen'));
        $description = count($descItems) > 1
            ? '<ul>' . implode('', array_map(fn($d) => '<li>' . htmlspecialchars($d) . '</li>', $descItems)) . '</ul>'
            : ($descItems[0] ?? '');
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;

        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $recurrenceType = in_array($_POST['recurrence_type'] ?? '', ['daily', 'weekly', 'monthly'], true) ? $_POST['recurrence_type'] : null;
        $isRecurring = !empty($_POST['is_recurring']) ? 1 : 0;
        if ($isRecurring && $recurrenceType === null) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Please select a repeat type for the recurring task.']);
                exit;
            }
            if (function_exists('setError')) setError("Please select a repeat type for the recurring task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }
        if (!$isRecurring) { $recurrenceType = null; }
        $recurrenceEnd = ($isRecurring && !empty($_POST['recurrence_end_date'])) ? $_POST['recurrence_end_date'] : null;

        $reviewerId = !empty($_POST['reviewer_id']) ? (int)$_POST['reviewer_id'] : null;
        $toIdList = fn($v) => array_values(array_unique(array_filter(array_map('intval', (array)$v))));
        $ccIds = $toIdList($_POST['cc_ids'] ?? []);
        $collaboratorIds = $toIdList($_POST['collaborator_ids'] ?? []);

        $mentionedUserIds = [];
        if ($reviewerId) {
            $mentionedUserIds[] = $reviewerId;
        }
        $mentionedJson = !empty($mentionedUserIds) ? json_encode($mentionedUserIds) : null;
        $ccJson = !empty($ccIds) ? json_encode($ccIds) : null;
        $collaboratorJson = !empty($collaboratorIds) ? json_encode($collaboratorIds) : null;

        // Attachment handling (keep existing if none uploaded)
        $attachmentImage = $existingTask['attachment_image'];
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/tasks/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            $ext = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $fileName = time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $fileName;
            if (@move_uploaded_file($_FILES['attachment']['tmp_name'], $targetPath)) {
                $attachmentImage = $fileName;
            }
        }

        // Reconcile checklist_data when updated
        // Fetch existing checklist to preserve completed flags if item text matches
        $existingCL = [];
        $rawCL = $task['checklist_data'] ?? '';
        if ($rawCL) {
            $decoded = json_decode($rawCL, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (isset($item['text'])) {
                        $existingCL[trim($item['text'])] = !empty($item['completed']);
                    }
                }
            }
        }
        $newCL = [];
        foreach ($descItems as $idx => $item) {
            $newCL[] = [
                'id' => $idx + 1,
                'text' => $item,
                'completed' => !empty($existingCL[$item])
            ];
        }
        $checklistData = !empty($newCL) ? json_encode($newCL, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $this->db->prepare("
            UPDATE tasks SET
                title = ?,
                expected_output = ?,
                user_id = ?,
                description = ?,
                checklist_data = ?,
                start_date = ?,
                deadline = ?,
                priority = ?,
                mentioned_user_ids = ?,
                cc_user_ids = ?,
                attachment_image = ?,
                project_id = ?,
                is_recurring = ?,
                recurrence_type = ?,
                recurrence_end_date = ?,
                collaborator_user_ids = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->bind_param(
            "ssissssssssiisssi",
            $title,
            $expectedOutput,
            $assignedToId,
            $description,
            $checklistData,
            $startDate,
            $deadline,
            $priority,
            $mentionedJson,
            $ccJson,
            $attachmentImage,
            $projectId,
            $isRecurring,
            $recurrenceType,
            $recurrenceEnd,
            $collaboratorJson,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task updated successfully.',
                    'task_id' => $id
                ]);
                exit;
            }
            if (function_exists('setSuccess')) setSuccess("Task updated successfully.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        } else {
            $err = $stmt->error;
            $stmt->close();
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Failed to update task: ' . $err]);
                exit;
            }
            if (function_exists('setError')) setError("Failed to update task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }
    }

    /**
     * Mark task as completed (Assignee / Collaborator / Owner / Admin)
     */
    public function completeTask($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $id = (int)$id;
        $isAjax = !empty($_GET['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if (!$userId) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
                exit;
            }
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/login");
            exit;
        }

        $st = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $st->bind_param("i", $id);
        $st->execute();
        $task = $st->get_result()->fetch_assoc();

        if (!$task) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
                exit;
            }
            if (function_exists('setError')) setError("Task not found.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        $ids = fn($v) => array_map('intval', preg_match_all('/\d+/', (string)$v, $m) ? $m[0] : []);
        $collabIds = $ids($task['collaborator_user_ids'] ?? '');

        // Can complete if Assignee (user_id), Collaborator, Owner (assigned_by), or Admin
        $canComplete = ((int)$task['user_id'] === $userId || in_array($userId, $collabIds, true) || (int)$task['assigned_by'] === $userId || in_array($roleId, [1, 3, 33], true));

        if (!$canComplete) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => "You don't have permission to complete this task."]);
                exit;
            }
            if (function_exists('setError')) setError("You don't have permission to complete this task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        $taskModel = new Task($this->db);
        if ($taskModel->updateProgress($id, 100, $task['remarks'] ?? null)) {
            $hasReviewers = !empty($task['mentioned_user_ids']) && count($ids($task['mentioned_user_ids'])) > 0;
            $actMsg = $hasReviewers ? "Completed task and submitted for review." : "Marked task as completed.";
            $this->logTaskActivity($id, $userId, 'system', $actMsg);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task marked as completed successfully.',
                    'task_id' => $id
                ]);
                exit;
            }
            if (function_exists('setSuccess')) setSuccess("Task marked as completed successfully.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Failed to mark task as completed.']);
                exit;
            }
            if (function_exists('setError')) setError("Failed to mark task as completed.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }
    }

    /**
     * Reviewer Action: either approve (Done) or request improvements (add note & return to in_progress)
     */
    public function reviewerAction($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $id = (int)$id;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $st = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $st->bind_param("i", $id);
        $st->execute();
        $task = $st->get_result()->fetch_assoc();

        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
            exit;
        }

        $ids = fn($v) => array_map('intval', preg_match_all('/\d+/', (string)$v, $m) ? $m[0] : []);
        $reviewerIds = $ids($task['mentioned_user_ids'] ?? '');

        $isReviewer = in_array($userId, $reviewerIds, true) || in_array($roleId, [1, 3, 33], true);
        if (!$isReviewer) {
            echo json_encode(['status' => 'error', 'message' => "Only an assigned reviewer can review this task."]);
            exit;
        }

        $action = trim($_POST['review_action'] ?? '');
        $note = trim($_POST['review_note'] ?? '');

        if ($action === 'approve') {
            // Mark task as fully approved / completed
            $taskModel = new Task($this->db);
            $remarks = !empty($task['remarks']) ? $task['remarks'] : 'Approved by reviewer.';
            if ($taskModel->updateProgress($id, 100, $remarks)) {
                $this->logTaskActivity($id, $userId, 'system', "Approved task as completed (Done).");
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Task reviewed and approved as completed.',
                    'task_id' => $id
                ]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to approve task.']);
                exit;
            }
        } elseif ($action === 'request_changes') {
            if ($note === '') {
                echo json_encode(['status' => 'error', 'message' => 'Please write a review note explaining the needed improvements.']);
                exit;
            }
            $reviewerName = $_SESSION['user']['full_name'] ?? 'Reviewer';
            $datedNote = "[" . date('d M Y, h:i A') . "] Reviewer Note (" . $reviewerName . "): " . $note;
            $newRemarks = !empty($task['remarks']) ? $task['remarks'] . "\n\n" . $datedNote : $datedNote;

            // Revert status to in_progress and progress to 60%
            $stmt = $this->db->prepare("UPDATE tasks SET status = 'in_progress', progress = 60, remarks = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("si", $newRemarks, $id);
            if ($stmt->execute()) {
                $stmt->close();
                $this->logTaskActivity($id, $userId, 'system', "Requested changes with note: \"{$note}\"");
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Review note added and task sent back for improvement.',
                    'task_id' => $id
                ]);
                exit;
            } else {
                $err = $stmt->error;
                $stmt->close();
                echo json_encode(['status' => 'error', 'message' => 'Failed to update task: ' . $err]);
                exit;
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid review action.']);
            exit;
        }
    }

    /**
     * Toggle acceptance checklist item state and auto-complete when 100% checked
     */
    public function toggleChecklist($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $id = (int)$id;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $st = $this->db->prepare("SELECT * FROM tasks WHERE id = ?");
        $st->bind_param("i", $id);
        $st->execute();
        $task = $st->get_result()->fetch_assoc();

        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
            exit;
        }

        $ids = fn($v) => array_map('intval', preg_match_all('/\d+/', (string)$v, $m) ? $m[0] : []);
        $collabIds = $ids($task['collaborator_user_ids'] ?? '');

        // Can toggle checklist if Assignee (user_id), Collaborator, Owner (assigned_by), or Admin
        $canToggle = ((int)$task['user_id'] === $userId || in_array($userId, $collabIds, true) || (int)$task['assigned_by'] === $userId || in_array($roleId, [1, 3, 33], true));
        if (!$canToggle) {
            echo json_encode(['status' => 'error', 'message' => "Only the assignee or owner can check items."]);
            exit;
        }

        $itemIndex = isset($_POST['index']) ? (int)$_POST['index'] : -1;
        $completed = !empty($_POST['completed']);

        // Load existing checklist items or reconstruct from description
        $checklist = [];
        $raw = $task['checklist_data'] ?? '';
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $checklist = $decoded;
        }
        if (empty($checklist)) {
            $descRaw = (string)($task['description'] ?? '');
            if (preg_match_all('/<li>(.*?)<\/li>/is', $descRaw, $matches)) {
                foreach ($matches[1] as $idx => $m) {
                    $itemText = trim(html_entity_decode(strip_tags($m)));
                    if ($itemText !== '') {
                        $checklist[] = [
                            'id' => $idx + 1,
                            'text' => $itemText,
                            'completed' => false
                        ];
                    }
                }
            } elseif (trim(strip_tags($descRaw)) !== '') {
                $checklist[] = [
                    'id' => 1,
                    'text' => trim(html_entity_decode(strip_tags($descRaw))),
                    'completed' => false
                ];
            }
        }

        if ($itemIndex < 0 || !isset($checklist[$itemIndex])) {
            echo json_encode(['status' => 'error', 'message' => 'Checklist item not found.']);
            exit;
        }

        $checklist[$itemIndex]['completed'] = $completed;

        // Calculate progress percentage
        $totalItems = count($checklist);
        $completedItems = 0;
        foreach ($checklist as $ci) {
            if (!empty($ci['completed'])) $completedItems++;
        }

        $allDone = ($totalItems > 0 && $completedItems === $totalItems);
        $progress = $totalItems > 0 ? (int)round(($completedItems / $totalItems) * 100) : (int)($task['progress'] ?? 0);

        // Status update logic: if all items are done, mark as completed (or ready for review)
        $newStatus = $task['status'];
        if ($allDone) {
            $newStatus = 'completed';
            $progress = 100;
        } elseif ($task['status'] === 'completed' && !$allDone) {
            // Unchecking any item reverts status back to in_progress
            $newStatus = 'in_progress';
        }

        $jsonChecklist = json_encode($checklist, JSON_UNESCAPED_UNICODE);
        $stmt = $this->db->prepare("UPDATE tasks SET checklist_data = ?, progress = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sisi", $jsonChecklist, $progress, $newStatus, $id);
        if ($stmt->execute()) {
            $stmt->close();

            // Log activity event
            $itemText = $checklist[$itemIndex]['text'] ?? 'item';
            $actMsg = $completed ? "Completed checklist item: \"{$itemText}\"" : "Unchecked checklist item: \"{$itemText}\"";
            $this->logTaskActivity($id, $userId, 'system', $actMsg);

            if ($allDone && $task['status'] !== 'completed') {
                $this->logTaskActivity($id, $userId, 'system', "All acceptance checklist items completed. Task marked as completed.");
            }

            echo json_encode([
                'status' => 'success',
                'task_id' => $id,
                'checklist' => $checklist,
                'progress' => $progress,
                'task_status' => $newStatus,
                'all_completed' => $allDone,
                'message' => $allDone ? 'All checklist items completed! Task marked as completed.' : 'Checklist updated.'
            ]);
            exit;
        } else {
            $err = $stmt->error;
            $stmt->close();
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $err]);
            exit;
        }
    }

    /**
     * Add a comment to a task
     */
    public function addComment($id)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        header('Content-Type: application/json');
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $id = (int)$id;

        if (!$userId) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $comment = trim($_POST['comment'] ?? '');
        if ($comment === '') {
            echo json_encode(['status' => 'error', 'message' => 'Comment cannot be empty.']);
            exit;
        }

        $st = $this->db->prepare("SELECT id FROM tasks WHERE id = ?");
        $st->bind_param("i", $id);
        $st->execute();
        $task = $st->get_result()->fetch_assoc();
        if (!$task) {
            echo json_encode(['status' => 'error', 'message' => 'Task not found.']);
            exit;
        }

        if ($this->logTaskActivity($id, $userId, 'comment', $comment)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Comment added successfully.',
                'task_id' => $id
            ]);
            exit;
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save comment.']);
            exit;
        }
    }

    /**
     * Helper to log an activity/comment to task_activities
     */
    private function logTaskActivity($taskId, $userId, $type, $activity)
    {
        $stmt = $this->db->prepare("INSERT INTO task_activities (task_id, user_id, type, activity, created_at) VALUES (?, ?, ?, ?, NOW())");
        if (!$stmt) return false;
        $stmt->bind_param("iiss", $taskId, $userId, $type, $activity);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }
}