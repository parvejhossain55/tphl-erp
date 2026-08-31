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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 33, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        // ── Handle attachment upload ──────────────────────────────────
        $attachmentFile = uploadFile('attachment', 'vendor_quotation');

        $data = [
            'request_id'    => (int)($_POST['request_id'] ?? 0),
            'vendor_id'     => (int)($_POST['vendor_id'] ?? 0),
            'validity_date' => $_POST['validity_date'] ?? null,
            'status'        => 'Active',
            'attachment'    => $attachmentFile, // null if no file uploaded
            'created_by'    => (int)($_SESSION['user']['id'] ?? 0),
        ];

        // Prevent duplicate vendor quotation for the same PR
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM quotations WHERE request_id = ? AND vendor_id = ?");
        $stmt->bind_param('ii', $data['request_id'], $data['vendor_id']);
        $stmt->execute();
        $resRow = $stmt->get_result()->fetch_assoc();
        if ($resRow && $resRow['cnt'] > 0) {
            setError('A quotation from this vendor has already been added for this Purchase Request. If you need to make changes, please edit the existing quotation.');
            header("Location: {$GLOBALS['base_url']}/con-quotation-create/{$data['request_id']}");
            exit;
        }

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
            $errDetail = $this->quotModel->lastError ?? 'Unknown error';
            setError('Failed to save quotation. DB Error: ' . $errDetail);
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

        // Fetch Negotiation Notes for this PR
        $notesQuery = $this->db->prepare("
            SELECT n.*, u.full_name AS author_name, u.role_id AS author_role
            FROM po_negotiation_notes n
            JOIN users u ON u.id = n.user_id
            WHERE n.request_id = ?
            ORDER BY n.created_at DESC
        ");
        $notesQuery->bind_param('i', $requestId);
        $notesQuery->execute();
        $negotiationNotes = $notesQuery->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/quotations/compare-quotations', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Comparative Statement — PR #' . $requestId,
            'pr'            => $pr,
            'comparison'    => $comparison,
            'negotiationNotes' => $negotiationNotes,
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

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [17, 23])) {
            setError('You do not have permission to edit records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $quotation = $this->quotModel->find($id);
        if (!$quotation) {
            setError('Quotation not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $requestId = $quotation['request_id'];
        $pr = $this->prModel->find($requestId);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        // Check if PO is already generated and approved
        $poCheck = $this->db->query("SELECT status FROM purchase_orders WHERE request_id = {$requestId} ORDER BY id DESC LIMIT 1");
        if ($poCheck && $poCheck->num_rows > 0) {
            $poStatus = $poCheck->fetch_assoc()['status'];
            if (in_array($poStatus, ['Approved', 'Purchase Head Approved', 'Fully Received', 'Partially Received'])) {
                setError('Purchase Order is already approved or processed. You cannot edit this quotation anymore.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$requestId}");
                exit;
            }
        }

        $prItems        = $this->prModel->getItems($requestId);
        $quotationItems = $this->quotModel->getItems($id);
        $vendors        = $this->vendorModel->getAll();
        $existing       = $this->quotModel->getByRequest($requestId);

        $this->view('phase2/quotations/create-quotation', [
            'current_route'  => 'con-purchase-requests',
            'page_title'     => 'Edit Vendor Quotation — PR #' . $requestId,
            'pr'             => $pr,
            'prItems'        => $prItems,
            'quotation'      => $quotation,
            'quotationItems' => $quotationItems,
            'vendors'        => $vendors,
            'existing'       => $existing,
            'isEdit'         => true,
        ]);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update($quotationId)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [17, 23])) {
            setError('You do not have permission to edit records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $quotation = $this->quotModel->find($quotationId);
        if (!$quotation) {
            setError('Quotation not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $requestId = $quotation['request_id'];
        $pr = $this->prModel->find($requestId);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        // Check if PO is already generated and approved
        $poCheck = $this->db->query("SELECT status FROM purchase_orders WHERE request_id = {$requestId} ORDER BY id DESC LIMIT 1");
        if ($poCheck && $poCheck->num_rows > 0) {
            $poStatus = $poCheck->fetch_assoc()['status'];
            if (in_array($poStatus, ['Approved', 'Purchase Head Approved', 'Fully Received', 'Partially Received'])) {
                setError('Purchase Order is already approved or processed. You cannot edit this quotation anymore.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$requestId}");
                exit;
            }
        }

        // Handle attachment upload if any
        $attachmentFile = uploadFile('attachment', 'vendor_quotation');

        $data = [
            'validity_date' => $_POST['validity_date'] ?? null,
            'attachment'    => $attachmentFile, // null if no file uploaded
            'created_by'    => (int)($_SESSION['user']['id'] ?? 0),
        ];

        // If no new attachment, keep the old one
        if (empty($attachmentFile)) {
            $data['attachment'] = $quotation['attachment'];
        }

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
            header("Location: {$GLOBALS['base_url']}/con-quotation-edit/{$quotationId}");
            exit;
        }

        $success = $this->quotModel->update($quotationId, $data, $items);
        if ($success) {
            setSuccess('Vendor quotation updated successfully.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$requestId}");
        } else {
            $errDetail = $this->quotModel->lastError ?? 'Unknown error';
            setError('Failed to update quotation. DB Error: ' . $errDetail);
            header("Location: {$GLOBALS['base_url']}/con-quotation-edit/{$quotationId}");
        }
        exit;
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [17, 23])) {
            setError('You do not have permission to delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $quotation = $this->quotModel->find($id);
        if (!$quotation) {
            setError('Quotation not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $requestId = $quotation['request_id'];
        $pr = $this->prModel->find($requestId);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        // Check if PO is already generated and approved
        $poCheck = $this->db->query("SELECT status FROM purchase_orders WHERE request_id = {$requestId} ORDER BY id DESC LIMIT 1");
        if ($poCheck && $poCheck->num_rows > 0) {
            $poStatus = $poCheck->fetch_assoc()['status'];
            if (in_array($poStatus, ['Approved', 'Purchase Head Approved', 'Fully Received', 'Partially Received'])) {
                setError('Purchase Order is already approved or processed. You cannot delete this quotation anymore.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$requestId}");
                exit;
            }
        }

        // Delete attachment file if exists
        if (!empty($quotation['attachment'])) {
            $filePath = dirname(__DIR__, 2) . '/public/uploads/vendor_quotation/' . $quotation['attachment'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }

        $success = $this->quotModel->delete($id);
        if ($success) {
            setSuccess('Vendor quotation deleted successfully.');
        } else {
            setError('Failed to delete quotation. DB Error: ' . ($this->quotModel->lastError ?? 'Unknown error'));
        }
        header("Location: {$GLOBALS['base_url']}/con-quotation-create/{$requestId}");
        exit;
    }
}
