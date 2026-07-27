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
        $orders = $this->poModel->getAll();

        $this->view('phase2/purchase-orders/purchase-order-lists', [
            'current_route' => 'con-purchase-orders',
            'page_title'    => 'Purchase Orders',
            'orders'        => $orders,
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

        // Role guard — QC (24) + Management (3) approval needed
        $qcApproved    = !empty($po['qc_approved_by_id']);
        $mgmtApproved  = !empty($po['mgmt_approved_by_id']);
        $fullyApproved = $qcApproved && $mgmtApproved;

        $canApproveQC   = ($currentRoleId === 24) && !$qcApproved;
        $canApproveMgmt = ($currentRoleId === 3) && $qcApproved && !$mgmtApproved;

        $items        = $this->poModel->getItems($id);
        $pendingItems = $this->poModel->getPendingItems($id);

        // Fetch the BOQ linked to this PO (via PR.bom_id)
        $linkedBoq = $this->poModel->getLinkedBoq($id);

        // Check if any material receiving already exists for this PO
        $existingReceivingQuery = $this->db->prepare("SELECT COUNT(*) as count FROM material_receiving WHERE purchase_order_id = ?");
        $existingReceivingQuery->bind_param('i', $id);
        $existingReceivingQuery->execute();
        $hasReceiving = $existingReceivingQuery->get_result()->fetch_assoc()['count'] > 0;

        $this->view('phase2/purchase-orders/show-purchase-order', [
            'current_route'  => 'con-purchase-orders',
            'page_title'     => 'Purchase Order #' . $id,
            'po'             => $po,
            'items'          => $items,
            'pendingItems'   => $pendingItems,
            'qcApproved'     => $qcApproved,
            'mgmtApproved'   => $mgmtApproved,
            'fullyApproved'  => $fullyApproved,
            'canApproveQC'   => $canApproveQC,
            'canApproveMgmt' => $canApproveMgmt,
            'currentRoleId'  => $currentRoleId,
            'linkedBoq'      => $linkedBoq,
            'hasReceiving'   => $hasReceiving,
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
            // Fetch project name
            $projectQuery = $this->db->prepare("SELECT p.name FROM purchase_requests pr JOIN projects p ON p.id = pr.project_id WHERE pr.id = ?");
            $projectQuery->bind_param('i', $requestId);
            $projectQuery->execute();
            $pNameRow = $projectQuery->get_result()->fetch_assoc();
            $projectName = $pNameRow['name'] ?? 'Unknown Project';

            $mailService->notifyApproversForNewPO($requestId, $projectName, $result['id']);

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

        // 1. QC & Internal Audit Approval (role 24)
        if ($roleId === 24) {
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
                setSuccess('Purchase Order approved by QC & Internal Audit. Awaiting Management approval.');
            } else {
                setError('Failed to approve Purchase Order: ' . $stmt->error);
            }

        // 2. Management Approval (role 3)
        } elseif ($roleId === 3) {
            if (empty($po['qc_approved_by_id'])) {
                setError('QC & Internal Audit must approve this Purchase Order first.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
            if (!empty($po['mgmt_approved_by_id'])) {
                setError('Management has already approved this PO.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET mgmt_approved_by_id = ?, mgmt_approved_at = NOW(), mgmt_note = ?, status = 'Approved' WHERE id = ?"
            );
            $stmt->bind_param('isi', $userId, $note, $id);
            if ($stmt->execute()) {
                // ── Notify Site Operator ─────────────────────────
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                $mailService->notifySiteOperatorForApprovedPO($id, $po['project_name']);

                setSuccess('Purchase Order approved by Management. Status updated to Fully Approved!');
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

        // Role guard — Only QC (24) or Management (3) can reject
        if (!in_array($roleId, [3, 24])) {
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
}
