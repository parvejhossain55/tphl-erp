<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if($db->connect_error) { echo 'ERROR: '.$db->connect_error; exit; }

$queries = [
    'ALTER TABLE initial_estimate_budgets ADD COLUMN work_type_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER architecture_id',
    'ALTER TABLE initial_estimate_items ADD COLUMN work_type_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER budget_id',
    'ALTER TABLE initial_estimate_labors ADD COLUMN work_type_id BIGINT UNSIGNED NULL DEFAULT NULL AFTER budget_id',
];

foreach ($queries as $sql) {
    try {
        $db->query($sql);
        echo "OK: $sql\n";
    } catch (\Exception $e) {
        echo "NOTE: ".$e->getMessage()."\n";
    }
}
echo "DONE\n";
