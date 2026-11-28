<?php
/**
 * TPHL ERP Recurring Task Generator & Email Notifier Cron (1-Day Advance for Weekly & Monthly)
 * --------------------------------------------------------------------------------------------
 * Runs daily at 10:00 AM (Sat-Thu; Friday off):
 *   - Daily & Custom tasks : created on the same day (`runDate`).
 *   - Weekly & Monthly tasks: created 1 day in advance (`runDate + 1 day`).
 *   - Thursday special rule : since Friday is off, Saturday's Weekly & Monthly tasks
 *                             (`runDate + 2 days`) are created on Thursday.
 *   - Sends in-app notifications and consolidated notification emails (via email_queue +
 *     MailService) for ALL newly generated recurring tasks.
 *
 * Crontab Schedule (10:00 AM daily, Sat-Thu, Friday off):
 *   0 10 * * 0-4,6 curl -fsS "https://thepremiumhomesltd.com/TPHL-ERP/public/cron_recurring_tasks_advance.php?token=TphlSecretCron2026" > /dev/null 2>&1
 *
 * CLI / Testing Options:
 *   php public/cron_recurring_tasks_advance.php TphlSecretCron2026
 *   php public/cron_recurring_tasks_advance.php TphlSecretCron2026 2026-11-26 --dry-run
 *   https://your-domain.com/TPHL-ERP/public/cron_recurring_tasks_advance.php?token=TphlSecretCron2026&dry_run=1
 *   https://your-domain.com/TPHL-ERP/public/cron_recurring_tasks_advance.php?token=TphlSecretCron2026&date=2026-11-26&dry_run=1
 */

date_default_timezone_set('Asia/Dhaka');

/**
 * Resolve the target occurrence date (YYYY-MM-DD) for a recurrence type when cron runs on $runDate.
 * - Daily / Custom : same day ($runDate)
 * - Weekly / Monthly: 1 day in advance ($runDate + 1 day), or Saturday ($runDate + 2 days) when $runDate is Thursday (Friday off)
 */
function getAdvanceTargetDateForRecurrence(string $recurrenceType, string $runDate): ?string
{
    $runTs = strtotime($runDate);
    if ($runTs === false) {
        return null;
    }

    $runDow = (int)date('w', $runTs); // 0=Sun..4=Thu, 5=Fri, 6=Sat
    if ($runDow === 5) {
        return null; // Friday off
    }

    $type = strtolower(trim($recurrenceType));
    if ($type === 'daily' || $type === 'custom') {
        return date('Y-m-d', $runTs);
    }

    if ($type === 'weekly' || $type === 'monthly') {
        // Thursday -> look ahead to Saturday (+2 days) because Friday is weekly off
        $offsetDays = ($runDow === 4) ? 2 : 1;
        return date('Y-m-d', strtotime($runDate . " +{$offsetDays} days"));
    }

    return null;
}

/**
 * Determine whether a root recurring task should spawn when cron runs on $runDate.
 * Returns the scheduled occurrence date (YYYY-MM-DD) if it should run, or null otherwise.
 */
