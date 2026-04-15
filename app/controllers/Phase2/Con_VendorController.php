<?php

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

        $this->view('phase2/vendors/index', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Vendors & Suppliers',
            'vendors'       => $vendors,
        ]);
    }

    public function create()
    {
        $this->view('phase2/vendors/create', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Add Vendor',
        ]);
    }

    public function store()
    {
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
        $vendor = $this->vendorModel->find($id);
        $this->view('phase2/vendors/edit', [
            'current_route' => 'con-vendors',
            'page_title'    => 'Edit Vendor',
            'vendor'        => $vendor,
        ]);
    }

    public function update($id)
    {
        $data = [
            'name'    => trim($_POST['name'] ?? ''),
            'phone'   => $_POST['phone'] ?? null,
            'email'   => $_POST['email'] ?? null,
            'address' => $_POST['address'] ?? null,
            'status'  => $_POST['status'] ?? 'active',
        ];

        if ($this->vendorModel->update($id, $data)) {
            setSuccess('Vendor updated!');
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-vendors");
        exit;
    }
}
