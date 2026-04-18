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
        $this->db = $db;
        $this->boqModel = new Con_BOQ($this->db);
        $this->projectModel = new Project($this->db);
        $this->materialModel = new Con_Material($this->db);
    }

    public function index()
    {
        $boqs = $this->boqModel->getAll();
        $this->view('phase2/boq/index', [
            'current_route' => 'con-boq',
            'page_title'    => 'Bill of Quantities (BOQ)',
            'boqs'          => $boqs
        ]);
    }

    public function create()
    {
        $projects = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $this->view('phase2/boq/create', [
            'current_route' => 'con-boq',
            'page_title'    => 'Set New BOQ',
            'projects'      => $projects,
            'materials'     => $materials
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id' => $_POST['project_id'],
            'version'    => $_POST['version'] ?? '1.0',
            'status'     => $_POST['status'] ?? 'Draft',
            'created_by' => $_SESSION['user']['id']
        ];

        $boqId = $this->boqModel->createMaster($data);
        if ($boqId) {
            $materialIds = $_POST['material_id'] ?? [];
            $quantities  = $_POST['quantity'] ?? [];
            $unitCosts   = $_POST['unit_cost'] ?? [];

            $items = [];
            foreach ($materialIds as $index => $mid) {
                if (!empty($mid)) {
                    $items[] = [
                        'material_id' => $mid,
                        'quantity'    => $quantities[$index] ?? 0,
                        'unit_cost'   => $unitCosts[$index] ?? 0
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
        header("Location: {$GLOBALS['base_url']}/con-boq/create");
        exit;
    }

    public function show($id)
    {
        $boq = $this->boqModel->find($id);
        if (!$boq) {
            setError('BOQ not found.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        $items = $this->boqModel->getItems($id);

        $this->view('phase2/boq/show', [
            'current_route' => 'con-boq',
            'page_title'    => 'BOQ Details - ' . $boq['project_name'],
            'boq'           => $boq,
            'items'         => $items
        ]);
    }

    public function delete()
    {
        $id = $_POST['id'] ?? 0;
        if ($this->boqModel->delete($id)) {
            setSuccess('BOQ deleted.');
        } else {
            setError('Delete failed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-boq");
        exit;
    }
}
