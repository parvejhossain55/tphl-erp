<?php
namespace Phase2;

use Controller;
use Con_Operator;

class Con_OperatorController extends Controller
{
    protected $db;
    protected $operatorModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->operatorModel = new Con_Operator($this->db);
    }

    /* =====================
       List all operators
    ====================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $operators = $this->operatorModel->getAll();

        $this->view('phase2/operators/operators-list', [
            'current_route' => 'con-operators',
            'page_title'    => 'Operators & Contractors',
            'operators'     => $operators,
        ]);
    }

    /* =====================
       Create Form
    ====================== */
    public function create()
    {
        $this->view('phase2/operators/create-operator', [
            'current_route' => 'con-operators',
            'page_title'    => 'Add Operator / Contractor',
        ]);
    }

    /* =====================
       Store
    ====================== */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'role'       => $_POST['role'] ?? 'Contractor',
            'trade'      => $_POST['trade'] ?? null,
            'phone'      => $_POST['phone'] ?? null,
            'nid_number' => $_POST['nid_number'] ?? null,
            'address'    => $_POST['address'] ?? null,
            'daily_rate' => $_POST['daily_rate'] ?? null,
            'status'     => 'active',
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name'])) {
            setError('Operator name is required.');
            header("Location: {$GLOBALS['base_url']}/con-operator-create");
            exit;
        }

        if ($this->operatorModel->create($data)) {
            setSuccess('Operator created successfully!');
            header("Location: {$GLOBALS['base_url']}/con-operators");
            exit;
        }

        setError('Failed to create operator.');
        header("Location: {$GLOBALS['base_url']}/con-operator-create");
        exit;
    }

    /* =====================
       Edit Form
    ====================== */
    public function edit($id)
    {
        $operator = $this->operatorModel->find($id);
        if (!$operator) {
            setError('Operator not found.');
            header("Location: {$GLOBALS['base_url']}/con-operators");
            exit;
        }

        $this->view('phase2/operators/edit-operator', [
            'current_route' => 'con-operators',
            'page_title'    => 'Edit Operator',
            'operator'      => $operator,
        ]);
    }

    /* =====================
       Update
    ====================== */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'       => trim($_POST['name'] ?? ''),
            'role'       => $_POST['role'] ?? 'Contractor',
            'trade'      => $_POST['trade'] ?? null,
            'phone'      => $_POST['phone'] ?? null,
            'nid_number' => $_POST['nid_number'] ?? null,
            'address'    => $_POST['address'] ?? null,
            'daily_rate' => $_POST['daily_rate'] ?? null,
            'status'     => $_POST['status'] ?? 'active',
        ];

        if ($this->operatorModel->update($id, $data)) {
            setSuccess('Operator updated successfully!');
            header("Location: {$GLOBALS['base_url']}/con-operators");
            exit;
        }

        setError('Failed to update operator.');
        header("Location: {$GLOBALS['base_url']}/con-operator-edit/$id");
        exit;
    }

    /* =====================
       Delete
    ====================== */
    public function delete()
    {
        $id = $_POST['id'] ?? 0;
        if ($this->operatorModel->delete($id)) {
            setSuccess('Operator deleted.');
        } else {
            setError('Delete failed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-operators");
        exit;
    }
}
