<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$projectId = 13; // The Premium Canvas of Happiness

echo "=== 1. Cashout Payments for Project $projectId ===\n";
$res = $db->query("SELECT cp.amount, cp.payment_date, cr.purpose_paid_to, cr.project_id FROM cashout_payments cp LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id WHERE (cr.project_id = $projectId OR cp.project_id = $projectId)");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "=== 2. Vendor Payments / Contractor Bills for Project $projectId ===\n";
$res = $db->query("SELECT amount_paid, transaction_date, ref_type FROM con_financial_transactions WHERE project_id = $projectId");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "=== 3. Fund Requisitions for Project $projectId ===\n";
$res = $db->query("SELECT amount, paid_at FROM fund_requisitions WHERE project_id = $projectId AND status = 'paid'");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "=== 4. Check global payslips sum in July 2026 & June 2026 ===\n";
$res = $db->query("SELECT SUM(net_salary) as sum_jul FROM payslips WHERE DATE(updated_at) BETWEEN '2026-07-01' AND '2026-07-31' AND status = 'paid'");
if ($row = $res->fetch_assoc()) echo "July Payslips: " . $row['sum_jul'] . "\n";

$res = $db->query("SELECT SUM(net_salary) as sum_jun FROM payslips WHERE DATE(updated_at) BETWEEN '2026-06-01' AND '2026-06-30' AND status = 'paid'");
if ($row = $res->fetch_assoc()) echo "June Payslips: " . $row['sum_jun'] . "\n";

echo "=== 5. Check global management_cashout sum in July 2026 & June 2026 ===\n";
$res = $db->query("SELECT SUM(amount) as sum_jul FROM management_cashout WHERE DATE(transaction_date) BETWEEN '2026-07-01' AND '2026-07-31' AND payment_status = 'paid'");
if ($row = $res->fetch_assoc()) echo "July Mgmt Cashout: " . $row['sum_jul'] . "\n";

$res = $db->query("SELECT SUM(amount) as sum_jun FROM management_cashout WHERE DATE(transaction_date) BETWEEN '2026-06-01' AND '2026-06-30' AND payment_status = 'paid'");
if ($row = $res->fetch_assoc()) echo "June Mgmt Cashout: " . $row['sum_jun'] . "\n";
