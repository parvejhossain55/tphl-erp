<?php
class CashoutRequest extends Model
{
    protected $db;
    protected $table = 'cashout_requests';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Create new cashout request
    public function createRequest($data)
    {
        $sql = "INSERT INTO {$this->table} 
                (requested_by, purpose_paid_to, amount, description, payment_method, 
                 transaction_ref, due_amount, status, attachment_path, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->db->error);
            return ['success' => false, 'message' => 'Database prepare failed.'];
        }
        
        $dueAmount = $data['amount']; // Initially due amount = total amount
        $status = 'pending';
        
        $stmt->bind_param(
            "isdssssss",
            $data['requested_by'],
            $data['purpose_paid_to'],
            $data['amount'],
            $data['description'],
            $data['payment_method'],
            $data['transaction_ref'],
            $dueAmount,
            $status,
            $data['attachment_path']
        );
        
        $result = $stmt->execute();
        $insertId = $this->db->insert_id;
        $stmt->close();
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Cashout request submitted successfully!',
                'id' => $insertId
            ];
        } else {
            error_log("Execute failed: " . $this->db->error);
            return ['success' => false, 'message' => 'Failed to submit request.'];
        }
    }

    // Get all requests for a user
    public function getUserRequests($userId)
    {
        $sql = "SELECT cr.*, 
                u.full_name as requester_name,
                approver.full_name as approved_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN users approver ON cr.approved_by = approver.id
                WHERE cr.requested_by = ?
                ORDER BY cr.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        $stmt->close();
        return $requests;
    }

    // Get all requests (for admin/approvers)
    public function getAllRequests($filters = [])
    {
        $sql = "SELECT cr.*, 
                u.full_name as requester_name,
                approver.full_name as approved_by_name,
                rejector.full_name as rejected_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN users approver ON cr.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id
                WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($filters['status'])) {
            $sql .= " AND cr.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND cr.requested_by = ?";
            $params[] = $filters['user_id'];
            $types .= "i";
        }
        
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(cr.created_at) >= ?";
            $params[] = $filters['start_date'];
            $types .= "s";
        }
        
        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(cr.created_at) <= ?";
            $params[] = $filters['end_date'];
            $types .= "s";
        }
        
        $sql .= " ORDER BY cr.created_at DESC";
        
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            $result = $this->db->query($sql);
        }
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
        
        if (isset($stmt)) $stmt->close();
        return $requests;
    }

    // Get request by ID
    public function getRequestById($id)
    {
        $sql = "SELECT cr.*, 
                u.full_name as requester_name,
                approver.full_name as approved_by_name,
                rejector.full_name as rejected_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN users approver ON cr.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id
                WHERE cr.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();
        
        $stmt->close();
        return $request;
    }

    // Update request status - SIMPLIFIED VERSION
    public function updateStatus($id, $status, $userId = null, $rejectionReason = null)
    {
        $sql = "UPDATE {$this->table} SET 
                status = ?,
                updated_at = NOW()";
        
        $params = [$status];
        $types = "s";
        
        // Handle different status updates
        if ($status === 'approved') {
            // Single approval - record who approved
            $sql .= ", approved_by = ?, approved_at = NOW()";
            $params[] = $userId;
            $types .= "i";
        } elseif ($status === 'rejected') {
            // Record rejection
            $sql .= ", rejected_by = ?, rejected_at = NOW(), rejection_reason = ?";
            $params[] = $userId;
            $params[] = $rejectionReason;
            $types .= "is";
        }
        // For 'partially_paid' and 'fully_paid', just update status
        
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $types .= "i";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    // Update due amount
    public function updateDueAmount($id, $dueAmount)
    {
        $sql = "UPDATE {$this->table} SET 
                due_amount = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("di", $dueAmount, $id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    }

    // Get statistics
    public function getStatistics($userId = null)
    {
        $sql = "SELECT 
                COUNT(*) as total_requests,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(due_amount), 0) as total_due,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
                COUNT(CASE WHEN status = 'partially_paid' THEN 1 END) as partial_count,
                COUNT(CASE WHEN status = 'fully_paid' THEN 1 END) as paid_count
                FROM {$this->table}";
        
        if ($userId) {
            $sql .= " WHERE requested_by = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
        } else {
            $result = $this->db->query($sql);
            $stats = $result->fetch_assoc();
        }
        
        return $stats;
    }

    // Delete request
    public function deleteRequest($id)
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