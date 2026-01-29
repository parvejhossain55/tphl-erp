<?php
class CashoutPayment extends Model
{
    protected $db;
    protected $table = 'cashout_payments';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Add payment
    public function addPayment($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (cashout_request_id, amount, payment_method, transaction_ref, 
                 payment_date, bank_name, bank_account_no, cheque_no, online_type, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return ['success' => false, 'message' => 'Database prepare failed.'];
        }
        
        $stmt->bind_param(
            "idsssssssi",
            $data['cashout_request_id'],
            $data['amount'],
            $data['payment_method'],
            $data['transaction_ref'],
            $data['payment_date'],
            $data['bank_name'],
            $data['bank_account_no'],
            $data['cheque_no'],
            $data['online_type'],
            $data['created_by']
        );
        
        $result = $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Payment recorded successfully!',
                'id' => $insertId
            ];
        } else {
            error_log("Execute failed: " . $this->db->error);
            return ['success' => false, 'message' => 'Failed to record payment.'];
        }
    }

    // Get payments for a request
    public function getPaymentsByRequest($requestId)
    {
        $sql = "SELECT cp.*, u.full_name as created_by_name
                FROM {$this->table} cp
                LEFT JOIN users u ON cp.created_by = u.id
                WHERE cp.cashout_request_id = ?
                ORDER BY cp.payment_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $payments[] = $row;
        }
        
        $stmt->close();
        return $payments;
    }

    // Get total paid amount for a request
    public function getTotalPaid($requestId)
    {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total_paid
                FROM {$this->table}
                WHERE cashout_request_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        $stmt->close();
        return $row['total_paid'];
    }

    // Delete payment
    public function deletePayment($id)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }
}
?>