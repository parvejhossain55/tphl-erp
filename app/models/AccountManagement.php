<?php
class AccountManagement extends Model
{
    protected $db;
        protected $managementModel;   
    protected $roleModel; 
    protected $table = 'management_cashin';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all transactions
    public function getAllTransactions()
    {
        $sql = "SELECT mc.*, 
                       u.full_name as created_by_name
                FROM {$this->table} mc
                LEFT JOIN users u ON mc.created_by = u.id
                ORDER BY mc.transaction_date DESC, mc.created_at DESC";
        
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

    // Get statistics
    public function getStatistics()
    {
        $sql = "SELECT 
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as pending_amount
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
        
        $stats['total_transactions'] = $stats['total_transactions'] ?? 0;
        $stats['total_amount'] = $stats['total_amount'] ?? 0;
        $stats['paid_amount'] = $stats['paid_amount'] ?? 0;
        $stats['pending_amount'] = $stats['pending_amount'] ?? 0;
        
        return $stats;
    }

    // Add new transaction
public function addTransaction($data)
{
    try {
        if (!$this->validateUserExists($data['created_by'])) {
            return [
                'success' => false,
                'message' => 'Invalid user ID. User does not exist in database.'
            ];
        }
        
        $sql = "INSERT INTO {$this->table} 
                (role_id, role_name, person_name, transaction_date, source, description, amount, 
                 payment_method, payment_status, transaction_ref,
                 bank_name, bank_account_no, cheque_no, online_type, created_by)
                VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return [
                'success' => false,
                'message' => 'Database prepare failed.'
            ];
        }
        
        // Correct type string: i-s-s-s-s-s-d-s-s-s-s-s-s-s-i (15 types for 15 parameters)
        $stmt->bind_param(
            "isssssdsssssssi",  // Fixed: added one more 's' (was 14, now 15)
            $data['role_id'],           // i - integer
            $data['role_name'],         // s - string
            $data['person_name'],       // s - string
            $data['transaction_date'],  // s - string (date)
            $data['source'],            // s - string
            $data['description'],       // s - string
            $data['amount'],            // d - double/decimal
            $data['payment_method'],    // s - string
            $data['payment_status'],    // s - string
            $data['transaction_ref'],   // s - string
            $data['bank_name'],         // s - string
            $data['bank_account_no'],   // s - string
            $data['cheque_no'],         // s - string
            $data['online_type'],       // s - string
            $data['created_by']         // i - integer
        );
        
        $result = $stmt->execute();
        $stmt->close();
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Transaction added successfully!',
                'id' => $this->db->insert_id
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

    // Validate user exists
    private function validateUserExists($userId)
    {
        $sql = "SELECT id FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            return false;
        }
        
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }

    // Get transaction by ID
    public function getTransactionById($id)
    {
        $sql = "SELECT mc.*, 
                       u.full_name as created_by_name
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

    // Update transaction
    public function updateTransaction($data)
    {
        try {
            $sql = "UPDATE {$this->table} SET
                    role_id = ?,
                    role_name = ?,
                    person_name = ?,
                    transaction_date = ?,
                    source = ?,
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
            
            $stmt->bind_param(
                "isssssdsssssssi",
                $data['role_id'],
                $data['role_name'],
                $data['person_name'],
                $data['transaction_date'],
                $data['source'],
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
                    'message' => 'Transaction updated successfully!'
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
                    'message' => 'Transaction deleted successfully!'
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

    // Add this method to your AccountManagement model class

/**
 * Get filtered transactions with optional date range (all roles)
 */
public function getFilteredTransactions($dateFilter = [])
{
    $sql = "SELECT 
        mc.*,
        u.full_name as created_by_name
    FROM {$this->table} mc
    LEFT JOIN users u ON mc.created_by = u.id
    WHERE 1=1";
    
    $types = "";
    $params = [];
    
    // Add date filter if provided
    if (!empty($dateFilter['from']) && !empty($dateFilter['to'])) {
        $sql .= " AND mc.transaction_date BETWEEN ? AND ?";
        $types .= "ss";
        $params[] = $dateFilter['from'];
        $params[] = $dateFilter['to'];
    }
    
    $sql .= " ORDER BY mc.transaction_date DESC, mc.created_at DESC";
    
    $stmt = $this->db->prepare($sql);
    
    if (!$stmt) {
        error_log("Prepare failed: " . $this->db->error);
        return [];
    }
    
    // Bind parameters if any exist
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    $stmt->close();
    return $transactions;
}
}