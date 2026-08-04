<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$tables = ['cashout_payments', 'cashout_requests', 'con_financial_transactions', 'fund_requisitions', 'land_fund_requisitions', 'project_extra_assets', 'management_cashout', 'payslips', 'adjustment_transactions'];

foreach ($tables as $t) {
    echo "--- Table $t ---\n";
    $res = $db->query("SELECT * FROM $t WHERE 1=1 LIMIT 10");
    if ($res) {
        $fields = $res->fetch_fields();
        $cols = array_map(function($f) { return $f->name; }, $fields);
        echo "Cols: " . implode(', ', $cols) . "\n";
    }
}
