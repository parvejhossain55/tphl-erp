<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

echo "=== CLEANING UP DUPLICATE DISBURSEMENT JOURNALS ===\n";

// Find all journal IDs for Disbursement for Transaction #1
$res = $db->query("SELECT id FROM journals WHERE narration LIKE '%Disbursement for Transaction #1%' ORDER BY id ASC");
$ids = [];
while ($r = $res->fetch_assoc()) {
    $ids[] = (int)$r['id'];
}

echo "Found " . count($ids) . " journals for Transaction #1.\n";

if (count($ids) > 1) {
    // Keep the first one, delete the rest
    $keepId = array_shift($ids);
    echo "Keeping Journal ID: $keepId\n";
    
    // Update the kept journal with source_module and source_ref_id
    $db->query("UPDATE journals SET source_module = 'disbursement', source_ref_id = 1, entry_type = 'disbursement' WHERE id = $keepId");
    
    // Delete duplicate journals and their details
    $deleteIdsStr = implode(',', $ids);
    echo "Deleting duplicate Journal IDs: $deleteIdsStr\n";
    
    $db->query("DELETE FROM journal_details WHERE journal_id IN ($deleteIdsStr)");
    $db->query("DELETE FROM journals WHERE id IN ($deleteIdsStr)");
    echo "Cleanup complete!\n";
} elseif (count($ids) === 1) {
    $keepId = $ids[0];
    $db->query("UPDATE journals SET source_module = 'disbursement', source_ref_id = 1, entry_type = 'disbursement' WHERE id = $keepId");
    echo "Single journal updated with source_module and source_ref_id.\n";
} else {
    echo "No journals found to cleanup.\n";
}
