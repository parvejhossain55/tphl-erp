<?php
namespace Phase2;

use Controller;
use Con_MaterialIssue;
use Con_Material;
use Con_Warehouse;
use Con_Stock;
use Project;

class Con_MaterialIssueController extends Controller
{
    protected $db;
    protected $issueModel;
    protected $materialModel;
    protected $warehouseModel;
    protected $stockModel;
    protected $projectModel;

    public function __construct($db)
    {
        $this->db             = $db;
        $this->issueModel     = new Con_MaterialIssue($this->db);
        $this->materialModel  = new Con_Material($this->db);
        $this->warehouseModel = new Con_Warehouse($this->db);
        $this->stockModel     = new Con_Stock($this->db);
        $this->projectModel   = new Project($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        $issues = $this->issueModel->getAll();

        $this->view('phase2/material-issues/issue-lists', [
            'current_route' => 'con-material-issues',
            'page_title'    => 'Material Issues (Stock OUT)',
            'issues'        => $issues,
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

        // Fetch projects that have completed material_receiving (GRNs)
        $sqlProjects = "SELECT DISTINCT p.* 
                        FROM projects p 
                        JOIN material_receiving mr ON mr.project_id = p.id
                        WHERE mr.overall_status IN ('Accepted', 'Partially Accepted')
                        ORDER BY p.name ASC";
        $pRes = $this->db->query($sqlProjects);
        $projects = $pRes ? $pRes->fetch_all(MYSQLI_ASSOC) : [];

        $warehouses = $this->warehouseModel->getAll();
        $materials  = $this->materialModel->getAll();
        $stocks     = $this->stockModel->getCurrentStock();

        // Fetch Working Plans and Targets
        $workingPlanModel = new \Con_WorkingPlan($this->db);
        $workPlans = $workingPlanModel->getAll();
        
        $targets = [];
        try {
            $tRes = $this->db->query("
                SELECT tf.*, p.name AS project_name 
                FROM target_fixed tf 
                LEFT JOIN projects p ON p.id = tf.project_id 
                ORDER BY tf.id DESC
            ");
            if ($tRes) $targets = $tRes->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {}

        // Fetch received materials per project
        $receivedMaterials = [];
        try {
            $rmRes = $this->db->query("
                SELECT mr.project_id, m.name AS material_name, m.unit, SUM(mri.received_quantity) AS total_received
                FROM material_receiving_items mri
                JOIN material_receiving mr ON mr.id = mri.receiving_id
                JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                JOIN con_materials m ON m.id = poi.material_id
                WHERE mri.status = 'Accepted' AND mr.overall_status IN ('Accepted', 'Partially Accepted')
                GROUP BY mr.project_id, m.id
            ");
            if ($rmRes) {
                while ($row = $rmRes->fetch_assoc()) {
                    $receivedMaterials[$row['project_id']][] = $row;
                }
            }
        } catch (\Exception $e) {}

        // Fetch contractors from contractors table
        $contractors = [];
        try {
            $cRes = $this->db->query(
                "SELECT id, name, phone, trade FROM contractors ORDER BY name ASC"
            );
            if ($cRes) $contractors = $cRes->fetch_all(MYSQLI_ASSOC);
        } catch (\Exception $e) {}

        $this->view('phase2/material-issues/create-issue', [
            'current_route' => 'con-material-issues-create',
            'page_title'    => 'New Material Issue',
            'projects'      => $projects,
            'warehouses'    => $warehouses,
            'materials'     => $materials,
            'stocks'        => $stocks,
            'workPlans'     => $workPlans,
            'targets'       => $targets,
            'contractors'   => $contractors,
            'receivedMaterials' => $receivedMaterials,
        ]);
    }

    // =========================================================================
    // STORE — one row per material
    // =========================================================================

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $data = [
            'project_id'   => (int)($_POST['project_id'] ?? 0),
            'warehouse_id' => (int)($_POST['warehouse_id'] ?? 0),
            'material_id'  => (int)($_POST['material_id'] ?? 0),
            'quantity'     => (float)($_POST['quantity'] ?? 0),
            'issued_to'    => trim($_POST['issued_to'] ?? ''),
            'purpose'      => trim($_POST['purpose'] ?? ''),
            'issued_by'    => $userId,
            'issue_date'   => $_POST['issue_date'] ?? date('Y-m-d'),
            'target_id'    => !empty($_POST['target_id']) ? (int)$_POST['target_id'] : null,
            'work_plan_id' => !empty($_POST['work_plan_id']) ? (int)$_POST['work_plan_id'] : null,
        ];

        if (empty($data['material_id']) || $data['quantity'] <= 0) {
            setError('Material and quantity are required.');
            header("Location: {$GLOBALS['base_url']}/con-material-issue-create");
            exit;
        }

        $id = $this->issueModel->create($data);
        if ($id) {
            setSuccess("Material Issue ISS-{$id} created. Stock deducted.");
            header("Location: {$GLOBALS['base_url']}/con-material-issue/{$id}");
        } else {
            setError('Failed to create issue. Check stock availability.');
            header("Location: {$GLOBALS['base_url']}/con-material-issue-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $issue = $this->issueModel->find($id);
        if (!$issue) {
            setError('Material Issue not found.');
            header("Location: {$GLOBALS['base_url']}/con-material-issues");
            exit;
        }

        $this->view('phase2/material-issues/show-issue', [
            'current_route' => 'con-material-issues',
            'page_title'    => 'Material Issue ISS-' . $id,
            'issue'         => $issue,
        ]);
    }
}
