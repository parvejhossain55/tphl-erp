<?php
class CashoutRequest extends Model
{
    protected $db;
    protected $table = 'cashout_requests';

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Create new cashout request - ALWAYS start as pending
// Create new cashout request - ALWAYS start as pending
public function createRequest($data)
{
    $this->db->begin_transaction();

    try {
        // ALWAYS set initial status as 'pending'
        $status = 'pending';
        $dueAmount = $data['amount'];

        // Insert into cashout_requests
        $sql = "INSERT INTO {$this->table} 
                (requested_by, purpose_paid_to, amount, description, payment_method, 
                 transaction_ref, due_amount, status, attachment_path, project_id, office, bank_id, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->db->error);
        }

        // Prepare parameters - ensure null values are handled correctly
        $projectId = !empty($data['project_id']) ? $data['project_id'] : null;
        $office = !empty($data['office']) ? $data['office'] : null;
        $bankId = !empty($data['bank_id']) ? (int)$data['bank_id'] : null;
        
        // Bind parameters including project_id, office and bank_id
        $stmt->bind_param(
            "isdssssssssi",
            $data['requested_by'],
            $data['purpose_paid_to'],
            $data['amount'],
            $data['description'],
            $data['payment_method'],
            $data['transaction_ref'],
            $dueAmount,
            $status,
            $data['attachment_path'],
            $projectId,
            $office,
            $bankId
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $insertId = $this->db->insert_id;
        $stmt->close();

        // Create approval request entry - ALWAYS pending
        $approvalSql = "INSERT INTO approval_requests 
                      (department, request_type, title, source_table, source_id, 
                       request_amount, requested_by, approve_role, meta_data, status, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

        $approvalStmt = $this->db->prepare($approvalSql);

        $department = 'Accounts';
        $requestType = 'cashout';
        
        // Include project and office in title if available
        $titlePrefix = '';
        $projectName = null;
        
        if (!empty($data['project_id'])) {
            $projectName = $this->getProjectName($data['project_id']);
            $titlePrefix = "[Project: {$projectName}] ";
        }
        if (!empty($data['office'])) {
            $titlePrefix .= "[Office: {$data['office']}] ";
        }
        
        $title = $titlePrefix . 'Cashout Request: ' . substr($data['purpose_paid_to'], 0, 100);
        $sourceTable = 'cashout_requests';
        $approveRole = 'management';

        // Get requester name for meta data
        $requesterName = $this->getUserName($data['requested_by']);

        $metaData = json_encode([
            'purpose' => $data['purpose_paid_to'],
            'payment_method' => $data['payment_method'],
            'requester_name' => $requesterName,
            'description' => $data['description'],
            'project_id' => $data['project_id'] ?? null,
            'project_name' => $projectName,
            'office' => $data['office'] ?? null
        ]);

        $approvalStmt->bind_param(
            "ssssidsss",
            $department,
            $requestType,
            $title,
            $sourceTable,
            $insertId,
            $data['amount'],
            $data['requested_by'],
            $approveRole,
            $metaData
        );

        if (!$approvalStmt->execute()) {
            throw new Exception("Approval request creation failed: " . $approvalStmt->error);
        }

        $approvalStmt->close();
        $this->db->commit();

        return [
            'success' => true,
            'message' => 'Cashout request submitted successfully! Waiting for approval.',
            'id' => $insertId
        ];
    } catch (Exception $e) {
        $this->db->rollback();
        error_log("Cashout request error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to submit request: ' . $e->getMessage()];
    }
}

    // Get project name
    private function getProjectName($projectId)
    {
        if (!$projectId) return null;
        
        $sql = "SELECT name FROM projects WHERE id = ? AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $project = $result->fetch_assoc();
        $stmt->close();

        return $project['name'] ?? null;
    }

    // Get user name for meta data
    private function getUserName($userId)
    {
        $sql = "SELECT full_name FROM users WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user['full_name'] ?? 'Unknown';
    }

    // Get all requests for a user with proper approval status
    public function getUserRequests($userId)
    {
        $sql = "SELECT cr.*, 
                u.full_name as requester_name,
                (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(id, cr.project_id)) AS project_name,
                ar.status as approval_status,
                ar.approved_amount as approval_approved_amount,
                ar.approved_at as approval_approved_at,
                ar.approved_by as approval_user_id,
                ar.id as approval_id,
                ar.approval_note,
                approver.full_name as approved_by_name,
                rejector.full_name as rejected_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id
                WHERE cr.requested_by = ?
                ORDER BY cr.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            // FIX: Copy approved_amount from approval system to main field
            if (!empty($row['approval_approved_amount'])) {
                $row['approved_amount'] = $row['approval_approved_amount'];
            }
            $row['display_status'] = $this->syncStatusWithApproval($row);
            $requests[] = $row;
        }

        $stmt->close();
        return $requests;
    }

    // Get all requests (for admin/approvers)
    public function getAllRequests($filters = [])
    {
        $sql = "
            SELECT * FROM (
                SELECT 
                    cr.id,
                    'general' AS cashout_type,
                    cr.amount,
                    cr.due_amount,
                    cr.status,
                    cr.created_at,
                    cr.requested_by,
                    cr.project_id,
                    cr.office,
                    cr.purpose_paid_to,
                    cr.payment_method,
                    cr.transaction_ref,
                    cr.description,
                    cr.attachment_path,
                    cr.bank_id,
                    cr.approved_amount,
                    cr.approval_note,
                    cr.approved_by,
                    cr.approved_at,
                    cr.rejected_by,
                    cr.rejected_at,
                    cr.rejection_reason,
                    u.full_name AS requester_name,
                    (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, cr.project_id)) AS project_name,
                    ar.status AS approval_status,
                    ar.approved_amount AS approval_approved_amount,
                    ar.approved_at AS approval_approved_at,
                    ar.approved_by AS approval_user_id,
                    ar.id AS approval_id,
                    approver.full_name AS approved_by_name,
                    rejector.full_name AS rejected_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id

                UNION ALL

                SELECT 
                    vpr.id,
                    'vendor' AS cashout_type,
                    vpr.invoice_amount AS amount,
                    vpr.due_amount,
                    vpr.status,
                    vpr.created_at,
                    vpr.created_by AS requested_by,
                    CAST(vpr.project_id AS CHAR) AS project_id,
                    NULL AS office,
                    v.name AS purpose_paid_to,
                    'Bank/Cash' AS payment_method,
                    vpr.invoice_number AS transaction_ref,
                    vpr.payment_type AS description,
                    vpr.payment_attachment AS attachment_path,
                    NULL AS bank_id,
                    vpr.approved_amount,
                    NULL AS approval_note,
                    vpr.approved_by,
                    NULL AS approved_at,
                    NULL AS rejected_by,
                    NULL AS rejected_at,
                    NULL AS rejection_reason,
                    u.full_name AS requester_name,
                    (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, vpr.project_id)) AS project_name,
                    NULL AS approval_status,
                    NULL AS approval_approved_amount,
                    NULL AS approval_approved_at,
                    NULL AS approval_user_id,
                    NULL AS approval_id,
                    approver.full_name AS approved_by_name,
                    NULL AS rejected_by_name
                FROM vendor_payment_requests vpr
                LEFT JOIN vendors v ON vpr.vendor_id = v.id
                LEFT JOIN users u ON vpr.created_by = u.id
                LEFT JOIN users approver ON vpr.approved_by = approver.id

                UNION ALL

                SELECT 
                    cbr.id,
                    'contractor bill' AS cashout_type,
                    cbr.bill_amount AS amount,
                    cbr.due_amount,
                    cbr.status,
                    cbr.created_at,
                    cbr.created_by AS requested_by,
                    CAST(cbr.project_id AS CHAR) AS project_id,
                    NULL AS office,
                    cbr.work_description AS purpose_paid_to,
                    'Bank/Cash' AS payment_method,
                    NULL AS transaction_ref,
                    cbr.work_description AS description,
                    cbr.attachment_path,
                    NULL AS bank_id,
                    cbr.approved_amount,
                    cbr.verification_note AS approval_note,
                    cbr.approved_by,
                    NULL AS approved_at,
                    NULL AS rejected_by,
                    NULL AS rejected_at,
                    NULL AS rejection_reason,
                    u.full_name AS requester_name,
                    (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, cbr.project_id)) AS project_name,
                    NULL AS approval_status,
                    NULL AS approval_approved_amount,
                    NULL AS approval_approved_at,
                    NULL AS approval_user_id,
                    NULL AS approval_id,
                    approver.full_name AS approved_by_name,
                    NULL AS rejected_by_name
                FROM contractor_bill_requests cbr
                LEFT JOIN users u ON cbr.created_by = u.id
                LEFT JOIN users approver ON cbr.approved_by = approver.id
            ) AS cr
            WHERE 1 = 1
        ";

        $params = [];
        $types  = "";

        // Status filter
        if (!empty($filters['status'])) {
            $status = $filters['status'];

            if (in_array($status, ['pending', 'approved', 'rejected', 'partially_paid', 'fully_paid'])) {
                $sql .= " AND cr.status = ?";
                $params[] = $status;
                $types   .= "s";
            } else {
                // approval_requests status
                $sql .= " AND cr.approval_status = ?";
                $params[] = $status;
                $types   .= "s";
            }
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $sql .= " AND cr.requested_by = ?";
            $params[] = $filters['user_id'];
            $types   .= "i";
        }

        // Project filter
        if (!empty($filters['project_id'])) {
            $sql .= " AND FIND_IN_SET(?, cr.project_id)";
            $params[] = $filters['project_id'];
            $types   .= "s";
        }

        // Office filter
        if (!empty($filters['office'])) {
            $sql .= " AND FIND_IN_SET(?, cr.office)";
            $params[] = $filters['office'];
            $types   .= "s";
        }

        // Date range
        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(cr.created_at) >= ?";
            $params[] = $filters['start_date'];
            $types   .= "s";
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(cr.created_at) <= ?";
            $params[] = $filters['end_date'];
            $types   .= "s";
        }

        // ✅ ORDER BY only once
        $sql .= " ORDER BY cr.created_at DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception($this->db->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $requests = [];
        while ($row = $result->fetch_assoc()) {
            // approval system amount override
            if (!empty($row['approval_approved_amount'])) {
                $row['approved_amount'] = $row['approval_approved_amount'];
            }

            $row['display_status'] = $this->syncStatusWithApproval($row);
            $requests[] = $row;
        }

        $stmt->close();
        return $requests;
    }

    // Get request by ID
    public function getRequestById($id)
    {
        $sql = "SELECT cr.*, 
                u.full_name as requester_name,
                (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(id, cr.project_id)) AS project_name,
                ar.status as approval_status,
                ar.approved_amount as approval_approved_amount,
                ar.approval_note,
                ar.approved_at as approval_approved_at,
                ar.approved_by as approval_user_id,
                ar.id as approval_id,
                approver.full_name as approved_by_name,
                rejector.full_name as rejected_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id
                WHERE cr.id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        if ($request) {
            $request['display_status'] = $this->syncStatusWithApproval($request);
            // Sync approved amount from approval system
            if (!empty($request['approval_approved_amount'])) {
                $request['approved_amount'] = $request['approval_approved_amount'];
            }
        }

        $stmt->close();
        return $request;
    }

    // Update request status - BOTH tables AND sync approved amount
    public function updateStatus($id, $status, $userId = null, $rejectionReason = null)
    {
        $this->db->begin_transaction();

        try {
            // Get approval details first
            $approvalRequest = $this->getApprovalRequest($id);
            $approvedAmount = $approvalRequest['approved_amount'] ?? null;
            $approvalNote = $approvalRequest['approval_note'] ?? null;

            // 1. Update cashout_requests table with approved amount from approval system
            $cashoutSql = "UPDATE {$this->table} SET 
                    status = ?,
                    approved_amount = ?,
                    approval_note = ?,
                    updated_at = NOW()";

            $cashoutParams = [$status, $approvedAmount, $approvalNote];
            $cashoutTypes = "sss";

            if ($status === 'approved') {
                $cashoutSql .= ", approved_by = ?, approved_at = NOW()";
                $cashoutParams[] = $userId;
                $cashoutTypes .= "i";
            } elseif ($status === 'rejected') {
                $cashoutSql .= ", rejected_by = ?, rejected_at = NOW(), rejection_reason = ?";
                $cashoutParams[] = $userId;
                $cashoutParams[] = $rejectionReason;
                $cashoutTypes .= "is";
            }

            $cashoutSql .= " WHERE id = ?";
            $cashoutParams[] = $id;
            $cashoutTypes .= "i";

            $stmt = $this->db->prepare($cashoutSql);
            $stmt->bind_param($cashoutTypes, ...$cashoutParams);

            if (!$stmt->execute()) {
                throw new Exception("Cashout request update failed: " . $stmt->error);
            }
            $stmt->close();

            // 2. Update approval_requests table if not already updated from homepage
            if (!empty($approvalRequest) && $approvalRequest['status'] === 'pending') {
                $approvalSql = "UPDATE approval_requests 
                               SET status = ?, approved_by = ?, approved_at = NOW()
                               WHERE source_table = 'cashout_requests' AND source_id = ?";

                $approvalStmt = $this->db->prepare($approvalSql);
                $approvalStmt->bind_param("sii", $status, $userId, $id);

                if (!$approvalStmt->execute()) {
                    throw new Exception("Approval request update failed: " . $approvalStmt->error);
                }
                $approvalStmt->close();
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Update status error: " . $e->getMessage());
            return false;
        }
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
        if ($userId) {
            $sql = "SELECT 
            COUNT(cr.id) as total_requests,
            COALESCE(SUM(cr.amount), 0) as total_amount,
            COALESCE(SUM(cr.approved_amount), 0) as total_approved,
            COALESCE(SUM(cr.due_amount), 0) as total_due,
            COUNT(CASE WHEN cr.status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN cr.status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN cr.status = 'rejected' THEN 1 END) as rejected_count,
            COUNT(CASE WHEN cr.status = 'partially_paid' THEN 1 END) as partial_count,
            COUNT(CASE WHEN cr.status = 'fully_paid' THEN 1 END) as paid_count
            FROM {$this->table} cr
            WHERE cr.requested_by = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $stats = $result->fetch_assoc();
            $stmt->close();
        } else {
            $sql = "SELECT 
                COUNT(*) as total_requests,
                COALESCE(SUM(amount), 0) as total_amount,
                COALESCE(SUM(approved_amount), 0) as total_approved,
                COALESCE(SUM(due_amount), 0) as total_due,
                COUNT(CASE WHEN status = 'pending' OR status = 'Pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'approved' OR status = 'Approved' THEN 1 END) as approved_count,
                COUNT(CASE WHEN status = 'rejected' OR status = 'Rejected' THEN 1 END) as rejected_count,
                COUNT(CASE WHEN status = 'partially_paid' OR status = 'Partially_Paid' THEN 1 END) as partial_count,
                COUNT(CASE WHEN status = 'fully_paid' OR status = 'Fully_Paid' THEN 1 END) as paid_count
            FROM (
                SELECT amount, approved_amount, due_amount, status FROM {$this->table}
                UNION ALL
                SELECT invoice_amount as amount, approved_amount, due_amount, status FROM vendor_payment_requests
                UNION ALL
                SELECT bill_amount as amount, approved_amount, due_amount, status FROM contractor_bill_requests
            ) cr";

            $result = $this->db->query($sql);
            $stats = $result->fetch_assoc();
        }

        return $stats;
    }

    // Delete request and associated approval
    public function deleteRequest($id)
    {
        $this->db->begin_transaction();

        try {
            // Delete from approval_requests
            $approvalSql = "DELETE FROM approval_requests 
                           WHERE source_table = 'cashout_requests' AND source_id = ?";
            $approvalStmt = $this->db->prepare($approvalSql);
            $approvalStmt->bind_param("i", $id);
            $approvalStmt->execute();
            $approvalStmt->close();

            // Delete from cashout_requests
            $sql = "DELETE FROM {$this->table} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $id);
            $result = $stmt->execute();
            $stmt->close();

            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Delete request error: " . $e->getMessage());
            return false;
        }
    }

    // Get approval request
    public function getApprovalRequest($cashoutId)
    {
        $sql = "SELECT * FROM approval_requests 
                WHERE source_table = 'cashout_requests' AND source_id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $cashoutId);
        $stmt->execute();
        $result = $stmt->get_result();
        $approval = $result->fetch_assoc();
        $stmt->close();

        return $approval;
    }

    // Check if approval is needed
    public function needsApproval($userId)
    {
        // ALL requests need approval through the approval system
        return true;
    }

    // Sync status logic
    private function syncStatusWithApproval($request)
    {
        $approvalStatus = $request['approval_status'] ?? null;
        $cashoutStatus = $request['status'] ?? 'pending';

        // Priority: approval_requests status determines the real status
        if (!empty($approvalStatus)) {
            switch ($approvalStatus) {
                case 'approved':
                case 'partial':
                    // If approved or partial, check payment status
                    if (isset($request['due_amount']) && $request['due_amount'] <= 0) {
                        return 'fully_paid';
                    } elseif (isset($request['due_amount']) && $request['due_amount'] < $request['amount']) {
                        return 'partially_paid';
                    }
                    return 'approved';

                case 'rejected':
                    return 'rejected';

                case 'pending':
                default:
                    return 'pending';
            }
        }

        // If no approval record, use cashout status
        return $cashoutStatus;
    }

    // NEW: Force sync all pending requests with approval system
    public function syncPendingRequests()
    {
        $sql = "UPDATE {$this->table} cr
                LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                SET cr.status = CASE 
                    WHEN ar.status = 'approved' OR ar.status = 'partial' THEN 'approved'
                    WHEN ar.status = 'rejected' THEN 'rejected'
                    ELSE 'pending'
                END,
                cr.approved_amount = ar.approved_amount,
                cr.approval_note = ar.approval_note,
                cr.approved_by = ar.approved_by,
                cr.approved_at = ar.approved_at
                WHERE cr.status != 'fully_paid' AND cr.status != 'partially_paid'";

        $result = $this->db->query($sql);
        return $result;
    }

    // NEW: Update approved amount directly (for manual updates)
    public function updateApprovedAmount($id, $approvedAmount)
    {
        $sql = "UPDATE {$this->table} SET 
                approved_amount = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("di", $approvedAmount, $id);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }

    // NEW: Get all projects for dropdown
    public function getAllProjects()
    {
        $sql = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
        $result = $this->db->query($sql);
        
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        
        return $projects;
    }
}
?>