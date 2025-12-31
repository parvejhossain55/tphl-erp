<?php
class AccountManagementCashOut extends Model
{
    protected $db;
    protected $table = 'management_cashout';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all transactions
    public function getAllTransactions()
    {
        $sql = "SELECT mc.*, u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                ORDER BY mc.created_at DESC";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Database error: " . $this->db->error);
            return [];
        }
        
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        
        $result->free();
        return $transactions;
    }

    // Get transaction by ID
    public function getTransactionById($id)
    {
        $sql = "SELECT mc.*, u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                WHERE mc.id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return false;
        }
        
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $transaction = $result->fetch_assoc();
        
        $stmt->close();
        return $transaction;
    }

    // Add new transaction
// Add new transaction - CORRECTED VERSION
public function addTransaction($data)
{
    try {
        // SQL INSERT with correct field order matching database table
        $sql = "INSERT INTO {$this->table} 
                (role_name, role_id, person_name, transaction_date, paid_to, description, 
                 amount, payment_method, payment_status, transaction_ref, 
                 bank_name, bank_account_no, cheque_no, online_type, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return [
                'success' => false,
                'message' => 'Database prepare failed.'
            ];
        }
        
        // Bind parameters - 15 parameters total in CORRECT ORDER
        $stmt->bind_param(
            "sissssdsssssssi",  // Note: s first for role_name, then i for role_id
            $data['role_name'],
            $data['role_id'],
            $data['person_name'],
            $data['transaction_date'],
            $data['paid_to'],
            $data['description'],
            $data['amount'],
            $data['payment_method'],
            $data['payment_status'],
            $data['transaction_ref'],
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
                'message' => 'Cash out transaction added successfully!',
                'id' => $insertId
            ];
        } else {
            error_log("Execute failed: " . $this->db->error);
            return [
                'success' => false,
                'message' => 'Failed to add transaction.'
            ];
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

    // Update transaction
// Update transaction - FIXED VERSION
// Update transaction - CORRECTED VERSION
public function updateTransaction($data)
{
    try {
        // SQL UPDATE with correct field order matching database table
        $sql = "UPDATE {$this->table} SET
                role_name = ?,
                role_id = ?,
                person_name = ?,
                transaction_date = ?,
                paid_to = ?,
                description = ?,
                amount = ?,
                payment_method = ?,
                payment_status = ?,
                transaction_ref = ?,
                bank_name = ?,
                bank_account_no = ?,
                cheque_no = ?,
                online_type = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return [
                'success' => false,
                'message' => 'Database prepare failed.'
            ];
        }
        
        // Bind parameters in CORRECT ORDER matching SQL above
        $stmt->bind_param(
            "sissssdsssssssi",  // Note: s first for role_name, then i for role_id
            $data['role_name'],
            $data['role_id'],
            $data['person_name'],
            $data['transaction_date'],
            $data['paid_to'],
            $data['description'],
            $data['amount'],
            $data['payment_method'],
            $data['payment_status'],
            $data['transaction_ref'],
            $data['bank_name'],
            $data['bank_account_no'],
            $data['cheque_no'],
            $data['online_type'],
            $data['transaction_id']
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Cash out transaction updated successfully!'
            ];
        } else {
            error_log("Execute failed: " . $this->db->error);
            return [
                'success' => false,
                'message' => 'Failed to update transaction.'
            ];
        }
    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

    // Delete transaction
    public function deleteTransaction($id)
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                error_log("Prepare failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Database prepare failed.'
                ];
            }
            
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Cash out transaction deleted successfully!'
                ];
            } else {
                error_log("Execute failed: " . $this->db->error);
                return [
                    'success' => false,
                    'message' => 'Failed to delete transaction.'
                ];
            }
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }

    // Get statistics
    public function getStatistics()
    {
        $sql = "SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending_amount
                FROM {$this->table}";
        
        $result = $this->db->query($sql);
        
        if (!$result) {
            error_log("Database error: " . $this->db->error);
            return [
                'total_transactions' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'pending_amount' => 0
            ];
        }
        
        $stats = $result->fetch_assoc();
        $result->free();
        
        return $stats;
    }
}