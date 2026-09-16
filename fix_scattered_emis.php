<?php
$config = require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . '/app/core/Model.php';
require_once __DIR__ . '/app/models/AccountClient.php';

$db = new Database($config);
$conn = $db->conn;
$accountModel = new AccountClient($conn);

// Get all unique batches that contain unverified/pending EMI payments
// We order by created_at so we process them chronologically
$sql = "SELECT DISTINCT batch_id, booking_id, MIN(created_at) as first_created
        FROM lead_payments 
        WHERE payment_type IN ('emi', 'emi_due_settlement') 
        AND verification_status IN ('unverified', 'pending')
        AND is_trashed = 0
        AND batch_id IS NOT NULL
        GROUP BY batch_id, booking_id
        ORDER BY first_created ASC";

$result = $conn->query($sql);
$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[] = $row['batch_id'];
}

if (empty($batches)) {
    echo "No unverified EMI batches found to process.\n";
    exit;
}

echo "Found " . count($batches) . " batches to re-process.\n";

foreach ($batches as $batchId) {
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
    
    $bookingId = $batchData['booking_id'];
    $totalAmount = (float)$batchData['total_amount'];
    
    echo "Processing Batch: $batchId | Booking: $bookingId | Amount: $totalAmount\n";
    
    // Begin transaction for safety
    $conn->begin_transaction();
    try {
        // First delete approval requests related to these payments to avoid foreign key or orphan issues
        // (if Handover was requested)
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
        
        // Construct transaction data
        $transactionData = [
            'booking_id' => $bookingId,
            'payment_type' => 'emi', // Use general EMI to trigger smart allocation
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