function shouldAdvanceRecurringTaskRun(array $task, string $runDate): ?string
{
    if (empty($task['is_recurring']) || empty($task['recurrence_type'])) {
        return null;
    }

    $type = strtolower(trim((string)$task['recurrence_type']));
    $occurrenceDate = getAdvanceTargetDateForRecurrence($type, $runDate);
    if ($occurrenceDate === null) {
        return null;
    }

    $occTs = strtotime($occurrenceDate);
    if ($occTs === false || (int)date('w', $occTs) === 5) {
        return null; // Friday is weekly off
    }

    // Anchor date: prefer start_date -> created_at -> deadline so a future deadline never blocks generation
    $anchorDate = '';
    foreach ([$task['start_date'] ?? '', $task['created_at'] ?? '', $task['deadline'] ?? ''] as $candidate) {
        $d = substr((string)$candidate, 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && $d !== '0000-00-00') {
            $anchorDate = $d;
            break;
        }
    }
    if ($anchorDate === '' || $occurrenceDate <= $anchorDate) {
        return null;
    }

    // Optional end date:
    // - If recurrence_end_date is set, stop after it.
    // - Else if root task has a deadline strictly after anchorDate, treat it as the end date.
    // - If no deadline / end date is set, the recurring task runs infinitely.
    $effectiveEndDate = '';
    if (!empty($task['recurrence_end_date'])) {
        $d = substr((string)$task['recurrence_end_date'], 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && $d !== '0000-00-00') {
            $effectiveEndDate = $d;
        }
    }
    if ($effectiveEndDate === '' && !empty($task['deadline'])) {
        $dl = substr((string)$task['deadline'], 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dl) && $dl !== '0000-00-00' && $dl > $anchorDate) {
            $effectiveEndDate = $dl;
        }
    }
    if ($effectiveEndDate !== '' && $occurrenceDate > $effectiveEndDate) {
        return null;
    }

    $anchorTs = strtotime($anchorDate);
    if ($anchorTs === false) {
        return null;
    }

    if ($type === 'daily') {
        return $occurrenceDate;
    }

    if ($type === 'weekly') {
        return ((int)date('w', $occTs) === (int)date('w', $anchorTs)) ? $occurrenceDate : null;
    }

    if ($type === 'monthly') {
        $anchorDay = (int)date('j', $anchorTs);
        $occDay = (int)date('j', $occTs);
        $lastDayOfOccMonth = (int)date('t', $occTs);
        return (min($anchorDay, $lastDayOfOccMonth) === $occDay) ? $occurrenceDate : null;
    }

    if ($type === 'custom') {
        $rawCustom = !empty($task['recurrence_custom_dates'])
            ? json_decode((string)$task['recurrence_custom_dates'], true)
            : [];
        if (!is_array($rawCustom) || empty($rawCustom)) {
            return null;
        }

        $weekdayKeys = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];
        $weekdays = [];
        $explicitDates = [];

        foreach ($rawCustom as $item) {
            $val = strtolower(trim((string)$item));
            if (in_array($val, $weekdayKeys, true)) {
                $weekdays[] = $val;
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $val)) {
                $explicitDates[] = $val;
            }
        }

        if (!empty($weekdays)) {
            $occWeekday = strtolower(date('D', $occTs));
            return in_array($occWeekday, $weekdays, true) ? $occurrenceDate : null;
        }

        if (!empty($explicitDates)) {
            return in_array($occurrenceDate, $explicitDates, true) ? $occurrenceDate : null;
        }
    }

    return null;
}

/**
 * Reset all checklist item completion flags for a newly spawned occurrence.
 */
function resetAdvanceRecurringChecklist(?string $checklistJson): ?string
{
    if (empty($checklistJson)) {
        return $checklistJson;
    }
    $decoded = json_decode($checklistJson, true);
    if (!is_array($decoded)) {
        return $checklistJson;
    }
    foreach ($decoded as &$item) {
        if (is_array($item)) {
            $item['completed'] = false;
        }
    }
    unset($item);
    return json_encode($decoded, JSON_UNESCAPED_UNICODE);
}

/**
 * Parse JSON or CSV user ID lists into unique positive integers.
 */
function parseAdvanceUserIds($value): array
{
    return array_values(array_unique(array_filter(
        array_map('intval', preg_match_all('/\d+/', (string)$value, $m) ? $m[0] : [])
    )));
}

// Allow including this file in unit/CLI checks without running the HTTP/DB entrypoint.
$scriptFile = $_SERVER['SCRIPT_FILENAME'] ?? '';
if ($scriptFile !== '' && realpath($scriptFile) !== false && realpath(__FILE__) !== realpath($scriptFile)) {
    return;
}

set_time_limit(0);
ignore_user_abort(true);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$isHttp = !empty($_SERVER['REQUEST_METHOD']) && PHP_SAPI !== 'cli';
$cliArgs = (isset($argv) && is_array($argv)) ? $argv : [];
$expectedToken = 'TphlSecretCron2026';
$providedToken = (string)($_GET['token'] ?? ($cliArgs[1] ?? ''));

if (!hash_equals($expectedToken, $providedToken)) {
    if ($isHttp) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid cron token.']) . PHP_EOL;
    exit(1);
}

$runDate = date('Y-m-d');
$dryRun = false;
$testEmail = trim((string)($_GET['test_email'] ?? ''));

