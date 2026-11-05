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

public function countAll()
{
    $sql = "SELECT COUNT(*) as total FROM vendors";
    $result = $this->db->query($sql);
    $row = $result ? $result->fetch_assoc() : null;
    return $row ? (int)$row['total'] : 0;
}

public function getPaginated($limit, $offset)
{
    $sql = "SELECT 
                v.*, 
                u.full_name AS created_by_name,
                COUNT(DISTINCT po.id) AS total_orders,
                COUNT(DISTINCT CASE 
                    WHEN vp.penalty_status = 'Approved' THEN vp.id 
                END) AS penalty_count
            FROM vendors v
            LEFT JOIN users u ON u.id = v.created_by
            LEFT JOIN purchase_order_items poi ON poi.selected_vendor_id = v.id
            LEFT JOIN purchase_orders po ON po.id = poi.purchase_order_id
            LEFT JOIN vendor_penalties vp ON vp.vendor_id = v.id
            GROUP BY v.id
            ORDER BY v.name ASC
            LIMIT ? OFFSET ?";
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
        $companyName = $data['company_name'] ?? null;
        $sql = "INSERT INTO {$this->table} (name, company_name, phone, email, address, rating, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssssdsi',
            $data['name'],
            $companyName,
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
                       m.name AS material_name, m.unit, COALESCE(cat.name, 'Uncategorized') AS category_name, p.name AS project_name,
                       (SELECT payment_type FROM vendor_payment_requests vpr WHERE vpr.purchase_order_id = po.id ORDER BY vpr.id DESC LIMIT 1) AS payment_type,
                       (SELECT due_amount FROM vendor_payment_requests vpr WHERE vpr.purchase_order_id = po.id ORDER BY vpr.id DESC LIMIT 1) AS due_amount
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

    /**
     * Get KPI Summary Metrics for Vendor Ledger based on filters
     */
    public function getVendorLedgerKpis($filters = [])
    {
        // 1. PO Amount & PO Count
        $poWhere = ["1=1"];
        $poTypes = '';
        $poParams = [];

        if (!empty($filters['vendor_id'])) {
            $poWhere[] = "poi.selected_vendor_id = ?";
            $poTypes .= 'i';
            $poParams[] = (int)$filters['vendor_id'];
        }
        if (!empty($filters['project_id'])) {
            $poWhere[] = "pr.project_id = ?";
            $poTypes .= 'i';
            $poParams[] = (int)$filters['project_id'];
        }
        if (!empty($filters['start_date'])) {
            $poWhere[] = "DATE(COALESCE(po.order_date, po.created_at)) >= ?";
            $poTypes .= 's';
            $poParams[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $poWhere[] = "DATE(COALESCE(po.order_date, po.created_at)) <= ?";
            $poTypes .= 's';
            $poParams[] = $filters['end_date'];
        }
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $poWhere[] = "(v.name LIKE ? OR v.phone LIKE ? OR v.email LIKE ? OR po.id LIKE ?)";
            $poTypes .= 'ssss';
            $poParams[] = $search;
            $poParams[] = $search;
            $poParams[] = $search;
            $poParams[] = $search;
        }

        $poSql = "SELECT 
                    COUNT(DISTINCT po.id) as total_pos,
                    COUNT(DISTINCT poi.selected_vendor_id) as active_vendors_with_pos,
                    COALESCE(SUM(poi.quantity * poi.unit_price), 0) as total_po_amt
                  FROM purchase_order_items poi
                  JOIN purchase_orders po ON po.id = poi.purchase_order_id
                  LEFT JOIN purchase_requests pr ON pr.id = po.request_id
                  LEFT JOIN vendors v ON v.id = poi.selected_vendor_id
                  WHERE " . implode(' AND ', $poWhere);

        $stmt = $this->db->prepare($poSql);
        if (!empty($poParams)) {
            $stmt->bind_param($poTypes, ...$poParams);
        }
        $stmt->execute();
        $poResult = $stmt->get_result()->fetch_assoc();
        $totalPoAmt = (float)($poResult['total_po_amt'] ?? 0);
        $totalPos = (int)($poResult['total_pos'] ?? 0);

        // 2. Total Paid from CFT
        $cftWhere = ["cft.ref_type = 'vendor_payment'"];
        $cftTypes = '';
        $cftParams = [];

        if (!empty($filters['vendor_id'])) {
            $cftWhere[] = "vpr.vendor_id = ?";
            $cftTypes .= 'i';
            $cftParams[] = (int)$filters['vendor_id'];
        }
        if (!empty($filters['project_id'])) {
            $cftWhere[] = "(cft.project_id = ? OR vpr.project_id = ?)";
            $cftTypes .= 'ii';
            $cftParams[] = (int)$filters['project_id'];
            $cftParams[] = (int)$filters['project_id'];
        }
        if (!empty($filters['start_date'])) {
            $cftWhere[] = "DATE(cft.transaction_date) >= ?";
            $cftTypes .= 's';
            $cftParams[] = $filters['start_date'];
        }
        if (!empty($filters['end_date'])) {
            $cftWhere[] = "DATE(cft.transaction_date) <= ?";
            $cftTypes .= 's';
            $cftParams[] = $filters['end_date'];
        }
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $cftWhere[] = "(v.name LIKE ? OR v.phone LIKE ? OR v.email LIKE ? OR cft.reference_number LIKE ?)";
            $cftTypes .= 'ssss';
            $cftParams[] = $search;
            $cftParams[] = $search;
            $cftParams[] = $search;
            $cftParams[] = $search;
        }

        $cftSql = "SELECT 
                     COALESCE(SUM(cft.amount_paid), 0) as total_paid
                   FROM con_financial_transactions cft
                   JOIN vendor_payment_requests vpr ON vpr.id = cft.ref_id
                   LEFT JOIN vendors v ON v.id = vpr.vendor_id
                   WHERE " . implode(' AND ', $cftWhere);

        $stmtCft = $this->db->prepare($cftSql);
        if (!empty($cftParams)) {
            $stmtCft->bind_param($cftTypes, ...$cftParams);
        }
        $stmtCft->execute();
        $cftResult = $stmtCft->get_result()->fetch_assoc();
        $totalPaid = (float)($cftResult['total_paid'] ?? 0);

        // Active vendors count
        if (!empty($filters['vendor_id'])) {
            $totalVendors = 1;
        } else {
            $vCountQ = $this->db->query("SELECT COUNT(*) as cnt FROM vendors WHERE status = 'active'");
            $totalVendors = (int)($vCountQ->fetch_assoc()['cnt'] ?? 0);
        }

        $totalDue = max(0, $totalPoAmt - $totalPaid);

        return [
            'total_vendors'     => $totalVendors,
            'total_po_amount'   => $totalPoAmt,
            'total_paid_amount' => $totalPaid,
            'total_due_amount'  => $totalDue,
            'total_pos_count'   => $totalPos,
        ];
    }

    /**
     * Get Master Vendor Ledger Summary list (Paginated)
     */
    public function getVendorLedgerSummary($filters = [], $limit = 20, $offset = 0)
    {
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);

        // Subquery PO stats filter conditions
        $poConds = ["1=1"];
        if (!empty($filters['project_id'])) {
            $poConds[] = "pr.project_id = " . (int)$filters['project_id'];
        }
        if (!empty($filters['start_date'])) {
            $sDate = $this->db->real_escape_string($filters['start_date']);
            $poConds[] = "DATE(COALESCE(po.order_date, po.created_at)) >= '{$sDate}'";
        }
        if (!empty($filters['end_date'])) {
            $eDate = $this->db->real_escape_string($filters['end_date']);
            $poConds[] = "DATE(COALESCE(po.order_date, po.created_at)) <= '{$eDate}'";
        }
        $poWhereSql = implode(' AND ', $poConds);

        // Subquery Paid stats filter conditions
        $paidConds = ["cft.ref_type = 'vendor_payment'"];
        if (!empty($filters['project_id'])) {
            $pId = (int)$filters['project_id'];
            $paidConds[] = "(cft.project_id = {$pId} OR vpr.project_id = {$pId})";
        }
        if (!empty($filters['start_date'])) {
            $sDate = $this->db->real_escape_string($filters['start_date']);
            $paidConds[] = "DATE(cft.transaction_date) >= '{$sDate}'";
        }
        if (!empty($filters['end_date'])) {
            $eDate = $this->db->real_escape_string($filters['end_date']);
            $paidConds[] = "DATE(cft.transaction_date) <= '{$eDate}'";
        }
        $paidWhereSql = implode(' AND ', $paidConds);

        $mainWhere = ["1=1"];
        $types = '';
        $params = [];

        if (!empty($filters['vendor_id'])) {
            $mainWhere[] = "v.id = ?";
            $types .= 'i';
            $params[] = (int)$filters['vendor_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $mainWhere[] = "(v.name LIKE ? OR v.phone LIKE ? OR v.email LIKE ? OR v.address LIKE ?)";
            $types .= 'ssss';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['project_id'])) {
            // Only show vendors that have POs or payments in this project
            $mainWhere[] = "(po_stat.total_pos > 0 OR paid_stat.total_paid > 0)";
        }

        $whereClause = "WHERE " . implode(' AND ', $mainWhere);

        $sql = "
            SELECT 
                v.id,
                v.name,
                v.phone,
                v.email,
                v.address,
                v.status,
                v.rating,
                COALESCE(po_stat.total_pos, 0) AS total_pos,
                COALESCE(po_stat.total_po_amount, 0) AS total_po_amount,
                COALESCE(paid_stat.total_paid, 0) AS total_paid,
                GREATEST(0, COALESCE(po_stat.total_po_amount, 0) - COALESCE(paid_stat.total_paid, 0)) AS total_due,
                paid_stat.last_payment_date,
                po_stat.projects_summary
            FROM vendors v
            LEFT JOIN (
                SELECT 
                    poi.selected_vendor_id,
                    COUNT(DISTINCT po.id) AS total_pos,
                    COALESCE(SUM(poi.quantity * poi.unit_price), 0) AS total_po_amount,
                    GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') AS projects_summary
                FROM purchase_order_items poi
                JOIN purchase_orders po ON po.id = poi.purchase_order_id
                LEFT JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN projects p ON p.id = pr.project_id
                WHERE {$poWhereSql}
                GROUP BY poi.selected_vendor_id
            ) AS po_stat ON po_stat.selected_vendor_id = v.id
            LEFT JOIN (
                SELECT 
                    vpr.vendor_id,
                    COALESCE(SUM(cft.amount_paid), 0) AS total_paid,
                    MAX(cft.transaction_date) AS last_payment_date
                FROM con_financial_transactions cft
                JOIN vendor_payment_requests vpr ON vpr.id = cft.ref_id
                WHERE {$paidWhereSql}
                GROUP BY vpr.vendor_id
            ) AS paid_stat ON paid_stat.vendor_id = v.id
            {$whereClause}
            ORDER BY COALESCE(po_stat.total_po_amount, 0) DESC, v.name ASC
            LIMIT ? OFFSET ?
        ";

        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Count total vendors matching filters for pagination
     */
    public function getVendorLedgerCount($filters = [])
    {
        $poConds = ["1=1"];
        if (!empty($filters['project_id'])) {
            $poConds[] = "pr.project_id = " . (int)$filters['project_id'];
        }
        if (!empty($filters['start_date'])) {
            $sDate = $this->db->real_escape_string($filters['start_date']);
            $poConds[] = "DATE(COALESCE(po.order_date, po.created_at)) >= '{$sDate}'";
        }
        if (!empty($filters['end_date'])) {
            $eDate = $this->db->real_escape_string($filters['end_date']);
            $poConds[] = "DATE(COALESCE(po.order_date, po.created_at)) <= '{$eDate}'";
        }
        $poWhereSql = implode(' AND ', $poConds);

        $paidConds = ["cft.ref_type = 'vendor_payment'"];
        if (!empty($filters['project_id'])) {
            $pId = (int)$filters['project_id'];
            $paidConds[] = "(cft.project_id = {$pId} OR vpr.project_id = {$pId})";
        }
        if (!empty($filters['start_date'])) {
            $sDate = $this->db->real_escape_string($filters['start_date']);
            $paidConds[] = "DATE(cft.transaction_date) >= '{$sDate}'";
        }
        if (!empty($filters['end_date'])) {
            $eDate = $this->db->real_escape_string($filters['end_date']);
            $paidConds[] = "DATE(cft.transaction_date) <= '{$eDate}'";
        }
        $paidWhereSql = implode(' AND ', $paidConds);

        $mainWhere = ["1=1"];
        $types = '';
        $params = [];

        if (!empty($filters['vendor_id'])) {
            $mainWhere[] = "v.id = ?";
            $types .= 'i';
            $params[] = (int)$filters['vendor_id'];
        }

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $mainWhere[] = "(v.name LIKE ? OR v.phone LIKE ? OR v.email LIKE ? OR v.address LIKE ?)";
            $types .= 'ssss';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['project_id'])) {
            $mainWhere[] = "(po_stat.total_pos > 0 OR paid_stat.total_paid > 0)";
        }

        $whereClause = "WHERE " . implode(' AND ', $mainWhere);

        $sql = "
            SELECT COUNT(*) AS total
            FROM vendors v
            LEFT JOIN (
                SELECT 
                    poi.selected_vendor_id,
                    COUNT(DISTINCT po.id) AS total_pos
                FROM purchase_order_items poi
                JOIN purchase_orders po ON po.id = poi.purchase_order_id
                LEFT JOIN purchase_requests pr ON pr.id = po.request_id
                WHERE {$poWhereSql}
                GROUP BY poi.selected_vendor_id
            ) AS po_stat ON po_stat.selected_vendor_id = v.id
            LEFT JOIN (
                SELECT 
                    vpr.vendor_id,
                    COALESCE(SUM(cft.amount_paid), 0) AS total_paid
                FROM con_financial_transactions cft
                JOIN vendor_payment_requests vpr ON vpr.id = cft.ref_id
                WHERE {$paidWhereSql}
                GROUP BY vpr.vendor_id
            ) AS paid_stat ON paid_stat.vendor_id = v.id
            {$whereClause}
        ";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return (int)($res['total'] ?? 0);
    }

    /**
     * Get project-wise breakdown of POs and Payments for a single vendor
     */
    public function getVendorProjectBreakdown($vendorId, $filters = [])
    {
        $vendorId = (int)$vendorId;
        $projWhere = ["poi.selected_vendor_id = {$vendorId}"];
        if (!empty($filters['project_id'])) {
            $projWhere[] = "pr.project_id = " . (int)$filters['project_id'];
        }
        if (!empty($filters['start_date'])) {
            $sDate = $this->db->real_escape_string($filters['start_date']);
            $projWhere[] = "DATE(COALESCE(po.order_date, po.created_at)) >= '{$sDate}'";
        }
        if (!empty($filters['end_date'])) {
            $eDate = $this->db->real_escape_string($filters['end_date']);
            $projWhere[] = "DATE(COALESCE(po.order_date, po.created_at)) <= '{$eDate}'";
        }

        $sql = "
            SELECT 
                COALESCE(pr.project_id, 0) as project_id,
                COALESCE(p.name, 'General / Unassigned') as project_name,
                COUNT(DISTINCT po.id) as po_count,
                COALESCE(SUM(poi.quantity * poi.unit_price), 0) as total_po_amount
            FROM purchase_order_items poi
            JOIN purchase_orders po ON po.id = poi.purchase_order_id
            LEFT JOIN purchase_requests pr ON pr.id = po.request_id
            LEFT JOIN projects p ON p.id = pr.project_id
            WHERE " . implode(' AND ', $projWhere) . "
            GROUP BY pr.project_id
            ORDER BY total_po_amount DESC
        ";
        $res = $this->db->query($sql);
        $projects = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $pId = (int)$row['project_id'];
                
                // Query paid for this project & vendor
                $paidSql = "
                    SELECT 
                        COALESCE(SUM(cft.amount_paid), 0) as paid_amount,
                        MAX(cft.transaction_date) as last_payment_date
                    FROM con_financial_transactions cft
                    JOIN vendor_payment_requests vpr ON vpr.id = cft.ref_id
                    WHERE cft.ref_type = 'vendor_payment' 
                      AND vpr.vendor_id = {$vendorId}
                      AND (cft.project_id = {$pId} OR vpr.project_id = {$pId})
                ";
                $paidRes = $this->db->query($paidSql)->fetch_assoc();
                $paid = (float)($paidRes['paid_amount'] ?? 0);
                $poAmt = (float)$row['total_po_amount'];
                $due = max(0, $poAmt - $paid);
                $progress = $poAmt > 0 ? min(100, round(($paid / $poAmt) * 100, 1)) : ($paid > 0 ? 100 : 0);

                $row['paid_amount'] = $paid;
                $row['due_amount'] = $due;
                $row['progress_percent'] = $progress;
                $row['last_payment_date'] = $paidRes['last_payment_date'] ?? null;
                $projects[] = $row;
            }
        }
        return $projects;
    }

    /**
     * Get detailed chronological ledger statement (running balance) for a single vendor
     */
    public function getDetailedVendorLedger($vendorId, $filters = [])
    {
        $vendorId = (int)$vendorId;

        $poExtra = '';
        $cftExtra = '';

        if (!empty($filters['project_id'])) {
            $pId = (int)$filters['project_id'];
            $poExtra .= " AND pr.project_id = {$pId}";
            $cftExtra .= " AND (cft.project_id = {$pId} OR vpr.project_id = {$pId})";
        }
        if (!empty($filters['start_date'])) {
            $sDate = $this->db->real_escape_string($filters['start_date']);
            $poExtra .= " AND DATE(COALESCE(po.order_date, po.created_at)) >= '{$sDate}'";
            $cftExtra .= " AND DATE(cft.transaction_date) >= '{$sDate}'";
        }
        if (!empty($filters['end_date'])) {
            $eDate = $this->db->real_escape_string($filters['end_date']);
            $poExtra .= " AND DATE(COALESCE(po.order_date, po.created_at)) <= '{$eDate}'";
            $cftExtra .= " AND DATE(cft.transaction_date) <= '{$eDate}'";
        }

        $sql = "
            (
                SELECT 
                    COALESCE(po.order_date, DATE(po.created_at)) as tx_date,
                    po.created_at as created_date,
                    'PURCHASE_ORDER' as entry_type,
                    po.id as ref_id,
                    CONCAT('PO-', po.id) as ref_no,
                    COALESCE(p.name, 'N/A') as project_name,
                    COALESCE(GROUP_CONCAT(DISTINCT m.name SEPARATOR ', '), 'Purchase Order Items') as description,
                    COALESCE(SUM(poi.quantity * poi.unit_price), 0) as debit_amount,
                    0.00 as credit_amount,
                    po.status as status_badge,
                    CONCAT('/con-purchase-order/', po.id) as action_url
                FROM purchase_orders po
                JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
                LEFT JOIN purchase_requests pr ON pr.id = po.request_id
                LEFT JOIN projects p ON p.id = pr.project_id
                LEFT JOIN con_materials m ON m.id = poi.material_id
                WHERE poi.selected_vendor_id = {$vendorId}
                  {$poExtra}
                GROUP BY po.id
            )
            UNION ALL
            (
                SELECT 
                    DATE(cft.transaction_date) as tx_date,
                    cft.created_at as created_date,
                    'PAYMENT' as entry_type,
                    cft.id as ref_id,
                    COALESCE(NULLIF(cft.reference_number, ''), CONCAT('TXN-', cft.id)) as ref_no,
                    COALESCE(p.name, 'N/A') as project_name,
                    CONCAT(
                        COALESCE(cft.payment_method, 'Payment Disbursed'),
                        IF(cft.notes IS NOT NULL AND cft.notes != '', CONCAT(' - ', cft.notes), '')
                    ) as description,
                    0.00 as debit_amount,
                    cft.amount_paid as credit_amount,
                    'Disbursed' as status_badge,
                    CONCAT('/con-vendor-payment-request/', vpr.id) as action_url
                FROM con_financial_transactions cft
                JOIN vendor_payment_requests vpr ON vpr.id = cft.ref_id
                LEFT JOIN projects p ON p.id = cft.project_id
                WHERE cft.ref_type = 'vendor_payment'
                  AND vpr.vendor_id = {$vendorId}
                  {$cftExtra}
            )
            ORDER BY tx_date ASC, created_date ASC
        ";

        $res = $this->db->query($sql);
        $entries = [];
        $runningBalance = 0;
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $debit = (float)$row['debit_amount'];
                $credit = (float)$row['credit_amount'];
                $runningBalance += ($debit - $credit);
                $row['running_balance'] = $runningBalance;
                $entries[] = $row;
            }
        }
        return $entries;
    }
}


