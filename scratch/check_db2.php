<?php
require_once __DIR__ . '/../app/config/database.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

echo "LEAD PAYMENTS\n";
$res = $db->query("SELECT payment_type, SUM(amount) as amt FROM lead_payments WHERE payment_status = 'paid' GROUP BY payment_type");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "MANAGEMENT CASHIN\n";
$res = $db->query("SELECT SUM(amount) as amt FROM management_cashin WHERE payment_status = 'paid'");
print_r($res->fetch_assoc());

echo "ADJUSTMENT TRANSACTIONS\n";
$res = $db->query("SELECT transaction_type, SUM(amount) as amt FROM adjustment_transactions JOIN expense_adjustments ON adjustment_transactions.adjustment_id = expense_adjustments.id WHERE expense_adjustments.status = 'approved' GROUP BY transaction_type");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}

echo "PROCUREMENT RETURNS\n";
$res = $db->query("SELECT SUM(return_amount) as amt FROM project_extra_assets WHERE payment_status = 'paid'");
print_r($res->fetch_assoc());
