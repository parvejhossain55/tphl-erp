<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Delete journal details for those journals
$db->query("DELETE jd FROM journal_details jd JOIN journals j ON jd.journal_id = j.id WHERE j.source_module IS NOT NULL");
// Delete the journals
$db->query("DELETE FROM journals WHERE source_module IS NOT NULL");
echo "Deleted auto-synced journals. Ready to resync!\n";

require_once __DIR__ . '/app/services/AutoPostingService.php';
$auto = new AutoPostingService($db);
$totalSynced = 0;
while(true) {
    $synced = $auto->syncUnpostedExpenses(500);
    $totalSynced += $synced;
    if ($synced == 0) break;
}
echo "Resynced $totalSynced expenses to journals with new COA mappings!\n";
