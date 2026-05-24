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
                       COALESCE(cat.name, 'Uncategorized') AS category_name,
                       COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0) AS unit_price,
                       (s.current_quantity * COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0)) AS stock_value,
                       last_out.quantity      AS last_issued_qty,
                       last_out.created_at    AS last_issued_at,
                       isu.full_name          AS last_issued_by_name
                FROM {$this->table} s
                JOIN con_materials  m ON m.id = s.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                JOIN con_warehouses w ON w.id = s.warehouse_id
                LEFT JOIN {$this->latestPriceSubquery()} ON poi_price.material_id = s.material_id
                LEFT JOIN (
                    SELECT sm.material_id, sm.warehouse_id,
                           sm.quantity, sm.created_at, sm.moved_by
                    FROM con_stock_movements sm
                    INNER JOIN (
                        SELECT material_id, warehouse_id, MAX(id) AS max_id
                        FROM con_stock_movements
                        WHERE type = 'OUT'
                        GROUP BY material_id, warehouse_id
                    ) t ON t.material_id = sm.material_id
                       AND t.warehouse_id = sm.warehouse_id
                       AND t.max_id = sm.id
                ) AS last_out ON last_out.material_id  = s.material_id
                             AND last_out.warehouse_id = s.warehouse_id
                LEFT JOIN users isu ON isu.id = last_out.moved_by
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
                       COALESCE(cat.name, 'Uncategorized') AS category_name,
                       COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0) AS unit_price,
                       (s.current_quantity * COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0)) AS stock_value
                FROM {$this->table} s
                JOIN con_materials m ON m.id = s.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
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
                       COALESCE(cat.name, 'Uncategorized') AS category_name,
                       COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0) AS unit_price,
                       (s.current_quantity * COALESCE(NULLIF(s.unit_price, 0), poi_price.unit_price, 0)) AS stock_value,
                       (m.min_stock_level - s.current_quantity) AS deficit,
                       CASE WHEN m.min_stock_level > 0
                            THEN ROUND((s.current_quantity / m.min_stock_level) * 100, 1)
                            ELSE 0 END AS stock_pct
                FROM {$this->table} s
                JOIN con_materials m ON m.id = s.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
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
        // ── Project-level summary ───────────────────────────────────────────
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

        // ── Material breakdown: received qty per project ────────────────────
        // mri has no material_id — must join through purchase_order_items
        $breakdownSql = "SELECT pr.project_id,
                                poi.material_id,
                                m.name AS material_name, m.unit,
                                COALESCE(cat.name, 'Uncategorized') AS category_name,
                                SUM(mri.received_quantity) AS total_received,
                                AVG(poi.unit_price)        AS avg_price,
                                SUM(mri.received_quantity * poi.unit_price) AS total_value
                         FROM material_receiving_items mri
                         JOIN material_receiving mr ON mr.id = mri.receiving_id
                         JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                         JOIN purchase_orders po ON po.id = poi.purchase_order_id
                         JOIN purchase_requests pr ON pr.id = po.request_id
                         JOIN con_materials m ON m.id = poi.material_id
                         LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                         WHERE mri.status = 'Accepted' 
                           AND mr.overall_status IN ('Accepted', 'Partially Accepted')
                           AND po.status != 'Cancelled'
                         GROUP BY pr.project_id, poi.material_id, m.name, m.unit, cat.name
                         ORDER BY pr.project_id ASC, total_value DESC";

        $bResult    = $this->db->query($breakdownSql);
        $breakdowns = $bResult ? $bResult->fetch_all(MYSQLI_ASSOC) : [];

        // ── Issued qty per material per project ─────────────────────────────
        $issuedSql = "SELECT mi.project_id, mi.material_id,
                             SUM(mi.quantity) AS total_issued
                      FROM con_material_issues mi
                      GROUP BY mi.project_id, mi.material_id";
        $iResult  = $this->db->query($issuedSql);
        $issuedMap = [];
        if ($iResult) {
            foreach ($iResult->fetch_all(MYSQLI_ASSOC) as $ir) {
                $issuedMap[$ir['project_id']][$ir['material_id']] = (float)$ir['total_issued'];
            }
        }

        // ── Issue detail: target + work_plan per project+material ───────────
        $detailSql = "SELECT mi.project_id, mi.material_id,
                             mi.quantity, mi.issue_date,
                             u.full_name AS issued_by_name,
                             tf.target_title,
                             wp.work_description AS work_plan_title
                      FROM con_material_issues mi
                      LEFT JOIN users u ON u.id = mi.issued_by
                      LEFT JOIN target_fixed tf ON tf.id = mi.target_id
                      LEFT JOIN project_working_plans wp ON wp.id = mi.work_plan_id
                      ORDER BY mi.project_id, mi.material_id, mi.issue_date DESC";
        $dResult = $this->db->query($detailSql);
        $issueDetailMap = [];
        if ($dResult) {
            foreach ($dResult->fetch_all(MYSQLI_ASSOC) as $dr) {
                $issueDetailMap[$dr['project_id']][$dr['material_id']][] = $dr;
            }
        }

        // ── Assemble breakdown per project ──────────────────────────────────
        $byProjectBreak = [];
        foreach ($breakdowns as $b) {
            $pid = $b['project_id'];
            $mid = $b['material_id'];
            $b['total_issued']   = $issuedMap[$pid][$mid] ?? 0;
            $b['remaining_qty']  = max(0, (float)$b['total_received'] - $b['total_issued']);
            $b['issue_details']  = $issueDetailMap[$pid][$mid] ?? [];
            $byProjectBreak[$pid][] = $b;
        }

        foreach ($projects as &$proj) {
            $pid               = $proj['project_id'];
            $proj['materials'] = $byProjectBreak[$pid] ?? [];
            $proj['pct']       = $grandTotal > 0
                                   ? round(($proj['total_value'] / $grandTotal) * 100, 1)
                                   : 0;

            // Total issued for project
            $proj['total_issued'] = 0;
            if (!empty($proj['materials'])) {
                $projTotal = $proj['total_value'];
                foreach ($proj['materials'] as &$mat) {
                    $mat['pct'] = $projTotal > 0
                        ? round(($mat['total_value'] / $projTotal) * 100, 1)
                        : 0;
                    $proj['total_issued'] += $mat['total_issued'];
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

    // =========================================================================
    // BUDGET TRACKING (Initial Budget vs Received)
    // =========================================================================
    public function getBudgetVsReceived()
    {
        $sql = "
            SELECT
                ieb.id AS budget_id,
                p.id AS project_id,
                p.name AS project_name,
                dt.name AS design_type_name,
                wt.work_type_name,
                m.id AS material_id,
                m.name AS material_name,
                m.unit,
                COALESCE(cat.name, 'Uncategorized') AS category_name,
                iei.estimated_qty AS initial_budget_qty,
                COALESCE(received.total_received, 0) AS total_received,
                (iei.estimated_qty - COALESCE(received.total_received, 0)) AS available_qty
            FROM initial_estimate_budgets ieb
            JOIN projects p ON p.id = ieb.project_id
            LEFT JOIN work_types wt ON wt.id = ieb.work_type_id
            LEFT JOIN design_types dt ON dt.id = wt.design_type_id
            JOIN initial_estimate_items iei ON iei.budget_id = ieb.id
            JOIN con_materials m ON m.id = iei.material_id
            LEFT JOIN con_material_categories cat ON cat.id = m.category_id
            LEFT JOIN (
                -- Simple: received qty per project + material, no boq_master dependency
                SELECT
                    pr.project_id,
                    poi.material_id,
                    SUM(mri.received_quantity) AS total_received
                FROM material_receiving_items mri
                JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                JOIN material_receiving mr    ON mr.id  = mri.receiving_id
                JOIN purchase_orders po       ON po.id  = mr.purchase_order_id
                JOIN purchase_requests pr     ON pr.id  = po.request_id
                WHERE mri.status = 'Accepted'
                  AND mr.overall_status IN ('Accepted', 'Partially Accepted')
                GROUP BY pr.project_id, poi.material_id
            ) received ON received.project_id = p.id AND received.material_id = iei.material_id
            WHERE ieb.status = 'approved' AND p.deleted_at IS NULL
            ORDER BY p.id ASC, wt.id ASC, m.name ASC
        ";

        $result = $this->db->query($sql);
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

        // ── Issued qty + value per material per project ───────────────────────
        $issuedSql = "SELECT mi.project_id, mi.material_id,
                             SUM(mi.quantity) AS total_issued,
                             SUM(mi.quantity * COALESCE(poi_p.unit_price, 0)) AS total_issued_value
                      FROM con_material_issues mi
                      LEFT JOIN (
                          SELECT material_id, MAX(unit_price) AS unit_price
                          FROM purchase_order_items
                          GROUP BY material_id
                      ) poi_p ON poi_p.material_id = mi.material_id
                      GROUP BY mi.project_id, mi.material_id";
        $iRes = $this->db->query($issuedSql);
        $issuedMap = [];
        if ($iRes) {
            foreach ($iRes->fetch_all(MYSQLI_ASSOC) as $ir) {
                $issuedMap[$ir['project_id']][$ir['material_id']] = [
                    'qty'   => (float)$ir['total_issued'],
                    'value' => (float)$ir['total_issued_value'],
                ];
            }
        }

        // Group by Project -> Work Type -> Material
        $grouped = [];
        foreach ($rows as $row) {
            $pid   = $row['project_id'];
            $mid   = $row['material_id'];
            $pname = $row['project_name'];
            $wname = trim($row['design_type_name'] . ' - ' . $row['work_type_name'], ' -');
            if (empty($wname)) $wname = 'General / Unspecified';

            // Attach issued data
            $issuedQty   = $issuedMap[$pid][$mid]['qty']   ?? 0;
            $issuedValue = $issuedMap[$pid][$mid]['value'] ?? 0;
            $budgetQty   = (float)$row['initial_budget_qty'];
            $row['issued_qty']       = $issuedQty;
            $row['issued_value']     = $issuedValue;
            $row['budget_remaining'] = max(0, $budgetQty - $issuedQty);
            $row['issued_pct']       = $budgetQty > 0
                                         ? min(100, round(($issuedQty / $budgetQty) * 100))
                                         : 0;

            if (!isset($grouped[$pid])) {
                $grouped[$pid] = ['project_name' => $pname, 'work_types' => []];
            }
            if (!isset($grouped[$pid]['work_types'][$wname])) {
                $grouped[$pid]['work_types'][$wname] = [];
            }
            $grouped[$pid]['work_types'][$wname][] = $row;
        }

        return $grouped;
    }
}
