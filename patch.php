<?php
$file = 'app/models/CashoutRequest.php';
$content = file_get_contents($file);

$search1 = "approved_amount = ?,
                updated_at = NOW()
                WHERE id = ?";
$replace1 = "approved_amount = ?,
                due_amount = ? - COALESCE((SELECT SUM(amount) FROM cashout_payments WHERE cashout_request_id = ?), 0),
                updated_at = NOW()
                WHERE id = ?";
$content = str_replace($search1, $replace1, $content);

$search2 = '$stmt->bind_param("di", $approvedAmount, $id);';
$replace2 = '$stmt->bind_param("ddii", $approvedAmount, $approvedAmount, $id, $id);';
$content = str_replace($search2, $replace2, $content);

file_put_contents($file, $content);
echo "Done";
