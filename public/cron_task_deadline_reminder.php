<?php
/**
 * TPHL ERP Daily Task Deadline Reminder Cron
 * -------------------------------------------------------------
 * Checks all incomplete tasks whose deadline is TODAY (DATE(deadline) = CURDATE())
 * and sends a consolidated daily reminder email + in-app notification to:
 *   1. Assignee (user_id)
 *   2. Collaborator (collaborator_user_ids)
 *   3. Reviewer (mentioned_user_ids)
 *   4. CC (cc_user_ids)
 *
 * Trigger Options:
 * - URL (Crontab / Browser):
 *   curl -s "https://your-domain.com/TPHL-ERP/cron_task_deadline_reminder.php?token=TphlSecretCron2026"
 * - CLI (Linux Crontab / Windows Task Scheduler):
 *   php public/cron_task_deadline_reminder.php TphlSecretCron2026
 * - Test Mode (sends only to test_email):
 *   http://localhost/TPHL-ERP/public/cron_task_deadline_reminder.php?token=TphlSecretCron2026&test_email=your@email.com&force=1
 */

set_time_limit(0);
ignore_user_abort(true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/MailServiceForPHPMailer.php';

$database = new Database($config);
$db = $database->conn;

// Ensure email_queue table exists
$db->query("
    CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body LONGTEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Token-based security check (supports both GET param and CLI argument)
$token = $_GET['token'] ?? ($argv[1] ?? '');
$secretToken = 'TphlSecretCron2026';
if ($token !== $secretToken) {
    if (PHP_SAPI !== 'cli') {
        header('HTTP/1.1 403 Forbidden');
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['status' => 'error', 'message' => 'Access Denied: Invalid Security Token.']);
    exit;
}

$testEmail = trim($_GET['test_email'] ?? ($argv[2] ?? ''));
$isTestMode = ($testEmail !== '' && filter_var($testEmail, FILTER_VALIDATE_EMAIL));
$forceSend = !empty($_GET['force']) || $isTestMode;

$todayFormatted = date('d M Y');

// 1. Fetch all incomplete tasks due today
$sql = "
    SELECT
        t.*,
        COALESCE(rp.name, tp.name, 'General') AS project_name,
        u_owner.full_name AS owner_name,
        u_assignee.full_name AS assignee_name
    FROM tasks t
    LEFT JOIN todo_projects tp ON t.todo_project_id = tp.id
    LEFT JOIN projects rp ON t.project_id = rp.id
    LEFT JOIN users u_owner ON t.assigned_by = u_owner.id
    LEFT JOIN users u_assignee ON t.user_id = u_assignee.id
    WHERE t.status != 'completed'
      AND t.deadline IS NOT NULL
      AND DATE(t.deadline) = CURDATE()
      AND NOT (t.parent_recurring_task_id IS NOT NULL AND DATE(t.created_at) = CURDATE())
    ORDER BY
        FIELD(LOWER(COALESCE(t.priority, 'medium')), 'urgent', 'high', 'medium', 'low'),
        t.id ASC
";

$resTasks = $db->query($sql);
$dueTodayTasks = [];
if ($resTasks) {
    while ($row = $resTasks->fetch_assoc()) {
        $dueTodayTasks[(int)$row['id']] = $row;
    }
}

if (empty($dueTodayTasks)) {
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'success',
        'message' => 'No open tasks due today (' . $todayFormatted . ').',
        'tasks_due_today' => 0,
        'emails_queued' => 0
    ], JSON_PRETTY_PRINT);
    exit;
}

// Helper to parse JSON or CSV user IDs
$parseIds = fn($v) => array_values(array_unique(array_filter(
    array_map('intval', preg_match_all('/\d+/', (string)$v, $m) ? $m[0] : [])
)));

// 2. Map tasks per user across Assignee, Collaborator, Reviewer, and CC
$userTasksMap = []; // [userId => [taskId => ['task' => $task, 'roles' => ['Assignee', ...]]]]
$allUserIds = [];

