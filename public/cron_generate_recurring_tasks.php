<?php
/**
 * TPHL ERP Daily Recurring Task Generator Cron
 * -------------------------------------------------------------
 * Runs daily at 10:00 AM (Sat-Thu; Friday off) and generates today's scheduled
 * occurrences from root recurring tasks (`is_recurring = 1` AND
 * `parent_recurring_task_id IS NULL`) in the `tasks` table.
 *
 * Crontab Schedule (10:00 AM daily, Friday off):
 *   0 10 * * 0-4,6 php /path/to/TPHL-ERP/public/cron_generate_recurring_tasks.php TphlSecretCron2026 >> /path/to/TPHL-ERP/storage/logs/recurring_cron.log 2>&1
 *
 * Trigger Options:
 * - CLI (Linux Crontab / Windows Task Scheduler):
 *   php public/cron_generate_recurring_tasks.php TphlSecretCron2026
 *   php public/cron_generate_recurring_tasks.php TphlSecretCron2026 2026-11-20 --dry-run
 * - URL (Crontab / Browser):
 *   curl -s "https://your-domain.com/TPHL-ERP/public/cron_generate_recurring_tasks.php?token=TphlSecretCron2026"
 *   curl -s "https://your-domain.com/TPHL-ERP/public/cron_generate_recurring_tasks.php?token=TphlSecretCron2026&date=2026-11-20&dry_run=1"
 */

date_default_timezone_set('Asia/Dhaka');

/**
 * Determine whether a root recurring task should spawn an occurrence on $targetDate (YYYY-MM-DD).
 */
function shouldRecurringTaskRunOnDate(array $task, string $targetDate): bool
{
    if (empty($task['is_recurring']) || empty($task['recurrence_type'])) {
        return false;
    }

    $targetTs = strtotime($targetDate);
    if ($targetTs === false) {
        return false;
    }

    // Friday is weekly off (5 = Friday)
    if ((int)date('w', $targetTs) === 5) {
        return false;
    }

    // Anchor date is the root task's own scheduled/initial date.
    // The root task itself represents the anchor date, so cron only spawns strictly after it.
    $anchorRaw = !empty($task['deadline'])
        ? $task['deadline']
        : (!empty($task['start_date']) ? $task['start_date'] : ($task['created_at'] ?? ''));
    $anchorDate = $anchorRaw !== '' ? substr((string)$anchorRaw, 0, 10) : '';
    if ($anchorDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $anchorDate)) {
        return false;
    }

    if ($targetDate <= $anchorDate) {
        return false;
    }

    if (!empty($task['recurrence_end_date'])) {
        $endDate = substr((string)$task['recurrence_end_date'], 0, 10);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate) && $targetDate > $endDate) {
            return false;
        }
    }

    $type = strtolower(trim((string)$task['recurrence_type']));
    $anchorTs = strtotime($anchorDate);
    if ($anchorTs === false) {
        return false;
    }

    if ($type === 'daily') {
        return true;
    }

    if ($type === 'weekly') {
        return (int)date('w', $targetTs) === (int)date('w', $anchorTs);
    }

    if ($type === 'monthly') {
        $anchorDay = (int)date('j', $anchorTs);
        $targetDay = (int)date('j', $targetTs);
        $lastDayOfTargetMonth = (int)date('t', $targetTs);
        // Clamp e.g. 31 -> 28/29 in Feb, 30 in 30-day months
        return min($anchorDay, $lastDayOfTargetMonth) === $targetDay;
    }

    if ($type === 'custom') {
        $rawCustom = !empty($task['recurrence_custom_dates'])
            ? json_decode((string)$task['recurrence_custom_dates'], true)
            : [];
        if (!is_array($rawCustom) || empty($rawCustom)) {
            return false;
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
            $todayWeekday = strtolower(date('D', $targetTs)); // sun..sat
            return in_array($todayWeekday, $weekdays, true);
        }

        if (!empty($explicitDates)) {
            return in_array($targetDate, $explicitDates, true);
        }
    }

    return false;
}

/**
 * Reset all checklist item completion flags for a newly spawned occurrence.
 */
function resetRecurringChecklist(?string $checklistJson): ?string
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

// Allow including this file in unit/CLI checks without running the HTTP/DB entrypoint.
if (realpath(__FILE__) !== realpath($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    return;
}

set_time_limit(0);
ignore_user_abort(true);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$isCli = (PHP_SAPI === 'cli');
$expectedToken = 'TphlSecretCron2026';
$providedToken = $isCli ? ($argv[1] ?? '') : ($_GET['token'] ?? '');

if (!hash_equals($expectedToken, (string)$providedToken)) {
    if (!$isCli) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid cron token.']) . PHP_EOL;
    exit(1);
}

$targetDate = date('Y-m-d');
$dryRun = false;

if ($isCli) {
    foreach (array_slice($argv, 2) as $arg) {
        if ($arg === '--dry-run') {
            $dryRun = true;
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $arg)) {
            $targetDate = $arg;
        }
    }
} else {
    if (!empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
        $targetDate = $_GET['date'];
    }
    $dryRun = !empty($_GET['dry_run']);
}

