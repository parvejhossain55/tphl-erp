<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get all tables
$tablesRes = $db->query("SHOW TABLES");
$tables = array_column($tablesRes->fetch_all(MYSQLI_NUM), 0);

echo "=== TABLES ===\n";
print_r($tables);

// Let's inspect column names in target_items or phase_items or initial_estimates
$targetTables = [
    'project_phases',
    'project_phase_items',
    'project_phase_item_details',
    'phase_monthly_targets',
    'phase_monthly_target_items',
    'initial_estimate_budgets',
    'initial_estimate_items',
    'initial_estimate_labors'
];

foreach ($targetTables as $tbl) {
    if (in_array($tbl, $tables)) {
        echo "\n=== COLUMNS FOR $tbl ===\n";
        $cols = $db->query("DESCRIBE $tbl")->fetch_all(MYSQLI_ASSOC);
        foreach ($cols as $c) {
            echo "{$c['Field']} - {$c['Type']}\n";
        }
    }
}
