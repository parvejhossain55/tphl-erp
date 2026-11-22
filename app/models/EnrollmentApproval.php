<?php

class EnrollmentApproval extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get employee and department reporting info for a user
     */
    public function getCreatorReportingInfo($userId)
    {
        $userId = (int)$userId;
        $sql = "
            SELECT 
                u.id as user_id,
                u.full_name as user_full_name,
                u.role_id,
                r.name as role_name,
                e.id as employee_id,
                e.is_dept_head,
                e.reporting_user_id,
                e.department_id,
                d.name as department_name,
                boss.full_name as reporting_boss_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN users boss ON boss.id = e.reporting_user_id
            WHERE u.id = {$userId}
            LIMIT 1
        ";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return $row;
        }
        return [
            'user_id' => $userId,
            'is_dept_head' => 0,
            'reporting_user_id' => null,
            'department_id' => null,
            'department_name' => 'General',
            'role_id' => 0,
            'role_name' => '',
            'reporting_boss_name' => null
        ];
    }

    /**
     * Initiate the approval workflow for newly created vendor or contractor
     */
    public function initiateApproval($type, $sourceId, $creatorUserId)
    {
        $sourceId = (int)$sourceId;
        $creatorUserId = (int)$creatorUserId;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';

        $creatorInfo = $this->getCreatorReportingInfo($creatorUserId);

        // Check if creator is Dept Head
        // An employee is considered department head if is_dept_head = 1 OR has no reporting boss
        $isDeptHead = ((int)($creatorInfo['is_dept_head'] ?? 0) === 1);
        $reportingUserId = !empty($creatorInfo['reporting_user_id']) ? (int)$creatorInfo['reporting_user_id'] : null;

        $deptHeadStatus = 'pending';
        $deptHeadNote = null;
        $purchaseHeadStatus = 'pending';
        $purchaseHeadNote = null;
        $role20Status = 'pending';
        $role20Note = null;
        $role38Status = 'pending';
        $mgmtStatus = 'pending';

        if ($isDeptHead || empty($reportingUserId) || $reportingUserId === $creatorUserId) {
            // Creator is already Dept Head -> skip Step 1
            $deptHeadStatus = 'skipped';
            $deptHeadNote = 'Auto-skipped: Creator is Department Head';
            $reportingUserId = null;

            // Check if creator is also Purchase Head (Role 17)
            if ((int)$creatorInfo['role_id'] === 17) {
                $purchaseHeadStatus = 'skipped';
                $purchaseHeadNote = 'Auto-skipped: Creator is Purchase Head';
                
                // Check if creator is also Role 20
                if ((int)$creatorInfo['role_id'] === 20) {
                    $role20Status = 'skipped';
                    $role20Note = 'Auto-skipped: Creator is Admin & Operation Head';
                    $initialStep = 'role_38';
                } else {
                    $initialStep = 'role_20';
                }
            } else {
                $initialStep = 'purchase_head';
            }
        } else {
            $initialStep = 'dept_head';
        }

        // Update vendor / contractor record
        $stmt = $this->db->prepare("
            UPDATE {$table} SET 
                status = 'pending_approval',
                current_step = ?,
                dept_head_id = ?,
                dept_head_status = ?,
                dept_head_note = ?,
                purchase_head_status = ?,
                purchase_head_note = ?,
                role_20_status = ?,
                role_20_note = ?,
                role_38_status = ?,
                management_status = ?
            WHERE id = ?
        ");

        if ($stmt) {
            $stmt->bind_param(
                'sissssssssi',
                $initialStep,
                $reportingUserId,
                $deptHeadStatus,
                $deptHeadNote,
                $purchaseHeadStatus,
                $purchaseHeadNote,
                $role20Status,
                $role20Note,
                $role38Status,
                $mgmtStatus,
                $sourceId
            );
            $stmt->execute();
            $stmt->close();
        }

        // Fetch name for title (Business Name preferred, fallback to Contact Person Name)
        $recordName = "ID #{$sourceId}";
        $nameCol = ($type === 'contractor') ? 'name' : "COALESCE(NULLIF(TRIM(company_name), ''), name) AS name";
        $nr = $this->db->query("SELECT {$nameCol} FROM {$table} WHERE id = {$sourceId} LIMIT 1");
        if ($nr && $nrow = $nr->fetch_assoc()) {
            $recordName = $nrow['name'];
        }

        $reqType = ($type === 'contractor') ? 'contractor_enrollment' : 'vendor_enrollment';
        $title = ($type === 'contractor') ? "Contractor Enrollment: {$recordName}" : "Vendor Enrollment: {$recordName}";

        // Insert into approval_requests
        $stmtApp = $this->db->prepare("
            INSERT INTO approval_requests 
            (department, request_type, title, source_table, source_id, requested_by, status, approve_role, created_at, updated_at)
            VALUES ('Construction', ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
        ");
        if ($stmtApp) {
            $stmtApp->bind_param('sssiis', $reqType, $title, $table, $sourceId, $creatorUserId, $initialStep);
            $stmtApp->execute();
            $stmtApp->close();
        }

        // Log into con_approval_logs
        $this->logAction($table, $sourceId, 'Enrollment Submission', $creatorUserId, 'approved', 'Enrollment request created and submitted for multi-step approval.');

        return $initialStep;
    }

    /**
     * Check if a specific enrollment record is actionable by the given user
     */
    public function isActionableForUser($item, $userId, $userRoleId, $userSecRoleId = 0)
    {
        if (empty($item) || ($item['status'] ?? '') !== 'pending_approval') {
            return false;
        }

        $userId = (int)$userId;
        $userRoleId = (int)$userRoleId;
        $userSecRoleId = (int)$userSecRoleId;
        $currentStep = $item['current_step'] ?? '';

        // Super Admin / Admin override access
        $isGlobalAdmin = in_array($userRoleId, [1, 3], true) || in_array($userSecRoleId, [1, 3], true);

        switch ($currentStep) {
            case 'dept_head':
                // Only specific reporting boss or Admin/Super Admin
                $deptHeadId = (int)($item['dept_head_id'] ?? 0);
                return ($userId > 0 && $userId === $deptHeadId) || $isGlobalAdmin;

            case 'purchase_head':
                // Role 17 (Purchase team leader) or Admin/Super Admin
                return in_array($userRoleId, [17, 1, 3], true) || in_array($userSecRoleId, [17, 1, 3], true);

            case 'role_20':
                // Role 20 (Admin & Operation head) or Admin/Super Admin
                return in_array($userRoleId, [20, 1, 3], true) || in_array($userSecRoleId, [20, 1, 3], true);

            case 'role_38':
                // Role 38 (Board of Director) or Admin/Super Admin
                return in_array($userRoleId, [38, 1, 3], true) || in_array($userSecRoleId, [38, 1, 3], true);

            case 'management':
                // Management: Roles 1, 3, 33, 34, 35, 38
                return in_array($userRoleId, [1, 3, 33, 34, 35, 38], true) || in_array($userSecRoleId, [1, 3, 33, 34, 35, 38], true);

            default:
                return false;
        }
    }

    /**
     * Get count of pending actionable requests for the logged-in user (for sidebar badge)
     */
    public function getActionableCountForUser($type, $userId, $userRoleId, $userSecRoleId = 0)
    {
        $userId = (int)$userId;
        $userRoleId = (int)$userRoleId;
        $userSecRoleId = (int)$userSecRoleId;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';

        $isGlobalAdmin = in_array($userRoleId, [1, 3], true) || in_array($userSecRoleId, [1, 3], true);
        $isPurchaseHead = in_array($userRoleId, [17, 1, 3], true) || in_array($userSecRoleId, [17, 1, 3], true);
        $isRole20 = in_array($userRoleId, [20, 1, 3], true) || in_array($userSecRoleId, [20, 1, 3], true);
        $isRole38 = in_array($userRoleId, [38, 1, 3], true) || in_array($userSecRoleId, [38, 1, 3], true);
        $isManagement = in_array($userRoleId, [1, 3, 33, 34, 35, 38], true) || in_array($userSecRoleId, [1, 3, 33, 34, 35, 38], true);

        $conditions = [];

        // Dept Head condition
        if ($isGlobalAdmin) {
            $conditions[] = "(current_step = 'dept_head')";
        } elseif ($userId > 0) {
            $conditions[] = "(current_step = 'dept_head' AND dept_head_id = {$userId})";
        }

        // Purchase Head
        if ($isPurchaseHead) {
            $conditions[] = "(current_step = 'purchase_head')";
        }

        // Role 20
        if ($isRole20) {
            $conditions[] = "(current_step = 'role_20')";
        }

        // Role 38
        if ($isRole38) {
            $conditions[] = "(current_step = 'role_38')";
        }

        // Management
        if ($isManagement) {
            $conditions[] = "(current_step = 'management')";
        }

        if (empty($conditions)) {
            return 0;
        }

        $whereClause = implode(" OR ", $conditions);
        $sql = "SELECT COUNT(*) as cnt FROM {$table} WHERE status = 'pending_approval' AND ({$whereClause})";
        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['cnt'];
        }
        return 0;
    }

    /**
     * Approve the current step in the workflow
     */
    public function approve($type, $id, $userId, $userRoleId, $userSecRoleId = 0, $note = '')
    {
        $id = (int)$id;
        $userId = (int)$userId;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';

        $item = $this->getRecordById($type, $id);
        if (!$item) {
            return ['success' => false, 'message' => 'Record not found.'];
        }

        if (!$this->isActionableForUser($item, $userId, $userRoleId, $userSecRoleId)) {
            return ['success' => false, 'message' => 'You do not have permission to approve this request at its current stage.'];
        }

        $currentStep = $item['current_step'];
        $nextStep = '';
        $stepTitle = '';
        $isFinal = false;

        switch ($currentStep) {
            case 'dept_head':
                $nextStep = 'purchase_head';
                $stepTitle = 'Department Head Approval';
                $colPrefix = 'dept_head';
                break;

            case 'purchase_head':
                $nextStep = 'role_20';
                $stepTitle = 'Purchase Head Approval';
                $colPrefix = 'purchase_head';
                break;

            case 'role_20':
                $nextStep = 'role_38';
                $stepTitle = 'Admin & Operation Head Approval';
                $colPrefix = 'role_20';
                break;

            case 'role_38':
                $nextStep = 'management';
                $stepTitle = 'Board of Director Approval';
                $colPrefix = 'role_38';
                break;

            case 'management':
                $nextStep = 'approved';
                $stepTitle = 'Management Final Approval';
                $colPrefix = 'management';
                $isFinal = true;
                break;

            default:
                return ['success' => false, 'message' => 'Invalid approval stage.'];
        }

        $statusUpdate = $isFinal ? "status = 'active'," : "";
        $noteSql = !empty($note) ? $note : ($isFinal ? 'Approved by Management' : "Approved at {$stepTitle}");

        $sql = "
            UPDATE {$table} SET 
                {$statusUpdate}
                current_step = ?,
                {$colPrefix}_status = 'approved',
                {$colPrefix}_by = ?,
                {$colPrefix}_at = NOW(),
                {$colPrefix}_note = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->db->error];
        }
        $stmt->bind_param('sisi', $nextStep, $userId, $noteSql, $id);
        $stmt->execute();
        $stmt->close();

        // Update central approval_requests table
        if ($isFinal) {
            $this->db->query("
                UPDATE approval_requests 
                SET status = 'approved', 
                    approve_role = 'approved', 
                    approved_by = {$userId}, 
                    approved_at = NOW(),
                    approval_note = " . ($note ? "'" . $this->db->real_escape_string($note) . "'" : "NULL") . "
                WHERE source_table = '{$table}' AND source_id = {$id}
            ");
        } else {
            $this->db->query("
                UPDATE approval_requests 
                SET approve_role = '{$nextStep}', 
                    updated_at = NOW() 
                WHERE source_table = '{$table}' AND source_id = {$id}
            ");
        }

        // Log action in con_approval_logs
        $this->logAction($table, $id, $stepTitle, $userId, 'approved', $noteSql);

        $successMsg = $isFinal
            ? ucfirst($type) . ' has been fully approved and is now active in the system!'
            : ucfirst($type) . " enrollment successfully approved at {$stepTitle}! Advanced to next stage: " . $this->getStepLabel($nextStep);

        return ['success' => true, 'message' => $successMsg, 'next_step' => $nextStep, 'is_final' => $isFinal];
    }

    /**
     * Reject the request at any step
     */
    public function reject($type, $id, $userId, $userRoleId, $userSecRoleId = 0, $reason = '')
    {
        $id = (int)$id;
        $userId = (int)$userId;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';

        $item = $this->getRecordById($type, $id);
        if (!$item) {
            return ['success' => false, 'message' => 'Record not found.'];
        }

        if (!$this->isActionableForUser($item, $userId, $userRoleId, $userSecRoleId)) {
            return ['success' => false, 'message' => 'You do not have permission to reject this request.'];
        }

        if (empty(trim($reason))) {
            return ['success' => false, 'message' => 'A rejection reason is required.'];
        }

        $currentStep = $item['current_step'];
        $colPrefix = $currentStep;
        if (!in_array($colPrefix, ['dept_head', 'purchase_head', 'role_20', 'role_38', 'management'])) {
            $colPrefix = 'management';
        }

        $sql = "
            UPDATE {$table} SET 
                status = 'rejected',
                current_step = 'rejected',
                rejection_reason = ?,
                {$colPrefix}_status = 'rejected',
                {$colPrefix}_by = ?,
                {$colPrefix}_at = NOW(),
                {$colPrefix}_note = ?
            WHERE id = ?
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->db->error];
        }
        $stmt->bind_param('sisi', $reason, $userId, $reason, $id);
        $stmt->execute();
        $stmt->close();

        // Update approval_requests
        $escReason = $this->db->real_escape_string($reason);
        $this->db->query("
            UPDATE approval_requests 
            SET status = 'rejected', 
                approve_role = 'rejected', 
                approval_note = '{$escReason}', 
                updated_at = NOW() 
            WHERE source_table = '{$table}' AND source_id = {$id}
        ");

        // Log action in con_approval_logs
        $this->logAction($table, $id, $this->getStepLabel($currentStep) . ' Rejection', $userId, 'rejected', $reason);

        return ['success' => true, 'message' => ucfirst($type) . ' enrollment request has been rejected.'];
    }

    /**
     * Get single record with all populated fields
     */
    public function getRecordById($type, $id)
    {
        $id = (int)$id;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';
        $sql = "
            SELECT 
                r.*,
                u_cr.full_name as created_by_name,
                u_cr.user_name as created_by_username,
                r_cr.name as created_by_role_name,
                d_cr.name as created_by_dept_name,
                dh_user.full_name as dept_head_name,
                dh_by.full_name as dept_head_by_name,
                ph_by.full_name as purchase_head_by_name,
                r20_by.full_name as role_20_by_name,
                r38_by.full_name as role_38_by_name,
                mgmt_by.full_name as management_by_name
            FROM {$table} r
            LEFT JOIN users u_cr ON u_cr.id = r.created_by
            LEFT JOIN roles r_cr ON r_cr.id = u_cr.role_id
            LEFT JOIN employees e_cr ON (e_cr.user_id = u_cr.id OR e_cr.id = u_cr.employee_id)
            LEFT JOIN departments d_cr ON d_cr.id = e_cr.department_id
            LEFT JOIN users dh_user ON dh_user.id = r.dept_head_id
            LEFT JOIN users dh_by ON dh_by.id = r.dept_head_by
            LEFT JOIN users ph_by ON ph_by.id = r.purchase_head_by
            LEFT JOIN users r20_by ON r20_by.id = r.role_20_by
            LEFT JOIN users r38_by ON r38_by.id = r.role_38_by
            LEFT JOIN users mgmt_by ON mgmt_by.id = r.management_by
            WHERE r.id = {$id}
            LIMIT 1
        ";
        $res = $this->db->query($sql);
        return ($res && $row = $res->fetch_assoc()) ? $row : null;
    }

    /**
     * Get count of filtered requests
     */
    public function getRequestsCount($type, $tab = 'action_required', $userId = 0, $userRoleId = 0, $userSecRoleId = 0, $search = '')
    {
        $userId = (int)$userId;
        $userRoleId = (int)$userRoleId;
        $userSecRoleId = (int)$userSecRoleId;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';

        $where = [];

        if (!empty($search)) {
            $escSearch = $this->db->real_escape_string($search);
            if ($type === 'contractor') {
                $where[] = "(r.name LIKE '%{$escSearch}%' OR r.phone LIKE '%{$escSearch}%' OR r.trade LIKE '%{$escSearch}%' OR r.nid_number LIKE '%{$escSearch}%' OR u_cr.full_name LIKE '%{$escSearch}%')";
            } else {
                $where[] = "(r.company_name LIKE '%{$escSearch}%' OR r.name LIKE '%{$escSearch}%' OR r.phone LIKE '%{$escSearch}%' OR r.email LIKE '%{$escSearch}%' OR r.nid_number LIKE '%{$escSearch}%' OR r.trade_license_number LIKE '%{$escSearch}%' OR u_cr.full_name LIKE '%{$escSearch}%')";
            }
        }

        $isGlobalAdmin = in_array($userRoleId, [1, 3], true) || in_array($userSecRoleId, [1, 3], true);
        $isPurchaseHead = in_array($userRoleId, [17, 1, 3], true) || in_array($userSecRoleId, [17, 1, 3], true);
        $isRole20 = in_array($userRoleId, [20, 1, 3], true) || in_array($userSecRoleId, [20, 1, 3], true);
        $isRole38 = in_array($userRoleId, [38, 1, 3], true) || in_array($userSecRoleId, [38, 1, 3], true);
        $isManagement = in_array($userRoleId, [1, 3, 33, 34, 35, 38], true) || in_array($userSecRoleId, [1, 3, 33, 34, 35, 38], true);

        if ($tab === 'action_required') {
            $actConds = [];
            if ($isGlobalAdmin) {
                $actConds[] = "(r.current_step = 'dept_head')";
            } elseif ($userId > 0) {
                $actConds[] = "(r.current_step = 'dept_head' AND r.dept_head_id = {$userId})";
            }
            if ($isPurchaseHead) $actConds[] = "(r.current_step = 'purchase_head')";
            if ($isRole20) $actConds[] = "(r.current_step = 'role_20')";
            if ($isRole38) $actConds[] = "(r.current_step = 'role_38')";
            if ($isManagement) $actConds[] = "(r.current_step = 'management')";

            if (!empty($actConds)) {
                $where[] = "r.status = 'pending_approval' AND (" . implode(" OR ", $actConds) . ")";
            } else {
                $where[] = "1 = 0";
            }
        } elseif ($tab === 'pending') {
            $where[] = "r.status = 'pending_approval'";
        } elseif ($tab === 'approved') {
            $where[] = "(r.status = 'active' AND r.current_step = 'approved')";
        } elseif ($tab === 'rejected') {
            $where[] = "r.status = 'rejected'";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT COUNT(*) as cnt
            FROM {$table} r
            LEFT JOIN users u_cr ON u_cr.id = r.created_by
            {$whereSql}
        ";

        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return (int)$row['cnt'];
        }
        return 0;
    }

    /**
     * Get filtered list of requests with pagination
     */
    public function getRequestsList($type, $tab = 'action_required', $userId = 0, $userRoleId = 0, $userSecRoleId = 0, $search = '', $limit = 50, $offset = 0)
    {
        $userId = (int)$userId;
        $userRoleId = (int)$userRoleId;
        $userSecRoleId = (int)$userSecRoleId;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';

        $where = [];

        if (!empty($search)) {
            $escSearch = $this->db->real_escape_string($search);
            if ($type === 'contractor') {
                $where[] = "(r.name LIKE '%{$escSearch}%' OR r.phone LIKE '%{$escSearch}%' OR r.trade LIKE '%{$escSearch}%' OR r.nid_number LIKE '%{$escSearch}%' OR u_cr.full_name LIKE '%{$escSearch}%')";
            } else {
                $where[] = "(r.company_name LIKE '%{$escSearch}%' OR r.name LIKE '%{$escSearch}%' OR r.phone LIKE '%{$escSearch}%' OR r.email LIKE '%{$escSearch}%' OR r.nid_number LIKE '%{$escSearch}%' OR r.trade_license_number LIKE '%{$escSearch}%' OR u_cr.full_name LIKE '%{$escSearch}%')";
            }
        }

        $isGlobalAdmin = in_array($userRoleId, [1, 3], true) || in_array($userSecRoleId, [1, 3], true);
        $isPurchaseHead = in_array($userRoleId, [17, 1, 3], true) || in_array($userSecRoleId, [17, 1, 3], true);
        $isRole20 = in_array($userRoleId, [20, 1, 3], true) || in_array($userSecRoleId, [20, 1, 3], true);
        $isRole38 = in_array($userRoleId, [38, 1, 3], true) || in_array($userSecRoleId, [38, 1, 3], true);
        $isManagement = in_array($userRoleId, [1, 3, 33, 34, 35, 38], true) || in_array($userSecRoleId, [1, 3, 33, 34, 35, 38], true);

        if ($tab === 'action_required') {
            $actConds = [];
            if ($isGlobalAdmin) {
                $actConds[] = "(r.current_step = 'dept_head')";
            } elseif ($userId > 0) {
                $actConds[] = "(r.current_step = 'dept_head' AND r.dept_head_id = {$userId})";
            }
            if ($isPurchaseHead) $actConds[] = "(r.current_step = 'purchase_head')";
            if ($isRole20) $actConds[] = "(r.current_step = 'role_20')";
            if ($isRole38) $actConds[] = "(r.current_step = 'role_38')";
            if ($isManagement) $actConds[] = "(r.current_step = 'management')";

            if (!empty($actConds)) {
                $where[] = "r.status = 'pending_approval' AND (" . implode(" OR ", $actConds) . ")";
            } else {
                $where[] = "1 = 0";
            }
        } elseif ($tab === 'pending') {
            $where[] = "r.status = 'pending_approval'";
        } elseif ($tab === 'approved') {
            $where[] = "(r.status = 'active' AND r.current_step = 'approved')";
        } elseif ($tab === 'rejected') {
            $where[] = "r.status = 'rejected'";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        $limitClause = ($limit > 0) ? "LIMIT {$limit} OFFSET {$offset}" : "";

        $sql = "
            SELECT 
                r.*,
                u_cr.full_name as created_by_name,
                u_cr.user_name as created_by_username,
                r_cr.name as created_by_role_name,
                d_cr.name as created_by_dept_name,
                dh_user.full_name as dept_head_name,
                dh_by.full_name as dept_head_by_name,
                ph_by.full_name as purchase_head_by_name,
                r20_by.full_name as role_20_by_name,
                r38_by.full_name as role_38_by_name,
                mgmt_by.full_name as management_by_name
            FROM {$table} r
            LEFT JOIN users u_cr ON u_cr.id = r.created_by
            LEFT JOIN roles r_cr ON r_cr.id = u_cr.role_id
            LEFT JOIN employees e_cr ON (e_cr.user_id = u_cr.id OR e_cr.id = u_cr.employee_id)
            LEFT JOIN departments d_cr ON d_cr.id = e_cr.department_id
            LEFT JOIN users dh_user ON dh_user.id = r.dept_head_id
            LEFT JOIN users dh_by ON dh_by.id = r.dept_head_by
            LEFT JOIN users ph_by ON ph_by.id = r.purchase_head_by
            LEFT JOIN users r20_by ON r20_by.id = r.role_20_by
            LEFT JOIN users r38_by ON r38_by.id = r.role_38_by
            LEFT JOIN users mgmt_by ON mgmt_by.id = r.management_by
            {$whereSql}
            ORDER BY 
                CASE WHEN r.status = 'pending_approval' THEN 1 ELSE 2 END,
                r.created_at DESC
            {$limitClause}
        ";

        $res = $this->db->query($sql);
        $items = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $row['is_actionable'] = $this->isActionableForUser($row, $userId, $userRoleId, $userSecRoleId);
                $items[] = $row;
            }
        }
        return $items;
    }

    /**
     * Get KPI Summary counts
     */
    public function getCountsSummary($type, $userId, $userRoleId, $userSecRoleId = 0)
    {
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';
        $actionable = $this->getActionableCountForUser($type, $userId, $userRoleId, $userSecRoleId);

        $pending = 0;
        $approved = 0;
        $rejected = 0;
        $total = 0;

        $r = $this->db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_cnt,
                SUM(CASE WHEN status = 'active' AND current_step = 'approved' THEN 1 ELSE 0 END) as approved_cnt,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_cnt
            FROM {$table}
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $total = (int)$row['total'];
            $pending = (int)$row['pending_cnt'];
            $approved = (int)$row['approved_cnt'];
            $rejected = (int)$row['rejected_cnt'];
        }

        return [
            'actionable' => $actionable,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'total' => $total
        ];
    }

    /**
     * Fetch timeline logs from con_approval_logs
     */
    public function getApprovalLogs($type, $id)
    {
        $id = (int)$id;
        $table = ($type === 'contractor') ? 'contractors' : 'vendors';
        $sql = "
            SELECT 
                l.*,
                u.full_name as user_name,
                r.name as role_name
            FROM con_approval_logs l
            LEFT JOIN users u ON u.id = l.actioned_by
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE l.ref_table = '{$table}' AND l.ref_id = {$id}
            ORDER BY l.actioned_at ASC, l.id ASC
        ";
        $res = $this->db->query($sql);
        $logs = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        return $logs;
    }

    /**
     * Human readable stage labels
     */
    public function getStepLabel($step)
    {
        $labels = [
            'dept_head' => 'Department Head / Reporting Boss',
            'purchase_head' => 'Purchase Head',
            'role_20' => 'Admin & Operation Head',
            'role_38' => 'Board of Director',
            'management' => 'Management (Final Approval)',
            'approved' => 'Approved (Active)',
            'rejected' => 'Rejected'
        ];
        return $labels[$step] ?? ucfirst($step);
    }

    /**
     * Log helper for con_approval_logs
     */
    protected function logAction($table, $id, $stepName, $userId, $status, $remarks)
    {
        $stmt = $this->db->prepare("
            INSERT INTO con_approval_logs (ref_table, ref_id, step_name, actioned_by, status, remarks, actioned_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
        ");
        if ($stmt) {
            $stmt->bind_param('sisissi', $table, $id, $stepName, $userId, $status, $remarks, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }
}
