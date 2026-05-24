<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die("Connection failed: " . $db->connect_error); }

$tables = ['purchase_requests', 'cashout_requests', 'vendor_payment_requests', 'contractor_bill_requests'];

foreach ($tables as $table) {
    echo "Columns for $table:\n";
    $result = $db->query("SHOW COLUMNS FROM $table");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . "\n";
        }
    } else {
        echo "Table $table not found.\n";
    }
    echo "\n";
}
