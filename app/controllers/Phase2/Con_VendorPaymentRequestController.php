<?php
namespace Phase2;

use Controller;
use Con_VendorPaymentRequest;
use Con_Vendor;
use Con_PurchaseOrder;
use Project;

class Con_VendorPaymentRequestController extends Controller
{
    protected $db;
    protected $model;
    protected $vendorModel;
    protected $projectModel;
    protected $poModel;

    public function __construct($db)
    {
        $this->db           = $db;
        $this->model        = new Con_VendorPaymentRequest($db);
        $this->vendorModel  = new Con_Vendor($db);
        $this->projectModel = new Project($db);
        $this->poModel      = new Con_PurchaseOrder($db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $requests = $this->model->getAll();

        $this->view('phase2/vendor-payment-requests/payment-lists', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Vendor Payment Requests',
            'requests'      => $requests,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        $projects     = $this->projectModel->getAll();
        $vendors      = $this->vendorModel->getAll();
        $paymentTypes = ['Advance', 'Partial', 'Final'];

        // Fetch all purchase orders for initial load (AJAX will filter by project)
        $allPOs = $this->poModel->getAll();

        $this->view('phase2/vendor-payment-requests/create-payment', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Create Payment Request',
            'projects'      => $projects,
            'vendors'       => $vendors,
            'payment_types' => $paymentTypes,
            'all_pos'       => $allPOs,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId      = (int)($_SESSION['user']['id'] ?? 0);
        $vendorId    = (int)($_POST['vendor_id'] ?? 0);
        $paymentType = $_POST['payment_type'] ?? 'Final';
        $invoiceAmt  = (float)($_POST['invoice_amount'] ?? 0);
        // purchase_order_id is NOT NULL in DB — use 0 if not selected
        $poId        = (int)($_POST['purchase_order_id'] ?? 0);

        // Advance: no penalty deduction, net = invoice amount
        if ($paymentType === 'Advance') {
            $penaltyDeducted = 0.00;
            $netPayable      = $invoiceAmt;
        } else {
            $penaltyDeducted = $this->model->getPendingPenaltySum($vendorId);
            $netPayable      = max(0, $invoiceAmt - $penaltyDeducted);
        }

        $data = [
            'project_id'        => (int)($_POST['project_id'] ?? 0),
            'vendor_id'         => $vendorId,
            'purchase_order_id' => $poId,          // int (0 = no PO)
            'payment_type'      => $paymentType,
            'invoice_amount'    => $invoiceAmt,
            'penalty_deducted'  => $penaltyDeducted,
            'net_payable'       => $netPayable,
            'delivery_verified' => ($paymentType === 'Advance') ? 0 : (int)($_POST['delivery_verified'] ?? 0),
            'created_by'        => $userId,
        ];

        if (!$data['project_id'] || !$data['vendor_id'] || $data['invoice_amount'] <= 0) {
            setError('Project, Vendor, and a valid Amount are required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
            exit;
        }

        $id = $this->model->create($data);
        if ($id) {
            $msg = ($paymentType === 'Advance')
                ? "Advance Payment Request created. Amount: ৳ " . number_format($invoiceAmt, 2)
                : "Payment Request created. Penalty deducted: ৳ " . number_format($penaltyDeducted, 2);
            setSuccess($msg);
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        } else {
            setError('Failed to create payment request.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $request = $this->model->find($id);
        if (!$request) {
            setError('Payment request not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-requests");
            exit;
        }

        // Fetch penalties linked to this request
        $penalties = [];
        $pStmt = $this->db->prepare("SELECT * FROM vendor_penalties WHERE payment_request_id = ?");
        $pStmt->bind_param('i', $id);
        $pStmt->execute();
        $res = $pStmt->get_result();
        if ($res) {
            $penalties = $res->fetch_all(MYSQLI_ASSOC);
        }

        // If Final/Partial: calculate total advance already paid for this PO
        $advancePaid = 0.00;
        if (!empty($request['purchase_order_id']) && $request['payment_type'] !== 'Advance') {
            $advancePaid = $this->model->getTotalAdvancePaid($request['purchase_order_id']);
        }

        // Fetch all payments for the same PO (for history)
        $poPayments = [];
        if (!empty($request['purchase_order_id'])) {
            $poPayments = $this->model->getByPurchaseOrder($request['purchase_order_id']);
        }

        $this->view('phase2/vendor-payment-requests/show-payment', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Payment Request Details',
            'request'       => $request,
            'penalties'     => $penalties,
            'advance_paid'  => $advancePaid,
            'po_payments'   => $poPayments,
        ]);
    }

    // =========================================================================
    // API: AJAX FETCH PENALTY
    // =========================================================================

    public function ajaxGetPenalty()
    {
        header('Content-Type: application/json');
        $vendorId = (int)($_GET['vendor_id'] ?? 0);
        $penalty  = $this->model->getPendingPenaltySum($vendorId);
        echo json_encode(['success' => true, 'penalty' => $penalty]);
        exit;
    }

    // =========================================================================
    // API: AJAX FETCH ADVANCE SUMMARY FOR A PO
    // =========================================================================

    /**
     * Returns total advance already paid + pending penalty for a PO+Vendor combo.
     * Used in Final/Partial payment form for auto deduction display.
     */
    public function ajaxGetAdvanceSummary()
    {
        header('Content-Type: application/json');

        $poId     = (int)($_GET['po_id'] ?? 0);
        $vendorId = (int)($_GET['vendor_id'] ?? 0);

        $advancePaid = $poId     ? $this->model->getTotalAdvancePaid($poId) : 0;
        $penalty     = $vendorId ? $this->model->getPendingPenaltySum($vendorId) : 0;

        echo json_encode([
            'success'      => true,
            'advance_paid' => $advancePaid,
            'penalty'      => $penalty,
        ]);
        exit;
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        if ($this->model->approve($id, $userId)) {
            setSuccess('Payment Request Approved — now visible in Pending Payments for processing.');
        } else {
            setError('Failed to approve or request already processed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }

    // =========================================================================
    // REJECT
    // =========================================================================

    public function reject($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        if ($this->model->reject($id, $userId)) {
            setSuccess('Payment Request Rejected.');
        } else {
            setError('Failed to reject request.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }
}
