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
                LEFT JOIN (
                    SELECT ar1.* FROM approval_requests ar1
                    INNER JOIN (
                        SELECT source_table, source_id, MAX(id) as max_id 
                        FROM approval_requests 
                        GROUP BY source_table, source_id
                    ) ar2 ON ar1.id = ar2.max_id
                ) ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
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
                    'general' COLLATE utf8mb4_general_ci AS cashout_type,
                    cr.amount,
                    cr.due_amount,
                    cr.status COLLATE utf8mb4_general_ci AS status,
                    cr.created_at,
                    cr.requested_by,
                    cr.project_id COLLATE utf8mb4_general_ci AS project_id,
                    cr.office COLLATE utf8mb4_general_ci AS office,
                    cr.purpose_paid_to COLLATE utf8mb4_general_ci AS purpose_paid_to,
                    cr.payment_method COLLATE utf8mb4_general_ci AS payment_method,
                    cr.transaction_ref COLLATE utf8mb4_general_ci AS transaction_ref,
                    cr.description COLLATE utf8mb4_general_ci AS description,
                    cr.attachment_path COLLATE utf8mb4_general_ci AS attachment_path,
                    cr.bank_id,
                    cr.approved_amount,
                    cr.approval_note COLLATE utf8mb4_general_ci AS approval_note,
                    cr.approved_by,
                    cr.approved_at,
                    cr.rejected_by,
                    cr.rejected_at,
                    cr.rejection_reason COLLATE utf8mb4_general_ci AS rejection_reason,
                    u.full_name COLLATE utf8mb4_general_ci AS requester_name,
                    (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, cr.project_id)) COLLATE utf8mb4_general_ci AS project_name,
                    ar.status COLLATE utf8mb4_general_ci AS approval_status,
                    ar.approved_amount AS approval_approved_amount,
                    ar.approved_at AS approval_approved_at,
                    ar.approved_by AS approval_user_id,
                    ar.id AS approval_id,
                    approver.full_name COLLATE utf8mb4_general_ci AS approved_by_name,
                    rejector.full_name COLLATE utf8mb4_general_ci AS rejected_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN (
                    SELECT ar1.* FROM approval_requests ar1
                    INNER JOIN (
                        SELECT source_table, source_id, MAX(id) as max_id 
                        FROM approval_requests 
                        GROUP BY source_table, source_id
                    ) ar2 ON ar1.id = ar2.max_id
                ) ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id

                UNION ALL

                SELECT 
                    vpr.id,
                    'vendor' COLLATE utf8mb4_general_ci AS cashout_type,
                    vpr.invoice_amount AS amount,
                    vpr.due_amount,
                    vpr.status COLLATE utf8mb4_general_ci AS status,
                    vpr.created_at,
                    vpr.created_by AS requested_by,
                    CAST(vpr.project_id AS CHAR) COLLATE utf8mb4_general_ci AS project_id,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS office,
                    v.name COLLATE utf8mb4_general_ci AS purpose_paid_to,
                    'Bank/Cash' COLLATE utf8mb4_general_ci AS payment_method,
                    vpr.invoice_number COLLATE utf8mb4_general_ci AS transaction_ref,
                    vpr.payment_type COLLATE utf8mb4_general_ci AS description,
                    vpr.payment_attachment COLLATE utf8mb4_general_ci AS attachment_path,
                    NULL AS bank_id,
                    vpr.approved_amount,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS approval_note,
                    vpr.approved_by,
                    ar.approved_at AS approved_at,
                    NULL AS rejected_by,
                    NULL AS rejected_at,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS rejection_reason,
                    u.full_name COLLATE utf8mb4_general_ci AS requester_name,
                    (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, vpr.project_id)) COLLATE utf8mb4_general_ci AS project_name,
                    ar.status COLLATE utf8mb4_general_ci AS approval_status,
                    ar.approved_amount AS approval_approved_amount,
                    ar.approved_at AS approval_approved_at,
                    ar.approved_by AS approval_user_id,
                    ar.id AS approval_id,
                    approver.full_name COLLATE utf8mb4_general_ci AS approved_by_name,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS rejected_by_name
                FROM vendor_payment_requests vpr
                LEFT JOIN vendors v ON vpr.vendor_id = v.id
                LEFT JOIN users u ON vpr.created_by = u.id
                LEFT JOIN (
                    SELECT ar1.* FROM approval_requests ar1
                    INNER JOIN (
                        SELECT source_table, source_id, MAX(id) as max_id 
                        FROM approval_requests 
                        GROUP BY source_table, source_id
                    ) ar2 ON ar1.id = ar2.max_id
                ) ar ON ar.source_table = 'vendor_payment_requests' AND ar.source_id = vpr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id

                UNION ALL

                SELECT 
                    cbr.id,
                    'contractor bill' COLLATE utf8mb4_general_ci AS cashout_type,
                    cbr.bill_amount AS amount,
                    cbr.due_amount,
                    cbr.status COLLATE utf8mb4_general_ci AS status,
                    cbr.created_at,
                    cbr.created_by AS requested_by,
                    CAST(cbr.project_id AS CHAR) COLLATE utf8mb4_general_ci AS project_id,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS office,
                    cbr.work_description COLLATE utf8mb4_general_ci AS purpose_paid_to,
                    'Bank/Cash' COLLATE utf8mb4_general_ci AS payment_method,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS transaction_ref,
                    cbr.work_description COLLATE utf8mb4_general_ci AS description,
                    cbr.attachment_path COLLATE utf8mb4_general_ci AS attachment_path,
                    NULL AS bank_id,
                    cbr.approved_amount,
                    cbr.verification_note COLLATE utf8mb4_general_ci AS approval_note,
                    cbr.approved_by,
                    ar.approved_at AS approved_at,
                    NULL AS rejected_by,
                    NULL AS rejected_at,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS rejection_reason,
                    u.full_name COLLATE utf8mb4_general_ci AS requester_name,
                    (SELECT GROUP_CONCAT(name SEPARATOR ', ') FROM projects WHERE FIND_IN_SET(projects.id, cbr.project_id)) COLLATE utf8mb4_general_ci AS project_name,
                    ar.status COLLATE utf8mb4_general_ci AS approval_status,
                    ar.approved_amount AS approval_approved_amount,
                    ar.approved_at AS approval_approved_at,
                    ar.approved_by AS approval_user_id,
                    ar.id AS approval_id,
                    approver.full_name COLLATE utf8mb4_general_ci AS approved_by_name,
                    CAST(NULL AS CHAR) COLLATE utf8mb4_general_ci AS rejected_by_name
                FROM contractor_bill_requests cbr
                LEFT JOIN users u ON cbr.created_by = u.id
                LEFT JOIN (
                    SELECT ar1.* FROM approval_requests ar1
                    INNER JOIN (
                        SELECT source_table, source_id, MAX(id) as max_id 
                        FROM approval_requests 
                        GROUP BY source_table, source_id
                    ) ar2 ON ar1.id = ar2.max_id
                ) ar ON ar.source_table = 'contractor_bill_requests' AND ar.source_id = cbr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id
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
                rejector.full_name as rejected_by_name,
                qc_approver.full_name as qc_approved_by_name
                FROM {$this->table} cr
                LEFT JOIN users u ON cr.requested_by = u.id
                LEFT JOIN (
                    SELECT ar1.* FROM approval_requests ar1
                    INNER JOIN (
                        SELECT source_table, source_id, MAX(id) as max_id 
                        FROM approval_requests 
                        GROUP BY source_table, source_id
                    ) ar2 ON ar1.id = ar2.max_id
                ) ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id
                LEFT JOIN users qc_approver ON cr.qc_approved_by_id = qc_approver.id
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
    public function updateStatus($id, $status, $userId = null, $rejectionReason = null, $manualApprovedAmount = null, $manualApprovalNote = null)
    {
        $this->db->begin_transaction();

        try {
            // Get approval details first
            $approvalRequest = $this->getApprovalRequest($id);
            $currentRequest = $this->getRequestById($id);

            // Determine approved amount
            if ($manualApprovedAmount !== null && (float)$manualApprovedAmount > 0) {
                $approvedAmount = (float)$manualApprovedAmount;
            } elseif (!empty($approvalRequest['approved_amount'])) {
                $approvedAmount = (float)$approvalRequest['approved_amount'];
            } elseif (!empty($currentRequest['approved_amount'])) {
                $approvedAmount = (float)$currentRequest['approved_amount'];
            } elseif ($status === 'approved') {
                $approvedAmount = (float)($currentRequest['amount'] ?? 0);
            } else {
                $approvedAmount = null;
            }

            // Determine approval note
            if ($manualApprovalNote !== null && $manualApprovalNote !== '') {
                $approvalNote = $manualApprovalNote;
            } elseif (!empty($approvalRequest['approval_note'])) {
                $approvalNote = $approvalRequest['approval_note'];
            } elseif (!empty($currentRequest['approval_note'])) {
                $approvalNote = $currentRequest['approval_note'];
            } else {
                $approvalNote = null;
            }

            // 1. Update cashout_requests table
            $cashoutSql = "UPDATE {$this->table} SET 
                    status = ?,
                    approved_amount = ?,
                    approval_note = ?,
                    updated_at = NOW()";

            $cashoutParams = [$status, $approvedAmount, $approvalNote];
            $cashoutTypes = "sds";

            if ($status === 'approved') {
                // Calculate due_amount = approved_amount - total payments
                $totalPaid = 0;
                $paidSql = "SELECT COALESCE(SUM(amount), 0) as total FROM cashout_payments WHERE cashout_request_id = ?";
                $paidStmt = $this->db->prepare($paidSql);
                if ($paidStmt) {
                    $paidStmt->bind_param("i", $id);
                    $paidStmt->execute();
                    $paidResult = $paidStmt->get_result();
                    if ($row = $paidResult->fetch_assoc()) {
                        $totalPaid = (float)$row['total'];
                    }
                    $paidStmt->close();
                }
                $dueAmount = max(0, (float)$approvedAmount - $totalPaid);

                $cashoutSql .= ", due_amount = ?, approved_by = ?, approved_at = NOW()";
                $cashoutParams[] = $dueAmount;
                $cashoutParams[] = $userId;
                $cashoutTypes .= "di";
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

            // 2. Update approval_requests table if exists
            if (!empty($approvalRequest)) {
                $approvalReqStatus = in_array($status, ['approved', 'fully_paid']) ? 'approved' : ($status === 'partially_paid' ? 'partially_paid' : $status);
                if (!in_array($approvalReqStatus, ['pending', 'approved', 'rejected', 'cancelled', 'partially_approved', 'partially_paid', 'partial'])) {
                    $approvalReqStatus = 'approved';
                }

                $approvalSql = "UPDATE approval_requests 
                               SET status = ?, approved_by = ?, approved_at = NOW()";
                $approvalParams = [$approvalReqStatus, $userId];
                $approvalTypes = "si";

                if ($approvedAmount !== null) {
                    $approvalSql .= ", approved_amount = ?";
                    $approvalParams[] = $approvedAmount;
                    $approvalTypes .= "d";
                }

                if ($approvalNote !== null) {
                    $approvalSql .= ", approval_note = ?";
                    $approvalParams[] = $approvalNote;
                    $approvalTypes .= "s";
                }

                $approvalSql .= " WHERE id = ?";
                $approvalParams[] = $approvalRequest['id'];
                $approvalTypes .= "i";

                $approvalStmt = $this->db->prepare($approvalSql);
                $approvalStmt->bind_param($approvalTypes, ...$approvalParams);

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

    public function updateQCStatus($id, $userId, $qcNote)
    {
        $sql = "UPDATE {$this->table} SET 
                status = 'qc_approved',
                qc_approved_by_id = ?,
                qc_approved_at = NOW(),
                qc_note = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("isi", $userId, $qcNote, $id);
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
                ORDER BY id DESC LIMIT 1";

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
                    // Only mark as fully_paid if approved_amount > 0 AND due_amount is explicitly 0
                    // (meaning payments were actually made to cover it)
                    if (isset($request['approved_amount']) && $request['approved_amount'] > 0 
                        && isset($request['due_amount']) && $request['due_amount'] <= 0) {
                        return 'fully_paid';
                    } elseif (isset($request['due_amount']) && isset($request['approved_amount'])
                        && $request['approved_amount'] > 0
                        && $request['due_amount'] > 0 
                        && $request['due_amount'] < $request['approved_amount']) {
                        return 'partially_paid';
                    }
                    return 'approved';

                case 'rejected':
                    return 'rejected';

                case 'pending':
                default:
                    if ($cashoutStatus === 'qc_approved') {
                        return 'qc_approved';
                    }
                    return 'pending';
            }
        }

        // If no approval record, use cashout status
        if ($cashoutStatus === 'qc_approved') {
            return 'qc_approved';
        }
        return $cashoutStatus;
    }

    // NEW: Force sync all pending requests with approval system
    public function syncPendingRequests()
    {
        $sql = "UPDATE {$this->table} cr
                LEFT JOIN (
                    SELECT ar1.* FROM approval_requests ar1
                    INNER JOIN (
                        SELECT source_table, source_id, MAX(id) as max_id 
                        FROM approval_requests 
                        GROUP BY source_table, source_id
                    ) ar2 ON ar1.id = ar2.max_id
                ) ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
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
        // Step 1: Update approved_amount
        $sql = "UPDATE {$this->table} SET 
                approved_amount = ?,
                updated_at = NOW()
                WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("di", $approvedAmount, $id);
        $result = $stmt->execute();
        $stmt->close();

        // Step 2: Calculate and set due_amount = approved_amount - total payments
        $totalPaid = 0;
        $paidSql = "SELECT COALESCE(SUM(amount), 0) as total FROM cashout_payments WHERE cashout_request_id = ?";
        $paidStmt = $this->db->prepare($paidSql);
        $paidStmt->bind_param("i", $id);
        $paidStmt->execute();
        $paidResult = $paidStmt->get_result();
        if ($row = $paidResult->fetch_assoc()) {
            $totalPaid = (float)$row['total'];
        }
        $paidStmt->close();

        $dueAmount = $approvedAmount - $totalPaid;
        $dueSql = "UPDATE {$this->table} SET due_amount = ? WHERE id = ?";
        $dueStmt = $this->db->prepare($dueSql);
        $dueStmt->bind_param("di", $dueAmount, $id);
        $dueStmt->execute();
        $dueStmt->close();

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
    // --- CSV IMPORT LOGIC ---

    public function parseAndValidateCSV($filePath)
    {
        $validRows = [];
        $errors = [];
        $summary = [
            'total_rows' => 0,
            'valid_rows' => 0,
            'error_rows' => 0,
            'total_amount' => 0
        ];

        if (!file_exists($filePath)) {
            $errors[] = ['row' => 0, 'message' => "File not found"];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        if (!$header) {
            $errors[] = ['row' => 0, 'message' => "CSV is empty or invalid format"];
            return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
        }

        // Map headers
        $headerMap = [];
        foreach ($header as $idx => $col) {
            $headerMap[trim($col)] = $idx;
        }

        $requiredHeaders = ['Payment Type', 'Project Name', 'Amount', 'Date', 'Status'];
        foreach ($requiredHeaders as $req) {
            if (!isset($headerMap[$req])) {
                $errors[] = ['row' => 0, 'message' => "Missing required column: '$req'"];
                return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
            }
        }

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) continue;
            
            $summary['total_rows']++;
            $rowErrors = [];

            $paymentTypeRaw = trim($row[$headerMap['Payment Type']] ?? '');
            $paymentType = strtolower($paymentTypeRaw);
            $projectName = trim($row[$headerMap['Project Name']] ?? '');
            $amountRaw = trim($row[$headerMap['Amount']] ?? '0');
            $amount = (float)str_replace(',', '', $amountRaw);
            $date = trim($row[$headerMap['Date']] ?? date('Y-m-d H:i:s'));
            if(strpos($date, ' ') === false) $date .= ' 00:00:00';
            
            $statusRaw = trim($row[$headerMap['Status']] ?? 'Pending');
            $status = strtolower($statusRaw);
            if ($status == 'paid') $status = 'fully_paid';
            if ($status == 'partially paid') $status = 'partially_paid';
            if ($status == 'approved') $status = 'approved';

            $purpose = trim($row[$headerMap['Purpose / Description']] ?? '');
            $vendorName = trim($row[$headerMap['Vendor Name']] ?? '');
            $vendorPhone = trim($row[$headerMap['Vendor Phone']] ?? '');
            $poNumber = trim($row[$headerMap['PO Number']] ?? '');
            $invoiceNumber = trim($row[$headerMap['Invoice Number']] ?? '');
            $operatorName = trim($row[$headerMap['Operator Name']] ?? '');
            $fileName = trim($row[$headerMap['File Attachment']] ?? '');
            $paymentMethod = trim($row[$headerMap['Payment Method']] ?? 'BankTransfer');
            $paidAmountRaw = trim($row[$headerMap['Paid Amount']] ?? '0');
            $paidAmount = (float)str_replace(',', '', $paidAmountRaw);

            // Validations
            if (!in_array($paymentType, ['general', 'vendor', 'contractor'])) {
                $rowErrors[] = "Invalid Payment Type '$paymentTypeRaw'";
            }

            if ($amount <= 0) {
                $rowErrors[] = "Amount must be greater than 0";
            }

            // User is always the session user (the person importing)
            $userId = null; // will be set from session in processCSVImportRow
            $resolvedUser = "Session User";

            // Resolve Project
            $projectId = null;
            if (!empty($projectName)) {
                $stmt = $this->db->prepare("SELECT id FROM projects WHERE name = ? LIMIT 1");
                $stmt->bind_param("s", $projectName);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($p = $res->fetch_assoc()) {
                    $projectId = $p['id'];
                } else {
                    $rowErrors[] = "Project '$projectName' not found";
                }
                $stmt->close();
            }

            $vendorId = null;
            $poId = null;
            $operatorId = null;

            if ($paymentType === 'vendor') {
                if (empty($vendorName)) {
                    $rowErrors[] = "Vendor Name is required for Vendor payments";
                } else {
                    // Try to find vendor by Name and optionally Phone
                    $vQuery = "SELECT id FROM vendors WHERE name = ?";
                    $vParams = [$vendorName];
                    $vTypes = "s";
                    
                    if (!empty($vendorPhone)) {
                        $vQuery .= " AND phone = ?";
                        $vParams[] = $vendorPhone;
                        $vTypes .= "s";
                    }
                    $vQuery .= " LIMIT 1";

                    $stmt = $this->db->prepare($vQuery);
                    $stmt->bind_param($vTypes, ...$vParams);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($v = $res->fetch_assoc()) {
                        $vendorId = $v['id'];
                    } else {
                        $rowErrors[] = "Vendor '$vendorName' not found" . (!empty($vendorPhone) ? " with phone '$vendorPhone'" : "");
                    }
                    $stmt->close();

                    // 1. If PO Number is explicitly provided in CSV (e.g., '123' or 'PO-123')
                    if (!empty($poNumber)) {
                        $poNumberClean = (int)str_replace('PO-', '', strtoupper($poNumber));
                        if ($poNumberClean > 0) {
                            $poId = $poNumberClean;
                        }
                    }

                    // 2. Intelligent Auto-Detection if PO ID is not provided
                    if (!$poId && $vendorId && $projectId) {
                        $poQuery = "
                            SELECT po.id, po.created_at, 
                                   (SELECT SUM(total_price) FROM purchase_order_items WHERE purchase_order_id = po.id) as total_amount
                            FROM purchase_orders po
                            JOIN purchase_requests pr ON po.request_id = pr.id
                            JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                            WHERE pr.project_id = ? AND poi.selected_vendor_id = ?
                            GROUP BY po.id
                        ";
                        $stmt = $this->db->prepare($poQuery);
                        $stmt->bind_param("ii", $projectId, $vendorId);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        
                        $bestPoId = null;
                        $bestScore = -1;
                        $paymentTimestamp = strtotime($date);

                        while ($po = $res->fetch_assoc()) {
                            $score = 0;
                            $poTimestamp = strtotime($po['created_at']);
                            
                            // A payment usually happens AFTER the PO is created.
                            // If PO was created after the payment date, it's highly unlikely to be the right one.
                            if ($poTimestamp > $paymentTimestamp) {
                                continue; 
                            }
                            
                            // Scoring logic
                            // +50 points if the total PO amount exactly matches the payment amount
                            if (abs((float)$po['total_amount'] - $amount) < 0.01) {
                                $score += 50;
                            }
                            
                            // +Points for time proximity (closer = better)
                            // Max 30 points, decays over time
                            $daysDiff = floor(($paymentTimestamp - $poTimestamp) / (60 * 60 * 24));
                            $timeScore = max(0, 30 - $daysDiff);
                            $score += $timeScore;
                            
                            if ($score > $bestScore) {
                                $bestScore = $score;
                                $bestPoId = $po['id'];
                            }
                        }
                        $stmt->close();
                        
                        if ($bestPoId) {
                            $poId = $bestPoId;
                        } else {
                            $rowErrors[] = "No matching Purchase Order found for this Vendor and Project before the payment date. Please specify 'PO Number' in CSV.";
                        }
                    } elseif (!$poId) {
                        $poId = 1; // Fallback if vendor/project are completely missing
                    }
                }
            } elseif ($paymentType === 'contractor') {
                if (empty($operatorName)) {
                    $rowErrors[] = "Operator Name is required for Contractor bills";
                } else {
                    $stmt = $this->db->prepare("SELECT id FROM contractors WHERE name = ? LIMIT 1");
                    $stmt->bind_param("s", $operatorName);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($o = $res->fetch_assoc()) {
                        $operatorId = $o['id'];
                    } else {
                        $rowErrors[] = "Operator '$operatorName' not found";
                    }
                    $stmt->close();
                }
            }

            if (!empty($rowErrors)) {
                $errors[] = [
                    'row' => $rowNum,
                    'messages' => $rowErrors
                ];
                $summary['error_rows']++;
            } else {
                $validRows[] = [
                    'row_num' => $rowNum,
                    'payment_type' => $paymentType,
                    'project_id' => $projectId,
                    'project_name' => $projectName,
                    'user_id' => $userId,
                    'resolved_user' => $resolvedUser,
                    'amount' => $amount,
                    'date' => $date,
                    'status_raw' => $statusRaw,
                    'status' => $status,
                    'purpose' => $purpose,
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendorName,
                    'po_id' => $poId,
                    'invoice_number' => $invoiceNumber,
                    'operator_id' => $operatorId,
                    'operator_name' => $operatorName,
                    'file_name' => $fileName,
                    'payment_method' => $paymentMethod,
                    'paid_amount' => $paidAmount
                ];
                $summary['valid_rows']++;
                $summary['total_amount'] += $amount;
            }
        }
        fclose($handle);

        return ['valid_rows' => $validRows, 'errors' => $errors, 'summary' => $summary];
    }

    public function processCSVImportRow($row, $sessionUserId)
    {
        $this->db->begin_transaction();
        try {
            $paymentType = $row['payment_type'];
            $projectId = $row['project_id'];
            $userId = $sessionUserId; // Always use logged-in user
            $amount = $row['amount'];
            $date = $row['date'];
            $status = $row['status'];
            $purpose = $row['purpose'];
            $fileName = $row['file_name'];
            $paymentMethod = $row['payment_method'];
            $paidAmount = $row['paid_amount'];

            $insertId = 0;

            if ($paymentType === 'general') {
                $dueAmount = $amount - $paidAmount;
                $attachmentPath = $fileName ? 'uploads/cashout/' . $fileName : null;

                $statusNormalized = strtolower(str_replace(' ', '_', $status));
                if ($statusNormalized === 'paid') $statusNormalized = 'fully_paid';
                $finalStatus = in_array($statusNormalized, ['pending','approved','rejected','partially_paid','fully_paid']) ? $statusNormalized : 'pending';

                $sql = "INSERT INTO cashout_requests (project_id, requested_by, purpose_paid_to, amount, approved_amount, due_amount, description, payment_method, status, attachment_path, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $approvedAmount = in_array($finalStatus, ['approved', 'partially_paid', 'fully_paid']) ? $amount : null;
                $stmt->bind_param("sisdddsssss", $projectId, $userId, $purpose, $amount, $approvedAmount, $dueAmount, $purpose, $paymentMethod, $finalStatus, $attachmentPath, $date);
                if (!$stmt->execute()) throw new Exception("General insert failed: " . $stmt->error);
                $insertId = $stmt->insert_id;
                $stmt->close();

                // Approval Request
                $appStatus = in_array($finalStatus, ['approved', 'partially_paid', 'fully_paid']) ? 'approved' : 'pending';
                $appSql = "INSERT INTO approval_requests (department, request_type, title, source_table, source_id, request_amount, approved_amount, status, requested_by, approve_role, created_at, approved_by, approved_at)
                           VALUES ('Accounts', 'cashout', ?, 'cashout_requests', ?, ?, ?, ?, ?, 'management', ?, ?, ?)";
                $appStmt = $this->db->prepare($appSql);
                $title = "Imported Cashout: " . substr($purpose, 0, 100);
                $approvedBy = ($appStatus === 'approved') ? $sessionUserId : null;
                $approvedAt = ($appStatus === 'approved') ? $date : null;
                $appStmt->bind_param("siddsisis", $title, $insertId, $amount, $approvedAmount, $appStatus, $userId, $date, $approvedBy, $approvedAt);
                $appStmt->execute();
                $appStmt->close();

                // Cashout Payment
                if ($paidAmount > 0) {
                    $paySql = "INSERT INTO cashout_payments (cashout_request_id, project_id, purpose_paid_to, amount, payment_method, payment_date, created_by, created_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $payStmt = $this->db->prepare($paySql);
                    $payStmt->bind_param("issdssis", $insertId, $projectId, $purpose, $paidAmount, $paymentMethod, $date, $sessionUserId, $date);
                    $payStmt->execute();
                    $payStmt->close();
                }

            } elseif ($paymentType === 'vendor') {
                $vendorId = $row['vendor_id'];
                $poId = $row['po_id'] ?: 1;
                $invoiceNumber = $row['invoice_number'];
                
                $statusNormalized = strtolower(str_replace(' ', '_', $row['status_raw']));
                if ($statusNormalized === 'paid') $finalStatus = 'Paid';
                elseif ($statusNormalized === 'fully_paid') $finalStatus = 'Fully_Paid';
                elseif ($statusNormalized === 'partially_paid') $finalStatus = 'Partially_Paid';
                elseif ($statusNormalized === 'approved') $finalStatus = 'Approved';
                elseif ($statusNormalized === 'rejected') $finalStatus = 'Rejected';
                elseif ($statusNormalized === 'submitted' || $statusNormalized === 'pending') $finalStatus = 'Submitted';
                else $finalStatus = 'Draft';
                
                $dueAmount = $amount - $paidAmount;
                $attachmentPath = $fileName ? 'uploads/construction-payments/' . $fileName : null;

                $sql = "INSERT INTO vendor_payment_requests (vendor_id, project_id, purchase_order_id, payment_type, created_by, invoice_number, invoice_amount, net_payable_amount, status, approved_amount, due_amount, payment_attachment, created_at)
                        VALUES (?, ?, ?, 'Final', ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $approvedAmount = in_array($finalStatus, ['Approved', 'Paid', 'Partially_Paid', 'Fully_Paid']) ? $amount : null;
                $stmt->bind_param("iiiisddsddss", $vendorId, $projectId, $poId, $userId, $invoiceNumber, $amount, $amount, $finalStatus, $approvedAmount, $dueAmount, $attachmentPath, $date);
                if (!$stmt->execute()) throw new Exception("Vendor insert failed: " . $stmt->error);
                $insertId = $stmt->insert_id;
                $stmt->close();

                // Approval Request
                $appStatus = in_array($finalStatus, ['Approved', 'Paid', 'Partially_Paid', 'Fully_Paid']) ? 'approved' : 'pending';
                $appSql = "INSERT INTO approval_requests (department, request_type, title, source_table, source_id, request_amount, approved_amount, status, requested_by, approve_role, created_at, approved_by, approved_at)
                           VALUES ('Construction', 'vendor_payment', ?, 'vendor_payment_requests', ?, ?, ?, ?, ?, 'management', ?, ?, ?)";
                $appStmt = $this->db->prepare($appSql);
                $title = "Imported Vendor Payment: " . substr($vendorName ?? 'Vendor', 0, 100);
                $approvedBy = ($appStatus === 'approved') ? $sessionUserId : null;
                $approvedAt = ($appStatus === 'approved') ? $date : null;
                $appStmt->bind_param("siddsisis", $title, $insertId, $amount, $approvedAmount, $appStatus, $userId, $date, $approvedBy, $approvedAt);
                $appStmt->execute();
                $appStmt->close();

                if ($paidAmount > 0) {
                    $paySql = "INSERT INTO con_financial_transactions (ref_type, project_id, ref_id, payment_type, payment_method, amount_paid, processed_by, created_by, transaction_date)
                               VALUES ('vendor_payment', ?, ?, 'Full', ?, ?, ?, ?, ?)";
                    $payStmt = $this->db->prepare($paySql);
                    $payStmt->bind_param("iisdiis", $projectId, $insertId, $paymentMethod, $paidAmount, $sessionUserId, $sessionUserId, $date);
                    $payStmt->execute();
                    $payStmt->close();
                }

            } elseif ($paymentType === 'contractor') {
                $operatorId = $row['operator_id'];
                
                $statusNormalized = strtolower(str_replace(' ', '_', $row['status_raw']));
                if ($statusNormalized === 'pending') $statusNormalized = 'submitted';
                $finalStatus = in_array($statusNormalized, ['draft','submitted','verified','approved','rejected','paid','partially_paid','fully_paid']) ? $statusNormalized : 'draft';
                
                $dueAmount = $amount - $paidAmount;
                $attachmentPath = $fileName ? 'uploads/contractor-bills/' . $fileName : null;
                $billDate = substr($date, 0, 10);

                $sql = "INSERT INTO contractor_bill_requests (project_id, operator_id, created_by, bill_date, work_description, bill_amount, status, approved_amount, due_amount, attachment_path, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $approvedAmount = in_array($finalStatus, ['approved', 'paid', 'partially_paid', 'fully_paid']) ? $amount : null;
                $stmt->bind_param("iiissdsddss", $projectId, $operatorId, $userId, $billDate, $purpose, $amount, $finalStatus, $approvedAmount, $dueAmount, $attachmentPath, $date);
                if (!$stmt->execute()) throw new Exception("Contractor insert failed: " . $stmt->error);
                $insertId = $stmt->insert_id;
                $stmt->close();

                // Approval Request
                $appStatus = in_array($finalStatus, ['approved', 'paid', 'partially_paid', 'fully_paid']) ? 'approved' : 'pending';
                $appSql = "INSERT INTO approval_requests (department, request_type, title, source_table, source_id, request_amount, approved_amount, status, requested_by, approve_role, created_at, approved_by, approved_at)
                           VALUES ('Construction', 'contractor_bill', ?, 'contractor_bill_requests', ?, ?, ?, ?, ?, 'management', ?, ?, ?)";
                $appStmt = $this->db->prepare($appSql);
                $title = "Imported Contractor Bill: " . substr($operatorName ?? 'Contractor', 0, 100);
                $approvedBy = ($appStatus === 'approved') ? $sessionUserId : null;
                $approvedAt = ($appStatus === 'approved') ? $date : null;
                $appStmt->bind_param("siddsisis", $title, $insertId, $amount, $approvedAmount, $appStatus, $userId, $date, $approvedBy, $approvedAt);
                $appStmt->execute();
                $appStmt->close();

                if ($paidAmount > 0) {
                    $paySql = "INSERT INTO con_financial_transactions (ref_type, project_id, ref_id, payment_type, payment_method, amount_paid, processed_by, created_by, transaction_date)
                               VALUES ('contractor_bill', ?, ?, 'Full', ?, ?, ?, ?, ?)";
                    $payStmt = $this->db->prepare($paySql);
                    $payStmt->bind_param("iisdiis", $projectId, $insertId, $paymentMethod, $paidAmount, $sessionUserId, $sessionUserId, $date);
                    $payStmt->execute();
                    $payStmt->close();
                }
            }

            $this->db->commit();
            return ['success' => true, 'message' => 'Imported successfully', 'insert_id' => $insertId];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
?>