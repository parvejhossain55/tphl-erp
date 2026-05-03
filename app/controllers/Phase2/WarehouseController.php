<?php
namespace Phase2;

use Controller;
use Con_Warehouse;
use Project;

class WarehouseController extends Controller
{
    protected $db;
    protected $warehouseModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->warehouseModel = new Con_Warehouse($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $warehouses = $this->warehouseModel->getAll();

        $this->view('phase2/warehouses/con-warehouses', [
            'current_route' => 'con-warehouses',
            'page_title'    => 'Warehouses',
            'warehouses'    => $warehouses,
        ]);
    }

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/warehouses/create-warehouse', [
            'current_route' => 'con-warehouses',
            'page_title'    => 'Add Warehouse',
            'projects'      => $projects,
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
            'project_id' => !empty($_POST['project_id']) ? $_POST['project_id'] : null,
            'location'   => trim($_POST['location'] ?? ''),
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError('Name is required.');
            header("Location: {$GLOBALS['base_url']}/con-warehouse-create");
            exit;
        }

        if ($this->warehouseModel->create($data)) {
            setSuccess('Warehouse created successfully!');
            header("Location: {$GLOBALS['base_url']}/con-warehouses");
            exit;
        }

        setError('Failed to create warehouse.');
        header("Location: {$GLOBALS['base_url']}/con-warehouse-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $warehouse = $this->warehouseModel->find($id);
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/warehouses/edit-warehouse', [
            'current_route' => 'con-warehouses',
            'page_title'    => 'Edit Warehouse',
            'warehouse'     => $warehouse,
            'projects'      => $projects,
        ]);
    }

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'project_id' => !empty($_POST['project_id']) ? $_POST['project_id'] : null,
            'location'   => trim($_POST['location'] ?? ''),
        ];

        if ($this->warehouseModel->update($id, $data)) {
            setSuccess('Warehouse updated!');
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-warehouses");
        exit;
    }
}
