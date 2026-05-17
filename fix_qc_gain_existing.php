<?php
/**
 * Fix existing QC-approved weekly reports
 * - Sets qc_gain_percentage from progress_percentage for already-approved reports
 * - Recalculates target_fixed.gain_percentage
 * 
 * NOTE: This is a one-time fix for reports approved before qc_gain_percentage column existed.
 * For new reports, QC must enter the gain% in the form.
 */
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

echo "=== Fixing existing QC-approved reports ===" . PHP_EOL . PHP_EOL;

// Get all reports that are QC approved but have 0 qc_gain_percentage
$rows = $db->query("SELECT id, target_id, progress_percentage, qc_gain_percentage, approved_by_qc FROM weekly_progress_report WHERE approved_by_qc IS NOT NULL AND qc_gain_percentage = 0.00")->fetch_all(MYSQLI_ASSOC);

if (empty($rows)) {
    echo "✅ No reports need fixing." . PHP_EOL;
} else {
    foreach ($rows as $row) {
        // Use progress_percentage as qc_gain_percentage for existing approved reports
        $gain = (float)$row['progress_percentage'];
        $reportId = (int)$row['id'];
        
        $upd = $db->prepare("UPDATE weekly_progress_report SET qc_gain_percentage = ? WHERE id = ?");
        $upd->bind_param('di', $gain, $reportId);
        if ($upd->execute()) {
            echo "✅ Report #{$reportId}: Set qc_gain_percentage = {$gain}%" . PHP_EOL;
        } else {
            echo "❌ Report #{$reportId}: FAILED - " . $upd->error . PHP_EOL;
        }
    }
    
    echo PHP_EOL . "=== Recalculating target_fixed.gain_percentage ===" . PHP_EOL;
    
    // Get all unique target_ids that need recalculation
    $targetIds = array_unique(array_filter(array_column($rows, 'target_id')));
    
    foreach ($targetIds as $targetId) {
        if (!$targetId) continue;
        $sumQ = $db->query("SELECT SUM(qc_gain_percentage) AS total_gain FROM weekly_progress_report WHERE target_id = {$targetId} AND approved_by_qc IS NOT NULL");
        $totalGain = $sumQ ? (float)$sumQ->fetch_assoc()['total_gain'] : 0;
        
        $updTarget = $db->prepare("UPDATE target_fixed SET gain_percentage = ? WHERE id = ?");
        $updTarget->bind_param('di', $totalGain, $targetId);
        if ($updTarget->execute()) {
            echo "✅ Target #{$targetId}: gain_percentage = {$totalGain}%" . PHP_EOL;
        } else {
            echo "❌ Target #{$targetId}: FAILED - " . $updTarget->error . PHP_EOL;
        }
    }
}

echo PHP_EOL . "Done!" . PHP_EOL;