foreach ($dueTodayTasks as $taskId => $task) {
    $assigneeId  = (int)($task['user_id'] ?? 0);
    $collabIds   = $parseIds($task['collaborator_user_ids'] ?? '');
    $reviewerIds = $parseIds($task['mentioned_user_ids'] ?? '');
    $ccIds       = $parseIds($task['cc_user_ids'] ?? '');

    $taskRoleMap = [];
    if ($assigneeId > 0) {
        $taskRoleMap[$assigneeId][] = 'Assignee';
    }
    foreach ($collabIds as $cid) {
        $taskRoleMap[$cid][] = 'Collaborator';
    }
    foreach ($reviewerIds as $rid) {
        $taskRoleMap[$rid][] = 'Reviewer';
    }
    foreach ($ccIds as $ccid) {
        $taskRoleMap[$ccid][] = 'CC';
    }

    foreach ($taskRoleMap as $uid => $roles) {
        $allUserIds[$uid] = $uid;
        $userTasksMap[$uid][$taskId] = [
            'task'  => $task,
            'roles' => array_unique($roles)
        ];
    }
}

if (empty($allUserIds)) {
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'success',
        'message' => 'No recipients found for tasks due today.',
        'tasks_due_today' => count($dueTodayTasks),
        'emails_queued' => 0
    ], JSON_PRETTY_PRINT);
    exit;
}

// 3. Fetch recipient names & emails (users.email with fallback to employees.email)
$inClause = implode(',', array_map('intval', $allUserIds));
$resUsers = $db->query("
    SELECT
        u.id,
        u.full_name,
        COALESCE(
            NULLIF(TRIM(u.email), ''),
            (SELECT NULLIF(TRIM(e.email), '') FROM employees e WHERE (e.user_id = u.id OR e.id = u.employee_id) AND e.deleted_at IS NULL LIMIT 1)
        ) AS email
    FROM users u
    WHERE u.id IN ({$inClause})
");

$usersInfo = [];
if ($resUsers) {
    while ($u = $resUsers->fetch_assoc()) {
        $usersInfo[(int)$u['id']] = $u;
    }
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 0) == 443) ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'thepremiumhomesltd.com';
$basePath = rtrim($GLOBALS['base_url'] ?? '/TPHL-ERP', '/');
$dashboardUrl = $protocol . $host . $basePath . '/my-work-dashboard';

