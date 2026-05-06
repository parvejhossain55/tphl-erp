<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $conn->query("SHOW COLUMNS FROM cashout_payments LIKE 'payment_method'");
echo "Cashout Payments enum: " . $res->fetch_assoc()['Type'] . "\n";
$res = $conn->query("SHOW COLUMNS FROM con_financial_transactions LIKE 'payment_method'");
echo "Con Financial Transactions enum: " . $res->fetch_assoc()['Type'] . "\n";
$conn->close();
?>
