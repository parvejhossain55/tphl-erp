<?php

class QcAuditController extends Controller
{
    protected $db;
    protected $userId;
    protected $userRole;
    protected $roleName;

    public function __construct($db)
    {
        $this->db = $db;

        if (session_status() === PHP_SESSION_NONE) session_start();
        checkLogin();

        $this->userId = $_SESSION['user']['id'] ?? null;
        $this->userRole = $_SESSION['user']['role_id'] ?? null;
        $this->roleName = strtolower($_SESSION['user']['role_name'] ?? '');

        // Usually we check permission here, e.g. hasPermission('QC & Internal Audit') or role check
        if (!hasPermission('QC & Internal Audit') && !str_contains($this->roleName, 'qc & internal audit') && !in_array($this->userRole, [1, 3, 33, 24, 25])) {
            // allow admins (1, 3) or QC & Internal Audit roles
            setError("Unauthorized access.");
            header("Location: {$GLOBALS['base_url']}/home");
            exit;
        }
    }

    public function dashboard()
    {
        // Gather Pending QC approvals
        $stats = [
            'pending' => 0,
            'approved' => 0
        ];
        
        // Pending (Actionable for QC)
        $q1 = $this->db->query("SELECT COUNT(*) as c FROM cashout_requests WHERE status IN ('pending', 'submitted')");
        $stats['pending'] += $q1->fetch_assoc()['c'] ?? 0;
        
        $q2 = $this->db->query("SELECT COUNT(*) as c FROM vendor_payment_requests WHERE status IN ('pending', 'submitted', 'Submitted') AND purchase_head_approved_by_id IS NOT NULL");
        $stats['pending'] += $q2 ? ($q2->fetch_assoc()['c'] ?? 0) : 0;
        
        $q3 = $this->db->query("SELECT COUNT(*) as c FROM contractor_bill_requests WHERE status = 'verified' AND purchase_head_approved_by_id IS NOT NULL");
        $stats['pending'] += $q3 ? ($q3->fetch_assoc()['c'] ?? 0) : 0;
        
        // Approved (by QC)
        $a1 = $this->db->query("SELECT COUNT(*) as c FROM cashout_requests WHERE qc_approved_by_id IS NOT NULL");
        $stats['approved'] += $a1->fetch_assoc()['c'] ?? 0;
        
        $a2 = $this->db->query("SELECT COUNT(*) as c FROM vendor_payment_requests WHERE qc_approved_by_id IS NOT NULL");
        $stats['approved'] += $a2->fetch_assoc()['c'] ?? 0;
        
        $a3 = $this->db->query("SELECT COUNT(*) as c FROM contractor_bill_requests WHERE qc_approved_by_id IS NOT NULL");
        $stats['approved'] += $a3->fetch_assoc()['c'] ?? 0;

        // Today's Cashout (EOD Audit)
        $today = date('Y-m-d');
        $todayCashout = $this->db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(amount),0) as total FROM cashout_requests WHERE DATE(created_at) = '$today'");
        $todayRow = $todayCashout->fetch_assoc();
        $stats['today_cashout_count'] = $todayRow['cnt'] ?? 0;
        $stats['today_cashout_total'] = $todayRow['total'] ?? 0;

