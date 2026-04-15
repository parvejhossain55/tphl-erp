<?php

class Con_MaterialController extends Controller
{
    protected $db;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->materialModel = new Con_Material($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $materials = $this->materialModel->getAll();

        $this->view('phase2/materials/index', [
            'current_route' => 'con-materials',
            'page_title'    => 'Construction Materials',
            'materials'     => $materials,
        ]);
    }

    public function create()
    {
        $this->view('phase2/materials/create', [
            'current_route' => 'con-materials',
            'page_title'    => 'Add Material',
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'unit'            => trim($_POST['unit'] ?? ''),
            'min_stock_level' => $_POST['min_stock_level'] ?? 0,
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name']) || empty($data['unit'])) {
            setError('Name and Unit are required.');
            header("Location: {$GLOBALS['base_url']}/con-material-create");
            exit;
        }

        if ($this->materialModel->create($data)) {
            setSuccess('Material added successfully!');
            header("Location: {$GLOBALS['base_url']}/con-materials");
            exit;
        }

        setError('Failed to add material.');
        header("Location: {$GLOBALS['base_url']}/con-material-create");
        exit;
    }

    public function edit($id)
    {
        $material = $this->materialModel->find($id);
        $this->view('phase2/materials/edit', [
            'current_route' => 'con-materials',
            'page_title'    => 'Edit Material',
            'material'      => $material,
        ]);
    }

    public function update($id)
    {
        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'unit'            => trim($_POST['unit'] ?? ''),
            'min_stock_level' => $_POST['min_stock_level'] ?? 0,
        ];

        if ($this->materialModel->update($id, $data)) {
            setSuccess('Material updated!');
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-materials");
        exit;
    }
}
