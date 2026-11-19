<?php
/**
 * TPHL ERP Daily Recurring Tasks Cron
 * -------------------------------------------------------------
 * Checks all active recurring task series (`is_recurring = 1`) and
 * generates today's occurrence (`DATE(deadline) = CURDATE()`) when due
 * for `daily`, `weekly`, `monthly`, and `custom` schedules without duplicating
 * existing tasks for today.
 *
 * Trigger Options:
 * - URL (Crontab / Browser):
 *   curl -s "https://your-domain.com/TPHL-ERP/cron_recurring_tasks.php?token=TphlSecretCron2026"
 * - CLI (Linux Crontab / Windows Task Scheduler):
 *   php public/cron_recurring_tasks.php TphlSecretCron2026
 * - Dry Run (preview without inserting/queuing):
 *   php public/cron_recurring_tasks.php TphlSecretCron2026 --dry-run
 */

set_time_limit(0);
ignore_user_abort(true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$database = new Database($config);
$db = $database->conn;

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

$isDryRun = !empty($_GET['dry_run']) || in_array('--dry-run', $argv ?? [], true);
$today = date('Y-m-d');
$todayDeadline = $today . ' 00:00:00';
$todayFormatted = date('d M Y');

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

// Fetch latest task per recurring series so historical clones don't multiply
$sql = "
    SELECT t.*
    FROM tasks t
    INNER JOIN (
        SELECT MAX(id) AS max_id
        FROM tasks
        WHERE is_recurring = 1
          AND recurrence_type IN ('daily', 'weekly', 'monthly', 'custom')
        GROUP BY user_id, COALESCE(assigned_by, 0), title, recurrence_type
    ) latest ON t.id = latest.max_id
    ORDER BY t.id ASC
";

$res = $db->query($sql);
$seriesList = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$spawnedCount = 0;
$queuedEmailsCount = 0;
$skippedCount = 0;
$details = [];

$stmtExistsToday = $db->prepare("SELECT id FROM tasks WHERE user_id = ? AND title = ? AND DATE(deadline) = ? LIMIT 1");
$stmtInsertTask = $db->prepare("
    INSERT INTO tasks (
        title, expected_output, user_id, assigned_by, todo_project_id,
        description, checklist_data, start_date, deadline, priority,
        working_hour, status, progress, employee_accept, mentioned_user_ids,
        cc_user_ids, attachment_image, project_id,
        is_recurring, recurrence_type, recurrence_end_date, recurrence_custom_dates, collaborator_user_ids, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, NULL, 'pending', 0, 0, ?, ?, ?, ?, 1, ?, ?, ?, ?, NOW())
");
$stmtAct = $db->prepare("INSERT INTO task_activities (task_id, user_id, type, activity, created_at) VALUES (?, ?, 'system', ?, NOW())");
$stmtQueue = $db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
$stmtNotif = $db->prepare("INSERT INTO notifications (user_id, task_id, assigned_by, message, created_at) VALUES (?, ?, ?, ?, NOW())");

$parseIds = static fn($v): array => array_values(array_unique(array_filter(array_map('intval', preg_match_all('/\d+/', (string)$v, $m) ? $m[0] : []))));

$baseUrl = rtrim((string)($config['base_url'] ?? ''), '/');
$dashboardUrl = $baseUrl . '/my-work-dashboard';

foreach ($seriesList as $t) {
    $baseDate = !empty($t['deadline'])
        ? date('Y-m-d', strtotime($t['deadline']))
        : date('Y-m-d', strtotime($t['created_at']));

    // Check recurrence_end_date
    if (!empty($t['recurrence_end_date'])) {
        $endDate = date('Y-m-d', strtotime($t['recurrence_end_date']));
        if ($today > $endDate) {
            $skippedCount++;
            continue;
        }
    }

    $type = (string)$t['recurrence_type'];
    $isDueToday = false;

    if ($today > $baseDate) {
        if ($type === 'daily') {
            $isDueToday = true;
        } elseif ($type === 'weekly') {
            $isDueToday = (date('w', strtotime($today)) === date('w', strtotime($baseDate)));
        } elseif ($type === 'monthly') {
            $targetDay = (int)date('j', strtotime($baseDate));
            $todayDay = (int)date('j', strtotime($today));
            $daysInMonth = (int)date('t', strtotime($today));
            $isDueToday = ($todayDay === min($targetDay, $daysInMonth));
        } elseif ($type === 'custom') {
            $dates = !empty($t['recurrence_custom_dates']) ? json_decode((string)$t['recurrence_custom_dates'], true) : [];
            if (is_array($dates) && count($dates) > 1) {
                $isDueToday = in_array($today, $dates, true);
            } else {
                $startDate = (!empty($dates[0]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$dates[0])) ? (string)$dates[0] : $baseDate;
                $isDueToday = ($today >= $startDate);
            }
        }
    }

    if (!$isDueToday) {
        $skippedCount++;
        continue;
    }

    // Prevent duplicate task for the same user + title on today's date
    $uid = (int)$t['user_id'];
    $title = (string)$t['title'];
    $stmtExistsToday->bind_param("iss", $uid, $title, $today);
    $stmtExistsToday->execute();
    if ($stmtExistsToday->get_result()->num_rows > 0) {
        $skippedCount++;
        continue;
    }

    if ($isDryRun) {
        $spawnedCount++;
        $details[] = ['source_task_id' => (int)$t['id'], 'title' => $title, 'type' => $type, 'user_id' => $uid, 'dry_run' => true];
        continue;
    }

    // Reset checklist completion flags for new occurrence
    $checklistData = $t['checklist_data'] ?? null;
    if (!empty($checklistData)) {
        $decodedCl = json_decode((string)$checklistData, true);
        if (is_array($decodedCl)) {
            foreach ($decodedCl as &$item) {
                $item['completed'] = false;
            }
            unset($item);
            $checklistData = json_encode($decodedCl, JSON_UNESCAPED_UNICODE);
        }
    }

    $stmtInsertTask->bind_param(
        "ssiiisssssssissss",
        $t['title'],
        $t['expected_output'],
        $t['user_id'],
        $t['assigned_by'],
        $t['todo_project_id'],
        $t['description'],
        $checklistData,
        $todayDeadline,
        $t['priority'],
        $t['mentioned_user_ids'],
        $t['cc_user_ids'],
        $t['attachment_image'],
        $t['project_id'],
        $t['recurrence_type'],
        $t['recurrence_end_date'],
        $t['recurrence_custom_dates'],
        $t['collaborator_user_ids']
    );

    if (!$stmtInsertTask->execute()) {
        $skippedCount++;
        continue;
    }

    $newTaskId = (int)$stmtInsertTask->insert_id;
    $spawnedCount++;
    $details[] = ['new_task_id' => $newTaskId, 'source_task_id' => (int)$t['id'], 'title' => $title, 'type' => $type];

    // Log activity
    $creatorId = !empty($t['assigned_by']) ? (int)$t['assigned_by'] : $uid;
    $actText = "Auto-generated recurring task ({$type}) from T-{$t['id']}.";
    if ($stmtAct) {
        $stmtAct->bind_param("iis", $newTaskId, $creatorId, $actText);
        $stmtAct->execute();
    }

    // Queue emails & in-app notifications for involved users
    $reviewerIds = $parseIds($t['mentioned_user_ids'] ?? '');
    $collabIds = $parseIds($t['collaborator_user_ids'] ?? '');
    $ccIds = $parseIds($t['cc_user_ids'] ?? '');
    $recipientIds = array_values(array_unique(array_filter(
        array_merge([$uid], $reviewerIds, $collabIds, $ccIds),
        static fn($id) => $id > 0
    )));

    if (!empty($recipientIds)) {
        $inClause = implode(',', array_map('intval', $recipientIds));
        $uRes = $db->query("SELECT id, full_name, email FROM users WHERE id IN ({$inClause})");
        $usersMap = [];
        while ($uRes && ($u = $uRes->fetch_assoc())) {
            $usersMap[(int)$u['id']] = $u;
        }

        $taskCode = 'T-' . $newTaskId;
        $priorityLabel = ucfirst(strtolower((string)($t['priority'] ?? 'medium')));
        $subject = "[{$taskCode}] Recurring Task (" . ucfirst($type) . "): {$title}";

        foreach ($recipientIds as $rUid) {
            if (!isset($usersMap[$rUid])) {
                continue;
            }
            $rName = trim((string)($usersMap[$rUid]['full_name'] ?? 'Team Member')) ?: 'Team Member';
            $rEmail = trim((string)($usersMap[$rUid]['email'] ?? ''));

            if ($stmtQueue && $rEmail !== '' && filter_var($rEmail, FILTER_VALIDATE_EMAIL)) {
                $body = "
                <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; padding: 32px 15px;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 14px rgba(20,45,38,0.08); border: 1px solid #e2eae5;'>
                        <div style='background: #087F5B; padding: 20px 26px;'>
                            <div style='font-size: 11px; letter-spacing: 1.2px; text-transform: uppercase; color: rgba(255,255,255,0.85); margin-bottom: 4px;'>TPHL ERP &middot; Recurring Task</div>
                            <h2 style='margin: 0; color: #ffffff; font-size: 19px; font-weight: 700;'>{$taskCode}: " . htmlspecialchars($title) . "</h2>
                        </div>
                        <div style='padding: 26px;'>
                            <p style='color: #142D26; font-size: 15px; margin: 0 0 12px;'>Hello <strong>" . htmlspecialchars($rName) . "</strong>,</p>
                            <p style='color: #4A5D55; font-size: 14px; line-height: 1.6; margin: 0 0 20px;'>
                                A scheduled <strong>" . htmlspecialchars(ucfirst($type)) . "</strong> recurring task has been created for today (<strong>{$todayFormatted}</strong>):
                            </p>
                            <div style='background-color: #F8FAF9; border-left: 4px solid #087F5B; border-radius: 6px; padding: 16px 18px; margin-bottom: 22px;'>
                                <div style='font-size: 16px; font-weight: 700; color: #142D26; margin-bottom: 8px;'>" . htmlspecialchars($title) . "</div>
                                <div style='font-size: 13px; color: #4A5D55;'><strong>Deadline:</strong> {$todayFormatted} &middot; <strong>Priority:</strong> {$priorityLabel}</div>
                            </div>
                            <div style='text-align: center; margin: 24px 0 8px;'>
                                <a href='{$dashboardUrl}' style='background-color: #087F5B; color: #ffffff; padding: 11px 26px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>Open My Work Dashboard &rarr;</a>
                            </div>
                        </div>
                    </div>
                </div>";
                $stmtQueue->bind_param("sss", $rEmail, $subject, $body);
                if ($stmtQueue->execute()) {
                    $queuedEmailsCount++;
                }
            }

            if ($stmtNotif) {
                $notifMsg = "Recurring task {$taskCode}: \"{$title}\" has been created for today ({$todayFormatted}).";
                $stmtNotif->bind_param("iiis", $rUid, $newTaskId, $creatorId, $notifMsg);
                $stmtNotif->execute();
            }
        }
    }
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
}
echo json_encode([
    'status' => 'success',
    'date' => $todayFormatted,
    'dry_run' => $isDryRun,
    'series_checked' => count($seriesList),
    'spawned_tasks' => $spawnedCount,
    'emails_queued' => $queuedEmailsCount,
    'skipped' => $skippedCount,
    'details' => $details
], JSON_PRETTY_PRINT);
