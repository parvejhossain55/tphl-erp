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
        $stmt = $this->db->prepare(
            "SELECT po.*, pr.project_id, pr.notes AS pr_notes, p.name AS project_name,
                    u.full_name AS created_by_name
             FROM {$this->table} po
             JOIN purchase_requests pr ON pr.id = po.request_id
             LEFT JOIN projects p ON p.id = pr.project_id
             LEFT JOIN users u ON u.id = po.created_by
             WHERE po.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getItems($poId)
    {
        $stmt = $this->db->prepare(
            "SELECT poi.*, m.name AS material_name, m.unit, v.name AS vendor_name
             FROM {$this->itemTable} poi
             JOIN con_materials m ON m.id = poi.material_id
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
                       COALESCE(recv.received_total, 0) AS received_total,
                       (poi.quantity - COALESCE(recv.received_total, 0)) AS pending_qty
                FROM {$this->itemTable} poi
                JOIN con_materials m ON m.id = poi.material_id
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
}
