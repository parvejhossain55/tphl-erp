<?php

class Con_Material extends Model
{
    protected $table = 'con_materials';

    public function getAll()
    {
        $sql = "SELECT m.*,
                       u.full_name    AS created_by_name,
                       COALESCE(c.name, 'Uncategorized') AS category_name,
                       COALESCE(c.total_lead_days, 0) AS total_lead_days,
                       c.pr_to_qtn_days,
                       c.cs_analysis_days,
                       c.approval_days,
                       c.wo_po_days,
                       c.adv_payment_days,
                       c.transport_days
                FROM {$this->table} m
                LEFT JOIN users u ON u.id = m.created_by
                LEFT JOIN con_material_categories c ON c.id = m.category_id
                ORDER BY c.name ASC, m.name ASC";
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
        $sql = "INSERT INTO {$this->table} (category_id, name, unit, min_stock_level, created_by)
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issdi',
            $data['category_id'],
            $data['name'],
            $data['unit'],
            $data['min_stock_level'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET category_id=?, name=?, unit=?, min_stock_level=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('issdi',
            $data['category_id'],
            $data['name'],
            $data['unit'],
            $data['min_stock_level'],
            $id
        );
        return $stmt->execute();
    }

    public function delete($id)
    {
        // Check if referenced in boq_items
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM boq_items WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        // Check if referenced in purchase_request_items
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM purchase_request_items WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        // Check if referenced in purchase_order_items
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM purchase_order_items WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        // Check if referenced in con_stocks
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM con_stocks WHERE material_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ((int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0) > 0) return false;

        try {
            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->bind_param('i', $id);
            return $stmt->execute();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Build WHERE clause and bound params for project material queries
     */
    protected function buildProjectMaterialWhere($filters = [], $search = null)
    {
        $where = " WHERE (p.deleted_at IS NULL OR p.id IS NULL)";
        $params = [];
        $types  = "";

        if (!empty($filters['project_id'])) {
            $where .= " AND p.id = ?";
            $params[] = (int)$filters['project_id'];
            $types   .= "i";
        }

        if (!empty($filters['material_id'])) {
            $matId = (int)$filters['material_id'];
            // Look up material name to match potential duplicate entries of the same material
            $matRes = $this->db->query("SELECT name FROM con_materials WHERE id = {$matId}");
            $matName = ($matRes && $r = $matRes->fetch_assoc()) ? $r['name'] : '';
            $cleanMat = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $matName));

            if (!empty($cleanMat)) {
                $where .= " AND (
                    m.id = ? 
                    OR m.name = ? 
                    OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(m.name, ' ', ''), '(', ''), ')', ''), '}', '')) = ?
                )";
                $params[] = $matId;
                $params[] = $matName;
                $params[] = $cleanMat;
                $types   .= "iss";
            } else {
                $where .= " AND m.id = ?";
                $params[] = $matId;
                $types   .= "i";
            }
        }

        if (!empty($filters['category_id'])) {
            $where .= " AND m.category_id = ?";
            $params[] = (int)$filters['category_id'];
            $types   .= "i";
        }

        if (!empty($filters['start_date'])) {
            $where .= " AND (DATE(po.created_at) >= ? OR DATE(pr.created_at) >= ?)";
            $params[] = $filters['start_date'];
            $params[] = $filters['start_date'];
            $types   .= "ss";
        }

        if (!empty($filters['end_date'])) {
            $where .= " AND (DATE(po.created_at) <= ? OR DATE(pr.created_at) <= ?)";
            $params[] = $filters['end_date'];
            $params[] = $filters['end_date'];
            $types   .= "ss";
        }

        if (!empty($search)) {
            $rawSearch = trim($search);
            $s = "%" . $rawSearch . "%";
            $cleanSearch = "%" . strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawSearch)) . "%";

            $where .= " AND (
                m.name LIKE ?
                OR LOWER(REPLACE(REPLACE(REPLACE(REPLACE(m.name, ' ', ''), '(', ''), ')', ''), '}', '')) LIKE ?
                OR p.name LIKE ?
                OR v.name LIKE ?
                OR pr_u.full_name LIKE ?
                OR CONCAT('PO-', po.id) LIKE ?
                OR CONCAT('PR-', pr.id) LIKE ?
                OR poi.id IN (
                    SELECT mri_s.purchase_order_item_id
                    FROM material_receiving_items mri_s
                    JOIN material_receiving mr_s ON mr_s.id = mri_s.receiving_id
                    LEFT JOIN users u_s ON u_s.id = mr_s.received_by
                    LEFT JOIN employees emp_s ON emp_s.user_id = u_s.id
                    WHERE emp_s.full_name LIKE ? OR emp_s.employee_id LIKE ? OR u_s.full_name LIKE ?
                )
            )";
            $params[] = $s;
            $params[] = $cleanSearch;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
            $types   .= "ssssssssss";
        }

        return ['where' => $where, 'params' => $params, 'types' => $types];
    }

    /**
     * Fast aggregated KPI summary statistics for Project Material Report
     */
    public function getProjectMaterialSummaryStats($filters = [], $search = null)
    {
        $clause = $this->buildProjectMaterialWhere($filters, $search);

        $sql = "SELECT 
            COUNT(poi.id) AS total_orders,
            COALESCE(SUM(COALESCE(poi.total_price, poi.quantity * poi.unit_price)), 0) AS total_order_value,
            COALESCE(SUM(poi.quantity), 0) AS total_order_qty,
            COUNT(CASE WHEN recv.total_received_qty > 0 THEN 1 END) AS received_count,
            COUNT(CASE WHEN recv.total_received_qty IS NULL OR recv.total_received_qty <= 0 THEN 1 END) AS not_received_count
        FROM purchase_order_items poi
        JOIN purchase_orders po ON po.id = poi.purchase_order_id
        JOIN purchase_requests pr ON pr.id = po.request_id
        LEFT JOIN projects p ON p.id = pr.project_id
        JOIN con_materials m ON m.id = poi.material_id
        LEFT JOIN con_material_categories cat ON cat.id = m.category_id
        LEFT JOIN vendors v ON v.id = COALESCE(poi.selected_vendor_id, po.vendor_id)
        LEFT JOIN users pr_u ON pr_u.id = pr.created_by
        LEFT JOIN (
            SELECT purchase_order_item_id, SUM(received_quantity) AS total_received_qty
            FROM material_receiving_items
            GROUP BY purchase_order_item_id
        ) recv ON recv.purchase_order_item_id = poi.id" . $clause['where'];

        if (!empty($clause['params'])) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($clause['types'], ...$clause['params']);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc() ?: [
                'total_orders'       => 0,
                'total_order_value'  => 0,
                'total_order_qty'    => 0,
                'received_count'     => 0,
                'not_received_count' => 0,
            ];
        }

        $res = $this->db->query($sql);
        return ($res ? $res->fetch_assoc() : [
            'total_orders'       => 0,
            'total_order_value'  => 0,
            'total_order_qty'    => 0,
            'received_count'     => 0,
            'not_received_count' => 0,
        ]);
    }

    /**
     * Get count of filtered project material records for server-side pagination
     */
    public function getProjectMaterialReportCount($filters = [], $search = null)
    {
        $clause = $this->buildProjectMaterialWhere($filters, $search);

        $sql = "SELECT COUNT(poi.id) AS total_count
        FROM purchase_order_items poi
        JOIN purchase_orders po ON po.id = poi.purchase_order_id
        JOIN purchase_requests pr ON pr.id = po.request_id
        LEFT JOIN projects p ON p.id = pr.project_id
        JOIN con_materials m ON m.id = poi.material_id
        LEFT JOIN con_material_categories cat ON cat.id = m.category_id
        LEFT JOIN vendors v ON v.id = COALESCE(poi.selected_vendor_id, po.vendor_id)
        LEFT JOIN users pr_u ON pr_u.id = pr.created_by" . $clause['where'];

        if (!empty($clause['params'])) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($clause['types'], ...$clause['params']);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            return (int)($res['total_count'] ?? 0);
        }

        $res = $this->db->query($sql);
        $row = $res ? $res->fetch_assoc() : null;
        return (int)($row['total_count'] ?? 0);
    }

    /**
     * Paginated fetch for Project Material Report with receiving details attached
     */
    public function getProjectMaterialReportPaginated($filters = [], $limit = 25, $offset = 0, $search = null, $orderCol = 0, $orderDir = 'DESC')
    {
        $clause = $this->buildProjectMaterialWhere($filters, $search);

        // Sorting mapping
        $allowedColumns = [
            0 => 'po.id',
            1 => 'p.name',
            2 => 'm.name',
            3 => 'v.name',
            4 => 'po.id',
            5 => 'pr.created_at',
            6 => 'poi.quantity',
            7 => 'poi.unit_price',
            8 => 'total_amount',
            9 => 'po.id',
            10 => 'poi.id',
        ];
        $colName = $allowedColumns[(int)$orderCol] ?? 'po.id';
        $direction = (strtoupper($orderDir) === 'ASC') ? 'ASC' : 'DESC';

        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        $sql = "SELECT 
            poi.id AS poi_id,
            po.id AS po_id,
            po.created_at AS po_created_at,
            po.status AS po_status,
            pr.id AS pr_id,
            pr.created_at AS pr_created_at,
            pr.created_by AS pr_created_by,
            pr_u.full_name AS requester_name,
            COALESCE(pri.required_date, pr.required_date) AS pr_required_date,
            p.id AS project_id,
            COALESCE(p.name, 'Common / Unassigned') AS project_name,
            m.id AS material_id,
            m.name AS material_name,
            m.unit AS material_unit,
            COALESCE(cat.id, 0) AS category_id,
            COALESCE(cat.name, 'Uncategorized') AS category_name,
            COALESCE(v.id, 0) AS vendor_id,
            COALESCE(v.name, 'Not Selected') AS vendor_name,
            poi.quantity AS order_quantity,
            poi.unit_price,
            COALESCE(poi.total_price, poi.quantity * poi.unit_price) AS total_amount,
            -- Approvers
            po.approved_by_pm, po.pm_approved_at, u_pm.full_name AS pm_approver_name,
            po.qc_approved_by_id, po.qc_approved_at, u_qc.full_name AS qc_approver_name,
            po.mgmt_approved_by_id, po.mgmt_approved_at, u_mgmt.full_name AS mgmt_approver_name,
            po.purchase_head_approved_by_id, po.purchase_head_approved_at, u_ph.full_name AS ph_approver_name,
            po.op_head_approved_by_id, po.op_head_approved_at, u_op.full_name AS op_approver_name,
            po.approved_by_se, po.se_approved_at, u_se.full_name AS se_approver_name
        FROM purchase_order_items poi
        JOIN purchase_orders po ON po.id = poi.purchase_order_id
        JOIN purchase_requests pr ON pr.id = po.request_id
        LEFT JOIN projects p ON p.id = pr.project_id
        JOIN con_materials m ON m.id = poi.material_id
        LEFT JOIN con_material_categories cat ON cat.id = m.category_id
        LEFT JOIN vendors v ON v.id = COALESCE(poi.selected_vendor_id, po.vendor_id)
        LEFT JOIN users pr_u ON pr_u.id = pr.created_by
        LEFT JOIN (
            SELECT request_id, material_id, MIN(required_date) AS required_date
            FROM purchase_request_items
            GROUP BY request_id, material_id
        ) pri ON pri.request_id = pr.id AND pri.material_id = poi.material_id
        LEFT JOIN users u_pm ON u_pm.id = po.approved_by_pm
        LEFT JOIN users u_qc ON u_qc.id = po.qc_approved_by_id
        LEFT JOIN users u_mgmt ON u_mgmt.id = po.mgmt_approved_by_id
        LEFT JOIN users u_ph ON u_ph.id = po.purchase_head_approved_by_id
        LEFT JOIN users u_op ON u_op.id = po.op_head_approved_by_id
        LEFT JOIN users u_se ON u_se.id = po.approved_by_se" .
        $clause['where'] .
        " ORDER BY {$colName} {$direction}, poi.id ASC LIMIT {$limit} OFFSET {$offset}";

        if (!empty($clause['params'])) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($clause['types'], ...$clause['params']);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $rows = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        if (empty($rows)) {
            return [];
        }

        // Attach receiving details in 1 fast batch query for the paginated slice
        $poiIds = array_column($rows, 'poi_id');
        $placeholders = implode(',', array_fill(0, count($poiIds), '?'));
        $types = str_repeat('i', count($poiIds));

        $recvSql = "SELECT 
            mri.purchase_order_item_id,
            SUM(mri.received_quantity) AS total_received_qty,
            GROUP_CONCAT(DISTINCT mri.status SEPARATOR ', ') AS receiving_status,
            GROUP_CONCAT(DISTINCT COALESCE(emp_rcv.full_name, u_rcv.full_name) SEPARATOR ', ') AS receiver_names,
            GROUP_CONCAT(DISTINCT COALESCE(emp_rcv.employee_id, u_rcv.employee_id, 'N/A') SEPARATOR ', ') AS receiver_emp_ids,
            MAX(mr.receiving_date) AS latest_received_date
        FROM material_receiving_items mri
        JOIN material_receiving mr ON mr.id = mri.receiving_id
        LEFT JOIN users u_rcv ON u_rcv.id = mr.received_by
        LEFT JOIN employees emp_rcv ON emp_rcv.user_id = u_rcv.id
        WHERE mri.purchase_order_item_id IN ($placeholders)
        GROUP BY mri.purchase_order_item_id";

        $stmtRecv = $this->db->prepare($recvSql);
        $stmtRecv->bind_param($types, ...$poiIds);
        $stmtRecv->execute();
        $recvRows = $stmtRecv->get_result()->fetch_all(MYSQLI_ASSOC);
        $recvMap = [];
        foreach ($recvRows as $r) {
            $recvMap[$r['purchase_order_item_id']] = $r;
        }

        foreach ($rows as &$row) {
            $pId = $row['poi_id'];
            $row['total_received_qty']  = $recvMap[$pId]['total_received_qty'] ?? 0;
            $row['receiving_status']   = $recvMap[$pId]['receiving_status'] ?? null;
            $row['receiver_names']     = $recvMap[$pId]['receiver_names'] ?? null;
            $row['receiver_emp_ids']   = $recvMap[$pId]['receiver_emp_ids'] ?? null;
            $row['latest_received_date'] = $recvMap[$pId]['latest_received_date'] ?? null;
        }
        unset($row);

        return $rows;
    }

    /**
     * Backward-compatible legacy full fetch for Project Material Report
     */
    public function getProjectMaterialReport($filters = [])
    {
        return $this->getProjectMaterialReportPaginated($filters, 10000, 0, null, 0, 'DESC');
    }
}
