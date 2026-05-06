<?php 
require 'config/database.php'; 
$tables = ['cashout_requests', 'vendor_payment_requests', 'contractor_bill_requests']; 
foreach ($tables as $table) { 
    echo "\n\n--- $table ---\n"; 
    $res = $conn->query("DESCRIBE $table"); 
    if ($res) { 
        while($row=$res->fetch_assoc()) echo $row['Field'].' '.$row['Type']."\n"; 
    } else {
        echo "Table not found.\n";
    }
} 
?>
