<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';

$root = dirname(__DIR__);
$config = require $root . "/app/config/config.php";
spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);

    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
        $root . "/app/controllers/Phase2/$class.php",   // Phase 2 controllers
        $root . "/app/services/$class.php",
        $root . "/app/helpers/$class.php",
        $root . "/app/ai/$class.php"
    ];

    foreach ($paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$database = new Database($config);
$db = $database->conn;

echo "--- MONTHLY TARGETS ---\n";
$mtRes = $db->query("SELECT * FROM phase_monthly_targets WHERE project_id = 13");
while ($row = $mtRes->fetch_assoc()) {
    echo "Target ID: {$row['id']} - Month: {$row['target_month']} - Status: {$row['planning_status']}\n";
}

echo "\n--- MONTHLY TARGET ITEMS FOR TARGET 1 (JUNE 2026) ---\n";
$mtiRes = $db->query("SELECT * FROM phase_monthly_target_items WHERE target_id = 1");
while ($row = $mtiRes->fetch_assoc()) {
    echo "Item ID: {$row['id']} - Name: {$row['work_item_name']} - TargetQty: {$row['monthly_target_qty']} - W1: {$row['week_1_qty']} - W2: {$row['week_2_qty']} - W3: {$row['week_3_qty']} - W4: {$row['week_4_qty']} - ActualQty: {$row['actual_qty']}\n";
}

echo "\n--- WEEKLY PROGRESS FOR TARGET 1 ---\n";
$wres = $db->query("SELECT * FROM phase_weekly_progress WHERE target_id = 1");
while ($row = $wres->fetch_assoc()) {
    echo "Week ID: {$row['id']} - WeekNo: {$row['week_number']} - ProgressPct: {$row['overall_progress_pct']}% - Status: {$row['entry_status']}\n";
}


echo "\n--- WEEKLY PROGRESS ITEMS FOR WEEK 1 ---\n";
$wires = $db->query("SELECT * FROM phase_weekly_progress_items WHERE weekly_progress_id = 1");
while ($row = $wires->fetch_assoc()) {
    echo "WI ID: {$row['id']} - TargetItemID: {$row['target_item_id']} - Planned: {$row['planned_qty']} - Achieved: {$row['achieved_qty']}\n";
}

echo "\n--- DAILY PROGRESS LOGS FOR WEEK 1 ---\n";
$dplres = $db->query("SELECT * FROM daily_progress_logs WHERE weekly_progress_id = 1");
while ($row = $dplres->fetch_assoc()) {
    echo "Daily Log ID: {$row['id']} - Date: {$row['log_date']} - Safety: {$row['safety_ok']} - QC: {$row['qc_checked']}\n";
    // Get daily work items
    $dwilres = $db->query("SELECT * FROM daily_work_item_logs WHERE daily_log_id = {$row['id']}");
    while ($drow = $dwilres->fetch_assoc()) {
        echo "   -> Item ID: {$drow['id']} - TargetItemID: {$drow['target_item_id']} - QtyDone: {$drow['qty_done_today']} - Remarks: {$drow['remarks']}\n";
    }
}



