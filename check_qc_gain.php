<?php
// Fix existing approved QC reports - update gain_percentage in target_fixed
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

echo "=== Checking weekly_progress_report columns ===" . PHP_EOL;
$r = $db->query('SHOW COLUMNS FROM weekly_progress_report LIKE "qc_gain_percentage"');
if ($r && $r->num_rows > 0) {
    echo "✅ qc_gain_percentage column EXISTS" . PHP_EOL;
} else {
    echo "❌ qc_gain_percentage column MISSING" . PHP_EOL;
}

echo PHP_EOL . "=== Reports with QC approved but 0 gain ===" . PHP_EOL;
$rows = $db->query("SELECT id, project_id, target_id, approved_by_qc, qc_gain_percentage FROM weekly_progress_report WHERE approved_by_qc IS NOT NULL")->fetch_all(MYSQLI_ASSOC);
foreach ($rows as $row) {
    echo "Report #{$row['id']}: target_id={$row['target_id']}, qc_gain_pct={$row['qc_gain_percentage']}, approved_by_qc={$row['approved_by_qc']}" . PHP_EOL;
}

echo PHP_EOL . "=== target_fixed gain_percentage ===" . PHP_EOL;
$tf = $db->query("SELECT id, target_title, target_percentage, gain_percentage FROM target_fixed")->fetch_all(MYSQLI_ASSOC);
foreach ($tf as $row) {
    echo "Target #{$row['id']}: {$row['target_title']} | target%={$row['target_percentage']} | gain%={$row['gain_percentage']}" . PHP_EOL;
}
