<?php

class OfficeProcurementOrder
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureSchema();
    }

    /**
     * Idempotently ensure all OPO, CST, Revision tables & PR workflow columns exist.
     */
    public function ensureSchema()
    {
        static $checked = false;
        if ($checked || !$this->db) {
            return;
        }
        $checked = true;

        // 1. Quotations table for Office Procurement CST
        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `office_procurement_quotations` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `extra_purchase_id` INT(11) NOT NULL,
                `vendor_id` INT(11) NOT NULL,
                `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
                `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `total_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `validity_date` DATE NULL DEFAULT NULL,
                `attachment` VARCHAR(255) NULL DEFAULT NULL,
                `drive_link` TEXT NULL DEFAULT NULL,
                `notes` TEXT NULL DEFAULT NULL,
                `is_selected` TINYINT(1) NOT NULL DEFAULT 0,
                `created_by` INT(11) NOT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_opq_pr` (`extra_purchase_id`),
                KEY `idx_opq_vendor` (`vendor_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 2. Office Procurement Orders (OPO) master table
        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `office_procurement_orders` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `opo_number` VARCHAR(40) NOT NULL,
                `extra_purchase_id` INT(11) NOT NULL,
                `selected_quotation_id` INT(11) NULL DEFAULT NULL,
                `vendor_id` INT(11) NOT NULL,
                `item_name` VARCHAR(255) NOT NULL,
                `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
                `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
                `order_date` DATE NOT NULL,
                `status` VARCHAR(40) NOT NULL DEFAULT 'pending_purchase_head',
                `revision_count` INT(11) NOT NULL DEFAULT 0,
                `purchase_head_status` ENUM('pending','approved','revision_requested','rejected') DEFAULT 'pending',
                `purchase_head_by` INT(11) NULL DEFAULT NULL,
                `purchase_head_at` DATETIME NULL DEFAULT NULL,
                `purchase_head_note` TEXT NULL DEFAULT NULL,
                `qc_status` ENUM('pending','approved','revision_requested','rejected') DEFAULT 'pending',
                `qc_by` INT(11) NULL DEFAULT NULL,
                `qc_at` DATETIME NULL DEFAULT NULL,
                `qc_note` TEXT NULL DEFAULT NULL,
                `op_head_status` ENUM('pending','approved','revision_requested','rejected') DEFAULT 'pending',
                `op_head_by` INT(11) NULL DEFAULT NULL,
                `op_head_at` DATETIME NULL DEFAULT NULL,
                `op_head_note` TEXT NULL DEFAULT NULL,
                `created_by` INT(11) NOT NULL,
                `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_opo_number` (`opo_number`),
                KEY `idx_opo_pr` (`extra_purchase_id`),
                KEY `idx_opo_vendor` (`vendor_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 3. Revision & Full Activity Audit Log table
        @$this->db->query("
            CREATE TABLE IF NOT EXISTS `office_procurement_opo_revisions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `opo_id` INT(11) NOT NULL DEFAULT 0,
                `extra_purchase_id` INT(11) NOT NULL,
                `action_type` VARCHAR(40) NOT NULL,
                `stage_name` VARCHAR(80) NOT NULL,
                `old_unit_price` DECIMAL(15,2) NULL DEFAULT NULL,
                `suggested_unit_price` DECIMAL(15,2) NULL DEFAULT NULL,
                `new_unit_price` DECIMAL(15,2) NULL DEFAULT NULL,
                `note` TEXT NULL DEFAULT NULL,
                `acted_by` INT(11) NOT NULL,
                `acted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_rev_opo` (`opo_id`),
                KEY `idx_rev_pr` (`extra_purchase_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        // 4. Extend project_extra_assets with workflow & handover columns
        $peaCols = [
            'assigned_cst_user_id'         => "INT(11) NULL DEFAULT NULL",
            'assigned_cst_by'              => "INT(11) NULL DEFAULT NULL",
            'assigned_cst_at'              => "DATETIME NULL DEFAULT NULL",
            'opo_id'                       => "INT(11) NULL DEFAULT NULL",
            'workflow_stage'               => "VARCHAR(50) NULL DEFAULT NULL",
            'assignee_payment_received_by' => "INT(11) NULL DEFAULT NULL",
            'assignee_payment_received_at' => "DATETIME NULL DEFAULT NULL",
            'asset_received_by'            => "INT(11) NULL DEFAULT NULL",
            'asset_received_at'            => "DATETIME NULL DEFAULT NULL",
            'asset_received_note'          => "TEXT NULL DEFAULT NULL",
        ];

        $existing = [];
        $res = @$this->db->query("SHOW COLUMNS FROM `project_extra_assets`");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $existing[$r['Field']] = true;
            }
        }
        foreach ($peaCols as $col => $def) {
            if (!isset($existing[$col])) {
                @$this->db->query("ALTER TABLE `project_extra_assets` ADD COLUMN `{$col}` {$def}");
            }
        }
    }

    /**
     * Generate sequential OPO number: OPO-YYYY-NNNN
     */
    public function generateOpoNumber()
    {
        $year = date('Y');
        $prefix = "OPO-{$year}-";
        $stmt = $this->db->prepare("SELECT opo_number FROM office_procurement_orders WHERE opo_number LIKE ? ORDER BY id DESC LIMIT 1");
        $like = $prefix . '%';
        $nextNum = 1;
        if ($stmt) {
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            if ($row && !empty($row['opo_number'])) {
                $nextNum = (int)substr($row['opo_number'], strlen($prefix)) + 1;
            }
            $stmt->close();
        }
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Log any action in the Office Procurement lifecycle (with exact user & datetime)
     */
    public function logActivity($extraPurchaseId, $opoId, $actionType, $stageName, $actedBy, $note = '', $oldPrice = null, $suggestedPrice = null, $newPrice = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO office_procurement_opo_revisions
            (opo_id, extra_purchase_id, action_type, stage_name, old_unit_price, suggested_unit_price, new_unit_price, note, acted_by, acted_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) {
            return false;
        }
        $opoIdInt = (int)$opoId;
        $prIdInt  = (int)$extraPurchaseId;
        $userInt  = (int)$actedBy;
        $stmt->bind_param(
            'iissdddsi',
            $opoIdInt,
            $prIdInt,
            $actionType,
            $stageName,
            $oldPrice,
            $suggestedPrice,
            $newPrice,
            $note,
            $userInt
        );
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * Get full chronological activity / revision log for a PR or OPO
     */
    public function getActivityLogs($extraPurchaseId)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, u.full_name AS acted_by_name, r.name AS acted_by_role
            FROM office_procurement_opo_revisions l
            LEFT JOIN users u ON u.id = l.acted_by
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE l.extra_purchase_id = ?
            ORDER BY l.acted_at DESC, l.id DESC
        ");
        if (!$stmt) return [];
        $stmt->bind_param('i', $extraPurchaseId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Fetch employees eligible for CST assignment:
     * Admin & Operation Department, Purchase Department, IT Department.
     */
    public function getAssignableCstEmployees()
    {
        $sql = "
            SELECT DISTINCT u.id, u.full_name, u.email,
                   COALESCE(NULLIF(TRIM(e.employee_id), ''), CONCAT('UID-', u.id)) AS emp_code,
                   COALESCE(d.name, r.name, 'General') AS dept_name,
                   r.name AS role_name
            FROM users u
            LEFT JOIN employees e ON (e.user_id = u.id AND e.deleted_at IS NULL)
            LEFT JOIN departments d ON d.id = e.department_id
            LEFT JOIN roles r ON r.id = u.role_id
            WHERE (e.id IS NULL OR e.employee_status = 'active')
              AND (
                  d.id IN (6, 20, 22)
                  OR LOWER(COALESCE(d.name, '')) LIKE '%operations & admin%'
                  OR LOWER(COALESCE(d.name, '')) LIKE '%admin & operation%'
                  OR LOWER(COALESCE(d.name, '')) LIKE '%purchase%'
                  OR LOWER(COALESCE(d.name, '')) LIKE '%procurement%'
                  OR LOWER(COALESCE(d.name, '')) LIKE 'it %'
                  OR LOWER(COALESCE(d.name, '')) = 'it'
                  OR u.role_id IN (17, 20, 23, 31, 32)
              )
            ORDER BY dept_name ASC, u.full_name ASC
        ";
        $res = @$this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            return $res->fetch_all(MYSQLI_ASSOC);
        }
        $resFallback = @$this->db->query("SELECT u.id, u.full_name, u.email, CONCAT('UID-', u.id) AS emp_code, 'Staff' AS dept_name, '' AS role_name FROM users u ORDER BY u.full_name ASC");
        return $resFallback ? $resFallback->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Role 32 (Admin & Operation) approves PR and assigns employee for CST
     */
    public function assignCstEmployee($prId, $assigneeUserId, $assignedBy, $note = '')
    {
        $prId = (int)$prId;
        $assigneeUserId = (int)$assigneeUserId;
        $assignedBy = (int)$assignedBy;
        if ($prId <= 0 || $assigneeUserId <= 0) return false;

        $stmt = $this->db->prepare("
            UPDATE project_extra_assets
            SET operations_status = 'approved',
                operations_by = COALESCE(operations_by, ?),
                operations_at = COALESCE(operations_at, NOW()),
                operations_note = COALESCE(NULLIF(?, ''), operations_note),
                assigned_cst_user_id = ?,
                assigned_cst_by = ?,
                assigned_cst_at = NOW(),
                workflow_stage = 'cst_pending',
                overall_status = 'in_review'
            WHERE id = ?
        ");
        if (!$stmt) return false;
        $stmt->bind_param('isiii', $assignedBy, $note, $assigneeUserId, $assignedBy, $prId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $uRes = $this->db->query("SELECT full_name FROM users WHERE id = {$assigneeUserId} LIMIT 1");
            $assigneeName = ($uRes && $uRow = $uRes->fetch_assoc()) ? $uRow['full_name'] : "User #{$assigneeUserId}";
            $logNote = "Approved by Admin & Operation and assigned to {$assigneeName} for Multi-Vendor CST." . ($note ? " Note: {$note}" : '');
            $this->logActivity($prId, 0, 'assigned', 'Admin & Operation', $assignedBy, $logNote);

            // Send in-app notification to the assigned CST employee
            try {
                if (file_exists(__DIR__ . '/HeadNotification.php')) {
                    require_once __DIR__ . '/HeadNotification.php';
                    $notifModel = new \HeadNotification($this->db);
                    $baseUrl = $GLOBALS['base_url'] ?? '/TPHL-ERP';
                    $notifModel->create([
                        'user_id' => $assigneeUserId,
                        'type'    => 'Office CST Assignment',
                        'title'   => "Assigned for Office CST — PR #{$prId}",
                        'message' => "Admin & Operation assigned Office Procurement Request #{$prId} to you for Multi-Vendor CST & OPO generation." . ($note ? " Note: {$note}" : ''),
                        'url'     => "{$baseUrl}/office-procurement/cst/{$prId}",
                    ]);
                }
            } catch (\Throwable $e) {
                error_log('[OfficeProcurementOrder::assignCstEmployee] Notification error: ' . $e->getMessage());
            }
        }

        return $ok;
    }

    /**
     * Get all active vendors for CST selection
     */
    public function getActiveVendors()
    {
        $res = @$this->db->query("
            SELECT id,
                   COALESCE(NULLIF(TRIM(company_name), ''), name) AS display_name,
                   company_name, name, phone, email
            FROM vendors
            WHERE LOWER(status) = 'active'
            ORDER BY display_name ASC
        ");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get all CST quotations for a PR
     */
    public function getQuotationsByPr($prId)
    {
        $stmt = $this->db->prepare("
            SELECT q.*,
                   COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name,
                   v.company_name AS vendor_company_name,
                   v.phone AS vendor_phone,
                   v.email AS vendor_email,
                   u.full_name AS created_by_name
            FROM office_procurement_quotations q
            LEFT JOIN vendors v ON v.id = q.vendor_id
            LEFT JOIN users u ON u.id = q.created_by
            WHERE q.extra_purchase_id = ?
            ORDER BY q.is_selected DESC, q.unit_price ASC, q.id ASC
        ");
        if (!$stmt) return [];
        $stmt->bind_param('i', $prId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Save Multi-Vendor CST and Generate (or Update on Revision) OPO
     */
    public function saveCstAndGenerateOpo($prId, $prRow, $quotations, $selectedIndex, $userId)
    {
        $prId = (int)$prId;
        $userId = (int)$userId;
        if (empty($quotations) || !isset($quotations[$selectedIndex])) {
            return ['success' => false, 'error' => 'Please add at least one vendor quotation and select a winning vendor.'];
        }

        $this->db->begin_transaction();
        try {
            // Preserve existing attachments if user didn't re-upload
            $existingQuots = $this->getQuotationsByPr($prId);
            $existingAttachByVendor = [];
            foreach ($existingQuots as $eq) {
                if (!empty($eq['attachment'])) {
                    $existingAttachByVendor[(int)$eq['vendor_id']] = $eq['attachment'];
                }
            }

            // Replace quotations for this PR
            $delStmt = $this->db->prepare("DELETE FROM office_procurement_quotations WHERE extra_purchase_id = ?");
            $delStmt->bind_param('i', $prId);
            $delStmt->execute();
            $delStmt->close();

            $insQ = $this->db->prepare("
                INSERT INTO office_procurement_quotations
                (extra_purchase_id, vendor_id, quantity, unit_price, total_price, validity_date, attachment, drive_link, notes, is_selected, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$insQ) {
                throw new \RuntimeException('Failed to prepare quotation insert: ' . $this->db->error);
            }

            $selectedQuotationId = 0;
            $selectedVendorId    = 0;
            $selectedQty         = 0.0;
            $selectedUnitPrice   = 0.0;
            $selectedTotalPrice  = 0.0;

            foreach ($quotations as $idx => $q) {
                $vId     = (int)$q['vendor_id'];
                $qty     = max(0.01, (float)$q['quantity']);
                $uPrice  = max(0.0, (float)$q['unit_price']);
                $tPrice  = round($qty * $uPrice, 2);
                $valDate = !empty($q['validity_date']) ? $q['validity_date'] : null;
                $attach  = !empty($q['attachment']) ? $q['attachment'] : ($existingAttachByVendor[$vId] ?? null);
                $dLink   = !empty($q['drive_link']) ? trim($q['drive_link']) : null;
                $notes   = trim($q['notes'] ?? '');
                $isSel   = ((int)$idx === (int)$selectedIndex) ? 1 : 0;

                $insQ->bind_param(
                    'iidddssssii',
                    $prId,
                    $vId,
                    $qty,
                    $uPrice,
                    $tPrice,
                    $valDate,
                    $attach,
                    $dLink,
                    $notes,
                    $isSel,
                    $userId
                );
                if (!$insQ->execute()) {
                    throw new \RuntimeException('Failed to save quotation: ' . $insQ->error);
                }

                if ($isSel === 1) {
                    $selectedQuotationId = (int)$this->db->insert_id;
                    $selectedVendorId    = $vId;
                    $selectedQty         = $qty;
                    $selectedUnitPrice   = $uPrice;
                    $selectedTotalPrice  = $tPrice;
                }
            }
            $insQ->close();

            if ($selectedVendorId <= 0 || $selectedTotalPrice <= 0) {
                throw new \RuntimeException('Selected vendor must have a valid rate greater than 0.');
            }

            // Look up selected vendor display name (company_name fallback name)
            $vendorDisplayName = null;
            $vStmt = $this->db->prepare("SELECT COALESCE(NULLIF(TRIM(company_name), ''), name) AS display_name FROM vendors WHERE id = ?");
            if ($vStmt) {
                $vStmt->bind_param('i', $selectedVendorId);
                $vStmt->execute();
                $vRow = $vStmt->get_result()->fetch_assoc();
                if ($vRow) $vendorDisplayName = $vRow['display_name'];
                $vStmt->close();
            }

            // Check if an OPO already exists for this PR (revision resubmission)
            $existingOpo = $this->findOrderByPr($prId);
            $itemName = !empty($prRow['item_name']) ? $prRow['item_name'] : 'Office Procurement Item';
            $oldUnitPrice = $existingOpo ? (float)$existingOpo['unit_price'] : (float)($prRow['unit_price'] ?? 0);

            if ($existingOpo) {
                $opoId = (int)$existingOpo['id'];
                $opoNumber = $existingOpo['opo_number'];
                $newRevCount = (int)$existingOpo['revision_count'] + 1;

                // Reset OPO approval chain back to Purchase Head for fresh approval after CST revision
                $updOpo = $this->db->prepare("
                    UPDATE office_procurement_orders
                    SET selected_quotation_id = ?,
                        vendor_id = ?,
                        quantity = ?,
                        unit_price = ?,
                        total_amount = ?,
                        status = 'pending_purchase_head',
                        revision_count = ?,
                        purchase_head_status = 'pending',
                        purchase_head_by = NULL,
                        purchase_head_at = NULL,
                        purchase_head_note = NULL,
                        qc_status = 'pending',
                        qc_by = NULL,
                        qc_at = NULL,
                        qc_note = NULL,
                        op_head_status = 'pending',
                        op_head_by = NULL,
                        op_head_at = NULL,
                        op_head_note = NULL
                    WHERE id = ?
                ");
                $updOpo->bind_param('iidddii', $selectedQuotationId, $selectedVendorId, $selectedQty, $selectedUnitPrice, $selectedTotalPrice, $newRevCount, $opoId);
                if (!$updOpo->execute()) {
                    throw new \RuntimeException('Failed to update OPO: ' . $updOpo->error);
                }
                $updOpo->close();

                $actionType = 'cst_resubmitted';
                $logNote = "CST updated (Revision #{$newRevCount}) with {$vendorDisplayName} @ ৳" . number_format($selectedUnitPrice, 2) . " (Total: ৳" . number_format($selectedTotalPrice, 2) . ") and resubmitted for OPO approval.";
            } else {
                $opoNumber = $this->generateOpoNumber();
                $orderDate = date('Y-m-d');
                $insOpo = $this->db->prepare("
                    INSERT INTO office_procurement_orders
                    (opo_number, extra_purchase_id, selected_quotation_id, vendor_id, item_name, quantity, unit_price, total_amount, order_date, status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending_purchase_head', ?)
                ");
                $insOpo->bind_param('siiisdddsi', $opoNumber, $prId, $selectedQuotationId, $selectedVendorId, $itemName, $selectedQty, $selectedUnitPrice, $selectedTotalPrice, $orderDate, $userId);
                if (!$insOpo->execute()) {
                    throw new \RuntimeException('Failed to create OPO: ' . $insOpo->error);
                }
                $opoId = (int)$this->db->insert_id;
                $insOpo->close();

                $actionType = 'cst_submitted';
                $logNote = "Multi-Vendor CST submitted (" . count($quotations) . " vendor(s)). Generated {$opoNumber} with {$vendorDisplayName} @ ৳" . number_format($selectedUnitPrice, 2) . " (Total: ৳" . number_format($selectedTotalPrice, 2) . ").";
            }

            // Sync winning vendor, rate, total & OPO ID back to project_extra_assets
            $intQty = (int)round($selectedQty);
            $updPr = $this->db->prepare("
                UPDATE project_extra_assets
                SET vendor_id = ?,
                    vendor_name = ?,
                    quantity = ?,
                    unit_price = ?,
                    total_price = ?,
                    opo_id = ?,
                    purchase_head_status = 'pending',
                    qc_audit_status = 'pending',
                    mgmt_status = 'pending',
                    workflow_stage = 'opo_purchase_head',
                    overall_status = 'in_review'
                WHERE id = ?
            ");
            $updPr->bind_param('isiddii', $selectedVendorId, $vendorDisplayName, $intQty, $selectedUnitPrice, $selectedTotalPrice, $opoId, $prId);
            $updPr->execute();
            $updPr->close();

            $this->logActivity($prId, $opoId, $actionType, 'CST & OPO Generation', $userId, $logNote, $oldUnitPrice, null, $selectedUnitPrice);

            $this->db->commit();
            return ['success' => true, 'opo_id' => $opoId, 'opo_number' => $opoNumber];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Find single OPO with full joins (PR, Vendor, Approvers, Approval Request)
     */
    public function findOrder($opoId)
    {
        $stmt = $this->db->prepare("
            SELECT opo.*,
                   pea.project_id, pea.department_id, pea.office_branch_id, pea.asset_location,
                   pea.assigned_to, pea.item_category_id, pea.request_type, pea.account_id,
                   pea.justification, pea.attachment AS pr_attachment,
                   pea.requested_by, pea.created_at AS pr_created_at,
                   pea.dept_head_status, pea.dept_head_by, pea.dept_head_at, pea.dept_head_note,
                   pea.operations_status, pea.operations_by, pea.operations_at, pea.operations_note,
                   pea.assigned_cst_user_id, pea.assigned_cst_by, pea.assigned_cst_at,
                   pea.mgmt_status, pea.mgmt_by, pea.mgmt_at, pea.mgmt_note,
                   pea.payment_status, pea.paid_by, pea.paid_at, pea.payment_method, pea.paid_amount, pea.payment_attachment,
                   pea.assignee_payment_received_by, pea.assignee_payment_received_at,
                   pea.purchase_document, pea.purchase_note, pea.purchase_date, pea.return_amount, pea.return_note,
                   pea.purchase_confirmed_by, pea.purchase_confirmed_at,
                   pea.asset_received_by, pea.asset_received_at, pea.asset_received_note,
                   pea.overall_status AS pr_overall_status, pea.workflow_stage,
                   COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name,
                   v.company_name AS vendor_company_name, v.name AS vendor_contact_name,
                   v.phone AS vendor_phone, v.email AS vendor_email, v.address AS vendor_address,
                   p.name AS project_name,
                   ob.name AS branch_name,
                   cat.name AS category_name,
                   d.name AS department_name,
                   u_req.full_name AS requested_by_name,
                   u_dh.full_name AS dept_head_by_name,
                   u_op32.full_name AS operations_by_name,
                   u_cst.full_name AS assigned_cst_name,
                   u_asgn_by.full_name AS assigned_cst_by_name,
                   u_ph.full_name AS purchase_head_by_name,
                   u_qc.full_name AS qc_by_name,
                   u_oph.full_name AS op_head_by_name,
                   u_mgmt.full_name AS mgmt_by_name,
                   u_paid.full_name AS paid_by_name,
                   u_prcv.full_name AS payment_received_by_name,
                   u_pur.full_name AS purchased_by_name,
                   u_arcv.full_name AS asset_received_by_name,
                   ar.id AS approval_request_id,
                   ar.accounts_head_status, ar.accounts_head_by, ar.accounts_head_at, ar.accounts_head_note,
                   u_ah.full_name AS accounts_head_by_name
            FROM office_procurement_orders opo
            JOIN project_extra_assets pea ON pea.id = opo.extra_purchase_id
            LEFT JOIN vendors v ON v.id = opo.vendor_id
            LEFT JOIN projects p ON p.id = pea.project_id
            LEFT JOIN office_branches ob ON ob.id = pea.office_branch_id
            LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
            LEFT JOIN departments d ON d.id = pea.department_id
            LEFT JOIN users u_req ON u_req.id = pea.requested_by
            LEFT JOIN users u_dh ON u_dh.id = pea.dept_head_by
            LEFT JOIN users u_op32 ON u_op32.id = pea.operations_by
            LEFT JOIN users u_cst ON u_cst.id = pea.assigned_cst_user_id
            LEFT JOIN users u_asgn_by ON u_asgn_by.id = pea.assigned_cst_by
            LEFT JOIN users u_ph ON u_ph.id = opo.purchase_head_by
            LEFT JOIN users u_qc ON u_qc.id = opo.qc_by
            LEFT JOIN users u_oph ON u_oph.id = opo.op_head_by
            LEFT JOIN users u_mgmt ON u_mgmt.id = pea.mgmt_by
            LEFT JOIN users u_paid ON u_paid.id = pea.paid_by
            LEFT JOIN users u_prcv ON u_prcv.id = pea.assignee_payment_received_by
            LEFT JOIN users u_pur ON u_pur.id = pea.purchase_confirmed_by
            LEFT JOIN users u_arcv ON u_arcv.id = pea.asset_received_by
            LEFT JOIN approval_requests ar ON (ar.source_table IN ('project_extra_assets', 'purchase_requests') AND ar.source_id = pea.id)
            LEFT JOIN users u_ah ON u_ah.id = ar.accounts_head_by
            WHERE opo.id = ?
            LIMIT 1
        ");
        if (!$stmt) return null;
        $stmt->bind_param('i', $opoId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    public function findOrderByPr($prId)
    {
        $stmt = $this->db->prepare("SELECT * FROM office_procurement_orders WHERE extra_purchase_id = ? ORDER BY id DESC LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param('i', $prId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row;
    }

    /**
     * List all OPOs with filters & role-based tab support
     */
    public function getAllOrders($filters = [])
    {
        $sql = "
            SELECT opo.*,
                   pea.requested_by, pea.assigned_cst_user_id, pea.payment_status, pea.paid_amount,
                   pea.mgmt_status, pea.overall_status AS pr_overall_status, pea.workflow_stage,
                   pea.assignee_payment_received_at, pea.purchase_confirmed_at, pea.asset_received_at,
                   COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name,
                   COALESCE(ob.name, pea.asset_location, 'Head Office') AS location_name,
                   cat.name AS category_name,
                   u_req.full_name AS requested_by_name,
                   u_cst.full_name AS assigned_cst_name,
                   u_ph.full_name AS purchase_head_by_name,
                   u_qc.full_name AS qc_by_name,
                   u_oph.full_name AS op_head_by_name,
                   ar.id AS approval_request_id,
                   ar.accounts_head_status
            FROM office_procurement_orders opo
            JOIN project_extra_assets pea ON pea.id = opo.extra_purchase_id
            LEFT JOIN vendors v ON v.id = opo.vendor_id
            LEFT JOIN office_branches ob ON ob.id = pea.office_branch_id
            LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
            LEFT JOIN users u_req ON u_req.id = pea.requested_by
            LEFT JOIN users u_cst ON u_cst.id = pea.assigned_cst_user_id
            LEFT JOIN users u_ph ON u_ph.id = opo.purchase_head_by
            LEFT JOIN users u_qc ON u_qc.id = opo.qc_by
            LEFT JOIN users u_oph ON u_oph.id = opo.op_head_by
            LEFT JOIN approval_requests ar ON (ar.source_table IN ('project_extra_assets', 'purchase_requests') AND ar.source_id = pea.id)
            WHERE 1=1
        ";

        $params = [];
        $types  = '';

        if (!empty($filters['status'])) {
            $sql .= " AND opo.status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }

        if (!empty($filters['vendor_id'])) {
            $sql .= " AND opo.vendor_id = ?";
            $params[] = (int)$filters['vendor_id'];
            $types .= 'i';
        }

        if (!empty($filters['search'])) {
            $s = '%' . trim($filters['search']) . '%';
            $sql .= " AND (opo.opo_number LIKE ? OR opo.item_name LIKE ? OR v.company_name LIKE ? OR v.name LIKE ?)";
            $params = array_merge($params, [$s, $s, $s, $s]);
            $types .= 'ssss';
        }

        $tab    = $filters['tab'] ?? 'all';
        $userId = (int)($filters['user_id'] ?? 0);
        $roleId = (int)($filters['role_id'] ?? 0);

        if ($tab === 'pending_my_action') {
            $conds = [];
            if (in_array($roleId, [17, 31, 1, 3], true)) {
                $conds[] = "opo.status = 'pending_purchase_head'";
            }
            if (in_array($roleId, [24, 1, 3], true)) {
                $conds[] = "opo.status = 'pending_qc'";
            }
            if (in_array($roleId, [20, 1, 3], true)) {
                $conds[] = "opo.status = 'pending_op_head'";
            }
            if (in_array($roleId, [30, 1, 3], true)) {
                $conds[] = "(opo.status = 'approved' AND (ar.accounts_head_status IS NULL OR ar.accounts_head_status != 'approved') AND pea.payment_status = 'unpaid')";
            }
            if (in_array($roleId, [1, 3, 38], true)) {
                $conds[] = "(opo.status = 'approved' AND opo.total_amount > 10000.01 AND ar.accounts_head_status = 'approved' AND pea.mgmt_status = 'pending')";
            }
            if (in_array($roleId, [6, 30, 1, 3], true)) {
                $conds[] = "(opo.status = 'approved' AND ar.accounts_head_status = 'approved' AND (opo.total_amount <= 10000.01 OR pea.mgmt_status = 'approved') AND pea.payment_status = 'unpaid')";
            }
            // CST Assignee actions: Revision needed, Receive payment, Mark purchased
            $conds[] = "(pea.assigned_cst_user_id = {$userId} AND opo.status IN ('revision_requested', 'paid', 'process_on_purchase'))";
            // Requester action: Receive asset
            $conds[] = "(pea.requested_by = {$userId} AND opo.status = 'purchased')";

            $sql .= " AND (" . implode(' OR ', $conds) . ")";
        } elseif ($tab === 'revision_requested') {
            $sql .= " AND opo.status = 'revision_requested'";
        } elseif ($tab === 'my_orders') {
            $sql .= " AND (pea.requested_by = {$userId} OR pea.assigned_cst_user_id = {$userId} OR opo.created_by = {$userId})";
        }

        $sql .= " ORDER BY opo.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    /**
     * Handle OPO Approval / Send for Rate Review / Rejection
     * Stages: 'purchase_head' (Role 17/31), 'qc' (Role 24), 'op_head' (Role 20)
     */
    public function handleOpoAction($opoId, $stage, $action, $userId, $note = '', $suggestedUnitPrice = null)
    {
        $opo = $this->findOrder($opoId);
        if (!$opo) {
            return ['success' => false, 'error' => 'OPO not found.'];
        }

        $prId = (int)$opo['extra_purchase_id'];
        $oldPrice = (float)$opo['unit_price'];
        $stageLabels = [
            'purchase_head' => 'Purchase Department Head',
            'qc'            => 'QC & Internal Audit Head',
            'op_head'       => 'Admin & Operation Head',
        ];
        $stageLabel = $stageLabels[$stage] ?? ucfirst($stage);

        $this->db->begin_transaction();
        try {
            if ($action === 'revision_requested') {
                $colPrefix = $stage; // purchase_head | qc | op_head
                $upd = $this->db->prepare("
                    UPDATE office_procurement_orders
                    SET `{$colPrefix}_status` = 'revision_requested',
                        `{$colPrefix}_by` = ?,
                        `{$colPrefix}_at` = NOW(),
                        `{$colPrefix}_note` = ?,
                        status = 'revision_requested'
                    WHERE id = ?
                ");
                $upd->bind_param('isi', $userId, $note, $opoId);
                $upd->execute();
                $upd->close();

                $updPr = $this->db->prepare("UPDATE project_extra_assets SET workflow_stage = 'cst_revision', overall_status = 'in_review' WHERE id = ?");
                $updPr->bind_param('i', $prId);
                $updPr->execute();
                $updPr->close();

                $revNote = ($note ?: 'Rate review requested.') . ($suggestedUnitPrice !== null && $suggestedUnitPrice > 0 ? " Suggested Rate: ৳" . number_format((float)$suggestedUnitPrice, 2) : '');
                $this->logActivity($prId, $opoId, 'review_requested', $stageLabel, $userId, $revNote, $oldPrice, $suggestedUnitPrice, null);

                // Notify CST Assignee about rate revision request
                if (!empty($opo['assigned_cst_user_id'])) {
                    try {
                        if (file_exists(__DIR__ . '/HeadNotification.php')) {
                            require_once __DIR__ . '/HeadNotification.php';
                            $notifModel = new \HeadNotification($this->db);
                            $baseUrl = $GLOBALS['base_url'] ?? '/TPHL-ERP';
                            $notifModel->create([
                                'user_id' => (int)$opo['assigned_cst_user_id'],
                                'type'    => 'OPO Rate Revision',
                                'title'   => "Rate Review Requested — {$opo['opo_number']}",
                                'message' => "{$stageLabel} sent {$opo['opo_number']} (PR #{$prId}) back for CST rate revision. {$revNote}",
                                'url'     => "{$baseUrl}/office-procurement/cst/{$prId}",
                            ]);
                        }
                    } catch (\Throwable $e) {
                        error_log('[OfficeProcurementOrder::handleOpoAction] Revision notification error: ' . $e->getMessage());
                    }
                }

                $this->db->commit();
                return ['success' => true, 'message' => "OPO sent back to CST Assignee for rate review & revision."];
            }

            if ($action === 'rejected') {
                $colPrefix = $stage;
                $upd = $this->db->prepare("
                    UPDATE office_procurement_orders
                    SET `{$colPrefix}_status` = 'rejected',
                        `{$colPrefix}_by` = ?,
                        `{$colPrefix}_at` = NOW(),
                        `{$colPrefix}_note` = ?,
                        status = 'rejected'
                    WHERE id = ?
                ");
                $upd->bind_param('isi', $userId, $note, $opoId);
                $upd->execute();
                $upd->close();

                $prStatusCol = ($stage === 'purchase_head') ? 'purchase_head_status' : (($stage === 'qc') ? 'qc_audit_status' : 'mgmt_status');
                $prByCol     = ($stage === 'purchase_head') ? 'purchase_head_by' : (($stage === 'qc') ? 'qc_audit_by' : 'mgmt_by');
                $prAtCol     = ($stage === 'purchase_head') ? 'purchase_head_at' : (($stage === 'qc') ? 'qc_audit_at' : 'mgmt_at');
                $prNoteCol   = ($stage === 'purchase_head') ? 'purchase_head_note' : (($stage === 'qc') ? 'qc_audit_note' : 'mgmt_note');

                $updPr = $this->db->prepare("
                    UPDATE project_extra_assets
                    SET `{$prStatusCol}` = 'rejected', `{$prByCol}` = ?, `{$prAtCol}` = NOW(), `{$prNoteCol}` = ?,
                        overall_status = 'rejected', workflow_stage = 'rejected'
                    WHERE id = ?
                ");
                $updPr->bind_param('isi', $userId, $note, $prId);
                $updPr->execute();
                $updPr->close();

                $escNote = $this->db->real_escape_string($note ?: 'Rejected at OPO stage');
                @$this->db->query("UPDATE approval_requests SET status = 'rejected', approve_role = 'rejected', approved_by = {$userId}, approved_at = NOW(), approval_note = '{$escNote}' WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$prId}");

                $this->logActivity($prId, $opoId, 'rejected', $stageLabel, $userId, $note ?: 'Rejected');

                $this->db->commit();
                return ['success' => true, 'message' => "OPO rejected by {$stageLabel}."];
            }

            // action === 'approved'
            if ($stage === 'purchase_head') {
                $upd = $this->db->prepare("
                    UPDATE office_procurement_orders
                    SET purchase_head_status = 'approved', purchase_head_by = ?, purchase_head_at = NOW(), purchase_head_note = ?, status = 'pending_qc'
                    WHERE id = ?
                ");
                $upd->bind_param('isi', $userId, $note, $opoId);
                $upd->execute();
                $upd->close();

                $updPr = $this->db->prepare("
                    UPDATE project_extra_assets
                    SET purchase_head_status = 'approved', purchase_head_by = ?, purchase_head_at = NOW(), purchase_head_note = ?,
                        workflow_stage = 'opo_qc', overall_status = 'in_review'
                    WHERE id = ?
                ");
                $updPr->bind_param('isi', $userId, $note, $prId);
                $updPr->execute();
                $updPr->close();

                $this->logActivity($prId, $opoId, 'approved', $stageLabel, $userId, $note ?: 'Approved by Purchase Department Head');
                $msg = "OPO approved by Purchase Department Head. Forwarded to QC & Internal Audit Head (Role 24).";

            } elseif ($stage === 'qc') {
                $upd = $this->db->prepare("
                    UPDATE office_procurement_orders
                    SET qc_status = 'approved', qc_by = ?, qc_at = NOW(), qc_note = ?, status = 'pending_op_head'
                    WHERE id = ?
                ");
                $upd->bind_param('isi', $userId, $note, $opoId);
                $upd->execute();
                $upd->close();

                $updPr = $this->db->prepare("
                    UPDATE project_extra_assets
                    SET qc_audit_status = 'approved', qc_audit_by = ?, qc_audit_at = NOW(), qc_audit_note = ?,
                        workflow_stage = 'opo_op_head', overall_status = 'in_review'
                    WHERE id = ?
                ");
                $updPr->bind_param('isi', $userId, $note, $prId);
                $updPr->execute();
                $updPr->close();

                $this->logActivity($prId, $opoId, 'approved', $stageLabel, $userId, $note ?: 'Approved by QC & Internal Audit Head');
                $msg = "OPO approved by QC & Internal Audit Head. Forwarded to Admin & Operation Head (Role 20).";

            } elseif ($stage === 'op_head') {
                $upd = $this->db->prepare("
                    UPDATE office_procurement_orders
                    SET op_head_status = 'approved', op_head_by = ?, op_head_at = NOW(), op_head_note = ?, status = 'approved'
                    WHERE id = ?
                ");
                $upd->bind_param('isi', $userId, $note, $opoId);
                $upd->execute();
                $upd->close();

                $isOver10K = ((float)$opo['total_amount'] > 10000.01);
                if (!$isOver10K) {
                    // <= 10K: mark mgmt_status = approved so once Accounts Head (Role 30) approves, Accounts can disburse immediately
                    $updPr = $this->db->prepare("
                        UPDATE project_extra_assets
                        SET mgmt_status = 'approved', mgmt_by = ?, mgmt_at = NOW(), mgmt_note = ?,
                            overall_status = 'approved', workflow_stage = 'payment_request'
                        WHERE id = ?
                    ");
                    $updPr->bind_param('isi', $userId, $note, $prId);
                    $updPr->execute();
                    $updPr->close();
                } else {
                    // > 10K: stays pending for Accounts Head first, then Management
                    $updPr = $this->db->prepare("
                        UPDATE project_extra_assets
                        SET workflow_stage = 'payment_request', overall_status = 'in_review'
                        WHERE id = ?
                    ");
                    $updPr->bind_param('i', $prId);
                    $updPr->execute();
                    $updPr->close();
                }

                // Auto push Payment Request into central approval_requests for Accounts Head (Role 30)
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/ApprovalRequest.php';
                }
                $appModel = new \ApprovalRequest($this->db);
                $appModel->syncMissingOfficeProcurements();

                $escNote = $this->db->real_escape_string($note ?: 'OPO Approved by Admin & Operation Head');
                @$this->db->query("
                    UPDATE approval_requests
                    SET op_head_status = 'approved',
                        op_head_by = {$userId},
                        op_head_at = NOW(),
                        op_head_note = '{$escNote}',
                        approve_role = 'accounts_head',
                        request_amount = " . (float)$opo['total_amount'] . "
                    WHERE source_table IN ('project_extra_assets', 'purchase_requests') AND source_id = {$prId}
                ");

                // Post Accrual Journal Entry (Dr Fixed Asset / Expense, Cr AP Vendors 2110) if <= 10K (for >10K it posts on Mgmt approval)
                if (!$isOver10K) {
                    $this->postProcurementAccrualJournal($prId, $userId);
                }

                $this->logActivity($prId, $opoId, 'approved', $stageLabel, $userId, ($note ?: 'OPO Approved') . ' — Auto-forwarded Payment Request to Accounts Head (Role 30).');
                $msg = "OPO approved by Admin & Operation Head! Payment Request has been automatically sent to All Approval Requests for Accounts Head (Role 30).";
            } else {
                throw new \RuntimeException('Invalid OPO approval stage.');
            }

            $this->db->commit();
            return ['success' => true, 'message' => $msg];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post Accrual Journal Entry to General Journal / Ledger / Balance Sheet / Income Statement
     */
    public function postProcurementAccrualJournal($prId, $userId)
    {
        try {
            if (!class_exists('AssetManagement')) {
                require_once __DIR__ . '/AssetManagement.php';
            }
            if (!class_exists('AutoPostingService')) {
                require_once dirname(__DIR__) . '/services/AutoPostingService.php';
            }
            $assetModel  = new \AssetManagement($this->db);
            $autoPosting = new \AutoPostingService($this->db);
            $req         = $assetModel->findExtraPurchase($prId);
            if (!$req || (float)$req['total_price'] <= 0) return false;

            $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110'); // Accounts Payable - Vendors
            $requestType = strtolower($req['request_type'] ?? 'purchase');
            if ($requestType === 'purchase') {
                // Capitalize to Fixed Asset (Balance Sheet -> Non-Current Assets)
                $debitAccountId = !empty($req['account_id'])
                    ? (int)$req['account_id']
                    : $assetModel->getFixedAssetAccountId($req['item_category_id'] ?? null, $req['item_name'] ?? '');
                if (!$debitAccountId) {
                    $debitAccountId = $autoPosting->getAccountIdByCode('100002');
                }
                $narration = "Office Fixed Asset Procurement (PR-{$prId}): " . ($req['item_name'] ?? '') . " (Qty: {$req['quantity']})";
            } else {
                // Servicing / Maintenance (Income Statement -> Operating Expenses)
                $debitAccountId = !empty($req['account_id'])
                    ? (int)$req['account_id']
                    : $autoPosting->getAccountIdByCode('5220');
                $narration = "Office Asset Servicing (PR-{$prId}): " . ($req['item_name'] ?? '');
            }

            $partyType = !empty($req['vendor_id']) ? 'vendor' : 'employee';
            $partyId   = !empty($req['vendor_id']) ? (int)$req['vendor_id'] : ($req['assigned_to'] ?? $req['requested_by'] ?? 1);
            $office    = !empty($req['branch_name']) ? $req['branch_name'] : (!empty($req['asset_location']) ? $req['asset_location'] : 'Head Office');

            return $autoPosting->postAccrualEntry(
                'asset_procurement',
                $prId,
                $debitAccountId,
                $vendorPayableAccId,
                (float)$req['total_price'],
                $narration,
                !empty($req['project_id']) ? (int)$req['project_id'] : null,
                $partyType,
                $partyId,
                $userId,
                $office
            );
        } catch (\Throwable $e) {
            error_log("[OfficeProcurementOrder::postProcurementAccrualJournal] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Step: CST Assignee clicks "Received Payment" -> status becomes 'process_on_purchase'
     */
    public function markPaymentReceived($prId, $userId, $note = '')
    {
        $prId = (int)$prId;
        $userId = (int)$userId;
        $opo = $this->findOrderByPr($prId);
        $opoId = $opo ? (int)$opo['id'] : 0;

        $stmt = $this->db->prepare("
            UPDATE project_extra_assets
            SET assignee_payment_received_by = ?,
                assignee_payment_received_at = NOW(),
                overall_status = 'process_on_purchase',
                workflow_stage = 'process_on_purchase'
            WHERE id = ? AND payment_status = 'paid'
        ");
        if (!$stmt) return false;
        $stmt->bind_param('ii', $userId, $prId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok && $opoId > 0) {
            $uOpo = $this->db->prepare("UPDATE office_procurement_orders SET status = 'process_on_purchase' WHERE id = ?");
            $uOpo->bind_param('i', $opoId);
            $uOpo->execute();
            $uOpo->close();
        }

        if ($ok) {
            $this->logActivity($prId, $opoId, 'payment_received', 'CST Assignee — Payment Received', $userId, $note ?: 'Disbursed payment received. Status updated to Process on Purchase.');
        }

        return $ok;
    }

    /**
     * Step: CST Assignee marks item as Purchased (uploads invoice)
     */
    public function markPurchasedByAssignee($prId, $userId, $document = null, $note = '', $purchaseDate = null, $returnAmount = 0, $returnNote = '')
    {
        $prId = (int)$prId;
        $userId = (int)$userId;
        $opo = $this->findOrderByPr($prId);
        $opoId = $opo ? (int)$opo['id'] : 0;
        $pDate = !empty($purchaseDate) ? $purchaseDate : date('Y-m-d');

        $stmt = $this->db->prepare("
            UPDATE project_extra_assets
            SET overall_status = 'purchased',
                workflow_stage = 'purchased',
                purchase_document = COALESCE(?, purchase_document),
                purchase_note = ?,
                purchase_date = ?,
                return_amount = ?,
                return_note = ?,
                purchase_confirmed_by = ?,
                purchase_confirmed_at = NOW()
            WHERE id = ?
        ");
        if (!$stmt) return false;
        $stmt->bind_param('sssdsii', $document, $note, $pDate, $returnAmount, $returnNote, $userId, $prId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok && $opoId > 0) {
            $uOpo = $this->db->prepare("UPDATE office_procurement_orders SET status = 'purchased' WHERE id = ?");
            $uOpo->bind_param('i', $opoId);
            $uOpo->execute();
            $uOpo->close();
        }

        if ($ok) {
            $this->logActivity($prId, $opoId, 'purchased', 'CST Assignee — Purchased', $userId, $note ?: 'Item purchased. Awaiting asset receipt confirmation from requester.');
        }

        return $ok;
    }

    /**
     * Final Step: Original Requester clicks "Received Asset"
     * -> Status becomes 'received'
     * -> Auto-registers asset(s) into Asset Tracker (AST-YYYY-NNNN)
     * -> Ensures Financial Statements (Journal, Ledger, Balance Sheet, Income Statement, Assets) are synced
     */
    public function markAssetReceived($prId, $userId, $note = '')
    {
        $prId = (int)$prId;
        $userId = (int)$userId;
        $opo = $this->findOrderByPr($prId);
        $opoId = $opo ? (int)$opo['id'] : 0;

        if (!class_exists('AssetManagement')) {
            require_once __DIR__ . '/AssetManagement.php';
        }
        $assetModel = new \AssetManagement($this->db);
        $req = $assetModel->findExtraPurchase($prId);
        if (!$req) return ['success' => false, 'error' => 'Procurement request not found.'];

        // Preserve the CST Assignee who actually purchased & sent the item to requester
        $origPurchasedBy = !empty($req['purchase_confirmed_by']) ? (int)$req['purchase_confirmed_by'] : (!empty($req['assigned_cst_user_id']) ? (int)$req['assigned_cst_user_id'] : $userId);
        $origPurchasedAt = !empty($req['purchase_confirmed_at']) ? $req['purchase_confirmed_at'] : null;

        // 1. Register into Product Master & Asset Tracker + Capitalization Journal
        $pDate = !empty($req['purchase_date']) ? $req['purchase_date'] : date('Y-m-d');
        $assetModel->confirmPurchased(
            $prId,
            $origPurchasedBy,
            $req['purchase_document'] ?? null,
            $req['purchase_note'] ?? '',
            $req['assigned_to'] ?? $req['requested_by'],
            $req['asset_location'] ?? null,
            (float)($req['return_amount'] ?? 0),
            $req['return_note'] ?? '',
            $pDate
        );

        // 2. Ensure Accrual journal exists (for Servicing -> Income Statement, Purchase -> Balance Sheet Fixed Assets)
        $this->postProcurementAccrualJournal($prId, $userId);

        // 3. Mark PR & OPO as 'received' with requester's timestamp (keeping original purchaser intact)
        $stmt = $this->db->prepare("
            UPDATE project_extra_assets
            SET overall_status = 'received',
                workflow_stage = 'received',
                purchase_confirmed_by = ?,
                purchase_confirmed_at = COALESCE(?, purchase_confirmed_at),
                asset_received_by = ?,
                asset_received_at = NOW(),
                asset_received_note = ?
            WHERE id = ?
        ");
        if ($stmt) {
            $stmt->bind_param('isisi', $origPurchasedBy, $origPurchasedAt, $userId, $note, $prId);
            $stmt->execute();
            $stmt->close();
        }

        if ($opoId > 0) {
            $uOpo = $this->db->prepare("UPDATE office_procurement_orders SET status = 'received' WHERE id = ?");
            if ($uOpo) {
                $uOpo->bind_param('i', $opoId);
                $uOpo->execute();
                $uOpo->close();
            }
        }

        // Fetch generated asset codes for feedback & audit log
        $codes = [];
        $prRemark = "%PR-" . $prId . "%";
        $cStmt = $this->db->prepare("SELECT asset_code FROM office_assets WHERE remarks LIKE ? ORDER BY id ASC");
        if ($cStmt) {
            $cStmt->bind_param('s', $prRemark);
            $cStmt->execute();
            $cRes = $cStmt->get_result();
            while ($cRow = $cRes->fetch_assoc()) {
                $codes[] = $cRow['asset_code'];
            }
            $cStmt->close();
        }

        $codeText = !empty($codes) ? (' Registered Asset Code(s): ' . implode(', ', $codes)) : '';
        $this->logActivity($prId, $opoId, 'asset_received', 'Requester — Asset Received', $userId, ($note ?: 'Asset received by requester.') . $codeText);

        return ['success' => true, 'asset_codes' => $codes];
    }

    /**
     * Count pending OPO actions for sidebar badge
     */
    public function getPendingOpoCountForUser($userId, $roleId)
    {
        $rows = $this->getAllOrders([
            'tab'     => 'pending_my_action',
            'user_id' => $userId,
            'role_id' => $roleId,
        ]);
        return count($rows);
    }
}
