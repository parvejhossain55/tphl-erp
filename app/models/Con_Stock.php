<?php

class Con_Stock extends Model
{
    protected $table = 'con_stocks';

    public function getCurrentStock($material_id = null, $warehouse_id = null)
    {
        $sql = "SELECT s.*, m.name AS material_name, m.unit, w.name AS warehouse_name
                FROM {$this->table} s
                JOIN con_materials m ON m.id = s.material_id
                JOIN con_warehouses w ON w.id = s.warehouse_id
                WHERE 1=1";
        
        $params = [];
        $types = "";

        if ($material_id) {
            $sql .= " AND s.material_id = ?";
            $params[] = $material_id;
            $types .= "i";
        }
        if ($warehouse_id) {
            $sql .= " AND s.warehouse_id = ?";
            $params[] = $warehouse_id;
            $types .= "i";
        }

        $sql .= " ORDER BY w.name ASC, m.name ASC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function logMovement($data)
    {
        // 1. Log the movement
        $sql = "INSERT INTO con_stock_movements (material_id, warehouse_id, type, quantity, ref_type, ref_id, moved_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iisdsii',
            $data['material_id'],
            $data['warehouse_id'],
            $data['type'], // 'IN' or 'OUT'
            $data['quantity'],
            $data['ref_type'],
            $data['ref_id'],
            $data['moved_by']
        );
        $stmt->execute();

        // 2. Update current stock table
        $this->updateCurrentStock($data['material_id'], $data['warehouse_id'], $data['type'], $data['quantity']);
        
        return true;
    }

    private function updateCurrentStock($material_id, $warehouse_id, $type, $quantity)
    {
        // Check if record exists
        $stmt = $this->db->prepare("SELECT id, current_quantity FROM con_stocks WHERE material_id=? AND warehouse_id=?");
        $stmt->bind_param('ii', $material_id, $warehouse_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res) {
            $new_qty = ($type == 'IN') ? ($res['current_quantity'] + $quantity) : ($res['current_quantity'] - $quantity);
            $upd = $this->db->prepare("UPDATE con_stocks SET current_quantity=? WHERE id=?");
            $upd->bind_param('di', $new_qty, $res['id']);
            $upd->execute();
        } else {
            if ($type == 'IN') {
                $ins = $this->db->prepare("INSERT INTO con_stocks (material_id, warehouse_id, current_quantity) VALUES (?, ?, ?)");
                $ins->bind_param('iid', $material_id, $warehouse_id, $quantity);
                $ins->execute();
            }
        }
    }
}
