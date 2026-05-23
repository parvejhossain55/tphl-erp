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
        if (!hasPermission('QC & Internal Audit') && $this->roleName !== 'qc & internal audit' && !in_array($this->userRole, [1, 3])) {
            // allow admins (1, 3) or QC & Internal Audit role
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
        
        // Pending
        $q1 = $this->db->query("SELECT COUNT(*) as c FROM cashout_requests WHERE status IN ('pending', 'submitted')");
        $stats['pending'] += $q1->fetch_assoc()['c'] ?? 0;
        
        $q2 = $this->db->query("SELECT COUNT(*) as c FROM vendor_payment_requests WHERE status IN ('pending', 'submitted')");
        $stats['pending'] += $q2->fetch_assoc()['c'] ?? 0;
        
        $q3 = $this->db->query("SELECT COUNT(*) as c FROM contractor_bill_requests WHERE status IN ('pending', 'submitted', 'verified')");
        $stats['pending'] += $q3->fetch_assoc()['c'] ?? 0;
        
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

        // Purchase Orders to verify
        $poPending = $this->db->query("SELECT COUNT(*) as c FROM purchase_orders WHERE status IN ('pending','submitted')");
        $stats['po_pending'] = $poPending ? ($poPending->fetch_assoc()['c'] ?? 0) : 0;

        // Monthly Advance Cash (this month)
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $monthCash = $this->db->query("SELECT COUNT(*) as cnt, COALESCE(SUM(amount),0) as total FROM cashout_requests WHERE created_at BETWEEN '$monthStart' AND '$monthEnd 23:59:59'");
        $monthRow = $monthCash->fetch_assoc();
        $stats['month_cashout_count'] = $monthRow['cnt'] ?? 0;
        $stats['month_cashout_total'] = $monthRow['total'] ?? 0;

        // Vendor Payments pending
        $vpPending = $this->db->query("SELECT COUNT(*) as c, COALESCE(SUM(invoice_amount),0) as total FROM vendor_payment_requests WHERE status IN ('pending','submitted','Draft','Submitted')");
        $vpRow = $vpPending->fetch_assoc();
        $stats['vendor_pending'] = $vpRow['c'] ?? 0;
        $stats['vendor_pending_total'] = $vpRow['total'] ?? 0;

        // Contractor Bills pending
        $cbPending = $this->db->query("SELECT COUNT(*) as c, COALESCE(SUM(bill_amount),0) as total FROM contractor_bill_requests WHERE status IN ('pending','submitted','verified','draft')");
        $cbRow = $cbPending->fetch_assoc();
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

        $this->view('qc-audit/dashboard', [
            'current_route' => 'qc-audit-dashboard',
            'page_title' => 'QC & Internal Audit Overview',
            'stats' => $stats
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

        $this->view('qc-audit/advanced-filtering', [
            'current_route' => 'qc-advanced-audit',
            'page_title' => 'Advanced Auditing',
            'projects' => $projects
        ]);
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
                status AS mgmt_status
            FROM cashout_requests
            UNION ALL
            SELECT 
                created_at AS request_date,
                'Vendor Payment' AS request_type,
                id AS ref_id,
                net_payable_amount AS amount,
                CASE WHEN qc_approved_by_id IS NOT NULL THEN 'QC Approved' ELSE 'Pending' END AS qc_status,
                status AS mgmt_status
            FROM vendor_payment_requests
            UNION ALL
            SELECT 
                created_at AS request_date,
                'Contractor Bill' AS request_type,
                id AS ref_id,
                bill_amount AS amount,
                CASE WHEN qc_approved_by_id IS NOT NULL THEN 'QC Approved' ELSE 'Pending' END AS qc_status,
                status AS mgmt_status
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
        // 1. Pending Purchase Orders (PM Approved, awaiting QC)
        $q1 = "SELECT po.id, po.created_at, p.name as project_name, 'Purchase Order' as type, po.status 
               FROM purchase_orders po
               JOIN purchase_requests pr ON pr.id = po.request_id
               LEFT JOIN projects p ON pr.project_id = p.id 
               WHERE po.status = 'PM Approved'";

        // 3. Pending Vendor Payment Requests
        $q3 = "SELECT v.id, v.created_at, p.name as project_name, 'Vendor Payment' as type, v.status 
               FROM vendor_payment_requests v 
               LEFT JOIN projects p ON v.project_id = p.id 
               WHERE v.status IN ('Submitted', 'pending')";

        // 4. Pending Contractor Bill Requests
        $q4 = "SELECT b.id, b.created_at, p.name as project_name, 'Contractor Bill' as type, b.status 
               FROM contractor_bill_requests b 
               LEFT JOIN projects p ON b.project_id = p.id 
               WHERE b.status IN ('Submitted', 'pending')";
               
        $query = "$q1 UNION ALL $q3 UNION ALL $q4 ORDER BY created_at DESC";
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
                if ($type === 'Purchase Order') $table = 'purchase_orders';
                if ($type === 'Vendor Payment') $table = 'vendor_payment_requests';
                if ($type === 'Contractor Bill') $table = 'contractor_bill_requests';

                if ($table) {
                    $qc_status = 'QC Approved';
                    if ($type === 'Purchase Order') $qc_status = 'Approved'; // PO uses Fully Approved logic

                    $q = "UPDATE $table SET status = ?, qc_approved_by_id = ?, qc_note = ?, qc_approved_at = NOW() WHERE id = ?";
                    $stmt = $this->db->prepare($q);
                    if ($stmt) {
                        $stmt->bind_param('sisi', $qc_status, $qc_id, $note, $id);
                        $stmt->execute();

                        // For Vendor Payments, also need to create approval_request for management
                        if ($type === 'Vendor Payment') {
                            $reqStmt = $this->db->query("SELECT * FROM vendor_payment_requests WHERE id = " . (int)$id);
                            $reqData = $reqStmt->fetch_assoc();
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
                                    'approve_role'   => 'management',
                                    'meta_data'      => null,
                                    'request_amount' => $reqData['net_payable'],
                                ]);
                            }
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
                    qc.full_name as qc_approver_name,
                    mgmt.full_name as mgmt_approver_name,
                    vn.name as vendor_name
                    FROM vendor_payment_requests v
                    LEFT JOIN users u ON v.created_by = u.id
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
                    qc.full_name as qc_approver_name,
                    mgmt.full_name as mgmt_approver_name,
                    c.name as contractor_name
                    FROM contractor_bill_requests b
                    LEFT JOIN users u ON b.created_by = u.id
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