$stmtCheckDup = $db->prepare("
    SELECT id FROM email_queue
    WHERE to_email = ?
      AND subject LIKE 'Due Today Task Reminder%'
      AND DATE(created_at) = CURDATE()
    LIMIT 1
");
$stmtInsertQueue = $db->prepare("
    INSERT INTO email_queue (to_email, subject, body, status)
    VALUES (?, ?, ?, 'pending')
");
$stmtCheckNotif = $db->prepare("
    SELECT id FROM notifications
    WHERE user_id = ?
      AND task_id = ?
      AND message LIKE 'Due Today Reminder:%'
      AND DATE(created_at) = CURDATE()
    LIMIT 1
");
$stmtInsertNotif = $db->prepare("
    INSERT INTO notifications (user_id, task_id, assigned_by, message, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

$emailsQueued = 0;
$notificationsCreated = 0;
$skippedDuplicates = 0;
$skippedNoEmail = 0;
$notifiedTaskUsers = []; // [taskId => ['Name (Role)', ...]]

foreach ($userTasksMap as $uid => $taskEntries) {
    $user = $usersInfo[$uid] ?? null;
    if (!$user) {
        continue;
    }

    $userName = $user['full_name'] ?: 'Team Member';
    $targetEmail = $isTestMode ? $testEmail : trim((string)($user['email'] ?? ''));

    // Create in-app notifications per task (once per day per user)
    foreach ($taskEntries as $tId => $entry) {
        $tRow = $entry['task'];
        $roleTag = implode(', ', $entry['roles']);
        $tCode = 'T-' . $tId;
        $tTitle = $tRow['title'] ?? 'Untitled Task';
        $ownerId = (int)($tRow['assigned_by'] ?? 0);

        if (!$isTestMode && $stmtCheckNotif && $stmtInsertNotif) {
            $stmtCheckNotif->bind_param('ii', $uid, $tId);
            $stmtCheckNotif->execute();
            $dupNotif = $stmtCheckNotif->get_result()->num_rows > 0;
            if (!$dupNotif) {
                $notifMsg = "Due Today Reminder: Task {$tCode} \"{$tTitle}\" is due today ({$todayFormatted}) [Role: {$roleTag}].";
                $stmtInsertNotif->bind_param('iiis', $uid, $tId, $ownerId, $notifMsg);
                if ($stmtInsertNotif->execute()) {
                    $notificationsCreated++;
                }
            }
        }
    }

    if ($targetEmail === '' || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
        $skippedNoEmail++;
        continue;
    }

    // Duplicate prevention: only 1 daily digest email per recipient per day
    if (!$forceSend && $stmtCheckDup) {
        $stmtCheckDup->bind_param('s', $targetEmail);
        $stmtCheckDup->execute();
        if ($stmtCheckDup->get_result()->num_rows > 0) {
            $skippedDuplicates++;
            continue;
        }
    }

    $taskCount = count($taskEntries);
    $subject = "Due Today Task Reminder ({$todayFormatted}) - {$taskCount} Task" . ($taskCount === 1 ? '' : 's');
    if ($isTestMode) {
        $subject = "[TEST for {$userName}] " . $subject;
    }

    // Build rows HTML for all Due Today tasks for this user
    $rowsHtml = '';
    foreach ($taskEntries as $tId => $entry) {
        $tRow = $entry['task'];
        $roleTag = implode(', ', $entry['roles']);
        $tCode = 'T-' . $tId;
        $tTitle = htmlspecialchars($tRow['title'] ?? 'Untitled Task');
        $tProject = htmlspecialchars($tRow['project_name'] ?: 'General');
        $tOwner = htmlspecialchars($tRow['owner_name'] ?: '—');
        $tAssignee = htmlspecialchars($tRow['assignee_name'] ?: '—');
        $tPriority = ucfirst(strtolower($tRow['priority'] ?? 'medium'));
        $tStatus = ['pending' => 'To do', 'in_progress' => 'In progress', 'expired' => 'Expired'][$tRow['status']] ?? 'To do';

        $prioColor = '#087F5B';
        if (strtolower($tPriority) === 'urgent' || strtolower($tPriority) === 'high') {
            $prioColor = '#C92A2A';
        } elseif (strtolower($tPriority) === 'medium') {
            $prioColor = '#D97706';
        }

        $rowsHtml .= "
        <div style='background-color: #F8FAF9; border-left: 4px solid #087F5B; border-radius: 6px; padding: 14px 16px; margin-bottom: 14px;'>
            <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;'>
                <span style='font-size: 12px; font-weight: 700; color: #087F5B;'>" . htmlspecialchars($tCode) . " &middot; {$tProject}</span>
                <span style='font-size: 11px; font-weight: 700; background: #E3F6EE; color: #087F5B; padding: 2px 8px; border-radius: 99px;'>Role: " . htmlspecialchars($roleTag) . "</span>
            </div>
            <div style='font-size: 15px; font-weight: 700; color: #142D26; margin-bottom: 8px;'>{$tTitle}</div>
            <table style='width: 100%; font-size: 12.5px; color: #4A5D55; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 3px 0;'><strong>Owner:</strong> {$tOwner}</td>
                    <td style='padding: 3px 0;'><strong>Assigned To:</strong> {$tAssignee}</td>
                </tr>
                <tr>
                    <td style='padding: 3px 0;'><strong>Deadline:</strong> <span style='color: #C92A2A; font-weight: 700;'>Today ({$todayFormatted})</span></td>
                    <td style='padding: 3px 0;'><strong>Priority:</strong> <span style='color: {$prioColor}; font-weight: 700;'>" . htmlspecialchars($tPriority) . "</span> &middot; <strong>Status:</strong> " . htmlspecialchars($tStatus) . "</td>
                </tr>
            </table>
        </div>";

        $notifiedTaskUsers[$tId][] = "{$userName} ({$roleTag})";
    }

    $emailBody = "
    <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; padding: 32px 15px;'>
        <div style='max-width: 620px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(20,45,38,0.08); border: 1px solid #e2eae5;'>
            <div style='background: #087F5B; padding: 20px 26px;'>
                <div style='font-size: 11px; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,0.85); margin-bottom: 4px;'>TPHL ERP &middot; Daily Deadline Reminder</div>
                <h2 style='margin: 0; color: #ffffff; font-size: 19px; font-weight: 700;'>You Have {$taskCount} Task" . ($taskCount === 1 ? '' : 's') . " Due Today ({$todayFormatted})</h2>
            </div>
            <div style='padding: 26px;'>
                <p style='color: #142D26; font-size: 15px; margin: 0 0 12px;'>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p style='color: #4A5D55; font-size: 14px; line-height: 1.6; margin: 0 0 20px;'>
                    This is an automated daily reminder that the following task" . ($taskCount === 1 ? ' is' : 's are') . " scheduled for completion <strong>today ({$todayFormatted})</strong>:
                </p>
                {$rowsHtml}
                <div style='text-align: center; margin: 24px 0 8px;'>
                    <a href='{$dashboardUrl}' style='background-color: #087F5B; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>Open My Work Dashboard &rarr;</a>
                </div>
            </div>
            <div style='background-color: #F8FAF9; padding: 14px 26px; text-align: center; border-top: 1px solid #E2EAE5;'>
                <p style='margin: 0; font-size: 11.5px; color: #8A9992;'>Automated Daily Deadline Reminder &middot; TPHL ERP</p>
            </div>
        </div>
    </div>";

    if ($stmtInsertQueue) {
        $stmtInsertQueue->bind_param('sss', $targetEmail, $subject, $emailBody);
        if ($stmtInsertQueue->execute()) {
            $emailsQueued++;
        }
    }

    // In test mode, only queue once for the first matched user to avoid flooding test inbox
    if ($isTestMode) {
        break;
    }
}

// Log activity once per task when daily reminders are queued
if (!$isTestMode && !empty($notifiedTaskUsers)) {
    $stmtAct = $db->prepare("
        INSERT INTO task_activities (task_id, user_id, type, activity, created_at)
        VALUES (?, ?, 'system', ?, NOW())
    ");
    if ($stmtAct) {
        foreach ($notifiedTaskUsers as $tId => $recipientLabels) {
            $ownerId = (int)($dueTodayTasks[$tId]['assigned_by'] ?? 0);
            $actText = 'Automated Daily Due-Today Reminder queued for ' . implode(', ', $recipientLabels) . '.';
            $stmtAct->bind_param('iis', $tId, $ownerId, $actText);
            $stmtAct->execute();
        }
        $stmtAct->close();
    }
}

// 4. Trigger background queue processor if emails were queued
if ($emailsQueued > 0) {
    $mailService = new MailService($db);
    $mailService->triggerQueueProcessorAsync();
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}
echo json_encode([
    'status'                => 'success',
    'date'                  => $todayFormatted,
    'test_mode'             => $isTestMode,
    'tasks_due_today'       => count($dueTodayTasks),
    'recipients_matched'    => count($userTasksMap),
    'emails_queued'         => $emailsQueued,
    'notifications_created' => $notificationsCreated,
    'skipped_duplicates'    => $skippedDuplicates,
    'skipped_no_email'      => $skippedNoEmail
], JSON_PRETTY_PRINT);
exit;
