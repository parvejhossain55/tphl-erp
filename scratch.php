<?php
$db = new mysqli("localhost", "root", "", "tphl_db");

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Find all rejected emi_due_settlement
$res = $db->query("SELECT id, amount, parent_payment_id FROM lead_payments WHERE settlement_type = 'emi_due_settlement' AND verification_status = 'rejected'");
while ($row = $res->fetch_assoc()) {
    $amount = (float)$row['amount'];
    $parentId = (int)$row['parent_payment_id'];
    
    echo "Found rejected settlement ID {$row['id']} with amount {$amount} for parent {$parentId}\n";
    
    // Check if parent has 0 due and was partially paid
    $parentRes = $db->query("SELECT due_emi_amount, payment_status FROM lead_payments WHERE id = {$parentId}");
    if ($parentRow = $parentRes->fetch_assoc()) {
        echo "Parent current due: {$parentRow['due_emi_amount']}, status: {$parentRow['payment_status']}\n";
        
        // Restore parent's due
        $db->query("UPDATE lead_payments SET due_emi_amount = due_emi_amount + {$amount}, payment_status = 'partially_paid' WHERE id = {$parentId}");
        echo "Restored {$amount} to parent {$parentId}\n";
    }
}
echo "Done\n";