        // Today's Payments Disbursed
        $todayPay = $this->db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(amount),0) as total FROM cashout_payments WHERE DATE(created_at) = '$today'");
        $todayPayRow = $todayPay->fetch_assoc();
        $stats['today_payment_count'] = $todayPayRow['cnt'] ?? 0;
        $stats['today_payment_total'] = $todayPayRow['total'] ?? 0;

        // Material Requisitions pending verification
        $matPending = $this->db->query("SELECT COUNT(*) as c FROM purchase_requests WHERE status = 'pending'");
        $stats['material_pending'] = $matPending->fetch_assoc()['c'] ?? 0;

        // Purchase Orders to verify (2nd Approval - QC & Audit)
        $poPending = $this->db->query("SELECT COUNT(*) as c FROM purchase_orders WHERE qc_approved_by_id IS NULL AND (status != 'Cancelled' OR status IS NULL)");
        $stats['po_pending'] = $poPending ? ($poPending->fetch_assoc()['c'] ?? 0) : 0;

        // Monthly Advance Cash (this month)
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $monthCash = $this->db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(amount),0) as total FROM cashout_requests WHERE created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59'");
        $monthRow = $monthCash->fetch_assoc();
        $stats['month_cashout_count'] = $monthRow['cnt'] ?? 0;
        $stats['month_cashout_total'] = $monthRow['total'] ?? 0;

        // Vendor Payments pending
        $vpPending = $this->db->query("SELECT COUNT(*) as c, COALESCE(SUM(invoice_amount),0) as total FROM vendor_payment_requests WHERE status IN ('pending','submitted','Draft','Submitted') AND purchase_head_approved_by_id IS NOT NULL");
        $vpRow = $vpPending ? $vpPending->fetch_assoc() : [];
        $stats['vendor_pending'] = $vpRow['c'] ?? 0;
        $stats['vendor_pending_total'] = $vpRow['total'] ?? 0;

        // Contractor Bills pending
        $cbPending = $this->db->query("SELECT COUNT(*) as c, COALESCE(SUM(bill_amount),0) as total FROM contractor_bill_requests WHERE status = 'verified' AND purchase_head_approved_by_id IS NOT NULL");
        $cbRow = $cbPending ? $cbPending->fetch_assoc() : [];
        $stats['contractor_pending'] = $cbRow['c'] ?? 0;
        $stats['contractor_pending_total'] = $cbRow['total'] ?? 0;

        // Rejected requests (all types)
        $rejCount = 0;
        $r1 = $this->db->query("SELECT COUNT(*) as c FROM cashout_requests WHERE status = 'rejected'");
        $rejCount += $r1->fetch_assoc()['c'] ?? 0;
        $r2 = $this->db->query("SELECT COUNT(*) as c FROM vendor_payment_requests WHERE status IN ('rejected','Rejected')");
        $rejCount += $r2->fetch_assoc()['c'] ?? 0;
        $r3 = $this->db->query("SELECT COUNT(*) as c FROM contractor_bill_requests WHERE status = 'rejected'");
        $rejCount += $r3->fetch_assoc()['c'] ?? 0;
        $stats['rejected'] = $rejCount;

        // Fetch Tables for Dashboard
        $cashoutRequests = $this->db->query("
            SELECT cr.*, p.name as project_name, u.full_name as requested_by_name 
            FROM cashout_requests cr 
            LEFT JOIN projects p ON p.id = cr.project_id 
            LEFT JOIN users u ON u.id = cr.requested_by 
            WHERE cr.status IN ('pending', 'submitted') 
            ORDER BY cr.created_at DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);

        $vendorPayments = $this->db->query("
            SELECT vpr.*, p.name as project_name, v.name as vendor_name, u.full_name as requested_by_name 
            FROM vendor_payment_requests vpr 
            LEFT JOIN projects p ON p.id = vpr.project_id 
            LEFT JOIN vendors v ON v.id = vpr.vendor_id 
            LEFT JOIN users u ON u.id = vpr.created_by 
            WHERE vpr.status IN ('pending', 'submitted', 'Draft', 'Submitted') 
            ORDER BY vpr.created_at DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);
        
        $contractorBills = $this->db->query("
            SELECT cbr.*, p.name as project_name, u2.full_name as contractor_name, u.full_name as requested_by_name 
            FROM contractor_bill_requests cbr 
            LEFT JOIN projects p ON p.id = cbr.project_id 
            LEFT JOIN users u2 ON u2.id = cbr.operator_id 
            LEFT JOIN users u ON u.id = cbr.created_by 
            WHERE cbr.status IN ('pending', 'submitted', 'verified', 'draft') 
            ORDER BY cbr.created_at DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);

        $purchaseOrders = $this->db->query("
            SELECT po.*, p.name as project_name, v.name as vendor_name 
            FROM purchase_orders po 
            LEFT JOIN purchase_requests pr ON pr.id = po.request_id 
            LEFT JOIN projects p ON p.id = pr.project_id 
            LEFT JOIN vendors v ON v.id = po.vendor_id 
            WHERE po.qc_approved_by_id IS NULL AND (po.status != 'Cancelled' OR po.status IS NULL)
            ORDER BY po.id DESC LIMIT 10
        ")->fetch_all(MYSQLI_ASSOC);

        $this->view('qc-audit/dashboard', [
            'current_route' => 'qc-audit-dashboard',
            'page_title' => 'QC & Internal Audit Overview',
            'stats' => $stats,
            'cashoutRequests' => $cashoutRequests,
            'vendorPayments' => $vendorPayments,
            'contractorBills' => $contractorBills,
            'purchaseOrders' => $purchaseOrders
        ]);
    }

    public function advancedAudit()
    {
        $projects = [];
        $result = $this->db->query("SELECT id, name FROM projects ORDER BY name ASC");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $projects[] = $row;
            }
        }

        $vendors = [];
        $vRes = $this->db->query("SELECT id, name, company_name, phone FROM vendors WHERE status = 'active' OR status IS NULL ORDER BY name ASC");
        if ($vRes) {
            while ($vRow = $vRes->fetch_assoc()) {
                $vendors[] = $vRow;
            }
        }

        $contractors = [];
        $cRes = $this->db->query("SELECT id, name, trade, role, phone FROM contractors ORDER BY name ASC");
        if ($cRes) {
            while ($cRow = $cRes->fetch_assoc()) {
                $contractors[] = $cRow;
            }
        }

        $this->view('qc-audit/advanced-filtering', [
            'current_route' => 'qc-advanced-audit',
            'page_title' => 'Advanced Auditing',
            'projects' => $projects,
            'vendors' => $vendors,
            'contractors' => $contractors
        ]);
    }

    public function getAuditVendorPOs()
    {
        $vendorId  = !empty($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $status    = !empty($_GET['status']) ? trim($_GET['status']) : null;
        $search    = !empty($_GET['search']) ? trim($_GET['search']) : null;

        $where = ["1=1"];
        if ($vendorId) {
            $where[] = "po.vendor_id = $vendorId";
        }
        if ($projectId) {
            $where[] = "pr.project_id = $projectId";
        }
        if ($status) {
            $safeStatus = $this->db->real_escape_string($status);
            $where[] = "(po.status = '$safeStatus' OR vpr.status = '$safeStatus')";
        }
        if ($search) {
            $safeSearch = $this->db->real_escape_string($search);
            $where[] = "(v.name LIKE '%$safeSearch%' OR p.name LIKE '%$safeSearch%' OR po.id = '$safeSearch' OR poi_agg.materials_summary LIKE '%$safeSearch%')";
        }

        $whereSql = implode(' AND ', $where);

        $query = "SELECT 
            po.id as po_id,
            po.order_date,
            po.created_at,
            po.status as po_status,
            v.id as vendor_id,
            v.name as vendor_name,
            v.phone as vendor_phone,
            p.id as project_id,
            p.name as project_name,
            COALESCE(poi_agg.item_count, 0) as item_count,
            COALESCE(poi_agg.po_total_amount, 0) as po_total_amount,
            COALESCE(poi_agg.materials_summary, 'No items specified') as materials_summary,
            COALESCE(poi_agg.items_json, '[]') as items_json,
            vpr.id as vpr_id,
            vpr.status as vpr_status,
            vpr.invoice_number,
            vpr.invoice_amount,
            COALESCE(
                (SELECT SUM(cft.amount_paid) FROM con_financial_transactions cft WHERE cft.ref_id = vpr.id AND cft.ref_type = 'vendor_payment'),
                CASE WHEN vpr.status = 'Fully_Paid' THEN vpr.invoice_amount ELSE 0 END,
                0
            ) as paid_amount,
            COALESCE(
                vpr.due_amount,
                CASE WHEN vpr.status = 'Fully_Paid' THEN 0 
                     ELSE (COALESCE(poi_agg.po_total_amount, 0) - COALESCE((SELECT SUM(cft.amount_paid) FROM con_financial_transactions cft WHERE cft.ref_id = vpr.id AND cft.ref_type = 'vendor_payment'), 0)) 
                END,
                0
            ) as due_amount
        FROM purchase_orders po
        JOIN vendors v ON po.vendor_id = v.id
        LEFT JOIN purchase_requests pr ON pr.id = po.request_id
        LEFT JOIN projects p ON p.id = pr.project_id
        LEFT JOIN (
            SELECT 
                poi.purchase_order_id,
                COUNT(poi.id) as item_count,
                SUM(poi.total_price) as po_total_amount,
                GROUP_CONCAT(CONCAT(m.name, ' (', poi.quantity, ' ', COALESCE(m.unit, ''), ')') SEPARATOR ', ') as materials_summary,
                CONCAT('[', GROUP_CONCAT(
                    CONCAT('{\"name\":\"', REPLACE(REPLACE(m.name, '\"', '\\\"'), '\\\\', '\\\\\\\\'), '\",\"qty\":', poi.quantity, ',\"unit\":\"', COALESCE(m.unit, ''), '\",\"rate\":', poi.unit_price, ',\"total\":', poi.total_price, '}')
                    SEPARATOR ','
                ), ']') as items_json
            FROM purchase_order_items poi
            JOIN con_materials m ON poi.material_id = m.id
            GROUP BY poi.purchase_order_id
        ) poi_agg ON poi_agg.purchase_order_id = po.id
        LEFT JOIN vendor_payment_requests vpr ON vpr.purchase_order_id = po.id
        WHERE $whereSql
        ORDER BY po.id DESC";

        $result = $this->db->query($query);
        $data = [];
        $totalPOs = 0;
        $totalAmount = 0;
        $totalPaid = 0;
        $totalDue = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $totalPOs++;
                $totalAmount += (float)$row['po_total_amount'];
                $totalPaid   += (float)$row['paid_amount'];
                $totalDue    += (float)$row['due_amount'];
                $data[] = $row;
            }
        }

        // Materials breakdown for the selected filter / vendor
        $matBreakdownQuery = "SELECT 
            m.name as material_name,
            COALESCE(m.unit, '') as unit,
            SUM(poi.quantity) as total_quantity,
            SUM(poi.total_price) as total_amount,
            COUNT(DISTINCT po.id) as po_occurrences
        FROM purchase_orders po
        JOIN vendors v ON po.vendor_id = v.id
        LEFT JOIN purchase_requests pr ON pr.id = po.request_id
        LEFT JOIN projects p ON p.id = pr.project_id
        JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
        JOIN con_materials m ON poi.material_id = m.id
        LEFT JOIN vendor_payment_requests vpr ON vpr.purchase_order_id = po.id
        WHERE $whereSql
        GROUP BY m.name, m.unit
        ORDER BY total_amount DESC";

        $matRes = $this->db->query($matBreakdownQuery);
        $materialsBreakdown = [];
        if ($matRes) {
            while ($mRow = $matRes->fetch_assoc()) {
                $materialsBreakdown[] = $mRow;
            }
        }

        $summary = [
            'total_pos' => $totalPOs,
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_due' => max(0, $totalDue),
            'material_count' => count($materialsBreakdown),
            'materials_breakdown' => $materialsBreakdown
        ];

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'summary' => $summary
        ]);
        exit;
    }

    public function getAuditContractorBills()
    {
        $contractorId = !empty($_GET['contractor_id']) ? (int)$_GET['contractor_id'] : null;
        $projectId    = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $status       = !empty($_GET['status']) ? trim($_GET['status']) : null;
        $search       = !empty($_GET['search']) ? trim($_GET['search']) : null;

        $where = ["1=1"];
        if ($contractorId) {
            $where[] = "cbr.operator_id = $contractorId";
        }
        if ($projectId) {
            $where[] = "cbr.project_id = $projectId";
        }
        if ($status) {
            $safeStatus = $this->db->real_escape_string($status);
            $where[] = "cbr.status = '$safeStatus'";
        }
        if ($search) {
            $safeSearch = $this->db->real_escape_string($search);
            $where[] = "(c.name LIKE '%$safeSearch%' OR c.trade LIKE '%$safeSearch%' OR p.name LIKE '%$safeSearch%' OR cbr.id = '$safeSearch' OR cbr.work_description LIKE '%$safeSearch%')";
        }

        $whereSql = implode(' AND ', $where);

        $query = "SELECT 
            cbr.id as bill_id,
            cbr.bill_date,
            cbr.bill_type,
            cbr.work_description,
            cbr.status as bill_status,
            c.id as contractor_id,
            c.name as contractor_name,
            c.trade as contractor_trade,
            c.role as contractor_role,
            c.phone as contractor_phone,
            p.id as project_id,
            p.name as project_name,
            cbr.bill_amount,
            COALESCE(cbr.approved_amount, 0) as approved_amount,
            COALESCE(
                (SELECT SUM(cft.amount_paid) FROM con_financial_transactions cft WHERE cft.ref_id = cbr.id AND cft.ref_type = 'contractor_bill'),
                CASE WHEN cbr.status IN ('paid', 'fully_paid') THEN COALESCE(cbr.approved_amount, cbr.bill_amount) ELSE 0 END,
                0
            ) as paid_amount,
            COALESCE(
                cbr.due_amount,
                CASE WHEN cbr.status IN ('paid', 'fully_paid') THEN 0 
                     ELSE (COALESCE(cbr.approved_amount, cbr.bill_amount) - COALESCE((SELECT SUM(cft.amount_paid) FROM con_financial_transactions cft WHERE cft.ref_id = cbr.id AND cft.ref_type = 'contractor_bill'), 0)) 
                END,
                0
            ) as due_amount
        FROM contractor_bill_requests cbr
        JOIN contractors c ON cbr.operator_id = c.id
        LEFT JOIN projects p ON cbr.project_id = p.id
        WHERE $whereSql
        ORDER BY cbr.id DESC";

        $result = $this->db->query($query);
        $data = [];
        $totalBills = 0;
        $totalRequested = 0;
        $totalApproved = 0;
        $totalPaid = 0;
        $totalDue = 0;

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $totalBills++;
                $totalRequested += (float)$row['bill_amount'];
                $totalApproved  += (float)$row['approved_amount'];
                $totalPaid      += (float)$row['paid_amount'];
                $totalDue       += (float)$row['due_amount'];
                $data[] = $row;
            }
        }

        $summary = [
            'total_bills' => $totalBills,
            'total_requested' => $totalRequested,
            'total_approved' => $totalApproved,
            'total_paid' => $totalPaid,
            'total_due' => max(0, $totalDue)
        ];

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data,
            'summary' => $summary
        ]);
        exit;
    }

    public function getAuditMaterials()
    {
        $query = "SELECT 
                pr.created_at AS request_date,
                p.name AS project_name,
                pr.id AS requisition_id,
                m.name AS material_name,
                pri.quantity AS requested_quantity,
                COALESCE(s.current_quantity, 0) AS stock_available,
                pr.status
            FROM purchase_requests pr
            JOIN purchase_request_items pri ON pri.request_id = pr.id
            JOIN con_materials m ON pri.material_id = m.id
            LEFT JOIN projects p ON pr.project_id = p.id
            LEFT JOIN con_stocks s ON s.material_id = m.id AND s.warehouse_id = p.id 
            ORDER BY pr.created_at DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $data]);
        exit;
    }

    public function getAuditPurchases()
    {
        $query = "SELECT 
                po.created_at AS transaction_date,
                po.id AS transaction_id,
                p.name AS project_name,
                SUM(poi.total_price) AS total_amount,
                po.status
            FROM purchase_orders po
            JOIN purchase_requests pr ON po.request_id = pr.id
            JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
            LEFT JOIN projects p ON pr.project_id = p.id
            GROUP BY po.id
            ORDER BY po.created_at DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $data]);
        exit;
    }

    public function getAuditPayments()
    {
        $query = "SELECT 
                created_at AS request_date,
                'Cashout' AS request_type,
                id AS ref_id,
                amount AS amount,
                CASE WHEN qc_approved_by_id IS NOT NULL THEN 'QC Approved' ELSE 'Pending' END AS qc_status,
                status AS mgmt_status,
                COALESCE((SELECT SUM(amount) FROM cashout_payments WHERE cashout_request_id = cashout_requests.id), 0) as paid_amount
            FROM cashout_requests
            UNION ALL
            SELECT 
                created_at AS request_date,
                'Vendor Payment' AS request_type,
                id AS ref_id,
                net_payable_amount AS amount,
                CASE WHEN qc_approved_by_id IS NOT NULL THEN 'QC Approved' ELSE 'Pending' END AS qc_status,
                status AS mgmt_status,
                COALESCE((SELECT SUM(amount_paid) FROM con_financial_transactions WHERE ref_id = vendor_payment_requests.id AND ref_type = 'vendor_payment'), CASE WHEN status IN ('Fully_Paid', 'Paid') THEN invoice_amount ELSE 0 END, 0) as paid_amount
            FROM vendor_payment_requests
            UNION ALL
            SELECT 
                created_at AS request_date,
                'Contractor Bill' AS request_type,
                id AS ref_id,
                bill_amount AS amount,
                CASE WHEN qc_approved_by_id IS NOT NULL THEN 'QC Approved' ELSE 'Pending' END AS qc_status,
                status AS mgmt_status,
                COALESCE((SELECT SUM(amount_paid) FROM con_financial_transactions WHERE ref_id = contractor_bill_requests.id AND ref_type = 'contractor_bill'), CASE WHEN status IN ('paid', 'fully_paid') THEN bill_amount ELSE 0 END, 0) as paid_amount
            FROM contractor_bill_requests
            ORDER BY request_date DESC";
        
        $result = $this->db->query($query);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['data' => $data]);
        exit;
    }

    public function pendingRequests()
    {
        // 1. Pending Purchase Orders (awaiting QC)
        $q1 = "SELECT po.id, po.created_at, p.name as project_name, 'Purchase Order' as type, po.status, u.full_name as requester_name, 
               (SELECT SUM(total_price) FROM purchase_order_items WHERE purchase_order_id = po.id) as amount,
               1 as can_qc_approve, 'Ready for QC' as pending_stage_label
               FROM purchase_orders po
               JOIN purchase_requests pr ON pr.id = po.request_id
               LEFT JOIN projects p ON pr.project_id = p.id 
               LEFT JOIN users u ON po.created_by = u.id
               WHERE po.status = 'Issued'";

        // 2. Pending Cashout Requests
        $q2 = "SELECT c.id, c.created_at, p.name as project_name, 'Cashout Request' as type, c.status, u.full_name as requester_name, c.amount,
               1 as can_qc_approve, 'Ready for QC' as pending_stage_label
               FROM cashout_requests c 
               LEFT JOIN projects p ON c.project_id = p.id 
               LEFT JOIN users u ON c.requested_by = u.id
               WHERE c.status IN ('submitted', 'pending')";

        // 3. Pending Vendor Payment Requests
        $q3 = "SELECT v.id, v.created_at, p.name as project_name, 'Vendor Payment' as type, v.status, u.full_name as requester_name, v.net_payable as amount,
               CASE WHEN v.purchase_head_approved_by_id IS NOT NULL THEN 1 ELSE 0 END as can_qc_approve,
               CASE WHEN v.purchase_head_approved_by_id IS NOT NULL THEN 'Ready for QC' ELSE 'Pending Purchase Head' END as pending_stage_label
               FROM vendor_payment_requests v 
               LEFT JOIN projects p ON v.project_id = p.id 
               LEFT JOIN users u ON v.created_by = u.id
               WHERE v.status IN ('Submitted', 'pending')";

        // 4. Pending Contractor Bill Requests
        $q4 = "SELECT b.id, b.created_at, p.name as project_name, 'Contractor Bill' as type, b.status, u.full_name as requester_name, b.bill_amount as amount,
               CASE WHEN b.status = 'verified' AND b.purchase_head_approved_by_id IS NOT NULL THEN 1 ELSE 0 END as can_qc_approve,
               CASE WHEN b.status != 'verified' THEN 'Pending PM Verification'
                    WHEN b.purchase_head_approved_by_id IS NULL THEN 'Pending Purchase Head'
                    ELSE 'Ready for QC' END as pending_stage_label
               FROM contractor_bill_requests b 
               LEFT JOIN projects p ON b.project_id = p.id 
               LEFT JOIN users u ON b.created_by = u.id
               WHERE b.status IN ('Submitted', 'submitted', 'pending', 'verified')";
               
        $query = "$q1 UNION ALL $q2 UNION ALL $q3 UNION ALL $q4 ORDER BY created_at DESC";
        $result = $this->db->query($query);
        $requests = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
        }

        $this->view('qc-audit/pending-requests', [
            'current_route' => 'qc-requests',
            'page_title' => 'Pending QC Approvals',
            'requests' => $requests
        ]);
    }

    public function approveRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $type = $_POST['type'] ?? null;
            $note = $_POST['qc_note'] ?? '';
            $qc_id = $_SESSION['user']['id'] ?? 1;

            if ($id && $type) {
                $table = '';
                $qc_status = 'QC Approved'; // Default

                if ($type === 'Purchase Order') {
                    $table = 'purchase_orders';
                    $qc_status = 'Approved'; // Purchase Orders don't have QC Approved enum, so we can use 'PM Approved' or something else, but let's just use 'Approved' or keep what is valid. Actually we'll let it use 'Approved' maybe? Wait, I will use 'PM Approved' or whatever is allowed. Let me just use a switch.
                }
                if ($type === 'Vendor Payment') {
                    $table = 'vendor_payment_requests';
                    $qc_status = 'QC Approved';
                }
                if ($type === 'Contractor Bill') {
                    $table = 'contractor_bill_requests';
                    $qc_status = 'qc_approved';
                }
                if ($type === 'Cashout Request') {
                    $table = 'cashout_requests';
                    $qc_status = 'qc_approved';
                }

                if ($table) {
                    $isMgmtUser = in_array((int)$this->userRole, [1, 3], true);

                    if ($type === 'Vendor Payment') {
                        $vprRow = $this->db->query("SELECT * FROM vendor_payment_requests WHERE id = " . (int)$id)->fetch_assoc();
                        if ($vprRow && empty($vprRow['purchase_head_approved_by_id']) && !$isMgmtUser) {
                            setError("Sequential Approval Required: Head of Purchase (Role 17) must approve Vendor Payment #$id before QC & Internal Audit can approve.");
                            header("Location: " . $GLOBALS['base_url'] . "/qc-requests");
                            exit;
                        }
                        if ($vprRow && empty($vprRow['purchase_head_approved_by_id']) && $isMgmtUser) {
                            $escNote = $this->db->real_escape_string(($note ?: 'Approved') . ' (Management Override)');
                            $this->db->query("UPDATE vendor_payment_requests SET purchase_head_approved_by_id = " . (int)$qc_id . ", purchase_head_approved_at = NOW(), purchase_head_note = '{$escNote}' WHERE id = " . (int)$id);
                        }
                    } elseif ($type === 'Contractor Bill') {
                        $cbrRow = $this->db->query("SELECT * FROM contractor_bill_requests WHERE id = " . (int)$id)->fetch_assoc();
                        if ($cbrRow && ($cbrRow['status'] !== 'verified' || empty($cbrRow['purchase_head_approved_by_id'])) && !$isMgmtUser) {
                            setError("Sequential Approval Required: PM Verification and Head of Purchase (Role 17) approval must be completed for Contractor Bill #$id before QC & Internal Audit can approve.");
                            header("Location: " . $GLOBALS['base_url'] . "/qc-requests");
                            exit;
                        }
                        if ($cbrRow && $isMgmtUser) {
                            $escNote = $this->db->real_escape_string(($note ?: 'Approved') . ' (Management Override)');
                            if ($cbrRow['status'] !== 'verified') {
                                $this->db->query("UPDATE contractor_bill_requests SET verified_by = " . (int)$qc_id . ", verified_at = NOW(), verification_note = '{$escNote}' WHERE id = " . (int)$id);
                            }
                            if (empty($cbrRow['purchase_head_approved_by_id'])) {
                                $this->db->query("UPDATE contractor_bill_requests SET purchase_head_approved_by_id = " . (int)$qc_id . ", purchase_head_approved_at = NOW(), purchase_head_note = '{$escNote}' WHERE id = " . (int)$id);
                            }
                        }
                    }

                    $q = "UPDATE $table SET status = ?, qc_approved_by_id = ?, qc_note = ?, qc_approved_at = NOW() WHERE id = ?";
                    $stmt = $this->db->prepare($q);
                    if ($stmt) {
                        $stmt->bind_param('sisi', $qc_status, $qc_id, $note, $id);
                        $stmt->execute();

                        // For Vendor Payments & Contractor Bills, forward approval_request to Operation Head ('operations')
                        if ($type === 'Vendor Payment') {
                            $this->db->query("UPDATE vendor_payment_requests SET qc_verified = 1, qc_approved_amount = COALESCE(NULLIF(qc_approved_amount, 0), net_payable) WHERE id = " . (int)$id);
                            $checkApp = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'vendor_payment_requests' AND source_id = " . (int)$id)->fetch_assoc();
                            if ($checkApp) {
                                $this->db->query("UPDATE approval_requests SET status = 'pending', approve_role = 'operations' WHERE id = " . (int)$checkApp['id']);
                            } else {
                                $reqStmt = $this->db->query("SELECT * FROM vendor_payment_requests WHERE id = " . (int)$id);
                                $reqData = $reqStmt ? $reqStmt->fetch_assoc() : null;
                                if ($reqData) {
                                    require_once __DIR__ . '/../models/ApprovalRequest.php';
                                    $approvalModel = new \ApprovalRequest($this->db);
                                    $project = $this->db->query("SELECT name FROM projects WHERE id = " . (int)$reqData['project_id'])->fetch_assoc();
                                    $vendor  = $this->db->query("SELECT name FROM vendors WHERE id = " . (int)$reqData['vendor_id'])->fetch_assoc();

                                    $approvalModel->create([
                                        'department'     => 'construction',
                                        'request_type'   => 'vendor_payment',
                                        'title'          => 'Vendor Payment — ' . ($vendor['name'] ?? '') . ' | ' . ($project['name'] ?? ''),
                                        'source_table'   => 'vendor_payment_requests',
                                        'source_id'      => $id,
                                        'requested_days' => 0,
                                        'requested_by'   => $reqData['created_by'],
                                        'approve_role'   => 'operations',
                                        'meta_data'      => null,
                                        'request_amount' => $reqData['net_payable'],
                                    ]);
                                }
                            }
                        } elseif ($type === 'Contractor Bill') {
                            $this->db->query("UPDATE contractor_bill_requests SET approved_amount = COALESCE(NULLIF(approved_amount, 0), bill_amount), due_amount = COALESCE(NULLIF(due_amount, 0), bill_amount) WHERE id = " . (int)$id);
                            $this->db->query("UPDATE approval_requests SET status = 'pending', approve_role = 'operations' WHERE source_table = 'contractor_bill_requests' AND source_id = " . (int)$id);
                        }

                        $_SESSION['flash_success'] = "Request #$id successfully approved by QC!";
                    } else {
                        $_SESSION['flash_error'] = "Failed to prepare query: " . $this->db->error;
                    }
                }
            }
            header("Location: " . $GLOBALS['base_url'] . "/qc-requests");
            exit;
        }
    }
    public function getAuditTrail()
    {
        $type = $_GET['type'] ?? '';
        $id = (int)($_GET['id'] ?? 0);

        if (!$id || !$type) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            exit;
        }

        $trail = [];

        // --- CASHOUT ---
        if ($type === 'Cashout') {
            // 1. Request info
            $sql = "SELECT c.*, u.full_name as requester_name,
                    qc.full_name as qc_approver_name,
                    mgmt.full_name as mgmt_approver_name
                    FROM cashout_requests c
                    LEFT JOIN users u ON c.requested_by = u.id
                    LEFT JOIN users qc ON c.qc_approved_by_id = qc.id
                    LEFT JOIN users mgmt ON c.approved_by = mgmt.id
                    WHERE c.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($req) {
                $trail[] = [
                    'date' => $req['created_at'],
                    'action' => 'Request Created',
                    'details' => 'Cashout request submitted for TK.' . number_format($req['amount'], 2),
                    'user' => $req['requester_name'] ?? 'System',
                    'icon' => 'bi-plus-circle',
                    'color' => 'primary'
                ];

                if (!empty($req['qc_approved_at'])) {
                    $trail[] = [
                        'date' => $req['qc_approved_at'],
                        'action' => 'QC Approved',
                        'details' => !empty($req['qc_note']) ? 'Note: ' . $req['qc_note'] : 'Approved by QC & Internal Audit',
                        'user' => $req['qc_approver_name'] ?? 'QC Auditor',
                        'icon' => 'bi-shield-check',
                        'color' => 'warning'
                    ];
                }

                if (!empty($req['approved_at'])) {
                    $amt = !empty($req['approved_amount']) ? ' (TK.' . number_format($req['approved_amount'], 2) . ')' : '';
                    $trail[] = [
                        'date' => $req['approved_at'],
                        'action' => 'Management Approved' . $amt,
                        'details' => !empty($req['approval_note']) ? 'Note: ' . $req['approval_note'] : 'Approved by Management',
                        'user' => $req['mgmt_approver_name'] ?? 'Management',
                        'icon' => 'bi-check-circle',
                        'color' => 'success'
                    ];
                }

                if (!empty($req['rejected_at'])) {
                    $trail[] = [
                        'date' => $req['rejected_at'],
                        'action' => 'Rejected',
                        'details' => !empty($req['rejection_reason']) ? 'Reason: ' . $req['rejection_reason'] : 'Request rejected',
                        'user' => $req['mgmt_approver_name'] ?? 'Management',
                        'icon' => 'bi-x-circle',
                        'color' => 'danger'
                    ];
                }

                // Payments
                $paySql = "SELECT cp.*, u.full_name as paid_by_name 
                           FROM cashout_payments cp 
                           LEFT JOIN users u ON cp.created_by = u.id 
                           WHERE cp.cashout_request_id = ? ORDER BY cp.created_at ASC";
                $payStmt = $this->db->prepare($paySql);
                $payStmt->bind_param('i', $id);
                $payStmt->execute();
                $payments = $payStmt->get_result();
                while ($pay = $payments->fetch_assoc()) {
                    $trail[] = [
                        'date' => $pay['created_at'],
                        'action' => 'Payment Disbursed',
                        'details' => 'TK.' . number_format($pay['amount'], 2) . ' via ' . ucfirst($pay['payment_method'] ?? 'cash'),
                        'user' => $pay['paid_by_name'] ?? 'Accounts',
                        'icon' => 'bi-cash-stack',
                        'color' => 'info'
                    ];
                }
                $payStmt->close();
            }
        }

        // --- VENDOR PAYMENT ---
        elseif ($type === 'Vendor Payment') {
            $sql = "SELECT v.*, u.full_name as requester_name,
                    ptl.full_name as tl_approver_name,
                    ph.full_name as ph_approver_name,
                    qc.full_name as qc_approver_name,
                    mgmt.full_name as mgmt_approver_name,
                    vn.name as vendor_name
                    FROM vendor_payment_requests v
                    LEFT JOIN users u ON v.created_by = u.id
                    LEFT JOIN users ptl ON v.purchase_team_leader_by_id = ptl.id
                    LEFT JOIN users ph ON v.purchase_head_approved_by_id = ph.id
                    LEFT JOIN users qc ON v.qc_approved_by_id = qc.id
                    LEFT JOIN users mgmt ON v.approved_by = mgmt.id
                    LEFT JOIN vendors vn ON v.vendor_id = vn.id
                    WHERE v.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($req) {
                $trail[] = [
                    'date' => $req['created_at'],
                    'action' => 'Request Created',
                    'details' => 'Vendor payment for ' . ($req['vendor_name'] ?? 'Unknown') . ' — TK.' . number_format($req['invoice_amount'] ?? 0, 2),
                    'user' => $req['requester_name'] ?? 'System',
                    'icon' => 'bi-plus-circle',
                    'color' => 'primary'
                ];

                if (!empty($req['purchase_team_leader_at'])) {
                    $trail[] = [
                        'date' => $req['purchase_team_leader_at'],
                        'action' => 'Purchase Team Leader Reviewed',
                        'details' => !empty($req['purchase_team_leader_note']) ? 'Note: ' . $req['purchase_team_leader_note'] : 'Reviewed & forwarded by Purchase Team Leader',
                        'user' => $req['tl_approver_name'] ?? 'Purchase Team Leader',
                        'icon' => 'bi-person-check',
                        'color' => 'info'
                    ];
                }

                if (!empty($req['purchase_head_approved_at'])) {
                    $trail[] = [
                        'date' => $req['purchase_head_approved_at'],
                        'action' => 'Head of Purchase Approved',
                        'details' => !empty($req['purchase_head_note']) ? 'Note: ' . $req['purchase_head_note'] : 'Approved by Head of Purchase',
                        'user' => $req['ph_approver_name'] ?? 'Head of Purchase',
                        'icon' => 'bi-check2-square',
                        'color' => 'primary'
                    ];
                }

                if (!empty($req['qc_approved_at'])) {
                    $trail[] = [
                        'date' => $req['qc_approved_at'],
                        'action' => 'QC Approved',
                        'details' => !empty($req['qc_note']) ? 'Note: ' . $req['qc_note'] : 'Approved by QC & Internal Audit',
                        'user' => $req['qc_approver_name'] ?? 'QC Auditor',
                        'icon' => 'bi-shield-check',
                        'color' => 'warning'
                    ];
                }

                if (!empty($req['approved_at'])) {
                    $amt = !empty($req['approved_amount']) ? ' (TK.' . number_format($req['approved_amount'], 2) . ')' : '';
                    $trail[] = [
                        'date' => $req['approved_at'],
                        'action' => 'Management Approved' . $amt,
                        'details' => 'Approved by Management',
                        'user' => $req['mgmt_approver_name'] ?? 'Management',
                        'icon' => 'bi-check-circle',
                        'color' => 'success'
                    ];
                }

                // Financial transactions
                $paySql = "SELECT ft.*, u.full_name as paid_by_name 
                           FROM con_financial_transactions ft 
                           LEFT JOIN users u ON ft.created_by = u.id 
                           WHERE ft.ref_type = 'vendor_payment' AND ft.ref_id = ? ORDER BY ft.created_at ASC";
                $payStmt = $this->db->prepare($paySql);
                $payStmt->bind_param('i', $id);
                $payStmt->execute();
                $payments = $payStmt->get_result();
                while ($pay = $payments->fetch_assoc()) {
                    $trail[] = [
                        'date' => $pay['created_at'] ?? $pay['transaction_date'],
                        'action' => 'Payment Disbursed',
                        'details' => 'TK.' . number_format($pay['amount_paid'] ?? 0, 2) . ' via ' . ($pay['payment_method'] ?? 'Bank'),
                        'user' => $pay['paid_by_name'] ?? 'Accounts',
                        'icon' => 'bi-cash-stack',
                        'color' => 'info'
                    ];
                }
                $payStmt->close();
            }
        }

        // --- CONTRACTOR BILL ---
        elseif ($type === 'Contractor Bill') {
            $sql = "SELECT b.*, u.full_name as requester_name,
                    pm.full_name as pm_approver_name,
                    ptl.full_name as tl_approver_name,
                    ph.full_name as ph_approver_name,
                    qc.full_name as qc_approver_name,
                    mgmt.full_name as mgmt_approver_name,
                    c.name as contractor_name
                    FROM contractor_bill_requests b
                    LEFT JOIN users u ON b.created_by = u.id
                    LEFT JOIN users pm ON b.verified_by = pm.id
                    LEFT JOIN users ptl ON b.purchase_team_leader_by_id = ptl.id
                    LEFT JOIN users ph ON b.purchase_head_approved_by_id = ph.id
                    LEFT JOIN users qc ON b.qc_approved_by_id = qc.id
                    LEFT JOIN users mgmt ON b.approved_by = mgmt.id
                    LEFT JOIN contractors c ON b.operator_id = c.id
                    WHERE b.id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $req = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($req) {
                $trail[] = [
                    'date' => $req['created_at'],
                    'action' => 'Bill Submitted',
                    'details' => 'Contractor bill for ' . ($req['contractor_name'] ?? 'Unknown') . ' — TK.' . number_format($req['bill_amount'] ?? 0, 2),
                    'user' => $req['requester_name'] ?? 'System',
                    'icon' => 'bi-plus-circle',
                    'color' => 'primary'
                ];

                if (!empty($req['verified_at'])) {
                    $trail[] = [
                        'date' => $req['verified_at'],
                        'action' => 'PM / Dept Verified',
                        'details' => !empty($req['verification_note']) ? 'Note: ' . $req['verification_note'] : 'Verified by Project Manager',
                        'user' => $req['pm_approver_name'] ?? 'Project Manager',
                        'icon' => 'bi-person-check',
                        'color' => 'info'
                    ];
                }

                if (!empty($req['purchase_team_leader_at'])) {
                    $trail[] = [
                        'date' => $req['purchase_team_leader_at'],
                        'action' => 'Purchase Team Leader Reviewed',
                        'details' => !empty($req['purchase_team_leader_note']) ? 'Note: ' . $req['purchase_team_leader_note'] : 'Reviewed & forwarded by Purchase Team Leader',
                        'user' => $req['tl_approver_name'] ?? 'Purchase Team Leader',
                        'icon' => 'bi-person-check',
                        'color' => 'info'
                    ];
                }

                if (!empty($req['purchase_head_approved_at'])) {
                    $trail[] = [
                        'date' => $req['purchase_head_approved_at'],
                        'action' => 'Head of Purchase Approved',
                        'details' => !empty($req['purchase_head_note']) ? 'Note: ' . $req['purchase_head_note'] : 'Approved by Head of Purchase',
                        'user' => $req['ph_approver_name'] ?? 'Head of Purchase',
                        'icon' => 'bi-check2-square',
                        'color' => 'primary'
                    ];
                }

                if (!empty($req['qc_approved_at'])) {
                    $trail[] = [
                        'date' => $req['qc_approved_at'],
                        'action' => 'QC Approved',
                        'details' => !empty($req['verification_note']) ? 'Note: ' . $req['verification_note'] : 'Approved by QC & Internal Audit',
                        'user' => $req['qc_approver_name'] ?? 'QC Auditor',
                        'icon' => 'bi-shield-check',
                        'color' => 'warning'
                    ];
                }

                if (!empty($req['approved_at'])) {
                    $amt = !empty($req['approved_amount']) ? ' (TK.' . number_format($req['approved_amount'], 2) . ')' : '';
                    $trail[] = [
                        'date' => $req['approved_at'],
                        'action' => 'Management Approved' . $amt,
                        'details' => 'Approved by Management',
                        'user' => $req['mgmt_approver_name'] ?? 'Management',
                        'icon' => 'bi-check-circle',
                        'color' => 'success'
                    ];
                }

                // Financial transactions
                $paySql = "SELECT ft.*, u.full_name as paid_by_name 
                           FROM con_financial_transactions ft 
                           LEFT JOIN users u ON ft.created_by = u.id 
                           WHERE ft.ref_type = 'contractor_bill' AND ft.ref_id = ? ORDER BY ft.created_at ASC";
                $payStmt = $this->db->prepare($paySql);
                $payStmt->bind_param('i', $id);
                $payStmt->execute();
                $payments = $payStmt->get_result();
                while ($pay = $payments->fetch_assoc()) {
                    $trail[] = [
                        'date' => $pay['created_at'] ?? $pay['transaction_date'],
                        'action' => 'Payment Disbursed',
                        'details' => 'TK.' . number_format($pay['amount_paid'] ?? 0, 2) . ' via ' . ($pay['payment_method'] ?? 'Bank'),
                        'user' => $pay['paid_by_name'] ?? 'Accounts',
                        'icon' => 'bi-cash-stack',
                        'color' => 'info'
                    ];
                }
                $payStmt->close();
            }
        }

        // Also check approval_requests table for any extra records
        $arSql = "SELECT ar.*, u.full_name as approver_name 
                  FROM approval_requests ar 
                  LEFT JOIN users u ON ar.approved_by = u.id 
                  WHERE ar.source_id = ? AND ar.source_table = ?
                  ORDER BY ar.created_at ASC";
        $sourceTable = '';
        if ($type === 'Cashout') $sourceTable = 'cashout_requests';
        elseif ($type === 'Vendor Payment') $sourceTable = 'vendor_payment_requests';
        elseif ($type === 'Contractor Bill') $sourceTable = 'contractor_bill_requests';

        if ($sourceTable) {
            $arStmt = $this->db->prepare($arSql);
            $arStmt->bind_param('is', $id, $sourceTable);
            $arStmt->execute();
            $arResult = $arStmt->get_result();
            while ($ar = $arResult->fetch_assoc()) {
                // Only add if it provides new info not already captured
                if ($ar['status'] === 'pending') {
                    $trail[] = [
                        'date' => $ar['created_at'],
                        'action' => 'Approval Request Created',
                        'details' => 'Sent to ' . ($ar['approve_role'] ?? 'management') . ' for approval — TK.' . number_format($ar['request_amount'] ?? 0, 2),
                        'user' => 'System',
                        'icon' => 'bi-send',
                        'color' => 'secondary'
                    ];
                }
            }
            $arStmt->close();
        }

        // Sort by date
        usort($trail, function($a, $b) {
            return strtotime($a['date']) - strtotime($b['date']);
        });

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'trail' => $trail]);
        exit;
    }
}
