<?php
/**
 * Con_PurchaseOrder Model
 * Tables: purchase_orders, purchase_order_items
 */
class Con_PurchaseOrder extends Model
{
    protected $table     = 'purchase_orders';
    protected $itemTable = 'purchase_order_items';

    // =========================================================================
    // READ
    // =========================================================================

    public function getAll()
    {
        $sql = "SELECT po.*, pr.project_id, p.name AS project_name,
                       u.full_name AS created_by_name,
                       (SELECT SUM(total_price) FROM {$this->itemTable} WHERE purchase_order_id = po.id) AS total_amount
                FROM {$this->table} po
                JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN users u ON u.id = po.created_by
                ORDER BY po.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $sql = "SELECT po.*, pr.project_id, pr.notes AS pr_notes, p.name AS project_name,
                    u.full_name AS created_by_name,
                    apv.full_name AS pm_approver_name,
                    apv_r.name    AS pm_approver_role,
                    qc_u.full_name AS qc_approver_name,
                    mgmt_u.full_name AS mgmt_approver_name
             FROM {$this->table} po
             JOIN purchase_requests pr ON pr.id = po.request_id
             LEFT JOIN projects p ON p.id = pr.project_id
             LEFT JOIN users u ON u.id = po.created_by
             LEFT JOIN users apv ON apv.id = po.approved_by_pm
             LEFT JOIN roles apv_r ON apv_r.id = apv.role_id
             LEFT JOIN users qc_u ON qc_u.id = po.qc_approved_by_id
             LEFT JOIN users mgmt_u ON mgmt_u.id = po.mgmt_approved_by_id
             WHERE po.id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $this->db->error . " | SQL: " . $sql);
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT poi.*, m.name AS material_name, m.unit, v.name AS vendor_name, COALESCE(cat.name, 'Uncategorized') AS category_name
             FROM {$this->itemTable} poi
             JOIN con_materials m ON m.id = poi.material_id
             LEFT JOIN con_material_categories cat ON cat.id = m.category_id
             JOIN vendors v ON v.id = poi.selected_vendor_id
             WHERE poi.purchase_order_id = ?
             ORDER BY poi.id ASC"
        );
        $stmt->bind_param('i', $poId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get pending items for a PO (ordered - received)
     * Used by Material Receiving to know what's still expected
     */
    public function getPendingItems($poId)
    {
        $sql = "SELECT poi.*, m.name AS material_name, m.unit, v.name AS vendor_name,
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

        // Check if PO already exists for this request
        $check = $this->db->prepare("SELECT id FROM {$this->table} WHERE request_id = ?");
        $check->bind_param('i', $requestId);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            return ['success' => false, 'error' => 'PO already exists for this Purchase Request.'];
        }

        $this->db->begin_transaction();
        try {
            // Create PO header
            $orderDate = date('Y-m-d');
            $status = 'Issued';
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->table} (request_id, created_by, status, order_date)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('iiss', $requestId, $userId, $status, $orderDate);
            $stmt->execute();
            $poId = $this->db->insert_id;

            // Create PO items from winning CST selections
            $itemStmt = $this->db->prepare(
                "INSERT INTO {$this->itemTable} (purchase_order_id, material_id, selected_vendor_id, quantity, unit_price, created_by)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );

            // Get quantities from PR items
            $prModel = new \Con_PurchaseRequest($this->db);
            $prItems = $prModel->getItems($requestId);
            $prQtyMap = [];
            foreach ($prItems as $pri) {
                $prQtyMap[$pri['material_id']] = $pri['quantity'];
            }

            foreach ($winners as $w) {
                $qty = $prQtyMap[$w['material_id']] ?? $w['quantity'] ?? 0;
                $itemStmt->bind_param('iiiddi',
                    $poId,
                    $w['material_id'],
                    $w['vendor_id'],
                    $qty,
                    $w['unit_price'],
                    $userId
                );
                $itemStmt->execute();
            }

            // Update PR status
            $prModel->updateStatus($requestId, 'PO Issued');

            $this->db->commit();
            return ['success' => true, 'id' => $poId, 'error' => ''];
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
            JOIN purchase_requests pr ON pr.id = po.request_id
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
