<?php

class RequisitionRevisionService
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `requisition_revisions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `module_table` VARCHAR(64) NOT NULL,
                `source_id` INT UNSIGNED NOT NULL,
                `status` ENUM('open', 'resubmitted', 'resolved') NOT NULL DEFAULT 'open',
                `return_stage` VARCHAR(50) NOT NULL,
                `return_stage_label` VARCHAR(100) NOT NULL,
                `requested_by` INT NOT NULL,
                `requested_by_name` VARCHAR(150) NULL DEFAULT NULL,
                `requested_by_role` VARCHAR(100) NULL DEFAULT NULL,
                `review_note` TEXT NOT NULL,
                `creator_user_id` INT NOT NULL DEFAULT 0,
                `old_amount` DECIMAL(15,2) NULL DEFAULT NULL,
                `new_amount` DECIMAL(15,2) NULL DEFAULT NULL,
                `resubmitted_at` DATETIME NULL DEFAULT NULL,
                `resubmitted_note` TEXT NULL DEFAULT NULL,
                `revision_type` VARCHAR(30) NOT NULL DEFAULT 'review',
                `attachment_path` VARCHAR(255) NULL DEFAULT NULL,
                `resolved_at` DATETIME NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_rev_mod_src_status` (`module_table`, `source_id`, `status`),
                KEY `idx_rev_creator_status` (`creator_user_id`, `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // Safely upgrade existing requisition_revisions table with rejection-appeal columns
        $colCheck = @$this->db->query("SHOW COLUMNS FROM `requisition_revisions` LIKE 'revision_type'");
        if ($colCheck && $colCheck->num_rows === 0) {
            @$this->db->query("ALTER TABLE `requisition_revisions` ADD COLUMN `revision_type` VARCHAR(30) NOT NULL DEFAULT 'review' AFTER `resubmitted_note`");
        }
        $attCheck = @$this->db->query("SHOW COLUMNS FROM `requisition_revisions` LIKE 'attachment_path'");
        if ($attCheck && $attCheck->num_rows === 0) {
            @$this->db->query("ALTER TABLE `requisition_revisions` ADD COLUMN `attachment_path` VARCHAR(255) NULL DEFAULT NULL AFTER `revision_type`");
        }

        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `requisition_edit_logs` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `module_table` VARCHAR(64) NOT NULL,
                `source_id` INT UNSIGNED NOT NULL,
                `edited_by` INT NOT NULL,
                `edited_by_name` VARCHAR(150) NOT NULL,
                `edited_by_role` VARCHAR(100) NULL DEFAULT NULL,
                `change_summary` TEXT NULL DEFAULT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_rel_mod_src` (`module_table`, `source_id`, `id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    public function getOpenRevision(string $moduleTable, int $sourceId): ?array
    {
        if (!$this->db || $sourceId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND source_id = ? AND status = 'open'
            ORDER BY id DESC LIMIT 1
        ");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('si', $moduleTable, $sourceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getLatestActiveOrResubmitted(string $moduleTable, int $sourceId): ?array
    {
        if (!$this->db || $sourceId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND source_id = ? AND status IN ('open', 'resubmitted')
            ORDER BY id DESC LIMIT 1
        ");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('si', $moduleTable, $sourceId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    public function getHistory(string $moduleTable, int $sourceId): array
    {
        if (!$this->db || $sourceId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND source_id = ?
            ORDER BY id DESC
        ");
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('si', $moduleTable, $sourceId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows ?: [];
    }

    public function getOpenRevisionMap(string $moduleTable): array
    {
        if (!$this->db) {
            return [];
        }
        $map = [];
        $stmt = $this->db->prepare("
            SELECT * FROM requisition_revisions
            WHERE module_table = ? AND status = 'open'
            ORDER BY id DESC
        ");
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $moduleTable);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $sid = (int)$row['source_id'];
            if (!isset($map[$sid])) {
                $map[$sid] = $row;
            }
        }
        $stmt->close();
        return $map;
    }

    public function sendBackForReview(
        string $moduleTable,
        int $sourceId,
        string $returnStage,
        string $returnStageLabel,
        int $approverUserId,
        string $approverName,
        string $approverRole,
        string $reviewNote,
        int $creatorUserId,
        ?float $oldAmount = null
    ): bool {
        if (!$this->db || $sourceId <= 0 || trim($reviewNote) === '') {
            return false;
        }

        // Resolve any previously open/resubmitted rows before opening a new one
        $stmtClose = $this->db->prepare("
            UPDATE requisition_revisions
            SET status = 'resolved', resolved_at = NOW()
            WHERE module_table = ? AND source_id = ? AND status IN ('open', 'resubmitted')
        ");
        if ($stmtClose) {
            $stmtClose->bind_param('si', $moduleTable, $sourceId);
            $stmtClose->execute();
            $stmtClose->close();
        }

        $stmt = $this->db->prepare("
            INSERT INTO requisition_revisions
            (module_table, source_id, status, return_stage, return_stage_label, requested_by, requested_by_name, requested_by_role, review_note, creator_user_id, old_amount)
            VALUES (?, ?, 'open', ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param(
            'sississsid',
            $moduleTable,
            $sourceId,
            $returnStage,
            $returnStageLabel,
            $approverUserId,
            $approverName,
            $approverRole,
            $reviewNote,
            $creatorUserId,
            $oldAmount
        );
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }

    public function markResubmitted(string $moduleTable, int $sourceId, ?float $newAmount = null, ?string $resubmittedNote = null): bool
    {
        if (!$this->db || $sourceId <= 0) {
            return false;
        }
        $stmt = $this->db->prepare("
            UPDATE requisition_revisions
            SET status = 'resubmitted', new_amount = ?, resubmitted_note = ?, resubmitted_at = NOW(), updated_at = NOW()
            WHERE module_table = ? AND source_id = ? AND status = 'open'
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('dssi', $newAmount, $resubmittedNote, $moduleTable, $sourceId);
        $ok = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        return $ok && $affected > 0;
    }

    public function markResolved(string $moduleTable, int $sourceId): void
    {
        if (!$this->db || $sourceId <= 0) {
            return;
        }
        $stmt = $this->db->prepare("
            UPDATE requisition_revisions
            SET status = 'resolved', resolved_at = NOW(), updated_at = NOW()
            WHERE module_table = ? AND source_id = ? AND status IN ('open', 'resubmitted')
        ");
        if ($stmt) {
            $stmt->bind_param('si', $moduleTable, $sourceId);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Shared editable-field definitions per module for the unified
     * "Edit & Resubmit for Review (Against Rejection)" modal & controller.
     * Each entry: [column => ['label' => ..., 'type' => 'text|number|date|textarea', 'col' => 6|12]]
     */
    public static function getEditableFieldsConfig(string $moduleTable): array
    {
        $map = [
            'fund_requisitions' => [
                'amount'            => ['label' => 'Amount (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6, 'is_amount' => true],
                'location'          => ['label' => 'Location / Destination', 'type' => 'text', 'col' => 6],
                'mode_of_transport' => ['label' => 'Mode of Transport', 'type' => 'text', 'col' => 6],
                'purpose'           => ['label' => 'Purpose / Description', 'type' => 'textarea', 'col' => 12],
            ],
            'project_other_costs' => [
                'request_amount' => ['label' => 'Request Amount (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6, 'is_amount' => true],
                'purpose'        => ['label' => 'Purpose / Description', 'type' => 'textarea', 'col' => 12],
            ],
            'con_labor_requisitions' => [
                'labor_count'    => ['label' => 'Labor Count', 'type' => 'number', 'step' => '1', 'col' => 4],
                'per_labor_rate' => ['label' => 'Rate per Labor (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 4],
                'working_days'   => ['label' => 'Working Days', 'type' => 'number', 'step' => '1', 'col' => 4],
                'purpose'        => ['label' => 'Purpose / Work Description', 'type' => 'textarea', 'col' => 12],
            ],
            'vendor_payment_requests' => [
                'invoice_amount' => ['label' => 'Invoice / Request Amount (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6, 'is_amount' => true],
                'invoice_number' => ['label' => 'Invoice / Bill Number', 'type' => 'text', 'col' => 6],
            ],
            'contractor_bill_requests' => [
                'bill_amount'      => ['label' => 'Bill Amount (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6, 'is_amount' => true],
                'bill_date'        => ['label' => 'Bill Date', 'type' => 'date', 'col' => 6],
                'work_description' => ['label' => 'Work Description', 'type' => 'textarea', 'col' => 12],
            ],
            'purchase_requests' => [
                'required_date' => ['label' => 'Required Date', 'type' => 'date', 'col' => 6],
                'purpose'       => ['label' => 'Purpose', 'type' => 'text', 'col' => 6],
                'notes'         => ['label' => 'Notes / Remarks', 'type' => 'textarea', 'col' => 12],
            ],
            'purchase_orders' => [
                'order_date' => ['label' => 'Order Date', 'type' => 'date', 'col' => 6],
                'remarks'    => ['label' => 'PO Remarks', 'type' => 'textarea', 'col' => 12],
            ],
            'project_extra_assets' => [
                'item_name'     => ['label' => 'Item Name', 'type' => 'text', 'col' => 12],
                'quantity'      => ['label' => 'Quantity', 'type' => 'number', 'step' => '1', 'col' => 6],
                'unit_price'    => ['label' => 'Unit Price (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6],
                'justification' => ['label' => 'Justification / Purpose', 'type' => 'textarea', 'col' => 12],
            ],
            'land_fund_requisitions' => [
                'requested_amount' => ['label' => 'Requested Amount (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6, 'is_amount' => true],
                'purpose'          => ['label' => 'Purpose / Details', 'type' => 'textarea', 'col' => 12],
            ],
            'cashout_requests' => [
                'amount'          => ['label' => 'Amount (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6, 'is_amount' => true],
                'purpose_paid_to' => ['label' => 'Purpose / Paid To', 'type' => 'text', 'col' => 6],
                'description'     => ['label' => 'Description', 'type' => 'textarea', 'col' => 12],
            ],
            'vendors' => [
                'company_name' => ['label' => 'Company Name', 'type' => 'text', 'col' => 6],
                'name'         => ['label' => 'Contact Person', 'type' => 'text', 'col' => 6],
                'phone'        => ['label' => 'Phone', 'type' => 'text', 'col' => 6],
                'email'        => ['label' => 'Email', 'type' => 'text', 'col' => 6],
                'address'      => ['label' => 'Address', 'type' => 'textarea', 'col' => 12],
            ],
            'contractors' => [
                'name'       => ['label' => 'Contractor Name', 'type' => 'text', 'col' => 6],
                'trade'      => ['label' => 'Trade', 'type' => 'text', 'col' => 6],
                'phone'      => ['label' => 'Phone', 'type' => 'text', 'col' => 6],
                'daily_rate' => ['label' => 'Daily Rate (৳)', 'type' => 'number', 'step' => '0.01', 'col' => 6],
                'address'    => ['label' => 'Address', 'type' => 'textarea', 'col' => 12],
            ],
        ];
        return $map[$moduleTable] ?? [];
    }

    /**
     * Record a post-rejection appeal / resubmission by the creator (with valid cause + optional attachment).
     * Creates a 'resubmitted' revision row of type 'rejection_appeal' so the rejecting approver sees
     * the creator's explanation & attachment right on the review page.
     */
    public function recordRejectionAppeal(
        string $moduleTable,
        int $sourceId,
        string $returnStage,
        string $returnStageLabel,
        int $creatorUserId,
        string $originalRejectionNote,
        string $appealNote,
        ?string $attachmentPath = null,
        ?float $oldAmount = null,
        ?float $newAmount = null
    ): bool {
        if (!$this->db || $sourceId <= 0 || trim($appealNote) === '') {
            return false;
        }
        $this->ensureSchema();

        // Resolve any earlier open/resubmitted rows first
        $this->markResolved($moduleTable, $sourceId);

        $origNote = trim($originalRejectionNote) !== '' ? trim($originalRejectionNote) : 'Rejected at ' . $returnStageLabel;
        $revType  = 'rejection_appeal';
        $reqName  = $returnStageLabel;
        $reqRole  = $returnStageLabel;
        $reqBy    = 0;
        $finalNew = $newAmount ?? $oldAmount;

        $stmt = $this->db->prepare("
            INSERT INTO requisition_revisions
                (module_table, source_id, status, return_stage, return_stage_label,
                 requested_by, requested_by_name, requested_by_role, review_note,
                 creator_user_id, old_amount, new_amount, resubmitted_at, resubmitted_note,
                 revision_type, attachment_path)
            VALUES (?, ?, 'resubmitted', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param(
            'sississsiddsss',
            $moduleTable,
            $sourceId,
            $returnStage,
            $returnStageLabel,
            $reqBy,
            $reqName,
            $reqRole,
            $origNote,
            $creatorUserId,
            $oldAmount,
            $finalNew,
            $appealNote,
            $revType,
            $attachmentPath
        );
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $summary = 'Resubmitted against rejection (' . $returnStageLabel . '): ' . mb_substr($appealNote, 0, 140)
                . (!empty($attachmentPath) ? ' [Attachment attached]' : '');
            $this->logEdit($moduleTable, $sourceId, $summary);
        }

        return (bool)$ok;
    }

    /**
     * Check whether a record in any of the 12 supported tables is currently in a rejected state,
     * and return ['is_rejected' => bool, 'stage' => string, 'stage_label' => string, 'reason' => string].
     */
    public function getRejectionStatus(string $moduleTable, int $sourceId): array
    {
        $none = ['is_rejected' => false, 'stage' => '', 'stage_label' => '', 'reason' => ''];
        if (!$this->db || $sourceId <= 0) {
            return $none;
        }
        $allowed = [
            'fund_requisitions', 'project_other_costs', 'con_labor_requisitions',
            'vendor_payment_requests', 'contractor_bill_requests', 'purchase_requests',
            'purchase_orders', 'project_extra_assets', 'land_fund_requisitions',
            'cashout_requests', 'vendors', 'contractors',
        ];
        if (!in_array($moduleTable, $allowed, true)) {
            return $none;
        }

        $res = @$this->db->query("SELECT * FROM `{$moduleTable}` WHERE id = {$sourceId} LIMIT 1");
        $rec = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        if (!$rec) {
            return $none;
        }
        $none['record'] = $rec;
        $wrap = function (array $info) use ($rec): array {
            $info['record'] = $rec;
            return $info;
        };

        // Check approval_requests for Op Head / Accounts Head rejections too
        $arRow = null;
        $arTables = [$moduleTable];
        if ($moduleTable === 'land_fund_requisitions') $arTables[] = 'con_land_requisitions';
        if ($moduleTable === 'contractor_bill_requests') $arTables[] = 'con_contractor_bills';
        $inList = "'" . implode("','", $arTables) . "'";
        $arRes = @$this->db->query("SELECT status, op_head_status, op_head_note, accounts_head_status, accounts_head_note, approval_note FROM approval_requests WHERE source_table IN ({$inList}) AND source_id = {$sourceId} ORDER BY id DESC LIMIT 1");
        if ($arRes && $arRes->num_rows > 0) {
            $arRow = $arRes->fetch_assoc();
        }

        if ($arRow) {
            if (strtolower($arRow['accounts_head_status'] ?? '') === 'rejected') {
                return ['is_rejected' => true, 'stage' => 'accounts_head', 'stage_label' => 'Accounts Head', 'reason' => trim($arRow['accounts_head_note'] ?? '')];
            }
            if (strtolower($arRow['op_head_status'] ?? '') === 'rejected') {
                return ['is_rejected' => true, 'stage' => 'op_head', 'stage_label' => 'Operation Head', 'reason' => trim($arRow['op_head_note'] ?? '')];
            }
        }

        switch ($moduleTable) {
            case 'fund_requisitions':
                if (($rec['status'] ?? '') === 'rejected' || ($rec['dept_head_status'] ?? '') === 'rejected') {
                    if (($rec['dept_head_status'] ?? '') === 'rejected') {
                        return ['is_rejected' => true, 'stage' => 'dept_head', 'stage_label' => 'Department Head', 'reason' => trim($rec['dept_head_approval_note'] ?? '')];
                    }
                    if (empty($rec['qc_approved_by']) && !empty($rec['qc_approval_note'])) {
                        return ['is_rejected' => true, 'stage' => 'qc', 'stage_label' => 'QC & Internal Audit', 'reason' => trim($rec['qc_approval_note'] ?? '')];
                    }
                    return ['is_rejected' => true, 'stage' => 'mgmt', 'stage_label' => 'Management / Approver', 'reason' => trim($rec['approval_note'] ?? ($rec['qc_approval_note'] ?? ''))];
                }
                break;

            case 'project_other_costs':
                if (($rec['status'] ?? '') === 'rejected' || ($rec['dept_head_status'] ?? '') === 'rejected') {
                    if (($rec['dept_head_status'] ?? '') === 'rejected') {
                        return ['is_rejected' => true, 'stage' => 'dept_head', 'stage_label' => 'Department Head', 'reason' => trim($rec['dept_head_approval_note'] ?? '')];
                    }
                    if (strtolower($rec['op_head_status'] ?? '') === 'rejected') {
                        return ['is_rejected' => true, 'stage' => 'op_head', 'stage_label' => 'Operation Head', 'reason' => trim($rec['op_head_note'] ?? '')];
                    }
                    if (strtolower($rec['accounts_head_status'] ?? '') === 'rejected') {
                        return ['is_rejected' => true, 'stage' => 'accounts_head', 'stage_label' => 'Accounts Head', 'reason' => trim($rec['accounts_head_note'] ?? '')];
                    }
                    if (empty($rec['qc_audit_approved_id'])) {
                        return ['is_rejected' => true, 'stage' => 'qc', 'stage_label' => 'QC & Internal Audit', 'reason' => trim($rec['qc_audit_note'] ?? '')];
                    }
                    return ['is_rejected' => true, 'stage' => 'mgmt', 'stage_label' => 'Management', 'reason' => trim($rec['management_apporved_note'] ?? '')];
                }
                break;

            case 'con_labor_requisitions':
                if (($rec['status'] ?? '') === 'rejected') {
                    $st = empty($rec['pm_approved_by']) ? ['pm', 'Dept. Head / PM'] : (empty($rec['qc_approved_by']) ? ['qc', 'QC & Internal Audit'] : ['mgmt', 'Management']);
                    return ['is_rejected' => true, 'stage' => $st[0], 'stage_label' => $st[1], 'reason' => trim($rec['rejection_reason'] ?? '')];
                }
                break;

            case 'vendor_payment_requests':
                if (strtolower($rec['status'] ?? '') === 'rejected') {
                    $st = !empty($rec['qc_approved_by_id']) ? ['mgmt', 'Management'] : (!empty($rec['purchase_head_approved_by_id']) ? ['qc', 'QC & Internal Audit'] : ['purchase_head', 'Head of Purchase']);
                    $reason = trim($rec['management_note'] ?? ($rec['qc_note'] ?? ($rec['purchase_head_note'] ?? '')));
                    return ['is_rejected' => true, 'stage' => $st[0], 'stage_label' => $st[1], 'reason' => $reason];
                }
                break;

            case 'contractor_bill_requests':
                if (strtolower($rec['status'] ?? '') === 'rejected') {
                    $st = !empty($rec['qc_approved_by_id']) ? ['mgmt', 'Management'] : (!empty($rec['purchase_head_approved_by_id']) ? ['qc', 'QC & Internal Audit'] : (!empty($rec['verified_by']) ? ['purchase_head', 'Head of Purchase'] : ['pm', 'Dept. Head / PM']));
                    return ['is_rejected' => true, 'stage' => $st[0], 'stage_label' => $st[1], 'reason' => trim($rec['rejection_reason'] ?? '')];
                }
                break;

            case 'purchase_requests':
                if (in_array($rec['status'] ?? '', ['Rejected', 'PO Rejected'], true)) {
                    return ['is_rejected' => true, 'stage' => 'dept_head', 'stage_label' => 'Dept. Head / Approver', 'reason' => trim($rec['management_note'] ?? ($rec['po_rejection_note'] ?? ''))];
                }
                break;

            case 'purchase_orders':
                if (($rec['status'] ?? '') === 'Cancelled') {
                    $st = !empty($rec['op_head_approved_by_id']) ? ['bod', 'Board of Director'] : (!empty($rec['qc_approved_by_id']) ? ['op_head', 'Operation Head'] : (!empty($rec['purchase_head_approved_by_id']) ? ['qc', 'QC & Internal Audit'] : ['purchase_head', 'Head of Purchase']));
                    $reason = trim($rec['mgmt_note'] ?? ($rec['op_head_note'] ?? ($rec['qc_note'] ?? ($rec['purchase_head_note'] ?? ''))));
                    return ['is_rejected' => true, 'stage' => $st[0], 'stage_label' => $st[1], 'reason' => $reason];
                }
                break;

            case 'project_extra_assets':
                $stageCols = [
                    'dept_head_status'     => ['dept_head', 'Department Head', 'dept_head_note'],
                    'it_head_status'       => ['it_head', 'IT Head', 'it_head_note'],
                    'operations_status'    => ['operations', 'Admin & Operation', 'operations_note'],
                    'purchase_head_status' => ['purchase_head', 'Head of Purchase', 'purchase_head_note'],
                    'qc_audit_status'      => ['qc', 'QC & Internal Audit', 'qc_audit_note'],
                    'mgmt_status'          => ['mgmt', 'Management', 'mgmt_note'],
                ];
                foreach ($stageCols as $col => [$st, $lbl, $noteCol]) {
                    if (($rec[$col] ?? '') === 'rejected') {
                        return ['is_rejected' => true, 'stage' => $st, 'stage_label' => $lbl, 'reason' => trim($rec[$noteCol] ?? '')];
                    }
                }
                if (($rec['overall_status'] ?? '') === 'rejected') {
                    return ['is_rejected' => true, 'stage' => 'mgmt', 'stage_label' => 'Approver', 'reason' => trim($rec['mgmt_note'] ?? '')];
                }
                break;

            case 'land_fund_requisitions':
                if (($rec['mgmt_status'] ?? '') === 'Rejected' || ($rec['accounts_status'] ?? '') === 'Rejected') {
                    $lbl = ($rec['mgmt_status'] ?? '') === 'Rejected' ? 'Management' : 'Accounts';
                    return ['is_rejected' => true, 'stage' => strtolower($lbl), 'stage_label' => $lbl, 'reason' => trim($rec['mgmt_note'] ?? '')];
                }
                break;

            case 'cashout_requests':
                if (strtolower($rec['status'] ?? '') === 'rejected') {
                    $st = !empty($rec['qc_approved_by_id']) ? ['mgmt', 'Management'] : ['qc', 'QC & Internal Audit'];
                    return ['is_rejected' => true, 'stage' => $st[0], 'stage_label' => $st[1], 'reason' => trim($rec['rejection_reason'] ?? ($rec['approval_note'] ?? ''))];
                }
                break;

            case 'vendors':
            case 'contractors':
                if (($rec['status'] ?? '') === 'rejected') {
                    $stMap = [
                        'dept_head_status'     => ['dept_head', 'Department Head'],
                        'purchase_head_status' => ['purchase_head', 'Head of Purchase'],
                        'role_20_status'       => ['op_head', 'Operation Head'],
                        'role_38_status'       => ['bod', 'Board of Director'],
                        'management_status'    => ['mgmt', 'Management'],
                    ];
                    foreach ($stMap as $col => [$st, $lbl]) {
                        if (($rec[$col] ?? '') === 'rejected') {
                            return ['is_rejected' => true, 'stage' => $st, 'stage_label' => $lbl, 'reason' => trim($rec['rejection_reason'] ?? '')];
                        }
                    }
                    return ['is_rejected' => true, 'stage' => 'mgmt', 'stage_label' => 'Approver', 'reason' => trim($rec['rejection_reason'] ?? '')];
                }
                break;
        }

        return $none;
    }

    /**
     * Record a permanent edit audit entry (who edited, role, timestamp, change summary)
     */
    public function logEdit(string $moduleTable, int $sourceId, string $changeSummary = '', ?int $userId = null, ?string $userName = null, ?string $userRole = null): bool
    {
        if (!$this->db || $sourceId <= 0) {
            return false;
        }
        try {
            $uid   = $userId ?? (int)($_SESSION['user']['id'] ?? 0);
            $uname = trim((string)($userName ?? ($_SESSION['user']['full_name'] ?? 'User')));
            $urole = trim((string)($userRole ?? ($_SESSION['user']['role_name'] ?? '')));
            $sum   = trim($changeSummary) !== '' ? trim($changeSummary) : 'Details updated';

            $stmt = $this->db->prepare("
                INSERT INTO requisition_edit_logs (module_table, source_id, edited_by, edited_by_name, edited_by_role, change_summary)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('siisss', $moduleTable, $sourceId, $uid, $uname, $urole, $sum);
            $ok = $stmt->execute();
            $stmt->close();
            return (bool)$ok;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the most recent edit log entry for a requisition
     */
    public function getLastEdit(string $moduleTable, int $sourceId): ?array
    {
        if (!$this->db || $sourceId <= 0) {
            return null;
        }
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM requisition_edit_logs
                WHERE module_table = ? AND source_id = ?
                ORDER BY id DESC LIMIT 1
            ");
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param('si', $moduleTable, $sourceId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $row ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get the most recent edit log entries (up to $limit) for a requisition,
     * useful for showing exact field-by-field updates + timestamps to the approver on resubmission.
     */
    public function getRecentEdits(string $moduleTable, int $sourceId, int $limit = 3): array
    {
        if (!$this->db || $sourceId <= 0) {
            return [];
        }
        try {
            $lim = max(1, min(10, $limit));
            $stmt = $this->db->prepare("
                SELECT * FROM requisition_edit_logs
                WHERE module_table = ? AND source_id = ?
                ORDER BY id DESC LIMIT {$lim}
            ");
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('si', $moduleTable, $sourceId);
            $stmt->execute();
            $res = $stmt->get_result();
            $rows = [];
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
            $stmt->close();
            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Batch-fetch the latest edit log entry per source_id for a module table (single query, zero N+1)
     */
    public function getLastEditMap(string $moduleTable): array
    {
        if (!$this->db) {
            return [];
        }
        try {
            $map = [];
            $stmt = $this->db->prepare("
                SELECT l.*
                FROM requisition_edit_logs l
                INNER JOIN (
                    SELECT source_id, MAX(id) AS max_id
                    FROM requisition_edit_logs
                    WHERE module_table = ?
                    GROUP BY source_id
                ) m ON m.max_id = l.id
            ");
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('s', $moduleTable);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $map[(int)$row['source_id']] = $row;
            }
            $stmt->close();
            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Build a clean human-readable summary of changed fields
     */
    public static function buildChangeSummary(array $changes): string
    {
        $parts = [];
        foreach ($changes as $label => $pair) {
            if (!is_array($pair) || count($pair) < 2) {
                continue;
            }
            [$old, $new] = $pair;
            $oldStr = trim((string)$old);
            $newStr = trim((string)$new);
            if ($oldStr === $newStr) {
                continue;
            }
            if (is_numeric($old) && is_numeric($new)) {
                if ((float)$old == (float)$new) {
                    continue;
                }
                $parts[] = "{$label}: " . number_format((float)$old, 2) . " → " . number_format((float)$new, 2);
            } else {
                $parts[] = "{$label} updated";
            }
        }
        return !empty($parts) ? implode(' | ', $parts) : 'Details updated';
    }
}
