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

        // Role guards — SE (12) approves SE step, PM (15) approves PM step
        $seApproved   = !empty($po['approved_by_se']);
        $pmApproved   = !empty($po['approved_by_pm']);
        $fullyApproved = $seApproved && $pmApproved;

        $canApproveSE = ($currentRoleId === 12) && !$seApproved; // && ($currentUserId !== $createdBy);
        $canApprovePM = in_array($currentRoleId, [1, 3, 15, 20]) && !$pmApproved;

        $items        = $this->poModel->getItems($id);
        $pendingItems = $this->poModel->getPendingItems($id);

        // Fetch the BOQ linked to this PO (via PR.bom_id) — shown to PM before approval
        $linkedBoq = $this->poModel->getLinkedBoq($id);

        $this->view('phase2/purchase-orders/show-purchase-order', [
            'current_route'  => 'con-purchase-orders',
            'page_title'     => 'Purchase Order #' . $id,
            'po'             => $po,
            'items'          => $items,
            'pendingItems'   => $pendingItems,
            'seApproved'     => $seApproved,
            'pmApproved'     => $pmApproved,
            'fullyApproved'  => $fullyApproved,
            'canApproveSE'   => $canApproveSE,
            'canApprovePM'   => $canApprovePM,
            'currentRoleId'  => $currentRoleId,
            'linkedBoq'      => $linkedBoq,
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

            setSuccess('Purchase Order #' . $result['id'] . ' generated. Awaiting SE & PM approval.');
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

        // Role guard
        if ($roleId === 12) {
            // SE approval
            if (!empty($po['approved_by_se'])) {
                setError('SE has already approved this PO.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET approved_by_se = ?, se_approved_at = NOW() WHERE id = ?"
            );
            $stmt->bind_param('ii', $userId, $id);
            $stmt->execute();

        } elseif (in_array($roleId, [1, 3, 15, 20])) {
            // PM / Top Management approval
            if (!empty($po['approved_by_pm'])) {
                setError('Authorized PM/Director has already approved this PO.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
                exit;
            }
            $stmt = $this->db->prepare(
                "UPDATE purchase_orders SET approved_by_pm = ?, pm_approved_at = NOW() WHERE id = ?"
            );
            $stmt->bind_param('ii', $userId, $id);
            $stmt->execute();

        } else {
            setError('You are not authorized to approve Purchase Orders.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
            exit;
        }

        // Refresh PO to check full approval
        $fresh = $this->poModel->find($id);
        if (!empty($fresh['approved_by_se']) && !empty($fresh['approved_by_pm'])) {
            // Both approved — update status to 'Approved'
            $upd = $this->db->prepare("UPDATE purchase_orders SET status = 'Approved' WHERE id = ?");
            $upd->bind_param('i', $id);
            $upd->execute();

            // ── Notify Site Operator ─────────────────────────
            require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
            $mailService = new \ProjectWorkflowMailService($this->db);
            $mailService->notifySiteOperatorForApprovedPO($id, $po['project_name']);

            setSuccess('PO fully approved by SE & PM. Status updated to Approved!');
        } else {
            setSuccess('Your approval has been recorded. Waiting for the other approval.');
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$id}");
        exit;
    }
}
