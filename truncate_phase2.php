<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$tables = [
    'project_drawings',
    'project_structural_designs',
    'soil_tests',
    'architect_engineering_plans',
    'architect_engineerings',
    'ae_designs',
    'boq_master',
    'boq_items',
    'purchase_requests',
    'purchase_request_items',
    'quotation_comparison_table',
    'quotations',
    'purchase_orders',
    'purchase_order_items',
    'material_receiving',
    'material_receiving_items',
    'vendor_payment_requests',
    'approval_requests',
    'con_financial_transactions',
    'cashout_requests',
    'contractor_bill_requests',
    'contractor_bills'
];
foreach($tables as $t) {
    try {
        $mysqli->query("TRUNCATE TABLE $t");
    } catch (Exception $e) {}
}
echo "Truncated phase 2 tables.\n";
