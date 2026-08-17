<?php
$content = file_get_contents('app/models/AccountClient.php');

$methods = <<<'EOD'

    /**
     * Move a transaction to trash
     */
    public function trashTransaction($paymentId, $bookingId, $userId)
    {
        $stmt = $this->db->prepare("UPDATE lead_payments SET is_trashed = 1, trashed_by = ?, trashed_at = NOW() WHERE id = ? AND booking_id = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }
        $stmt->bind_param("iii", $userId, $paymentId, $bookingId);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Transaction moved to trash successfully.'];
            }
            return ['success' => false, 'message' => 'Transaction not found or already trashed.'];
        }
        return ['success' => false, 'message' => 'Failed to trash transaction: ' . $stmt->error];
    }

    /**
     * Restore a transaction from trash
     */
    public function restoreTransaction($paymentId, $bookingId)
    {
        $stmt = $this->db->prepare("UPDATE lead_payments SET is_trashed = 0, trashed_by = NULL, trashed_at = NULL WHERE id = ? AND booking_id = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }
        $stmt->bind_param("ii", $paymentId, $bookingId);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return ['success' => true, 'message' => 'Transaction restored successfully.'];
            }
            return ['success' => false, 'message' => 'Transaction not found or not trashed.'];
        }
        return ['success' => false, 'message' => 'Failed to restore transaction: ' . $stmt->error];
    }

    /**
     * Get all trashed transactions
     */
    public function getTrashedTransactions()
    {
        $sql = "
            SELECT 
                lp.*,
                lb.id as booking_id,
                l.full_name as client_name,
                p.name as project_name,
                f.flat_name as unit_name,
                u.full_name as trashed_by_name
            FROM lead_payments lp
            JOIN lead_bookings lb ON lp.booking_id = lb.id
            JOIN leads l ON lb.lead_id = l.id
            JOIN projects p ON lb.project_id = p.id
            JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON lp.trashed_by = u.id
            WHERE lp.is_trashed = 1
            ORDER BY lp.trashed_at DESC
        ";
        $result = $this->db->query($sql);
        if (!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
EOD;

$content = preg_replace('/\}\s*$/', $methods, $content);
file_put_contents('app/models/AccountClient.php', $content);
echo "Methods added successfully.\n";
