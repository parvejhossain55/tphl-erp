<?php
namespace Phase2;

use Controller;
use Con_Vendor;

class Con_VendorController extends Controller
{
    protected $db;
    protected $vendorModel;

    public function __construct($db)
    {
        $this->db          = $db;
        $this->vendorModel = new Con_Vendor($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $vendors = $this->vendorModel->getAll();

        $this->view('phase2/vendors/vendor-lists', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Vendors & Suppliers',
            'vendors'       => $vendors,
        ]);
    }

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $this->view('phase2/vendors/create-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Add Vendor',
        ]);
    }

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'phone'      => $_POST['phone'] ?? null,
            'email'      => $_POST['email'] ?? null,
            'address'    => $_POST['address'] ?? null,
            'rating'     => 5.00,
            'status'     => 'active',
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError('Vendor name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-create");
            exit;
        }

        if ($this->vendorModel->create($data)) {
            setSuccess('Vendor added successfully!');
            header("Location: {$GLOBALS['base_url']}/con-vendors");
            exit;
        }

        setError('Failed to add vendor.');
        header("Location: {$GLOBALS['base_url']}/con-vendor-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $vendor = $this->vendorModel->find($id);

        if (!$vendor) {
            setError('Vendor not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendors");
            exit;
        }

        $this->view('phase2/vendors/edit-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Edit Vendor',
            'vendor'        => $vendor,
        ]);
    }

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'    => trim($_POST['name'] ?? ''),
            'phone'   => $_POST['phone'] ?? null,
            'email'   => $_POST['email'] ?? null,
            'address' => $_POST['address'] ?? null,
            'status'  => $_POST['status'] ?? 'active',
        ];

        if (empty($data['name'])) {
            setError('Vendor name is required.');
            header("Location: {$GLOBALS['base_url']}/con-vendor-edit/{$id}");
            exit;
        }

        if ($this->vendorModel->update($id, $data)) {
            setSuccess('Vendor updated successfully!');
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-vendors");
        exit;
    }

    public function show($id)
    {
        $vendor = $this->vendorModel->find($id);
        if (!$vendor) {
            setError('Vendor not found.');
            header("Location: {$GLOBALS['base_url']}/con-vendors");
            exit;
        }

        $stats     = $this->vendorModel->getStats($id);
        $penalties = $this->vendorModel->getPenalties($id);
        $poHistory = $this->vendorModel->getPurchaseHistory($id);

        $this->view('phase2/vendors/show-vendor', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Vendor Profile — ' . $vendor['name'],
            'vendor'        => $vendor,
            'stats'         => $stats,
            'penalties'     => $penalties,
            'poHistory'     => $poHistory,
        ]);
    }

    public function delete()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->vendorModel->delete($id)) {
            setSuccess('Vendor deleted.');
        } else {
            setError('Failed to delete vendor.');
        }
        header("Location: {$GLOBALS['base_url']}/con-vendors");
        exit;
    }
}