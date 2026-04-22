<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) { die('DB error: ' . $db->connect_error); }

$tables = ['con_financial_transactions', 'initial_estimate_budgets', 'target_fixed', 'projects', 'budget_extend_requests', 'vendor_payment_requests', 'contractor_bill_requests', 'operational_fund_requests', 'soil_tests'];

foreach ($tables as $t) {
    $r = $db->query("DESCRIBE `$t`");
    if (!$r) { echo "TABLE $t: NOT FOUND\n"; continue; }
    echo "\n=== $t ===\n";
    while ($row = $r->fetch_assoc()) {
        echo "  " . $row['Field'] . " | " . $row['Type'] . "\n";
    }
}
