<?php
namespace Phase2;

use Con_Contractor;
use Controller;

class Con_ContractorController extends Controller
{
    protected $db;
    protected $contractorModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->contractorModel = new Con_Contractor($this->db);
    }

    /* =====================
       List all operators
    ====================== */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $operators = $this->contractorModel->getAll();

        $this->view('phase2/contractors/contractor-lists', [
            'current_route' => 'con-contractors',
            'page_title'    => ' Contractors',
            'operators'     => $operators,
        ]);
    }

    /* =====================
       Create Form
    ====================== */
    public function create()
    {
        $this->view('phase2/contractors/create-contractor', [
            'current_route' => 'con-contractor-create',
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
            header("Location: {$GLOBALS['base_url']}/con-contractors-create");
            exit;
        }

        if ($this->contractorModel->create($data)) {
            setSuccess('contractors created successfully!');
            header("Location: {$GLOBALS['base_url']}/con-contractors");
            exit;
        }

        setError('Failed to create contractors.');
        header("Location: {$GLOBALS['base_url']}/con-contractors-create");
        exit;
    }

    /* =====================
       Edit Form
    ====================== */
    public function edit($id)
    {
        $contractors = $this->contractorModel->find($id);
        if (!$contractors) {
            setError('contractors not found.');
            header("Location: {$GLOBALS['base_url']}/con-contractors");
            exit;
        }

        $this->view('phase2/contractors/edit-contractors', [
            'current_route' => 'con-contractors',
            'page_title'    => 'Edit contractors',
            'contractors'      => $contractors,
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

        if ($this->contractorModel->update($id, $data)) {
            setSuccess('contractors updated successfully!');
            header("Location: {$GLOBALS['base_url']}/con-contractors");
            exit;
        }

        setError('Failed to update contractors.');
        header("Location: {$GLOBALS['base_url']}/con-contractors-edit/$id");
        exit;
    }

    /* =====================
       Delete
    ====================== */
    public function delete()
    {
        $id = $_POST['id'] ?? 0;
        if ($this->contractorModel->delete($id)) {
            setSuccess('contractors deleted.');
        } else {
            setError('Delete failed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-contractors");
        exit;
    }
}
