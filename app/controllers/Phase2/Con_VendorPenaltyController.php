<?php
namespace Phase2;

use Controller;
use Con_VendorPenalty;
use Con_Vendor;

class Con_VendorPenaltyController extends Controller
{
    protected $db;
    protected $penaltyModel;
    protected $vendorModel;

    public function __construct($db)
    {
        $this->db           = $db;
        $this->penaltyModel = new Con_VendorPenalty($this->db);
        $this->vendorModel  = new Con_Vendor($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $penalties = $this->penaltyModel->getAll();
        $stats     = $this->penaltyModel->getStats();

        $this->view('phase2/vendor-penalties/penalty-lists', [
            'current_route' => 'con-vendor-penalties',
            'page_title'    => 'Vendor Penalties',
            'penalties'     => $penalties,
            'stats'         => $stats,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $vendors = $this->vendorModel->getAll();

        $this->view('phase2/vendor-penalties/create-penalty', [
            'current_route' => 'con-vendor-penalties',
            'page_title'    => 'Issue Vendor Penalty',
            'vendors'       => $vendors,
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
            'vendor_id'         => (int)($_POST['vendor_id'] ?? 0),
            'purchase_order_id' => (int)($_POST['purchase_order_id'] ?? 0),
            'penalty_category'  => trim($_POST['penalty_category'] ?? ''),
            'penalty_amount'    => (float)($_POST['penalty_amount'] ?? 0),
            'reason'            => trim($_POST['reason'] ?? ''),
            'action_taken'      => $_POST['action_taken'] ?? 'Deduct from Bill',
            'penalty_status'    => 'Pending',
            'created_by'        => (int)($_SESSION['user']['id'] ?? 0),
        ];

        if (empty($data['vendor_id']) || empty($data['penalty_category'])) {
            setError('Vendor and penalty category are required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-penalty-create");
            exit;
        }

        if ($this->penaltyModel->create($data)) {
            setSuccess('Vendor penalty issued. Rating recalculated.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-penalties");
        } else {
            setError('Failed to create penalty.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-penalty-create");
        }
        exit;
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    public function delete()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->penaltyModel->delete($id)) {
            setSuccess('Penalty removed. Vendor rating recalculated.');
        } else {
            setError('Failed to delete penalty.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendor-penalties");
        exit;
    }
}
