<?php
namespace Phase2;

use Controller;
use Con_Quotation;
use Con_PurchaseRequest;
use Con_Vendor;

class Con_QuotationController extends Controller
{
    protected $db;
    protected $quotModel;
    protected $prModel;
    protected $vendorModel;

    public function __construct($db)
    {
        $this->db          = $db;
        $this->quotModel   = new Con_Quotation($this->db);
        $this->prModel     = new Con_PurchaseRequest($this->db);
        $this->vendorModel = new Con_Vendor($this->db);
    }

    // =========================================================================
    // CREATE (add quotation for a PR)
    // =========================================================================

    public function create($requestId)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $pr = $this->prModel->find($requestId);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $prItems   = $this->prModel->getItems($requestId);
        $vendors   = $this->vendorModel->getAll();
        $existing  = $this->quotModel->getByRequest($requestId);

        $this->view('phase2/quotations/create-quotation', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Add Vendor Quotation — PR #' . $requestId,
            'pr'            => $pr,
            'prItems'       => $prItems,
            'vendors'       => $vendors,
            'existing'      => $existing,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'request_id'    => (int)($_POST['request_id'] ?? 0),
            'vendor_id'     => (int)($_POST['vendor_id'] ?? 0),
            'validity_date' => $_POST['validity_date'] ?? null,
            'status'        => 'Active',
            'created_by'    => (int)($_SESSION['user']['id'] ?? 0),
        ];

        $materialIds = $_POST['material_id'] ?? [];
        $quantities  = $_POST['quantity']    ?? [];
        $unitPrices  = $_POST['unit_price']  ?? [];

        $items = [];
        foreach ($materialIds as $i => $mid) {
            if (!empty($mid)) {
                $items[] = [
                    'material_id' => (int)$mid,
                    'quantity'    => (float)($quantities[$i] ?? 0),
                    'unit_price'  => (float)($unitPrices[$i] ?? 0),
                ];
            }
        }

        if (empty($items)) {
            setError('No quotation items provided.');
            header("Location: {$GLOBALS['base_url']}/con-quotation-create/{$data['request_id']}");
            exit;
        }

        $id = $this->quotModel->create($data, $items);
        if ($id) {
            setSuccess('Vendor quotation added successfully.');
            header("Location: {$GLOBALS['base_url']}/con-quotation-create/{$data['request_id']}");
        } else {
            setError('Failed to save quotation.');
            header("Location: {$GLOBALS['base_url']}/con-quotation-create/{$data['request_id']}");
        }
        exit;
    }

    // =========================================================================
    // COMPARE (CST — Comparative Statement)
    // =========================================================================

    public function compare($requestId)
    {
        $pr = $this->prModel->find($requestId);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $comparison = $this->quotModel->buildComparison($requestId);

        $this->view('phase2/quotations/compare-quotations', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Comparative Statement — PR #' . $requestId,
            'pr'            => $pr,
            'comparison'    => $comparison,
        ]);
    }

    // =========================================================================
    // SELECT WINNER (save CST selections)
    // =========================================================================

    public function selectWinner()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $userId    = (int)($_SESSION['user']['id'] ?? 0);

        $selectedVendors  = $_POST['selected_vendor'] ?? [];
        $materialIds      = $_POST['sel_material_id'] ?? [];
        $unitPrices       = $_POST['sel_unit_price'] ?? [];

        $selections = [];
        foreach ($materialIds as $i => $mid) {
            $vendorId = (int)($selectedVendors[$i] ?? 0);
            $price    = (float)($unitPrices[$i] ?? 0);
            if ($vendorId > 0 && $mid > 0) {
                $selections[] = [
                    'material_id'  => (int)$mid,
                    'vendor_id'    => $vendorId,
                    'unit_price'   => $price,
                    'is_selected'  => 1,
                ];
            }
        }

        if (empty($selections)) {
            setError('No vendor selections made.');
            header("Location: {$GLOBALS['base_url']}/con-quotation-compare/{$requestId}");
            exit;
        }

        $compId = $this->quotModel->saveComparison($requestId, $selections, $userId);
        if ($compId) {
            setSuccess('CST selections saved. You can now generate a Purchase Order.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$requestId}");
        } else {
            setError('Failed to save CST selections.');
            header("Location: {$GLOBALS['base_url']}/con-quotation-compare/{$requestId}");
        }
        exit;
    }
}
