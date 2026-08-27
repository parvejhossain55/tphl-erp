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

        $orders = $this->poModel->getAll();

        // Determine filters
        $isApproverRole = in_array($currentRoleId, [17, 24, 20]);
        $filter = $_GET['filter'] ?? ($isApproverRole ? 'pending_my_approval' : 'all');
        $projectId = $_GET['project_id'] ?? '';

        $filteredOrders = [];
        foreach ($orders as $po) {
            // Project filter
            if ($projectId !== '' && $po['project_id'] != $projectId) {
                continue;
            }

            $creatorRole   = (int)($po['creator_role_id'] ?? 0);
            $phNeeded      = in_array($creatorRole, [23, 31]);
            $phApproved    = !empty($po['purchase_head_approved_by_id']);
            $qcApproved    = !empty($po['qc_approved_by_id']);
            $opApproved    = !empty($po['op_head_approved_by_id']);

            $canApprovePH   = ($currentRoleId === 17) && $phNeeded && !$phApproved;
            $canApproveQC   = ($currentRoleId === 24) && (!$phNeeded || $phApproved) && !$qcApproved;
            $canApproveOP   = ($currentRoleId === 20) && $qcApproved && !$opApproved;

            $iCanApprove = $canApprovePH || $canApproveQC || $canApproveOP;
            $iHaveApproved = ($po['purchase_head_approved_by_id'] == $currentUserId) ||
                             ($po['qc_approved_by_id'] == $currentUserId) ||
                             ($po['op_head_approved_by_id'] == $currentUserId);

            if ($filter === 'pending_my_approval') {
                if ($iCanApprove) {
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

        // Sort so that CSV uploads appear at the bottom
        usort($filteredOrders, function($a, $b) {
            $aCsv = (strpos(strtolower($a['upload_from'] ?? ''), 'csv') !== false) ? 1 : 0;
            $bCsv = (strpos(strtolower($b['upload_from'] ?? ''), 'csv') !== false) ? 1 : 0;
            if ($aCsv !== $bCsv) {
                return $aCsv - $bCsv;
            }
            return $b['id'] <=> $a['id']; // Descending by ID for same upload_from type
        });

        $this->view('phase2/purchase-orders/purchase-order-lists', [
            'current_route'    => 'con-purchase-orders',
            'page_title'       => 'Purchase Orders',
            'orders'           => $filteredOrders,
            'currentFilter'    => $filter,
            'projects'         => $projects,
            'currentProjectId' => $projectId
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
        $fullyApproved = ($phNeeded ? $phApproved : true) && $qcApproved && $opApproved;

        $canApprovePH   = ($currentRoleId === 17) && $phNeeded && !$phApproved;
        $canApproveQC   = ($currentRoleId === 24) && (!$phNeeded || $phApproved) && !$qcApproved;
        $canApproveOP   = ($currentRoleId === 20) && $qcApproved && !$opApproved;

        // Management checks are mapped to Operation Head parameters for compatibility
        $mgmtApproved   = $opApproved;
        $canApproveMgmt = $canApproveOP;

        $items        = $this->poModel->getItems($id);
        $pendingItems = $this->poModel->getPendingItems($id);

        // Fetch the BOQ linked to this PO (via PR.bom_id)
        $linkedBoq = $this->poModel->getLinkedBoq($id);

        // Check if any material receiving already exists for this PO
        $existingReceivingQuery = $this->db->prepare("SELECT COUNT(*) as count FROM material_receiving WHERE purchase_order_id = ?");
        $existingReceivingQuery->bind_param('i', $id);
        $existingReceivingQuery->execute();
        $hasReceiving = $existingReceivingQuery->get_result()->fetch_assoc()['count'] > 0;

        // Fetch vendor selection data (attachment & prepared by)
        $vendorSelectionData = null;
        if (!empty($po['request_id']) && !empty($po['vendor_id'])) {
            $cstQuery = $this->db->prepare("
                SELECT q.attachment, qc.prepared_by, u.full_name AS selector_name
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

        $this->view('phase2/purchase-orders/show-purchase-order', [
            'current_route'  => 'con-purchase-orders',
            'page_title'     => 'Purchase Order #' . $id,
            'po'             => $po,
            'items'          => $items,
            'pendingItems'   => $pendingItems,
            'phNeeded'       => $phNeeded,
            'phApproved'     => $phApproved,
            'qcApproved'     => $qcApproved,
            'opApproved'     => $opApproved,
            'fullyApproved'  => $fullyApproved,
            'canApprovePH'   => $canApprovePH,
            'canApproveQC'   => $canApproveQC,
            'canApproveOP'   => $canApproveOP,
            'mgmtApproved'   => $mgmtApproved,
            'canApproveMgmt' => $canApproveMgmt,
            'currentRoleId'  => $currentRoleId,
            'linkedBoq'      => $linkedBoq,
            'hasReceiving'   => $hasReceiving,
            'vendorSelectionData' => $vendorSelectionData,
            'cstMatrix'      => $cstMatrix,
            'negotiationNotes' => $negotiationNotes,
        ]);
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
            $projectQuery->bind_param('i', $requestId);
            $projectQuery->execute();
            $pNameRow = $projectQuery->get_result()->fetch_assoc();
            $projectName = $pNameRow['name'] ?? 'Unknown Project';

            $mailService->notifyApproversForNewPO((int)($pNameRow['project_id'] ?? 0), $projectName, $result['id']);

            setSuccess('Purchase Order #' . $result['id'] . ' generated. Awaiting QC & Internal Audit approval.');
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

        // 1. Purchase Head Approval (role 17)
        if ($roleId === 17 && $phNeeded) {
            if (!empty($po['purchase_head_approved_by_id'])) {
                setError('Purchase Head has already approved this PO.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
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

        // 2. QC & Internal Audit Approval (role 24)
        } elseif ($roleId === 24) {
            if ($phNeeded && empty($po['purchase_head_approved_by_id'])) {
                setError('Purchase Head must approve this Purchase Order first.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
            if (!empty($po['qc_approved_by_id'])) {
                setError('QC & Internal Audit has already approved this PO.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
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

        // 3. Operation Head Approval (role 20)
        } elseif ($roleId === 20) {
            if (empty($po['qc_approved_by_id'])) {
                setError('QC & Internal Audit must approve this Purchase Order first.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
            if (!empty($po['op_head_approved_by_id'])) {
                setError('Operation Head has already approved this PO.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET op_head_approved_by_id = ?, op_head_approved_at = NOW(), op_head_note = ?, status = 'Approved' WHERE id = ?"
            );
            $stmt->bind_param('isi', $userId, $note, $id);
            if ($stmt->execute()) {
                // ── Notify Site Operator ─────────────────────────
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                $mailService->notifySiteOperatorForApprovedPO($id, $po['project_name']);

                setSuccess('Purchase Order approved by Operation Head. Status updated to Fully Approved!');
            } else {
                setError('Failed to approve Purchase Order: ' . $stmt->error);
            }

        } else {
            setError('You are not authorized to approve Purchase Orders.');
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

        // Role guard — Only Purchase Head (17), QC (24), or Operation Head (20) can reject
        if (!in_array($roleId, [17, 20, 24])) {
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

        // Update status to Cancelled
        $upd = $this->db->prepare("UPDATE purchase_orders SET status = 'Cancelled' WHERE id = ?");
        $upd->bind_param('i', $id);
        if ($upd->execute()) {
            setSuccess('Purchase Order has been rejected.');

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
        $stmt->bind_param('iiis', $poId, $po['request_id'], $userId, $noteText);
        if ($stmt->execute()) {
            setSuccess('Observation note added successfully.');
        } else {
            setError('Failed to add note: ' . $stmt->error);
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$poId}");
        exit;
    }
}
