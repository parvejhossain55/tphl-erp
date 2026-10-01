<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$tables = [
    'fund_requisitions', 'con_labor_requisitions', 'vendor_payment_requests', 
    'contractor_bill_requests', 'operational_fund_requests', 'project_other_costs', 
    'project_extra_assets', 'payslips', 'advance_salaries', 'cashout_requests', 
    'client_refund_requests', 'project_incentive_claims', 'land_fund_requisitions'
];
foreach($tables as $t) {
    $db->query("ALTER TABLE $t ADD COLUMN account_id INT NULL");
    if($db->error) {
        echo "Error on $t: " . $db->error . "\n";
    } else {
        echo "Added account_id to $t\n";
    }
}
