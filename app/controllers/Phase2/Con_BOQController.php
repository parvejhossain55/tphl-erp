<?php
namespace Phase2;

use Controller;
use Con_BOQ;
use Project;
use Con_Material;

class Con_BOQController extends Controller
{
    protected $db;
    protected $boqModel;
    protected $projectModel;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->boqModel      = new Con_BOQ($this->db);
        $this->projectModel  = new Project($this->db);
        $this->materialModel = new Con_Material($this->db);
    }

    // =========================================================================
    // BOQ — LIST
    // =========================================================================

    public function index()
    {
        $boqs = $this->boqModel->getAll();

        $this->view('phase2/boq/boq-lists', [
            'current_route' => 'con-boq',
            'page_title'    => 'Bill of Quantities (BOQ)',
            'boqs'          => $boqs,
        ]);
    }

    // =========================================================================
    // BOQ — CREATE
    // =========================================================================

    public function create()
    {
        $projects  = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $this->view('phase2/boq/create-boq', [
            'current_route' => 'con-boq-create',
            'page_title'    => 'New Bill of Quantities',
            'projects'      => $projects,
            'materials'     => $materials,
        ]);
    }

    // =========================================================================
    // BOQ — STORE
    // =========================================================================

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'version'    => trim($_POST['version'] ?? '1.0'),
            'status'     => $_POST['status'] ?? 'Draft',
            'created_by' => (int)($_SESSION['user']['id'] ?? 0),
        ];

        if (!$data['project_id']) {
            setError('Project is required.');
            header("Location: {$GLOBALS['base_url']}/con-boq-create");
            exit;
        }

        $boqId = $this->boqModel->createMaster($data);
        if ($boqId) {
            $materialIds = $_POST['material_id'] ?? [];
            $quantities  = $_POST['quantity']    ?? [];
            $unitCosts   = $_POST['unit_cost']   ?? [];

            $items = [];
            foreach ($materialIds as $index => $mid) {
                if (!empty($mid) && ($quantities[$index] ?? 0) > 0) {
                    $items[] = [
                        'material_id' => (int)$mid,
                        'quantity'    => (float)$quantities[$index],
                        'unit_cost'   => (float)($unitCosts[$index] ?? 0),
                    ];
                }
            }

            if (!empty($items)) {
                $this->boqModel->addItems($boqId, $items);
            }

            setSuccess('BOQ created successfully.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        setError('Failed to create BOQ.');
        header("Location: {$GLOBALS['base_url']}/con-boq-create");
        exit;
    }

    // =========================================================================
    // BOQ — SHOW
    // =========================================================================

    public function show($id)
    {
        $boq = $this->boqModel->find($id);
        if (!$boq) {
            setError('BOQ not found.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        $items = $this->boqModel->getItems($id);

        $this->view('phase2/boq/show-boq', [
            'current_route' => 'con-boq',
            'page_title'    => 'BOQ Details — ' . $boq['project_name'],
            'boq'           => $boq,
            'items'         => $items,
        ]);
    }

    // =========================================================================
    // BOQ — EDIT
    // =========================================================================

    public function edit($id)
    {
        $boq = $this->boqModel->find($id);
        if (!$boq) {
            setError('BOQ not found.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        if ($boq['status'] === 'Approved') {
            setError('Cannot edit an Approved BOQ.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        $items     = $this->boqModel->getItems($id);
        $projects  = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $this->view('phase2/boq/edit-boq', [
            'current_route' => 'con-boq',
            'page_title'    => 'Edit BOQ — ' . $boq['project_name'],
            'boq'           => $boq,
            'items'         => $items,
            'projects'      => $projects,
            'materials'     => $materials,
        ]);
    }

    // =========================================================================
    // BOQ — UPDATE
    // =========================================================================

    public function update($id)
    {
        $data = [
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'version'    => trim($_POST['version'] ?? '1.0'),
        ];

        $this->boqModel->updateMaster($id, $data);

        // Replace items
        $materialIds = $_POST['material_id'] ?? [];
        $quantities  = $_POST['quantity']    ?? [];
        $unitCosts   = $_POST['unit_cost']   ?? [];

        $items = [];
        foreach ($materialIds as $index => $mid) {
            if (!empty($mid) && ($quantities[$index] ?? 0) > 0) {
                $items[] = [
                    'material_id' => (int)$mid,
                    'quantity'    => (float)$quantities[$index],
                    'unit_cost'   => (float)($unitCosts[$index] ?? 0),
                ];
            }
        }

        if (!empty($items)) {
            $this->boqModel->addItems($id, $items);
        }

        setSuccess('BOQ updated successfully.');
        header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
        exit;
    }

    // =========================================================================
    // BOQ — APPROVE / SUBMIT / REJECT
    // =========================================================================

    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $status = $_POST['status'] ?? 'Approved';
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        if (!in_array($status, ['Submitted', 'Approved', 'Rejected'])) {
            setError('Invalid status.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        if ($this->boqModel->updateStatus($id, $status, $userId)) {
            setSuccess("BOQ status updated to {$status}.");
        } else {
            setError('Failed to update status.');
        }
        header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
        exit;
    }

    // =========================================================================
    // BOQ — DELETE
    // =========================================================================

    public function delete()
    {
        $id = (int)($_POST['id'] ?? 0);
        if ($this->boqModel->delete($id)) {
            setSuccess('BOQ deleted.');
        } else {
            setError('Cannot delete — BOQ may be Approved or not found.');
        }
        header("Location: {$GLOBALS['base_url']}/con-boq");
        exit;
    }
}
