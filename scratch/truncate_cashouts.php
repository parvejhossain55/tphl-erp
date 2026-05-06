<?php
$c = new mysqli('localhost', 'root', '', 'tphl_db');
$c->query('SET FOREIGN_KEY_CHECKS=0');

$tables = ['cashout_requests', 'cashout_payments', 'vendor_payment_requests', 'contractor_bill_requests', 'con_financial_transactions'];
foreach ($tables as $t) {
    $c->query("TRUNCATE TABLE $t");
    echo "Truncated $t\n";
}

$c->query("DELETE FROM approval_requests WHERE source_table IN ('cashout_requests','vendor_payment_requests','contractor_bill_requests')");
echo "Cleared approval_requests entries\n";

$c->query('SET FOREIGN_KEY_CHECKS=1');
echo "Done!\n";
$c->close();
