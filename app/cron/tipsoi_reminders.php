<?php
/**
 * Tipsoi Pending Requests Reminder Cron Script
 * 
 * Can be run from CLI:
 *   php app/cron/tipsoi_reminders.php
 *   php app/cron/tipsoi_reminders.php --force
 * 
 * Or via web browser / URL:
 *   https://domain/app/cron/tipsoi_reminders.php
 *   https://domain/app/cron/tipsoi_reminders.php?force=1
 */

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/TipsoiReminderService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error . "\n");
}

$isForce = false;
if (isset($_GET['force']) && ($_GET['force'] == '1' || $_GET['force'] == 'true')) {
    $isForce = true;
} elseif (isset($argv) && in_array('--force', $argv)) {
    $isForce = true;
}

$dayOfMonth = date('d');
$dayOfWeek = date('w'); // 0 (Sun) to 6 (Sat)
$runType = ($dayOfMonth == '23') ? 'Monthly (23rd Cutoff)' : ($dayOfWeek == 0 ? 'Weekly (Sunday)' : 'Daily Check');

echo "========================================================\n";
echo " TPHL ERP — Tipsoi Pending Requests Reminder Dispatcher \n";
echo "========================================================\n";
echo "Run Time   : " . date('Y-m-d H:i:s') . "\n";
echo "Schedule   : $runType\n";
echo "Force Mode : " . ($isForce ? "YES (Cooldown Bypassed)" : "NO (Cooldown Active)") . "\n";
echo "--------------------------------------------------------\n";

$start = microtime(true);
$service = new TipsoiReminderService($db);

echo "Fetching pending requests from Tipsoi API...\n";
$report = $service->runCompanyWideSync($isForce, 'cron');
$duration = round(microtime(true) - $start, 2);

echo "Completed in $duration seconds!\n\n";
echo "Summary:\n";
echo "- Total Pending Requests Found : {$report['total_pending']}\n";
echo "- Approvers with Pending Items : {$report['total_approvers_pending']}\n";
echo "- Requesters with Pending Items: {$report['total_requesters_pending']}\n";
echo "- Emails Dispatched            : {$report['dispatched_count']}\n";
echo "- Skipped (Cooldown Active)    : {$report['skipped_count']}\n\n";

if (!empty($report['dispatched'])) {
    echo "Dispatched Emails Details:\n";
    foreach ($report['dispatched'] as $d) {
        echo "  [{$d['recipient_type']}] {$d['name']} <{$d['email']}> - {$d['pending_count']} pending - Status: {$d['status']}" . ($d['error'] ? " (Error: {$d['error']})" : "") . "\n";
    }
    echo "\n";
}

if (!empty($report['skipped']) && count($report['skipped']) <= 10) {
    echo "Skipped Details (first " . count($report['skipped']) . "):\n";
    foreach ($report['skipped'] as $s) {
        echo "  [{$s['type']}] <{$s['email']}> - Reason: {$s['reason']}\n";
    }
} elseif (!empty($report['skipped'])) {
    echo "Skipped " . count($report['skipped']) . " recipients due to 7-day cooldown (already notified recently).\n";
}

echo "========================================================\n";
echo "Finished.\n";