foreach (array_slice($cliArgs, 2) as $arg) {
    $argStr = (string)$arg;
    if ($argStr === '--dry-run') {
        $dryRun = true;
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $argStr)) {
        $runDate = $argStr;
    } elseif (filter_var($argStr, FILTER_VALIDATE_EMAIL)) {
        $testEmail = $argStr;
    }
}
if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$_GET['date'])) {
    $runDate = (string)$_GET['date'];
}
if (!empty($_GET['dry_run'])) {
    $dryRun = true;
}
$isTestEmailMode = ($testEmail !== '' && filter_var($testEmail, FILTER_VALIDATE_EMAIL));

// Friday is weekly off (5 = Friday) — skip before touching DB
if ((int)date('w', strtotime($runDate)) === 5) {
    if ($isHttp) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'skipped',
        'run_date' => $runDate,
        'dry_run' => $dryRun,
        'reason' => 'Friday is off (weekly holiday).',
        'tasks_created' => 0,
        'emails_queued' => 0,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/MailServiceForPHPMailer.php';

$database = new Database($config);
$db = $database->conn;

// Ensure email_queue table exists (matches cron_task_deadline_reminder.php)
if (!$dryRun) {
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
}

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
    WHERE t.is_recurring = 1
      AND t.parent_recurring_task_id IS NULL
      AND t.recurrence_type IS NOT NULL
      AND (t.recurrence_end_date IS NULL OR t.recurrence_end_date >= ?)
    ORDER BY t.id ASC
";

$stFetch = $db->prepare($sql);
if (!$stFetch) {
    if ($isHttp) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to query recurring tasks. Ensure migration Asif/2026_11_19_add_recurring_cron_columns_to_tasks.sql has been applied.'
    ]) . PHP_EOL;
    exit(1);
}

$stFetch->bind_param("s", $runDate);
$stFetch->execute();
$res = $stFetch->get_result();

$roots = [];
while ($row = $res->fetch_assoc()) {
    $roots[] = $row;
}
$stFetch->close();

$advanceTargetPreview = getAdvanceTargetDateForRecurrence('weekly', $runDate);

$summary = [
    'status' => 'success',
    'run_date' => $runDate,
    'advance_target_date' => $advanceTargetPreview,
    'dry_run' => $dryRun,
    'test_email_mode' => $isTestEmailMode,
    'roots_checked' => count($roots),
    'matched' => 0,
    'tasks_created' => 0,
    'skipped_existing' => 0,
    'notifications_created' => 0,
    'emails_queued' => 0,
    'skipped_no_email' => 0,
    'created_task_ids' => [],
    'matched_items' => [],
    'errors' => [],
];

$insertSql = "
    INSERT IGNORE INTO tasks (
        title, expected_output, user_id, assigned_by, todo_project_id,
        description, checklist_data, start_date, deadline, priority,
        working_hour, status, progress, employee_accept, mentioned_user_ids,
        cc_user_ids, attachment_image, project_id,
        is_recurring, recurrence_type, recurrence_end_date, recurrence_custom_dates,
        collaborator_user_ids, parent_recurring_task_id, recurrence_occurrence_date, created_at
    ) VALUES (
        ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?,
        ?, 'pending', 0, 0, ?,
        ?, ?, ?,
        1, ?, ?, ?,
        ?, ?, ?, NOW()
    )
";

