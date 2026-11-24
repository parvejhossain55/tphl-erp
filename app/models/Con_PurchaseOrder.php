<?php
/**
 * Con_PurchaseOrder Model
 * Tables: purchase_orders, purchase_order_items
 */
class Con_PurchaseOrder extends Model
{
    protected $table     = 'purchase_orders';
    protected $itemTable = 'purchase_order_items';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? ($GLOBALS['db'] ?? null);
        self::ensureSchema($this->db);
    }

    public static function ensureSchema($db)
    {
        static $checked = false;
        if ($checked || !$db) {
            return;
        }
        $checked = true;

        if (!class_exists('Con_PurchaseRequest') && file_exists(__DIR__ . '/Con_PurchaseRequest.php')) {
            require_once __DIR__ . '/Con_PurchaseRequest.php';
        }
        if (class_exists('Con_PurchaseRequest')) {
            Con_PurchaseRequest::ensureSchema($db);
        }

        @$db->query("
            CREATE TABLE IF NOT EXISTS `po_negotiation_notes` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `purchase_order_id` int unsigned DEFAULT NULL,
              `request_id` int unsigned NOT NULL,
              `user_id` int NOT NULL,
              `note_text` text NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        @$db->query("
            CREATE TABLE IF NOT EXISTS `po_item_qty_logs` (
              `id` int unsigned NOT NULL AUTO_INCREMENT,
              `purchase_order_id` int unsigned NOT NULL,
              `purchase_order_item_id` int unsigned NOT NULL,
              `old_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
              `new_quantity` decimal(12,3) NOT NULL DEFAULT '0.000',
              `reason` text,
              `changed_by` int NOT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_po_qty_log` (`purchase_order_item_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");

        $poCols = [
            'vendor_id'                    => "INT UNSIGNED NULL DEFAULT NULL AFTER `request_id`",
            'order_date'                   => "DATE NULL DEFAULT NULL",
            'approved_by_se'               => "INT DEFAULT 0",
            'approved_by_pm'               => "INT DEFAULT 0",
            'se_approved_at'               => "DATETIME NULL DEFAULT NULL",
            'pm_approved_at'               => "DATETIME NULL DEFAULT NULL",
            'qc_approved_by_id'            => "INT NULL DEFAULT NULL",
            'qc_approved_at'               => "DATETIME NULL DEFAULT NULL",
            'qc_note'                      => "TEXT NULL DEFAULT NULL",
            'mgmt_approved_by_id'          => "INT NULL DEFAULT NULL",
            'mgmt_approved_at'             => "DATETIME NULL DEFAULT NULL",
            'mgmt_note'                    => "TEXT NULL DEFAULT NULL",
            'purchase_head_approved_by_id' => "INT NULL DEFAULT NULL",
            'purchase_head_approved_at'    => "DATETIME NULL DEFAULT NULL",
            'purchase_head_note'           => "TEXT NULL DEFAULT NULL",
            'op_head_approved_by_id'       => "INT NULL DEFAULT NULL",
            'op_head_approved_at'          => "DATETIME NULL DEFAULT NULL",
            'op_head_note'                 => "TEXT NULL DEFAULT NULL",
            'bod_approved_by_id'           => "INT NULL DEFAULT NULL",
            'bod_approved_at'              => "DATETIME NULL DEFAULT NULL",
            'bod_note'                     => "TEXT NULL DEFAULT NULL",
            'upload_from'                  => "VARCHAR(50) NULL DEFAULT NULL",
            'remarks'                      => "TEXT NULL DEFAULT NULL",
        ];
        foreach ($poCols as $col => $def) {
            $chk = @$db->query("SHOW COLUMNS FROM `purchase_orders` LIKE '{$col}'");
            if ($chk && $chk->num_rows === 0) {
                if (!@$db->query("ALTER TABLE `purchase_orders` ADD COLUMN `{$col}` {$def}")) {
                    $cleanDef = preg_replace('/\s+AFTER\s+`\w+`$/i', '', $def);
                    @$db->query("ALTER TABLE `purchase_orders` ADD COLUMN `{$col}` {$cleanDef}");
                }
            }
        }

        // Ensure status ENUM includes 'Operation Head Approved' if status column is an ENUM
        $statusCol = @$db->query("SHOW COLUMNS FROM `purchase_orders` LIKE 'status'");
        if ($statusCol && ($statusRow = $statusCol->fetch_assoc())) {
            $colType = $statusRow['Type'] ?? '';
            if (stripos($colType, 'enum(') === 0 && stripos($colType, 'Operation Head Approved') === false) {
                if (preg_match("/^enum\((.*)\)$/i", $colType, $m)) {
                    $newEnum = "ENUM(" . $m[1] . ",'Operation Head Approved')";
                    @$db->query("ALTER TABLE `purchase_orders` MODIFY COLUMN `status` {$newEnum} NOT NULL DEFAULT 'Issued'");
                }
            }
        }
    }

    // =========================================================================
    // READ
    // =========================================================================

    public function getAll()
    {
        $sql = "SELECT po.*, COALESCE(pr.project_id, vpr.project_id) AS project_id, pr.is_urgent AS pr_is_urgent, pr.urgent_reason AS pr_urgent_reason, pr.required_date AS pr_required_date, p.name AS project_name,
                       u.full_name AS created_by_name,
                       u.role_id AS creator_role_id,
                       COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name,
                       v.company_name AS vendor_company_name,
                       (SELECT SUM(total_price) FROM {$this->itemTable} WHERE purchase_order_id = po.id) AS total_amount,
                       (SELECT COALESCE(SUM(quantity), 0) FROM {$this->itemTable} WHERE purchase_order_id = po.id) AS total_qty,
                       (SELECT COALESCE(SUM(mri.received_quantity), 0)
                        FROM material_receiving_items mri
                        JOIN {$this->itemTable} poi_rec ON poi_rec.id = mri.purchase_order_item_id
                        WHERE poi_rec.purchase_order_id = po.id AND mri.status = 'Accepted') AS received_qty,
                       (SELECT u2.full_name FROM po_negotiation_notes n JOIN users u2 ON u2.id = n.user_id WHERE n.purchase_order_id = po.id ORDER BY n.created_at DESC LIMIT 1) AS latest_note_author,
                       (SELECT n2.note_text FROM po_negotiation_notes n2 WHERE n2.purchase_order_id = po.id ORDER BY n2.created_at DESC LIMIT 1) AS latest_note_text,
                       vpr.id AS vpr_id,
                       vpr.status AS vpr_status,
                       vpr.payment_type AS vpr_payment_type,
                       COALESCE(vpr.net_payable, vpr.net_payable_amount, 0) AS vpr_net_payable,
                       COALESCE(vpr_txn.total_paid, 0) AS vpr_total_paid,
                       GREATEST(0, COALESCE(vpr.net_payable, vpr.net_payable_amount, 0) - COALESCE(vpr_txn.total_paid, 0)) AS vpr_due,
                       COALESCE(
                           (SELECT GROUP_CONCAT(DISTINCT m.category_id)
                            FROM {$this->itemTable} poi
                            JOIN con_materials m ON m.id = poi.material_id
                            WHERE poi.purchase_order_id = po.id),
                           (SELECT GROUP_CONCAT(DISTINCT m_pr.category_id)
                            FROM purchase_request_items pri
                            JOIN con_materials m_pr ON m_pr.id = pri.material_id
                            WHERE pri.request_id = po.request_id)
                       ) AS category_ids,
                       COALESCE(
                           (SELECT GROUP_CONCAT(DISTINCT COALESCE(cat.name, 'Uncategorized') SEPARATOR ', ')
                            FROM {$this->itemTable} poi
                            JOIN con_materials m ON m.id = poi.material_id
                            LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                            WHERE poi.purchase_order_id = po.id),
                           (SELECT GROUP_CONCAT(DISTINCT COALESCE(cat_pr.name, 'Uncategorized') SEPARATOR ', ')
                            FROM purchase_request_items pri
                            JOIN con_materials m_pr ON m_pr.id = pri.material_id
                            LEFT JOIN con_material_categories cat_pr ON cat_pr.id = m_pr.category_id
                            WHERE pri.request_id = po.request_id)
                       ) AS category_names
                FROM {$this->table} po
                LEFT JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN (
                    SELECT v1.* 
                    FROM vendor_payment_requests v1
                    INNER JOIN (
                        SELECT purchase_order_id, MAX(id) AS max_id 
                        FROM vendor_payment_requests 
                        GROUP BY purchase_order_id
                    ) v2 ON v1.id = v2.max_id
                ) vpr ON vpr.purchase_order_id = po.id
                LEFT JOIN projects p ON p.id = COALESCE(pr.project_id, vpr.project_id)
                LEFT JOIN users u ON u.id = po.created_by
                LEFT JOIN vendors v ON v.id = po.vendor_id
                LEFT JOIN (
                    SELECT ref_id, SUM(amount_paid) AS total_paid 
                    FROM con_financial_transactions 
                    WHERE ref_type = 'vendor_payment' 
                    GROUP BY ref_id
                ) vpr_txn ON vpr_txn.ref_id = vpr.id
                ORDER BY po.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $sql = "SELECT po.*, COALESCE(pr.project_id, vpr.project_id) AS project_id, pr.notes AS pr_notes, p.name AS project_name,
                    pr.required_date AS pr_required_date,
                    pr.is_urgent AS pr_is_urgent,
                    pr.urgent_reason AS pr_urgent_reason,
                    u.full_name AS created_by_name,
                    u.role_id AS creator_role_id,
                    COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name,
                    v.company_name AS vendor_company,
                    apv.full_name AS pm_approver_name,
                    apv_r.name    AS pm_approver_role,
                    qc_u.full_name AS qc_approver_name,
                    mgmt_u.full_name AS mgmt_approver_name,
                    ph_u.full_name AS ph_approver_name,
                    op_u.full_name AS op_approver_name,
                    bod_u.full_name AS bod_approver_name,
                    (SELECT COALESCE(SUM(quantity * unit_price), 0) FROM {$this->itemTable} WHERE purchase_order_id = po.id) AS total_amount
             FROM {$this->table} po
             LEFT JOIN purchase_requests pr ON pr.id = po.request_id
             LEFT JOIN (
                 SELECT v1.purchase_order_id, v1.project_id
                 FROM vendor_payment_requests v1
                 INNER JOIN (
                     SELECT purchase_order_id, MAX(id) AS max_id
                     FROM vendor_payment_requests
                     GROUP BY purchase_order_id
                 ) v2 ON v1.id = v2.max_id
             ) vpr ON vpr.purchase_order_id = po.id
             LEFT JOIN projects p ON p.id = COALESCE(pr.project_id, vpr.project_id)
             LEFT JOIN users u ON u.id = po.created_by
             LEFT JOIN vendors v ON v.id = po.vendor_id
             LEFT JOIN users apv ON apv.id = po.approved_by_pm
             LEFT JOIN roles apv_r ON apv_r.id = apv.role_id
             LEFT JOIN users qc_u ON qc_u.id = po.qc_approved_by_id
             LEFT JOIN users mgmt_u ON mgmt_u.id = po.mgmt_approved_by_id
             LEFT JOIN users ph_u ON ph_u.id = po.purchase_head_approved_by_id
             LEFT JOIN users op_u ON op_u.id = po.op_head_approved_by_id
             LEFT JOIN users bod_u ON bod_u.id = po.bod_approved_by_id
             WHERE po.id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $this->db->error . " | SQL: " . $sql);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }

    public function getItems($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT poi.*, m.name AS material_name, m.unit, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, v.company_name AS vendor_company_name, COALESCE(cat.name, 'Uncategorized') AS category_name,
                    COALESCE(pri.current_stock, 0) AS current_stock,
                    COALESCE(pri.pr_quantity, 0) AS pr_quantity,
                    COALESCE(pri.next_3_months_requirement, 0) AS next_3_months_requirement,
                    (SELECT COUNT(*) FROM po_item_qty_logs l WHERE l.purchase_order_item_id = poi.id) AS edit_count,
                    (SELECT u.full_name FROM po_item_qty_logs l JOIN users u ON u.id = l.changed_by WHERE l.purchase_order_item_id = poi.id ORDER BY l.id DESC LIMIT 1) AS last_edited_by_name,
                    (SELECT l.created_at FROM po_item_qty_logs l WHERE l.purchase_order_item_id = poi.id ORDER BY l.id DESC LIMIT 1) AS last_edited_at
             FROM {$this->itemTable} poi
             JOIN purchase_orders po ON po.id = poi.purchase_order_id
             LEFT JOIN (
                 SELECT request_id, material_id,
                        SUM(quantity) AS pr_quantity,
                        MAX(current_stock) AS current_stock,
                        SUM(COALESCE(month_1_forecast, 0) + COALESCE(month_2_forecast, 0) + COALESCE(month_3_forecast, 0)) AS next_3_months_requirement
                 FROM purchase_request_items
                 GROUP BY request_id, material_id
             ) pri ON pri.request_id = po.request_id AND pri.material_id = poi.material_id
             JOIN con_materials m ON m.id = poi.material_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
             JOIN vendors v ON v.id = poi.selected_vendor_id
             WHERE poi.purchase_order_id = ?
             ORDER BY poi.id ASC"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    /**
     * Get all quantity adjustment logs for a Purchase Order
     */
    public function getQtyLogs($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT l.*, u.full_name AS changed_by_name, r.name AS changed_by_role, m.name AS material_name, m.unit
             FROM po_item_qty_logs l
             LEFT JOIN users u ON u.id = l.changed_by
             LEFT JOIN roles r ON r.id = u.role_id
             LEFT JOIN con_materials m ON m.id = l.material_id
             WHERE l.purchase_order_id = ?
             ORDER BY l.id DESC"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res;
    }

    /**
     * Update PO item quantity with CST calculation and audit trail
     */
    public function updateItemQuantity($poId, $itemId, $newQty, $userId, $reason = '')
    {
        $newQty = (float)$newQty;
        if ($newQty <= 0) {
            return ['success' => false, 'error' => 'Quantity must be greater than zero.'];
        }

        // Verify item exists for this PO
        $stmt = $this->db->prepare("SELECT * FROM {$this->itemTable} WHERE id = ? AND purchase_order_id = ?");
        if (!$stmt) {
            return ['success' => false, 'error' => 'Database error (verify item): ' . $this->db->error];
        }
        $stmt->bind_param('ii', $itemId, $poId);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$item) {
            return ['success' => false, 'error' => 'Purchase order item not found.'];
        }

        // Check already received quantity
        $recvStmt = $this->db->prepare("
            SELECT COALESCE(SUM(received_quantity), 0) AS received
            FROM material_receiving_items
            WHERE purchase_order_item_id = ? AND status IN ('Accepted', 'Partially Accepted')
        ");
        if (!$recvStmt) {
            return ['success' => false, 'error' => 'Database error (check receiving): ' . $this->db->error];
        }
        $recvStmt->bind_param('i', $itemId);
        $recvStmt->execute();
        $recvRow = $recvStmt->get_result()->fetch_assoc();
        $recvStmt->close();
        $receivedTotal = (float)($recvRow['received'] ?? 0);

        if ($newQty < $receivedTotal) {
            return [
                'success' => false, 
                'error' => "Cannot set quantity to " . number_format($newQty, 3) . " because " . number_format($receivedTotal, 3) . " units have already been received."
            ];
        }

        $oldQty = (float)$item['quantity'];
        if (abs($oldQty - $newQty) < 0.0001) {
            return ['success' => false, 'error' => 'Quantity was not changed (same value entered).'];
        }

        $unitPrice = (float)$item['unit_price'];
        $oldTotalPrice = (float)($item['total_price'] ?: ($oldQty * $unitPrice));
        $newTotalPrice = round($newQty * $unitPrice, 4);
        $materialId = (int)$item['material_id'];

        $this->db->begin_transaction();
        try {
            // Update purchase_order_items (only update quantity; total_price is a generated column that MySQL recalculates automatically)
            $upStmt = $this->db->prepare("UPDATE {$this->itemTable} SET quantity = ? WHERE id = ?");
            if (!$upStmt) {
                throw new \Exception("Database error on prepare item update: " . $this->db->error);
            }
            $upStmt->bind_param('di', $newQty, $itemId);
            $upStmt->execute();
            $upStmt->close();

            // Insert audit record
            $logStmt = $this->db->prepare("
                INSERT INTO po_item_qty_logs 
                (purchase_order_id, purchase_order_item_id, material_id, old_quantity, new_quantity, unit_price, old_total_price, new_total_price, changed_by, reason)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$logStmt) {
                throw new \Exception("Database error on prepare audit log: " . $this->db->error);
            }
            $logStmt->bind_param(
                'iiidddddis',
                $poId,
                $itemId,
                $materialId,
                $oldQty,
                $newQty,
                $unitPrice,
                $oldTotalPrice,
                $newTotalPrice,
                $userId,
                $reason
            );
            $logStmt->execute();
            $logStmt->close();

            // If PO was already Approved by Operation Head (<= 30,000) and new total exceeds 30,000 without BOD approval,
            // require Board of Director approval by reverting status to 'Operation Head Approved'.
            $totStmt = $this->db->prepare("SELECT po.status, po.op_head_approved_by_id, po.bod_approved_by_id, COALESCE(SUM(poi.quantity * poi.unit_price), 0) AS new_total FROM {$this->table} po LEFT JOIN {$this->itemTable} poi ON poi.purchase_order_id = po.id WHERE po.id = ? GROUP BY po.id");
            if ($totStmt) {
                $totStmt->bind_param('i', $poId);
                $totStmt->execute();
                $totRow = $totStmt->get_result()->fetch_assoc();
                $totStmt->close();
                if ($totRow && $totRow['status'] === 'Approved' && !empty($totRow['op_head_approved_by_id']) && empty($totRow['bod_approved_by_id']) && (float)$totRow['new_total'] > 30000) {
                    $revStmt = $this->db->prepare("UPDATE {$this->table} SET status = 'Operation Head Approved' WHERE id = ?");
                    if ($revStmt) {
                        $revStmt->bind_param('i', $poId);
                        $revStmt->execute();
                        $revStmt->close();
                    }
                }
            }

            $this->db->commit();
            return [
                'success'         => true,
                'old_quantity'    => $oldQty,
                'new_quantity'    => $newQty,
                'unit_price'      => $unitPrice,
                'new_total_price' => $newTotalPrice,
                'error'           => ''
            ];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get pending items for a PO (ordered - received)
     * Used by Material Receiving to know what's still expected
     */
    public function getPendingItems($poId)
    {
        $sql = "SELECT poi.*, m.name AS material_name, m.unit, COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) AS vendor_name, v.company_name AS vendor_company_name,
                       COALESCE(cat.name, 'Uncategorized') AS category_name,
                       COALESCE(recv.received_total, 0) AS received_total,
                       (poi.quantity - COALESCE(recv.received_total, 0)) AS pending_qty
                FROM {$this->itemTable} poi
                JOIN con_materials m ON m.id = poi.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                JOIN vendors v ON v.id = poi.selected_vendor_id
                LEFT JOIN (
                    SELECT mri.purchase_order_item_id, SUM(mri.received_quantity) AS received_total
                    FROM material_receiving_items mri
                    WHERE mri.status IN ('Accepted','Partially Accepted')
                    GROUP BY mri.purchase_order_item_id
                ) recv ON recv.purchase_order_item_id = poi.id
                WHERE poi.purchase_order_id = ?
                ORDER BY poi.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // WRITE
    // =========================================================================

    /**
     * Generate PO from CST winning selections.
     * Atomically: create PO → PO items → update PR status to 'PO Issued'
     */
    public function generate($requestId, $userId)
    {
        $quotModel = new \Con_Quotation($this->db);
        $winners = $quotModel->getWinningSelections($requestId);

        if (empty($winners)) {
            return ['success' => false, 'error' => 'No winning selections found. Complete CST first.'];
        }

        // Group winners by vendor_id
        $groupedWinners = [];
        foreach ($winners as $w) {
            $groupedWinners[$w['vendor_id']][] = $w;
        }

        // Check which active POs already exist for this request to prevent duplication (ignoring Cancelled/Rejected POs)
        $existingVendors = [];
        $check = $this->db->prepare("SELECT vendor_id FROM {$this->table} WHERE request_id = ? AND status != 'Cancelled'");
        $check->bind_param('i', $requestId);
        $check->execute();
        $res = $check->get_result();
        while ($row = $res->fetch_assoc()) {
            if ($row['vendor_id'] !== null) {
                $existingVendors[] = (int)$row['vendor_id'];
            }
        }

        // Find vendors for which POs need to be created
        $vendorsToCreate = [];
        foreach ($groupedWinners as $vId => $items) {
            if (!in_array($vId, $existingVendors, true)) {
                $vendorsToCreate[$vId] = $items;
            }
        }

        if (empty($vendorsToCreate)) {
            return ['success' => false, 'error' => 'PO(s) already exist for all selected vendors in this Purchase Request.'];
        }

        $this->db->begin_transaction();
        try {
            $firstPoId = null;
            $orderDate = date('Y-m-d');
            $status = 'Issued';

            // Get quantities from PR items
            $prModel = new \Con_PurchaseRequest($this->db);
            $prItems = $prModel->getItems($requestId);
            $prQtyMap = [];
            foreach ($prItems as $pri) {
                if (!isset($prQtyMap[$pri['material_id']])) {
                    $prQtyMap[$pri['material_id']] = (float)$pri['quantity'];
                } else {
                    $prQtyMap[$pri['material_id']] += (float)$pri['quantity'];
                }
            }

            // Prepared statements
            $poStmt = $this->db->prepare(
                "INSERT INTO {$this->table} (request_id, created_by, status, order_date, vendor_id)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable} (purchase_order_id, material_id, selected_vendor_id, quantity, unit_price, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            foreach ($vendorsToCreate as $vendorId => $items) {
                // Insert PO Header
                $poStmt->bind_param('iissi', $requestId, $userId, $status, $orderDate, $vendorId);
                $poStmt->execute();
                $poId = $poStmt->insert_id;

                if (!$firstPoId) {
                    $firstPoId = $poId;
                }

                // Insert PO Items
                foreach ($items as $w) {
                    $qty = $prQtyMap[$w['material_id']] ?? $w['quantity'] ?? 0;
                    $itemStmt->bind_param('iiiddi',
                        $poId,
                        $w['material_id'],
                        $vendorId,
                        $qty,
                        $w['unit_price'],
                        $userId
                    );
                    $itemStmt->execute();
                }
            }

            // Update PR status
            $prModel->updateStatus($requestId, 'PO Issued');

            $this->db->commit();
            return ['success' => true, 'id' => $firstPoId, 'error' => ''];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function updateStatus($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $id);
        return $stmt->execute();
    }

    // =========================================================================
    // BOQ REFERENCE — Trace PO → PR → BOQ
    // =========================================================================

    /**
     * Get the BOQ (master + items) that is linked to a PO via purchase_requests.bom_id.
     * Returns ['boq' => [...], 'items' => [...]] or null if no BOQ linked.
     */
    public function getLinkedBoq($poId)
    {
        // Step 1: get bom_id from the purchase_request linked to this PO
        $stmt = $this->db->prepare("
            SELECT pr.bom_id, pr.project_id, p.name AS project_name
            FROM purchase_orders po
            LEFT JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            WHERE po.id = ?
            LIMIT 1");
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        if (!$row || empty($row['bom_id'])) {
            return null;
        }

        $boqId = (int)$row['bom_id'];

        // Step 2: get BOQ master info
        $stmtBoq = $this->db->prepare("
            SELECT bm.*, p.name AS project_name,
                   CONCAT(pp.phase_name, ' - ', DATE_FORMAT(pmt.target_month, '%M %Y')) AS target_title,
                   pmt.target_year AS target_year,
                   pmt.target_month_number AS target_month,
                   NULL AS target_week,
                   dt.name AS design_type_name,
                   u.full_name AS created_by_name
            FROM boq_master bm
            LEFT JOIN projects             p   ON p.id   = bm.project_id
            LEFT JOIN phase_monthly_targets pmt ON pmt.id  = bm.target_id
            LEFT JOIN project_phases       pp  ON pp.id   = pmt.phase_id
            LEFT JOIN design_types         dt  ON dt.id   = bm.design_type_id
            LEFT JOIN users               u   ON u.id   = bm.created_by
            WHERE bm.id = ?
            LIMIT 1");
        $stmtBoq->bind_param('i', $boqId);
        $stmtBoq->execute();
        $boq = $stmtBoq->get_result()->fetch_assoc();

        if (!$boq) {
            return null;
        }

        // Step 3: get BOQ items
        $stmtItems = $this->db->prepare("
            SELECT bi.*, m.name AS material_name, m.unit,
                   COALESCE(cat.name, 'Uncategorized') AS category_name,
                   (bi.quantity * bi.unit_cost) AS line_total
            FROM boq_items bi
            JOIN con_materials m ON m.id = bi.material_id
            LEFT JOIN con_material_categories cat ON cat.id = m.category_id
            WHERE bi.boq_id = ?
            ORDER BY m.name ASC");
        $stmtItems->bind_param('i', $boqId);
        $stmtItems->execute();
        $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'boq'   => $boq,
            'items' => $items,
        ];
    }
}
