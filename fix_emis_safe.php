<?php
$config = require __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/models/AccountClient.php';

$db = new Database($config);
$conn = $db->conn;
$accountModel = new AccountClient($conn);

// 1. Get all batches that contain unverified or pending EMI payments
$sql = "SELECT batch_id, booking_id, MIN(id) as first_id
        FROM lead_payments 
        WHERE payment_type IN ('emi', 'emi_due_settlement') 
        AND verification_status IN ('unverified', 'pending')
        AND is_trashed = 0
        AND batch_id IS NOT NULL
        GROUP BY batch_id, booking_id
        ORDER BY first_id ASC";

$result = $conn->query($sql);
$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[] = $row;
}

if (empty($batches)) {
    echo "No unverified EMI batches found to process.\n";
    exit;
}

echo "Found " . count($batches) . " batches to re-process safely.\n";

foreach ($batches as $batchInfo) {
    $batchId = $batchInfo['batch_id'];
    $bookingId = $batchInfo['booking_id'];
    
    // Get total amount and common metadata for the batch
    $stmt = $conn->prepare("
        SELECT booking_id, SUM(amount) as total_amount, payment_method, payment_date, 
               transaction_ref, bank_name, bank_account_no, cheque_no, online_type, 
               attachment_paths, created_by, handover_to, created_at
        FROM lead_payments 
        WHERE batch_id = ? AND is_trashed = 0
        GROUP BY batch_id
    ");
    $stmt->bind_param("s", $batchId);
    $stmt->execute();
    $batchData = $stmt->get_result()->fetch_assoc();
    
    if (!$batchData) continue;
    
    $totalAmount = (float)$batchData['total_amount'];
    
    echo "Processing Batch: $batchId | Booking: $bookingId | Amount: $totalAmount\n";
    
    $conn->begin_transaction();
    try {
        // Find any emi_due_settlement in this batch and restore the parent's due amount
        $settlements = $conn->prepare("SELECT amount, parent_payment_id FROM lead_payments WHERE batch_id = ? AND settlement_type = 'emi_due_settlement'");
        $settlements->bind_param("s", $batchId);
        $settlements->execute();
        $settleRes = $settlements->get_result();
        
        while($s = $settleRes->fetch_assoc()) {
            if (!empty($s['parent_payment_id'])) {
                $amt = (float)$s['amount'];
                $pid = $s['parent_payment_id'];
                // Restore parent due and set status back to partially_paid
                $conn->query("UPDATE lead_payments SET due_emi_amount = due_emi_amount + $amt, payment_status = 'partially_paid' WHERE id = '$pid'");
            }
        }

        // Clean up approval requests
        $delAppr = $conn->prepare("
            DELETE FROM approval_requests 
            WHERE source_table = 'lead_payments' 
            AND source_id IN (SELECT id FROM lead_payments WHERE batch_id = ?)
        ");
        $delAppr->bind_param("s", $batchId);
        $delAppr->execute();

        // Delete related emi_schedules
        $delSched = $conn->prepare("
            DELETE es FROM emi_schedules es
            INNER JOIN lead_payments lp ON es.lead_payment_id = lp.id
            WHERE lp.batch_id = ?
        ");
        $delSched->bind_param("s", $batchId);
        $delSched->execute();
        
        // Delete lead_payments for this batch
        $delPay = $conn->prepare("DELETE FROM lead_payments WHERE batch_id = ?");
        $delPay->bind_param("s", $batchId);
        $delPay->execute();
        
        // Re-run the transaction through the smart allocation logic
        $transactionData = [
            'booking_id' => $bookingId,
            'payment_type' => 'emi',
            'amount' => $totalAmount,
            'payment_method' => $batchData['payment_method'],
            'payment_date' => $batchData['payment_date'],
            'transaction_ref' => $batchData['transaction_ref'],
            'bank_name' => $batchData['bank_name'],
            'bank_account_no' => $batchData['bank_account_no'],
            'cheque_no' => $batchData['cheque_no'],
            'online_type' => $batchData['online_type'],
            'attachment_paths' => $batchData['attachment_paths'],
            'created_by' => $batchData['created_by'],
            'handover_to' => $batchData['handover_to']
        ];
        
        $accountModel->addTransactionWithEMI($transactionData);
        
        $conn->commit();
        echo "  -> Successfully re-processed batch $batchId\n";
    } catch (Exception $e) {
        $conn->rollback();
        echo "  -> Failed to re-process batch $batchId: " . $e->getMessage() . "\n";
    }
}

echo "All batches processed successfully.\n";
