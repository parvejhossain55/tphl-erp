<?php
namespace Phase2;

use Controller;
use Con_VendorPaymentRequest;
use Con_Vendor;
use Project;

class Con_VendorPaymentRequestController extends Controller
{
    protected $db;
    protected $model;
    protected $vendorModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->model = new Con_VendorPaymentRequest($db);
        $this->vendorModel = new Con_Vendor($db);
        $this->projectModel = new Project($db);
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
        $projects = $this->projectModel->getAll();
        $vendors = $this->vendorModel->getAll();

        $this->view('phase2/vendor-payment-requests/create-payment', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Create Payment Request',
            'projects'      => $projects,
            'vendors'       => $vendors,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $vendorId = (int)($_POST['vendor_id'] ?? 0);
        $invoiceAmt = (float)($_POST['invoice_amount'] ?? 0);
        
        // Re-validate penalty on backend to prevent tampering
        $penaltyDeducted = $this->model->getPendingPenaltySum($vendorId);
        $netPayable = max(0, $invoiceAmt - $penaltyDeducted);

        $data = [
            'project_id'       => (int)($_POST['project_id'] ?? 0),
            'vendor_id'        => $vendorId,
            'invoice_amount'   => $invoiceAmt,
            'penalty_deducted' => $penaltyDeducted,
            'net_payable'      => $netPayable,
            'payment_date'     => $_POST['payment_date'] ?? date('Y-m-d'),
            'remarks'          => trim($_POST['remarks'] ?? ''),
            'created_by'       => $userId
        ];

        if (!$data['project_id'] || !$data['vendor_id'] || $data['invoice_amount'] <= 0) {
            setError('Project, Vendor, and a valid Invoice Amount are required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-payment-create");
            exit;
        }

        $id = $this->model->create($data);
        if ($id) {
            setSuccess("Payment Request created successfully. Deducted ৳ " . number_format($penaltyDeducted, 2) . " as penalty.");
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

        // Fetch penalties linked to this request for detailed view
        $penalties = [];
        $pStmt = $this->db->prepare("SELECT * FROM vendor_penalties WHERE payment_request_id = ?");
        $pStmt->bind_param('i', $id);
        $pStmt->execute();
        $res = $pStmt->get_result();
        if ($res) {
            $penalties = $res->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/vendor-payment-requests/show-payment', [
            'current_route' => 'con-vendor-payment-requests',
            'page_title'    => 'Payment Request Details',
            'request'       => $request,
            'penalties'     => $penalties
        ]);
    }

    // =========================================================================
    // API: AJAX FETCH PENALTY
    // =========================================================================

    public function ajaxGetPenalty()
    {
        header('Content-Type: application/json');
        $vendorId = (int)($_GET['vendor_id'] ?? 0);
        
        $penalty = $this->model->getPendingPenaltySum($vendorId);
        
        echo json_encode(['success' => true, 'penalty' => $penalty]);
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
            setSuccess('Payment Request Approved.');
        } else {
            setError('Failed to approve or request already processed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendor-payment-show/{$id}");
        exit;
    }
}
