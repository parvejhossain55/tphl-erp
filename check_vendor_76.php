<?php
$config = require 'app/config/config.php';
require 'app/core/Database.php';
$db = (new Database($config))->conn;

require_once 'app/core/Model.php';
require_once 'app/models/Con_Vendor.php';
$vendorModel = new Con_Vendor($db);

$vendor = $vendorModel->find(76);
echo "Vendor 76 Info:\n";
print_r($vendor);

$stats = $vendorModel->getStats(76);
echo "\nStats for Vendor 76:\n";
print_r($stats);

$history = $vendorModel->getPurchaseHistory(76);
echo "\nPO History count: " . count($history) . "\n";
print_r($history);

echo "Vendor 76 Info:\n";
print_r($vendor);

$stats = $vendorModel->getStats(76);
echo "\nStats for Vendor 76:\n";
print_r($stats);

$vprIds = [339, 1551, 1563, 1564, 1596, 1618];
$idsStr = implode(',', $vprIds);
$resPay = $db->query("SELECT * FROM con_financial_transactions WHERE ref_id IN ($idsStr)");
echo "\nTransactions for Vendor 76 VPRs: " . $resPay->num_rows . "\n";
while ($r = $resPay->fetch_assoc()) {
    echo "ID: {$r['id']} | ref_type: {$r['ref_type']} | ref_id: {$r['ref_id']} | amount: {$r['amount_paid']} | method: {$r['payment_method']} | date: {$r['transaction_date']}\n";
}
