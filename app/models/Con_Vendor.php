<?php

class Con_Vendor extends Model
{
    protected $table = 'vendors';

public function getAll()
{
    $sql = "SELECT 
                v.*, 
                u.full_name AS created_by_name,

                COUNT(DISTINCT po.id) AS total_orders,

                COUNT(DISTINCT CASE 
                    WHEN vp.penalty_status = 'Approved' THEN vp.id 
                END) AS penalty_count

            FROM vendors v

            LEFT JOIN users u 
                ON u.id = v.created_by

            LEFT JOIN purchase_order_items poi 
                ON poi.selected_vendor_id = v.id

            LEFT JOIN purchase_orders po 
                ON po.id = poi.purchase_order_id

            LEFT JOIN vendor_penalties vp 
                ON vp.vendor_id = v.id

            GROUP BY v.id

            ORDER BY v.name ASC";

    return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
}

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} (name, phone, email, address, rating, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssdsi',
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['rating'],
            $data['status'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET name=?, phone=?, email=?, address=?, status=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssssi',
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /** Get all penalties for a vendor */
    public function getPenalties($vendorId)
    {
        $stmt = $this->db->prepare(
            "SELECT vp.*, u.full_name AS created_by_name
             FROM vendor_penalties vp
             LEFT JOIN users u ON u.id = vp.created_by
             WHERE vp.vendor_id = ?
             ORDER BY vp.id DESC"
        );
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Get PO items history for a vendor */
    public function getPurchaseHistory($vendorId)
    {
        $sql = "SELECT poi.*, po.id AS po_id, po.order_date, po.status AS po_status,
                       m.name AS material_name, m.unit, COALESCE(cat.name, 'Uncategorized') AS category_name, p.name AS project_name
                FROM purchase_order_items poi
                JOIN purchase_orders po ON po.id = poi.purchase_order_id
                JOIN purchase_requests pr ON pr.id = po.request_id
                JOIN con_materials m ON m.id = poi.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                LEFT JOIN projects p ON p.id = pr.project_id
                WHERE poi.selected_vendor_id = ?
                ORDER BY po.order_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Get quick stats for vendor profile */
    public function getStats($vendorId)
    {
        // Total PO value
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(poi.quantity * poi.unit_price), 0) AS total_value,
                    COUNT(DISTINCT poi.purchase_order_id) AS po_count
             FROM purchase_order_items poi
             WHERE poi.selected_vendor_id = ?"
        );
        $stmt->bind_param('i', $vendorId);
        $stmt->execute();
        $poStats = $stmt->get_result()->fetch_assoc();

        // Penalty count + total amount
        $stmt2 = $this->db->prepare(
            "SELECT COUNT(*) AS penalty_count, COALESCE(SUM(penalty_amount), 0) AS penalty_total
             FROM vendor_penalties WHERE vendor_id = ?"
        );
        $stmt2->bind_param('i', $vendorId);
        $stmt2->execute();
        $penaltyStats = $stmt2->get_result()->fetch_assoc();

        // Accepted / Rejected items
        $stmt3 = $this->db->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN mri.status = 'Accepted' THEN 1 ELSE 0 END), 0) AS accepted_items,
                COALESCE(SUM(CASE WHEN mri.status = 'Rejected' THEN 1 ELSE 0 END), 0) AS rejected_items
             FROM material_receiving_items mri
             JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
             WHERE poi.selected_vendor_id = ?"
        );
        $stmt3->bind_param('i', $vendorId);
        $stmt3->execute();
        $qcStats = $stmt3->get_result()->fetch_assoc();

        return array_merge($poStats, $penaltyStats, $qcStats);
    }
}

