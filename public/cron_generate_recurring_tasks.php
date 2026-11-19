<?php
/**
 * TPHL ERP Daily Recurring Task Generator Cron
 * -------------------------------------------------------------
 * Generates today's scheduled tasks from active `recurring_task_templates`.
 *
 * Trigger Options:
 * - URL (Crontab / Browser):
 *   curl -s "https://your-domain.com/TPHL-ERP/public/cron_generate_recurring_tasks.php?token=TphlSecretCron2026"
 * - CLI (Linux Crontab / Windows Task Scheduler):
 *   php public/cron_generate_recurring_tasks.php TphlSecretCron2026
 */

set_time_limit(0);
ignore_user_abort(true);

$isCli = (php_sapi_name() === 'cli');
$expectedToken = 'TphlSecretCron2026';
$providedToken = $isCli ? ($argv[1] ?? '') : ($_GET['token'] ?? '');

if (!hash_equals($expectedToken, (string)$providedToken)) {
    if (!$isCli) {
        http_response_code(403);
        header('Content-Type: application/json');
    }
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Invalid cron token.']) . PHP_EOL;
    exit(1);
}

$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/RecurringTaskTemplate.php';

$database = new Database($config);
$db = $database->conn;

$targetDate = (!$isCli && !empty($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']))
    ? $_GET['date']
    : date('Y-m-d');

$generator = new RecurringTaskTemplate($db);
$result = $generator->generateDueForDate($targetDate);

if (!$isCli) {
    header('Content-Type: application/json');
}
echo json_encode([
    'status' => 'success',
    'date' => $result['date'],
    'templates_checked' => $result['checked'],
    'tasks_created' => $result['created'],
    'task_ids' => $result['task_ids'],
]) . PHP_EOL;
