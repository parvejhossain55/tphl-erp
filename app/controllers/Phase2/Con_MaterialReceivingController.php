<?php
namespace Phase2;

use Controller;
use Con_MaterialReceiving;
use Con_PurchaseOrder;
use Con_Warehouse;

class Con_MaterialReceivingController extends Controller
{
    protected $db;
    protected $receivingModel;
    protected $poModel;
    protected $warehouseModel;

    public function __construct($db)
    {
        $this->db              = $db;
        $this->receivingModel  = new Con_MaterialReceiving($this->db);
        $this->poModel         = new Con_PurchaseOrder($this->db);
        $this->warehouseModel  = new Con_Warehouse($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $receivings = $this->receivingModel->getAll();

        $this->view('phase2/material-receiving/receiving-lists', [
            'current_route' => 'con-material-receivings',
            'page_title'    => 'Material Receiving (GRN)',
            'receivings'    => $receivings,
        ]);
    }

    // =========================================================================
    // CREATE — New Receiving for a PO
    // =========================================================================

    public function create($poId)
    {
        $po = $this->poModel->find($poId);
        if (!$po) {
            setError('Purchase Order not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-orders");
            exit;
        }

        $pendingItems = $this->poModel->getPendingItems($poId);
        $warehouses   = $this->warehouseModel->getAll();

        $this->view('phase2/material-receiving/create-receiving', [
            'current_route' => 'con-material-receivings',
            'page_title'    => 'New Material Receiving — PO-' . $poId,
            'po'            => $po,
            'pendingItems'  => $pendingItems,
            'warehouses'    => $warehouses,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // File upload helper
        $uploadPhoto = function ($fileInputName) {
            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../../public/uploads/material_receivings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_' . basename($_FILES[$fileInputName]['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFile)) {
                    return '/public/uploads/material_receivings/' . $fileName;
                }
            }
            return null;
        };

        $data = [
            'purchase_order_id' => (int)($_POST['purchase_order_id'] ?? 0),
            'warehouse_id'      => (int)($_POST['warehouse_id'] ?? 0),
            'received_by'       => $userId,
            'receiving_date'    => $_POST['receiving_date'] ?? date('Y-m-d'),
            'received_status'   => $_POST['received_status'] ?? 'Full',
            'notes'             => trim($_POST['notes'] ?? ''),
            'site_eng_note'     => trim($_POST['site_eng_note'] ?? ''),
            'site_eng_photo'    => $uploadPhoto('site_eng_photo'),
            'created_by'        => $userId,
        ];

        $poItemIds       = $_POST['purchase_order_item_id'] ?? [];
        $orderedQtys     = $_POST['ordered_quantity']       ?? [];
        $receivedQtys    = $_POST['received_quantity']      ?? [];

        $items = [];
        foreach ($poItemIds as $i => $poiId) {
            $recvQty = (float)($receivedQtys[$i] ?? 0);
            if ($recvQty > 0) {
                $items[] = [
                    'purchase_order_item_id' => (int)$poiId,
                    'ordered_quantity'       => (float)($orderedQtys[$i] ?? 0),
                    'received_quantity'      => $recvQty,
                ];
            }
        }

        if (empty($items)) {
            setError('No items with quantity to receive.');
            header("Location: {$GLOBALS['base_url']}/con-material-receiving-create/{$data['purchase_order_id']}");
            exit;
        }

        $id = $this->receivingModel->create($data, $items);
        if ($id) {
            setSuccess('Material Receiving GRN-' . $id . ' logged. Proceed to QC Review.');
            header("Location: {$GLOBALS['base_url']}/con-material-receiving-qc/{$id}");
        } else {
            setError('Failed to create receiving record.');
            header("Location: {$GLOBALS['base_url']}/con-material-receiving-create/{$data['purchase_order_id']}");
        }
        exit;
    }

    // =========================================================================
    // QC REVIEW
    // =========================================================================

    public function qcReview($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->qcStore($id);
        }

        $receiving = $this->receivingModel->find($id);
        if (!$receiving) {
            setError('Receiving record not found.');
            header("Location: {$GLOBALS['base_url']}/con-material-receivings");
            exit;
        }

        $items = $this->receivingModel->getItems($id);

        $this->view('phase2/material-receiving/qc-review-receiving', [
            'current_route' => 'con-material-receivings',
            'page_title'    => 'QC Review — GRN-' . $id,
            'receiving'     => $receiving,
            'items'         => $items,
        ]);
    }

    // =========================================================================
    // QC STORE
    // =========================================================================

    private function qcStore($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId   = (int)($_SESSION['user']['id'] ?? 0);
        $statuses = $_POST['item_status']   ?? [];
        $reasons  = $_POST['issue_reason']  ?? [];

        $itemStatuses = [];
        foreach ($statuses as $itemId => $status) {
            $itemStatuses[(int)$itemId] = [
                'status'       => $status,
                'issue_reason' => $reasons[$itemId] ?? null,
            ];
        }

        // File upload helper
        $uploadPhoto = function ($fileInputName) {
            if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../../public/uploads/material_receivings/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_' . basename($_FILES[$fileInputName]['name']);
                $targetFile = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetFile)) {
                    return '/public/uploads/material_receivings/' . $fileName;
                }
            }
            return null;
        };

        if (empty($itemStatuses)) {
            setError('No QC decisions provided.');
            header("Location: {$GLOBALS['base_url']}/con-material-receiving-qc/{$id}");
            exit;
        }

        $itemStatuses['qc_note'] = trim($_POST['qc_note'] ?? '');
        $itemStatuses['qc_photo'] = $uploadPhoto('qc_photo');

        if ($this->receivingModel->qcReview($id, $itemStatuses, $userId)) {
            setSuccess('QC Review completed. Stock updated for accepted items.');
        } else {
            setError('QC Review failed. Please check the logs.');
        }
        header("Location: {$GLOBALS['base_url']}/con-material-receiving-qc/{$id}");
        exit;
    }
}