$stInsert = $dryRun ? null : $db->prepare($insertSql);
$stActivity = $dryRun ? null : $db->prepare("
    INSERT INTO task_activities (task_id, user_id, type, activity, created_at)
    VALUES (?, ?, 'system', ?, NOW())
");
$stNotif = $dryRun ? null : $db->prepare("
    INSERT INTO notifications (user_id, task_id, assigned_by, message, created_at)
    VALUES (?, ?, ?, ?, NOW())
");

if (!$dryRun && !$stInsert) {
    if ($isHttp) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare task insert statement.']) . PHP_EOL;
    exit(1);
}

$createdTasks = []; // Newly created tasks to notify via email

foreach ($roots as $root) {
    $occurrenceDate = shouldAdvanceRecurringTaskRun($root, $runDate);
    if ($occurrenceDate === null) {
        continue;
    }

    $rootId = (int)$root['id'];
    $isAdvance = ($occurrenceDate > $runDate);
    $recType = strtolower(trim((string)$root['recurrence_type']));

    $summary['matched']++;
    $summary['matched_items'][] = [
        'root_id' => $rootId,
        'recurrence_type' => $recType,
        'occurrence_date' => $occurrenceDate,
        'is_advance' => $isAdvance,
    ];

    if ($dryRun) {
        continue;
    }

    $title = (string)$root['title'];
    $expectedOutput = $root['expected_output'];
    $userId = (int)$root['user_id'];
    $assignedBy = (int)$root['assigned_by'];
    $todoProjectId = !empty($root['todo_project_id']) ? (int)$root['todo_project_id'] : null;
    $description = $root['description'];
    $checklistData = resetAdvanceRecurringChecklist($root['checklist_data'] ?? null);
    $priority = (string)($root['priority'] ?? 'medium');
    $workingHour = ($root['working_hour'] !== null && $root['working_hour'] !== '')
        ? (float)$root['working_hour']
        : null;
    $mentionedUserIds = $root['mentioned_user_ids'];
    $ccUserIds = $root['cc_user_ids'];
    $attachmentImage = $root['attachment_image'];
    $projectId = !empty($root['project_id']) ? (int)$root['project_id'] : null;
    $recurrenceType = $root['recurrence_type'];
    $recurrenceEndDate = $root['recurrence_end_date'];
    $recurrenceCustomDates = $root['recurrence_custom_dates'];
    $collaboratorUserIds = $root['collaborator_user_ids'];
    $deadlineDt = $occurrenceDate . ' 00:00:00';

    $stInsert->bind_param(
        "ssiiisssssdsssissssis",
        $title,
        $expectedOutput,
        $userId,
        $assignedBy,
        $todoProjectId,
        $description,
        $checklistData,
        $occurrenceDate,
        $deadlineDt,
        $priority,
        $workingHour,
        $mentionedUserIds,
        $ccUserIds,
        $attachmentImage,
        $projectId,
        $recurrenceType,
        $recurrenceEndDate,
        $recurrenceCustomDates,
        $collaboratorUserIds,
        $rootId,
        $occurrenceDate
    );

    try {
        $executed = $stInsert->execute();
    } catch (\Throwable $e) {
        $executed = false;
        $summary['errors'][] = "Root T-{$rootId}: " . $e->getMessage();
    }

    if ($executed) {
        if ($stInsert->affected_rows > 0) {
            $newTaskId = (int)$stInsert->insert_id;
            $summary['tasks_created']++;
            $summary['created_task_ids'][] = $newTaskId;

            $occFormatted = date('d M Y (D)', strtotime($occurrenceDate));
            $advanceTag = $isAdvance ? ' (created in advance)' : '';

            if ($stActivity) {
                $actMsg = "Auto-generated {$recType} recurring task from T-{$rootId} for {$occurrenceDate}{$advanceTag}.";
                $stActivity->bind_param("iis", $newTaskId, $assignedBy, $actMsg);
                $stActivity->execute();
            }

            if ($stNotif) {
                $notifRecipients = [];
                if ($userId > 0) {
                    $notifRecipients[$userId] = $isAdvance
                        ? "Upcoming {$recType} recurring task T-{$newTaskId} scheduled for {$occFormatted}: {$title}"
                        : "New {$recType} recurring task T-{$newTaskId} for today ({$occFormatted}): {$title}";
                }
                foreach (parseAdvanceUserIds($collaboratorUserIds) as $cid) {
                    if ($cid > 0 && !isset($notifRecipients[$cid])) {
                        $notifRecipients[$cid] = $isAdvance
                            ? "You are a collaborator on upcoming {$recType} recurring task T-{$newTaskId} ({$occFormatted}): {$title}"
                            : "You are a collaborator on {$recType} recurring task T-{$newTaskId}: {$title}";
                    }
                }
                foreach ($notifRecipients as $uid => $msg) {
                    $stNotif->bind_param("iiis", $uid, $newTaskId, $assignedBy, $msg);
                    if ($stNotif->execute()) {
                        $summary['notifications_created']++;
                    }
                }
            }

            $createdTasks[$newTaskId] = [
                'id' => $newTaskId,
                'root_id' => $rootId,
                'title' => $title,
                'project_name' => $root['project_name'] ?: 'General',
                'owner_name' => $root['owner_name'] ?: '—',
                'assignee_name' => $root['assignee_name'] ?: '—',
                'priority' => $priority,
                'recurrence_type' => $recType,
                'occurrence_date' => $occurrenceDate,
                'is_advance' => $isAdvance,
                'user_id' => $userId,
                'assigned_by' => $assignedBy,
                'collaborator_user_ids' => $collaboratorUserIds,
                'mentioned_user_ids' => $mentionedUserIds,
                'cc_user_ids' => $ccUserIds,
            ];
        } else {
            $summary['skipped_existing']++;
        }
    } elseif ($stInsert->error !== '') {
        $summary['errors'][] = "Root T-{$rootId}: " . $stInsert->error;
    }
}

if ($stInsert) $stInsert->close();
if ($stActivity) $stActivity->close();
if ($stNotif) $stNotif->close();

// Queue consolidated notification emails for ALL newly created recurring tasks
if (!$dryRun && !empty($createdTasks)) {
    $userTasksMap = [];
    $allUserIds = [];

    foreach ($createdTasks as $tId => $taskItem) {
        $taskRoleMap = [];
        if ($taskItem['user_id'] > 0) {
            $taskRoleMap[$taskItem['user_id']][] = 'Assignee';
        }
        foreach (parseAdvanceUserIds($taskItem['collaborator_user_ids']) as $cid) {
            $taskRoleMap[$cid][] = 'Collaborator';
        }
        foreach (parseAdvanceUserIds($taskItem['mentioned_user_ids']) as $rid) {
            $taskRoleMap[$rid][] = 'Reviewer';
        }
        foreach (parseAdvanceUserIds($taskItem['cc_user_ids']) as $ccid) {
            $taskRoleMap[$ccid][] = 'CC';
        }

        foreach ($taskRoleMap as $uid => $roles) {
            $allUserIds[$uid] = $uid;
            $userTasksMap[$uid][$tId] = [
                'task' => $taskItem,
                'roles' => array_unique($roles),
            ];
        }
    }

    if (!empty($allUserIds)) {
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

        $protocol = (PHP_SAPI === 'cli' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? 0) == 443) ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'thepremiumhomesltd.com';
        $basePath = rtrim($GLOBALS['base_url'] ?? '/TPHL-ERP', '/');
        $dashboardUrl = $protocol . $host . $basePath . '/my-work-dashboard';
        $runDateFormatted = date('d M Y', strtotime($runDate));

        $stmtInsertQueue = $db->prepare("
            INSERT INTO email_queue (to_email, subject, body, status)
            VALUES (?, ?, ?, 'pending')
        ");

        foreach ($userTasksMap as $uid => $taskEntries) {
            $user = $usersInfo[$uid] ?? null;
            if (!$user) {
                continue;
            }

            $userName = $user['full_name'] ?: 'Team Member';
            $targetEmail = $isTestEmailMode ? $testEmail : trim((string)($user['email'] ?? ''));

            if ($targetEmail === '' || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
                $summary['skipped_no_email']++;
                continue;
            }

            $taskCount = count($taskEntries);
            $hasAdvance = false;
            $rowsHtml = '';

            foreach ($taskEntries as $tId => $entry) {
                $tRow = $entry['task'];
                $roleTag = implode(', ', $entry['roles']);
                $tCode = 'T-' . $tId;
                $tTitle = htmlspecialchars($tRow['title']);
                $tProject = htmlspecialchars($tRow['project_name']);
                $tOwner = htmlspecialchars($tRow['owner_name']);
                $tAssignee = htmlspecialchars($tRow['assignee_name']);
                $tPriority = ucfirst(strtolower($tRow['priority']));
                $tRecLabel = ucfirst($tRow['recurrence_type']);
                $occLabel = date('d M Y (D)', strtotime($tRow['occurrence_date']));

                if ($tRow['is_advance']) {
                    $hasAdvance = true;
                }

                $prioColor = '#087F5B';
                if (strtolower($tPriority) === 'urgent' || strtolower($tPriority) === 'high') {
                    $prioColor = '#C92A2A';
                } elseif (strtolower($tPriority) === 'medium') {
                    $prioColor = '#D97706';
                }

                $advanceBadge = $tRow['is_advance']
                    ? "<span style='font-size: 11px; font-weight: 700; background: #FFF4E6; color: #D9480F; padding: 2px 8px; border-radius: 99px; margin-left: 6px;'>Advance Notice</span>"
                    : "<span style='font-size: 11px; font-weight: 700; background: #E7F5FF; color: #1971C2; padding: 2px 8px; border-radius: 99px; margin-left: 6px;'>Today</span>";

                $rowsHtml .= "
                <div style='background-color: #F8FAF9; border-left: 4px solid #087F5B; border-radius: 6px; padding: 14px 16px; margin-bottom: 14px;'>
                    <div style='margin-bottom: 6px;'>
                        <span style='font-size: 12px; font-weight: 700; color: #087F5B;'>" . htmlspecialchars($tCode) . " &middot; {$tProject}</span>
                        <span style='font-size: 11px; font-weight: 700; background: #E3F6EE; color: #087F5B; padding: 2px 8px; border-radius: 99px; margin-left: 6px;'>" . htmlspecialchars($tRecLabel) . "</span>
                        {$advanceBadge}
                        <span style='font-size: 11px; font-weight: 700; background: #F1F3F5; color: #495057; padding: 2px 8px; border-radius: 99px; margin-left: 6px;'>Role: " . htmlspecialchars($roleTag) . "</span>
                    </div>
                    <div style='font-size: 15px; font-weight: 700; color: #142D26; margin-bottom: 8px;'>{$tTitle}</div>
                    <table style='width: 100%; font-size: 12.5px; color: #4A5D55; border-collapse: collapse;'>
                        <tr>
                            <td style='padding: 3px 0;'><strong>Owner:</strong> {$tOwner}</td>
                            <td style='padding: 3px 0;'><strong>Assigned To:</strong> {$tAssignee}</td>
                        </tr>
                        <tr>
                            <td style='padding: 3px 0;'><strong>Scheduled Date:</strong> <span style='color: #142D26; font-weight: 700;'>" . htmlspecialchars($occLabel) . "</span></td>
                            <td style='padding: 3px 0;'><strong>Priority:</strong> <span style='color: {$prioColor}; font-weight: 700;'>" . htmlspecialchars($tPriority) . "</span></td>
                        </tr>
                    </table>
                </div>";
            }

            $subject = ($hasAdvance ? "New & Upcoming Recurring Tasks" : "New Recurring Tasks")
                . " ({$runDateFormatted}) - {$taskCount} Task" . ($taskCount === 1 ? '' : 's');
            if ($isTestEmailMode) {
                $subject = "[TEST for {$userName}] " . $subject;
            }

            $emailBody = "
            <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; padding: 32px 15px;'>
                <div style='max-width: 620px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(20,45,38,0.08); border: 1px solid #e2eae5;'>
                    <div style='background: #087F5B; padding: 20px 26px;'>
                        <div style='font-size: 11px; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,0.85); margin-bottom: 4px;'>TPHL ERP &middot; Recurring Task Notification</div>
                        <h2 style='margin: 0; color: #ffffff; font-size: 19px; font-weight: 700;'>{$taskCount} Recurring Task" . ($taskCount === 1 ? '' : 's') . " Scheduled</h2>
                    </div>
                    <div style='padding: 26px;'>
                        <p style='color: #142D26; font-size: 15px; margin: 0 0 12px;'>Hello <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                        <p style='color: #4A5D55; font-size: 14px; line-height: 1.6; margin: 0 0 20px;'>
                            The following recurring task" . ($taskCount === 1 ? ' has' : 's have') . " been automatically created for you (Weekly &amp; Monthly tasks are created one working day in advance):
                        </p>
                        {$rowsHtml}
                        <div style='text-align: center; margin: 24px 0 8px;'>
                            <a href='{$dashboardUrl}' style='background-color: #087F5B; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>Open My Work Dashboard &rarr;</a>
                        </div>
                    </div>
                    <div style='background-color: #F8FAF9; padding: 14px 26px; text-align: center; border-top: 1px solid #E2EAE5;'>
                        <p style='margin: 0; font-size: 11.5px; color: #8A9992;'>Automated Recurring Task Notification &middot; TPHL ERP</p>
                    </div>
                </div>
            </div>";

            if ($stmtInsertQueue) {
                $stmtInsertQueue->bind_param('sss', $targetEmail, $subject, $emailBody);
                if ($stmtInsertQueue->execute()) {
                    $summary['emails_queued']++;
                }
            }

            if ($isTestEmailMode) {
                break;
            }
        }

        if ($stmtInsertQueue) {
            $stmtInsertQueue->close();
        }

        if ($summary['emails_queued'] > 0) {
            $mailService = new MailService($db);
            $mailService->triggerQueueProcessorAsync();
        }
    }
}

if ($isHttp) {
    header('Content-Type: application/json; charset=utf-8');
}
echo json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
