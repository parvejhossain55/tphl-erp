<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');

$result = $db->query("
    SELECT ea.id, ea.amount, ea.approved_amount, ea.due_amount, ea.status,
           (SELECT COALESCE(SUM(amount),0) FROM adjustment_transactions WHERE adjustment_id = ea.id) as total_paid
    FROM expense_adjustments ea
");

echo "=== Before Fix ===" . PHP_EOL;
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | Requested: {$row['amount']} | Approved: {$row['approved_amount']} | Due: {$row['due_amount']} | Paid: {$row['total_paid']} | Status: {$row['status']}" . PHP_EOL;
}

// Fix: Recalculate due_amount based on approved_amount
$db->query("
    UPDATE expense_adjustments ea
    SET ea.due_amount = GREATEST(0, ea.approved_amount - COALESCE((SELECT SUM(amount) FROM adjustment_transactions WHERE adjustment_id = ea.id), 0)),
        ea.status = CASE 
            WHEN ea.approved_amount - COALESCE((SELECT SUM(amount) FROM adjustment_transactions WHERE adjustment_id = ea.id), 0) <= 0 THEN 'fully_paid'
            WHEN COALESCE((SELECT SUM(amount) FROM adjustment_transactions WHERE adjustment_id = ea.id), 0) > 0 THEN 'partially_paid'
            ELSE ea.status
        END
    WHERE ea.status IN ('approved', 'partially_paid')
");
echo PHP_EOL . "Rows affected: " . $db->affected_rows . PHP_EOL;

$result2 = $db->query("
    SELECT ea.id, ea.amount, ea.approved_amount, ea.due_amount, ea.status,
           (SELECT COALESCE(SUM(amount),0) FROM adjustment_transactions WHERE adjustment_id = ea.id) as total_paid
    FROM expense_adjustments ea
");

echo PHP_EOL . "=== After Fix ===" . PHP_EOL;
while ($row = $result2->fetch_assoc()) {
    echo "ID: {$row['id']} | Requested: {$row['amount']} | Approved: {$row['approved_amount']} | Due: {$row['due_amount']} | Paid: {$row['total_paid']} | Status: {$row['status']}" . PHP_EOL;
}
