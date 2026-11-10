<?php

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $allowedTables = [
        'lead_bookings',
        'employee_leaves',
        'payslips',

        'salary_requests',
        'cashout_requests',
        'manpower_requisitions',
        'advance_salaries',

        // Phase 2 / Construction & Client refund tables
        'client_refund_requests',
        'operational_fund_requests',
        'con_work_orders',
        'purchase_requests',
        'vendor_payment_requests',
        'contractor_bill_requests',
        'lead_payments',
        'project_incentive_claims',
        'fund_requisitions',
        'project_other_costs',
        'project_extra_assets',
        'con_labor_requisitions',
        'expense_adjustments',
        'land_fund_requisitions',
        'con_land_requisitions'
    ];

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->ensureSchema();
    }

    private function ensureSchema()
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        try {
            $existingCols = [];
            $cRes = $this->db->query("SHOW COLUMNS FROM `approval_requests`");
            if ($cRes) {
                while ($r = $cRes->fetch_assoc()) {
                    $existingCols[$r['Field']] = true;
                }
            }
            if (!empty($existingCols)) {
                $requiredCols = [
                    'meta_data'            => "LONGTEXT NULL DEFAULT NULL",
                    'request_amount'       => "DECIMAL(15,2) NOT NULL DEFAULT 0.00",
                    'attachment'           => "VARCHAR(255) NULL DEFAULT NULL",
                    'approved_amount'      => "DECIMAL(15,2) NULL DEFAULT NULL",
                    'approved_by'          => "INT NULL DEFAULT NULL",
                    'approved_at'          => "DATETIME NULL DEFAULT NULL",
                    'rejection_reason'     => "TEXT NULL DEFAULT NULL",
                    'accounts_head_status' => "ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'",
                    'accounts_head_by'     => "INT UNSIGNED NULL DEFAULT NULL",
                    'accounts_head_at'     => "TIMESTAMP NULL DEFAULT NULL",
                    'accounts_head_note'   => "TEXT NULL DEFAULT NULL",
                    'op_head_status'       => "VARCHAR(20) NOT NULL DEFAULT 'pending'",
                    'op_head_by'           => "INT UNSIGNED NULL DEFAULT NULL",
                    'op_head_at'           => "DATETIME NULL DEFAULT NULL",
                    'op_head_note'         => "TEXT NULL DEFAULT NULL",
                ];
                foreach ($requiredCols as $colName => $colDef) {
                    if (!isset($existingCols[$colName])) {
                        @$this->db->query("ALTER TABLE `approval_requests` ADD COLUMN `{$colName}` {$colDef}");
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently ignore if restricted
        }
    }

    public function create($data)
    {
        $department = $data['department'] ?? 'General';
        $request_type = $data['request_type'] ?? '';
        $title = $data['title'] ?? '';
        $source_table = $data['source_table'] ?? '';
        $source_id = (int)($data['source_id'] ?? 0);
        $requested_days = (int)($data['requested_days'] ?? 0);
        $requested_by = !empty($data['requested_by']) ? (int)$data['requested_by'] : null;
        $approve_role = $data['approve_role'] ?? 'management';
        $meta_data = $data['meta_data'] ?? null;
        $request_amount = (float)($data['request_amount'] ?? 0.00);
        $attachment = !empty($data['attachment']) ? $data['attachment'] : null;
        $status = $data['status'] ?? 'pending';
        $approved_amount = isset($data['approved_amount']) ? (float)$data['approved_amount'] : null;
        $approved_by = !empty($data['approved_by']) ? (int)$data['approved_by'] : null;
        $approved_at = $data['approved_at'] ?? null;

        $sql = "INSERT INTO {$this->table} 
        (department, request_type, title, source_table, source_id, requested_days, requested_by, approve_role, meta_data, request_amount, attachment, status, approved_amount, approved_by, approved_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new \Exception("ApprovalRequest prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "ssssiiissdssdis",
            $department,
            $request_type,
            $title,
            $source_table,
            $source_id,
            $requested_days,
            $requested_by,
            $approve_role,
            $meta_data,
            $request_amount,
            $attachment,
            $status,
            $approved_amount,
            $approved_by,
            $approved_at
        );

        $stmt->execute();

        if ($stmt->error) {
            $err = $stmt->error;
            $stmt->close();
            throw new \Exception("ApprovalRequest execute failed: " . $err);
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }



    private function parseMetaData(?string $meta)
    {
        return $meta ? json_decode($meta, true) : [];
    }
    private function getLeadById(int $id)
    {
        return $this->db
            ->query("SELECT id, full_name, phone FROM leads WHERE id = {$id}")
            ->fetch_assoc();
    }

    private function getFlatById(int $id)
    {
        $sql = "
        SELECT 
            f.id,
            f.flat_name,
            f.sqft,
            p.name AS project_name,
            l.name as location_name
        FROM flats f
        LEFT JOIN projects p ON p.id = f.project_id
        Left JOIN locations l ON l.id = p.location_id
        WHERE f.id = {$id}
        LIMIT 1
    ";

        return $this->db->query($sql)->fetch_assoc();
    }




    private function getEmployeeById(int $id)
    {
        $sql = "
        SELECT 
            e.id,
            e.full_name,
            e.phone,
            d.name AS designation
        FROM employees e
        LEFT JOIN designations d ON d.id = e.designation_id
        WHERE e.id = {$id}
        LIMIT 1
    ";

        return $this->db->query($sql)->fetch_assoc();
    }



    private function getSalaryDetails(int $employeeId, ?string $month = null)
    {
        if ($month) {
            $mysqlMonth = date('Y-m-d', strtotime($month));
            $stmt = $this->db->prepare("SELECT * FROM payslips WHERE employee_id = ? AND payslip_month = ?");
            $stmt->bind_param("is", $employeeId, $mysqlMonth);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM payslips WHERE employee_id = ?");
            $stmt->bind_param("i", $employeeId);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    private function getLeaveDetails(int $leaveId)
    {
        return $this->db
            ->query("
            SELECT el.*, lt.name AS leave_type
            FROM employee_leaves el
            LEFT JOIN leave_types lt ON lt.id = el.leave_type_id
            WHERE el.id = {$leaveId}
        ")
            ->fetch_assoc();
    }

    private function getAccountDetails(string $sourceTable, int $sourceId)
    {
        return $this->db
            ->query("SELECT * FROM {$sourceTable} WHERE id = {$sourceId}")
            ->fetch_assoc();
    }
    private function getUserById(int $id)
    {
        $stmt = $this->db->prepare("
        SELECT 
            users.id, 
            users.full_name AS user_name, 
            users.email, 
            d.name AS designation
        FROM users
        LEFT JOIN designations AS d ON users.designation_id = d.id
        WHERE users.id = ?
    ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    private function resolveApprovalDetails(array $approval)
    {
        $meta = $this->parseMetaData($approval['meta_data']);
        $details = [];

        /* =====================
       SALES → LEAD DISCOUNT
       ===================== */
        if ($approval['request_type'] === 'discount') {

            if (!empty($meta['lead_id'])) {
                $details['lead'] = $this->getLeadById((int) $meta['lead_id']);
            }

            if (!empty($meta['flat_id'])) {
                $details['flat'] = $this->getFlatById((int) $meta['flat_id']);
            }

            // Universal requested date fallback
            $details['requested_on'] = $details['requested_on']
                ?? $approval['created_at'];

            // Attachment resolution
            $attachment = !empty($approval['attachment']) ? $approval['attachment'] : null;
            if (!$attachment && !empty($approval['source_id']) && $approval['source_table'] === 'lead_bookings') {
                $lbRes = $this->db->query("SELECT past_approval_attachment FROM lead_bookings WHERE id = " . (int)$approval['source_id'] . " LIMIT 1");
                if ($lbRes && $lbRow = $lbRes->fetch_assoc()) {
                    $attachment = $lbRow['past_approval_attachment'];
                }
            }
            if ($attachment) {
                $details['attachment'] = $attachment;
            }
        }

        /* =====================
       HR → SALARY
       ===================== */
        if ($approval['request_type'] === 'salary') {

            if (!empty($meta['employee_id'])) {
                $details['employee'] = $this->getEmployeeById((int) $meta['employee_id']);
                $details['salary'] = $this->getSalaryDetails(
                    (int) $meta['employee_id'],
                    $meta['month'] ?? null
                );
            }
        }

        /* =====================
       HR → LEAVE
       ===================== */
        if ($approval['request_type'] === 'leave') {

            if (!empty($meta['user_id'])) {
                $details['employee'] = $this->getUserById((int) $meta['user_id']);
            }

            if (!empty($approval['source_id'])) {
                $leave = $this->getLeaveDetails((int) $approval['source_id']);

                if ($leave) {
                    $details['leave'] = $leave;
                    $details['requested_on'] = $leave['created_at'];
                }
            }
        }

        // Manpower request 

        // if($approval['request_type'] === 'manpower_requisition'){
        //     if(){

        //     }
        // }

        /* =====================
       ACCOUNTS → CASH IN / OUT
       ===================== */
        if ($approval['department'] === 'Accounts') {

            $details['account'] = $this->getAccountDetails(
                $approval['source_table'],
                (int) $approval['source_id']
            );

            if ($approval['request_type'] === 'Cash In Handover' && $approval['source_table'] === 'lead_payments') {
                $handoverToId = $details['account']['handover_to'] ?? null;
                if ($handoverToId) {
                    $employee = $this->getUserById((int) $handoverToId);
                    if ($employee) {
                        $meta['handover_to'] = $employee['user_name'];
                    }
                }
            }

            $details['meta'] = $meta; // purpose, payment_method etc
        }

        /* =====================
       GENERAL → MANPOWER REQUISITION
       ===================== */
        if ($approval['request_type'] === 'manpower_requisition') {
            $details['requisition'] = $this->db
                ->query("SELECT mr.*, d.name as department_name, des.name AS designation_name, p.name AS project_name
                        FROM manpower_requisitions mr
                        LEFT JOIN departments d ON mr.department_id = d.id
                        LEFT JOIN designations des ON mr.designation_id = des.id
                        LEFT JOIN projects p ON mr.project_id = p.id
                        WHERE mr.id = " . (int) $approval['source_id'])
                ->fetch_assoc();
        }

        /* =====================
       OFFICE PROCUREMENT → PROJECT EXTRA ASSETS
       ===================== */
        if (($approval['source_table'] ?? '') === 'project_extra_assets') {
            $peaRes = $this->db->query("
                SELECT pea.*, 
                       p.name AS project_name,
                       d.name AS department_name,
                       cat.name AS category_name,
                       u_req.full_name AS requested_by_name,
                       u_dh.full_name AS dept_head_by_name,
                       u_rb.full_name AS reporting_boss_name,
                       u_op.full_name AS operations_by_name,
                       u_ph.full_name AS purchase_head_by_name,
                       u_qc.full_name AS qc_audit_by_name,
                       u_mg.full_name AS mgmt_by_name
                FROM project_extra_assets pea
                LEFT JOIN projects p ON p.id = pea.project_id
                LEFT JOIN departments d ON d.id = pea.department_id
                LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
                LEFT JOIN users u_req ON u_req.id = pea.requested_by
                LEFT JOIN users u_dh ON u_dh.id = pea.dept_head_by
                LEFT JOIN users u_rb ON u_rb.id = pea.reporting_user_id
                LEFT JOIN users u_op ON u_op.id = pea.operations_by
                LEFT JOIN users u_ph ON u_ph.id = pea.purchase_head_by
                LEFT JOIN users u_qc ON u_qc.id = pea.qc_audit_by
                LEFT JOIN users u_mg ON u_mg.id = pea.mgmt_by
                WHERE pea.id = " . (int) $approval['source_id'] . "
                LIMIT 1
            ");
            if ($peaRes && $details['procurement'] = $peaRes->fetch_assoc()) {
                $details['meta'] = [
                    'item' => $details['procurement']['item_name'],
                    'quantity' => $details['procurement']['quantity'],
                    'unit_price' => number_format((float)$details['procurement']['unit_price'], 2),
                    'total_price' => number_format((float)$details['procurement']['total_price'], 2),
                    'location' => $details['procurement']['asset_location'],
                    'justification' => $details['procurement']['justification'],
                    'dept_head' => ucfirst($details['procurement']['dept_head_status'] ?? 'not_required'),
                    'operations_status' => ucfirst($details['procurement']['operations_status']),
                    'purchase_head' => ucfirst($details['procurement']['purchase_head_status']),
                    'qc_audit' => ucfirst($details['procurement']['qc_audit_status']),
                    'management_status' => ucfirst($details['procurement']['mgmt_status']),
                ];
            }
        }

        /* =====================
       CONSTRUCTION → LABOR REQUISITIONS
       ===================== */
        if (($approval['source_table'] ?? '') === 'con_labor_requisitions') {
            $lrRes = $this->db->query("
                SELECT lr.*, p.name AS project_name, u.full_name AS requested_by_name
                FROM con_labor_requisitions lr
                LEFT JOIN projects p ON p.id = lr.project_id
                LEFT JOIN users u ON u.id = lr.requested_by
                WHERE lr.id = " . (int) $approval['source_id'] . "
                LIMIT 1
            ");
            if ($lrRes && $details['labor_req'] = $lrRes->fetch_assoc()) {
                $details['meta'] = [
                    'project'     => $details['labor_req']['project_name'],
                    'labor_count' => $details['labor_req']['labor_count'] . ' ' . $details['labor_req']['labor_type'],
                    'rate'        => '৳' . number_format((float)$details['labor_req']['per_labor_rate'], 2),
                    'days'        => $details['labor_req']['working_days'] . ' Days',
                    'purpose'     => $details['labor_req']['purpose'],
                ];
            }
        }

        // Fetch requester info
        if (!empty($approval['requested_by'])) {
            $details['requester'] = $this->getUserById((int) $approval['requested_by']);
        }


        return $details;
    }

    public function getPendingForRole(array $roles, $limit = 5)
    {
        $this->syncMissingFundRequisitions();
        $this->syncMissingOfficeProcurements();
        $this->syncMissingLaborRequisitions();
        $this->syncMissingVendorPaymentRequests();
        $this->syncMissingContractorBills();
        $this->syncMissingLandFundRequisitions();
        $this->syncMissingClientRefundRequests();
        $this->syncMissingPayslips();

        $roles = array_map('strtolower', $roles);

        if (empty($roles)) {
            return [];
        }

        $conditions = [];
        $params = [];
        $types = '';

        foreach ($roles as $role) {
            $conditions[] = "FIND_IN_SET(?, LOWER(approve_role))";
            $params[] = $role;
            $types .= 's';
        }

        $limit = (int) $limit;

        $sql = "SELECT *
            FROM {$this->table}
            WHERE status='pending'
            AND (" . implode(' OR ', $conditions) . ")
            ORDER BY created_at DESC
            LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        foreach ($rows as &$row) {
            $row['context'] = $this->resolveApprovalDetails($row);
        }

        return $rows;
    }

    /**
     * Approve the Accounts Head stage for an approval request (Role 30)
     */
    public function approveAccountsHead($id, $userId = null, $note = null, $coaId = null)
    {
        $id = (int)$id;
        $userId = $userId ? (int)$userId : (int)($_SESSION['user']['id'] ?? 0);
        $note = $note !== null ? trim($note) : 'Approved by Accounts Head';

        $req = $this->find($id);
        if (!$req) {
            return false;
        }

        // Save coa_id directly to the source table
        if ($coaId && !empty($req['source_table']) && !empty($req['source_id'])) {
            $srcTable = preg_replace('/[^a-zA-Z0-9_]/', '', $req['source_table']);
            $srcId = (int)$req['source_id'];
            $cId = (int)$coaId;
            // Check if column exists to avoid errors on tables that don't have it
            $colCheck = $this->db->query("SHOW COLUMNS FROM `{$srcTable}` LIKE 'account_id'");
            if ($colCheck && $colCheck->num_rows > 0) {
                $this->db->query("UPDATE `{$srcTable}` SET account_id = {$cId} WHERE id = {$srcId}");
            }
        }

        $reqAmount = (float)($req['request_amount'] ?? 0);
        $isOver10k = ($reqAmount > 10000.01);
        $isPayslipReq = (($req['source_table'] ?? '') === 'payslips');
        $isAlreadyPaid = in_array(strtolower($req['status'] ?? ''), ['paid', 'partially_paid']);
        $isAlreadyMgmtApproved = (strtolower($req['status'] ?? '') === 'approved' && !empty($req['approved_by']));

        if (($isOver10k || $isPayslipReq) && !$isAlreadyPaid && !$isAlreadyMgmtApproved) {
            // For > 10,000 or Payslips (when Management hasn't approved yet): Accounts Head verification complete (Step 2).
            // ALWAYS forward to Management (Role 1/3) for final executive sign-off!
            $nextRole = 'management';
            $nextStatus = 'pending';
        } else {
            // For <= 10,000 (non-payslip), or when Management has already approved, or historical already paid: fully approved and ready for Accounts Payout!
            $nextRole = ($req['status'] === 'partially_approved' && ($req['source_table'] ?? '') === 'fund_requisitions') ? ($req['approve_role'] ?? 'accounts') : 'accounts';
            $nextStatus = 'approved';
        }

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET accounts_head_status = 'approved',
                accounts_head_by = ?,
                accounts_head_at = NOW(),
                accounts_head_note = ?,
                approve_role = ?,
                status = CASE WHEN status IN ('paid', 'partially_paid') THEN status ELSE ? END,
                approved_at = CASE WHEN ? = 'approved' AND approved_at IS NULL THEN NOW() WHEN ? = 'pending' THEN NULL ELSE approved_at END,
                approved_by = CASE WHEN ? = 'pending' THEN NULL ELSE approved_by END
            WHERE id = ?
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('issssssi', $userId, $note, $nextRole, $nextStatus, $nextStatus, $nextStatus, $nextStatus, $id);
        $res = $stmt->execute();
        $stmt->close();

        // Always sync payslips accounts_head_status column if source_table is payslips
        if ($isPayslipReq && !empty($req['source_id'])) {
            $psId = (int)$req['source_id'];
            $colAh = $this->db->query("SHOW COLUMNS FROM `payslips` LIKE 'accounts_head_status'");
            if ($colAh && $colAh->num_rows > 0) {
                $escNote = $this->db->real_escape_string($note);
                $this->db->query("UPDATE `payslips` SET accounts_head_status = 'approved', accounts_head_by = {$userId}, accounts_head_at = NOW(), accounts_head_note = '{$escNote}' WHERE id = {$psId}");
            }
        }

        // If <= 10,000 (non-payslip), or Management already approved, or historical already paid: sync source table to approved so payout can proceed
        if ((!$isOver10k && !$isPayslipReq) || $isAlreadyPaid || $isAlreadyMgmtApproved) {
            $sourceTable = $req['source_table'] ?? '';
            $sourceId = (int)($req['source_id'] ?? 0);
            if ($sourceTable && $sourceId) {
                if ($sourceTable === 'project_incentive_claims') {
                    $this->db->query("UPDATE project_incentive_claims SET claim_status = 'approved', approved_by = {$userId}, approved_at = NOW() WHERE id = {$sourceId}");
                } else {
                    $actualApproverId = !empty($req['approved_by']) ? (int)$req['approved_by'] : (!empty($req['op_head_by']) ? (int)$req['op_head_by'] : $userId);
                    $this->syncSourceTableStatusDirect($sourceTable, $sourceId, 'approved', $actualApproverId, $reqAmount, $note);
                }
            }
        }

        return $res;
    }

    /**
     * Reject the Accounts Head stage for an approval request
     */
    public function rejectAccountsHead($id, $userId = null, $note = null)
    {
        $id = (int)$id;
        $userId = $userId ? (int)$userId : (int)($_SESSION['user']['id'] ?? 0);
        $note = $note !== null ? trim($note) : 'Rejected by Accounts Head';

        $req = $this->find($id);
        if (!$req) {
            return false;
        }

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET accounts_head_status = 'rejected',
                status = 'rejected',
                approve_role = 'rejected',
                accounts_head_by = ?,
                accounts_head_at = NOW(),
                accounts_head_note = ?
            WHERE id = ?
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('isi', $userId, $note, $id);
        $res = $stmt->execute();
        $stmt->close();

        // Also update source table status if applicable
        $sourceTable = $req['source_table'] ?? '';
        $sourceId = (int)($req['source_id'] ?? 0);
        if ($sourceTable && $sourceId) {
            if ($sourceTable === 'project_incentive_claims') {
                $this->db->query("UPDATE project_incentive_claims SET claim_status = 'rejected' WHERE id = {$sourceId}");
            } else {
                $colCheck = $this->db->query("SHOW COLUMNS FROM `{$sourceTable}` LIKE 'status'");
                if ($colCheck && $colCheck->num_rows > 0) {
                    $this->db->query("UPDATE `{$sourceTable}` SET status = 'rejected' WHERE id = {$sourceId}");
                }
            }
        }

        return $res;
    }

    /**
     * Approve the Admin & Operation Head stage (Role 20 / 32)
     * For requests > 10,000, this is the MANDATORY prerequisite before Management (Role 3 / 1) can approve.
     * For requests <= 10,000, either Role 20 OR Role 3 can approve directly.
     */
    public function approveOperationHead($id, $userId = null, $note = null)
    {
        $id = (int)$id;
        $userId = $userId ? (int)$userId : (int)($_SESSION['user']['id'] ?? 0);
        $note = $note !== null ? trim($note) : 'Approved by Admin & Operation Head';

        $req = $this->find($id);
        if (!$req) {
            return false;
        }

        $reqAmount = (float)($req['request_amount'] ?? 0);
        $isOver1Lac = ($reqAmount > 10000.01);

        // In both > 10,000 and <= 10,000 flows: Role 20 approves and forwards to Accounts Head (Role 30)!
        // For > 10k: Accounts Head approves (Step 2) BEFORE Management (Step 3).
        // For <= 10k: Accounts Head approves (Step 2) and authorizes payment directly.
        $nextRole = ($req['source_table'] === 'lead_bookings') ? 'completed' : 'accounts_head';
        $nextAh = ($req['source_table'] === 'lead_bookings') ? 'approved' : 'pending';

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET op_head_status = 'approved',
                op_head_by = ?,
                op_head_at = NOW(),
                op_head_note = ?,
                approve_role = ?,
                accounts_head_status = CASE WHEN accounts_head_status = 'approved' THEN 'approved' ELSE ? END
            WHERE id = ?
        ");
        if (!$stmt) return false;
        $stmt->bind_param('isssi', $userId, $note, $nextRole, $nextAh, $id);
        $res = $stmt->execute();
        $stmt->close();

        if ($req['source_table'] === 'lead_bookings') {
            $this->db->query("UPDATE lead_bookings SET approval_status = 'approved' WHERE id = " . (int)$req['source_id']);
        }

        return $res;
    }

    /**
     * Reject the Operation Head stage
     */
    public function rejectOperationHead($id, $userId = null, $note = null)
    {
        $id = (int)$id;
        $userId = $userId ? (int)$userId : (int)($_SESSION['user']['id'] ?? 0);
        $note = $note !== null ? trim($note) : 'Rejected by Admin & Operation Head';

        $req = $this->find($id);
        if (!$req) return false;

        $stmt = $this->db->prepare("
            UPDATE {$this->table}
            SET op_head_status = 'rejected',
                status = 'rejected',
                approve_role = 'rejected',
                op_head_by = ?,
                op_head_at = NOW(),
                op_head_note = ?,
                approval_note = ?
            WHERE id = ?
        ");
        if (!$stmt) return false;
        $stmt->bind_param('issi', $userId, $note, $note, $id);
        $res = $stmt->execute();
        $stmt->close();

        $sourceTable = $req['source_table'];
        $sourceId = (int)$req['source_id'];
        if ($sourceTable === 'lead_bookings') {
            $this->db->query("UPDATE lead_bookings SET approval_status = 'rejected' WHERE id = {$sourceId}");
        } elseif ($sourceTable && $sourceId) {
            $this->syncSourceTableStatusDirect($sourceTable, $sourceId, 'rejected', $userId, null, $note);
        }

        return $res;
    }

    /**
     * Helper to directly sync source table when approved by Role 20 or Management
     */
    public function syncSourceTableStatusDirect($sourceTable, $sourceId, $status, $userId, $amount = null, $note = '')
    {
        $sourceId = (int)$sourceId;
        $userId = (int)$userId;
        $note = $this->db->real_escape_string($note);

        if ($status === 'rejected') {
            if ($sourceTable === 'vendor_payment_requests') {
                $this->db->query("UPDATE vendor_payment_requests SET status = 'Rejected', approved_by = {$userId}, management_note = '{$note}', updated_at = NOW() WHERE id = {$sourceId}");
                $this->db->query("UPDATE vendor_penalties SET payment_request_id = NULL WHERE payment_request_id = {$sourceId}");
            } elseif ($sourceTable === 'contractor_bill_requests') {
                $this->db->query("UPDATE contractor_bill_requests SET status = 'rejected', approved_by = {$userId}, management_note = '{$note}', rejection_reason = '{$note}', updated_at = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'fund_requisitions') {
                $this->db->query("UPDATE fund_requisitions SET status = 'rejected', approved_by = {$userId}, approval_note = '{$note}', updated_at = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'project_other_costs') {
                $this->db->query("UPDATE project_other_costs SET status = 'rejected', management_apporved_by_id = {$userId}, management_apporved_note = '{$note}', management_apporved_at = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'cashout_requests') {
                $this->db->query("UPDATE cashout_requests SET status = 'rejected', approved_by = {$userId}, approved_at = NOW(), approval_note = '{$note}' WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'land_fund_requisitions') {
                $this->db->query("UPDATE land_fund_requisitions SET mgmt_status = 'Rejected', mgmt_by = {$userId}, mgmt_approved_at = NOW(), mgmt_note = '{$note}' WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'project_extra_assets') {
                $this->db->query("UPDATE project_extra_assets SET overall_status = 'rejected', mgmt_status = 'rejected', mgmt_by = {$userId}, mgmt_at = NOW(), mgmt_note = '{$note}' WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'con_labor_requisitions') {
                $this->db->query("UPDATE con_labor_requisitions SET status = 'rejected', management_approved_by = {$userId}, management_approved_at = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'expense_adjustments') {
                $this->db->query("UPDATE expense_adjustments SET status = 'rejected', approved_by = {$userId}, approved_at = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'advance_salaries') {
                $this->db->query("UPDATE advance_salaries SET status = 'rejected', approved_date = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'payslips') {
                $this->db->query("UPDATE payslips SET mgmt_status = 'rejected', status = 'rejected' WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'project_incentive_claims') {
                $this->db->query("UPDATE project_incentive_claims SET claim_status = 'rejected', approved_by = {$userId}, approved_at = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'client_refund_requests') {
                $this->db->query("UPDATE client_refund_requests SET status = 'rejected', approved_by = {$userId}, approved_at = NOW() WHERE id = {$sourceId}");
            } elseif ($sourceTable === 'lead_bookings') {
                $this->db->query("UPDATE lead_bookings SET approval_status = 'rejected' WHERE id = {$sourceId}");
            }
            return;
        }

        if ($sourceTable === 'cashout_requests') {
            $this->db->query("UPDATE cashout_requests SET status = 'approved', approved_by = {$userId}, approved_at = NOW(), approval_note = '{$note}' WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'fund_requisitions') {
            require_once __DIR__ . '/FundRequisitionApprovalLog.php';
            $frPrev = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$sourceId} LIMIT 1")->fetch_assoc();
            if ($frPrev) {
                try { \FundRequisitionApprovalLog::backfillIfMissing($this->db, $frPrev); } catch (\Throwable $t) {}
            }
            $this->db->query("UPDATE fund_requisitions SET status = 'approved', approved_by = {$userId}, approved_at = NOW(), approved_amount = amount, approval_note = '{$note}' WHERE id = {$sourceId}");
            if ($frPrev) {
                $frAmt = (float)($frPrev['amount'] ?? 0);
                $frPosted = max(0, $frAmt - (float)($frPrev['approved_amount'] ?? 0));
                if ($frPosted > 0) {
                    try { \FundRequisitionApprovalLog::log($this->db, $sourceId, 'approved', $frPosted, $frAmt, $userId, stripslashes($note)); } catch (\Throwable $t) {}
                }
            }
        } elseif ($sourceTable === 'land_fund_requisitions') {
            $this->db->query("UPDATE land_fund_requisitions SET mgmt_status = 'Approved', mgmt_by = {$userId}, mgmt_approved_at = NOW(), mgmt_note = '{$note}' WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'project_extra_assets') {
            $this->db->query("UPDATE project_extra_assets SET mgmt_status = 'approved', mgmt_by = {$userId}, mgmt_at = NOW(), mgmt_note = '{$note}' WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'vendor_payment_requests') {
            $amtSql = ($amount !== null && (float)$amount > 0) ? (float)$amount : "COALESCE(qc_approved_amount, net_payable, invoice_amount, 0.00)";
            $this->db->query("UPDATE vendor_payment_requests SET status = 'Approved', approved_by = COALESCE(approved_by, {$userId}), approved_amount = {$amtSql}, due_amount = {$amtSql}, updated_at = NOW(), management_note = CASE WHEN management_note IS NULL OR management_note = '' THEN '{$note}' ELSE management_note END WHERE id = {$sourceId}");
            try {
                require_once dirname(__DIR__) . '/services/JournalIntegrationService.php';
                $journalService = new \Services\JournalIntegrationService($this->db);
                $journalService->generateVendorAccrual($sourceId, $userId);
            } catch (\Throwable $e) {
                error_log("Failed to auto-generate vendor accrual journal in syncSourceTableStatusDirect: " . $e->getMessage());
            }
        } elseif ($sourceTable === 'contractor_bill_requests') {
            $amtSql = ($amount !== null && (float)$amount > 0) ? (float)$amount : "COALESCE(approved_amount, bill_amount, 0.00)";
            $this->db->query("UPDATE contractor_bill_requests SET status = 'approved', approved_by = COALESCE(approved_by, {$userId}), approved_amount = {$amtSql}, due_amount = {$amtSql}, updated_at = NOW(), management_note = CASE WHEN management_note IS NULL OR management_note = '' THEN '{$note}' ELSE management_note END WHERE id = {$sourceId}");
            try {
                require_once dirname(__DIR__) . '/services/JournalIntegrationService.php';
                $journalService = new \Services\JournalIntegrationService($this->db);
                $journalService->generateContractorAccrual($sourceId, $userId);
            } catch (\Throwable $e) {
                error_log("Failed to auto-generate contractor accrual journal in syncSourceTableStatusDirect: " . $e->getMessage());
            }
        } elseif ($sourceTable === 'project_other_costs') {
            $this->db->query("UPDATE project_other_costs SET status = 'management_approved', management_apporved_by_id = COALESCE(management_apporved_by_id, {$userId}), management_apporved_at = COALESCE(management_apporved_at, NOW()), management_apporved_note = CASE WHEN management_apporved_note IS NULL OR management_apporved_note = '' THEN '{$note}' ELSE management_apporved_note END WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'con_labor_requisitions') {
            $this->db->query("UPDATE con_labor_requisitions SET status = 'management_approved', management_approved_by = COALESCE(management_approved_by, {$userId}), management_approved_at = COALESCE(management_approved_at, NOW()), approved_amount = total_amount WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'expense_adjustments') {
            $this->db->query("UPDATE expense_adjustments SET status = 'approved', approved_by = {$userId}, approved_at = NOW() WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'advance_salaries') {
            $this->db->query("UPDATE advance_salaries SET status = 'approved', approved_date = NOW() WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'payslips') {
            $this->db->query("UPDATE payslips SET mgmt_status = 'approved', status = 'approved' WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'project_incentive_claims') {
            $this->db->query("UPDATE project_incentive_claims SET claim_status = 'approved', approved_by = {$userId}, approved_at = NOW() WHERE id = {$sourceId}");
        } elseif ($sourceTable === 'client_refund_requests') {
            $this->db->query("UPDATE client_refund_requests SET status = 'approved', approved_by = {$userId}, approved_at = NOW() WHERE id = {$sourceId}");
            
            // Check if cashout_requests exists for this refund; if not, auto-create it
            $crRes = $this->db->query("SELECT * FROM client_refund_requests WHERE id = {$sourceId} LIMIT 1");
            if ($crRes && ($crRow = $crRes->fetch_assoc())) {
                if (empty($crRow['cashout_request_id'])) {
                    $coAmt = (float)$crRow['refund_amount'];
                    $bId = (int)$crRow['booking_id'];
                    $bRes = $this->db->query("SELECT c.name as client_name, f.flat_name, p.name as project_name FROM lead_bookings lb LEFT JOIN clients c ON lb.client_id = c.id LEFT JOIN flats f ON lb.flat_id = f.id LEFT JOIN projects p ON lb.project_id = p.id WHERE lb.id = {$bId} LIMIT 1");
                    $bData = $bRes ? $bRes->fetch_assoc() : [];
                    $coTitle = 'Client Refund — ' . ($bData['client_name'] ?? 'Client') . ' | ' . ($bData['flat_name'] ?? '') . ' | ' . ($bData['project_name'] ?? '');
                    $coDesc = 'Auto-generated from Client Refund #' . $sourceId . '. Reason: ' . ($crRow['reason'] ?? 'N/A');
                    
                    $this->db->query("INSERT INTO cashout_requests (requested_by, purpose_paid_to, amount, approved_amount, due_amount, description, payment_method, project_id, status, created_at, updated_at) VALUES (" . (int)$crRow['requested_by'] . ", '" . $this->db->real_escape_string($coTitle) . "', {$coAmt}, {$coAmt}, {$coAmt}, '" . $this->db->real_escape_string($coDesc) . "', 'cash', " . (int)$crRow['project_id'] . ", 'approved', NOW(), NOW())");
                    $newCoId = $this->db->insert_id;
                    if ($newCoId) {
                        $this->db->query("UPDATE client_refund_requests SET cashout_request_id = {$newCoId} WHERE id = {$sourceId}");
                    }
                }
            }
        } elseif ($sourceTable === 'lead_bookings') {
            if ($status === 'approved') {
                $approvedAmt = ($amount !== null && (float)$amount > 0) ? (float)$amount : "discount_amount";
                $this->db->query("UPDATE lead_bookings SET approval_status = 'approved', approved_amount = {$approvedAmt} WHERE id = {$sourceId}");
            } elseif ($status === 'rejected') {
                $this->db->query("UPDATE lead_bookings SET approval_status = 'rejected' WHERE id = {$sourceId}");
            }
        }
    }

    /**
     * Get Accounts Head and Operation Head approval status and designated approver info for any module
     */
    public static function getAccountsHeadApprovalInfo($db, string $sourceTable, int $sourceId)
    {
        static $cachedAhUser = null;
        if ($cachedAhUser === null && $db) {
            $cachedAhUser = false;
            $ahUserRes = $db->query("
                SELECT u.id, u.full_name, u.email, COALESCE(e.employee_id, u.employee_id) AS emp_code, IFNULL(d.name, 'Accounts Head') AS designation
                FROM users u
                LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
                LEFT JOIN designations d ON d.id = COALESCE(e.designation_id, u.designation_id)
                WHERE (u.role_id = 30 OR u.secondary_role_id = 30) AND (e.deleted_at IS NULL OR e.id IS NULL)
                ORDER BY u.id ASC LIMIT 1
            ");
            if ($ahUserRes && ($ahRow = $ahUserRes->fetch_assoc())) {
                $cachedAhUser = $ahRow;
            }
        }

        $defAhName = $cachedAhUser['full_name'] ?? 'Accounts Head';
        $defAhCode = $cachedAhUser['emp_code'] ?? null;
        $defAhDesig = $cachedAhUser['designation'] ?? 'Accounts Head';
        $defAhEmail = $cachedAhUser['email'] ?? null;

        $defaultDesignated = [
            'has_request'               => false,
            'approval_request_id'       => null,
            'status'                    => 'pending',
            'approve_role'              => 'management',
            'request_amount'            => 0,
            'is_over_1lac'              => false,
            'op_head_status'            => 'pending',
            'is_op_head_approved'       => false,
            'is_pending_op_head'        => false,
            'is_op_head_bypassed'       => false,
            'is_mgmt_approved'          => false,
            'can_mgmt_approve'          => true,
            'op_head_name'              => null,
            'op_head_code'              => null,
            'op_head_designation'       => 'Admin & Operation Head',
            'op_head_at'                => null,
            'op_head_note'              => null,
            'accounts_head_status'      => 'pending',
            'is_pending_accounts_head'  => false,
            'is_accounts_head_approved' => false,
            'is_accounts_head_rejected' => false,
            'can_disburse_payment'      => false,
            'designated_name'           => $defAhName,
            'designated_emp_id'         => $defAhCode,
            'designated_designation'    => $defAhDesig,
            'designated_email'          => $defAhEmail,
            'approved_by_name'          => null,
            'approved_by_emp_id'        => null,
            'approved_by_designation'   => null,
            'approved_at'               => null,
            'approval_note'             => null,
        ];

        if (!$db || empty($sourceTable) || empty($sourceId)) {
            return $defaultDesignated;
        }

        $stmt = $db->prepare("
            SELECT ar.id, ar.status, ar.approve_role, ar.request_amount,
                   ar.approved_by, ar.approved_at, ar.approval_note,
                   u_mg.full_name AS mgmt_approver_name,
                   ar.op_head_status, ar.op_head_by, ar.op_head_at, ar.op_head_note,
                   u_op.full_name AS op_head_name,
                   e_op.employee_id AS op_head_code,
                   IFNULL(des_op.name, 'Admin & Operation Head') AS op_head_designation,
                   ar.accounts_head_status, ar.accounts_head_by, ar.accounts_head_at, ar.accounts_head_note,
                   u_ah.full_name AS accounts_head_name,
                   e_ah.employee_id AS accounts_head_code,
                   IFNULL(des_ah.name, 'AGM - Finance & Accounts') AS accounts_head_designation
            FROM approval_requests ar
            LEFT JOIN users u_mg ON ar.approved_by = u_mg.id
            LEFT JOIN users u_op ON ar.op_head_by = u_op.id
            LEFT JOIN employees e_op ON (e_op.user_id = u_op.id OR e_op.id = u_op.employee_id)
            LEFT JOIN designations des_op ON des_op.id = COALESCE(e_op.designation_id, u_op.designation_id)
            LEFT JOIN users u_ah ON ar.accounts_head_by = u_ah.id
            LEFT JOIN employees e_ah ON (e_ah.user_id = u_ah.id OR e_ah.id = u_ah.employee_id)
            LEFT JOIN designations des_ah ON des_ah.id = COALESCE(e_ah.designation_id, u_ah.designation_id)
            WHERE ar.source_table = ? AND ar.source_id = ?
            ORDER BY ar.id DESC LIMIT 1
        ");
        if (!$stmt) {
            return $defaultDesignated;
        }

        $stmt->bind_param('si', $sourceTable, $sourceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return $defaultDesignated;
        }

        $reqAmount = (float)($row['request_amount'] ?? 0);
        $isOver1Lac = ($reqAmount > 10000.01);
        $opStatus = strtolower($row['op_head_status'] ?? 'pending');
        $reqStatus = strtolower($row['status'] ?? '');

        // Fund Requisitions: source table is the source of truth for management decision.
        // Self-heal legacy rows where approval_requests.status stayed 'pending' after a partial management approval.
        if ($sourceTable === 'fund_requisitions') {
            $frRes = $db->query("SELECT status FROM fund_requisitions WHERE id = " . (int)$sourceId . " LIMIT 1");
            $frSt = ($frRes && ($frRow = $frRes->fetch_assoc())) ? strtolower((string)$frRow['status']) : '';
            if ($frSt === 'partially_approved' && $reqStatus !== 'partially_approved' && !in_array($reqStatus, ['rejected', 'cancelled'])) {
                $db->query("UPDATE approval_requests SET status = 'partially_approved' WHERE id = " . (int)$row['id']);
                $reqStatus = 'partially_approved';
                $row['status'] = 'partially_approved';
            }
        }

        $mgmtDoneStatuses = ['approved', 'partially_paid', 'paid', 'partially_approved'];
        $isMgmtApproved = in_array($reqStatus, $mgmtDoneStatuses);

        $isVprOrCbr = in_array($sourceTable, ['vendor_payment_requests', 'contractor_bill_requests'], true);
        $isReadyForOpHead = true;
        if ($sourceTable === 'vendor_payment_requests') {
            $vStRes = $db->query("SELECT status FROM vendor_payment_requests WHERE id = " . (int)$sourceId . " LIMIT 1");
            $vSt = ($vStRes && ($vStRow = $vStRes->fetch_assoc())) ? strtolower(trim($vStRow['status'])) : '';
            $isReadyForOpHead = in_array($vSt, ['qc approved', 'qc_approved', 'approved', 'partially_paid', 'paid', 'fully_paid'], true);
        } elseif ($sourceTable === 'contractor_bill_requests') {
            $cStRes = $db->query("SELECT status FROM contractor_bill_requests WHERE id = " . (int)$sourceId . " LIMIT 1");
            $cSt = ($cStRes && ($cStRow = $cStRes->fetch_assoc())) ? strtolower(trim($cStRow['status'])) : '';
            $isReadyForOpHead = in_array($cSt, ['qc_approved', 'approved', 'partially_paid', 'paid', 'fully_paid'], true);
        }

        $isOpHeadApproved = ($opStatus === 'approved') || in_array($reqStatus, ['approved', 'partially_paid', 'paid']);
        $isOpHeadBypassed = ($isOver1Lac || $isVprOrCbr) && !$isOpHeadApproved && $isMgmtApproved && $opStatus !== 'rejected';
        $isPendingOpHead = ($isOver1Lac || $isVprOrCbr) && $isReadyForOpHead && !$isOpHeadApproved && !$isOpHeadBypassed && !in_array($reqStatus, ['rejected', 'cancelled']);

        $ahStatus = strtolower($row['accounts_head_status'] ?? 'pending');
        $isAhApproved = ($ahStatus === 'approved');
        $isAhRejected = ($ahStatus === 'rejected');

        // NEW ORDER: Accounts Head stage is pending:
        // For > 10k or VPR/CBR: as soon as Op Head approves (BEFORE Management for > 10k, or as final step for <= 10k VPR/CBR)
        // For <= 10k (other modules): as soon as Op Head OR Management approves, OR approve_role is accounts_head
        if ($sourceTable === 'payslips') {
            $isPendingAh = !$isAhApproved && !in_array($reqStatus, ['rejected', 'cancelled']);
        } elseif ($isOver1Lac || $isVprOrCbr) {
            $isPendingAh = $isOpHeadApproved && !$isAhApproved && !in_array($reqStatus, ['rejected', 'cancelled']);
        } else {
            $isPendingAh = ($isOpHeadApproved || $isMgmtApproved || strtolower($row['approve_role'] ?? '') === 'accounts_head') && !$isAhApproved && !in_array($reqStatus, ['rejected', 'cancelled']);
        }

        // Management can approve:
        // Management (Role 1, 3) can always approve (with warning if earlier stage is pending),
        // while standard sequential flow requires Op Head + Accounts Head for > 10k.
        $canMgmtApprove = !$isOver1Lac || ($isOpHeadApproved && $isAhApproved) || $isOpHeadBypassed;
        $isMgmtLocked = $isOver1Lac && (!$isOpHeadApproved || !$isAhApproved) && !$isOpHeadBypassed;

        return [
            'has_request'               => true,
            'approval_request_id'       => (int)$row['id'],
            'status'                    => $row['status'],
            'approve_role'              => $row['approve_role'],
            'request_amount'            => $reqAmount,
            'is_over_1lac'              => $isOver1Lac,
            'is_ready_for_op_head'      => $isReadyForOpHead,
            'op_head_status'            => $opStatus,
            'is_op_head_approved'       => $isOpHeadApproved,
            'is_op_head_bypassed'       => $isOpHeadBypassed,
            'is_pending_op_head'        => $isPendingOpHead,
            'is_mgmt_approved'          => $isMgmtApproved,
            'can_mgmt_approve'          => $canMgmtApprove,
            'mgmt_approver_name'        => $row['mgmt_approver_name'] ?: ($isMgmtApproved ? 'Management' : null),
            'mgmt_approved_at'          => $row['approved_at'] ?: null,
            'mgmt_approval_note'        => $row['approval_note'] ?: null,
            'op_head_name'              => $row['op_head_name'] ?: 'Admin & Operation Head',
            'op_head_code'              => $row['op_head_code'] ?: null,
            'op_head_designation'       => $row['op_head_designation'] ?: 'Admin & Operation Head',
            'op_head_at'                => $row['op_head_at'],
            'op_head_note'              => $row['op_head_note'],
            'accounts_head_status'      => $ahStatus,
            'is_pending_accounts_head'  => $isPendingAh,
            'is_accounts_head_approved' => $isAhApproved,
            'is_accounts_head_rejected' => $isAhRejected,
            'can_disburse_payment'      => $isOver1Lac ? ($isAhApproved && $isMgmtApproved) : $isAhApproved,
            'designated_name'           => $defAhName,
            'designated_emp_id'         => $defAhCode,
            'designated_designation'    => $defAhDesig,
            'designated_email'          => $defAhEmail,
            'approved_by_name'          => $row['accounts_head_name'] ?: ($isAhApproved ? $defAhName : null),
            'approved_by_emp_id'        => $row['accounts_head_code'] ?: ($isAhApproved ? $defAhCode : null),
            'approved_by_designation'   => $row['accounts_head_designation'] ?: ($isAhApproved ? $defAhDesig : 'Accounts Head'),
            'approved_at'               => $row['accounts_head_at'],
            'approval_note'             => $row['accounts_head_note'],
        ];
    }

    public function updateStatus($id, $status, $approvedAmount = null, $approved_days = null, $approvalNote = null)
    {
        $approvedBy = $_SESSION['user']['id'] ?? null;

        $request = $this->find($id);
        if (!$request) {
            return false;
        }

        $sourceTable = $request['source_table'];
        $sourceId = (int) $request['source_id'];

        if ($sourceTable === 'advance_salaries' && strtolower($request['approve_role'] ?? '') === 'hr_head' && $status === 'approved') {
            // Update source table
            $this->db->query("UPDATE advance_salaries SET status = 'pending_accounts', approved_date = NOW() WHERE id = {$sourceId}");

            // Forward to Accounts!
            $sqlForward = "UPDATE {$this->table}
                SET 
                    department = 'Accounts',
                    request_type = 'advance_salary_payment',
                    approve_role = 'management',
                    status = 'pending',
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $hrNote = "Approved by HR Head. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $hrNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'payslips' && strtolower($request['approve_role'] ?? '') === 'hr' && $status === 'approved') {
            // Update payslip hr_status
            $this->db->query("UPDATE payslips SET hr_status = 'approved' WHERE id = {$sourceId}");

            // Forward to management for final approval
            $sqlForward = "UPDATE {$this->table}
                SET 
                    approve_role = 'management',
                    status = 'pending',
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $hrNote = "Approved by HR. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $hrNote, $id);
            return $stmtForward->execute();
        }

        $userRoleSession = (int)($_SESSION['user']['role_id'] ?? 0);
        $isMgmtOverride = in_array($userRoleSession, [1, 3], true);

        if ($sourceTable === 'vendor_payment_requests' && in_array(strtolower($request['approve_role'] ?? ''), ['purchase_head', 'purchase', 'purchase team leader', 'purchase_lead'], true) && $status === 'approved' && !$isMgmtOverride) {
            $stmtPh = $this->db->prepare("UPDATE vendor_payment_requests SET purchase_head_approved_by_id = ?, purchase_head_approved_at = NOW(), purchase_head_note = ? WHERE id = ?");
            if ($stmtPh) {
                $stmtPh->bind_param('isi', $approvedBy, $approvalNote, $sourceId);
                $stmtPh->execute();
                $stmtPh->close();
            }
            $sqlForward = "UPDATE {$this->table}
                SET approve_role = 'qc',
                    status = 'pending',
                    approval_note = ?
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $phNote = "Approved by Head of Purchase. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $phNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'vendor_payment_requests' && strtolower($request['approve_role'] ?? '') === 'qc' && $status === 'approved' && !$isMgmtOverride) {
            // Update source table to QC Approved
            $qcApprovedAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0);
            $stmtVpr = $this->db->prepare("UPDATE vendor_payment_requests SET status = 'QC Approved', qc_verified = 1, qc_approved_by_id = ?, qc_approved_at = NOW(), qc_approved_amount = ?, qc_note = ? WHERE id = ?");
            if ($stmtVpr) {
                $stmtVpr->bind_param('idsi', $approvedBy, $qcApprovedAmt, $approvalNote, $sourceId);
                $stmtVpr->execute();
                $stmtVpr->close();
            }

            // Forward to Operation Head ('operations')
            $sqlForward = "UPDATE {$this->table}
                SET 
                    approve_role = 'operations',
                    status = 'pending',
                    request_amount = ?,
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $qcNote = "Audited & Approved by QC. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('dsi', $qcApprovedAmt, $qcNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'contractor_bill_requests' && strtolower($request['approve_role'] ?? '') === 'pm' && $status === 'approved' && !$isMgmtOverride) {
            $stmtCb = $this->db->prepare("UPDATE contractor_bill_requests SET status = 'verified', verified_by = ?, verified_at = NOW(), verification_note = ? WHERE id = ?");
            if ($stmtCb) {
                $stmtCb->bind_param('isi', $approvedBy, $approvalNote, $sourceId);
                $stmtCb->execute();
                $stmtCb->close();
            }

            // Forward to Head of Purchase ('purchase_head')
            $sqlForward = "UPDATE {$this->table}
                SET 
                    approve_role = 'purchase_head',
                    status = 'pending',
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $pmNote = "PM Approved. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $pmNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'contractor_bill_requests' && in_array(strtolower($request['approve_role'] ?? ''), ['purchase_head', 'purchase', 'purchase team leader', 'purchase_lead'], true) && $status === 'approved' && !$isMgmtOverride) {
            $stmtPh = $this->db->prepare("UPDATE contractor_bill_requests SET purchase_head_approved_by_id = ?, purchase_head_approved_at = NOW(), purchase_head_note = ? WHERE id = ?");
            if ($stmtPh) {
                $stmtPh->bind_param('isi', $approvedBy, $approvalNote, $sourceId);
                $stmtPh->execute();
                $stmtPh->close();
            }
            $sqlForward = "UPDATE {$this->table}
                SET approve_role = 'qc',
                    status = 'pending',
                    approval_note = ?
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $phNote = "Approved by Head of Purchase. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $phNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'contractor_bill_requests' && strtolower($request['approve_role'] ?? '') === 'qc' && $status === 'approved' && !$isMgmtOverride) {
            // Update source table to QC Approved
            $qcApprovedAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0);
            $stmtCb = $this->db->prepare("UPDATE contractor_bill_requests SET status = 'qc_approved', qc_approved_by_id = ?, qc_approved_at = NOW(), qc_note = ?, approved_amount = ?, due_amount = ? WHERE id = ?");
            if ($stmtCb) {
                $stmtCb->bind_param('isddi', $approvedBy, $approvalNote, $qcApprovedAmt, $qcApprovedAmt, $sourceId);
                $stmtCb->execute();
                $stmtCb->close();
            }

            // Forward to Operation Head ('operations')
            $sqlForward = "UPDATE {$this->table}
                SET 
                    approve_role = 'operations',
                    status = 'pending',
                    request_amount = ?,
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $qcNote = "Audited & Approved by QC. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('dsi', $qcApprovedAmt, $qcNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'expense_adjustments' && strtolower($request['approve_role'] ?? '') === 'qc' && $status === 'approved') {
            $qcApprovedAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0);
            $stmtEa = $this->db->prepare("UPDATE expense_adjustments SET status = 'qc_approved', qc_approved_by = ?, qc_approved_at = NOW(), qc_note = ?, approved_amount = ?, due_amount = ? WHERE id = ?");
            if ($stmtEa) {
                $stmtEa->bind_param('isddi', $approvedBy, $approvalNote, $qcApprovedAmt, $qcApprovedAmt, $sourceId);
                $stmtEa->execute();
                $stmtEa->close();
            }

            // Forward to management instead of marking as completed
            $sqlForward = "UPDATE {$this->table}
                SET 
                    approve_role = 'management',
                    status = 'pending',
                    request_amount = ?,
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $qcNote = "Audited & Approved by QC. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('dsi', $qcApprovedAmt, $qcNote, $id);
            return $stmtForward->execute();
        }

        // Intermediate stages for fund_requisitions (dept_head -> qc -> management)
        if ($sourceTable === 'fund_requisitions' && strpos(strtolower($request['approve_role'] ?? ''), 'dept_head') === 0 && $status === 'approved' && !$isMgmtOverride) {
            $stmtFr = $this->db->prepare("UPDATE fund_requisitions SET dept_head_status = 'approved', dept_head_approved_by = ?, dept_head_approval_note = ?, dept_head_approved_at = NOW(), status = 'pending_qc' WHERE id = ?");
            if ($stmtFr) {
                $stmtFr->bind_param('isi', $approvedBy, $approvalNote, $sourceId);
                $stmtFr->execute();
                $stmtFr->close();
            }
            $sqlForward = "UPDATE {$this->table} SET approve_role = 'qc', status = 'pending', approval_note = ? WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $dhNote = "Approved by Dept Head. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $dhNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'fund_requisitions' && strtolower($request['approve_role'] ?? '') === 'qc' && $status === 'approved' && !$isMgmtOverride) {
            $qcApprovedAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0);
            $stmtFr = $this->db->prepare("UPDATE fund_requisitions SET status = 'pending', qc_approved_by = ?, qc_approved_at = NOW(), qc_approval_note = ?, qc_approved_amount = ? WHERE id = ?");
            if ($stmtFr) {
                $stmtFr->bind_param('isdi', $approvedBy, $approvalNote, $qcApprovedAmt, $sourceId);
                $stmtFr->execute();
                $stmtFr->close();
            }
            $sqlForward = "UPDATE {$this->table} SET approve_role = 'management', status = 'pending', request_amount = ?, approved_amount = NULL, approved_by = NULL, approval_note = ?, approved_at = NULL WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $qcNote = "Audited & Approved by QC. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('dsi', $qcApprovedAmt, $qcNote, $id);
            return $stmtForward->execute();
        }

        // Intermediate stages for project_other_costs (dept_head -> qc -> management)
        if ($sourceTable === 'project_other_costs' && strpos(strtolower($request['approve_role'] ?? ''), 'dept_head') === 0 && $status === 'approved' && !$isMgmtOverride) {
            $stmtPoc = $this->db->prepare("UPDATE project_other_costs SET dept_head_status = 'approved', dept_head_approved_by = ?, dept_head_approval_note = ?, dept_head_approved_at = NOW() WHERE id = ?");
            if ($stmtPoc) {
                $stmtPoc->bind_param('isi', $approvedBy, $approvalNote, $sourceId);
                $stmtPoc->execute();
                $stmtPoc->close();
            }
            $sqlForward = "UPDATE {$this->table} SET approve_role = 'qc', status = 'pending', approval_note = ? WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $dhNote = "Approved by Dept Head. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $dhNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'project_other_costs' && strtolower($request['approve_role'] ?? '') === 'qc' && $status === 'approved' && !$isMgmtOverride) {
            $qcApprovedAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0);
            $stmtPoc = $this->db->prepare("UPDATE project_other_costs SET status = 'qc_approved', qc_audit_approved_id = ?, qc_audit_approved_at = NOW(), qc_audit_note = ?, approved_amount = ? WHERE id = ?");
            if ($stmtPoc) {
                $stmtPoc->bind_param('isdi', $approvedBy, $approvalNote, $qcApprovedAmt, $sourceId);
                $stmtPoc->execute();
                $stmtPoc->close();
            }
            $sqlForward = "UPDATE {$this->table} SET approve_role = 'management', status = 'pending', request_amount = ?, approved_amount = NULL, approved_by = NULL, approval_note = ?, approved_at = NULL WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $qcNote = "Audited & Approved by QC. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('dsi', $qcApprovedAmt, $qcNote, $id);
            return $stmtForward->execute();
        }

        // Intermediate stages for project_extra_assets (Office Procurement: dept_head -> it_head -> operations -> purchase_head -> qc -> management)
        if ($sourceTable === 'project_extra_assets' && $status === 'approved') {
            $peaRole = strtolower($request['approve_role'] ?? 'management');
            $peaStep = null;
            if (strpos($peaRole, 'dept_head') === 0) {
                $peaStep = 'dept_head';
            } elseif ($peaRole === 'it_head') {
                $peaStep = 'it_head';
            } elseif ($peaRole === 'operations') {
                $peaStep = 'operations';
            } elseif (in_array($peaRole, ['purchase_head', 'purchase'], true)) {
                $peaStep = 'purchase_head';
            } elseif ($peaRole === 'qc') {
                $peaStep = 'qc_audit';
            }
            if ($peaStep !== null) {
                $statusCol = $peaStep . '_status';
                $byCol = $peaStep . '_by';
                $atCol = $peaStep . '_at';
                $noteCol = $peaStep . '_note';
                $stmtPea = $this->db->prepare("UPDATE project_extra_assets SET {$statusCol} = 'approved', {$byCol} = ?, {$atCol} = NOW(), {$noteCol} = ?, overall_status = 'in_review' WHERE id = ?");
                if ($stmtPea) {
                    $stmtPea->bind_param('isi', $approvedBy, $approvalNote, $sourceId);
                    $stmtPea->execute();
                    $stmtPea->close();
                }
                $this->syncMissingOfficeProcurements();
                return true;
            }
        }

        if ($status === 'approved' && ($approvedAmount === null || (float)$approvedAmount <= 0)) {
            $approvedAmount = (!empty($request['approved_amount']) && (float)$request['approved_amount'] > 0)
                ? (float)$request['approved_amount']
                : (float)($request['request_amount'] ?? 0);
        }

        if ($status === 'approved') {
            // If Accounts Head has already approved (standard flow for > 10k), Management is the final sign-off: forward to 'accounts' for payout!
            // If Accounts Head has not approved yet (e.g. direct approval for <= 10k), forward to 'accounts_head'.
            $isAhAlreadyApproved = (strtolower($request['accounts_head_status'] ?? '') === 'approved');
            if ($sourceTable === 'lead_bookings') {
                $nextApproveRole = 'completed';
                $nextAhStatus = 'approved';
            } elseif ($isAhAlreadyApproved) {
                $nextApproveRole = 'accounts';
                $nextAhStatus = 'approved';
            } else {
                $nextApproveRole = 'accounts_head';
                $nextAhStatus = 'pending';
            }

            $sql = "UPDATE {$this->table}
                SET 
                    status = ?, 
                    op_head_status = 'approved',
                    approve_role = '{$nextApproveRole}',
                    accounts_head_status = '{$nextAhStatus}',
                    approved_amount = ?, 
                    approved_days = ?, 
                    approved_by = ?, 
                    approval_note = ?, 
                    approved_at = NOW()
                WHERE id = ?";
        } elseif ($status === 'rejected') {
            $sql = "UPDATE {$this->table}
                SET 
                    status = ?, 
                    approve_role = 'rejected',
                    accounts_head_status = 'rejected',
                    approved_amount = ?, 
                    approved_days = ?, 
                    approved_by = ?, 
                    approval_note = ?, 
                    approved_at = NOW()
                WHERE id = ?";
        } else {
            $sql = "UPDATE {$this->table}
                SET 
                    status = ?, 
                    approved_amount = ?, 
                    approved_days = ?, 
                    approved_by = ?, 
                    approval_note = ?, 
                    approved_at = NOW()
                WHERE id = ?";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("ApprovalRequest updateStatus prepare failed: " . $this->db->error . " | SQL: " . $sql);
            return false;
        }

        $stmt->bind_param(
            'sdiisi',
            $status,
            $approvedAmount,
            $approved_days,
            $approvedBy,
            $approvalNote,
            $id
        );
        $stmt->execute();

        if ($sourceTable === 'lead_bookings' && $status === 'approved') {
            $this->db->query("UPDATE {$this->table} SET op_head_status = 'approved', approve_role = 'completed' WHERE id = {$id}");
        }

        $request = $this->find($id);
        if (!$request || empty($request['source_table']) || empty($request['source_id'])) {
            return false;
        }

        $sourceTable = $request['source_table'];
        $sourceId = (int) $request['source_id'];

        if (!in_array($sourceTable, $this->allowedTables, true)) {
            throw new Exception("Invalid source table: {$sourceTable}");
        }

        $columns = [];
        $result = $this->db->query("SHOW COLUMNS FROM {$sourceTable}");
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }


        $sourceStatus = match ($status) {
            'partial', 'partially_approved' => 'partial',
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };

        // Table-specific adjustments to match ENUM definitions in database
        if ($sourceTable === 'vendor_payment_requests') {
            $sourceStatus = match ($status) {
                'partial', 'partially_approved' => 'Partially_Paid',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => 'Submitted',
            };
        } elseif ($sourceTable === 'contractor_bill_requests') {
            $sourceStatus = match ($status) {
                'partial', 'partially_approved' => 'partially_paid',
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'submitted',
            };
        } elseif ($sourceTable === 'operational_fund_requests') {
            $sourceStatus = match ($status) {
                'partial', 'partially_approved' => 'partially_paid',
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'pending',
            };
        } elseif ($sourceTable === 'lead_payments') {
            $sourceStatus = match ($status) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'pending',
            };
        } elseif ($sourceTable === 'advance_salaries') {
            $sourceStatus = match ($status) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'pending_hr',
            };
        } elseif ($sourceTable === 'purchase_requests') {
            $sourceStatus = match ($status) {
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => 'Submitted',
            };
        }

        if ($sourceTable === 'con_labor_requisitions') {
            $lrStatus = ($status === 'approved') ? 'management_approved' : 'rejected';
            $lrAppAmt = ($status === 'approved') ? ($approvedAmount !== null && $approvedAmount > 0 ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0)) : null;
            $stmtLr = $this->db->prepare(
                "UPDATE con_labor_requisitions
                 SET status = ?,
                     management_approved_by = ?,
                     management_approved_at = NOW(),
                     approved_amount = COALESCE(?, approved_amount, total_amount),
                     management_note = ?,
                     rejection_reason = CASE WHEN ? = 'rejected' THEN ? ELSE rejection_reason END,
                     updated_at = NOW()
                 WHERE id = ?"
            );
            if ($stmtLr) {
                $stmtLr->bind_param('sidsissi', $lrStatus, $approvedBy, $lrAppAmt, $approvalNote, $lrStatus, $approvalNote, $sourceId);
                $stmtLr->execute();
                $stmtLr->close();
            }
            return true;
        }

        if ($sourceTable === 'project_other_costs') {
            $pocStatus = ($status === 'approved') ? 'management_approved' : 'rejected';
            $pocAppAmt = ($status === 'approved') ? ($approvedAmount !== null && $approvedAmount > 0 ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0)) : null;
            $stmtPoc = $this->db->prepare(
                "UPDATE project_other_costs 
                 SET status = ?, 
                     management_apporved_by_id = ?, 
                     management_apporved_at = NOW(), 
                     approved_amount = COALESCE(?, approved_amount, request_amount), 
                     management_apporved_note = ?,
                     updated_at = NOW() 
                 WHERE id = ?"
            );
            if ($stmtPoc) {
                $stmtPoc->bind_param('sidsi', $pocStatus, $approvedBy, $pocAppAmt, $approvalNote, $sourceId);
                $stmtPoc->execute();
                $stmtPoc->close();
            }
            return true;
        }

        if ($sourceTable === 'land_fund_requisitions' || $sourceTable === 'con_land_requisitions') {
            $lfrStatus = ($status === 'approved') ? 'Approved' : 'Rejected';
            $stmtLfr = $this->db->prepare(
                "UPDATE land_fund_requisitions 
                 SET mgmt_status = ?, 
                     mgmt_by = ?, 
                     mgmt_approved_at = NOW(), 
                     mgmt_note = ?,
                     updated_at = NOW() 
                 WHERE id = ?"
            );
            if ($stmtLfr) {
                $stmtLfr->bind_param('sisi', $lfrStatus, $approvedBy, $approvalNote, $sourceId);
                $stmtLfr->execute();
                $stmtLfr->close();
            }

            if ($status === 'approved') {
                $this->db->query("UPDATE {$this->table} SET approve_role = 'accounts_head', accounts_head_status = 'pending', status = 'approved', approved_by = {$approvedBy}, approved_at = NOW(), approval_note = '" . $this->db->real_escape_string($approvalNote) . "' WHERE id = {$id}");
            } else {
                $this->db->query("UPDATE {$this->table} SET approve_role = 'rejected', accounts_head_status = 'rejected', status = 'rejected', approved_by = {$approvedBy}, approved_at = NOW(), approval_note = '" . $this->db->real_escape_string($approvalNote) . "' WHERE id = {$id}");
            }
            return true;
        }

        if ($sourceTable === 'expense_adjustments') {
            $eaRec = $this->db->query("SELECT * FROM expense_adjustments WHERE id = {$sourceId} LIMIT 1")->fetch_assoc();
            if ($eaRec) {
                if ($status === 'rejected') {
                    $stmtEa = $this->db->prepare("UPDATE expense_adjustments SET status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_note = ? WHERE id = ?");
                    if ($stmtEa) {
                        $stmtEa->bind_param('isi', $approvedBy, $approvalNote, $sourceId);
                        $stmtEa->execute();
                        $stmtEa->close();
                    }
                } elseif ($status === 'approved') {
                    $eaAppAmt = ($approvedAmount !== null && (float)$approvedAmount > 0) ? (float)$approvedAmount : (float)($request['request_amount'] ?? $eaRec['amount']);
                    $stmtEa = $this->db->prepare("UPDATE expense_adjustments SET status = 'approved', approved_by = ?, approved_at = NOW(), mgmt_note = ?, approved_amount = ?, due_amount = ? WHERE id = ?");
                    if ($stmtEa) {
                        $stmtEa->bind_param('isddi', $approvedBy, $approvalNote, $eaAppAmt, $eaAppAmt, $sourceId);
                        $stmtEa->execute();
                        $stmtEa->close();
                    }

                    // Forward to accounts head for approval before payout
                    $this->db->query("UPDATE {$this->table} SET approve_role = 'accounts_head', accounts_head_status = 'pending', status = 'approved', approved_amount = {$eaAppAmt} WHERE id = {$id}");
                }
            }
            return true;
        }

        if ($sourceTable === 'fund_requisitions') {
            $frData = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$sourceId} LIMIT 1")->fetch_assoc();
            $frStatus = ($status === 'approved') ? 'approved' : 'rejected';
            $frAppAmt = ($status === 'approved') 
                ? ($approvedAmount !== null && (float)$approvedAmount > 0 ? (float)$approvedAmount : null) 
                : null;
            
            $targetAmt = $frAppAmt ?? (!empty($frData['qc_approved_amount']) && (float)$frData['qc_approved_amount'] > 0 ? (float)$frData['qc_approved_amount'] : (float)($frData['amount'] ?? 0));
            if ($frStatus === 'approved' && !empty($frData['paid_at']) && (float)($frData['paid_amount'] ?? 0) >= $targetAmt) {
                $frStatus = 'paid';
            }
            
            require_once __DIR__ . '/FundRequisitionApprovalLog.php';
            if ($frData) {
                try { \FundRequisitionApprovalLog::backfillIfMissing($this->db, $frData); } catch (\Throwable $t) {}
            }

            $stmtFr = $this->db->prepare(
                "UPDATE fund_requisitions 
                 SET status = ?, 
                     approved_by = ?, 
                     approved_at = NOW(), 
                     approved_amount = COALESCE(?, NULLIF(qc_approved_amount, 0), amount), 
                     approval_note = ?,
                     updated_at = NOW() 
                 WHERE id = ?"
            );
            if ($stmtFr) {
                $stmtFr->bind_param('sidsi', $frStatus, $approvedBy, $frAppAmt, $approvalNote, $sourceId);
                $stmtFr->execute();
                $stmtFr->close();
            }

            // Record who approved how much
            try {
                if ($status === 'approved') {
                    $frAfter = $this->db->query("SELECT approved_amount FROM fund_requisitions WHERE id = {$sourceId} LIMIT 1")->fetch_assoc();
                    $frNewCum = (float)($frAfter['approved_amount'] ?? 0);
                    $frPosted = max(0, $frNewCum - (float)($frData['approved_amount'] ?? 0));
                    if ($frPosted > 0) {
                        \FundRequisitionApprovalLog::log($this->db, $sourceId, 'approved', $frPosted, $frNewCum, (int)$approvedBy, $approvalNote);
                    }
                } else {
                    \FundRequisitionApprovalLog::log($this->db, $sourceId, 'rejected', 0, (float)($frData['approved_amount'] ?? 0), (int)$approvedBy, $approvalNote);
                }
            } catch (\Throwable $t) {
                error_log("FR approval log error (central): " . $t->getMessage());
            }

            if ($status === 'approved') {
                try {
                    require_once dirname(__DIR__) . '/services/AutoPostingService.php';
                    $autoService = new \AutoPostingService($this->db);
                    $officeExpenseAccId = $autoService->getAccountIdByCode('5220');
                    $accountsPayableAccId = $autoService->getAccountIdByCode('211000');
                    
                    $frData = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$sourceId} LIMIT 1")->fetch_assoc();
                    if ($frData) {
                        $postedAmount = (float)($frData['approved_amount'] ?? 0);
                        if ($postedAmount > 0) {
                            $autoService->postAccrualEntry(
                                'fund_requisition',
                                $sourceId,
                                !empty($frData['account_id']) ? $frData['account_id'] : $officeExpenseAccId,
                                $accountsPayableAccId,
                                $postedAmount,
                                "Fund Requisition (FR-{$sourceId}): " . ($frData['purpose'] ?: 'Operational Expense'),
                                $frData['project_id'],
                                'employee',
                                $frData['requested_by'],
                                $approvedBy
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("AutoPosting Exception (Fund Requisition via Central Approval): " . $e->getMessage());
                }
            }
            return true;
        }

        if ($sourceTable === 'vendor_payment_requests') {
            $vprStatus = ($status === 'approved') ? 'Approved' : 'Rejected';
            $vprAppAmt = ($status === 'approved')
                ? ($approvedAmount !== null && (float)$approvedAmount > 0 ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0))
                : null;
            $escVprNote = $this->db->real_escape_string($approvalNote ?? 'Approved by Management');

            if ($status === 'approved') {
                // Auto-populate any bypassed earlier stages on vendor_payment_requests & approval_requests
                $this->db->query("
                    UPDATE vendor_payment_requests
                    SET status = 'Approved',
                        approved_by = " . (int)$approvedBy . ",
                        approved_amount = {$vprAppAmt},
                        due_amount = {$vprAppAmt},
                        management_note = '{$escVprNote}',
                        purchase_head_approved_by_id = COALESCE(purchase_head_approved_by_id, " . (int)$approvedBy . "),
                        purchase_head_approved_at = COALESCE(purchase_head_approved_at, NOW()),
                        purchase_head_note = COALESCE(purchase_head_note, '{$escVprNote} (Management Override)'),
                        qc_approved_by_id = COALESCE(qc_approved_by_id, " . (int)$approvedBy . "),
                        qc_approved_at = COALESCE(qc_approved_at, NOW()),
                        qc_verified = 1,
                        qc_approved_amount = COALESCE(NULLIF(qc_approved_amount, 0), {$vprAppAmt}),
                        qc_note = COALESCE(qc_note, '{$escVprNote} (Management Override)'),
                        updated_at = NOW()
                    WHERE id = {$sourceId}
                ");

                $this->db->query("
                    UPDATE approval_requests
                    SET approve_role = 'accounts',
                        status = 'approved',
                        op_head_status = 'approved',
                        op_head_by = COALESCE(op_head_by, " . (int)$approvedBy . "),
                        op_head_at = COALESCE(op_head_at, NOW()),
                        op_head_note = COALESCE(op_head_note, '{$escVprNote} (Management Override)'),
                        accounts_head_status = 'approved',
                        accounts_head_by = COALESCE(accounts_head_by, " . (int)$approvedBy . "),
                        accounts_head_at = COALESCE(accounts_head_at, NOW()),
                        accounts_head_note = COALESCE(accounts_head_note, '{$escVprNote} (Management Override)')
                    WHERE id = " . (int)$id . "
                ");

                try {
                    require_once dirname(__DIR__) . '/services/JournalIntegrationService.php';
                    $journalService = new \Services\JournalIntegrationService($this->db);
                    $journalService->generateVendorAccrual($sourceId, $approvedBy);
                } catch (\Throwable $e) {
                    error_log("Failed to auto-generate vendor accrual journal via Central Approval: " . $e->getMessage());
                }
            } else {
                $this->db->query("UPDATE vendor_payment_requests SET status = 'Rejected', approved_by = " . (int)$approvedBy . ", management_note = '{$escVprNote}', updated_at = NOW() WHERE id = {$sourceId}");
                $this->db->query("UPDATE vendor_penalties SET payment_request_id = NULL WHERE payment_request_id = {$sourceId}");
            }
            return true;
        }

        if ($sourceTable === 'contractor_bill_requests') {
            $cbStatus = ($status === 'approved') ? 'approved' : 'rejected';
            $cbAppAmt = ($status === 'approved') 
                ? ($approvedAmount !== null && (float)$approvedAmount > 0 ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0)) 
                : null;
            $escCbNote = $this->db->real_escape_string($approvalNote ?? 'Approved by Management');
            
            if ($status === 'approved') {
                // Auto-populate any bypassed earlier stages on contractor_bill_requests & approval_requests
                $this->db->query("
                    UPDATE contractor_bill_requests
                    SET status = 'approved',
                        approved_by = " . (int)$approvedBy . ",
                        approved_amount = {$cbAppAmt},
                        due_amount = {$cbAppAmt},
                        management_note = '{$escCbNote}',
                        verified_by = COALESCE(verified_by, " . (int)$approvedBy . "),
                        verified_at = COALESCE(verified_at, NOW()),
                        verification_note = COALESCE(verification_note, '{$escCbNote} (Management Override)'),
                        purchase_head_approved_by_id = COALESCE(purchase_head_approved_by_id, " . (int)$approvedBy . "),
                        purchase_head_approved_at = COALESCE(purchase_head_approved_at, NOW()),
                        purchase_head_note = COALESCE(purchase_head_note, '{$escCbNote} (Management Override)'),
                        qc_approved_by_id = COALESCE(qc_approved_by_id, " . (int)$approvedBy . "),
                        qc_approved_at = COALESCE(qc_approved_at, NOW()),
                        qc_note = COALESCE(qc_note, '{$escCbNote} (Management Override)'),
                        updated_at = NOW()
                    WHERE id = {$sourceId}
                ");

                $this->db->query("
                    UPDATE approval_requests
                    SET approve_role = 'accounts',
                        status = 'approved',
                        op_head_status = 'approved',
                        op_head_by = COALESCE(op_head_by, " . (int)$approvedBy . "),
                        op_head_at = COALESCE(op_head_at, NOW()),
                        op_head_note = COALESCE(op_head_note, '{$escCbNote} (Management Override)'),
                        accounts_head_status = 'approved',
                        accounts_head_by = COALESCE(accounts_head_by, " . (int)$approvedBy . "),
                        accounts_head_at = COALESCE(accounts_head_at, NOW()),
                        accounts_head_note = COALESCE(accounts_head_note, '{$escCbNote} (Management Override)')
                    WHERE id = " . (int)$id . "
                ");

                try {
                    require_once dirname(__DIR__) . '/services/JournalIntegrationService.php';
                    $journalService = new \Services\JournalIntegrationService($this->db);
                    $journalService->generateContractorAccrual($sourceId, $approvedBy);
                } catch (\Throwable $e) {
                    error_log("Failed to auto-generate contractor accrual journal via Central Approval: " . $e->getMessage());
                }
            } else {
                $this->db->query("UPDATE contractor_bill_requests SET status = 'rejected', approved_by = " . (int)$approvedBy . ", management_note = '{$escCbNote}', rejection_reason = '{$escCbNote}', updated_at = NOW() WHERE id = {$sourceId}");
            }
            return true;
        }

        if ($sourceTable === 'project_extra_assets') {
            $approveRole = strtolower($request['approve_role'] ?? 'management');
            
            // Determine which step is being approved
            $step = 'mgmt';
            if (strpos($approveRole, 'dept_head') === 0) {
                $step = 'dept_head';
            } elseif ($approveRole === 'it_head') {
                $step = 'it_head';
            } elseif ($approveRole === 'operations') {
                $step = 'operations';
            } elseif (in_array($approveRole, ['purchase_head', 'purchase'])) {
                $step = 'purchase_head';
            } elseif ($approveRole === 'qc') {
                $step = 'qc_audit';
            }

            $stepStatus = ($status === 'approved') ? 'approved' : 'rejected';
            
            $statusCol = $step . '_status';
            $byCol = $step . '_by';
            $atCol = $step . '_at';
            $noteCol = $step . '_note';

            // Special fast-track logic for management approving while QC is pending (retaining old behavior)
            if ($step === 'mgmt') {
                $qcStatusUpdate = ", qc_audit_status = CASE WHEN qc_audit_status = 'pending' THEN '{$stepStatus}' ELSE qc_audit_status END";
            } else {
                $qcStatusUpdate = "";
            }

            $stmtPea = $this->db->prepare(
                "UPDATE project_extra_assets 
                 SET {$statusCol} = ?,
                     {$byCol} = ?,
                     {$atCol} = NOW(),
                     {$noteCol} = ?
                     {$qcStatusUpdate}
                 WHERE id = ?"
            );

            if ($stmtPea) {
                $stmtPea->bind_param('sisi', $stepStatus, $approvedBy, $approvalNote, $sourceId);
                $stmtPea->execute();
                $stmtPea->close();
            }

            // Update overall_status
            if ($stepStatus === 'rejected') {
                $this->db->query("UPDATE project_extra_assets SET overall_status = 'rejected' WHERE id = {$sourceId}");
            } elseif ($step === 'mgmt' && $stepStatus === 'approved') {
                $this->db->query("UPDATE project_extra_assets SET overall_status = 'approved' WHERE id = {$sourceId}");
            } else {
                $this->db->query("UPDATE project_extra_assets SET overall_status = 'in_review' WHERE id = {$sourceId}");
            }
            $this->syncMissingOfficeProcurements();

            if ($step === 'mgmt' && $status === 'approved') {
                try {
                    require_once dirname(__DIR__) . '/services/AutoPostingService.php';
                    $autoPosting = new \AutoPostingService($this->db);
                    $assetExpenseAccId = $autoPosting->getAccountIdByCode('5220');
                    $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110');
                    
                    $reqRes = $this->db->query("SELECT * FROM project_extra_assets WHERE id = {$sourceId} LIMIT 1");
                    if ($reqRes && $req = $reqRes->fetch_assoc()) {
                        if ((float)$req['total_price'] > 0) {
                            $procurementOffice = !empty($req['asset_location']) ? $req['asset_location'] : null;
                            $autoPosting->postAccrualEntry(
                                'asset_procurement',
                                $sourceId,
                                $assetExpenseAccId,
                                $vendorPayableAccId,
                                $req['total_price'],
                                "Office Asset Procurement (PR-{$sourceId}): " . $req['item_name'] . " (Qty: {$req['quantity']})",
                                $req['project_id'],
                                'employee',
                                $req['assigned_to'] ?? $req['requested_by'],
                                $approvedBy,
                                $procurementOffice
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("AutoPosting Exception (Procurement Approval via Central): " . $e->getMessage());
                }
            }
            return true;
        }

        $updateFields = [];
        $params = [];
        $types = '';

        $requestType = $request['request_type'] ?? '';

        if ($sourceTable === 'lead_payments' && $requestType === 'Penalty Waiver Request') {
            if (in_array('waiver_status', $columns)) {
                $updateFields[] = "waiver_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            }
            if ($approvedAmount !== null && in_array('penalty_waived', $columns)) {
                $updateFields[] = "penalty_waived = ?";
                $types .= 'd';
                $params[] = (float) $approvedAmount;
            }
            if ($approvedBy !== null && in_array('waiver_approved_by', $columns)) {
                $updateFields[] = "waiver_approved_by = ?";
                $types .= 'i';
                $params[] = (int) $approvedBy;
            }
        } else {
            if (in_array('claim_status', $columns)) {
                $updateFields[] = "claim_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            } elseif (in_array('approval_status', $columns)) {
                $updateFields[] = "approval_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            } elseif ($sourceTable === 'payslips') {
                $payStatus = ($status === 'approved') ? 'approved' : 'reject';
                if (in_array('status', $columns)) {
                    $updateFields[] = "status = ?";
                    $types .= 's';
                    $params[] = $payStatus;
                }
            } elseif (in_array('status', $columns)) {
                $updateFields[] = "status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            } elseif ($sourceTable === 'lead_payments' && in_array('handover_status', $columns)) {
                $updateFields[] = "handover_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            }

            if (in_array('approved_amount', $columns) && $approvedAmount !== null) {
                $updateFields[] = "approved_amount = ?";
                $types .= 'd';
                $params[] = $approvedAmount;
            }

            if (in_array('approved_days', $columns) && $approved_days !== null) {
                $updateFields[] = "approved_days = ?";
                $types .= 'i';
                $params[] = $approved_days;
            }

            if (in_array('approved_by', $columns)) {
                $updateFields[] = "approved_by = ?";
                $types .= 'i';
                $params[] = $approvedBy;
            }

            if ($sourceTable === 'purchase_requests') {
                if (in_array('mgmt_approved_by_id', $columns) && $approvedBy !== null) {
                    $updateFields[] = "mgmt_approved_by_id = ?";
                    $types .= 'i';
                    $params[] = $approvedBy;
                }
                if (in_array('mgmt_approved_at', $columns) && $status === 'approved') {
                    $updateFields[] = "mgmt_approved_at = NOW()";
                }
                if (in_array('management_note', $columns) && $approvalNote !== null) {
                    $updateFields[] = "management_note = ?";
                    $types .= 's';
                    $params[] = $approvalNote;
                }
            }

            if ($sourceTable === 'lead_payments' && $status === 'rejected' && in_array('handover_rejection_reason', $columns)) {
                $updateFields[] = "handover_rejection_reason = ?";
                $types .= 's';
                $params[] = $approvalNote;
            }

            if (in_array('approved_at', $columns)) {
                $updateFields[] = "approved_at = NOW()";
            }
        }

        //    7. Execute source update 
        if (!empty($updateFields)) {
            $sql2 = "UPDATE {$sourceTable} SET " . implode(', ', $updateFields) . " WHERE id = ?";

            if ($sourceTable === 'lead_payments') {
                $sql2 = "UPDATE {$sourceTable} SET " . implode(', ', $updateFields) . " WHERE id = ? OR bulk_parent_id = ?";
                $types .= 'ii';
                $params[] = $sourceId;
                $params[] = $sourceId;
            } else {
                $types .= 'i';
                $params[] = $sourceId;
            }

            $stmt2 = $this->db->prepare($sql2);
            if ($stmt2) {
                $stmt2->bind_param($types, ...$params);
                $stmt2->execute();
                $stmt2->close();
            } else {
                error_log("ApprovalRequest updateStatus prepare failed for table {$sourceTable}: " . $this->db->error . " | SQL: " . $sql2);
            }
        }

        return true;
    }


    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    public function getApprovalDashboardMetrics()
    {
        $this->syncMissingFundRequisitions();
        $this->syncMissingOfficeProcurements();
        $this->syncMissingLaborRequisitions();
        $this->syncMissingProjectOtherCosts();
        $this->syncMissingVendorPaymentRequests();
        $this->syncMissingContractorBills();
        $this->syncMissingAdvanceSalaries();
        $this->syncMissingCashoutRequests();
        $this->syncMissingLandFundRequisitions();
            $this->syncMissingClientRefundRequests();
        $this->syncMissingClientRefundRequests();

        $sql = "SELECT 
                    COUNT(*) AS total_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN status = 'pending' THEN IFNULL(request_amount, 0) ELSE 0 END) AS pending_amount,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN status = 'approved' THEN IFNULL(approved_amount, IFNULL(request_amount, 0)) ELSE 0 END) AS approved_amount,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
                FROM approval_requests";

        $res = $this->db->query($sql);
        $row = $res ? $res->fetch_assoc() : [];

        // Check Accounts total disbursed across incentive payments, vendor payment requests, fund requisitions, office procurement, labor requisitions, project other costs & land fund requisitions
        $sqlDisbursed = "SELECT 
                            (SELECT IFNULL(SUM(payment_amount), 0) FROM project_incentive_payments) + 
                            (SELECT IFNULL(SUM(approved_amount), 0) FROM vendor_payment_requests WHERE status = 'Approved') +
                            (SELECT IFNULL(SUM(amount), 0) FROM fund_requisitions WHERE status = 'paid') +
                            (SELECT IFNULL(SUM(paid_amount), 0) FROM project_extra_assets WHERE payment_status = 'paid') +
                            (SELECT IFNULL(SUM(total_paid), 0) FROM con_labor_requisitions WHERE status IN ('paid', 'partially_paid', 'labor_disbursed')) +
                            (SELECT IFNULL(SUM(COALESCE(payment_amount, approved_amount, request_amount)), 0) FROM project_other_costs WHERE status = 'paid') +
                            (SELECT IFNULL(SUM(requested_amount), 0) FROM land_fund_requisitions WHERE accounts_status = 'Paid') AS total_disbursed";
        $resD = $this->db->query($sqlDisbursed);
        $totalDisbursed = $resD ? (float) ($resD->fetch_assoc()['total_disbursed'] ?? 0) : 0.00;

        return [
            'total_requests' => (int) ($row['total_requests'] ?? 0),
            'pending_count' => (int) ($row['pending_count'] ?? 0),
            'pending_amount' => (float) ($row['pending_amount'] ?? 0.00),
            'approved_count' => (int) ($row['approved_count'] ?? 0),
            'approved_amount' => (float) ($row['approved_amount'] ?? 0.00),
            'rejected_count' => (int) ($row['rejected_count'] ?? 0),
            'total_disbursed' => $totalDisbursed
        ];
    }

    /**
     * Build SQL condition for requests that are actionable by the logged-in user / role
     */
    public function buildActionableCondition($filters = [], $tableAlias = 'ar')
    {
        $prefix = !empty($tableAlias) ? $tableAlias . '.' : '';
        $userRoleId = (int)($filters['role_id'] ?? ($_SESSION['user']['role_id'] ?? 0));
        $userId = (int)($filters['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
        $roleName = strtolower(trim($filters['role_name'] ?? ($_SESSION['user']['role_name'] ?? '')));

        // Chief Officer, Management Viewer & SEAL CEO have NO actionable approval items
        if (in_array($userRoleId, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true)) {
            return "1=0";
        }

        $isManagement = in_array($userRoleId, [1, 3, 38], true) || in_array($roleName, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true);
        $isSiteOperator = in_array($userRoleId, [20, 32], true) || strpos($roleName, 'operation head') !== false || strpos($roleName, 'admin & operation') !== false;
        $isAccountsHead = ($userRoleId === 30) || strpos($roleName, 'accounts head') !== false;
        $isAccounts = ($userRoleId === 6) || (strpos($roleName, 'account') !== false && !$isAccountsHead);
        $isQc = in_array($userRoleId, [13, 24, 25], true) || strpos($roleName, 'qc') !== false || strpos($roleName, 'audit') !== false;
        $isPurchaseHead = ($userRoleId === 17) || strpos($roleName, 'purchase head') !== false || strpos($roleName, 'head of purchase') !== false;
        $isPurchase = in_array($userRoleId, [17, 23, 31], true) || strpos($roleName, 'purchase') !== false;
        $isPM = in_array($userRoleId, [15, 22], true) || strpos($roleName, 'project manager') !== false;
        $isHr = ($userRoleId === 4) || strpos($roleName, 'hr') !== false;

        // Check if user has subordinates reporting directly to them
        $subordinateIds = [];
        $subRes = $this->db->query("
            SELECT u.id 
            FROM users u 
            JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            WHERE e.reporting_user_id = {$userId} AND e.deleted_at IS NULL
        ");
        if ($subRes) {
            while ($sRow = $subRes->fetch_assoc()) {
                $subordinateIds[] = (int)$sRow['id'];
            }
        }
        $isReportingBoss = !empty($subordinateIds);
        $isDesignatedDeptHead = in_array($userRoleId, [5, 9, 10, 14], true) || (strpos($roleName, 'head') !== false && !in_array($userRoleId, [8, 11, 12, 16, 18, 19, 21, 23, 25]));

        if ($isAccountsHead) {
            return "(
                LOWER({$prefix}approve_role) != 'completed' AND (
                    ({$prefix}status IN ('pending', 'partially_approved', '') AND ({$prefix}accounts_head_status != 'approved' OR {$prefix}accounts_head_status IS NULL) AND (
                        LOWER({$prefix}approve_role) = 'accounts_head'
                        OR {$prefix}source_table = 'payslips'
                        OR ({$prefix}request_amount > 10000.01 AND {$prefix}op_head_status = 'approved')
                        OR ({$prefix}source_table IN ('vendor_payment_requests', 'contractor_bill_requests') AND {$prefix}op_head_status = 'approved')
                        OR ({$prefix}source_table NOT IN ('vendor_payment_requests', 'contractor_bill_requests') AND {$prefix}request_amount <= 10000.01 AND ({$prefix}op_head_status = 'approved' OR {$prefix}status = 'approved' OR LOWER({$prefix}approve_role) IN ('management', 'operations', 'accounts_head', '')))
                    ))
                    OR ({$prefix}status = 'approved' AND ({$prefix}accounts_head_status != 'approved' OR {$prefix}accounts_head_status IS NULL OR LOWER({$prefix}approve_role) = 'accounts_head'))
                    OR ({$prefix}status = 'partially_approved' AND {$prefix}source_table = 'fund_requisitions' AND ({$prefix}accounts_head_status != 'approved' OR {$prefix}accounts_head_status IS NULL))
                    OR ({$prefix}accounts_head_status = 'approved' AND (
                        LOWER({$prefix}approve_role) = 'accounts'
                        OR ({$prefix}source_table = 'payslips' AND {$prefix}status = 'approved')
                        OR {$prefix}source_table IN ('project_incentive_claims', 'vendor_payment_requests', 'contractor_bill_requests', 'con_labor_requisitions', 'fund_requisitions', 'project_other_costs', 'project_extra_assets', 'expense_adjustments', 'land_fund_requisitions', 'con_land_requisitions')
                    ))
                )
            )";
        } elseif ($isAccounts) {
            return "(
                LOWER({$prefix}approve_role) != 'completed' AND {$prefix}accounts_head_status = 'approved' AND (
                    ({$prefix}status IN ('pending', 'partially_approved', '') AND LOWER({$prefix}approve_role) = 'accounts')
                    OR ({$prefix}status = 'partially_approved' AND {$prefix}source_table = 'fund_requisitions')
                    OR ({$prefix}status = 'approved' AND (LOWER({$prefix}approve_role) = 'accounts' OR {$prefix}source_table IN ('payslips', 'project_incentive_claims', 'vendor_payment_requests', 'contractor_bill_requests', 'con_labor_requisitions', 'fund_requisitions', 'project_other_costs', 'project_extra_assets', 'expense_adjustments', 'land_fund_requisitions', 'con_land_requisitions')))
                )
            )";
        } elseif ($isSiteOperator && !$isManagement) {
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND {$prefix}source_table != 'payslips'
                    AND (LOWER({$prefix}approve_role) IN ('management', 'operations', 'accounts_head') OR {$prefix}approve_role IS NULL OR {$prefix}approve_role = '') 
                    AND ({$prefix}op_head_status != 'approved' OR {$prefix}op_head_status IS NULL))";
        } elseif ($isManagement) {
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND (LOWER({$prefix}approve_role) IN ('management', 'operations') OR {$prefix}approve_role IS NULL OR {$prefix}approve_role = '' OR {$prefix}source_table = 'payslips')
                    AND ({$prefix}source_table = 'payslips' OR ({$prefix}request_amount <= 10000.01 AND {$prefix}source_table NOT IN ('vendor_payment_requests', 'contractor_bill_requests')) OR ({$prefix}op_head_status = 'approved' AND {$prefix}accounts_head_status = 'approved')))";
        } elseif ($isQc) {
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND LOWER({$prefix}approve_role) IN ('qc', 'qc_head'))";
        } elseif ($isPurchaseHead) {
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND LOWER({$prefix}approve_role) IN ('purchase_head', 'purchase', 'purchase team leader', 'purchase_lead'))";
        } elseif ($isPurchase) {
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND LOWER({$prefix}approve_role) IN ('purchase', 'purchase team leader', 'purchase_lead'))";
        } elseif ($isPM) {
            $subCond = $isReportingBoss ? " OR ({$prefix}approve_role = 'dept_head' AND {$prefix}requested_by IN (" . implode(',', $subordinateIds) . "))" : "";
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND (LOWER({$prefix}approve_role) IN ('pm', 'pm_head') OR {$prefix}approve_role = 'dept_head_" . $userId . "'{$subCond}))";
        } elseif ($isHr) {
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND LOWER({$prefix}approve_role) IN ('hr', 'hr_head'))";
        } elseif ($isReportingBoss) {
            $subIdsStr = implode(',', $subordinateIds);
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND ({$prefix}approve_role = 'dept_head_" . $userId . "' OR ({$prefix}approve_role = 'dept_head' AND {$prefix}requested_by IN ({$subIdsStr}))))";
        } elseif ($isDesignatedDeptHead) {
            return "({$prefix}status IN ('pending', 'partially_approved', '') 
                    AND {$prefix}approve_role = 'dept_head_" . $userId . "')";
        } else {
            // General employees / staff with no approval responsibilities (Draftsman, Site Engineer, Sales Executive, etc.)
            return "1=0";
        }
    }

    /**
     * Get real-time actionable count for any user/role to power sidebar badges perfectly
     */
    public function getActionableCountForUser($filters = [])
    {
        $condition = $this->buildActionableCondition($filters, 'ar');
        $res = $this->db->query("SELECT COUNT(*) AS total FROM approval_requests ar WHERE {$condition}");
        return $res ? (int)($res->fetch_assoc()['total'] ?? 0) : 0;
    }

    /**
     * Get Module-Wise Expense & Requisition Metrics (Contractor, Vendor, Fund Req, Labor, Procurement, etc.)
     */
    public function getModuleExpenseBreakdown($filters = [])
    {
        $this->syncMissingFundRequisitions();
        $this->syncMissingOfficeProcurements();
        $this->syncMissingLaborRequisitions();
        $this->syncMissingProjectOtherCosts();
        $this->syncMissingVendorPaymentRequests();
        $this->syncMissingContractorBills();
        $this->syncMissingAdvanceSalaries();
        $this->syncMissingCashoutRequests();
        $this->syncMissingExpenseAdjustments();
        $this->syncMissingLandFundRequisitions();
        $this->syncMissingClientRefundRequests();
        $this->syncMissingPayslips();

        $moduleDefs = [
            'contractor_bill_requests' => [
                'key'         => 'contractor_bill_requests',
                'name'        => 'Contractor Bills',
                'dept'        => 'Construction',
                'icon'        => 'fa-solid fa-hard-hat',
                'badge_color' => 'warning',
                'accent_hex'  => '#f59e0b',
                'portal_url'  => 'contractor-bills',
                'portal_name' => 'Contractor Bills'
            ],
            'vendor_payment_requests' => [
                'key'         => 'vendor_payment_requests',
                'name'        => 'Vendor Payments',
                'dept'        => 'Procurement',
                'icon'        => 'fa-solid fa-truck-ramp-box',
                'badge_color' => 'primary',
                'accent_hex'  => '#3b82f6',
                'portal_url'  => 'vendor-payment-requests',
                'portal_name' => 'Vendor Payments'
            ],
            'fund_requisitions' => [
                'key'         => 'fund_requisitions',
                'name'        => 'Fund Requisitions',
                'dept'        => 'Accounts & Ops',
                'icon'        => 'fa-solid fa-hand-holding-dollar',
                'badge_color' => 'success',
                'accent_hex'  => '#10b981',
                'portal_url'  => 'fund-requisitions',
                'portal_name' => 'Fund Requisitions'
            ],
            'con_labor_requisitions' => [
                'key'         => 'con_labor_requisitions',
                'name'        => 'SE Labor Bills',
                'dept'        => 'Construction',
                'icon'        => 'fa-solid fa-users-gear',
                'badge_color' => 'info',
                'accent_hex'  => '#06b6d4',
                'portal_url'  => 'con-labor-requisitions',
                'portal_name' => 'Labor Bills'
            ],
            'project_extra_assets' => [
                'key'         => 'project_extra_assets',
                'name'        => 'Office Procurement',
                'dept'        => 'Procurement',
                'icon'        => 'fa-solid fa-boxes-stacked',
                'badge_color' => 'purple',
                'accent_hex'  => '#8b5cf6',
                'portal_url'  => 'office-procurement',
                'portal_name' => 'Procurement'
            ],
            'project_other_costs' => [
                'key'         => 'project_other_costs',
                'name'        => 'Project Other Costs',
                'dept'        => 'Construction',
                'icon'        => 'fa-solid fa-receipt',
                'badge_color' => 'warning',
                'accent_hex'  => '#d97706',
                'portal_url'  => 'project-other-costs',
                'portal_name' => 'Other Costs'
            ],
            'cashout_requests' => [
                'key'         => 'cashout_requests',
                'name'        => 'Cashout Requests',
                'dept'        => 'Accounts',
                'icon'        => 'fa-solid fa-money-bill-transfer',
                'badge_color' => 'danger',
                'accent_hex'  => '#ef4444',
                'portal_url'  => 'cashout-requests',
                'portal_name' => 'Cashout'
            ],
            'expense_adjustments' => [
                'key'         => 'expense_adjustments',
                'name'        => 'Requisition Settlements',
                'dept'        => 'Accounts & Audit',
                'icon'        => 'fa-solid fa-scale-balanced',
                'badge_color' => 'info',
                'accent_hex'  => '#0ea5e9',
                'portal_url'  => 'expense-adjustments',
                'portal_name' => 'Settlements'
            ],
            'payslips' => [
                'key'         => 'payslips',
                'name'        => 'Payslips & Salary',
                'dept'        => 'HR & Accounts',
                'icon'        => 'fa-solid fa-file-invoice-dollar',
                'badge_color' => 'dark',
                'accent_hex'  => '#374151',
                'portal_url'  => 'payslip-generate',
                'portal_name' => 'Payroll'
            ],
            'advance_salaries' => [
                'key'         => 'advance_salaries',
                'name'        => 'Advance Salary',
                'dept'        => 'HR & Accounts',
                'icon'        => 'fa-solid fa-wallet',
                'badge_color' => 'indigo',
                'accent_hex'  => '#6366f1',
                'portal_url'  => 'advance-salaries',
                'portal_name' => 'Advance Salary'
            ],
            'project_incentive_claims' => [
                'key'         => 'project_incentive_claims',
                'name'        => 'Sales Incentives',
                'dept'        => 'Sales',
                'icon'        => 'fa-solid fa-award',
                'badge_color' => 'secondary',
                'accent_hex'  => '#eab308',
                'portal_url'  => 'incentive-management',
                'portal_name' => 'Incentives'
            ],
            'land_fund_requisitions' => [
                'key'         => 'land_fund_requisitions',
                'name'        => 'Land Fund Requisitions',
                'dept'        => 'Land & Construction',
                'icon'        => 'fa-solid fa-mountain-city',
                'badge_color' => 'success',
                'accent_hex'  => '#059669',
                'portal_url'  => 'con-land-requisitions',
                'portal_name' => 'Land Requisitions'
            ],
        ];

        // 1. Grouped approval_requests stats
        $statusParam = strtolower(trim($filters['status'] ?? ''));
        $isAllPendingMode = ($statusParam === 'all_pending');
        $pendingCondition = $isAllPendingMode 
            ? "status IN ('pending', 'partially_approved', '')" 
            : $this->buildActionableCondition($filters, '');

        $sqlAR = "SELECT 
                    CASE WHEN source_table = 'purchase_requests' THEN 'project_extra_assets' ELSE source_table END as mod_key,
                    COUNT(*) AS total_requests,
                    SUM(request_amount) AS total_amount,
                    SUM(CASE WHEN {$pendingCondition} THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN {$pendingCondition} THEN IFNULL(request_amount, 0) ELSE 0 END) AS pending_amount,
                    SUM(CASE WHEN status IN ('approved', 'paid') THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN status IN ('approved', 'paid') THEN IFNULL(approved_amount, request_amount) ELSE 0 END) AS approved_amount,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
                FROM approval_requests
                GROUP BY mod_key";

        $resAR = $this->db->query($sqlAR);
        $arData = [];
        if ($resAR) {
            while ($r = $resAR->fetch_assoc()) {
                $arData[$r['mod_key']] = $r;
            }
        }

        // 2. Paid and Due calculations across source tables
        $paidData = [];
        $dueData = [];

        // Vendor Payments
        $vRes = $this->db->query("SELECT 
            SUM(CASE WHEN status IN ('Paid', 'Fully_Paid') THEN approved_amount WHEN status = 'Partially_Paid' THEN (approved_amount - due_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status IN ('Approved', 'QC Approved', 'Partially_Paid') THEN due_amount ELSE 0 END) as due
        FROM vendor_payment_requests");
        if ($vRes && $row = $vRes->fetch_assoc()) {
            $paidData['vendor_payment_requests'] = (float)($row['paid'] ?? 0);
            $dueData['vendor_payment_requests'] = (float)($row['due'] ?? 0);
        }

        // Contractor Bills
        $cRes = $this->db->query("SELECT 
            SUM(CASE WHEN status IN ('paid', 'fully_paid') THEN COALESCE(approved_amount, bill_amount) WHEN status = 'partially_paid' THEN (COALESCE(approved_amount, bill_amount) - due_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status = 'approved' THEN COALESCE(approved_amount, bill_amount) WHEN status = 'partially_paid' THEN due_amount ELSE 0 END) as due
        FROM contractor_bill_requests");
        if ($cRes && $row = $cRes->fetch_assoc()) {
            $paidData['contractor_bill_requests'] = (float)($row['paid'] ?? 0);
            $dueData['contractor_bill_requests'] = (float)($row['due'] ?? 0);
        }

        // Fund Requisitions
        $frRes = $this->db->query("SELECT 
            SUM(paid_amount) as paid,
            SUM(CASE WHEN status IN ('approved', 'partially_approved') THEN (amount - IFNULL(paid_amount, 0)) ELSE 0 END) as due
        FROM fund_requisitions");
        if ($frRes && $row = $frRes->fetch_assoc()) {
            $paidData['fund_requisitions'] = (float)($row['paid'] ?? 0);
            $dueData['fund_requisitions'] = (float)($row['due'] ?? 0);
        }

        // Labor Requisitions
        $lrRes = $this->db->query("SELECT 
            SUM(total_paid) as paid,
            SUM(CASE WHEN status IN ('management_approved', 'partially_paid') THEN (COALESCE(approved_amount, total_amount) - IFNULL(total_paid, 0)) ELSE 0 END) as due
        FROM con_labor_requisitions");
        if ($lrRes && $row = $lrRes->fetch_assoc()) {
            $paidData['con_labor_requisitions'] = (float)($row['paid'] ?? 0);
            $dueData['con_labor_requisitions'] = (float)($row['due'] ?? 0);
        }

        // Project Extra Assets (Office Procurement)
        $peaRes = $this->db->query("SELECT 
            SUM(CASE WHEN payment_status = 'paid' THEN IFNULL(paid_amount, total_price) ELSE 0 END) as paid,
            SUM(CASE WHEN mgmt_status = 'approved' AND payment_status != 'paid' THEN total_price ELSE 0 END) as due
        FROM project_extra_assets");
        if ($peaRes && $row = $peaRes->fetch_assoc()) {
            $paidData['project_extra_assets'] = (float)($row['paid'] ?? 0);
            $dueData['project_extra_assets'] = (float)($row['due'] ?? 0);
        }

        // Project Other Costs
        $pocRes = $this->db->query("SELECT 
            SUM(CASE WHEN status = 'paid' THEN COALESCE(payment_amount, approved_amount, request_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status = 'management_approved' THEN COALESCE(approved_amount, request_amount) ELSE 0 END) as due
        FROM project_other_costs");
        if ($pocRes && $row = $pocRes->fetch_assoc()) {
            $paidData['project_other_costs'] = (float)($row['paid'] ?? 0);
            $dueData['project_other_costs'] = (float)($row['due'] ?? 0);
        }

        // Cashout Requests
        $coRes = $this->db->query("SELECT 
            SUM(CASE WHEN status = 'fully_paid' THEN COALESCE(approved_amount, amount) WHEN status = 'partially_paid' THEN (COALESCE(approved_amount, amount) - due_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status IN ('approved', 'partially_paid') THEN due_amount ELSE 0 END) as due
        FROM cashout_requests");
        if ($coRes && $row = $coRes->fetch_assoc()) {
            $paidData['cashout_requests'] = (float)($row['paid'] ?? 0);
            $dueData['cashout_requests'] = (float)($row['due'] ?? 0);
        }

        // Expense Adjustments (Settlements)
        $eaRes = $this->db->query("SELECT 
            SUM(CASE WHEN status = 'fully_paid' THEN COALESCE(approved_amount, amount) WHEN status = 'partially_paid' THEN (COALESCE(approved_amount, amount) - due_amount) ELSE 0 END) as paid,
            SUM(CASE WHEN status IN ('approved', 'partially_paid') THEN due_amount ELSE 0 END) as due
        FROM expense_adjustments");
        if ($eaRes && $row = $eaRes->fetch_assoc()) {
            $paidData['expense_adjustments'] = (float)($row['paid'] ?? 0);
            $dueData['expense_adjustments'] = (float)($row['due'] ?? 0);
        }

        // Payslips
        $psRes = $this->db->query("SELECT 
            SUM(CASE WHEN status = 'paid' OR payment_status = 'paid' THEN net_salary ELSE 0 END) as paid,
            SUM(CASE WHEN status = 'approved' AND (payment_status IS NULL OR payment_status != 'paid') THEN net_salary ELSE 0 END) as due
        FROM payslips");
        if ($psRes && $row = $psRes->fetch_assoc()) {
            $paidData['payslips'] = (float)($row['paid'] ?? 0);
            $dueData['payslips'] = (float)($row['due'] ?? 0);
        }

        // Advance Salaries
        $asRes = $this->db->query("SELECT 
            SUM(CASE WHEN status IN ('paid', 'deducted') THEN amount ELSE 0 END) as paid,
            SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as due
        FROM advance_salaries");
        if ($asRes && $row = $asRes->fetch_assoc()) {
            $paidData['advance_salaries'] = (float)($row['paid'] ?? 0);
            $dueData['advance_salaries'] = (float)($row['due'] ?? 0);
        }

        // Sales Incentive Claims
        $picRes = $this->db->query("SELECT 
            (SELECT IFNULL(SUM(payment_amount), 0) FROM project_incentive_payments) as paid,
            (SELECT IFNULL(SUM(incentive_amount), 0) FROM project_incentive_claims WHERE claim_status = 'approved') as due");
        if ($picRes && $row = $picRes->fetch_assoc()) {
            $paidData['project_incentive_claims'] = (float)($row['paid'] ?? 0);
            $dueData['project_incentive_claims'] = (float)($row['due'] ?? 0);
        }

        // Land Fund Requisitions
        $lfrRes = $this->db->query("SELECT 
            SUM(CASE WHEN accounts_status = 'Paid' THEN requested_amount ELSE 0 END) as paid,
            SUM(CASE WHEN mgmt_status = 'Approved' AND accounts_status != 'Paid' THEN requested_amount ELSE 0 END) as due
        FROM land_fund_requisitions");
        if ($lfrRes && $row = $lfrRes->fetch_assoc()) {
            $paidData['land_fund_requisitions'] = (float)($row['paid'] ?? 0);
            $dueData['land_fund_requisitions'] = (float)($row['due'] ?? 0);
        }

        // 3. Dynamic Pending Calculations: Actionable for Current User vs All Department Pending
        $statusFilter = strtolower(trim($filters['status'] ?? ''));
        $deptFilter = strtolower(trim($filters['department'] ?? ''));

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $userRoleId = (int)($filters['role_id'] ?? ($_SESSION['user']['role_id'] ?? 0));
        $userId = (int)($filters['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
        $sessionRoleName = strtolower(trim($filters['role_name'] ?? ($_SESSION['user']['role_name'] ?? '')));
        $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

        $isFullManagement = !$isChiefOfficer && (in_array($userRoleId, [1, 3, 38], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true));
        $isRole20 = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;
        $isAccountsHead = ($userRoleId === 30) || strpos($sessionRoleName, 'accounts head') !== false;
        $isAccounts = ($userRoleId === 6) || (strpos($sessionRoleName, 'account') !== false && !$isAccountsHead);
        $isHR = ($userRoleId === 4) || strpos($sessionRoleName, 'hr') !== false;
        $isQC = in_array($userRoleId, [13, 24, 25], true) || strpos($sessionRoleName, 'qc') !== false || strpos($sessionRoleName, 'audit') !== false;
        $isPurchase = in_array($userRoleId, [17, 23, 31], true) || strpos($sessionRoleName, 'purchase') !== false;
        $isDeptHead = in_array($userRoleId, [8, 9, 10, 11, 12, 14, 15, 16, 18, 19], true) || strpos($sessionRoleName, 'head') !== false || strpos($sessionRoleName, 'manager') !== false;

        $pSql = "SELECT 
                    id,
                    CASE WHEN source_table = 'purchase_requests' THEN 'project_extra_assets' ELSE source_table END as mod_key,
                    source_table,
                    request_amount,
                    approve_role,
                    op_head_status,
                    accounts_head_status,
                    status,
                    department,
                    requested_by
                 FROM approval_requests
                 WHERE status IN ('pending', 'partially_approved', '')
                    OR (status = 'approved' AND (accounts_head_status != 'approved' OR accounts_head_status IS NULL))";
        $pRes = $this->db->query($pSql);
        $myPendingData = [];
        $allPendingData = [];

        if ($pRes) {
            while ($pRow = $pRes->fetch_assoc()) {
                $mKey = $pRow['mod_key'];
                $srcTbl = $pRow['source_table'];
                $amt = (float)($pRow['request_amount'] ?? 0);
                $appRole = strtolower($pRow['approve_role'] ?? 'management');
                if (empty($appRole)) $appRole = 'management';
                $rowDept = strtolower($pRow['department'] ?? '');
                $rowStatus = strtolower($pRow['status'] ?? 'pending');
                $ahDone = (strtolower($pRow['accounts_head_status'] ?? 'pending') === 'approved');

                if (!empty($deptFilter) && strpos($rowDept, $deptFilter) === false) {
                    continue;
                }

                if (in_array($rowStatus, ['pending', 'partially_approved', '']) || !$ahDone) {
                    if (!isset($allPendingData[$mKey])) {
                        $allPendingData[$mKey] = ['count' => 0, 'amount' => 0.0];
                    }
                    $allPendingData[$mKey]['count']++;
                    $allPendingData[$mKey]['amount'] += $amt;
                }

                // Actionable pending check for current user
                $isActionable = false;
                if ($isAccounts) {
                    if ($appRole === 'accounts' && $ahDone) $isActionable = true;
                } elseif ($isRole20) {
                    if ($srcTbl !== 'payslips' && in_array($rowStatus, ['pending', 'partially_approved', ''])) {
                        $opDone = (strtolower($pRow['op_head_status'] ?? 'pending') === 'approved');
                        if (in_array($srcTbl, ['project_other_costs', 'expense_adjustments']) && in_array($appRole, ['management', ''])) {
                            $isActionable = true;
                        } elseif ($srcTbl === 'project_extra_assets') {
                            if ($appRole === 'operations' && in_array($userRoleId, [20, 32], true)) {
                                $isActionable = true;
                            } elseif (in_array($appRole, ['management', '']) && ($amt <= 10000.01 || !$opDone)) {
                                $isActionable = true;
                            }
                        } elseif (in_array($srcTbl, ['vendor_payment_requests', 'contractor_bill_requests'], true)) {
                            if (in_array($appRole, ['operations', 'management', '']) && !$opDone) {
                                $isActionable = true;
                            }
                        } elseif (in_array($appRole, ['management', 'operations', '']) && ($amt <= 10000.01 || !$opDone)) {
                            $isActionable = true;
                        }
                    }
                } elseif ($isFullManagement) {
                    $isOver1L = ($amt > 10000.01);
                    $opDone = (strtolower($pRow['op_head_status'] ?? 'pending') === 'approved');
                    if (in_array($rowStatus, ['pending', 'partially_approved', '']) && (in_array($appRole, ['management', '']) || $srcTbl === 'payslips')) {
                        if ($srcTbl === 'payslips' || ($isOver1L && $opDone && $ahDone) || (!$isOver1L && !in_array($srcTbl, ['vendor_payment_requests', 'contractor_bill_requests'], true))) {
                            $isActionable = true;
                        }
                    }
                } elseif ($isAccountsHead) {
                    $isOver1L = ($amt > 10000.01);
                    $opDone = (strtolower($pRow['op_head_status'] ?? 'pending') === 'approved');
                    if (!$ahDone) {
                        if ($appRole === 'accounts_head' || $srcTbl === 'payslips' || $rowStatus === 'approved') {
                            $isActionable = true;
                        } elseif (($isOver1L || in_array($srcTbl, ['vendor_payment_requests', 'contractor_bill_requests'], true)) && $opDone) {
                            $isActionable = true;
                        } elseif (!$isOver1L && !in_array($srcTbl, ['vendor_payment_requests', 'contractor_bill_requests'], true) && in_array($appRole, ['management', 'operations', 'accounts_head', ''])) {
                            $isActionable = true;
                        }
                    } elseif ($appRole === 'accounts') {
                        $isActionable = true;
                    }
                } elseif ($isQC) {
                    if (in_array($appRole, ['qc', 'qc_head'])) $isActionable = true;
                } elseif ($isPurchase) {
                    if ($userRoleId === 17 && in_array($appRole, ['purchase_head', 'purchase team leader', 'purchase', 'purchase_lead'])) {
                        $isActionable = true;
                    } elseif (in_array($appRole, ['purchase team leader', 'purchase', 'purchase_lead'])) {
                        $isActionable = true;
                    }
                } elseif ($isHR) {
                    if (in_array($appRole, ['hr', 'hr_head'])) $isActionable = true;
                } elseif ($isDeptHead) {
                    if ($appRole === 'dept_head' || $appRole === 'dept_head_' . $userId || (in_array($userRoleId, [15, 22], true) && in_array($appRole, ['pm', 'pm_head']))) $isActionable = true;
                }

                if ($isActionable) {
                    if (!isset($myPendingData[$mKey])) {
                        $myPendingData[$mKey] = ['count' => 0, 'amount' => 0.0];
                    }
                    $myPendingData[$mKey]['count']++;
                    $myPendingData[$mKey]['amount'] += $amt;
                }
            }
        }

        // Combine into final list
        $result = [];
        foreach ($moduleDefs as $key => $meta) {
            $ar = $arData[$key] ?? [];
            $totalRequests = (int)($ar['total_requests'] ?? 0);
            $totalAmount = (float)($ar['total_amount'] ?? 0);
            $approvedCount = (int)($ar['approved_count'] ?? 0);
            $approvedAmount = (float)($ar['approved_amount'] ?? 0);
            $rejectedCount = (int)($ar['rejected_count'] ?? 0);
            $paidAmount = (float)($paidData[$key] ?? 0);
            $dueAmount = (float)($dueData[$key] ?? 0);

            $myCount = (int)($myPendingData[$key]['count'] ?? 0);
            $myAmount = (float)($myPendingData[$key]['amount'] ?? 0.0);
            $allCount = (int)($allPendingData[$key]['count'] ?? 0);
            $allAmount = (float)($allPendingData[$key]['amount'] ?? 0.0);

            // Active display count & amount based on current status filter
            if ($statusFilter === 'all_pending') {
                $displayPendingCount = $allCount;
                $displayPendingAmount = $allAmount;
                $activeMode = 'all_pending';
            } elseif (empty($statusFilter) || $statusFilter === 'pending' || $statusFilter === 'my_pending') {
                $displayPendingCount = $myCount;
                $displayPendingAmount = $myAmount;
                $activeMode = 'my_pending';
            } elseif ($statusFilter === 'paid') {
                $displayPendingCount = $approvedCount;
                $displayPendingAmount = $paidAmount;
                $activeMode = 'paid';
            } elseif ($statusFilter === 'pending_payout' || $statusFilter === 'approved_unpaid') {
                $displayPendingCount = $approvedCount;
                $displayPendingAmount = $dueAmount;
                $activeMode = 'pending_payout';
            } elseif ($statusFilter === 'approved') {
                $displayPendingCount = $approvedCount;
                $displayPendingAmount = $approvedAmount;
                $activeMode = 'approved';
            } elseif ($statusFilter === 'rejected') {
                $displayPendingCount = $rejectedCount;
                $displayPendingAmount = 0.0;
                $activeMode = 'rejected';
            } else {
                $displayPendingCount = $allCount;
                $displayPendingAmount = $allAmount;
                $activeMode = 'all';
            }

            // Fallback calculation for due: if due is 0 but approved > paid, compute due
            if ($dueAmount <= 0 && $approvedAmount > $paidAmount) {
                $dueAmount = $approvedAmount - $paidAmount;
            }

            $result[$key] = array_merge($meta, [
                'total_requests'    => $totalRequests,
                'total_amount'      => $totalAmount,
                'pending_count'     => $displayPendingCount,
                'pending_amount'    => $displayPendingAmount,
                'my_pending_count'  => $myCount,
                'my_pending_amount' => $myAmount,
                'all_pending_count' => $allCount,
                'all_pending_amount'=> $allAmount,
                'active_mode'       => $activeMode,
                'approved_count'    => $approvedCount,
                'approved_amount'   => $approvedAmount,
                'paid_amount'       => $paidAmount,
                'due_amount'        => $dueAmount,
            ]);
        }

        return $result;
    }

    public function buildApprovalHistoryWhere($filters = [])
    {
        $where = ["1=1"];
        $params = [];
        $types = "";

        $statusParam = strtolower(trim($filters['status'] ?? ''));
        $isMyPending = (empty($statusParam) || $statusParam === 'pending' || $statusParam === 'my_pending');

        if ($isMyPending) {
            $where[] = $this->buildActionableCondition($filters, 'ar');
        } elseif ($statusParam === 'paid') {
            $where[] = "(ar.approve_role = 'completed' OR ar.status = 'paid')";
        } elseif ($statusParam === 'pending_payout' || $statusParam === 'approved_unpaid') {
            $where[] = "(ar.status = 'approved' AND ar.accounts_head_status = 'approved' AND (ar.approve_role = 'accounts' OR ar.approve_role = 'completed'))";
        } elseif ($statusParam === 'pending_accounts_head') {
            $where[] = "(ar.status IN ('pending', 'partially_approved', 'approved') AND (ar.accounts_head_status = 'pending' OR ar.accounts_head_status IS NULL OR ar.approve_role = 'accounts_head'))";
        } elseif ($statusParam === 'approved') {
            $where[] = "(ar.status IN ('approved', 'paid') OR ar.approve_role = 'completed')";
        } elseif ($statusParam === 'rejected') {
            $where[] = "ar.status = 'rejected'";
        } elseif ($statusParam === 'all_pending') {
            $where[] = "(ar.status IN ('pending', 'partially_approved', '') OR (ar.status = 'approved' AND (ar.accounts_head_status != 'approved' OR ar.accounts_head_status IS NULL)))";
        } elseif ($statusParam === 'all') {
            // All company requests: complete audit without status filter
        } else {
            $where[] = "ar.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['department'])) {
            $where[] = "(LOWER(ar.department) LIKE ? OR LOWER(d_req.name) LIKE ?)";
            $deptParam = '%' . strtolower($filters['department']) . '%';
            $params[] = $deptParam;
            $params[] = $deptParam;
            $types .= "ss";
        }

        if (!empty($filters['source_table'])) {
            if ($filters['source_table'] === 'purchase_requests' || $filters['source_table'] === 'project_extra_assets') {
                $where[] = "ar.source_table IN ('purchase_requests', 'project_extra_assets')";
            } else {
                $where[] = "ar.source_table = ?";
                $params[] = $filters['source_table'];
                $types .= "s";
            }
        }

        if (!empty($filters['search'])) {
            $searchRaw = trim($filters['search']);
            $numericId = 0;
            if (preg_match('/(?:REQ|CB|VPR|LR|POC|PR|LFR|CSH|ADJ|REF|PAY|INC|ADV|BK|WO)[-#\s]*(\d+)/i', $searchRaw, $m)) {
                $numericId = (int)$m[1];
            } elseif (ctype_digit($searchRaw)) {
                $numericId = (int)$searchRaw;
            }

            $searchLike = '%' . $searchRaw . '%';
            if ($numericId > 0) {
                $where[] = "(
                    ar.id = ? 
                    OR ar.source_id = ? 
                    OR e_req.employee_id = ?
                    OR u_req.employee_id = ?
                    OR ar.title LIKE ? 
                    OR ar.department LIKE ? 
                    OR ar.request_type LIKE ? 
                    OR u_req.full_name LIKE ?
                )";
                $params[] = $numericId;
                $params[] = $numericId;
                $params[] = $numericId;
                $params[] = $numericId;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $types .= "iiiissss";
            } else {
                $where[] = "(
                    ar.title LIKE ? 
                    OR ar.department LIKE ? 
                    OR ar.request_type LIKE ? 
                    OR ar.source_table LIKE ?
                    OR u_req.full_name LIKE ?
                    OR e_req.employee_id LIKE ?
                )";
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $params[] = $searchLike;
                $types .= "ssssss";
            }
        }

        return [$where, $params, $types];
    }

    public function countAllApprovalHistory($filters = [])
    {
        $this->syncMissingPayslips();
        list($where, $params, $types) = $this->buildApprovalHistoryWhere($filters);
        $whereClause = implode(" AND ", $where);

        $sql = "SELECT COUNT(DISTINCT ar.id) AS total_count
                FROM approval_requests ar
                LEFT JOIN users u_req ON ar.requested_by = u_req.id
                LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
                LEFT JOIN departments d_req ON e_req.department_id = d_req.id
                WHERE {$whereClause}";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($res['total_count'] ?? 0);
    }

    public function getAllApprovalHistory($filters = [], $limit = 0, $offset = 0)
    {
        $this->syncMissingPayslips();
        // Throttle background table syncs so they don't block every single click/page load
        $lastSync = $_SESSION['last_approval_sync_time'] ?? 0;
        if (time() - $lastSync > 180) {
            $_SESSION['last_approval_sync_time'] = time();
            $this->syncMissingFundRequisitions();
            $this->syncMissingOfficeProcurements();
            $this->syncMissingLaborRequisitions();
            $this->syncMissingProjectOtherCosts();
            $this->syncMissingVendorPaymentRequests();
            $this->syncMissingContractorBills();
            $this->syncMissingAdvanceSalaries();
            $this->syncMissingCashoutRequests();
            $this->syncMissingExpenseAdjustments();
            $this->syncMissingLandFundRequisitions();
        $this->syncMissingClientRefundRequests();
        $this->syncMissingPayslips();
        }

        list($where, $params, $types) = $this->buildApprovalHistoryWhere($filters);
        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    ar.id,
                    ar.department,
                    ar.request_type,
                    ar.title,
                    ar.source_table,
                    ar.source_id,
                    ar.approve_role,
                    IFNULL(ar.request_amount, 0.00) AS request_amount,
                    CASE WHEN ar.status = 'approved' THEN IFNULL(ar.approved_amount, ar.request_amount) ELSE ar.approved_amount END AS approved_amount,
                    ar.attachment,
                    ar.status,
                    ar.created_at AS requested_at,
                    ar.updated_at,
                    ar.approved_at,
                    ar.approval_note,
                    ar.accounts_head_status,
                    ar.accounts_head_by,
                    ar.accounts_head_at,
                    ar.accounts_head_note,
                    ar.op_head_status,
                    ar.op_head_by,
                    ar.op_head_at,
                    ar.op_head_note,
                    u_req.full_name AS requester_name,
                    e_req.employee_id AS requester_code,
                    d_req.name AS requester_dept,
                    u_app.full_name AS approver_name,
                    u_app.email AS approver_email,
                    IFNULL(des_app.name, 'Management Executive') AS approver_designation,
                    u_op.full_name AS op_head_name,
                    e_op.employee_id AS op_head_code,
                    IFNULL(des_op.name, 'Admin & Operation Head') AS op_head_designation,
                    u_ah.full_name AS accounts_head_name,
                    e_ah.employee_id AS accounts_head_code,
                    IFNULL(des_ah.name, 'AGM - Finance & Accounts') AS accounts_head_designation
                FROM approval_requests ar
                LEFT JOIN users u_req ON ar.requested_by = u_req.id
                LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
                LEFT JOIN departments d_req ON e_req.department_id = d_req.id
                LEFT JOIN users u_app ON ar.approved_by = u_app.id
                LEFT JOIN employees e_app ON (e_app.user_id = u_app.id OR e_app.id = u_app.employee_id)
                LEFT JOIN designations des_app ON des_app.id = COALESCE(e_app.designation_id, u_app.designation_id)
                LEFT JOIN users u_op ON ar.op_head_by = u_op.id
                LEFT JOIN employees e_op ON (e_op.user_id = u_op.id OR e_op.id = u_op.employee_id)
                LEFT JOIN designations des_op ON des_op.id = COALESCE(e_op.designation_id, u_op.designation_id)
                LEFT JOIN users u_ah ON ar.accounts_head_by = u_ah.id
                LEFT JOIN employees e_ah ON (e_ah.user_id = u_ah.id OR e_ah.id = u_ah.employee_id)
                LEFT JOIN designations des_ah ON des_ah.id = COALESCE(e_ah.designation_id, u_ah.designation_id)
                WHERE {$whereClause}
                ORDER BY COALESCE(ar.updated_at, ar.created_at) DESC, ar.id DESC";

        if ($limit > 0) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = (int)$limit;
            $params[] = (int)$offset;
            $types .= "ii";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log("ApprovalRequest::getAllApprovalHistory prepare error: " . $this->db->error);
            return [];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $sourceTable = $row['source_table'];
            $sourceId = (int) $row['source_id'];

            // Fallback and metadata enrichment for lead_bookings
            if ($sourceTable === 'lead_bookings') {
                $lbStmt = $this->db->prepare("
                    SELECT lb.agreement_value, lb.discount_amount, lb.past_approval_attachment,
                           COALESCE(NULLIF(lb.sales_by_id, 0), l.assigned_id) AS resolved_sales_id,
                           l.full_name AS client_name, l.phone AS client_phone,
                           p.name AS project_name,
                           f.flat_name, f.floor_no,
                           u.full_name AS sales_agent_name, e.employee_id AS sales_agent_code,
                           d.name AS sales_dept_name
                    FROM lead_bookings lb
                    LEFT JOIN leads l ON lb.lead_id = l.id
                    LEFT JOIN projects p ON lb.project_id = p.id
                    LEFT JOIN flats f ON lb.flat_id = f.id
                    LEFT JOIN users u ON u.id = COALESCE(NULLIF(lb.sales_by_id, 0), l.assigned_id)
                    LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
                    LEFT JOIN departments d ON e.department_id = d.id
                    WHERE lb.id = ? LIMIT 1
                ");
                if ($lbStmt) {
                    $lbStmt->bind_param("i", $sourceId);
                    $lbStmt->execute();
                    $lbRow = $lbStmt->get_result()->fetch_assoc();
                    $lbStmt->close();
                    if ($lbRow) {
                        if (empty($row['attachment']) && !empty($lbRow['past_approval_attachment'])) {
                            $row['attachment'] = $lbRow['past_approval_attachment'];
                        }
                        $row['booking_agreement_value'] = (float)($lbRow['agreement_value'] ?? 0);
                        $row['booking_client_name'] = $lbRow['client_name'] ?? '';
                        $row['booking_client_phone'] = $lbRow['client_phone'] ?? '';
                        $row['booking_project_name'] = $lbRow['project_name'] ?? '';
                        $row['booking_flat_name'] = $lbRow['flat_name'] ?? '';
                        $row['booking_floor_no'] = $lbRow['floor_no'] ?? '';
                        $row['booking_sales_agent'] = $lbRow['sales_agent_name'] ?? '';
                        $row['booking_sales_code'] = $lbRow['sales_agent_code'] ?? '';

                        // For lead_bookings discount, requester is always the sales agent (sales_by_id)
                        if (!empty($lbRow['sales_agent_name'])) {
                            $row['requester_name'] = $lbRow['sales_agent_name'];
                            $row['requester_code'] = $lbRow['sales_agent_code'];
                            if (!empty($lbRow['sales_dept_name'])) {
                                $row['requester_dept'] = $lbRow['sales_dept_name'];
                            }
                            // Self-heal requested_by in approval_requests if mismatched
                            $resolvedSalesId = (int)($lbRow['resolved_sales_id'] ?? 0);
                            if ($resolvedSalesId > 0 && (int)($row['requested_by'] ?? 0) !== $resolvedSalesId) {
                                $this->db->query("UPDATE approval_requests SET requested_by = {$resolvedSalesId} WHERE id = " . (int)$row['id']);
                                $row['requested_by'] = $resolvedSalesId;
                            }
                        }
                    }
                }

                // Fallback from meta_data if booking row is missing or deleted
                if (empty($row['booking_client_name']) && !empty($row['meta_data'])) {
                    $meta = json_decode($row['meta_data'], true);
                    if (!empty($meta['lead_id'])) {
                        $leadId = (int)$meta['lead_id'];
                        $leadQ = $this->db->query("SELECT full_name, phone FROM leads WHERE id = {$leadId} LIMIT 1");
                        if ($leadQ && $leadRow = $leadQ->fetch_assoc()) {
                            $row['booking_client_name'] = $leadRow['full_name'] ?? '';
                            $row['booking_client_phone'] = $leadRow['phone'] ?? '';
                        }
                    }
                    if (!empty($meta['flat_id'])) {
                        $flatId = (int)$meta['flat_id'];
                        $flatQ = $this->db->query("SELECT f.flat_name, f.floor_no, p.name AS project_name FROM flats f LEFT JOIN projects p ON f.project_id = p.id WHERE f.id = {$flatId} LIMIT 1");
                        if ($flatQ && $flatRow = $flatQ->fetch_assoc()) {
                            $row['booking_flat_name'] = $flatRow['flat_name'] ?? '';
                            $row['booking_floor_no'] = $flatRow['floor_no'] ?? '';
                            $row['booking_project_name'] = $flatRow['project_name'] ?? '';
                        }
                    }
                }
            }

            $payoutStatus = 'N/A';
            $payoutAmount = 0.00;
            $payoutDate = null;
            $row['disbursed_by_name'] = null;
            $row['disbursed_by_code'] = null;
            $row['disbursed_branch_name'] = null;
            $row['disbursed_branch_id'] = null;

            // Default stage label & pending with text
            $pendingStageLabel = 'Pending Review';
            $pendingForText = 'Management';
            $appRole = strtolower($row['approve_role'] ?? 'management');
            if (empty($appRole)) $appRole = 'management';
            $rowAhStatus = strtolower($row['accounts_head_status'] ?? 'pending');

            if ($appRole === 'dept_head') {
                $pendingStageLabel = 'Pending Dept Head';
                $pendingForText = 'Department Head / Reporting Boss';
            } elseif ($appRole === 'qc') {
                $pendingStageLabel = 'Pending QC & Audit';
                $pendingForText = 'QC & Internal Audit Head';
            } elseif ($appRole === 'accounts_head' || ($row['status'] === 'approved' && $rowAhStatus !== 'approved')) {
                $pendingStageLabel = 'Pending Accounts Head';
                $pendingForText = 'Accounts Head';
            } elseif ($appRole === 'accounts') {
                $pendingStageLabel = 'Pending Accounts Payout';
                $pendingForText = 'Accounts & Finance';
            } elseif (in_array($appRole, ['hr', 'hr_head'])) {
                $pendingStageLabel = 'Pending HR Approval';
                $pendingForText = 'HR Head';
            } elseif ($appRole === 'purchase_head') {
                $pendingStageLabel = 'Pending Purchase Head';
                $pendingForText = 'Head of Purchase';
            } elseif (in_array($appRole, ['purchase', 'purchase team leader', 'purchase_lead'])) {
                $pendingStageLabel = 'Pending Purchase Lead';
                $pendingForText = 'Purchase Team Leader';
            } elseif ($appRole === 'pm') {
                $pendingStageLabel = 'Pending PM Verification';
                $pendingForText = 'Project Manager';
            } elseif ($appRole === 'it_head') {
                $pendingStageLabel = 'Pending IT Head';
                $pendingForText = 'IT Head';
            } else {
                $isOver1Lac = ((float)($row['request_amount'] ?? 0) > 10000.01);
                $rowOpStatus = strtolower($row['op_head_status'] ?? 'pending');
                $rowAhStatus = strtolower($row['accounts_head_status'] ?? 'pending');
                if ($isOver1Lac) {
                    if ($rowOpStatus !== 'approved') {
                        $pendingStageLabel = 'Pending Op Head (>10K)';
                        $pendingForText = 'Admin & Operation Head';
                    } elseif ($rowAhStatus !== 'approved') {
                        $pendingStageLabel = 'Pending Accounts Head (>10K)';
                        $pendingForText = 'Accounts Head';
                        $row['approve_role'] = 'accounts_head';
                    } else {
                        $pendingStageLabel = 'Pending Management (>10K)';
                        $pendingForText = 'Management';
                        $row['approve_role'] = 'management';
                    }
                } else {
                    if ($rowOpStatus === 'approved' || in_array(strtolower($row['status'] ?? ''), ['approved', 'partially_approved'])) {
                        if ($rowAhStatus !== 'approved') {
                            $pendingStageLabel = 'Pending Accounts Head';
                            $pendingForText = 'Accounts Head';
                            $row['approve_role'] = 'accounts_head';
                        } else {
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                            $row['approve_role'] = 'accounts';
                        }
                    } else {
                        $pendingStageLabel = 'Pending Review (≤10K)';
                        $pendingForText = 'Op Head / Management';
                    }
                }
            }

            if ($sourceTable === 'project_incentive_claims') {
                $paySql = "SELECT SUM(payment_amount) AS total_paid, MAX(payment_date) AS pay_date 
                           FROM project_incentive_payments WHERE claim_id = ?";
                $pStmt = $this->db->prepare($paySql);
                if ($pStmt) {
                    $pStmt->bind_param("i", $sourceId);
                    $pStmt->execute();
                    $pRow = $pStmt->get_result()->fetch_assoc();
                    $pStmt->close();
                    $totalPaid = (float) ($pRow['total_paid'] ?? 0.00);
                    if ($totalPaid > 0) {
                        $payoutStatus = 'Paid & Settled';
                        $payoutAmount = $totalPaid;
                        $payoutDate = $pRow['pay_date'];
                        $pendingStageLabel = 'Paid & Settled';
                        $pendingForText = 'Settled';
                        $pipRes = $this->db->query("SELECT disbursed_by FROM project_incentive_payments WHERE claim_id = {$sourceId} ORDER BY id DESC LIMIT 1");
                        if ($pipRes && $pipRow = $pipRes->fetch_assoc()) {
                            if ($d = $this->getDisburserInfo($pipRow['disbursed_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        }
                    } else if ($row['status'] === 'approved') {
                        $payoutStatus = 'Pending Accounts Payout';
                        $pendingStageLabel = 'Pending Accounts Payout';
                        $pendingForText = 'Accounts & Finance';
                    }
                }
            } else if ($sourceTable === 'con_labor_requisitions') {
                $lrSql = "SELECT lr.status, lr.total_amount, lr.approved_amount, lr.qc_approved_amount, 
                                 lr.requisition_no,
                                 lr.qc_approved_by, lr.qc_approved_at, lr.qc_note,
                                 u_qc.full_name AS qc_approver_name,
                                 e_qc.employee_id AS qc_approver_code,
                                 COALESCE(des_qc.name, r_qc.name, 'QC & Audit Head') AS qc_approver_designation,
                                 IFNULL(lr.approved_amount, lr.total_amount) AS app_amt,
                                 IFNULL(lr.total_paid, 0.00) AS total_paid, lr.payment_date 
                          FROM con_labor_requisitions lr
                          LEFT JOIN users u_qc ON lr.qc_approved_by = u_qc.id
                          LEFT JOIN roles r_qc ON u_qc.role_id = r_qc.id
                          LEFT JOIN employees e_qc ON (e_qc.user_id = u_qc.id OR e_qc.id = u_qc.employee_id)
                          LEFT JOIN designations des_qc ON des_qc.id = COALESCE(e_qc.designation_id, u_qc.designation_id)
                          WHERE lr.id = ? LIMIT 1";
                $lrStmt = $this->db->prepare($lrSql);
                if ($lrStmt) {
                    $lrStmt->bind_param("i", $sourceId);
                    $lrStmt->execute();
                    $lrRow = $lrStmt->get_result()->fetch_assoc();
                    $lrStmt->close();
                    if ($lrRow) {
                        $row['requisition_no'] = !empty($lrRow['requisition_no']) ? $lrRow['requisition_no'] : ('LR-' . $sourceId);
                        $row['qc_approved_amount'] = (float)($lrRow['qc_approved_amount'] ?? 0);
                        $row['qc_approved_by'] = $lrRow['qc_approved_by'] ?? null;
                        $row['qc_approved_at'] = $lrRow['qc_approved_at'] ?? null;
                        $row['qc_note'] = $lrRow['qc_note'] ?? null;
                        $row['qc_approver_name'] = $lrRow['qc_approver_name'] ?? null;
                        $row['qc_approver_code'] = $lrRow['qc_approver_code'] ?? null;
                        $row['qc_approver_designation'] = $lrRow['qc_approver_designation'] ?? null;
                        $row['request_amount'] = (float)($lrRow['total_amount'] ?? $row['request_amount']);

                        if (!empty($lrRow['approved_amount']) && (float)$lrRow['approved_amount'] > 0) {
                            $row['approved_amount'] = (float)$lrRow['approved_amount'];
                        } elseif (!empty($lrRow['qc_approved_amount']) && (float)$lrRow['qc_approved_amount'] > 0) {
                            $row['approved_amount'] = (float)$lrRow['qc_approved_amount'];
                        } else {
                            $row['approved_amount'] = null;
                        }
                        $lrStatus = strtolower($lrRow['status'] ?? '');
                        if ($lrStatus === 'pending') {
                            $row['approve_role'] = 'pm';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending PM Verification';
                            $pendingForText = 'Project Manager';
                        } elseif ($lrStatus === 'payment_requested') {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head';
                        } elseif (in_array($lrStatus, ['qc_approved', 'pm_payment_approved'])) {
                            $isOver100k = ((float)($row['request_amount'] ?? 0) > 10000.01);
                            $isOpHeadApproved = (!empty($row['op_head_status']) && strtolower($row['op_head_status']) === 'approved');
                            $rowAhStatus = strtolower($row['accounts_head_status'] ?? 'pending');
                            if (!$isOver100k && $isOpHeadApproved) {
                                $row['status'] = 'approved';
                                if ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $pendingStageLabel = 'Pending Accounts Head';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'accounts';
                                    $pendingStageLabel = 'Pending Accounts Payout';
                                    $pendingForText = 'Accounts & Finance';
                                }
                            } elseif ($isOver100k) {
                                if (!$isOpHeadApproved) {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Op Head (>10K)';
                                    $pendingForText = 'Admin & Operation Head';
                                } elseif ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Accounts Head (>10K)';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Management (>10K)';
                                    $pendingForText = 'Management';
                                }
                            } else {
                                $row['approve_role'] = 'management';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending Management';
                                $pendingForText = 'Management / Operation Head';
                            }
                        } elseif ($lrStatus === 'management_approved') {
                            $row['status'] = 'approved';
                            if (($row['accounts_head_status'] ?? 'pending') !== 'approved') {
                                $row['approve_role'] = 'accounts_head';
                                $pendingStageLabel = 'Pending Accounts Head';
                                $pendingForText = 'Accounts Head';
                            } else {
                                $row['approve_role'] = 'accounts';
                                $pendingStageLabel = 'Pending Accounts Payout';
                                $pendingForText = 'Accounts & Finance';
                            }
                        } elseif (in_array($lrStatus, ['paid', 'labor_disbursed'])) {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif ($lrStatus === 'partially_paid') {
                            $row['status'] = 'partially_paid';
                            $row['approve_role'] = 'accounts';
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($lrStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if (in_array($lrStatus, ['paid', 'labor_disbursed'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) ($lrRow['total_paid'] > 0 ? $lrRow['total_paid'] : $lrRow['app_amt']);
                            $payoutDate = $lrRow['payment_date'];
                            $dId = !empty($lrRow['labor_disbursed_by']) ? $lrRow['labor_disbursed_by'] : ($lrRow['paid_by'] ?? null);
                            if ($dId && $d = $this->getDisburserInfo($dId)) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if ($lrStatus === 'partially_paid') {
                            $payoutStatus = 'Partially Paid';
                            $payoutAmount = (float) $lrRow['total_paid'];
                            $payoutDate = $lrRow['payment_date'];
                            $dId = !empty($lrRow['labor_disbursed_by']) ? $lrRow['labor_disbursed_by'] : ($lrRow['paid_by'] ?? null);
                            if ($dId && $d = $this->getDisburserInfo($dId)) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if (in_array($lrStatus, ['management_approved']) || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float) $lrRow['app_amt'];
                        }
                    }
                }
            } else if ($sourceTable === 'vendor_payment_requests') {
                $vSql = "SELECT status, approved_amount, qc_approved_amount, purchase_head_approved_by_id,
                                COALESCE(approved_amount, qc_approved_amount, net_payable, invoice_amount, 0.00) AS app_amt,
                                net_payable, invoice_amount, updated_at, payment_type, purchase_order_id 
                         FROM vendor_payment_requests WHERE id = ? LIMIT 1";
                $vStmt = $this->db->prepare($vSql);
                if ($vStmt) {
                    $vStmt->bind_param("i", $sourceId);
                    $vStmt->execute();
                    $vRow = $vStmt->get_result()->fetch_assoc();
                    $vStmt->close();
                    if ($vRow) {
                        $row['qc_approved_amount'] = (float)($vRow['qc_approved_amount'] ?? 0);
                        if (!empty($vRow['approved_amount']) && (float)$vRow['approved_amount'] > 0) {
                            $row['approved_amount'] = (float)$vRow['approved_amount'];
                        } elseif (!empty($vRow['qc_approved_amount']) && (float)$vRow['qc_approved_amount'] > 0) {
                            $row['approved_amount'] = (float)$vRow['qc_approved_amount'];
                        }
                        $vStatus = strtolower(trim(str_replace('_', ' ', $vRow['status'] ?? '')));
                        $row['vpr_payment_type'] = $vRow['payment_type'] ?? null;
                        $row['po_number'] = $vRow['purchase_order_id'] ?? null;

                        if (in_array($vStatus, ['submitted', 'pending', 'draft'])) {
                            if (empty($vRow['purchase_head_approved_by_id'])) {
                                $row['approve_role'] = 'purchase_head';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending Purchase Head';
                                $pendingForText = 'Head of Purchase';
                            } else {
                                $row['approve_role'] = 'qc';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending QC & Audit';
                                $pendingForText = 'QC & Internal Audit Head';
                            }
                        } elseif (in_array($vStatus, ['qc approved', 'qc_approved'])) {
                            $isOver100k = ((float)($row['request_amount'] ?? 0) > 10000.01);
                            $isOpHeadApproved = (!empty($row['op_head_status']) && strtolower($row['op_head_status']) === 'approved');
                            $rowAhStatus = strtolower($row['accounts_head_status'] ?? 'pending');
                            if (!$isOver100k) {
                                if (!$isOpHeadApproved) {
                                    $row['approve_role'] = 'operations';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Op Head (≤10K)';
                                    $pendingForText = 'Admin & Operation Head';
                                } elseif ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Accounts Head (≤10K)';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['status'] = 'approved';
                                    $row['approve_role'] = 'accounts';
                                    $pendingStageLabel = 'Pending Accounts Payout';
                                    $pendingForText = 'Accounts & Finance';
                                }
                            } else {
                                if (!$isOpHeadApproved) {
                                    $row['approve_role'] = 'operations';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Op Head (>10K)';
                                    $pendingForText = 'Admin & Operation Head';
                                } elseif ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Accounts Head (>10K)';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Management (>10K)';
                                    $pendingForText = 'Management';
                                }
                            }
                        } elseif (in_array($vStatus, ['approved', 'management approved', 'management_approved'])) {
                            $row['status'] = 'approved';
                            if (($row['accounts_head_status'] ?? 'pending') !== 'approved') {
                                $row['approve_role'] = 'accounts_head';
                                $pendingStageLabel = 'Pending Accounts Head';
                                $pendingForText = 'Accounts Head';
                            } else {
                                $row['approve_role'] = 'accounts';
                                $pendingStageLabel = 'Pending Accounts Payout';
                                $pendingForText = 'Accounts & Finance';
                            }
                        } elseif (in_array($vStatus, ['paid', 'completed', 'fully paid', 'fully_paid'])) {
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif (in_array($vStatus, ['partially paid', 'partially_paid'])) {
                            $row['status'] = 'partially_paid';
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($vStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if (in_array($vStatus, ['paid', 'completed', 'fully paid', 'fully_paid', 'partially paid', 'partially_paid'])) {
                            $payoutStatus = in_array($vStatus, ['partially paid', 'partially_paid']) ? 'Partially Paid' : 'Paid & Settled';
                            $payoutAmount = (float) $vRow['app_amt'];
                            $payoutDate = $vRow['updated_at'];
                            $cftQ = $this->db->query("SELECT processed_by FROM con_financial_transactions WHERE ref_type = 'vendor_payment' AND ref_id = {$sourceId} ORDER BY id DESC LIMIT 1");
                            if ($cftQ && $cftR = $cftQ->fetch_assoc()) {
                                if (!empty($cftR['processed_by']) && $d = $this->getDisburserInfo($cftR['processed_by'])) {
                                    $row['disbursed_by_name'] = $d['full_name'];
                                    $row['disbursed_by_code'] = $d['employee_id'];
                                    $row['disbursed_branch_name'] = $d['branch_name'];
                                    $row['disbursed_branch_id'] = $d['branch_id'];
                                }
                            }
                        } else if (in_array($vStatus, ['approved', 'management approved', 'management_approved']) || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float) $vRow['app_amt'];
                        }
                    }
                }
            } else if ($sourceTable === 'project_other_costs') {
                $pocSql = "SELECT poc.*, 
                                  p.name as project_name,
                                  e.reporting_user_id, 
                                  u_dh.full_name as dh_boss_name,
                                  u_mgmt.full_name as mgmt_approver_name,
                                  des_mgmt.name as mgmt_approver_des,
                                  u_payer.full_name as payer_name
                           FROM project_other_costs poc 
                           LEFT JOIN projects p ON p.id = poc.project_id
                           LEFT JOIN employees e ON e.user_id = poc.created_by_id AND e.deleted_at IS NULL
                           LEFT JOIN users u_dh ON u_dh.id = e.reporting_user_id
                           LEFT JOIN users u_mgmt ON u_mgmt.id = poc.management_apporved_by_id
                           LEFT JOIN employees e_mgmt ON (e_mgmt.user_id = u_mgmt.id OR e_mgmt.id = u_mgmt.employee_id)
                           LEFT JOIN designations des_mgmt ON des_mgmt.id = COALESCE(e_mgmt.designation_id, u_mgmt.designation_id)
                           LEFT JOIN users u_payer ON u_payer.id = poc.payment_by_id
                           WHERE poc.id = ? LIMIT 1";
                $pocStmt = $this->db->prepare($pocSql);
                if ($pocStmt) {
                    $pocStmt->bind_param("i", $sourceId);
                    $pocStmt->execute();
                    $pocRow = $pocStmt->get_result()->fetch_assoc();
                    $pocStmt->close();
                    if ($pocRow) {
                        $row['project_name'] = $pocRow['project_name'] ?? null;
                        if (!empty($pocRow['project_name']) && strpos($row['title'], $pocRow['project_name']) === false) {
                            $row['title'] .= ' | ' . $pocRow['project_name'];
                        }
                        if (!empty($pocRow['mgmt_approver_name'])) {
                            $row['approver_name'] = $pocRow['mgmt_approver_name'];
                            $row['approver_designation'] = $pocRow['mgmt_approver_des'] ?? 'Management';
                        }
                        if (!empty($pocRow['approved_amount'])) {
                            $row['approved_amount'] = (float)$pocRow['approved_amount'];
                        }

                        $pocStatus = strtolower($pocRow['status'] ?? '');
                        $dhStatus = strtolower($pocRow['dept_head_status'] ?? '');
                        if ($pocStatus === 'rejected' || $dhStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        } elseif ($pocStatus === 'pending' && $dhStatus === 'pending') {
                            $row['approve_role'] = 'dept_head';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Dept Head';
                            $pendingForText = !empty($pocRow['dh_boss_name']) ? 'Dept Head (' . $pocRow['dh_boss_name'] . ')' : 'Department Head';
                        } elseif (in_array($pocStatus, ['submitted', 'pending', ''])) {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head';
                        } elseif ($pocStatus === 'qc_approved') {
                            $isOver100k = ((float)($row['request_amount'] ?? 0) > 10000.01);
                            $isOpHeadApproved = (!empty($row['op_head_status']) && strtolower($row['op_head_status']) === 'approved');
                            $rowAhStatus = strtolower($row['accounts_head_status'] ?? 'pending');
                            if (!$isOver100k && $isOpHeadApproved) {
                                $row['status'] = 'approved';
                                if ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $pendingStageLabel = 'Pending Accounts Head';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'accounts';
                                    $pendingStageLabel = 'Pending Accounts Payout';
                                    $pendingForText = 'Accounts & Finance';
                                }
                            } elseif ($isOver100k) {
                                if (!$isOpHeadApproved) {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Op Head (>10K)';
                                    $pendingForText = 'Admin & Operation Head';
                                } elseif ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Accounts Head (>10K)';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Management (>10K)';
                                    $pendingForText = 'Management';
                                }
                            } else {
                                $row['approve_role'] = 'management';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending Management';
                                $pendingForText = 'Management / Operation Head';
                            }
                        } elseif ($pocStatus === 'management_approved' || $pocStatus === 'approved') {
                            $row['status'] = 'approved';
                            if (($row['accounts_head_status'] ?? 'pending') !== 'approved') {
                                $row['approve_role'] = 'accounts_head';
                                $pendingStageLabel = 'Pending Accounts Head';
                                $pendingForText = 'Accounts Head';
                            } else {
                                $row['approve_role'] = 'accounts';
                                $pendingStageLabel = 'Pending Accounts Payout';
                                $pendingForText = 'Accounts & Finance';
                            }
                            $payoutStatus = 'Pending Accounts Payout';
                        } elseif ($pocStatus === 'paid') {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) (!empty($pocRow['payment_amount']) ? $pocRow['payment_amount'] : (!empty($pocRow['approved_amount']) ? $pocRow['approved_amount'] : $pocRow['request_amount']));
                            $payoutDate = $pocRow['payment_at'] ?? $pocRow['updated_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = !empty($pocRow['payer_name']) ? 'Paid by ' . $pocRow['payer_name'] : 'Settled';
                        }

                        if ($pocRow['status'] === 'paid') {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) (!empty($pocRow['payment_amount']) ? $pocRow['payment_amount'] : (!empty($pocRow['approved_amount']) ? $pocRow['approved_amount'] : $pocRow['request_amount']));
                            $payoutDate = $pocRow['payment_at'] ?? $pocRow['updated_at'];
                            if (!empty($pocRow['payment_by_id']) && $d = $this->getDisburserInfo($pocRow['payment_by_id'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if ($row['status'] === 'approved' || $pocStatus === 'management_approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                        }
                    }
                }
            } else if ($sourceTable === 'fund_requisitions') {
                $frSql = "SELECT status, amount, approved_amount, qc_approved_amount, paid_amount, paid_at, paid_by FROM fund_requisitions WHERE id = ? LIMIT 1";
                $frStmt = $this->db->prepare($frSql);
                if ($frStmt) {
                    $frStmt->bind_param("i", $sourceId);
                    $frStmt->execute();
                    $frRow = $frStmt->get_result()->fetch_assoc();
                    $frStmt->close();
                    if ($frRow) {
                        $row['qc_approved_amount'] = (float)($frRow['qc_approved_amount'] ?? 0);
                        if (!empty($frRow['approved_amount']) && (float)$frRow['approved_amount'] > 0) {
                            $row['approved_amount'] = (float)$frRow['approved_amount'];
                        } elseif (!empty($frRow['qc_approved_amount']) && (float)$frRow['qc_approved_amount'] > 0) {
                            $row['approved_amount'] = (float)$frRow['qc_approved_amount'];
                        }
                        $frStatus = strtolower($frRow['status'] ?? '');
                        if ($frStatus === 'pending_dept') {
                            $row['approve_role'] = 'dept_head';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Dept Head';
                            $pendingForText = 'Department Head / Reporting Boss';
                        } elseif ($frStatus === 'pending_qc') {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head';
                        } elseif ($frStatus === 'pending') {
                            $isOver100k = ((float)($row['request_amount'] ?? 0) > 10000.01);
                            $isOpHeadApproved = (!empty($row['op_head_status']) && strtolower($row['op_head_status']) === 'approved');
                            $rowAhStatus = strtolower($row['accounts_head_status'] ?? 'pending');
                            if (!$isOver100k && $isOpHeadApproved) {
                                $row['status'] = 'approved';
                                if ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $pendingStageLabel = 'Pending Accounts Head';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'accounts';
                                    $pendingStageLabel = 'Pending Accounts Payout';
                                    $pendingForText = 'Accounts & Finance';
                                }
                            } elseif ($isOver100k) {
                                if (!$isOpHeadApproved) {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Op Head (>10K)';
                                    $pendingForText = 'Admin & Operation Head';
                                } elseif ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Accounts Head (>10K)';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Management (>10K)';
                                    $pendingForText = 'Management';
                                }
                            } else {
                                $row['approve_role'] = 'management';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending Management';
                                $pendingForText = 'Management / Operation Head';
                            }
                        } elseif ($frStatus === 'approved') {
                            $row['status'] = 'approved';
                            if (($row['accounts_head_status'] ?? 'pending') !== 'approved') {
                                $row['approve_role'] = 'accounts_head';
                                $pendingStageLabel = 'Pending Accounts Head';
                                $pendingForText = 'Accounts Head';
                            } else {
                                $row['approve_role'] = 'accounts';
                                $pendingStageLabel = 'Pending Accounts Payout';
                                $pendingForText = 'Accounts & Finance';
                            }
                        } elseif ($frStatus === 'partially_approved') {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'partially_approved';
                        } elseif ($frStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if ($frRow['status'] === 'paid' || $frRow['status'] === 'partially_paid' || !empty($frRow['paid_by'])) {
                            $payoutStatus = ($frRow['status'] === 'partially_paid') ? 'Partially Paid' : 'Paid & Settled';
                            $payoutAmount = !empty($frRow['paid_amount']) ? (float)$frRow['paid_amount'] : (float)$frRow['amount'];
                            $payoutDate = $frRow['paid_at'];
                            $pendingStageLabel = ($frRow['status'] === 'partially_paid') ? 'Partially Paid' : 'Paid & Settled';
                            $pendingForText = ($frRow['status'] === 'partially_paid') ? 'Accounts & Finance' : 'Settled';
                            if (!empty($frRow['paid_by']) && $d = $this->getDisburserInfo($frRow['paid_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if ($frRow['status'] === 'approved' || $frRow['status'] === 'partially_approved' || $row['status'] === 'approved' || $row['status'] === 'partial') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                            if ($frRow['status'] === 'partially_approved') {
                                $pendingStageLabel = 'Partially Approved (Mgmt)';
                                $frAhSt = strtolower((string)($row['accounts_head_status'] ?? 'pending'));
                                $pendingForText = ($frAhSt === 'approved')
                                    ? 'Accounts & Finance (Approved Part) / Management (Remaining)'
                                    : 'Accounts Head (Approved Part) / Management (Remaining)';
                            }
                        }

                        // Management / Operation Head approval breakdown (who approved how much)
                        if (in_array($frRow['status'], ['approved', 'partially_approved', 'paid', 'partially_paid'], true)) {
                            require_once __DIR__ . '/FundRequisitionApprovalLog.php';
                            try {
                                $row['mgmt_approvals'] = \FundRequisitionApprovalLog::getManagementApprovals($this->db, (int)$sourceId);
                            } catch (\Throwable $t) {
                                $row['mgmt_approvals'] = [];
                            }
                            $row['fr_status'] = $frRow['status'];
                            $row['fr_requested_amount'] = (float)$frRow['amount'];
                            $row['fr_mgmt_approved_total'] = (float)($frRow['approved_amount'] ?? 0);
                            $row['fr_mgmt_remaining'] = max(0, (float)$frRow['amount'] - (float)($frRow['approved_amount'] ?? 0));
                        }
                    }
                }
            } else if ($sourceTable === 'land_fund_requisitions' || $sourceTable === 'con_land_requisitions') {
                $lfrSql = "SELECT lfr.*, u_acc.full_name as accounts_name, u_mgmt.full_name as mgmt_name
                           FROM land_fund_requisitions lfr
                           LEFT JOIN users u_acc ON lfr.accounts_by = u_acc.id
                           LEFT JOIN users u_mgmt ON lfr.mgmt_by = u_mgmt.id
                           WHERE lfr.id = ? LIMIT 1";
                $lfrStmt = $this->db->prepare($lfrSql);
                if ($lfrStmt) {
                    $lfrStmt->bind_param("i", $sourceId);
                    $lfrStmt->execute();
                    $lfrRow = $lfrStmt->get_result()->fetch_assoc();
                    $lfrStmt->close();
                    if ($lfrRow) {
                        $mgmtSt = $lfrRow['mgmt_status'] ?? 'Pending';
                        $accSt = $lfrRow['accounts_status'] ?? 'Pending';
                        $row['approved_amount'] = (float)($lfrRow['requested_amount'] ?? 0);

                        if ($mgmtSt === 'Rejected' || $accSt === 'Rejected') {
                            $row['status'] = 'rejected';
                            $row['approve_role'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        } elseif ($accSt === 'Paid') {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$lfrRow['requested_amount'];
                            $payoutDate = $lfrRow['accounts_paid_at'] ?? $lfrRow['updated_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = !empty($lfrRow['accounts_name']) ? 'Paid by ' . $lfrRow['accounts_name'] : 'Settled';
                            if (!empty($lfrRow['accounts_by']) && $d = $this->getDisburserInfo($lfrRow['accounts_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } elseif ($mgmtSt === 'Approved') {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'accounts';
                            $payoutStatus = 'Pending Accounts Payout';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } else {
                            $row['status'] = 'pending';
                            $row['approve_role'] = 'management';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management Approval';
                        }
                    }
                }
            } else if ($sourceTable === 'lead_payments') {
                $lpSql = "SELECT booking_id FROM lead_payments WHERE id = ? LIMIT 1";
                $lpStmt = $this->db->prepare($lpSql);
                if ($lpStmt) {
                    $lpStmt->bind_param("i", $sourceId);
                    $lpStmt->execute();
                    $lpRow = $lpStmt->get_result()->fetch_assoc();
                    $lpStmt->close();
                    if ($lpRow && !empty($lpRow['booking_id'])) {
                        $row['booking_id'] = $lpRow['booking_id'];
                    }
                }
                if ($row['status'] === 'approved') {
                    $payoutStatus = 'Approved';
                    $pendingStageLabel = 'Approved from Management';
                    $pendingForText = 'Management Approved';
                }
            } else if ($sourceTable === 'project_extra_assets') {
                $peaSql = "SELECT payment_status, paid_amount, paid_at, reporting_user_id, dept_head_status, it_head_status, operations_status, purchase_head_status, qc_audit_status, mgmt_status, overall_status FROM project_extra_assets WHERE id = ? LIMIT 1";
                $peaStmt = $this->db->prepare($peaSql);
                if ($peaStmt) {
                    $peaStmt->bind_param("i", $sourceId);
                    $peaStmt->execute();
                    $peaRow = $peaStmt->get_result()->fetch_assoc();
                    $peaStmt->close();
                    if ($peaRow) {
                        $dhStatus = strtolower($peaRow['dept_head_status'] ?? 'not_required');
                        $itStatus = strtolower($peaRow['it_head_status'] ?? 'not_required');
                        $opStatus = strtolower($peaRow['operations_status'] ?? 'pending');
                        $phStatus = strtolower($peaRow['purchase_head_status'] ?? 'pending');
                        $qcStatus = strtolower($peaRow['qc_audit_status'] ?? 'pending');
                        $peaMgmtStatus = strtolower($peaRow['mgmt_status'] ?? 'pending');
                        $peaOverall = strtolower($peaRow['overall_status'] ?? 'pending');
                        $peaPayStatus = strtolower($peaRow['payment_status'] ?? 'unpaid');

                        if ($peaPayStatus === 'paid' || $peaOverall === 'purchased') {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif ($peaMgmtStatus === 'approved') {
                            $row['approve_role'] = 'accounts';
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($peaMgmtStatus === 'rejected' || $peaOverall === 'rejected' || $qcStatus === 'rejected' || $phStatus === 'rejected' || $opStatus === 'rejected' || $itStatus === 'rejected' || $dhStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $row['approve_role'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        } elseif ($dhStatus === 'pending') {
                            $repId = (int)($peaRow['reporting_user_id'] ?? 0);
                            $row['approve_role'] = $repId > 0 ? ('dept_head_' . $repId) : 'dept_head';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Dept Head';
                            $pendingForText = 'Department Head / Reporting Boss';
                        } elseif ($itStatus === 'pending') {
                            $row['approve_role'] = 'it_head';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending IT Head';
                            $pendingForText = 'IT Head';
                        } elseif ($opStatus === 'pending') {
                            $row['approve_role'] = 'operations';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Operations';
                            $pendingForText = 'Operations Head';
                        } elseif ($phStatus === 'pending') {
                            $row['approve_role'] = 'purchase_head';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Purchase Head';
                            $pendingForText = 'Purchase Head';
                        } elseif ($qcStatus === 'pending') {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head';
                        } else {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management / Operation Head';
                        }

                        if ($peaRow['payment_status'] === 'paid') {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) (!empty($peaRow['paid_amount']) ? $peaRow['paid_amount'] : $row['request_amount']);
                            $payoutDate = $peaRow['paid_at'];
                            if (!empty($peaRow['paid_by']) && $d = $this->getDisburserInfo($peaRow['paid_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if ($row['status'] === 'approved' || $peaMgmtStatus === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                        }
                    }
                }
            } else if ($sourceTable === 'contractor_bill_requests') {
                $cbSql = "SELECT cbr.status, IFNULL(cbr.approved_amount, cbr.bill_amount) AS app_amt, cbr.updated_at,
                                 cbr.verified_by, cbr.verified_at, cbr.purchase_head_approved_by_id, cbr.purchase_head_approved_at,
                                 cbr.qc_approved_by_id, cbr.qc_approved_at, cbr.approved_by,
                                 u_v.full_name AS verified_by_name,
                                 u_ph.full_name AS ph_approved_by_name,
                                 u_qc.full_name AS qc_approved_by_name,
                                 u_mgmt.full_name AS mgmt_approver_name,
                                 des_mgmt.name AS mgmt_approver_des
                          FROM contractor_bill_requests cbr 
                          LEFT JOIN users u_v ON u_v.id = cbr.verified_by
                          LEFT JOIN users u_ph ON u_ph.id = cbr.purchase_head_approved_by_id
                          LEFT JOIN users u_qc ON u_qc.id = cbr.qc_approved_by_id
                          LEFT JOIN users u_mgmt ON u_mgmt.id = cbr.approved_by
                          LEFT JOIN employees e_mgmt ON (e_mgmt.user_id = u_mgmt.id OR e_mgmt.id = u_mgmt.employee_id)
                          LEFT JOIN designations des_mgmt ON des_mgmt.id = COALESCE(e_mgmt.designation_id, u_mgmt.designation_id)
                          WHERE cbr.id = ? LIMIT 1";
                $cbStmt = $this->db->prepare($cbSql);
                if ($cbStmt) {
                    $cbStmt->bind_param("i", $sourceId);
                    $cbStmt->execute();
                    $cbRow = $cbStmt->get_result()->fetch_assoc();
                    $cbStmt->close();
                    if ($cbRow) {
                        if (!empty($cbRow['mgmt_approver_name'])) {
                            $row['approver_name'] = $cbRow['mgmt_approver_name'];
                            $row['approver_designation'] = $cbRow['mgmt_approver_des'] ?? 'Admin & Operation head';
                        }
                        $cbStatus = strtolower($cbRow['status'] ?? '');
                        if (in_array($cbStatus, ['pending', 'submitted'])) {
                            $row['approve_role'] = 'pm';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending PM Approval';
                            $pendingForText = 'Project Manager Head';
                        } elseif ($cbStatus === 'verified') {
                            if (empty($cbRow['purchase_head_approved_by_id'])) {
                                $row['approve_role'] = 'purchase_head';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending Purchase Head';
                                $pendingForText = 'Head of Purchase';
                            } else {
                                $row['approve_role'] = 'qc';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending QC & Audit';
                                $pendingForText = 'QC & Internal Audit Head';
                            }
                        } elseif ($cbStatus === 'qc_approved') {
                            $isOver1Lac = ((float)($row['request_amount'] ?? 0) > 10000.01);
                            $rowOpStatus = strtolower($row['op_head_status'] ?? 'pending');
                            $rowAhStatus = strtolower($row['accounts_head_status'] ?? 'pending');
                            if (!$isOver1Lac) {
                                if ($rowOpStatus !== 'approved') {
                                    $row['approve_role'] = 'operations';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Op Head (≤10K)';
                                    $pendingForText = 'Admin & Operation Head';
                                } elseif ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Accounts Head (≤10K)';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['status'] = 'approved';
                                    $row['approve_role'] = 'accounts';
                                    $pendingStageLabel = 'Pending Accounts Payout';
                                    $pendingForText = 'Accounts & Finance';
                                }
                            } else {
                                if ($rowOpStatus !== 'approved') {
                                    $row['approve_role'] = 'operations';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Op Head (>10K)';
                                    $pendingForText = 'Admin & Operation Head';
                                } elseif ($rowAhStatus !== 'approved') {
                                    $row['approve_role'] = 'accounts_head';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Accounts Head (>10K)';
                                    $pendingForText = 'Accounts Head';
                                } else {
                                    $row['approve_role'] = 'management';
                                    $row['status'] = 'pending';
                                    $pendingStageLabel = 'Pending Management (>10K)';
                                    $pendingForText = 'Management';
                                }
                            }
                        } elseif ($cbStatus === 'approved') {
                            if (($row['accounts_head_status'] ?? '') === 'approved') {
                                $row['approve_role'] = 'accounts';
                                $payoutStatus = 'Pending Accounts Payout';
                                $pendingStageLabel = 'Pending Accounts Payout';
                                $pendingForText = 'Accounts & Finance';
                            } else {
                                $row['approve_role'] = 'accounts_head';
                                $payoutStatus = 'Pending Accounts Head Approval';
                                $pendingStageLabel = 'Pending Accounts Head';
                                $pendingForText = 'Accounts Head';
                            }
                        } elseif (in_array($cbStatus, ['paid', 'fully_paid'])) {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif ($cbStatus === 'partially_paid') {
                            $row['status'] = 'partially_paid';
                            $row['approve_role'] = 'accounts';
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($cbStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if (in_array($cbStatus, ['paid', 'fully_paid', 'partially_paid'])) {
                            $payoutStatus = in_array($cbStatus, ['partially_paid']) ? 'Partially Paid' : 'Paid & Settled';
                            $payoutAmount = (float)$cbRow['app_amt'];
                            $payoutDate = $cbRow['updated_at'];
                            $cftQ = $this->db->query("SELECT processed_by FROM con_financial_transactions WHERE ref_type = 'contractor_bill' AND ref_id = {$sourceId} ORDER BY id DESC LIMIT 1");
                            if ($cftQ && $cftR = $cftQ->fetch_assoc()) {
                                if (!empty($cftR['processed_by']) && $d = $this->getDisburserInfo($cftR['processed_by'])) {
                                    $row['disbursed_by_name'] = $d['full_name'];
                                    $row['disbursed_by_code'] = $d['employee_id'];
                                    $row['disbursed_branch_name'] = $d['branch_name'];
                                    $row['disbursed_branch_id'] = $d['branch_id'];
                                }
                            }
                        } else if ($cbStatus === 'approved') {
                            $payoutAmount = (float)$cbRow['app_amt'];
                            if (($row['accounts_head_status'] ?? '') !== 'approved') {
                                $payoutStatus = 'Pending Accounts Head Approval';
                            } else {
                                $payoutStatus = 'Pending Accounts Payout';
                            }
                        }
                    }
                }
            } else if ($sourceTable === 'cashout_requests') {
                $coSql = "SELECT status, IFNULL(approved_amount, amount) AS app_amt, updated_at, approved_by FROM cashout_requests WHERE id = ? LIMIT 1";
                $coStmt = $this->db->prepare($coSql);
                if ($coStmt) {
                    $coStmt->bind_param("i", $sourceId);
                    $coStmt->execute();
                    $coRow = $coStmt->get_result()->fetch_assoc();
                    $coStmt->close();
                    if ($coRow) {
                        $coStatus = strtolower($coRow['status'] ?? '');
                        if (in_array($coStatus, ['fully_paid', 'paid'])) {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$coRow['app_amt'];
                            $payoutDate = $coRow['updated_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                            if (!empty($coRow['approved_by']) && $d = $this->getDisburserInfo($coRow['approved_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if ($coStatus === 'partially_paid') {
                            $payoutStatus = 'Partially Paid';
                            $payoutAmount = (float)$coRow['app_amt'];
                            $payoutDate = $coRow['updated_at'];
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                            if (!empty($coRow['approved_by']) && $d = $this->getDisburserInfo($coRow['approved_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if ($coStatus === 'approved' || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float)$coRow['app_amt'];
                            $row['approve_role'] = 'accounts';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        }
                    }
                }
                
            } else if ($sourceTable === 'expense_adjustments') {
                $eaSql = "SELECT status, adjustment_type, amount, approved_amount, due_amount, updated_at, approved_by FROM expense_adjustments WHERE id = ? LIMIT 1";
                $eaStmt = $this->db->prepare($eaSql);
                if ($eaStmt) {
                    $eaStmt->bind_param("i", $sourceId);
                    $eaStmt->execute();
                    $eaRow = $eaStmt->get_result()->fetch_assoc();
                    $eaStmt->close();
                    if ($eaRow) {
                        $eaStatus = strtolower($eaRow['status'] ?? '');
                        $adjType = strtolower($eaRow['adjustment_type'] ?? 'extra');
                        $adjAmt = (float)(!empty($eaRow['approved_amount']) ? $eaRow['approved_amount'] : $eaRow['amount']);
                        
                        if ($adjType === 'return') {
                            if ($eaStatus === 'fully_paid') {
                                $row['status'] = 'approved';
                                $row['approve_role'] = 'completed';
                                $payoutStatus = 'Paid & Settled';
                                $payoutAmount = (float)$eaRow['amount'];
                                $payoutDate = $eaRow['updated_at'];
                                $pendingStageLabel = 'Verified & Received';
                                $pendingForText = 'Accounts Verified';
                                if (!empty($eaRow['approved_by']) && $d = $this->getDisburserInfo($eaRow['approved_by'])) {
                                    $row['disbursed_by_name'] = $d['full_name'];
                                    $row['disbursed_by_code'] = $d['employee_id'];
                                    $row['disbursed_branch_name'] = $d['branch_name'];
                                    $row['disbursed_branch_id'] = $d['branch_id'];
                                }
                            } elseif ($eaStatus === 'partially_paid') {
                                $row['status'] = 'partially_paid';
                                $payoutStatus = 'Partially Paid';
                                $payoutAmount = (float)($eaRow['amount'] - ($eaRow['due_amount'] ?? 0));
                                $payoutDate = $eaRow['updated_at'];
                                $pendingStageLabel = 'Partially Received';
                                $pendingForText = 'Accounts & Finance';
                                if (!empty($eaRow['approved_by']) && $d = $this->getDisburserInfo($eaRow['approved_by'])) {
                                    $row['disbursed_by_name'] = $d['full_name'];
                                    $row['disbursed_by_code'] = $d['employee_id'];
                                    $row['disbursed_branch_name'] = $d['branch_name'];
                                    $row['disbursed_branch_id'] = $d['branch_id'];
                                }
                            } elseif ($eaStatus === 'rejected') {
                                $row['status'] = 'rejected';
                                $pendingStageLabel = 'Rejected';
                                $pendingForText = 'Rejected';
                            } else {
                                $row['approve_role'] = 'accounts';
                                $row['status'] = 'pending';
                                $payoutStatus = 'Pending Accounts Verification';
                                $pendingStageLabel = 'Pending Accounts Verification';
                                $pendingForText = 'Accounts Verification (Cash-In)';
                            }
                        } else {
                            // Extra money request
                            if ($eaStatus === 'pending') {
                                $row['approve_role'] = 'qc';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending QC & Audit';
                                $pendingForText = 'QC & Internal Audit Head';
                            } elseif ($eaStatus === 'qc_approved') {
                                $row['approve_role'] = 'management';
                                $row['status'] = 'pending';
                                $pendingStageLabel = 'Pending Management';
                                $pendingForText = 'Management / Role 20';
                            } elseif ($eaStatus === 'approved') {
                                $row['approve_role'] = 'accounts';
                                $row['status'] = 'approved';
                                $payoutStatus = 'Pending Accounts Payout';
                                $pendingStageLabel = 'Pending Accounts Payout';
                                $pendingForText = 'Accounts & Finance';
                            } elseif ($eaStatus === 'fully_paid') {
                                $row['status'] = 'approved';
                                $row['approve_role'] = 'completed';
                                $payoutStatus = 'Paid & Settled';
                                $payoutAmount = $adjAmt;
                                $payoutDate = $eaRow['updated_at'];
                                $pendingStageLabel = 'Paid & Settled';
                                $pendingForText = 'Settled';
                                if (!empty($eaRow['approved_by']) && $d = $this->getDisburserInfo($eaRow['approved_by'])) {
                                    $row['disbursed_by_name'] = $d['full_name'];
                                    $row['disbursed_by_code'] = $d['employee_id'];
                                    $row['disbursed_branch_name'] = $d['branch_name'];
                                    $row['disbursed_branch_id'] = $d['branch_id'];
                                }
                            } elseif ($eaStatus === 'partially_paid') {
                                $row['status'] = 'partially_paid';
                                $payoutStatus = 'Partially Paid';
                                $payoutAmount = (float)($adjAmt - ($eaRow['due_amount'] ?? 0));
                                $payoutDate = $eaRow['updated_at'];
                                $pendingStageLabel = 'Partially Paid';
                                $pendingForText = 'Accounts & Finance';
                                if (!empty($eaRow['approved_by']) && $d = $this->getDisburserInfo($eaRow['approved_by'])) {
                                    $row['disbursed_by_name'] = $d['full_name'];
                                    $row['disbursed_by_code'] = $d['employee_id'];
                                    $row['disbursed_branch_name'] = $d['branch_name'];
                                    $row['disbursed_branch_id'] = $d['branch_id'];
                                }
                            } elseif ($eaStatus === 'rejected') {
                                $row['status'] = 'rejected';
                                $pendingStageLabel = 'Rejected';
                                $pendingForText = 'Rejected';
                            }
                        }
                    }
                }
            } else if ($sourceTable === 'advance_salaries') {
                $asSql = "SELECT status, amount, updated_at, created_by FROM advance_salaries WHERE id = ? LIMIT 1";
                $asStmt = $this->db->prepare($asSql);
                if ($asStmt) {
                    $asStmt->bind_param("i", $sourceId);
                    $asStmt->execute();
                    $asRow = $asStmt->get_result()->fetch_assoc();
                    $asStmt->close();
                    if ($asRow) {
                        $asStatus = strtolower($asRow['status'] ?? '');
                        if (in_array($asStatus, ['paid', 'deducted'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$asRow['amount'];
                            $payoutDate = $asRow['updated_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                            if (!empty($asRow['created_by']) && $d = $this->getDisburserInfo($asRow['created_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if (in_array($asStatus, ['approved', 'pending_accounts']) || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float)$asRow['amount'];
                            $row['approve_role'] = 'accounts';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } else if ($asStatus === 'pending_hr') {
                            $row['approve_role'] = 'hr_head';
                            $pendingStageLabel = 'Pending HR Approval';
                            $pendingForText = 'HR Head';
                        }
                    }

                }
            } else if ($sourceTable === 'payslips') {
                $psSql = "SELECT net_salary, disbursed_by, disbursed_at, status, payment_status FROM payslips WHERE id = ? LIMIT 1";
                $psStmt = $this->db->prepare($psSql);
                if ($psStmt) {
                    $psStmt->bind_param("i", $sourceId);
                    $psStmt->execute();
                    $psRow = $psStmt->get_result()->fetch_assoc();
                    $psStmt->close();
                    if ($psRow) {
                        $isPsPaid = ($psRow['status'] === 'paid' || $psRow['payment_status'] === 'paid' || !empty($psRow['disbursed_by']));
                        if ($isPsPaid) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$psRow['net_salary'];
                            $payoutDate = $psRow['disbursed_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                            if (!empty($psRow['disbursed_by']) && $d = $this->getDisburserInfo($psRow['disbursed_by'])) {
                                $row['disbursed_by_name'] = $d['full_name'];
                                $row['disbursed_by_code'] = $d['employee_id'];
                                $row['disbursed_branch_name'] = $d['branch_name'];
                                $row['disbursed_branch_id'] = $d['branch_id'];
                            }
                        } else if ($row['status'] === 'approved') {
                            if (($row['accounts_head_status'] ?? '') === 'approved') {
                                $payoutStatus = 'Pending Accounts Payout';
                                $pendingStageLabel = 'Pending Accounts Payout';
                                $pendingForText = 'Accounts & Finance';
                            } else {
                                $payoutStatus = 'Pending Accounts Head Approval';
                                $pendingStageLabel = 'Pending Accounts Head';
                                $pendingForText = 'Accounts Head';
                            }
                        } else if (in_array(strtolower($row['status'] ?? ''), ['pending', ''])) {
                            if (($row['accounts_head_status'] ?? '') === 'approved') {
                                $pendingStageLabel = 'Pending Management';
                                $pendingForText = 'Management';
                            } else {
                                $pendingStageLabel = 'Pending Mgmt & Accts Head';
                                $pendingForText = 'Management / Accounts Head';
                            }
                        }
                    }
                }
            } else if ($row['status'] === 'approved' && $sourceTable !== 'lead_payments') {
                if (($row['accounts_head_status'] ?? '') === 'approved') {
                    $payoutStatus = 'Pending Accounts Payout';
                    $pendingStageLabel = 'Pending Accounts Payout';
                    $pendingForText = 'Accounts & Finance';
                } else {
                    $payoutStatus = 'Pending Accounts Head Approval';
                    $pendingStageLabel = 'Pending Accounts Head';
                    $pendingForText = 'Accounts Head';
                }
            } else if ($row['status'] === 'approved') {
                $payoutStatus = 'Approved';
                $pendingStageLabel = 'Approved from Management';
                $pendingForText = 'Approved';
            } elseif ($row['status'] === 'rejected') {
                $pendingStageLabel = 'Rejected';
                $pendingForText = 'Rejected';
            }

            $row['payout_status'] = $payoutStatus;
            $row['payout_amount'] = $payoutAmount;
            $row['payout_date'] = $payoutDate;
            $row['pending_stage_label'] = $pendingStageLabel;
            $row['pending_for_text'] = $pendingForText;

            $list[] = $row; 
        }

        if (isset($stmt) && $stmt) {
            $stmt->close();
        }

        // --- Role-based Visibility Filtering ("jar jeita pending shudu she oigulo dekhbe") ---
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $userRoleId = (int)($filters['role_id'] ?? ($_SESSION['user']['role_id'] ?? 0));
        $userId = (int)($filters['user_id'] ?? ($_SESSION['user']['id'] ?? 0));
        $sessionRoleName = strtolower(trim($filters['role_name'] ?? ($_SESSION['user']['role_name'] ?? '')));

        $isChiefOfficer = (in_array($userRoleId, [33, 34, 35], true) || in_array($sessionRoleName, ['chief officer', 'management viewer', 'seal ceo'], true));

        // Full Management: Super Admin (3), Admin (1)
        $isFullManagement = !$isChiefOfficer && (in_array($userRoleId, [1, 3, 38], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true));

        // Site Operator / Admin & Operation Head: Role 20, Role 32
        $isRole20 = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;

        // Accounts Head: Role 30 | Accounts: Role 6
        $isAccountsHead = ($userRoleId === 30) || strpos($sessionRoleName, 'accounts head') !== false;
        $isAccounts = ($userRoleId === 6) || (strpos($sessionRoleName, 'account') !== false && !$isAccountsHead);

        // HR: Role 4
        $isHR = ($userRoleId === 4) || strpos($sessionRoleName, 'hr') !== false;

        // QC & Internal Audit Head: Role 13, 24, 25
        $isQC = in_array($userRoleId, [13, 24, 25], true) || strpos($sessionRoleName, 'qc') !== false || strpos($sessionRoleName, 'audit') !== false;

        // Purchase Lead: Role 17, 23, 31
        $isPurchase = in_array($userRoleId, [17, 23, 31], true) || strpos($sessionRoleName, 'purchase') !== false;
        $isPurchaseHead = ($userRoleId === 17);

        // PM / PM Head: Role 15, 22
        $isPM = in_array($userRoleId, [15, 22], true) || strpos($sessionRoleName, 'project manager') !== false;

        // Fetch subordinates reporting directly to this user
        $subordinateUserIds = [];
        $subRes = $this->db->query("
            SELECT u.id 
            FROM users u 
            JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            WHERE e.reporting_user_id = {$userId} AND e.deleted_at IS NULL
        ");
        if ($subRes) {
            while ($sRow = $subRes->fetch_assoc()) {
                $subordinateUserIds[] = (int)$sRow['id'];
            }
        }
        $isReportingBoss = !empty($subordinateUserIds);
        $isDesignatedDeptHead = in_array($userRoleId, [5, 9, 10, 14], true) || (strpos($sessionRoleName, 'head') !== false && !in_array($userRoleId, [8, 11, 12, 16, 18, 19, 21, 23, 25]));

        $isActionableByCurrentUser = function($item) use ($isChiefOfficer, $isFullManagement, $isRole20, $isAccountsHead, $isAccounts, $isHR, $isQC, $isPurchase, $isPurchaseHead, $isPM, $isReportingBoss, $isDesignatedDeptHead, $subordinateUserIds, $userId) {
            // Chief Officer cannot approve anything
            if ($isChiefOfficer) {
                return false;
            }

            $status = strtolower($item['status'] ?? '');
            $isPending = in_array($status, ['pending', 'partially_approved', '']);
            $approveRole = strtolower($item['approve_role'] ?? 'management');
            if (empty($approveRole)) {
                $approveRole = 'management';
            }
            $payoutStatus = $item['payout_status'] ?? '';
            $amount = (float)($item['request_amount'] ?? 0);
            $ahStatus = strtolower($item['accounts_head_status'] ?? 'pending');

            $opStatus = strtolower($item['op_head_status'] ?? 'pending');
            $isOver10k = ($amount > 10000.01);
            $srcTbl = strtolower($item['source_table'] ?? '');

            // 1. Accounts Head (Role 30): Actionable = items pending Accounts Head approval OR approved and awaiting payout
            if ($isAccountsHead) {
                if ($payoutStatus === 'Paid & Settled' || $status === 'paid' || $status === 'rejected') {
                    return false;
                }
                // Pending Accounts Head approval (Step 2)
                if ($ahStatus !== 'approved') {
                    if ($srcTbl === 'payslips') {
                        return true;
                    }
                    if (in_array($srcTbl, ['vendor_payment_requests', 'contractor_bill_requests'], true)) {
                        return ($opStatus === 'approved' || $approveRole === 'accounts_head' || $status === 'approved');
                    }
                    if ($approveRole === 'accounts_head') {
                        return true;
                    }
                    if ($isOver10k && $opStatus === 'approved') {
                        return true;
                    }
                    if (!$isOver10k && ($opStatus === 'approved' || in_array($status, ['approved', 'partially_approved']) || $approveRole === 'accounts_head')) {
                        return true;
                    }
                }
                // Legacy: status = approved but Accounts Head hasn't signed off yet
                if ($status === 'approved' && ($ahStatus !== 'approved' || $approveRole === 'accounts_head')) {
                    return true;
                }
                // Accounts Head already approved and awaiting payout
                if ($ahStatus === 'approved' && (in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid']) || $approveRole === 'accounts')) {
                    return true;
                }
                return false;
            }

            // 1b. Accounts (Role 6): Only items approved by Accounts Head and awaiting Accounts payout/disbursement
            if ($isAccounts) {
                if ($payoutStatus === 'Paid & Settled' || $status === 'paid' || $status === 'rejected') {
                    return false;
                }
                if ($ahStatus !== 'approved') {
                    return false;
                }
                if (in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid'])) {
                    return true;
                }
                if ($approveRole === 'accounts' && in_array($status, ['pending', 'approved', 'partially_approved'])) {
                    return true;
                }
                return false;
            }

            // 2. Role 20 (DGM / Admin & Operation Head):
            if ($isRole20) {
                if (!$isPending) return false;
                if ($srcTbl === 'payslips') return false;
                if (!in_array($approveRole, ['management', 'operations', 'accounts_head', ''])) return false;
                return ($opStatus !== 'approved');
            }

            // 3. Full Management (Super Admin 3, Admin 1):
            if ($isFullManagement) {
                if (!$isPending) return false;
                if (!in_array($approveRole, ['management', 'operations', '']) && $srcTbl !== 'payslips') return false;
                
                // For VPR / CBR <= 10,000: goes Op Head -> Accounts Head -> Disburse (no DCEO step unless overriding via All Pending)
                if (!$isOver10k && in_array($srcTbl, ['vendor_payment_requests', 'contractor_bill_requests'], true)) {
                    return false;
                }
                // For requests > ৳10,000 (except payslips which use 2-tier Management + Accounts Head approval):
                // Management is ONLY actionable in My Actionable after BOTH Op Head AND Accounts Head have approved!
                if ($isOver10k && $srcTbl !== 'payslips') {
                    return ($opStatus === 'approved' && $ahStatus === 'approved');
                }
                return true;
            }

            // 4. QC & Internal Audit Head (Role 24, 13, 25):
            if ($isQC) {
                return ($isPending && in_array($approveRole, ['qc', 'qc_head']));
            }

            // 4b. PM / PM Head (Role 15, 22):
            if ($isPM) {
                return ($isPending && in_array($approveRole, ['pm', 'pm_head']));
            }

            // 5. Purchase Head (Role 17) & Purchase Team Leader / Executive (Role 23, 31):
            if ($isPurchaseHead) {
                return ($isPending && in_array($approveRole, ['purchase_head', 'purchase team leader', 'purchase', 'purchase_lead']));
            }
            if ($isPurchase) {
                return ($isPending && in_array($approveRole, ['purchase team leader', 'purchase', 'purchase_lead']));
            }

            // 6. HR (Role 4):
            if ($isHR) {
                return ($isPending && in_array($approveRole, ['hr', 'hr_head']));
            }

            // 7. Dept Head / Reporting Boss:
            if ($isPending && ($approveRole === 'dept_head' || $approveRole === 'dept_head_' . $userId)) {
                if ($isFullManagement) {
                    return true;
                }
                $reqUid = (int)($item['requested_by'] ?? 0);
                if ($isReportingBoss && in_array($reqUid, $subordinateUserIds, true)) {
                    return true;
                }
                if ($approveRole === 'dept_head_' . $userId) {
                    return true;
                }
                return false;
            }

            return false;
        };

        $statusFilter = strtolower(trim($filters['status'] ?? ''));

        // Visibility Filtering:
        // Default (or statusFilter = 'pending' or empty):
        // Show ONLY what is actionable pending for the logged-in user! Other roles' pending lists and already approved/paid records are hidden!
        if (empty($statusFilter) || $statusFilter === 'pending' || $statusFilter === 'my_pending') {
            $filteredList = [];
            foreach ($list as $item) {
                if ($isActionableByCurrentUser($item)) {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        } elseif ($statusFilter === 'approved') {
            $filteredList = [];
            foreach ($list as $item) {
                $st = strtolower($item['status'] ?? '');
                $pst = $item['payout_status'] ?? '';
                if ($st === 'approved' || $st === 'paid' || in_array($pst, ['Paid & Settled', 'Approved', 'Partially Paid'])) {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        } elseif ($statusFilter === 'rejected') {
            $filteredList = [];
            foreach ($list as $item) {
                if (strtolower($item['status'] ?? '') === 'rejected') {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        } elseif ($statusFilter === 'all_pending') {
            // Audit view for Management: All pending requests across all departments
            $filteredList = [];
            foreach ($list as $item) {
                $st = strtolower($item['status'] ?? '');
                $pst = $item['payout_status'] ?? '';
                $ahSt = strtolower($item['accounts_head_status'] ?? 'pending');
                if (in_array($st, ['pending', 'partially_approved', '']) || $ahSt !== 'approved' || in_array($pst, ['Pending Accounts Head Approval', 'Pending Accounts Payout', 'Partially Paid'])) {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        }
        // If $statusFilter === 'all', keep all items for complete company audit

        // Sort: Most recently updated/actionable requests first (updated_at DESC, id DESC)
        usort($list, function($a, $b) {
            $timeA = strtotime(!empty($a['updated_at']) ? $a['updated_at'] : ($a['requested_at'] ?? ''));
            $timeB = strtotime(!empty($b['updated_at']) ? $b['updated_at'] : ($b['requested_at'] ?? ''));
            if ($timeA === $timeB) {
                return ((int)$b['id']) <=> ((int)$a['id']);
            }
            return $timeB <=> $timeA;
        });

        return $list;

        return $list;
    }

    public function syncMissingFundRequisitions()
    {
        // 1. Insert missing requisitions
        $sql = "
            SELECT fr.*, d.name AS dept_name
            FROM fund_requisitions fr
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'fund_requisitions' AND ar.source_id = fr.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId = (int) $row['id'];
                $deptName = !empty($row['dept_name']) ? $row['dept_name'] : 'General';
                $requestType = $row['requisition_type'];
                $title = "Fund Requisition: " . (mb_strlen($row['purpose']) > 80 ? mb_substr($row['purpose'], 0, 80) . '...' : $row['purpose']);
                $amount = (float) $row['amount'];
                $requestedBy = (int) $row['requested_by'];
                
                $frStatus = $row['status'];
                $approveRole = 'management';
                if ($frStatus === 'pending_dept') {
                    $approveRole = 'dept_head';
                    $status = 'pending';
                } elseif ($frStatus === 'pending_qc') {
                    $approveRole = 'qc';
                    $status = 'pending';
                } elseif ($frStatus === 'pending') {
                    $approveRole = 'management';
                    $status = 'pending';
                } elseif ($frStatus === 'approved') {
                    $approveRole = 'accounts';
                    $status = 'approved';
                } elseif ($frStatus === 'partially_approved') {
                    // Approved part -> Accounts Head / Accounts; remaining balance stays with Management
                    $approveRole = 'management';
                    $status = 'partially_approved';
                } elseif (in_array($frStatus, ['paid', 'partially_paid'])) {
                    $approveRole = 'accounts';
                    $status = ($frStatus === 'paid') ? 'approved' : 'partially_paid';
                } elseif ($frStatus === 'rejected') {
                    $approveRole = 'management';
                    $status = 'rejected';
                } else {
                    $approveRole = 'management';
                    $status = 'pending';
                }

                $approvedBy = !empty($row['approved_by']) ? (int) $row['approved_by'] : null;
                $approvedAt = !empty($row['approved_at']) ? $row['approved_at'] : null;
                $approvalNote = !empty($row['approval_note']) ? $row['approval_note'] : null;
                $metaData = json_encode(['purpose' => $row['purpose'], 'category' => $row['category']]);
                $createdAt = $row['created_at'];

                $initAppAmt = (!empty($row['approved_amount']) && (float)$row['approved_amount'] > 0)
                    ? (float)$row['approved_amount']
                    : ((!empty($row['qc_approved_amount']) && (float)$row['qc_approved_amount'] > 0)
                        ? (float)$row['qc_approved_amount']
                        : (in_array($status, ['approved', 'partially_paid']) ? $amount : 0.00));

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'fund_requisitions', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $initAppAmt, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing fund_requisitions
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN fund_requisitions fr ON (ar.source_table = 'fund_requisitions' AND ar.source_id = fr.id)
            SET ar.approve_role = CASE 
                    WHEN fr.status = 'pending_dept' THEN 'dept_head'
                    WHEN fr.status = 'pending_qc' THEN 'qc'
                    WHEN fr.status = 'pending' THEN 'management'
                    WHEN fr.status = 'approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN fr.status = 'partially_approved' THEN 'management'
                    WHEN fr.status IN ('paid', 'partially_paid') THEN 'completed'
                    WHEN fr.status = 'rejected' THEN 'management'
                    ELSE ar.approve_role 
                END,
                ar.status = CASE 
                    WHEN fr.status IN ('pending_dept', 'pending_qc', 'pending') THEN 'pending'
                    WHEN fr.status IN ('approved', 'paid') THEN 'approved'
                    WHEN fr.status = 'partially_approved' THEN 'partially_approved'
                    WHEN fr.status = 'partially_paid' THEN 'partially_paid'
                    WHEN fr.status = 'rejected' THEN 'rejected'
                    ELSE 'pending'
                END,
                ar.approved_by = fr.approved_by,
                ar.approved_at = fr.approved_at,
                ar.approval_note = fr.approval_note,
                ar.request_amount = fr.amount,
                ar.approved_amount = CASE 
                    WHEN fr.approved_amount IS NOT NULL AND fr.approved_amount > 0 THEN fr.approved_amount
                    WHEN fr.qc_approved_amount IS NOT NULL AND fr.qc_approved_amount > 0 THEN fr.qc_approved_amount
                    WHEN fr.status IN ('approved', 'paid') THEN fr.amount
                    ELSE 0.00
                END
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingOfficeProcurements()
    {
        if (class_exists('AssetManagement')) {
            $am = new AssetManagement($this->db);
            if (method_exists($am, 'ensureExtraPurchasesTableSchema')) {
                $am->ensureExtraPurchasesTableSchema();
            }
        }
        // 1. Insert missing office procurement requests into approval_requests
        $sql = "
            SELECT pea.*, d.name AS dept_name
            FROM project_extra_assets pea
            LEFT JOIN departments d ON pea.department_id = d.id
            LEFT JOIN approval_requests ar ON (ar.source_table IN ('project_extra_assets', 'purchase_requests') AND ar.source_id = pea.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId = (int) $row['id'];
                $deptName = !empty($row['dept_name']) ? $row['dept_name'] : 'Procurement';
                $requestType = 'Office Procurement';
                $cleanItem = !empty($row['item_name']) ? $row['item_name'] : 'Asset Request';
                $title = mb_substr("Office Procurement: " . $cleanItem . " (Qty: " . ($row['quantity'] ?? 1) . ")", 0, 145);
                $amount = (float) $row['total_price'];
                $requestedBy = (int) $row['requested_by'];
                
                if ($row['mgmt_status'] === 'approved') {
                    $status = 'approved';
                    $approveRole = 'accounts';
                    $approvedAmount = $amount;
                    $approvedBy = !empty($row['mgmt_by']) ? (int)$row['mgmt_by'] : null;
                    $approvedAt = !empty($row['mgmt_at']) ? $row['mgmt_at'] : null;
                } elseif ($row['mgmt_status'] === 'rejected' || $row['overall_status'] === 'rejected' || ($row['qc_audit_status'] ?? '') === 'rejected' || ($row['purchase_head_status'] ?? '') === 'rejected' || ($row['operations_status'] ?? '') === 'rejected' || ($row['it_head_status'] ?? '') === 'rejected' || ($row['dept_head_status'] ?? '') === 'rejected') {
                    $status = 'rejected';
                    $approveRole = 'management';
                    $approvedAmount = null;
                    $approvedBy = !empty($row['mgmt_by']) ? (int)$row['mgmt_by'] : null;
                    $approvedAt = !empty($row['mgmt_at']) ? $row['mgmt_at'] : null;
                } else {
                    $status = 'pending';
                    $approvedAmount = null;
                    $approvedBy = null;
                    $approvedAt = null;
                    
                    if (($row['dept_head_status'] ?? 'not_required') === 'pending') {
                        $approveRole = (!empty($row['reporting_user_id']) && (int)$row['reporting_user_id'] > 0) ? ('dept_head_' . $row['reporting_user_id']) : 'dept_head';
                    } elseif (($row['it_head_status'] ?? 'not_required') === 'pending') {
                        $approveRole = 'it_head';
                    } elseif (($row['operations_status'] ?? 'pending') === 'pending') {
                        $approveRole = 'operations';
                    } elseif (($row['purchase_head_status'] ?? 'pending') === 'pending') {
                        $approveRole = 'purchase_head';
                    } elseif (($row['qc_audit_status'] ?? 'pending') === 'pending') {
                        $approveRole = 'qc';
                    } else {
                        $approveRole = 'management';
                    }
                }

                $approvalNote = !empty($row['mgmt_note']) ? $row['mgmt_note'] : null;
                $metaData = json_encode([
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'justification' => $row['justification']
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'project_extra_assets', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing project_extra_assets
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN project_extra_assets pea ON (ar.source_table IN ('project_extra_assets', 'purchase_requests') AND ar.source_id = pea.id)
            SET ar.status = CASE 
                    WHEN pea.payment_status = 'paid' OR pea.overall_status = 'purchased' THEN 'approved'
                    WHEN pea.mgmt_status = 'approved' THEN 'approved' 
                    WHEN pea.mgmt_status = 'rejected' OR pea.overall_status = 'rejected' OR pea.qc_audit_status = 'rejected' OR pea.purchase_head_status = 'rejected' OR pea.operations_status = 'rejected' OR pea.it_head_status = 'rejected' OR pea.dept_head_status = 'rejected' THEN 'rejected' 
                    ELSE 'pending' 
                END,
                ar.approve_role = CASE
                    WHEN pea.payment_status = 'paid' OR pea.overall_status = 'purchased' THEN 'completed'
                    WHEN pea.mgmt_status = 'approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN pea.mgmt_status = 'rejected' OR pea.overall_status = 'rejected' OR pea.qc_audit_status = 'rejected' OR pea.purchase_head_status = 'rejected' OR pea.operations_status = 'rejected' OR pea.it_head_status = 'rejected' OR pea.dept_head_status = 'rejected' THEN 'rejected'
                    WHEN pea.dept_head_status = 'pending' THEN IF(pea.reporting_user_id IS NOT NULL AND pea.reporting_user_id > 0, CONCAT('dept_head_', pea.reporting_user_id), 'dept_head')
                    WHEN pea.it_head_status = 'pending' THEN 'it_head'
                    WHEN pea.operations_status = 'pending' THEN 'operations'
                    WHEN pea.purchase_head_status = 'pending' THEN 'purchase_head'
                    WHEN pea.qc_audit_status = 'pending' THEN 'qc'
                    ELSE 'management'
                END,
                ar.approved_by = COALESCE(pea.mgmt_by, pea.qc_audit_by, pea.purchase_head_by, pea.operations_by, pea.it_head_by, pea.dept_head_by, ar.approved_by),
                ar.approved_at = COALESCE(pea.mgmt_at, pea.qc_audit_at, pea.purchase_head_at, pea.operations_at, pea.it_head_at, pea.dept_head_at, ar.approved_at),
                ar.approval_note = COALESCE(pea.mgmt_note, pea.qc_audit_note, pea.purchase_head_note, pea.operations_note, pea.it_head_note, pea.dept_head_note, ar.approval_note),
                ar.request_amount = pea.total_price,
                ar.approved_amount = CASE WHEN pea.mgmt_status = 'approved' THEN pea.total_price ELSE ar.approved_amount END
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingLaborRequisitions()
    {
        // 1. Insert missing labor bills (only when work_completed or beyond, so initial pending planning is not flooded)
        $sql = "
            SELECT lr.*, p.name AS project_name
            FROM con_labor_requisitions lr
            LEFT JOIN projects p ON lr.project_id = p.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'con_labor_requisitions' AND ar.source_id = lr.id)
            WHERE ar.id IS NULL
              AND lr.status IN ('work_completed', 'payment_requested', 'qc_approved', 'pm_payment_approved', 'management_approved', 'partially_paid', 'paid', 'labor_disbursed', 'rejected')
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId    = (int) $row['id'];
                $deptName    = 'Construction';
                $requestType = 'Labor Bill by SE';
                $projectName = !empty($row['project_name']) ? $row['project_name'] : 'Project #' . $row['project_id'];
                $title       = "Labor Bill — " . $row['requisition_no'] . " | " . $projectName . " (" . $row['labor_count'] . " " . $row['labor_type'] . " Labors)";
                $amount      = (float)$row['total_amount'];
                $requestedBy = (int) $row['requested_by'];
                
                $lrStatus = $row['status'];
                $approveRole = 'management';
                if ($lrStatus === 'pending') {
                    $approveRole = 'pm';
                    $status = 'pending';
                } elseif ($lrStatus === 'payment_requested') {
                    $approveRole = 'qc';
                    $status = 'pending';
                } elseif (in_array($lrStatus, ['qc_approved', 'pm_payment_approved'])) {
                    $approveRole = 'management';
                    $status = 'pending';
                } elseif ($lrStatus === 'management_approved') {
                    $approveRole = 'accounts';
                    $status = 'approved';
                } elseif (in_array($lrStatus, ['paid', 'labor_disbursed'])) {
                    $approveRole = 'completed';
                    $status = 'approved';
                } elseif ($lrStatus === 'partially_paid') {
                    $approveRole = 'accounts';
                    $status = 'partially_paid';
                } elseif ($lrStatus === 'rejected') {
                    $approveRole = 'management';
                    $status = 'rejected';
                } else {
                    $approveRole = 'management';
                    $status = 'pending';
                }

                $approvedAmount = in_array($status, ['approved', 'partially_paid']) ? (!empty($row['approved_amount']) ? (float)$row['approved_amount'] : $amount) : null;
                $approvedBy = !empty($row['management_approved_by']) ? (int)$row['management_approved_by'] : null;
                $approvedAt = !empty($row['management_approved_at']) ? $row['management_approved_at'] : null;

                $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                $metaData = json_encode([
                    'requisition_no' => $row['requisition_no'],
                    'labor_count'    => $row['labor_count'],
                    'labor_type'     => $row['labor_type'],
                    'working_days'   => $row['working_days'],
                    'per_labor_rate' => $row['per_labor_rate'],
                    'purpose'        => $row['purpose'],
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'con_labor_requisitions', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing con_labor_requisitions in approval_requests
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN con_labor_requisitions lr ON (ar.source_table = 'con_labor_requisitions' AND ar.source_id = lr.id)
            SET ar.approve_role = CASE 
                    WHEN lr.status = 'pending' THEN 'pm'
                    WHEN lr.status = 'payment_requested' THEN 'qc'
                    WHEN lr.status IN ('qc_approved', 'pm_payment_approved') THEN 'management'
                    WHEN lr.status = 'management_approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN lr.status IN ('paid', 'labor_disbursed') THEN 'completed'
                    WHEN lr.status = 'partially_paid' THEN 'accounts'
                    WHEN lr.status = 'rejected' THEN 'management'
                    ELSE 'management'
                END,
                ar.status = CASE 
                    WHEN lr.status IN ('management_approved', 'paid', 'labor_disbursed') THEN 'approved' 
                    WHEN lr.status = 'partially_paid' THEN 'partially_paid'
                    WHEN lr.status = 'rejected' THEN 'rejected' 
                    ELSE 'pending' 
                END,
                ar.approved_by = lr.management_approved_by,
                ar.approved_at = lr.management_approved_at,
                ar.approval_note = lr.management_note,
                ar.request_amount = lr.total_amount,
                ar.approved_amount = CASE 
                    WHEN lr.status IN ('management_approved', 'partially_paid', 'paid', 'labor_disbursed') THEN COALESCE(lr.approved_amount, lr.total_amount) 
                    WHEN lr.status IN ('qc_approved', 'pm_payment_approved') THEN COALESCE(lr.qc_approved_amount, lr.approved_amount)
                    ELSE NULL 
                END
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingProjectOtherCosts()
    {
        if (class_exists('ProjectOtherCost')) {
            new ProjectOtherCost($this->db);
        }
        // 1. Insert missing project other costs into approval_requests
        $sql = "
            SELECT poc.*, p.name AS project_name, e.reporting_user_id
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN employees e ON (e.user_id = poc.created_by_id AND e.deleted_at IS NULL)
            LEFT JOIN approval_requests ar ON (ar.source_table = 'project_other_costs' AND ar.source_id = poc.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId    = (int) $row['id'];
                $deptName    = 'Construction';
                $requestType = !empty($row['rcost_type']) ? $row['rcost_type'] : 'electricity_bill';
                $purpose     = !empty($row['purpose']) ? $row['purpose'] : ($row['project_name'] ?? 'Other Cost');
                $projPart    = !empty($row['project_name']) ? ' | ' . $row['project_name'] : '';
                $title       = "Project Other Cost: " . (mb_strlen($purpose) > 70 ? mb_substr($purpose, 0, 70) . '...' : $purpose) . $projPart;
                $amount      = (float) $row['request_amount'];
                $requestedBy = (int) $row['created_by_id'];

                $pocStatus = strtolower($row['status'] ?? '');
                $dhStatus  = strtolower($row['dept_head_status'] ?? '');

                $status = 'pending';
                $approveRole = 'qc';
                $approvedAmount = null;
                $approvedBy = null;
                $approvedAt = null;
                $approvalNote = null;

                if ($pocStatus === 'rejected' || $dhStatus === 'rejected') {
                    $status = 'rejected';
                    $approveRole = 'rejected';
                    $approvedBy = !empty($row['dept_head_approved_by']) ? (int)$row['dept_head_approved_by'] : (!empty($row['qc_audit_approved_id']) ? (int)$row['qc_audit_approved_id'] : (!empty($row['management_apporved_by_id']) ? (int)$row['management_apporved_by_id'] : null));
                    $approvedAt = !empty($row['dept_head_approved_at']) ? $row['dept_head_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['dept_head_approval_note']) ? $row['dept_head_approval_note'] : (!empty($row['qc_audit_note']) ? $row['qc_audit_note'] : (!empty($row['management_apporved_note']) ? $row['management_apporved_note'] : null));
                } elseif ($pocStatus === 'pending') {
                    if ($dhStatus === 'pending') {
                        $status = 'pending';
                        $approveRole = !empty($row['reporting_user_id']) ? 'dept_head_' . $row['reporting_user_id'] : 'dept_head';
                    } else {
                        $status = 'pending';
                        $approveRole = 'qc';
                        if ($dhStatus === 'approved') {
                            $approvedBy = !empty($row['dept_head_approved_by']) ? (int)$row['dept_head_approved_by'] : null;
                            $approvedAt = !empty($row['dept_head_approved_at']) ? $row['dept_head_approved_at'] : null;
                            $approvalNote = !empty($row['dept_head_approval_note']) ? $row['dept_head_approval_note'] : null;
                        }
                    }
                } elseif ($pocStatus === 'qc_approved') {
                    $status = 'pending';
                    $approveRole = 'management';
                    $approvedBy = !empty($row['qc_audit_approved_id']) ? (int)$row['qc_audit_approved_id'] : null;
                    $approvedAt = !empty($row['qc_audit_approved_at']) ? $row['qc_audit_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['qc_audit_note']) ? $row['qc_audit_note'] : null;
                } elseif (in_array($pocStatus, ['management_approved', 'approved'])) {
                    $status = 'approved';
                    $approveRole = 'accounts';
                    $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : $amount;
                    $approvedBy = !empty($row['management_apporved_by_id']) ? (int)$row['management_apporved_by_id'] : null;
                    $approvedAt = !empty($row['management_apporved_at']) ? $row['management_apporved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['management_apporved_note']) ? $row['management_apporved_note'] : null;
                } elseif ($pocStatus === 'paid') {
                    $status = 'approved';
                    $approveRole = 'completed';
                    $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : (!empty($row['payment_amount']) ? (float)$row['payment_amount'] : $amount);
                    $approvedBy = !empty($row['management_apporved_by_id']) ? (int)$row['management_apporved_by_id'] : null;
                    $approvedAt = !empty($row['management_apporved_at']) ? $row['management_apporved_at'] : (!empty($row['payment_at']) ? $row['payment_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null));
                    $approvalNote = !empty($row['management_apporved_note']) ? $row['management_apporved_note'] : null;
                }

                $metaData = json_encode(['purpose' => $row['purpose'], 'project_id' => $row['project_id'], 'project_name' => $row['project_name']]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'project_other_costs', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing project_other_costs in approval_requests (heals empty status and stale data)
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN project_other_costs poc ON (ar.source_table = 'project_other_costs' AND ar.source_id = poc.id)
            SET ar.approve_role = CASE 
                    WHEN poc.status = 'rejected' OR poc.dept_head_status = 'rejected' THEN 'rejected'
                    WHEN poc.status = 'pending' AND poc.dept_head_status = 'pending' THEN 'dept_head'
                    WHEN poc.status = 'pending' THEN 'qc'
                    WHEN poc.status = 'qc_approved' THEN 'management'
                    WHEN poc.status = 'management_approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN poc.status = 'paid' THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE 
                    WHEN poc.status = 'rejected' OR poc.dept_head_status = 'rejected' THEN 'rejected'
                    WHEN poc.status IN ('management_approved', 'approved', 'paid') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approved_by = CASE
                    WHEN poc.management_apporved_by_id IS NOT NULL AND poc.management_apporved_by_id != ar.accounts_head_by THEN poc.management_apporved_by_id
                    WHEN ar.op_head_by IS NOT NULL THEN ar.op_head_by
                    ELSE COALESCE(ar.approved_by, poc.management_apporved_by_id, poc.qc_audit_approved_id, poc.dept_head_approved_by)
                END,
                ar.approved_at = COALESCE(poc.management_apporved_at, poc.qc_audit_approved_at, poc.dept_head_approved_at, poc.updated_at, ar.approved_at),
                ar.approval_note = COALESCE(poc.management_apporved_note, poc.qc_audit_note, poc.dept_head_approval_note, ar.approval_note),
                ar.request_amount = poc.request_amount,
                ar.approved_amount = CASE 
                    WHEN poc.status IN ('management_approved', 'approved', 'paid') THEN COALESCE(poc.approved_amount, poc.payment_amount, poc.request_amount)
                    ELSE ar.approved_amount
                END
            WHERE ar.status = '' 
               OR ar.status IS NULL 
               OR (poc.status = 'paid' AND ar.approve_role != 'completed')
               OR (poc.status = 'management_approved' AND ar.status != 'approved')
               OR (poc.status = 'rejected' AND ar.status != 'rejected')
               OR (poc.status = 'qc_approved' AND ar.approve_role != 'management')
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingVendorPaymentRequests()
    {
        if (class_exists('Con_VendorPaymentRequest')) {
            new Con_VendorPaymentRequest();
        }
        // 1. Insert missing vendor_payment_requests into approval_requests
        $sql = "
            SELECT vpr.*, p.name AS project_name, v.name AS vendor_name
            FROM vendor_payment_requests vpr
            LEFT JOIN projects p ON vpr.project_id = p.id
            LEFT JOIN vendors v ON vpr.vendor_id = v.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'vendor_payment_requests' AND ar.source_id = vpr.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId    = (int) $row['id'];
                $deptName    = 'Construction';
                $requestType = 'vendor_payment';
                $vendorName  = !empty($row['vendor_name']) ? $row['vendor_name'] : 'Vendor';
                $projectName = !empty($row['project_name']) ? $row['project_name'] : 'Project';
                $title       = "Vendor Payment — {$vendorName} | {$projectName}";
                $netPayable  = (float) ($row['net_payable'] ?? $row['invoice_amount'] ?? 0);
                $qcAppAmt    = (isset($row['qc_approved_amount']) && (float)$row['qc_approved_amount'] > 0) ? (float)$row['qc_approved_amount'] : $netPayable;
                $requestedBy = (int) ($row['created_by'] ?? 1);

                $vStatus = strtolower(trim(str_replace('_', ' ', $row['status'] ?? '')));

                $status = 'pending';
                $approveRole = !empty($row['purchase_head_approved_by_id']) ? 'qc' : 'purchase_head';
                $requestAmount = $netPayable;
                $approvedAmount = null;
                $approvedBy = null;
                $approvedAt = null;
                $approvalNote = null;

                if ($vStatus === 'rejected') {
                    $status = 'rejected';
                    $approveRole = 'rejected';
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                } elseif (in_array($vStatus, ['submitted', 'pending', 'draft', ''])) {
                    $status = 'pending';
                    $approveRole = !empty($row['purchase_head_approved_by_id']) ? 'qc' : 'purchase_head';
                } elseif (in_array($vStatus, ['qc approved', 'qc_approved'])) {
                    $status = 'pending';
                    $approveRole = 'operations';
                    $requestAmount = $qcAppAmt;
                    $approvedBy = !empty($row['qc_approved_by_id']) ? (int)$row['qc_approved_by_id'] : null;
                    $approvedAt = !empty($row['qc_approved_at']) ? $row['qc_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['qc_note']) ? $row['qc_note'] : null;
                } elseif (in_array($vStatus, ['approved', 'management approved', 'management_approved'])) {
                    $status = 'approved';
                    $approveRole = 'accounts';
                    $requestAmount = $qcAppAmt;
                    $approvedAmount = (isset($row['approved_amount']) && (float)$row['approved_amount'] > 0) ? (float)$row['approved_amount'] : $netPayable;
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                    $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                } elseif (in_array($vStatus, ['paid', 'completed', 'fully paid', 'fully_paid', 'partially paid', 'partially_paid'])) {
                    $status = 'approved';
                    $approveRole = 'completed';
                    $requestAmount = $netPayable;
                    $approvedAmount = (isset($row['approved_amount']) && (float)$row['approved_amount'] > 0) ? (float)$row['approved_amount'] : $netPayable;
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                    $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                }

                $metaData = json_encode([
                    'vendor_id'         => $row['vendor_id'],
                    'vendor_name'       => $vendorName,
                    'project_id'        => $row['project_id'],
                    'project_name'      => $projectName,
                    'purchase_order_id' => $row['purchase_order_id'],
                    'payment_type'      => $row['payment_type'] ?? 'Final',
                    'invoice_number'    => $row['invoice_number'] ?? null
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'vendor_payment_requests', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $requestAmount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 1.5. Auto-heal vendor_payment_requests approved by BOTH Operation Head AND Accounts Head (<= 10k)
        $autoHealSql = "
            UPDATE vendor_payment_requests vpr
            INNER JOIN approval_requests ar ON (ar.source_table = 'vendor_payment_requests' AND ar.source_id = vpr.id)
            SET 
                vpr.status = 'Approved',
                vpr.approved_by = COALESCE(ar.accounts_head_by, ar.op_head_by, ar.approved_by, 200),
                vpr.approved_amount = COALESCE(vpr.qc_approved_amount, vpr.net_payable, vpr.invoice_amount, ar.request_amount),
                vpr.due_amount = COALESCE(vpr.qc_approved_amount, vpr.net_payable, vpr.invoice_amount, ar.request_amount),
                vpr.management_note = COALESCE(NULLIF(ar.accounts_head_note, ''), NULLIF(ar.op_head_note, ''), NULLIF(ar.approval_note, ''), vpr.management_note, 'Approved by Operation Head & Accounts Head'),
                vpr.updated_at = NOW()
            WHERE vpr.status = 'QC Approved'
              AND ar.op_head_status = 'approved'
              AND ar.accounts_head_status = 'approved'
              AND ar.request_amount <= 10000.01
        ";
        @$this->db->query($autoHealSql);

        // 2. Sync and self-heal existing vendor_payment_requests in approval_requests
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN vendor_payment_requests vpr ON (ar.source_table = 'vendor_payment_requests' AND ar.source_id = vpr.id)
            SET 
                ar.approve_role = CASE
                    WHEN vpr.status = 'Rejected' THEN 'rejected'
                    WHEN vpr.status IN ('Submitted', 'Draft') THEN IF(vpr.purchase_head_approved_by_id IS NULL, 'purchase_head', 'qc')
                    WHEN vpr.status = 'QC Approved' AND ar.op_head_status = 'approved' THEN 
                        CASE 
                            WHEN ar.accounts_head_status = 'approved' THEN (CASE WHEN ar.request_amount <= 10000.01 THEN 'accounts' ELSE 'management' END)
                            ELSE 'accounts_head' 
                        END
                    WHEN vpr.status = 'QC Approved' THEN 'operations'
                    WHEN vpr.status = 'Approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN vpr.status IN ('Paid', 'Partially_Paid', 'Fully_Paid') THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN vpr.status = 'Rejected' THEN 'rejected'
                    WHEN vpr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN 'approved'
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.request_amount = CASE
                    WHEN vpr.qc_approved_amount IS NOT NULL AND vpr.qc_approved_amount > 0 THEN vpr.qc_approved_amount
                    WHEN vpr.net_payable IS NOT NULL AND vpr.net_payable > 0 THEN vpr.net_payable
                    ELSE ar.request_amount
                END,
                ar.approved_amount = CASE
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN COALESCE(vpr.approved_amount, vpr.net_payable, ar.request_amount)
                    WHEN vpr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN COALESCE(vpr.approved_amount, vpr.qc_approved_amount, vpr.net_payable, ar.request_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_by = CASE
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN COALESCE(vpr.approved_by, ar.approved_by)
                    WHEN vpr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN COALESCE(ar.accounts_head_by, ar.op_head_by, ar.approved_by, 200)
                    WHEN vpr.status = 'QC Approved' THEN COALESCE(vpr.qc_approved_by_id, ar.approved_by)
                    ELSE ar.approved_by
                END,
                ar.approved_at = CASE
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN COALESCE(vpr.updated_at, ar.approved_at)
                    WHEN vpr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN COALESCE(ar.accounts_head_at, ar.op_head_at, vpr.updated_at, ar.approved_at)
                    WHEN vpr.status = 'QC Approved' THEN COALESCE(vpr.qc_approved_at, ar.approved_at)
                    ELSE ar.approved_at
                END,
                ar.approval_note = CASE
                    WHEN vpr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND ar.op_head_note IS NOT NULL AND ar.op_head_note != '' THEN ar.op_head_note
                    WHEN vpr.status = 'QC Approved' AND vpr.qc_note IS NOT NULL AND vpr.qc_note != '' THEN vpr.qc_note
                    WHEN vpr.status = 'Approved' AND vpr.management_note IS NOT NULL AND vpr.management_note != '' THEN vpr.management_note
                    ELSE ar.approval_note
                END
            WHERE 
                -- Items where QC has approved, but approval_request is not aligned with Op Head / Accounts Head / Management
                (vpr.status = 'QC Approved' AND ar.approve_role NOT IN ('operations', 'management', 'accounts_head', 'accounts'))
                OR (vpr.status = 'QC Approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status != 'approved' AND ar.approve_role != 'accounts_head')
                -- Items where Management has approved, but approval_request is not in accounts / approved
                OR (vpr.status = 'Approved' AND (ar.approve_role NOT IN ('accounts', 'accounts_head') OR ar.status != 'approved'))
                -- Items paid
                OR (vpr.status IN ('Paid', 'Partially_Paid', 'Fully_Paid') AND ar.approve_role != 'completed')
                -- Items rejected
                OR (vpr.status = 'Rejected' AND ar.status != 'rejected')
                -- Items submitted
                OR (vpr.status IN ('Submitted', 'Draft') AND ar.approve_role != IF(vpr.purchase_head_approved_by_id IS NULL, 'purchase_head', 'qc'))
                -- Empty status
                OR ar.status = '' OR ar.status IS NULL
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingContractorBills()
    {
        if (class_exists('Con_ContractorBill')) {
            new Con_ContractorBill();
        }
        // 1. Insert missing contractor_bill_requests into approval_requests
        $sql = "
            SELECT cbr.*, p.name AS project_name, c.name AS contractor_name
            FROM contractor_bill_requests cbr
            LEFT JOIN projects p ON cbr.project_id = p.id
            LEFT JOIN contractors c ON cbr.operator_id = c.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'contractor_bill_requests' AND ar.source_id = cbr.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId    = (int) $row['id'];
                $deptName    = 'Construction';
                $requestType = 'contractor_bill';
                $contractorName = !empty($row['contractor_name']) ? $row['contractor_name'] : 'Contractor';
                $projectName    = !empty($row['project_name']) ? $row['project_name'] : 'Project';
                $title       = "Contractor Bill CB-{$sourceId} — {$contractorName} | {$projectName}";
                $billAmount  = (float) ($row['bill_amount'] ?? 0);
                $approvedAmt = (isset($row['approved_amount']) && (float)$row['approved_amount'] > 0) ? (float)$row['approved_amount'] : $billAmount;
                $requestedBy = (int) ($row['created_by'] ?? 1);

                $cbStatus = strtolower(trim($row['status'] ?? ''));

                $status = 'pending';
                $approveRole = 'qc';
                $requestAmount = $billAmount;
                $approvedAmount = null;
                $approvedBy = null;
                $approvedAt = null;
                $approvalNote = null;

                if ($cbStatus === 'rejected') {
                    $status = 'rejected';
                    $approveRole = 'rejected';
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                } elseif (in_array($cbStatus, ['submitted', 'pending', 'draft', ''])) {
                    $status = 'pending';
                    $approveRole = 'pm';
                } elseif ($cbStatus === 'verified') {
                    $status = 'pending';
                    $approveRole = !empty($row['purchase_head_approved_by_id']) ? 'qc' : 'purchase_head';
                } elseif ($cbStatus === 'qc_approved') {
                    $status = 'pending';
                    $approveRole = 'operations';
                    $requestAmount = $approvedAmt;
                    $approvedBy = !empty($row['qc_approved_by_id']) ? (int)$row['qc_approved_by_id'] : null;
                    $approvedAt = !empty($row['qc_approved_at']) ? $row['qc_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['qc_note']) ? $row['qc_note'] : null;
                } elseif ($cbStatus === 'approved') {
                    $status = 'approved';
                    $approveRole = 'accounts';
                    $requestAmount = $billAmount;
                    $approvedAmount = $approvedAmt;
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                    $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                } elseif (in_array($cbStatus, ['paid', 'fully_paid', 'partially_paid'])) {
                    $status = 'approved';
                    $approveRole = 'completed';
                    $requestAmount = $billAmount;
                    $approvedAmount = $approvedAmt;
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                    $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                }

                $metaData = json_encode([
                    'contractor_id'   => $row['operator_id'],
                    'contractor_name' => $contractorName,
                    'project_id'      => $row['project_id'],
                    'project_name'    => $projectName,
                    'work_order_id'   => $row['work_order_id'],
                    'target_id'       => $row['target_id'],
                    'bill_date'       => $row['bill_date']
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'contractor_bill_requests', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $requestAmount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 1.5. Auto-heal contractor_bill_requests approved by BOTH Operation Head AND Accounts Head (<= 10k)
        $autoHealCbSql = "
            UPDATE contractor_bill_requests cbr
            INNER JOIN approval_requests ar ON (ar.source_table = 'contractor_bill_requests' AND ar.source_id = cbr.id)
            SET 
                cbr.status = 'approved',
                cbr.approved_by = COALESCE(ar.accounts_head_by, ar.op_head_by, ar.approved_by, 200),
                cbr.approved_amount = COALESCE(cbr.approved_amount, cbr.bill_amount, ar.request_amount),
                cbr.due_amount = COALESCE(cbr.approved_amount, cbr.bill_amount, ar.request_amount),
                cbr.management_note = COALESCE(NULLIF(ar.accounts_head_note, ''), NULLIF(ar.op_head_note, ''), NULLIF(ar.approval_note, ''), cbr.management_note, 'Approved by Operation Head & Accounts Head'),
                cbr.updated_at = NOW()
            WHERE cbr.status = 'qc_approved'
              AND ar.op_head_status = 'approved'
              AND ar.accounts_head_status = 'approved'
              AND ar.request_amount <= 10000.01
        ";
        @$this->db->query($autoHealCbSql);

        // 2. Synchronize existing approval_requests with contractor_bill_requests state
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN contractor_bill_requests cbr ON (ar.source_table = 'contractor_bill_requests' AND ar.source_id = cbr.id)
            SET 
                ar.approve_role = CASE
                    WHEN cbr.status IN ('submitted', 'pending', 'draft') AND cbr.qc_approved_at IS NULL THEN 'pm'
                    WHEN cbr.status = 'verified' AND cbr.qc_approved_at IS NULL THEN IF(cbr.purchase_head_approved_by_id IS NULL, 'purchase_head', 'qc')
                    WHEN cbr.status = 'qc_approved' AND ar.op_head_status = 'approved' THEN 
                        CASE 
                            WHEN ar.accounts_head_status = 'approved' THEN (CASE WHEN ar.request_amount <= 10000.01 THEN 'accounts' ELSE 'management' END)
                            ELSE 'accounts_head' 
                        END
                    WHEN cbr.status = 'qc_approved' THEN 'operations'
                    WHEN cbr.status = 'approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN cbr.status IN ('paid', 'fully_paid', 'partially_paid') THEN 'completed'
                    WHEN cbr.status = 'rejected' THEN 'rejected'
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN cbr.status = 'rejected' THEN 'rejected'
                    WHEN cbr.status = 'qc_approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN 'approved'
                    WHEN cbr.status IN ('approved', 'paid', 'fully_paid', 'partially_paid') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.request_amount = CASE
                    WHEN cbr.qc_approved_at IS NOT NULL AND cbr.approved_amount IS NOT NULL AND cbr.approved_amount > 0 THEN cbr.approved_amount
                    WHEN cbr.bill_amount IS NOT NULL AND cbr.bill_amount > 0 THEN cbr.bill_amount
                    ELSE ar.request_amount
                END,
                ar.approved_amount = CASE
                    WHEN cbr.status IN ('approved', 'paid', 'fully_paid', 'partially_paid') THEN COALESCE(cbr.approved_amount, cbr.bill_amount, ar.request_amount)
                    WHEN cbr.status = 'qc_approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN COALESCE(cbr.approved_amount, cbr.bill_amount, ar.request_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_by = CASE
                    WHEN cbr.status IN ('approved', 'paid', 'fully_paid', 'partially_paid') THEN COALESCE(cbr.approved_by, ar.approved_by)
                    WHEN cbr.status = 'qc_approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN COALESCE(ar.accounts_head_by, ar.op_head_by, ar.approved_by, 200)
                    WHEN cbr.status = 'qc_approved' THEN COALESCE(cbr.qc_approved_by_id, ar.approved_by)
                    ELSE ar.approved_by
                END,
                ar.approved_at = CASE
                    WHEN cbr.status IN ('approved', 'paid', 'fully_paid', 'partially_paid') THEN COALESCE(cbr.updated_at, ar.approved_at)
                    WHEN cbr.status = 'qc_approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status = 'approved' AND ar.request_amount <= 10000.01 THEN COALESCE(ar.accounts_head_at, ar.op_head_at, cbr.updated_at, ar.approved_at)
                    WHEN cbr.status = 'qc_approved' THEN COALESCE(cbr.qc_approved_at, ar.approved_at)
                    ELSE ar.approved_at
                END,
                ar.approval_note = CASE
                    WHEN cbr.status = 'qc_approved' AND ar.op_head_status = 'approved' AND ar.op_head_note IS NOT NULL AND ar.op_head_note != '' THEN ar.op_head_note
                    WHEN cbr.status = 'qc_approved' AND cbr.qc_note IS NOT NULL AND cbr.qc_note != '' THEN cbr.qc_note
                    WHEN cbr.status = 'approved' AND cbr.management_note IS NOT NULL AND cbr.management_note != '' THEN cbr.management_note
                    ELSE ar.approval_note
                END
            WHERE 
                -- Items where PM has not verified yet, or verified but QC has not approved yet
                (cbr.status IN ('submitted', 'pending', 'draft') AND cbr.qc_approved_at IS NULL AND ar.approve_role != 'pm')
                OR (cbr.status = 'verified' AND cbr.qc_approved_at IS NULL AND ar.approve_role != IF(cbr.purchase_head_approved_by_id IS NULL, 'purchase_head', 'qc'))
                -- Items where QC has approved, but approval_request is not aligned with Op Head / Accounts Head / Management
                OR (cbr.status = 'qc_approved' AND ar.approve_role NOT IN ('operations', 'management', 'accounts_head', 'accounts'))
                OR (cbr.status = 'qc_approved' AND ar.op_head_status = 'approved' AND ar.accounts_head_status != 'approved' AND ar.approve_role != 'accounts_head')
                -- Items where Management has approved, but approval_request is not in accounts / approved
                OR (cbr.status = 'approved' AND (ar.approve_role NOT IN ('accounts', 'accounts_head') OR ar.status != 'approved'))
                -- Items paid
                OR (cbr.status IN ('paid', 'fully_paid', 'partially_paid') AND ar.approve_role != 'completed')
                -- Items rejected
                OR (cbr.status = 'rejected' AND ar.status != 'rejected')
                -- Empty status
                OR ar.status = '' OR ar.status IS NULL
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingAdvanceSalaries()
    {
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN advance_salaries as_tbl ON (ar.source_table = 'advance_salaries' AND ar.source_id = as_tbl.id)
            SET 
                ar.approve_role = CASE
                    WHEN as_tbl.status = 'rejected' THEN 'rejected'
                    WHEN as_tbl.status = 'pending_hr' THEN 'hr_head'
                    WHEN as_tbl.status = 'pending' THEN 'management'
                    WHEN as_tbl.status IN ('pending_accounts', 'approved') THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN as_tbl.status IN ('paid', 'deducted') THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN as_tbl.status = 'rejected' THEN 'rejected'
                    WHEN as_tbl.status IN ('pending_accounts', 'approved', 'paid', 'deducted') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approved_amount = CASE
                    WHEN as_tbl.status IN ('pending_accounts', 'approved', 'paid', 'deducted') THEN COALESCE(ar.approved_amount, as_tbl.amount, ar.request_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_at = CASE
                    WHEN as_tbl.approved_date IS NOT NULL THEN as_tbl.approved_date
                    ELSE ar.approved_at
                END
            WHERE 
                (as_tbl.status IN ('pending_accounts', 'approved') AND (ar.approve_role != 'accounts' OR ar.status != 'approved'))
                OR (as_tbl.status IN ('paid', 'deducted') AND ar.approve_role != 'completed')
                OR (as_tbl.status = 'rejected' AND ar.status != 'rejected')
                OR (as_tbl.status = 'pending_hr' AND ar.approve_role != 'hr_head')
                OR (as_tbl.status = 'pending' AND ar.approve_role != 'management')
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingCashoutRequests()
    {
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN cashout_requests cr ON (ar.source_table = 'cashout_requests' AND ar.source_id = cr.id)
            SET 
                ar.approve_role = CASE
                    WHEN cr.status = 'rejected' THEN 'rejected'
                    WHEN cr.status = 'pending' THEN 'management'
                    WHEN cr.status = 'approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN cr.status IN ('fully_paid', 'paid') THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN cr.status = 'rejected' THEN 'rejected'
                    WHEN cr.status IN ('approved', 'fully_paid', 'paid') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approved_amount = CASE
                    WHEN cr.status IN ('approved', 'fully_paid', 'paid') THEN COALESCE(cr.approved_amount, cr.amount, ar.request_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_by = CASE
                    WHEN cr.approved_by IS NOT NULL THEN cr.approved_by
                    ELSE ar.approved_by
                END,
                ar.approved_at = CASE
                    WHEN cr.approved_at IS NOT NULL THEN cr.approved_at
                    ELSE ar.approved_at
                END
            WHERE 
                (cr.status IN ('fully_paid', 'paid') AND (ar.approve_role != 'completed' OR ar.status != 'approved'))
                OR (cr.status = 'approved' AND (ar.approve_role != 'accounts' OR ar.status != 'approved'))
                OR (cr.status = 'rejected' AND ar.status != 'rejected')
                OR (cr.status = 'pending' AND (ar.approve_role != 'management' OR ar.status != 'pending'))
                OR ar.status = '' OR ar.status IS NULL
        ";
        @$this->db->query($syncSql);
    }

    
    public function syncMissingClientRefundRequests()
    {
        // 1. Insert missing approval_requests for client_refund_requests
        $missingSql = "
            SELECT cr.id, cr.booking_id, cr.status, cr.total_paid, cr.retention_amount, cr.refund_amount, cr.requested_by, cr.reason, cr.created_at,
                   l.full_name AS client_name, f.flat_name, p.name AS project_name
            FROM client_refund_requests cr
            LEFT JOIN leads l ON l.id = cr.lead_id
            LEFT JOIN flats f ON f.id = cr.flat_id
            LEFT JOIN projects p ON p.id = cr.project_id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'client_refund_requests' AND ar.source_id = cr.id)
            WHERE ar.id IS NULL
        ";
        $missingRes = $this->db->query($missingSql);
        if ($missingRes && $missingRes->num_rows > 0) {
            while ($row = $missingRes->fetch_assoc()) {
                $rfId = (int)$row['id'];
                $reqAmt = (float)$row['refund_amount'];
                $status = strtolower($row['status'] ?? 'pending');
                $title = 'Client Refund — ' . ($row['client_name'] ?? 'Client') . ' | ' . ($row['flat_name'] ?? '') . ' | ' . ($row['project_name'] ?? '');
                
                $appRole = 'management';
                if ($status === 'approved') {
                    $appRole = 'accounts_head';
                } elseif ($status === 'refunded') {
                    $appRole = 'completed';
                } elseif ($status === 'rejected') {
                    $appRole = 'rejected';
                }
                
                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests (department, request_type, title, source_table, source_id, requested_days, requested_by, approve_role, request_amount, approved_amount, status, op_head_status, accounts_head_status, created_at, updated_at)
                    VALUES ('accounts', 'client_refund', ?, 'client_refund_requests', ?, 0, ?, ?, ?, ?, ?, 'pending', 'pending', ?, ?)
                ");
                if ($stmt) {
                    $reqBy = (int)$row['requested_by'];
                    $stmt->bind_param('siisddssss', $title, $rfId, $reqBy, $appRole, $reqAmt, $reqAmt, $status, $row['created_at'], $row['created_at']);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Keep existing approval_requests in sync with client_refund_requests status and refund_amount
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN client_refund_requests cr ON (ar.source_table = 'client_refund_requests' AND ar.source_id = cr.id)
            SET 
                ar.request_amount = cr.refund_amount,
                ar.approved_amount = CASE 
                    WHEN cr.status IN ('approved', 'refunded') THEN cr.refund_amount 
                    ELSE ar.approved_amount 
                END,
                ar.status = CASE
                    WHEN cr.status = 'rejected' THEN 'rejected'
                    WHEN cr.status IN ('approved', 'refunded') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approve_role = CASE
                    WHEN cr.status = 'rejected' THEN 'rejected'
                    WHEN cr.status = 'refunded' THEN 'completed'
                    WHEN cr.status = 'approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    WHEN cr.status = 'pending' THEN 'management'
                    ELSE ar.approve_role
                END
            WHERE 
                ar.request_amount != cr.refund_amount
                OR (cr.status = 'pending' AND ar.approve_role NOT IN ('management', 'operations') AND ar.status != 'pending')
                OR (cr.status = 'approved' AND ar.status != 'approved')
                OR (cr.status = 'rejected' AND ar.status != 'rejected')
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingExpenseAdjustments()
    {
        // 1. Insert missing expense adjustments
        $sql = "
            SELECT ea.*, d.name AS dept_name,
                   COALESCE(cr.purpose_paid_to, CONCAT('Vendor Payment #', vpr.id, IF(vpr.invoice_number IS NOT NULL AND vpr.invoice_number != '', CONCAT(' (', vpr.invoice_number, ')'), '')), cbr.work_description, pr.notes, CONCAT('Other Cost: ', IFNULL(poc.purpose, poc.rcost_type)), CONCAT('Extra Asset: ', pea.item_name)) as original_purpose
            FROM expense_adjustments ea
            JOIN users u ON ea.user_id = u.id
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN cashout_requests cr ON ea.cashout_request_id = cr.id AND ea.parent_type = 'cashout'
            LEFT JOIN vendor_payment_requests vpr ON ea.cashout_request_id = vpr.id AND ea.parent_type = 'vendor'
            LEFT JOIN contractor_bill_requests cbr ON ea.cashout_request_id = cbr.id AND ea.parent_type = 'contractor'
            LEFT JOIN purchase_requests pr ON ea.cashout_request_id = pr.id AND ea.parent_type = 'purchase'
            LEFT JOIN project_other_costs poc ON ea.cashout_request_id = poc.id AND ea.parent_type = 'other_cost'
            LEFT JOIN project_extra_assets pea ON ea.cashout_request_id = pea.id AND ea.parent_type = 'extra_purchase'
            LEFT JOIN approval_requests ar ON (ar.source_table = 'expense_adjustments' AND ar.source_id = ea.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId = (int)$row['id'];
                $deptName = !empty($row['dept_name']) ? $row['dept_name'] : 'Accounts & Finance';
                $isReturn = ($row['adjustment_type'] === 'return');
                $requestType = $isReturn ? 'Expense Return' : 'Extra Expense';
                $parentLabel = match ($row['parent_type']) {
                    'vendor' => 'Vendor Payment',
                    'contractor' => 'Contractor Bill',
                    'other_cost' => 'Project Other Cost',
                    'extra_purchase' => 'Extra Purchase',
                    default => 'Cashout'
                };
                $titlePrefix = $isReturn ? 'Return Unused: ' : 'Extra Amount: ';
                $title = $titlePrefix . $row['title'] . " (Against " . $parentLabel . " #" . $row['cashout_request_id'] . ")";
                $amount = (float)$row['amount'];
                $requestedBy = (int)$row['user_id'];
                $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : null;
                $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                $approvedAt = !empty($row['approved_at']) ? $row['approved_at'] : null;
                $approvalNote = !empty($row['note']) ? $row['note'] : null;

                $eaStatus = $row['status'];
                if ($isReturn) {
                    // Returns go straight to Accounts for verification & cash-in
                    if ($eaStatus === 'fully_paid') {
                        $status = 'approved';
                        $approveRole = 'completed';
                        $approvedAmount = $amount;
                    } elseif ($eaStatus === 'partially_paid') {
                        $status = 'partially_paid';
                        $approveRole = 'accounts';
                        $approvedAmount = $amount;
                    } elseif ($eaStatus === 'rejected') {
                        $status = 'rejected';
                        $approveRole = 'management';
                    } else {
                        $status = 'pending';
                        $approveRole = 'accounts';
                    }
                } else {
                    // Extra requests follow: QC -> Management -> Accounts
                    if ($eaStatus === 'pending') {
                        $status = 'pending';
                        $approveRole = 'qc';
                    } elseif ($eaStatus === 'qc_approved') {
                        $status = 'pending';
                        $approveRole = 'management';
                        $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                        $approvedAt = !empty($row['approved_at']) ? $row['approved_at'] : null;
                    } elseif ($eaStatus === 'approved') {
                        $status = 'approved';
                        $approveRole = 'accounts';
                    } elseif ($eaStatus === 'partially_paid') {
                        $status = 'partially_paid';
                        $approveRole = 'accounts';
                    } elseif ($eaStatus === 'fully_paid') {
                        $status = 'approved';
                        $approveRole = 'completed';
                    } elseif ($eaStatus === 'rejected') {
                        $status = 'rejected';
                        $approveRole = 'management';
                    } else {
                        $status = 'pending';
                        $approveRole = 'qc';
                    }
                }

                $metaData = json_encode([
                    'adjustment_type' => $row['adjustment_type'],
                    'parent_type' => $row['parent_type'],
                    'cashout_request_id' => $row['cashout_request_id'],
                    'original_purpose' => $row['original_purpose']
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'expense_adjustments', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync existing expense_adjustments status
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN expense_adjustments ea ON (ar.source_table = 'expense_adjustments' AND ar.source_id = ea.id)
            SET 
                ar.approve_role = CASE
                    WHEN ea.status = 'rejected' THEN 'management'
                    WHEN ea.status = 'fully_paid' THEN 'completed'
                    WHEN ea.status = 'partially_paid' THEN 'accounts'
                    WHEN ea.adjustment_type = 'return' AND ea.status = 'pending' THEN 'accounts'
                    WHEN ea.adjustment_type = 'extra' AND ea.status = 'pending' THEN 'qc'
                    WHEN ea.adjustment_type = 'extra' AND ea.status = 'qc_approved' THEN 'management'
                    WHEN ea.adjustment_type = 'extra' AND ea.status = 'approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN ea.status = 'rejected' THEN 'rejected'
                    WHEN ea.status IN ('fully_paid', 'approved') THEN 'approved'
                    WHEN ea.status = 'partially_paid' THEN 'partially_paid'
                    ELSE 'pending'
                END,
                ar.approved_amount = CASE
                    WHEN ea.status IN ('approved', 'fully_paid', 'partially_paid') THEN COALESCE(ea.approved_amount, ea.amount, ar.request_amount)
                    WHEN ea.status = 'qc_approved' THEN COALESCE(ea.approved_amount, ar.approved_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_by = CASE
                    WHEN ea.approved_by IS NOT NULL THEN ea.approved_by
                    ELSE ar.approved_by
                END,
                ar.approved_at = CASE
                    WHEN ea.approved_at IS NOT NULL THEN ea.approved_at
                    ELSE ar.approved_at
                END,
                ar.approval_note = CASE
                    WHEN ea.note IS NOT NULL AND ea.note != '' THEN ea.note
                    ELSE ar.approval_note
                END
            WHERE 
                (ea.status = 'rejected' AND ar.status != 'rejected')
                OR (ea.status = 'fully_paid' AND (ar.approve_role != 'completed' OR ar.status != 'approved'))
                OR (ea.adjustment_type = 'return' AND ea.status = 'pending' AND (ar.approve_role != 'accounts' OR ar.status != 'pending'))
                OR (ea.adjustment_type = 'extra' AND ea.status = 'pending' AND (ar.approve_role != 'qc' OR ar.status != 'pending'))
                OR (ea.adjustment_type = 'extra' AND ea.status = 'qc_approved' AND (ar.approve_role != 'management' OR ar.status != 'pending'))
                OR (ea.adjustment_type = 'extra' AND ea.status = 'approved' AND (ar.approve_role != 'accounts' OR ar.status != 'approved'))
                OR (ar.status = '' OR ar.status IS NULL)
        ";
        try {
            $this->db->query($syncSql);
        } catch (\Throwable $e) {
            error_log("syncMissingExpenseAdjustments status sync error: " . $e->getMessage());
        }
    }

    public function syncMissingLandFundRequisitions()
    {
        // 1. Insert missing land_fund_requisitions into approval_requests
        $sql = "
            SELECT lfr.*, la.land_owner_name, p.name AS project_name, u.full_name AS requester_name
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            LEFT JOIN projects p ON la.project_id = p.id
            LEFT JOIN users u ON lfr.requested_by = u.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'land_fund_requisitions' AND ar.source_id = lfr.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId = (int)$row['id'];
                $deptName = 'Land';
                $reqTypeLabel = !empty($row['requisition_type']) ? $row['requisition_type'] : 'Land Requisition';
                $ownerName = !empty($row['land_owner_name']) ? $row['land_owner_name'] : 'Land Owner';
                $projectName = !empty($row['project_name']) ? $row['project_name'] : 'Land Project';
                $title = "Land Fund: {$reqTypeLabel} — {$ownerName} | {$projectName}";
                $amount = (float)($row['requested_amount'] ?? 0);
                $approvedAmount = $amount;
                $requestedBy = !empty($row['requested_by']) ? (int)$row['requested_by'] : 1;

                $mgmtSt = $row['mgmt_status'] ?? 'Pending';
                $accSt = $row['accounts_status'] ?? 'Pending';

                if ($mgmtSt === 'Rejected' || $accSt === 'Rejected') {
                    $status = 'rejected';
                    $approveRole = 'rejected';
                    $approvedBy = !empty($row['mgmt_by']) ? (int)$row['mgmt_by'] : (!empty($row['accounts_by']) ? (int)$row['accounts_by'] : null);
                    $approvedAt = !empty($row['mgmt_approved_at']) ? $row['mgmt_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['mgmt_note']) ? $row['mgmt_note'] : (!empty($row['accounts_note']) ? $row['accounts_note'] : null);
                } elseif ($accSt === 'Paid') {
                    $status = 'approved';
                    $approveRole = 'completed';
                    $approvedBy = !empty($row['mgmt_by']) ? (int)$row['mgmt_by'] : (!empty($row['accounts_by']) ? (int)$row['accounts_by'] : null);
                    $approvedAt = !empty($row['accounts_paid_at']) ? $row['accounts_paid_at'] : (!empty($row['mgmt_approved_at']) ? $row['mgmt_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null));
                    $approvalNote = !empty($row['accounts_note']) ? $row['accounts_note'] : (!empty($row['mgmt_note']) ? $row['mgmt_note'] : null);
                } elseif ($mgmtSt === 'Approved') {
                    $status = 'approved';
                    $approveRole = 'accounts';
                    $approvedBy = !empty($row['mgmt_by']) ? (int)$row['mgmt_by'] : null;
                    $approvedAt = !empty($row['mgmt_approved_at']) ? $row['mgmt_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['mgmt_note']) ? $row['mgmt_note'] : null;
                } else {
                    $status = 'pending';
                    $approveRole = 'management';
                    $approvedBy = null;
                    $approvedAt = null;
                    $approvalNote = null;
                }

                $metaData = json_encode([
                    'purpose' => $row['purpose'] ?? '',
                    'land_owner_name' => $row['land_owner_name'] ?? '',
                    'project_name' => $row['project_name'] ?? '',
                    'requisition_type' => $row['requisition_type'] ?? ''
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, 'land_fund_requisitions', ?, 'land_fund_requisitions', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("ssiddississss", $deptName, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing land_fund_requisitions in approval_requests
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN land_fund_requisitions lfr ON (ar.source_table = 'land_fund_requisitions' AND ar.source_id = lfr.id)
            SET ar.approve_role = CASE 
                    WHEN lfr.mgmt_status = 'Rejected' OR lfr.accounts_status = 'Rejected' THEN 'rejected'
                    WHEN lfr.accounts_status = 'Paid' THEN 'completed'
                    WHEN lfr.mgmt_status = 'Approved' THEN CASE WHEN ar.accounts_head_status = 'approved' THEN 'accounts' ELSE 'accounts_head' END
                    ELSE 'management'
                END,
                ar.status = CASE 
                    WHEN lfr.mgmt_status = 'Rejected' OR lfr.accounts_status = 'Rejected' THEN 'rejected'
                    WHEN lfr.mgmt_status = 'Approved' OR lfr.accounts_status = 'Paid' THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approved_by = COALESCE(lfr.mgmt_by, ar.approved_by),
                ar.approved_at = COALESCE(lfr.accounts_paid_at, lfr.mgmt_approved_at, lfr.updated_at, ar.approved_at),
                ar.approval_note = COALESCE(lfr.accounts_note, lfr.mgmt_note, ar.approval_note),
                ar.request_amount = lfr.requested_amount,
                ar.approved_amount = lfr.requested_amount
            WHERE ar.status = '' 
               OR ar.status IS NULL 
               OR (lfr.accounts_status = 'Paid' AND ar.approve_role != 'completed')
               OR (lfr.mgmt_status = 'Approved' AND lfr.accounts_status != 'Paid' AND (ar.approve_role != 'accounts' OR ar.status != 'approved'))
               OR (lfr.mgmt_status = 'Rejected' AND ar.status != 'rejected')
        ";
        try {
            $this->db->query($syncSql);
        } catch (\Throwable $e) {
            error_log("syncMissingLandFundRequisitions sync error: " . $e->getMessage());
        }
    }


    /**
     * Helper to get user, employee, and office branch info with memoization
     */
    public function getDisburserInfo($userId)
    {
        if (empty($userId)) return null;
        static $disburserCache = [];
        if (isset($disburserCache[$userId])) {
            return $disburserCache[$userId];
        }

        $stmt = $this->db->prepare("
            SELECT u.id, u.full_name, e.employee_id, COALESCE(ob.name, 'Corporate Gulshan Office') as branch_name, COALESCE(ob.id, 1) as branch_id, r.name as role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN office_branches ob ON ob.id = e.office_branch_id
            WHERE u.id = ?
            LIMIT 1
        ");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $disburserCache[$userId] = $row;
            return $row;
        }
        return null;
    }

    /**
     * Get All Disbursed Transactions across the 14 expense & requisition modules
     */
    public function getBranchDisbursementTransactions($filters = [])
    {
        $queries = [];

        // 1. con_financial_transactions (vendor_payment, contractor_bill, operational_fund)
        $queries[] = "
            SELECT 
                cft.id as txn_id,
                COALESCE(cft.transaction_date, DATE(cft.created_at)) as payout_date,
                cft.created_at,
                CASE 
                    WHEN cft.ref_type = 'vendor_payment' THEN 'Vendor Payments'
                    WHEN cft.ref_type = 'contractor_bill' THEN 'Contractor Bills'
                    WHEN cft.ref_type = 'operational_fund' THEN 'Operational Fund'
                    ELSE 'Financial Transaction'
                END as module_name,
                CASE 
                    WHEN cft.ref_type = 'vendor_payment' THEN 'vendor_payment_requests'
                    WHEN cft.ref_type = 'contractor_bill' THEN 'contractor_bill_requests'
                    WHEN cft.ref_type = 'operational_fund' THEN 'operational_fund_requests'
                    ELSE cft.ref_type
                END as source_table,
                cft.ref_id as source_id,
                cft.amount_paid as amount,
                cft.payment_method,
                COALESCE(cft.reference_number, CONCAT('CFT-', cft.id)) as reference_no,
                COALESCE(cft.notes, 'Construction Disbursement') as title,
                COALESCE(p.name, 'Corporate / Construction') as project_name,
                CASE 
                    WHEN cft.ref_type = 'vendor_payment' THEN COALESCE(v.name, 'Vendor')
                    WHEN cft.ref_type = 'contractor_bill' THEN COALESCE(c.name, 'Contractor')
                    ELSE 'Recipient'
                END as payee_name,
                cft.processed_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM con_financial_transactions cft
            LEFT JOIN projects p ON p.id = cft.project_id
            LEFT JOIN vendor_payment_requests vpr ON (cft.ref_type = 'vendor_payment' AND vpr.id = cft.ref_id)
            LEFT JOIN vendors v ON v.id = vpr.vendor_id
            LEFT JOIN contractor_bill_requests cbr ON (cft.ref_type = 'contractor_bill' AND cbr.id = cft.ref_id)
            LEFT JOIN contractors c ON c.id = cbr.operator_id
            LEFT JOIN users u_disb ON u_disb.id = cft.processed_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE cft.amount_paid > 0
        ";

        // 2. fund_requisitions
        $queries[] = "
            SELECT 
                fr.id as txn_id,
                COALESCE(DATE(fr.paid_at), DATE(fr.updated_at)) as payout_date,
                fr.updated_at as created_at,
                'Fund Requisitions' as module_name,
                'fund_requisitions' as source_table,
                fr.id as source_id,
                COALESCE(fr.paid_amount, fr.amount) as amount,
                COALESCE(fr.payment_method, 'Cash') as payment_method,
                CONCAT('FR-', fr.id) as reference_no,
                fr.purpose as title,
                COALESCE(p.name, 'Operations') as project_name,
                u_req.full_name as payee_name,
                fr.paid_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM fund_requisitions fr
            LEFT JOIN projects p ON p.id = fr.project_id
            LEFT JOIN users u_req ON u_req.id = fr.requested_by
            LEFT JOIN users u_disb ON u_disb.id = fr.paid_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE (fr.status IN ('paid', 'partially_paid') OR fr.paid_by IS NOT NULL) AND COALESCE(fr.paid_amount, fr.amount) > 0
        ";

        // 3. con_labor_requisitions
        $queries[] = "
            SELECT 
                lr.id as txn_id,
                COALESCE(lr.payment_date, DATE(lr.labor_disbursed_at), DATE(lr.updated_at)) as payout_date,
                lr.updated_at as created_at,
                'SE Labor Bills' as module_name,
                'con_labor_requisitions' as source_table,
                lr.id as source_id,
                COALESCE(lr.total_paid, lr.approved_amount, lr.total_amount) as amount,
                COALESCE(lr.payment_method, 'Cash') as payment_method,
                COALESCE(lr.requisition_no, CONCAT('LR-', lr.id)) as reference_no,
                lr.purpose as title,
                COALESCE(p.name, 'Construction Site') as project_name,
                lr.labor_type as payee_name,
                COALESCE(lr.labor_disbursed_by, lr.paid_by) as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM con_labor_requisitions lr
            LEFT JOIN projects p ON p.id = lr.project_id
            LEFT JOIN users u_disb ON u_disb.id = COALESCE(lr.labor_disbursed_by, lr.paid_by)
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE (lr.status IN ('paid', 'labor_disbursed', 'partially_paid') OR lr.paid_by IS NOT NULL OR lr.labor_disbursed_by IS NOT NULL) 
              AND COALESCE(lr.total_paid, lr.approved_amount, lr.total_amount) > 0
        ";

        // 4. project_extra_assets
        $queries[] = "
            SELECT 
                pea.id as txn_id,
                COALESCE(DATE(pea.paid_at), DATE(pea.updated_at)) as payout_date,
                pea.updated_at as created_at,
                'Office Procurement' as module_name,
                'project_extra_assets' as source_table,
                pea.id as source_id,
                COALESCE(pea.paid_amount, pea.total_price) as amount,
                COALESCE(pea.payment_method, 'Cash') as payment_method,
                CONCAT('PR-', pea.id) as reference_no,
                pea.item_name as title,
                COALESCE(p.name, 'Corporate') as project_name,
                COALESCE(pea.vendor_name, u_req.full_name, 'Supplier') as payee_name,
                pea.paid_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, pea.office_branch_id, 1) as branch_id,
                COALESCE(ob_disb.name, ob_pea.name, 'Corporate Gulshan Office') as branch_name
            FROM project_extra_assets pea
            LEFT JOIN projects p ON p.id = pea.project_id
            LEFT JOIN users u_req ON u_req.id = pea.requested_by
            LEFT JOIN users u_disb ON u_disb.id = pea.paid_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN office_branches ob_pea ON ob_pea.id = pea.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE (pea.payment_status = 'paid' OR pea.paid_by IS NOT NULL) AND COALESCE(pea.paid_amount, pea.total_price) > 0
        ";

        // 5. project_other_costs
        $queries[] = "
            SELECT 
                poc.id as txn_id,
                COALESCE(DATE(poc.payment_at), DATE(poc.updated_at)) as payout_date,
                poc.updated_at as created_at,
                'Project Other Costs' as module_name,
                'project_other_costs' as source_table,
                poc.id as source_id,
                COALESCE(poc.payment_amount, poc.approved_amount, poc.request_amount) as amount,
                'Cash' as payment_method,
                CONCAT('POC-', poc.id) as reference_no,
                poc.purpose as title,
                COALESCE(p.name, 'Project') as project_name,
                u_req.full_name as payee_name,
                poc.payment_by_id as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM project_other_costs poc
            LEFT JOIN projects p ON p.id = poc.project_id
            LEFT JOIN users u_req ON u_req.id = poc.created_by_id
            LEFT JOIN users u_disb ON u_disb.id = poc.payment_by_id
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE (poc.status = 'paid' OR poc.payment_by_id IS NOT NULL) AND COALESCE(poc.payment_amount, poc.approved_amount, poc.request_amount) > 0
        ";

        // 6. land_fund_requisitions
        $queries[] = "
            SELECT 
                lfr.id as txn_id,
                COALESCE(DATE(lfr.accounts_paid_at), DATE(lfr.updated_at)) as payout_date,
                lfr.updated_at as created_at,
                'Land Fund Requisitions' as module_name,
                'land_fund_requisitions' as source_table,
                lfr.id as source_id,
                lfr.requested_amount as amount,
                COALESCE(lfr.payment_method, 'Bank Transfer') as payment_method,
                CONCAT('LFR-', lfr.id) as reference_no,
                lfr.purpose as title,
                'Land Development' as project_name,
                u_req.full_name as payee_name,
                lfr.accounts_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM land_fund_requisitions lfr
            LEFT JOIN users u_req ON u_req.id = lfr.requested_by
            LEFT JOIN users u_disb ON u_disb.id = lfr.accounts_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE (lfr.accounts_status = 'Paid' OR lfr.accounts_by IS NOT NULL) AND lfr.requested_amount > 0
        ";

        // 7. payslips
        $queries[] = "
            SELECT 
                ps.id as txn_id,
                COALESCE(DATE(ps.disbursed_at), DATE(ps.updated_at)) as payout_date,
                ps.updated_at as created_at,
                'Payslips & Salary' as module_name,
                'payslips' as source_table,
                ps.id as source_id,
                ps.net_salary as amount,
                COALESCE(ps.payment_method, 'Bank') as payment_method,
                CONCAT('PAY-', ps.id) as reference_no,
                CONCAT('Salary: ', ps.payslip_month) as title,
                'Payroll' as project_name,
                e_payee.full_name as payee_name,
                ps.disbursed_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM payslips ps
            LEFT JOIN employees e_payee ON ps.employee_id = e_payee.id
            LEFT JOIN users u_disb ON u_disb.id = ps.disbursed_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE (ps.status = 'paid' OR ps.payment_status = 'paid' OR ps.disbursed_by IS NOT NULL) AND ps.net_salary > 0
        ";

        // 8. project_incentive_payments
        $queries[] = "
            SELECT 
                pip.id as txn_id,
                COALESCE(pip.payment_date, DATE(pip.created_at)) as payout_date,
                pip.created_at,
                'Sales Incentives' as module_name,
                'project_incentive_claims' as source_table,
                pip.claim_id as source_id,
                pip.payment_amount as amount,
                COALESCE(pip.payment_method, 'Cash') as payment_method,
                CONCAT('INC-', pip.id) as reference_no,
                'Sales Incentive Disbursement' as title,
                'Sales Department' as project_name,
                u_payee.full_name as payee_name,
                pip.disbursed_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM project_incentive_payments pip
            LEFT JOIN project_incentive_claims pic ON pic.id = pip.claim_id
            LEFT JOIN users u_payee ON u_payee.id = pic.user_id
            LEFT JOIN users u_disb ON u_disb.id = pip.disbursed_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE pip.payment_amount > 0
        ";

        // 9. cashout_requests
        $queries[] = "
            SELECT 
                co.id as txn_id,
                COALESCE(DATE(co.approved_at), DATE(co.updated_at)) as payout_date,
                co.updated_at as created_at,
                'Cashout Requests' as module_name,
                'cashout_requests' as source_table,
                co.id as source_id,
                COALESCE(co.approved_amount, co.amount) as amount,
                COALESCE(co.payment_method, 'Cash') as payment_method,
                CONCAT('CO-', co.id) as reference_no,
                co.description as title,
                'Accounts Office' as project_name,
                co.purpose_paid_to as payee_name,
                co.approved_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM cashout_requests co
            LEFT JOIN users u_disb ON u_disb.id = co.approved_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE co.status IN ('fully_paid', 'partially_paid') AND COALESCE(co.approved_amount, co.amount) > 0
        ";

        // 10. expense_adjustments
        $queries[] = "
            SELECT 
                ea.id as txn_id,
                DATE(ea.updated_at) as payout_date,
                ea.updated_at as created_at,
                'Requisition Settlements' as module_name,
                'expense_adjustments' as source_table,
                ea.id as source_id,
                COALESCE(ea.approved_amount, ea.amount) as amount,
                'Cash' as payment_method,
                CONCAT('ADJ-', ea.id) as reference_no,
                ea.title as title,
                'Settlement / Audit' as project_name,
                u_req.full_name as payee_name,
                ea.approved_by as disburser_id,
                COALESCE(u_disb.full_name, 'Accounts Officer') as disburser_name,
                e_disb.employee_id as disburser_code,
                COALESCE(des_disb.name, r_disb.name, 'Accounts Officer') as disburser_designation,
                COALESCE(r_disb.name, 'Accounts') as disburser_role,
                COALESCE(ob_disb.id, 1) as branch_id,
                COALESCE(ob_disb.name, 'Corporate Gulshan Office') as branch_name
            FROM expense_adjustments ea
            LEFT JOIN users u_req ON u_req.id = ea.user_id
            LEFT JOIN users u_disb ON u_disb.id = ea.approved_by
            LEFT JOIN employees e_disb ON (e_disb.user_id = u_disb.id OR e_disb.id = u_disb.employee_id)
            LEFT JOIN designations des_disb ON des_disb.id = COALESCE(e_disb.designation_id, u_disb.designation_id)
            LEFT JOIN office_branches ob_disb ON ob_disb.id = e_disb.office_branch_id
            LEFT JOIN roles r_disb ON r_disb.id = u_disb.role_id
            WHERE ea.status IN ('fully_paid', 'partially_paid') AND COALESCE(ea.approved_amount, ea.amount) > 0
        ";

        // Execute and accumulate all rows
        $allRows = [];
        foreach ($queries as $sql) {
            $res = $this->db->query($sql);
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $allRows[] = $r;
                }
            }
        }

        // Apply filters
        $branchFilter = !empty($filters['branch_id']) ? (int)$filters['branch_id'] : 0;
        $disburserFilter = !empty($filters['disburser_id']) ? (int)$filters['disburser_id'] : 0;
        $moduleFilter = !empty($filters['module']) ? strtolower(trim($filters['module'])) : '';
        $dateFrom = !empty($filters['date_from']) ? $filters['date_from'] : '';
        $dateTo = !empty($filters['date_to']) ? $filters['date_to'] : '';
        $searchTerm = !empty($filters['search']) ? strtolower(trim($filters['search'])) : '';

        $filtered = [];
        foreach ($allRows as $item) {
            if ($branchFilter > 0 && (int)$item['branch_id'] !== $branchFilter) {
                continue;
            }
            if ($disburserFilter > 0 && (int)$item['disburser_id'] !== $disburserFilter) {
                continue;
            }
            if ($moduleFilter !== '' && strtolower($item['source_table']) !== $moduleFilter && strtolower($item['module_name']) !== $moduleFilter) {
                continue;
            }
            if ($dateFrom !== '' && $item['payout_date'] < $dateFrom) {
                continue;
            }
            if ($dateTo !== '' && $item['payout_date'] > $dateTo) {
                continue;
            }
            if ($searchTerm !== '') {
                $searchFields = strtolower(
                    ($item['reference_no'] ?? '') . ' ' . 
                    ($item['title'] ?? '') . ' ' . 
                    ($item['payee_name'] ?? '') . ' ' . 
                    ($item['disburser_name'] ?? '') . ' ' . 
                    ($item['disburser_code'] ?? '') . ' ' . 
                    ($item['branch_name'] ?? '') . ' ' . 
                    ($item['project_name'] ?? '') . ' ' .
                    ($item['module_name'] ?? '')
                );
                if (strpos($searchFields, $searchTerm) === false) {
                    continue;
                }
            }

            // Ensure alias keys for view compatibility
            $item['module_label'] = $item['module_name'] ?? 'Requisition';
            $item['ref_no'] = $item['reference_no'] ?? ('REF-' . ($item['source_id'] ?? 0));
            $item['purpose'] = $item['title'] ?? 'Disbursement Settlement';
            $item['disbursed_by_name'] = $item['disburser_name'] ?? 'Accounts Staff';
            $item['disbursed_by_code'] = $item['disburser_code'] ?? '';
            $item['disbursed_by_id'] = $item['disburser_id'] ?? 0;
            $item['disbursed_by_role'] = $item['disburser_role'] ?? 'Accounts';
            $item['voucher_url'] = ($GLOBALS['base_url'] ?? '/TPHL-ERP') . '/print-debit-voucher?module=' . urlencode($item['source_table']) . '&id=' . (int)$item['source_id'];

            $filtered[] = $item;
        }

        // Sort by payout_date DESC, created_at DESC
        usort($filtered, function($a, $b) {
            return strcmp(($b['payout_date'] ?? '') . ' ' . ($b['created_at'] ?? ''), ($a['payout_date'] ?? '') . ' ' . ($a['created_at'] ?? ''));
        });

        return $filtered;
    }

    /**
     * Get Office & Branch Disbursement Summary for KPI Cards
     */
    public function getBranchDisbursementSummary($filters = [])
    {
        // Fetch office branches
        $branchMap = [];
        $bRes = $this->db->query("SELECT id, name FROM office_branches ORDER BY id ASC");
        if ($bRes) {
            while ($b = $bRes->fetch_assoc()) {
                $bId = (int)$b['id'];
                $branchMap[$bId] = [
                    'branch_id'         => $bId,
                    'branch_name'       => $b['name'],
                    'branch_code'       => 'OB-' . $bId,
                    'total_amount'      => 0.00,
                    'transaction_count' => 0,
                    'officers'          => []
                ];
            }
        }

        // Fetch all transactions without branch filter so cards show full branch distribution
        $unfilteredBranchFilters = $filters;
        unset($unfilteredBranchFilters['branch_id']);
        $transactions = $this->getBranchDisbursementTransactions($unfilteredBranchFilters);

        $grandTotal = 0.00;
        $allOfficers = [];

        foreach ($transactions as $t) {
            $bId = (int)($t['branch_id'] ?? 1);
            $amt = (float)($t['amount'] ?? 0);
            $disburserId = (int)($t['disburser_id'] ?? 0);
            $disburserName = !empty($t['disburser_name']) ? $t['disburser_name'] : 'Accounts Officer';
            $disburserKey = $disburserId > 0 ? (string)$disburserId : $disburserName;
            $grandTotal += $amt;

            if (!isset($branchMap[$bId])) {
                $branchMap[$bId] = [
                    'branch_id'         => $bId,
                    'branch_name'       => !empty($t['branch_name']) ? $t['branch_name'] : 'Corporate Gulshan Office',
                    'branch_code'       => 'OB-' . $bId,
                    'total_amount'      => 0.00,
                    'transaction_count' => 0,
                    'officers'          => []
                ];
            }

            $branchMap[$bId]['total_amount'] += $amt;
            $branchMap[$bId]['transaction_count']++;

            if (!isset($branchMap[$bId]['officers'][$disburserKey])) {
                $branchMap[$bId]['officers'][$disburserKey] = [
                    'id'     => $disburserId,
                    'name'   => $disburserName,
                    'code'   => $t['disburser_code'] ?? '',
                    'role'   => $t['disburser_role'] ?? 'Accounts',
                    'amount' => 0.00,
                    'count'  => 0
                ];
            }
            $branchMap[$bId]['officers'][$disburserKey]['amount'] += $amt;
            $branchMap[$bId]['officers'][$disburserKey]['count']++;

            $allOfficers[$disburserKey] = ($allOfficers[$disburserKey] ?? 0.00) + $amt;
        }

        // Sort officers inside each branch by total amount descending and convert to indexed array
        foreach ($branchMap as $bId => &$bData) {
            $officersList = array_values($bData['officers']);
            usort($officersList, function($a, $b) {
                return $b['amount'] <=> $a['amount'];
            });
            $bData['officers'] = $officersList;
        }
        unset($bData);

        return [
            'branches' => $branchMap,
            'totals'   => [
                'total_amount'          => $grandTotal,
                'transaction_count'     => count($transactions),
                'active_officers_count' => count($allOfficers),
                'active_branches_count' => count(array_filter($branchMap, fn($b) => $b['transaction_count'] > 0))
            ]
        ];
    }

    /**
     * Get List of Active Disbursing Accounts Officers for Filter Dropdown
     */
    public function getDisbursingOfficersList()
    {
        $officers = [];
        $sql = "
            SELECT DISTINCT 
                u.id as user_id, 
                u.full_name as name, 
                COALESCE(e.employee_id, CONCAT('ID:', u.id)) as employee_code, 
                COALESCE(ob.name, 'Corporate Gulshan Office') as branch_name, 
                COALESCE(r.name, 'Accounts Staff') as role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id)
            LEFT JOIN office_branches ob ON ob.id = e.office_branch_id
            WHERE u.id IN (
                SELECT DISTINCT processed_by FROM con_financial_transactions WHERE processed_by IS NOT NULL
                UNION
                SELECT DISTINCT paid_by FROM fund_requisitions WHERE paid_by IS NOT NULL
                UNION
                SELECT DISTINCT labor_disbursed_by FROM con_labor_requisitions WHERE labor_disbursed_by IS NOT NULL
                UNION
                SELECT DISTINCT paid_by FROM project_extra_assets WHERE paid_by IS NOT NULL
                UNION
                SELECT DISTINCT payment_by_id FROM project_other_costs WHERE payment_by_id IS NOT NULL
                UNION
                SELECT DISTINCT accounts_by FROM land_fund_requisitions WHERE accounts_by IS NOT NULL
                UNION
                SELECT DISTINCT disbursed_by FROM payslips WHERE disbursed_by IS NOT NULL
                UNION
                SELECT DISTINCT disbursed_by FROM project_incentive_payments WHERE disbursed_by IS NOT NULL
            )
            ORDER BY u.full_name ASC
        ";
        $res = $this->db->query($sql);
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $officers[] = $r;
            }
        }
        return $officers;
    }

    /**
     * Ensure all non-draft payslips in `payslips` table have a corresponding record in `approval_requests`
     */
    public function syncMissingPayslips()
    {
        try {
            $sql = "SELECT p.*, e.full_name as emp_name, e.employee_id as emp_code
                    FROM payslips p
                    LEFT JOIN approval_requests ar ON ar.source_table = 'payslips' AND ar.source_id = p.id
                    LEFT JOIN employees e ON e.id = p.employee_id
                    WHERE ar.id IS NULL AND (p.status IS NULL OR p.status NOT IN ('draft', 'on_hold'))";
            $res = $this->db->query($sql);
            if ($res && $res->num_rows > 0) {
                $insStmt = $this->db->prepare("INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, requested_days, requested_by, approve_role, meta_data, request_amount, approved_amount, status, approved_by, approved_at, accounts_head_status, accounts_head_by, accounts_head_at, created_at)
                    VALUES ('HR', 'salary', ?, 'payslips', ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($insStmt) {
                    while ($row = $res->fetch_assoc()) {
                        $psId = (int)$row['id'];
                        $empId = (int)($row['employee_id'] ?? 0);
                        $empLabel = trim(($row['emp_name'] ?? 'Employee') . (!empty($row['emp_code']) ? ' (' . $row['emp_code'] . ')' : ''));
                        $psDate = !empty($row['payslip_month']) ? $row['payslip_month'] : (!empty($row['created_at']) ? $row['created_at'] : date('Y-m-01'));
                        $monthLabel = date('F Y', strtotime($psDate));
                        $title = 'Salary Approval';
                        $amt = (float)($row['net_salary'] ?? 0);
                        $metaData = json_encode([
                            'employee_id'   => $empId,
                            'employee_name' => $empLabel,
                            'net_salary'    => $amt,
                            'month'         => date('Y-m-01', strtotime($psDate))
                        ]);
                        $reqBy = !empty($row['created_by']) ? (int)$row['created_by'] : 1;
                        $pStatus = strtolower($row['status'] ?? 'pending');
                        $payStatus = strtolower($row['payment_status'] ?? 'unpaid');
                        $isPaid = ($pStatus === 'paid' || $payStatus === 'paid');
                        $arStatus = $isPaid ? 'approved' : (in_array($pStatus, ['approved', 'rejected']) ? $pStatus : 'pending');
                        $ahStatus = strtolower($row['accounts_head_status'] ?? 'pending');
                        if ($isPaid) {
                            $ahStatus = 'approved';
                        }
                        $appRole = ($arStatus === 'approved' && $ahStatus === 'approved') ? 'accounts' : (($arStatus === 'approved' && $ahStatus !== 'approved') ? 'accounts_head' : 'management');
                        $appAmt = ($arStatus === 'approved') ? $amt : null;
                        $appBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                        $appAt = !empty($row['approved_at']) ? $row['approved_at'] : null;
                        $ahBy = !empty($row['accounts_head_by']) ? (int)$row['accounts_head_by'] : null;
                        $ahAt = !empty($row['accounts_head_at']) ? $row['accounts_head_at'] : null;
                        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
                        $insStmt->bind_param("siissddsisssis", $title, $psId, $reqBy, $appRole, $metaData, $amt, $appAmt, $arStatus, $appBy, $appAt, $ahStatus, $ahBy, $ahAt, $createdAt);
                        $insStmt->execute();
                    }
                    $insStmt->close();
                }
            }
        } catch (\Throwable $e) {
            error_log("syncMissingPayslips error: " . $e->getMessage());
        }
    }
}