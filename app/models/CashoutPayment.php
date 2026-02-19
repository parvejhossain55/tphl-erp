<?php
class CashoutPayment extends Model
{
    protected $db;
    protected $table = 'cashout_payments';

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function addPayment($data)
    {
        // First, get the cashout request details to retrieve office, project_id, and purpose
        $request = $this->getCashoutRequest($data['cashout_request_id']);
        if (!$request) {
            return ['success' => false, 'message' => 'Invalid cashout request ID.'];
        }

        // Always include purpose_paid_to from the request
        $includePurpose = true;

        // Build the SQL dynamically – now purpose_paid_to is always included
        $columns = "cashout_request_id, office, project_id, purpose_paid_to, amount, payment_method, transaction_ref,
                payment_date, bank_name, bank_account_no, cheque_no, online_type, created_by";
        $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?";

        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return ['success' => false, 'message' => 'Database prepare failed.'];
        }

        // Base parameters (13 items including purpose_paid_to)
        $params = [
            $data['cashout_request_id'],
            $request['office'],
            $request['project_id'],
            $request['purpose_paid_to'],          // from the cashout request
            $data['amount'],
            $data['payment_method'],
            $data['transaction_ref'],
            $data['payment_date'],
            $data['bank_name'],
            $data['bank_account_no'],
            $data['cheque_no'],
            $data['online_type'],
            $data['created_by']
        ];

        // Build type string based on parameter types
        $types = "";
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= "i";
            } elseif (is_float($param)) {
                $types .= "d";
            } else {
                $types .= "s"; // includes NULL values
            }
        }

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);

        $result = $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();

        if ($result) {
            // Update due_amount and status in cashout_requests
            $this->updateRequestDueAmount($data['cashout_request_id']);

            return [
                'success' => true,
                'message' => 'Payment recorded successfully!',
                'id' => $insertId
            ];
        } else {
            error_log("Execute failed: " . $db->error);
            return ['success' => false, 'message' => 'Failed to record payment.'];
        }
    }

    // ====================== GET PAYMENTS FOR A REQUEST ======================
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

    // ====================== GET TOTAL PAID AMOUNT ======================
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

    // ====================== DELETE PAYMENT ======================
    public function deletePayment($id)
    {
        // First get the cashout_request_id to later update due_amount
        $sql = "SELECT cashout_request_id FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $payment = $result->fetch_assoc();
        $stmt->close();

        if (!$payment) {
            return false;
        }

        $requestId = $payment['cashout_request_id'];

        // Delete the payment
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // Recalculate due amount and status
            $this->updateRequestDueAmount($requestId);
        }

        return $result;
    }

    // ====================== HELPER: GET CASHOUT REQUEST DETAILS ======================
    private function getCashoutRequest($requestId)
    {
        $sql = "SELECT office, project_id, purpose_paid_to, amount, approved_amount, due_amount 
                FROM cashout_requests WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        $stmt->close();
        return $request;
    }

    // ====================== HELPER: UPDATE DUE AMOUNT AND STATUS ======================
    private function updateRequestDueAmount($requestId)
    {
        // Get total paid
        $totalPaid = $this->getTotalPaid($requestId);

        // Get the request to know the approved amount
        $request = $this->getCashoutRequest($requestId);
        if (!$request)
            return false;

        $approvedAmount = $request['approved_amount'] ?? $request['amount']; // fallback to original amount
        $newDue = max(0, $approvedAmount - $totalPaid);

        // Determine new status
        if ($newDue <= 0) {
            $status = 'fully_paid';
        } elseif ($totalPaid > 0) {
            $status = 'partially_paid';
        } else {
            $status = 'approved'; // or keep previous status; you may fetch current status if needed
        }

        // Update cashout_requests
        $sql = "UPDATE cashout_requests SET due_amount = ?, status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("dsi", $newDue, $status, $requestId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}
?>