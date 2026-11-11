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

        // 7. Focus Panel: Needs Review
        $stmtReviewList = $this->db->prepare("
            SELECT 
                t.*,
                u.full_name AS employee_name,
                u.photo AS employee_photo,
                des.name AS employee_designation
            FROM tasks t
            JOIN users u ON t.user_id = u.id
            LEFT JOIN designations des ON u.designation_id = des.id
            WHERE t.assigned_by = ?
              AND t.status != 'completed'
              AND (t.progress >= 80 OR t.employee_accept = 1)
            ORDER BY t.updated_at DESC
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

            $html .= "<tr class=\"{$rowClass}\" data-task-id=\"{$taskId}\">
                <td>
                    <div class=\"d-flex align-items-start\">
                        <input type=\"checkbox\" class=\"mw-task-check\" {$checkedAttr} disabled>
                        <div>
                            <a href=\"{$baseUrl}/show-task/{$taskId}\" class=\"mw-task-name\">
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
                        <a href=\"{$baseUrl}/show-task/{$taskId}\" class=\"mw-action-btn view\" title=\"View task\">
                            <i class=\"fa-solid fa-eye\"></i>
                        </a>
                        <a href=\"{$baseUrl}/edit-task/{$taskId}\" class=\"mw-action-btn edit\" title=\"Edit task\">
                            <i class=\"fa-solid fa-pen\"></i>
                        </a>
                        <button type=\"button\" class=\"mw-action-btn delete\" onclick=\"deleteMyWorkTask({$taskId}, this)\" title=\"Delete task\">
                            <i class=\"fa-solid fa-trash-can\"></i>
                        </button>
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
        if ((int)$task['assigned_by'] !== $userId && (int)$task['user_id'] !== $userId && !in_array($roleId, [1, 3, 33])) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => "You don't have permission to delete this task."]);
                exit;
            }
            if (function_exists('setError')) setError("You don't have permission to delete this task.");
            header("Location: " . ($GLOBALS['base_url'] ?? '') . "/my-work-dashboard");
            exit;
        }

        if ($taskModel->deleteFromEverywhere($id)) {
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
        // Expected output: one or many entries (description[]); several are stored as an HTML list
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

        $stmt = $this->db->prepare("
            INSERT INTO tasks (
                title, user_id, assigned_by, todo_project_id,
                description, start_date, deadline, priority,
                status, progress, employee_accept, mentioned_user_ids,
                cc_user_ids, attachment_image, project_id,
                is_recurring, recurrence_type, recurrence_end_date, collaborator_user_ids, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 0, 0, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->bind_param(
            "siiisssssssiisss",
            $title,
            $ownerId,
            $assignedById,
            $todoProjectId,
            $description,
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

        $names = [];
        $allIds = array_values(array_unique(array_merge($reviewerIds, $ccIds, $collabIds)));
        if ($allIds) {
            $in = implode(',', $allIds); // ints only
            $res = $this->db->query("SELECT id, full_name FROM users WHERE id IN ({$in})");
            while ($res && ($r = $res->fetch_assoc())) {
                $names[(int)$r['id']] = $r['full_name'];
            }
        }
        $nameList = fn(array $list) => array_values(array_filter(array_map(fn($i) => $names[$i] ?? null, $list)));

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

        echo json_encode([
            'status' => 'success',
            'task' => [
                'id' => (int)$t['id'],
                'code' => 'T-' . (int)$t['id'],
                'project' => $t['project_name'] ?: 'General',
                'title' => $t['title'],
                'status_label' => $statusLabel,
                'status_class' => $statusClass,
                'priority' => ucfirst($t['priority'] ?? 'medium'),
                'deadline' => $deadlineText,
                'overdue' => $overdueText,
                'owner' => $t['assigned_by_name'] ?: '—',
                'assigned_to' => $t['owner_name'] ?: '—',
                'reviewers' => $nameList($reviewerIds),
                'collaborators' => $nameList($collabIds),
                'cc' => $nameList($ccIds),
                'description_html' => strip_tags((string)$t['description'], '<p><br><ul><ol><li><strong><b><em><i>'),
                'remarks' => $t['remarks'],
                'blocked' => $blocked,
                'attachment_name' => $t['attachment_image'] ?: null,
                'attachment_url' => !empty($t['attachment_image']) ? $base . '/public/uploads/tasks/' . rawurlencode($t['attachment_image']) : null,
                'show_url' => $base . '/show-task/' . (int)$t['id'],
            ],
        ]);
        exit;
    }
}