// Friday is weekly off (5 = Friday) — skip before touching DB
if ((int)date('w', strtotime($targetDate)) === 5) {
    if (!$isCli) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'skipped',
        'date' => $targetDate,
        'dry_run' => $dryRun,
        'reason' => 'Friday is off (weekly holiday).',
        'tasks_created' => 0,
    ], JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$database = new Database($config);
$db = $database->conn;

$sql = "
    SELECT *
    FROM tasks
    WHERE is_recurring = 1
      AND parent_recurring_task_id IS NULL
      AND recurrence_type IS NOT NULL
      AND (recurrence_end_date IS NULL OR recurrence_end_date >= ?)
    ORDER BY id ASC
";

$stFetch = $db->prepare($sql);
if (!$stFetch) {
    if (!$isCli) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to query recurring tasks. Ensure migration Asif/2026_11_19_add_recurring_cron_columns_to_tasks.sql has been applied.'
    ]) . PHP_EOL;
    exit(1);
}

$stFetch->bind_param("s", $targetDate);
$stFetch->execute();
$res = $stFetch->get_result();

$roots = [];
while ($row = $res->fetch_assoc()) {
    $roots[] = $row;
}
$stFetch->close();

$summary = [
    'status' => 'success',
    'date' => $targetDate,
    'dry_run' => $dryRun,
    'roots_checked' => count($roots),
    'matched' => 0,
    'tasks_created' => 0,
    'skipped_existing' => 0,
    'created_task_ids' => [],
    'matched_root_ids' => [],
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
    if (!$isCli) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare task insert statement.']) . PHP_EOL;
    exit(1);
}

$deadlineDt = $targetDate . ' 00:00:00';

foreach ($roots as $root) {
    if (!shouldRecurringTaskRunOnDate($root, $targetDate)) {
        continue;
    }

    $rootId = (int)$root['id'];
    $summary['matched']++;
    $summary['matched_root_ids'][] = $rootId;

    if ($dryRun) {
        continue;
    }

    $title = (string)$root['title'];
    $expectedOutput = $root['expected_output'];
    $userId = (int)$root['user_id'];
    $assignedBy = (int)$root['assigned_by'];
    $todoProjectId = !empty($root['todo_project_id']) ? (int)$root['todo_project_id'] : null;
    $description = $root['description'];
    $checklistData = resetRecurringChecklist($root['checklist_data'] ?? null);
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

    $stInsert->bind_param(
        "ssiiisssssdsssissssis",
        $title,
        $expectedOutput,
        $userId,
        $assignedBy,
        $todoProjectId,
        $description,
        $checklistData,
        $targetDate,
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
        $targetDate
    );

    if ($stInsert->execute()) {
        if ($stInsert->affected_rows > 0) {
            $newTaskId = (int)$stInsert->insert_id;
            $summary['tasks_created']++;
            $summary['created_task_ids'][] = $newTaskId;

            if ($stActivity) {
                $actMsg = "Auto-generated recurring task from T-{$rootId} for {$targetDate}.";
                $stActivity->bind_param("iis", $newTaskId, $assignedBy, $actMsg);
                $stActivity->execute();
            }

            if ($stNotif) {
                $recipients = [];
                if ($userId > 0) {
                    $recipients[$userId] = "New recurring task T-{$newTaskId}: {$title}";
                }
                if (!empty($collaboratorUserIds)) {
                    $collabs = json_decode((string)$collaboratorUserIds, true);
                    if (is_array($collabs)) {
                        foreach ($collabs as $cid) {
                            $cid = (int)$cid;
                            if ($cid > 0 && !isset($recipients[$cid])) {
                                $recipients[$cid] = "You are a collaborator on recurring task T-{$newTaskId}: {$title}";
                            }
                        }
                    }
                }
                foreach ($recipients as $uid => $msg) {
                    $stNotif->bind_param("iiis", $uid, $newTaskId, $assignedBy, $msg);
                    $stNotif->execute();
                }
            }
        } else {
            $summary['skipped_existing']++;
        }
    }
}

if ($stInsert) $stInsert->close();
if ($stActivity) $stActivity->close();
if ($stNotif) $stNotif->close();

if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
}
echo json_encode($summary, JSON_UNESCAPED_UNICODE) . PHP_EOL;
