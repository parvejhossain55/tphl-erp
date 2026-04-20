<?php
namespace Phase2;

use Controller;
use Con_PurchaseRequest;
use Con_BOQ;
use Project;
use Con_Material;
use Con_Quotation;

class Con_PurchaseRequestController extends Controller
{
    protected $db;
    protected $prModel;
    protected $boqModel;
    protected $projectModel;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->prModel       = new Con_PurchaseRequest($this->db);
        $this->boqModel      = new Con_BOQ($this->db);
        $this->projectModel  = new Project($this->db);
        $this->materialModel = new Con_Material($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $requests = $this->prModel->getAll();

        $this->view('phase2/purchase-requests/purchase-request-lists', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Purchase Requests',
            'requests'      => $requests,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        $projects  = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $this->view('phase2/purchase-requests/create-purchase-request', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'New Purchase Request',
            'projects'      => $projects,
            'materials'     => $materials,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'created_by' => (int)($_SESSION['user']['id'] ?? 0),
            'status'     => $_POST['status'] ?? 'Draft',
            'notes'      => trim($_POST['notes'] ?? ''),
        ];

        $materialIds = $_POST['material_id'] ?? [];
        $quantities  = $_POST['quantity']    ?? [];

        $items = [];
        foreach ($materialIds as $i => $mid) {
            if (!empty($mid) && ($quantities[$i] ?? 0) > 0) {
                $items[] = [
                    'material_id' => (int)$mid,
                    'quantity'    => (float)$quantities[$i],
                ];
            }
        }

        if (empty($items)) {
            setError('At least one material item is required.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request-create");
            exit;
        }

        $result = $this->prModel->create($data, $items);

        if ($result['success']) {
            setSuccess('Purchase Request created successfully.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
        } else {
            $errMsg = implode(' | ', $result['errors']);
            setError("PR creation failed: {$errMsg}");
            header("Location: {$GLOBALS['base_url']}/con-purchase-request-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $pr = $this->prModel->find($id);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $items = $this->prModel->getItems($id);

        // Check if quotations / comparison exist
        $quotModel  = new Con_Quotation($this->db);
        $quotations = $quotModel->getByRequest($id);
        $hasCST     = $quotModel->hasComparison($id);

        $this->view('phase2/purchase-requests/show-purchase-request', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Purchase Request #' . $id,
            'pr'            => $pr,
            'items'         => $items,
            'quotations'    => $quotations,
            'hasCST'        => $hasCST,
        ]);
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    public function approve($id)
    {
        $status = $_POST['status'] ?? 'Approved';
        if (!in_array($status, ['Submitted', 'Approved', 'Rejected'])) {
            setError('Invalid status.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
            exit;
        }

        if ($this->prModel->updateStatus($id, $status)) {
            setSuccess("Purchase Request status updated to {$status}.");
        } else {
            setError('Failed to update status.');
        }
        header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
        exit;
    }
}
