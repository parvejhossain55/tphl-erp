<?php

class Con_Stock extends Model
{
    protected $table = 'con_stocks';

    // =========================================================================
    // SHARED SUBQUERY: Latest unit_price per material from purchase_order_items
    // =========================================================================
    private function latestPriceSubquery()
    {
        return "
            (SELECT poi2.material_id,
                    poi2.unit_price
             FROM purchase_order_items poi2
             WHERE poi2.id = (
                 SELECT MAX(poi3.id)
                 FROM purchase_order_items poi3
                 WHERE poi3.material_id = poi2.material_id
             )
            ) AS poi_price
        ";
    }

    // =========================================================================
    // CURRENT STOCK — full list with live unit_price from PO items
    // =========================================================================
    public function getCurrentStock($material_id = null, $warehouse_id = null)
    {
        $sql = "SELECT s.*,
                       m.name AS material_name, m.unit, m.min_stock_level,
                       w.name AS warehouse_name,
                       COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0) AS unit_price,
                       (s.current_quantity * COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0)) AS stock_value
                FROM {$this->table} s
                JOIN con_materials m ON m.id = s.material_id
                JOIN con_warehouses w ON w.id = s.warehouse_id
                LEFT JOIN {$this->latestPriceSubquery()} ON poi_price.material_id = s.material_id
                WHERE 1=1";

        $params = [];
        $types  = "";

        if ($material_id) {
            $sql .= " AND s.material_id = ?";
            $params[] = $material_id;
            $types   .= "i";
        }
        if ($warehouse_id) {
            $sql .= " AND s.warehouse_id = ?";
            $params[] = $warehouse_id;
            $types   .= "i";
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

    // =========================================================================
    // AVAILABLE STOCK — only qty > 0, with live unit_price
    // =========================================================================
    public function getAvailableStock()
    {
        $sql = "SELECT s.*,
                       m.name AS material_name, m.unit, m.min_stock_level,
                       w.name AS warehouse_name,
                       COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0) AS unit_price,
                       (s.current_quantity * COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0)) AS stock_value
                FROM {$this->table} s
                JOIN con_materials m ON m.id = s.material_id
                JOIN con_warehouses w ON w.id = s.warehouse_id
                LEFT JOIN {$this->latestPriceSubquery()} ON poi_price.material_id = s.material_id
                WHERE s.current_quantity > 0
                ORDER BY w.name ASC, m.name ASC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // LOW STOCK — current_quantity < min_stock_level, with live unit_price
    // =========================================================================
    public function getLowStockItems()
    {
        $sql = "SELECT s.*,
                       m.name AS material_name, m.unit, m.min_stock_level,
                       w.name AS warehouse_name,
                       COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0) AS unit_price,
                       (s.current_quantity * COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0)) AS stock_value,
                       (m.min_stock_level - s.current_quantity) AS deficit,
                       CASE WHEN m.min_stock_level > 0
                            THEN ROUND((s.current_quantity / m.min_stock_level) * 100, 1)
                            ELSE 0 END AS stock_pct
                FROM {$this->table} s
                JOIN con_materials m ON m.id = s.material_id
                JOIN con_warehouses w ON w.id = s.warehouse_id
                LEFT JOIN {$this->latestPriceSubquery()} ON poi_price.material_id = s.material_id
                WHERE s.current_quantity < m.min_stock_level
                ORDER BY stock_pct ASC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================================
    // SUMMARY STATS — KPI cards using live price from PO items
    // =========================================================================
    public function getStockSummaryStats()
    {
        // Total Stock Value using live PO prices
        $valSql = "SELECT COALESCE(SUM(
                       s.current_quantity * COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0)
                   ), 0) AS total_value
                   FROM {$this->table} s
                   LEFT JOIN {$this->latestPriceSubquery()} ON poi_price.material_id = s.material_id";
        $valRow = $this->db->query($valSql)->fetch_assoc();

        // Total distinct materials in stock
        $matRow = $this->db->query(
            "SELECT COUNT(DISTINCT material_id) AS total_materials FROM {$this->table} WHERE current_quantity > 0"
        )->fetch_assoc();

        // Low stock count
        $lowRow = $this->db->query(
            "SELECT COUNT(*) AS low_count
             FROM {$this->table} s
             JOIN con_materials m ON m.id = s.material_id
             WHERE s.current_quantity < m.min_stock_level"
        )->fetch_assoc();

        return [
            'total_value'     => (float)($valRow['total_value'] ?? 0),
            'total_materials' => (int)($matRow['total_materials'] ?? 0),
            'low_stock_count' => (int)($lowRow['low_count'] ?? 0),
        ];
    }

    // =========================================================================
    // BY PROJECT — aggregated from purchase_order_items (value source of truth)
    // =========================================================================
    public function getStockByProject()
    {
        // Project-level summary (value from PO items as source of truth)
        $sql = "SELECT p.id AS project_id, p.name AS project_name,
                       COUNT(DISTINCT po.id) AS po_count,
                       COUNT(DISTINCT poi.material_id) AS material_count,
                       COALESCE(SUM(poi.quantity * poi.unit_price), 0) AS total_value
                FROM projects p
                JOIN purchase_requests pr ON pr.project_id = p.id
                JOIN purchase_orders po ON po.request_id = pr.id
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                WHERE p.deleted_at IS NULL
                GROUP BY p.id, p.name
                ORDER BY total_value DESC";

        $result   = $this->db->query($sql);
        $projects = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        $grandTotal = array_sum(array_column($projects, 'total_value'));

        // Material breakdown per project
        $breakdownSql = "SELECT p.id AS project_id,
                                m.name AS material_name, m.unit,
                                SUM(poi.quantity) AS total_qty,
                                AVG(poi.unit_price) AS avg_price,
                                SUM(poi.quantity * poi.unit_price) AS total_value
                         FROM projects p
                         JOIN purchase_requests pr ON pr.project_id = p.id
                         JOIN purchase_orders po ON po.request_id = pr.id
                         JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                         JOIN con_materials m ON m.id = poi.material_id
                         WHERE p.deleted_at IS NULL
                         GROUP BY p.id, poi.material_id
                         ORDER BY p.id ASC, total_value DESC";

        $bResult    = $this->db->query($breakdownSql);
        $breakdowns = $bResult ? $bResult->fetch_all(MYSQLI_ASSOC) : [];

        $byProjectBreak = [];
        foreach ($breakdowns as $b) {
            $byProjectBreak[$b['project_id']][] = $b;
        }

        foreach ($projects as &$proj) {
            $pid             = $proj['project_id'];
            $proj['materials'] = $byProjectBreak[$pid] ?? [];
            $proj['pct']       = $grandTotal > 0
                                   ? round(($proj['total_value'] / $grandTotal) * 100, 1)
                                   : 0;

            if (!empty($proj['materials'])) {
                $projTotal = $proj['total_value'];
                foreach ($proj['materials'] as &$mat) {
                    $mat['pct'] = $projTotal > 0
                        ? round(($mat['total_value'] / $projTotal) * 100, 1)
                        : 0;
                }
            }
        }

        return [
            'projects'    => $projects,
            'grand_total' => $grandTotal,
        ];
    }

    // =========================================================================
    // LOG MOVEMENT — log + auto-resolve unit_price from PO items
    // =========================================================================
    public function logMovement($data)
    {
        // 1. Log the movement
        $sql  = "INSERT INTO con_stock_movements (material_id, warehouse_id, type, quantity, ref_type, ref_id, moved_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iisdsii',
            $data['material_id'],
            $data['warehouse_id'],
            $data['type'],
            $data['quantity'],
            $data['ref_type'],
            $data['ref_id'],
            $data['moved_by']
        );
        $stmt->execute();

        // 2. Resolve unit_price from purchase_order_items
        $unitPrice = $data['unit_price'] ?? 0;
        if (!$unitPrice && isset($data['ref_type']) && $data['ref_type'] === 'material_receiving') {
            $pStmt = $this->db->prepare(
                "SELECT poi.unit_price
                 FROM purchase_order_items poi
                 JOIN material_receiving_items mri ON mri.purchase_order_item_id = poi.id
                 JOIN material_receiving mr ON mr.id = mri.receiving_id
                 WHERE poi.material_id = ? AND mr.id = ?
                 ORDER BY poi.id DESC LIMIT 1"
            );
            $pStmt->bind_param('ii', $data['material_id'], $data['ref_id']);
            $pStmt->execute();
            $pRow      = $pStmt->get_result()->fetch_assoc();
            $unitPrice = $pRow ? (float)$pRow['unit_price'] : 0;

            // Fallback: any latest PO item for this material
            if (!$unitPrice) {
                $fallback = $this->db->prepare(
                    "SELECT unit_price FROM purchase_order_items WHERE material_id = ? ORDER BY id DESC LIMIT 1"
                );
                $fallback->bind_param('i', $data['material_id']);
                $fallback->execute();
                $fRow      = $fallback->get_result()->fetch_assoc();
                $unitPrice = $fRow ? (float)$fRow['unit_price'] : 0;
            }
        }

        // 3. Update current stock table
        $this->updateCurrentStock(
            $data['material_id'],
            $data['warehouse_id'],
            $data['type'],
            $data['quantity'],
            $unitPrice
        );

        return true;
    }

    // =========================================================================
    // UPDATE CURRENT STOCK (private)
    // =========================================================================
    private function updateCurrentStock($material_id, $warehouse_id, $type, $quantity, $unitPrice = 0)
    {
        $stmt = $this->db->prepare(
            "SELECT id, current_quantity FROM con_stocks WHERE material_id=? AND warehouse_id=?"
        );
        $stmt->bind_param('ii', $material_id, $warehouse_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if ($res) {
            $new_qty   = ($type == 'IN')
                ? ($res['current_quantity'] + $quantity)
                : max(0, $res['current_quantity'] - $quantity);
            $new_value = $new_qty * $unitPrice;

            if ($unitPrice > 0) {
                $upd = $this->db->prepare(
                    "UPDATE con_stocks SET current_quantity=?, unit_price=?, total_value=?, updated_at=NOW() WHERE id=?"
                );
                $upd->bind_param('dddi', $new_qty, $unitPrice, $new_value, $res['id']);
            } else {
                $upd = $this->db->prepare(
                    "UPDATE con_stocks SET current_quantity=?, updated_at=NOW() WHERE id=?"
                );
                $upd->bind_param('di', $new_qty, $res['id']);
            }
            $upd->execute();
        } else {
            if ($type == 'IN') {
                $totalVal = $quantity * $unitPrice;
                $ins      = $this->db->prepare(
                    "INSERT INTO con_stocks (material_id, warehouse_id, current_quantity, unit_price, total_value) VALUES (?, ?, ?, ?, ?)"
                );
                $ins->bind_param('iiddd', $material_id, $warehouse_id, $quantity, $unitPrice, $totalVal);
                $ins->execute();
            }
        }
    }
}
