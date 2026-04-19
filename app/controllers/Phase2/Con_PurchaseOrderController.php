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

        $items        = $this->poModel->getItems($id);
        $pendingItems = $this->poModel->getPendingItems($id);

        $this->view('phase2/purchase-orders/show-purchase-order', [
            'current_route' => 'con-purchase-orders',
            'page_title'    => 'Purchase Order #' . $id,
            'po'            => $po,
            'items'         => $items,
            'pendingItems'  => $pendingItems,
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
            setSuccess('Purchase Order #' . $result['id'] . ' generated successfully.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-order/{$result['id']}");
        } else {
            setError('PO generation failed: ' . $result['error']);
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$requestId}");
        }
        exit;
    }
}
