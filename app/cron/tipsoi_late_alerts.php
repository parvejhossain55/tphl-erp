<?php
/**
 * Tipsoi Realtime Late Arrival Alerts Dispatcher
 * 
 * Can be run from CLI:
 *   php app/cron/tipsoi_late_alerts.php
 *   php app/cron/tipsoi_late_alerts.php --dry-run
 *   php app/cron/tipsoi_late_alerts.php --test-email=admin@example.com
 * 
 * Or via web browser / URL:
 *   https://domain/app/cron/tipsoi_late_alerts.php
 *   https://domain/app/cron/tipsoi_late_alerts.php?dry_run=1
 */

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/TipsoiLateAlertService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error . "\n");
}

$isDryRun = false;
if (isset($_GET['dry_run']) && ($_GET['dry_run'] == '1' || $_GET['dry_run'] == 'true')) {
    $isDryRun = true;
} elseif (isset($argv) && in_array('--dry-run', $argv)) {
    $isDryRun = true;
}

$targetDate = null;
if (isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
    $targetDate = $_GET['date'];
} elseif (isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--force-date=') === 0) {
            $targetDate = substr($arg, strlen('--force-date='));
        }
    }
}

$testEmail = null;
if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $testEmail = trim($_GET['email']);
} elseif (isset($argv)) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--test-email=') === 0) {
            $testEmail = substr($arg, strlen('--test-email='));
        }
    }
}

$start = microtime(true);
$service = new TipsoiLateAlertService($db);

$tz = date_default_timezone_get();
date_default_timezone_set('Asia/Dhaka');
$effectiveDate = $targetDate ?: date('Y-m-d');
date_default_timezone_set($tz);

echo "========================================================\n";
echo " TPHL ERP — Tipsoi Realtime Late Arrival Notification   \n";
echo "========================================================\n";
echo "Run Time    : " . date('Y-m-d H:i:s') . "\n";
echo "Target Date : " . $effectiveDate . "\n";
echo "Mode        : " . ($isDryRun ? "DRY-RUN (Simulate Only)" : "LIVE DISPATCH") . "\n";
if ($testEmail) {
    echo "Test Email  : $testEmail (All emails routed here)\n";
}
echo "--------------------------------------------------------\n";

$result = $service->processDailyLateAlerts($effectiveDate, $isDryRun, 'cron', $testEmail);
$duration = round(microtime(true) - $start, 2);

if (!$result['success']) {
    echo "ERROR: " . $result['message'] . "\n";
    exit(1);
}

echo "Completed in {$duration}s!\n\n";
echo "Summary:\n";
echo "- Total Late Punch Today : {$result['total_late_today']}\n";
echo "- Emails Dispatched      : {$result['dispatched_count']}\n";
echo "- Skipped / Already Sent : {$result['skipped_count']}\n\n";

if (!empty($result['dispatched'])) {
    echo "Dispatched Details:\n";
    foreach ($result['dispatched'] as $d) {
        $stat = $d['status'] ?? 'Success';
        $err = !empty($d['error']) ? " (Error: {$d['error']})" : "";
        echo "  [ID: {$d['office_id']}] {$d['name']} <{$d['email']}> - Late: {$d['late_by']} - Status: {$stat}{$err}\n";
    }
    echo "\n";
}

if (!empty($result['skipped'])) {
    echo "Skipped Details:\n";
    foreach ($result['skipped'] as $s) {
        echo "  [ID: {$s['office_id']}] {$s['name']} - Reason: {$s['reason']}\n";
    }
    echo "\n";
}

echo "========================================================\n";
echo "Finished.\n";
