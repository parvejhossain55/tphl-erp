<?php
namespace Phase2;

use Controller;
use Con_PurchaseOrder;
use Con_PurchaseRequest;

class Con_PurchaseOrderController extends Controller
{
    protected $db;
    protected $poModel;
    protected $prModel;

    public function __construct($db)
    {
        $this->db      = $db;
        $this->poModel = new Con_PurchaseOrder($this->db);
        $this->prModel = new Con_PurchaseRequest($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);

        // Fetch projects for filter
        require_once __DIR__ . '/../../models/Project.php';
        $projectModel = new \Project($this->db);
        $projects = $projectModel->getAll();

        // Fetch material categories for filter
        require_once __DIR__ . '/../../models/Con_MaterialCategory.php';
        $categoryModel = new \Con_MaterialCategory($this->db);
        $categories    = $categoryModel->getAll();

        $orders = $this->poModel->getAll();

        // Determine filters
        $isApproverRole = in_array($currentRoleId, [17, 24, 20, 38]);
        $filter         = $_GET['filter'] ?? ($isApproverRole ? 'pending_my_approval' : 'all');
        $projectId      = $_GET['project_id'] ?? '';
        $categoryId     = $_GET['category_id'] ?? '';
        $paymentFilter  = $_GET['payment_filter'] ?? 'all';
        $creatorId      = $_GET['creator_id'] ?? '';
        $period         = $_GET['period'] ?? 'all';
        $startDate      = trim($_GET['start_date'] ?? '');
        $endDate        = trim($_GET['end_date'] ?? '');

        if ($period === 'today') {
            $startDate = date('Y-m-d');
            $endDate   = date('Y-m-d');
        } elseif ($period === '7d') {
            $startDate = date('Y-m-d', strtotime('-7 days'));
            $endDate   = date('Y-m-d');
        } elseif ($period === '30d') {
            $startDate = date('Y-m-d', strtotime('-30 days'));
            $endDate   = date('Y-m-d');
        } elseif ($period === 'month') {
            $startDate = date('Y-m-01');
            $endDate   = date('Y-m-t');
        } elseif ($period !== 'custom') {
            $startDate = '';
            $endDate   = '';
        }

        // Build unique creators list from POs
        $creatorsMap = [];
        foreach ($orders as $poRow) {
            $cid = (int)($poRow['created_by'] ?? 0);
            if ($cid > 0 && !isset($creatorsMap[$cid])) {
                $creatorsMap[$cid] = $poRow['created_by_name'] ?? ('User #' . $cid);
            }
        }
        asort($creatorsMap);

        $filteredOrders = [];
        foreach ($orders as $po) {
            // Project filter
            if ($projectId !== '' && (string)$po['project_id'] !== (string)$projectId) {
                continue;
            }

            // Creator filter
            if ($creatorId !== '' && (string)($po['created_by'] ?? '') !== (string)$creatorId) {
                continue;
            }

            // Period / Date Range filter
            if ($startDate !== '' || $endDate !== '') {
                $poDateRaw = !empty($po['order_date']) ? $po['order_date'] : ($po['created_at'] ?? '');
                $poDate = $poDateRaw !== '' ? date('Y-m-d', strtotime($poDateRaw)) : '';
                if ($poDate === '') {
                    continue;
                }
                if ($startDate !== '' && $poDate < $startDate) {
                    continue;
                }
                if ($endDate !== '' && $poDate > $endDate) {
                    continue;
                }
            }

            // Material Category filter
            if ($categoryId !== '') {
                $poCatIds = array_map('trim', explode(',', (string)($po['category_ids'] ?? '')));
                if (!in_array(trim((string)$categoryId), $poCatIds)) {
                    continue;
                }
            }

            // Vendor payment filter
            $hasVpr = !empty($po['vpr_id']);
            $isPaid = in_array($po['vpr_status'] ?? '', ['Fully_Paid', 'Paid']) || (($po['vpr_total_paid'] ?? 0) > 0 && ($po['vpr_due'] ?? 0) <= 0);

            if ($paymentFilter === 'unrequested' && $hasVpr) {
                continue;
            } elseif ($paymentFilter === 'requested' && (!$hasVpr || $isPaid)) {
                continue;
            } elseif ($paymentFilter === 'paid' && !$isPaid) {
                continue;
            }

            $creatorRole   = (int)($po['creator_role_id'] ?? 0);
            $phNeeded      = in_array($creatorRole, [23, 31]);
            $phApproved    = !empty($po['purchase_head_approved_by_id']);
            $qcApproved    = !empty($po['qc_approved_by_id']);
            $opApproved    = !empty($po['op_head_approved_by_id']);
            $bodNeeded     = ((float)($po['total_amount'] ?? 0) > 30000);
            $bodApproved   = !empty($po['bod_approved_by_id']);
            $isCancelled   = (($po['status'] ?? '') === 'Cancelled');

            $canApprovePH   = !$isCancelled && (in_array($currentRoleId, [17, 1, 3])) && $phNeeded && !$phApproved;
            $canApproveQC   = !$isCancelled && (in_array($currentRoleId, [24, 1, 3])) && (!$phNeeded || $phApproved) && !$qcApproved;
            $canApproveOP   = !$isCancelled && (in_array($currentRoleId, [20, 1, 3])) && $qcApproved && !$opApproved;
            $canApproveBOD  = !$isCancelled && (in_array($currentRoleId, [38, 1, 3])) && $opApproved && $bodNeeded && !$bodApproved;

            $iCanApprove = $canApprovePH || $canApproveQC || $canApproveOP || $canApproveBOD;
            $iHaveApproved = ($po['purchase_head_approved_by_id'] == $currentUserId) ||
                             ($po['qc_approved_by_id'] == $currentUserId) ||
                             ($po['op_head_approved_by_id'] == $currentUserId) ||
                             ($po['bod_approved_by_id'] == $currentUserId);

            if ($filter === 'pending_my_approval') {
                if ($iCanApprove) {
                    $filteredOrders[] = $po;
                }
            } elseif ($filter === 'pending_approval') {
                // Any approval pending (Issued, Purchase Head Approved, QC Approved, Operation Head Approved)
                if (in_array($po['status'], ['Issued', 'Purchase Head Approved', 'QC Approved', 'Operation Head Approved'])) {
                    $filteredOrders[] = $po;
                }
            } elseif ($filter === 'my_approved') {
                if ($iHaveApproved) {
                    $filteredOrders[] = $po;
                }
            } else {
                // 'all'
                $filteredOrders[] = $po;
            }
        }

        // Sorting:
        // 1. CSV uploads appear at the bottom
        // 2. Unrequested (vendor payment not requested / not made) appear at the TOP (Rank 0)
        // 3. Payment requested (in-progress) appear below unrequested (Rank 1)
        // 4. Payment complete appear below in-progress (Rank 2)
        // 5. Descending by PO ID within each rank
        usort($filteredOrders, function($a, $b) {
            $aCsv = (strpos(strtolower($a['upload_from'] ?? ''), 'csv') !== false) ? 1 : 0;
            $bCsv = (strpos(strtolower($b['upload_from'] ?? ''), 'csv') !== false) ? 1 : 0;
            if ($aCsv !== $bCsv) {
                return $aCsv - $bCsv;
            }

            $aHasReq = !empty($a['vpr_id']);
            $bHasReq = !empty($b['vpr_id']);

            $aPaid = in_array($a['vpr_status'] ?? '', ['Fully_Paid', 'Paid']) || (($a['vpr_total_paid'] ?? 0) > 0 && ($a['vpr_due'] ?? 0) <= 0);
            $bPaid = in_array($b['vpr_status'] ?? '', ['Fully_Paid', 'Paid']) || (($b['vpr_total_paid'] ?? 0) > 0 && ($b['vpr_due'] ?? 0) <= 0);

            $aRank = !$aHasReq ? 0 : ($aPaid ? 2 : 1);
            $bRank = !$bHasReq ? 0 : ($bPaid ? 2 : 1);

            if ($aRank !== $bRank) {
                return $aRank - $bRank;
            }

            return $b['id'] <=> $a['id']; // Descending by ID for same rank
        });

        // Compute summary card stats from filtered orders
        $summaryStats = [
            'total_orders'           => count($filteredOrders),
            'total_qty'              => 0.0,
            'received_qty'           => 0.0,
            'pending_qty'            => 0.0,
            'total_amount'           => 0.0,
            'total_paid'             => 0.0,
            'total_due'              => 0.0,
            'pending_approval_count' => 0,
        ];
        foreach ($filteredOrders as $fpo) {
            $qty = (float)($fpo['total_qty'] ?? 0);
            $rec = (float)($fpo['received_qty'] ?? 0);
            $amt = (float)($fpo['total_amount'] ?? 0);
            $paid = (float)($fpo['vpr_total_paid'] ?? 0);

            $summaryStats['total_qty']    += $qty;
            $summaryStats['received_qty'] += $rec;
            $summaryStats['total_amount'] += $amt;
            $summaryStats['total_paid']   += $paid;
            $summaryStats['total_due']    += max(0, $amt - $paid);
            if (in_array($fpo['status'] ?? '', ['Issued', 'Purchase Head Approved', 'QC Approved', 'Operation Head Approved'], true)) {
                $summaryStats['pending_approval_count']++;
            }
        }
        $summaryStats['pending_qty'] = max(0, $summaryStats['total_qty'] - $summaryStats['received_qty']);

        $this->view('phase2/purchase-orders/purchase-order-lists', [
            'current_route'     => 'con-purchase-orders',
            'page_title'        => 'Purchase Orders',
            'orders'            => $filteredOrders,
            'currentFilter'     => $filter,
            'projects'          => $projects,
            'currentProjectId'  => $projectId,
            'categories'        => $categories,
            'currentCategoryId' => $categoryId,
            'paymentFilter'     => $paymentFilter,
            'creators'          => $creatorsMap,
            'currentCreatorId'  => $creatorId,
            'currentPeriod'     => $period,
            'startDate'         => $startDate,
            'endDate'           => $endDate,
            'summaryStats'      => $summaryStats,
            'isApproverRole'    => $isApproverRole
        ]);
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $po = $this->poModel->find($id);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentUserId = (int)($_SESSION['user']['id']   ?? 0);
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $createdBy     = (int)($po['created_by'] ?? 0);

        // Role guard & sequence logic
        $creatorRole   = (int)($po['creator_role_id'] ?? 0);
        $phNeeded      = in_array($creatorRole, [23, 31]);
        $phApproved    = !empty($po['purchase_head_approved_by_id']);
        $qcApproved    = !empty($po['qc_approved_by_id']);
        $opApproved    = !empty($po['op_head_approved_by_id']);
        $bodNeeded     = ((float)($po['total_amount'] ?? 0) > 30000);
        $bodApproved   = !empty($po['bod_approved_by_id']);
        $alreadyFinalStatus = in_array($po['status'] ?? '', ['Approved', 'Partially Received', 'Fully Received'], true);
        $fullyApproved = $alreadyFinalStatus || (($phNeeded ? $phApproved : true) && $qcApproved && $opApproved && ($bodNeeded ? $bodApproved : true));

        $isCancelled    = ($po['status'] === 'Cancelled');
        $canApprovePH   = !$isCancelled && (in_array($currentRoleId, [17, 1, 3])) && $phNeeded && !$phApproved;
        $canApproveQC   = !$isCancelled && (in_array($currentRoleId, [24, 1, 3])) && (!$phNeeded || $phApproved) && !$qcApproved;
        $canApproveOP   = !$isCancelled && (in_array($currentRoleId, [20, 1, 3])) && $qcApproved && !$opApproved;
        $canApproveBOD  = !$isCancelled && (in_array($currentRoleId, [38, 1, 3])) && $opApproved && $bodNeeded && !$bodApproved;

        // Management checks are mapped to Operation Head parameters for compatibility
        $mgmtApproved   = $opApproved;
        $canApproveMgmt = $canApproveOP;

        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        $isCreator = ($currentUserId == $createdBy);
        $isAdminOverride = in_array($currentRoleId, [1, 3], true);
        $isManagement = (strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false || in_array($currentRoleId, [1, 3, 6, 30]));
        $isNotApproved = empty($po['purchase_head_approved_by_id'])
            && empty($po['qc_approved_by_id'])
            && empty($po['op_head_approved_by_id'])
            && empty($po['bod_approved_by_id'])
            && !in_array($po['status'] ?? '', ['Approved', 'Partially Received', 'Fully Received', 'Cancelled'], true);
        require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        $poRevSvc       = new \RequisitionRevisionService($this->db);
        $latestRevision = $poRevSvc->getLatestActiveOrResubmitted('purchase_orders', (int)$id);
        $isPoRevOpen    = ($latestRevision && ($latestRevision['status'] ?? '') === 'open');

        $canDeletePO = ($isCreator || $isManagement) && $isNotApproved && $po['status'] !== 'Cancelled';
        $canEditPO   = !$isCancelled && ($isNotApproved || $isPoRevOpen) && ($isCreator || $isAdminOverride);

        // Creator can edit item quantity BEFORE anyone approves OR when sent back for review; active approvers or Admin can adjust during approval
        $canEditItemQty = !$isCancelled && !$alreadyFinalStatus && (
            $isAdminOverride
            || $canApprovePH
            || $canApproveQC
            || $canApproveOP
            || $canApproveBOD
            || ($isCreator && ($isNotApproved || $isPoRevOpen))
        );

        $items        = $this->poModel->getItems($id);
        $pendingItems = $this->poModel->getPendingItems($id);
        $qtyLogs      = $this->poModel->getQtyLogs($id);

        // Fetch the BOQ linked to this PO (via PR.bom_id)
        $linkedBoq = $this->poModel->getLinkedBoq($id);

        // Check if any material receiving already exists for this PO
        $hasReceiving = false;
        $existingReceivingQuery = $this->db->prepare("SELECT COUNT(*) as count FROM material_receiving WHERE purchase_order_id = ?");
        if ($existingReceivingQuery) {
            $existingReceivingQuery->bind_param('i', $id);
            $existingReceivingQuery->execute();
            $hasReceiving = ($existingReceivingQuery->get_result()->fetch_assoc()['count'] ?? 0) > 0;
            $existingReceivingQuery->close();
        }

        // Fetch vendor selection data (attachment & prepared by)
        $vendorSelectionData = null;
        if (!empty($po['request_id']) && !empty($po['vendor_id'])) {
            $cstQuery = $this->db->prepare("
                SELECT q.attachment, q.drive_link, qc.prepared_by, u.full_name AS selector_name
                FROM quotations q
                LEFT JOIN quotation_comparisons qc ON qc.request_id = q.request_id
                LEFT JOIN users u ON u.id = qc.prepared_by
                WHERE q.request_id = ? AND q.vendor_id = ?
                LIMIT 1
            ");
            if ($cstQuery) {
                $cstQuery->bind_param('ii', $po['request_id'], $po['vendor_id']);
                $cstQuery->execute();
                $vendorSelectionData = $cstQuery->get_result()->fetch_assoc();
                $cstQuery->close();
            }
        }

        // Fetch the Comparative Statement (CST) matrix for this PR
        require_once __DIR__ . '/../../models/Con_Quotation.php';
        $quotModel = new \Con_Quotation($this->db);
        $cstMatrix = $quotModel->buildComparison($po['request_id']);

        // Fetch Negotiation Notes
        $notesQuery = $this->db->prepare("
            SELECT n.*, u.full_name AS author_name, u.role_id AS author_role
            FROM po_negotiation_notes n
            JOIN users u ON u.id = n.user_id
            WHERE n.purchase_order_id = ?
            ORDER BY n.created_at DESC
        ");

        if (!$notesQuery) {
            // This will print out exactly which column or table is missing
            die("Database Error in fetching negotiation notes: " . $this->db->error);
        }

        $notesQuery->bind_param('i', $id);
        $notesQuery->execute();
        $negotiationNotes = $notesQuery->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch vendor payment requests & payment transactions for this PO
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
        $canProcessPayment = (
            in_array($currentRoleId, [1, 3, 33, 6, 30])
            || strpos($roleName, 'admin') !== false
            || strpos($roleName, 'management') !== false
            || strpos($roleName, 'accounts') !== false
        );

        $poPaymentsStmt = $this->db->prepare("
            SELECT vpr.*, u.full_name AS created_by_name, ab.full_name AS approved_by_name, qc.full_name AS qc_approved_by_name,
                   COALESCE((SELECT SUM(cft.amount_paid) FROM con_financial_transactions cft WHERE cft.ref_id = vpr.id AND cft.ref_type = 'vendor_payment'), 0) AS total_paid
            FROM vendor_payment_requests vpr
            LEFT JOIN users u ON u.id = vpr.created_by
            LEFT JOIN users ab ON ab.id = vpr.approved_by
            LEFT JOIN users qc ON qc.id = vpr.qc_approved_by_id
            WHERE vpr.purchase_order_id = ?
            ORDER BY vpr.id DESC
        ");
        $poPayments = [];
        if ($poPaymentsStmt) {
            $poPaymentsStmt->bind_param('i', $id);
            $poPaymentsStmt->execute();
            $poPayments = $poPaymentsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $poTotalValue = 0;
        foreach ($items as $itm) {
            $poTotalValue += (float)($itm['quantity'] * $itm['unit_price']);
        }
        $poTotalPaid = 0;
        foreach ($poPayments as $vpr) {
            $poTotalPaid += (float)($vpr['total_paid'] ?? 0);
        }
        $poDueBalance = max(0, $poTotalValue - $poTotalPaid);

        // Fetch Selected Vendor Full Details
        $selectedVendor = null;
        $targetVendorId = !empty($po['vendor_id']) ? (int)$po['vendor_id'] : (!empty($items[0]['selected_vendor_id']) ? (int)$items[0]['selected_vendor_id'] : 0);
        if ($targetVendorId > 0) {
            $vStmt = $this->db->prepare("SELECT id, name, phone, email, address, company_name FROM vendors WHERE id = ?");
            if ($vStmt) {
                $vStmt->bind_param('i', $targetVendorId);
                $vStmt->execute();
                $selectedVendor = $vStmt->get_result()->fetch_assoc();
            }
        }

        // Fetch All Quotations & CST data for this PR
        $quotationsList = [];
        $cstInfo        = null;
        $prAttachment   = null;

        if (!empty($po['request_id'])) {
            $prId = (int)$po['request_id'];

            // Fetch PR details & attachment
            $prDetails = null;
            $prStmt = $this->db->prepare("
                SELECT pr.*, u.full_name AS requester_name, p.name AS pr_project_name 
                FROM purchase_requests pr 
                LEFT JOIN users u ON u.id = pr.created_by 
                LEFT JOIN projects p ON p.id = pr.project_id 
                WHERE pr.id = ?
            ");
            if ($prStmt) {
                $prStmt->bind_param('i', $prId);
                $prStmt->execute();
                $prDetails = $prStmt->get_result()->fetch_assoc();
                $prAttachment = $prDetails['attachment'] ?? null;
            }

            // Fetch CST Comparison Header
            $cstStmt = $this->db->prepare("
                SELECT qc.*, u.full_name AS prepared_by_name 
                FROM quotation_comparisons qc 
                LEFT JOIN users u ON u.id = qc.prepared_by 
                WHERE qc.request_id = ?
                LIMIT 1
            ");
            if ($cstStmt) {
                $cstStmt->bind_param('i', $prId);
                $cstStmt->execute();
                $cstInfo = $cstStmt->get_result()->fetch_assoc();
            }

            // Fetch all Quotations for this PR with full vendor info & attachments
            $qStmt = $this->db->prepare("
                SELECT q.*, 
                       v.name AS vendor_name, 
                       v.phone AS vendor_phone, 
                       v.email AS vendor_email, 
                       v.address AS vendor_address, 
                       v.company_name AS vendor_company_name,
                       u.full_name AS quoted_by_name,
                       (SELECT SUM(qi.quantity * qi.unit_price) FROM quotation_items qi WHERE qi.quotation_id = q.id) AS total_quoted_amount
                FROM quotations q 
                LEFT JOIN vendors v ON v.id = q.vendor_id 
                LEFT JOIN users u ON u.id = q.created_by
                WHERE q.request_id = ?
                ORDER BY q.id ASC
            ");
            if ($qStmt) {
                $qStmt->bind_param('i', $prId);
                $qStmt->execute();
                $quotationsList = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }

        $siteSettings = null;
        $sQuery = $this->db->query("SELECT * FROM site_settings LIMIT 1");
        if ($sQuery) {
            $siteSettings = $sQuery->fetch_assoc();
        }

        require_once __DIR__ . '/../../models/Con_MaterialReturn.php';
        $returnModel = new \Con_MaterialReturn($this->db);
        $poReturns = $returnModel->getByPurchaseOrder($id);

        $this->view('phase2/purchase-orders/show-purchase-order', [
            'current_route'       => 'con-purchase-orders',
            'page_title'          => 'Purchase Order #' . $id,
            'po'                  => $po,
            'items'               => $items,
            'pendingItems'        => $pendingItems,
            'phNeeded'            => $phNeeded,
            'phApproved'          => $phApproved,
            'qcApproved'          => $qcApproved,
            'opApproved'          => $opApproved,
            'fullyApproved'       => $fullyApproved,
            'canApprovePH'        => $canApprovePH,
            'canApproveQC'        => $canApproveQC,
            'canApproveOP'        => $canApproveOP,
            'bodNeeded'           => $bodNeeded,
            'bodApproved'         => $bodApproved,
            'canApproveBOD'       => $canApproveBOD,
            'mgmtApproved'        => $mgmtApproved,
            'canApproveMgmt'      => $canApproveMgmt,
            'canDeletePO'         => $canDeletePO,
            'canEditPO'           => $canEditPO,
            'latestRevision'      => $latestRevision,
            'currentRoleId'       => $currentRoleId,
            'linkedBoq'           => $linkedBoq,
            'hasReceiving'        => $hasReceiving,
            'vendorSelectionData' => $vendorSelectionData,
            'cstMatrix'           => $cstMatrix,
            'negotiationNotes'    => $negotiationNotes,
            'canProcessPayment'   => $canProcessPayment,
            'poPayments'          => $poPayments,
            'poTotalValue'        => $poTotalValue,
            'poTotalPaid'         => $poTotalPaid,
            'poDueBalance'        => $poDueBalance,
            'selectedVendor'      => $selectedVendor,
            'quotationsList'      => $quotationsList,
            'cstInfo'             => $cstInfo,
            'prAttachment'        => $prAttachment,
            'prDetails'           => $prDetails,
            'siteSettings'        => $siteSettings,
            'qtyLogs'             => $qtyLogs,
            'canEditItemQty'      => $canEditItemQty,
            'poReturns'           => $poReturns,
        ]);
    }

    // =========================================================================
    // PRINT PO (Clean, Standalone Print Layout)
    // =========================================================================

    public function printPO($id)
    {
        $po = $this->poModel->find($id);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $creatorRole   = (int)($po['creator_role_id'] ?? 0);
        $phNeeded      = in_array($creatorRole, [23, 31]);
        $phApproved    = !empty($po['purchase_head_approved_by_id']);
        $qcApproved    = !empty($po['qc_approved_by_id']);
        $opApproved    = !empty($po['op_head_approved_by_id']);
        $bodNeeded     = ((float)($po['total_amount'] ?? 0) > 30000);
        $bodApproved   = !empty($po['bod_approved_by_id']);
        $alreadyFinalStatus = in_array($po['status'] ?? '', ['Approved', 'Partially Received', 'Fully Received'], true);
        $fullyApproved = $alreadyFinalStatus || (($phNeeded ? $phApproved : true) && $qcApproved && $opApproved && ($bodNeeded ? $bodApproved : true));

        $items = $this->poModel->getItems($id);

        // Fetch vendor selection data
        $vendorSelectionData = null;
        if (!empty($po['request_id']) && !empty($po['vendor_id'])) {
            $cstQuery = $this->db->prepare("
                SELECT q.attachment, q.drive_link, qc.prepared_by, u.full_name AS selector_name
                FROM quotations q
                LEFT JOIN quotation_comparisons qc ON qc.request_id = q.request_id
                LEFT JOIN users u ON u.id = qc.prepared_by
                WHERE q.request_id = ? AND q.vendor_id = ?
                LIMIT 1
            ");
            $cstQuery->bind_param('ii', $po['request_id'], $po['vendor_id']);
            $cstQuery->execute();
            $vendorSelectionData = $cstQuery->get_result()->fetch_assoc();
        }

        // Fetch Comparative Statement (CST) matrix
        require_once __DIR__ . '/../../models/Con_Quotation.php';
        $quotModel = new \Con_Quotation($this->db);
        $cstMatrix = $quotModel->buildComparison($po['request_id']);

        // Fetch Negotiation / Observation Notes
        $notesQuery = $this->db->prepare("
            SELECT n.*, u.full_name AS author_name, u.role_id AS author_role
            FROM po_negotiation_notes n
            JOIN users u ON u.id = n.user_id
            WHERE n.purchase_order_id = ?
            ORDER BY n.created_at DESC
        ");
        $negotiationNotes = [];
        if ($notesQuery) {
            $notesQuery->bind_param('i', $id);
            $notesQuery->execute();
            $negotiationNotes = $notesQuery->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $poTotalValue = 0;
        foreach ($items as $itm) {
            $poTotalValue += (float)($itm['quantity'] * $itm['unit_price']);
        }

        // Selected Vendor Full Details
        $selectedVendor = null;
        $printTargetVendorId = !empty($po['vendor_id']) ? (int)$po['vendor_id'] : (!empty($items[0]['selected_vendor_id']) ? (int)$items[0]['selected_vendor_id'] : 0);
        if ($printTargetVendorId > 0) {
            $vStmt = $this->db->prepare("SELECT id, name, phone, email, address, company_name FROM vendors WHERE id = ?");
            if ($vStmt) {
                $vStmt->bind_param('i', $printTargetVendorId);
                $vStmt->execute();
                $selectedVendor = $vStmt->get_result()->fetch_assoc();
            }
        }

        // Fetch PR Details & CST info
        $quotationsList = [];
        $cstInfo        = null;
        $prDetails      = null;

        if (!empty($po['request_id'])) {
            $prId = (int)$po['request_id'];

            $prStmt = $this->db->prepare("
                SELECT pr.*, u.full_name AS requester_name, p.name AS pr_project_name 
                FROM purchase_requests pr 
                LEFT JOIN users u ON u.id = pr.created_by 
                LEFT JOIN projects p ON p.id = pr.project_id 
                WHERE pr.id = ?
            ");
            if ($prStmt) {
                $prStmt->bind_param('i', $prId);
                $prStmt->execute();
                $prDetails = $prStmt->get_result()->fetch_assoc();
            }

            $cstStmt = $this->db->prepare("
                SELECT qc.*, u.full_name AS prepared_by_name 
                FROM quotation_comparisons qc 
                LEFT JOIN users u ON u.id = qc.prepared_by 
                WHERE qc.request_id = ?
                LIMIT 1
            ");
            if ($cstStmt) {
                $cstStmt->bind_param('i', $prId);
                $cstStmt->execute();
                $cstInfo = $cstStmt->get_result()->fetch_assoc();
            }

            $qStmt = $this->db->prepare("
                SELECT q.*, 
                       v.name AS vendor_name, 
                       v.phone AS vendor_phone, 
                       v.email AS vendor_email, 
                       v.address AS vendor_address, 
                       v.company_name AS vendor_company_name,
                       u.full_name AS quoted_by_name,
                       (SELECT SUM(qi.quantity * qi.unit_price) FROM quotation_items qi WHERE qi.quotation_id = q.id) AS total_quoted_amount
                FROM quotations q 
                LEFT JOIN vendors v ON v.id = q.vendor_id 
                LEFT JOIN users u ON u.id = q.created_by
                WHERE q.request_id = ?
                ORDER BY q.id ASC
            ");
            if ($qStmt) {
                $qStmt->bind_param('i', $prId);
                $qStmt->execute();
                $quotationsList = $qStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            }
        }

        // Fetch Site Settings
        $siteSettings = null;
        $sQuery = $this->db->query("SELECT * FROM site_settings LIMIT 1");
        if ($sQuery) {
            $siteSettings = $sQuery->fetch_assoc();
        }

        $qtyLogs        = $this->poModel->getQtyLogs($id);
        $canEditItemQty = false;

        $this->view('phase2/purchase-orders/print-purchase-order', [
            'page_title'          => 'Print Purchase Order #' . $id,
            'po'                  => $po,
            'items'               => $items,
            'phNeeded'            => $phNeeded,
            'phApproved'          => $phApproved,
            'qcApproved'          => $qcApproved,
            'opApproved'          => $opApproved,
            'bodNeeded'           => $bodNeeded,
            'bodApproved'         => $bodApproved,
            'fullyApproved'       => $fullyApproved,
            'vendorSelectionData' => $vendorSelectionData,
            'cstMatrix'           => $cstMatrix,
            'negotiationNotes'    => $negotiationNotes,
            'poTotalValue'        => $poTotalValue,
            'selectedVendor'      => $selectedVendor,
            'quotationsList'      => $quotationsList,
            'cstInfo'             => $cstInfo,
            'prDetails'           => $prDetails,
            'siteSettings'        => $siteSettings,
            'qtyLogs'             => $qtyLogs,
            'canEditItemQty'      => $canEditItemQty,
        ], false); // $useMaster = false (standalone document)
    }

    // =========================================================================
    // GENERATE PO FROM CST
    // =========================================================================

    public function generate($requestId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $result = $this->poModel->generate($requestId, $userId);

        if ($result['success']) {
            // ── Send Email Notification to Approvers ─────────────────────────
            require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
            $mailService = new \ProjectWorkflowMailService($this->db);
            // Fetch project ID and name. The mail service expects a project ID.
            $projectQuery = $this->db->prepare("SELECT pr.project_id, p.name FROM purchase_requests pr JOIN projects p ON p.id = pr.project_id WHERE pr.id = ?");
            $projectName = 'Unknown Project';
            $projectId = 0;
            if ($projectQuery) {
                $projectQuery->bind_param('i', $requestId);
                $projectQuery->execute();
                $pNameRow = $projectQuery->get_result()->fetch_assoc();
                $projectName = $pNameRow['name'] ?? 'Unknown Project';
                $projectId = (int)($pNameRow['project_id'] ?? 0);
                $projectQuery->close();
            }

            try {
                $mailService->notifyApproversForNewPO($projectId, $projectName, $result['id']);
            } catch (\Throwable $e) {
                error_log("[Con_PurchaseOrderController] Failed to send PO notification email: " . $e->getMessage());
            }

            $poData = $this->poModel->find($result['id']);
            $creatorRole = (int)($poData['creator_role_id'] ?? 0);
            $nextStage = in_array($creatorRole, [23, 31], true) ? 'Purchase Head' : 'QC & Internal Audit';

            setSuccess('Purchase Order #' . $result['id'] . ' generated. Awaiting ' . $nextStage . ' approval.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$result['id']}");
        } else {
            setError('PO generation failed: ' . $result['error']);
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$requestId}");
        }
        exit;
    }

    // =========================================================================
    // APPROVE PO STEP — SE (12) or PM (15)
    // =========================================================================

    public function approvePO($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']   ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $po = $this->poModel->find($id);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        $note = trim($_POST['approval_note'] ?? '');
        $creatorRole = (int)($po['creator_role_id'] ?? 0);
        $phNeeded = in_array($creatorRole, [23, 31]);
        $bodNeeded = ((float)($po['total_amount'] ?? 0) > 30000);

        // 1. Purchase Head Approval (role 17 or Admin)
        if (($roleId === 17 || in_array($roleId, [1, 3])) && $phNeeded && empty($po['purchase_head_approved_by_id'])) {
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET purchase_head_approved_by_id = ?, purchase_head_approved_at = NOW(), purchase_head_note = ?, status = 'Purchase Head Approved' WHERE id = ?"
            );
            $stmt->bind_param('isi', $userId, $note, $id);
            if ($stmt->execute()) {
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                $mailService->notifyPOApprovalStage($po['project_name'], $id, [24], 'QC & Internal Audit');
                setSuccess('Purchase Order approved by Purchase Head. Awaiting QC & Internal Audit approval.');
            } else {
                setError('Failed to approve Purchase Order: ' . $stmt->error);
            }

        // 2. QC & Internal Audit Approval (role 24 or Admin)
        } elseif (($roleId === 24 || in_array($roleId, [1, 3])) && (!$phNeeded || !empty($po['purchase_head_approved_by_id'])) && empty($po['qc_approved_by_id'])) {
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET qc_approved_by_id = ?, qc_approved_at = NOW(), qc_note = ?, status = 'QC Approved' WHERE id = ?"
            );
            $stmt->bind_param('isi', $userId, $note, $id);
            if ($stmt->execute()) {
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                $mailService->notifyPOApprovalStage($po['project_name'], $id, [20], 'Operation Head');
                setSuccess('Purchase Order approved by QC & Internal Audit. Awaiting Operation Head approval.');
            } else {
                setError('Failed to approve Purchase Order: ' . $stmt->error);
            }

        // 3. Operation Head Approval (role 20 or Admin)
        } elseif (($roleId === 20 || in_array($roleId, [1, 3])) && !empty($po['qc_approved_by_id']) && empty($po['op_head_approved_by_id'])) {
            $nextStatus = $bodNeeded ? 'Operation Head Approved' : 'Approved';
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET op_head_approved_by_id = ?, op_head_approved_at = NOW(), op_head_note = ?, status = ? WHERE id = ?"
            );
            $stmt->bind_param('issi', $userId, $note, $nextStatus, $id);
            if ($stmt->execute()) {
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                if ($bodNeeded) {
                    $mailService->notifyPOApprovalStage($po['project_name'], $id, [38], 'Board of Director');
                    setSuccess('Purchase Order approved by Operation Head. Awaiting Board of Director approval (PO exceeds ৳30,000).');
                } else {
                    $mailService->notifySiteOperatorForApprovedPO($id, $po['project_name']);
                    setSuccess('Purchase Order approved by Operation Head. Status updated to Fully Approved!');
                }
            } else {
                setError('Failed to approve Purchase Order: ' . $stmt->error);
            }

        // 4. Board of Director Approval (role 38 or Admin) — required when PO total > 30,000
        } elseif (($roleId === 38 || in_array($roleId, [1, 3])) && !empty($po['op_head_approved_by_id']) && $bodNeeded && empty($po['bod_approved_by_id'])) {
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET bod_approved_by_id = ?, bod_approved_at = NOW(), bod_note = ?, status = 'Approved' WHERE id = ?"
            );
            $stmt->bind_param('isi', $userId, $note, $id);
            if ($stmt->execute()) {
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                $mailService->notifySiteOperatorForApprovedPO($id, $po['project_name']);
                setSuccess('Purchase Order approved by Board of Director. Status updated to Fully Approved!');
            } else {
                setError('Failed to approve Purchase Order: ' . $stmt->error);
            }

        } else {
            setError('You are not authorized to approve Purchase Orders at this stage.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
            exit;
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT PO STEP
    // =========================================================================

    public function rejectPO($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']   ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $po = $this->poModel->find($id);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        // Role guard — Purchase Head (17), QC (24), Operation Head (20), Board of Director (38), or Admin (1, 3) can reject
        if (!in_array($roleId, [17, 20, 24, 38, 1, 3])) {
            setError('You are not authorized to reject Purchase Orders.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
            exit;
        }

        if ($po['status'] === 'Cancelled' || $po['status'] === 'Fully Received') {
            setError('This PO cannot be rejected anymore.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
            exit;
        }

        $remarks = trim($_POST['remarks'] ?? '');
        if (empty($remarks)) {
            setError('Rejection remarks are required.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
            exit;
        }

        // Update PO status to Cancelled and save remarks
        $upd = $this->db->prepare("UPDATE purchase_orders SET status = 'Cancelled', remarks = ? WHERE id = ?");
        $upd->bind_param('si', $remarks, $id);
        if ($upd->execute()) {
            // Also update the linked Purchase Request so requester knows it needs revision
            if (!empty($po['request_id'])) {
                $prId = (int)$po['request_id'];
                $prUpd = $this->db->prepare("
                    UPDATE purchase_requests 
                    SET status = 'PO Rejected', 
                        po_rejected_by_id = ?, 
                        po_rejected_at = NOW(), 
                        po_rejection_note = ?, 
                        last_rejected_po_id = ? 
                    WHERE id = ?
                ");
                if ($prUpd) {
                    $prUpd->bind_param('isii', $userId, $remarks, $id, $prId);
                    $prUpd->execute();
                    $prUpd->close();
                }
            }

            setSuccess('Purchase Order has been rejected and Requisition returned for revision.');

            // Trigger rejection email
            require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
            $mailService = new \ProjectWorkflowMailService($this->db);
            $mailService->notifyAfterPORejection((int)$po['project_id'], $po['project_name'], $id, $remarks);
        } else {
            setError('Failed to reject Purchase Order.');
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
        exit;
    }

    // =========================================================================
    // ADD NEGOTIATION NOTE
    // =========================================================================

    public function addNote($poId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']   ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Allow Purchase Head (17, 23, 31) and QC (24)
        if (!in_array($roleId, [17, 23, 31, 24])) {
            setError('You are not authorized to add negotiation notes.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        $po = $this->poModel->find($poId);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        $noteText = trim($_POST['note_text'] ?? '');
        if (empty($noteText)) {
            setError('Note cannot be empty.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        $stmt = $this->db->prepare("INSERT INTO po_negotiation_notes (purchase_order_id, request_id, user_id, note_text) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('iiis', $poId, $po['request_id'], $userId, $noteText);
            if ($stmt->execute()) {
                setSuccess('Observation note added successfully.');
            } else {
                setError('Failed to add note: ' . $stmt->error);
            }
            $stmt->close();
        } else {
            setError('Failed to prepare note: ' . $this->db->error);
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
        exit;
    }

    // =========================================================================
    // DELETE PO
    // =========================================================================

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $roleName = strtolower($_SESSION['user']['role_name'] ?? '');

        $po = $this->poModel->find($id);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        $isCreator = ($po['created_by'] == $userId);
        $isManagement = (strpos($roleName, 'management') !== false || strpos($roleName, 'admin') !== false || in_array($roleId, [1, 3, 6, 30]));

        if (!$isCreator && !$isManagement) {
            setError('You do not have permission to delete this Purchase Order.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
            exit;
        }

        if (!empty($po['purchase_head_approved_by_id']) || !empty($po['qc_approved_by_id']) || !empty($po['op_head_approved_by_id']) || !empty($po['bod_approved_by_id'])) {
            setError('This Purchase Order has already been approved and cannot be deleted.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
            exit;
        }

        $requestId = (int)$po['request_id'];

        $this->db->begin_transaction();
        try {
            $this->db->query("DELETE FROM purchase_order_items WHERE purchase_order_id = {$id}");
            $this->db->query("DELETE FROM po_negotiation_notes WHERE purchase_order_id = {$id}");
            $this->db->query("DELETE FROM purchase_orders WHERE id = {$id}");

            // Reset PR status to Approved if no other PO exists for this PR
            $poCountCheck = $this->db->query("SELECT COUNT(*) as count FROM purchase_orders WHERE request_id = {$requestId}");
            if ($poCountCheck && $poCountCheck->fetch_assoc()['count'] == 0) {
                $this->db->query("UPDATE purchase_requests SET status = 'Approved' WHERE id = {$requestId}");
            }

            $this->db->commit();
            setSuccess('Purchase Order deleted successfully.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
        } catch (\Exception $e) {
            $this->db->rollback();
            setError('Failed to delete Purchase Order: ' . $e->getMessage());
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
        }
        exit;
    }

    // =========================================================================
    // UPDATE ITEM QUANTITY (Approvers / Creator with CST Recalculation & Audit)
    // =========================================================================

    public function updateItemQuantity($poId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentUserId = (int)($_SESSION['user']['id']   ?? 0);
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if ($currentUserId <= 0) {
            setError('Unauthorized access. Please log in.');
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $po = $this->poModel->find($poId);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        if ($po['status'] === 'Cancelled' || in_array($po['status'] ?? '', ['Approved', 'Partially Received', 'Fully Received'], true)) {
            setError('Cannot modify quantities for a cancelled, approved, or received Purchase Order.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        // Strict Pre-Approval Lock for Creator: once any approver approves, creator is locked out
        $creatorRole = (int)($po['creator_role_id'] ?? 0);
        $phNeeded    = in_array($creatorRole, [23, 31], true);
        $phApproved  = !empty($po['purchase_head_approved_by_id']);
        $qcApproved  = !empty($po['qc_approved_by_id']);
        $opApproved  = !empty($po['op_head_approved_by_id']);
        $bodNeeded   = ((float)($po['total_amount'] ?? 0) > 30000);
        $bodApproved = !empty($po['bod_approved_by_id']);

        $isNotApproved   = !$phApproved && !$qcApproved && !$opApproved && !$bodApproved;
        $isAdminOverride = in_array($currentRoleId, [1, 3], true);
        $isCreator       = ($currentUserId === (int)($po['created_by'] ?? 0));

        $canApprovePH  = in_array($currentRoleId, [17, 1, 3], true) && $phNeeded && !$phApproved;
        $canApproveQC  = in_array($currentRoleId, [24, 1, 3], true) && (!$phNeeded || $phApproved) && !$qcApproved;
        $canApproveOP  = in_array($currentRoleId, [20, 1, 3], true) && $qcApproved && !$opApproved;
        $canApproveBOD = in_array($currentRoleId, [38, 1, 3], true) && $opApproved && $bodNeeded && !$bodApproved;
        $isActiveApprover = ($canApprovePH || $canApproveQC || $canApproveOP || $canApproveBOD);

        require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        $poRevSvc = new \RequisitionRevisionService($this->db);
        $openRev  = $poRevSvc->getOpenRevision('purchase_orders', (int)$poId);

        if (!$isAdminOverride && !$isActiveApprover && !($isCreator && ($isNotApproved || !empty($openRev)))) {
            $errMsg = ($isCreator && !$isNotApproved)
                ? 'This Purchase Order has already been approved by at least one approver. Creator can no longer modify quantities.'
                : 'You do not have permission to modify quantities for this Purchase Order.';
            if ((!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1')) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $errMsg]);
                exit;
            }
            setError($errMsg);
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        $itemId = (int)($_POST['item_id'] ?? 0);
        $newQty = (float)($_POST['new_quantity'] ?? 0);
        $reason = trim($_POST['adjustment_reason'] ?? '');

        $result = $this->poModel->updateItemQuantity($poId, $itemId, $newQty, $currentUserId, $reason);

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
                  || (isset($_POST['is_ajax']) && $_POST['is_ajax'] == '1');

        if ($result['success']) {
            $msg = "Quantity updated successfully to " . number_format($newQty, 3) . ". PO amount recalculated based on CST vendor rate.";
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => $msg, 'data' => $result]);
                exit;
            }
            setSuccess($msg);
        } else {
            $err = $result['error'] ?: 'Failed to update item quantity.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $err]);
                exit;
            }
            setError($err);
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
        exit;
    }

    /**
     * Update PO Header Details (Order Date & Remarks) — Creator or Admin BEFORE anyone approves
     */
    public function update($poId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $poId          = (int)$poId;
        $currentUserId = (int)($_SESSION['user']['id'] ?? 0);
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $po = $this->poModel->find($poId);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        $isNotApproved = empty($po['purchase_head_approved_by_id'])
            && empty($po['qc_approved_by_id'])
            && empty($po['op_head_approved_by_id'])
            && empty($po['bod_approved_by_id'])
            && !in_array($po['status'] ?? '', ['Approved', 'Partially Received', 'Fully Received', 'Cancelled'], true);

        require_once __DIR__ . '/../../services/RequisitionRevisionService.php';
        $poRevSvc = new \RequisitionRevisionService($this->db);
        $openRev  = $poRevSvc->getOpenRevision('purchase_orders', $poId);

        $isCreator       = ($currentUserId === (int)($po['created_by'] ?? 0));
        $isAdminOverride = in_array($currentRoleId, [1, 3], true);

        if (!$isNotApproved && empty($openRev)) {
            setError('This Purchase Order has already been approved or finalized and can no longer be edited.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        if (!$isCreator && !$isAdminOverride) {
            setError('Only the creator or Admin can edit Purchase Order details before approval.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
            exit;
        }

        $orderDate = trim($_POST['order_date'] ?? ($po['order_date'] ?? date('Y-m-d')));
        $remarks   = trim($_POST['remarks'] ?? '');

        if (empty($orderDate)) {
            $orderDate = date('Y-m-d');
        }

        $stmt = $this->db->prepare("UPDATE purchase_orders SET order_date = ?, remarks = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('ssi', $orderDate, $remarks, $poId);
            if ($stmt->execute()) {
                if (!empty($openRev)) {
                    $poRevSvc->markResubmitted('purchase_orders', $poId, null, trim($_POST['resubmitted_note'] ?? ''));
                    setSuccess("Purchase Order #{$poId} updated & resubmitted directly to {$openRev['return_stage_label']}.");
                } else {
                    setSuccess("Purchase Order #{$poId} details updated successfully.");
                }
            } else {
                setError('Failed to update Purchase Order details.');
            }
            $stmt->close();
        } else {
            setError('Database error while updating Purchase Order.');
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
        exit;
    }
}
