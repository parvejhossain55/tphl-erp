<?php
namespace Phase2;

use Controller;
use Con_WorkingPlan;
use Project;
use Con_InitialEstimate;
use Con_DesignType;

class Con_WorkingPlanController extends Controller

{
    protected $db;
    protected $model;
    protected $projectModel;
    protected $budgetModel;
    protected $designTypeModel;

    public function __construct($db)
    {
        $this->db              = $db;
        $this->model           = new Con_WorkingPlan($db);
        $this->projectModel    = new Project($db);
        $this->budgetModel     = new Con_InitialEstimate($db);
        $this->designTypeModel = new Con_DesignType($db);
    }

    // =========================================================================
    // WORKING PLANS — LIST
    // =========================================================================

    public function index()
    {
        $plans = $this->model->getAll();

        $this->view('phase2/working_plans/working-plan-lists', [
            'current_route' => 'con-working-plans',
            'page_title'    => 'Project Working Plans',
            'plans'         => $plans,
        ]);
    }

    // =========================================================================
    // WORKING PLANS — CREATE
    // =========================================================================

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 22, 23])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        $budgets     = $this->budgetModel->getAll();
        $designTypes = $this->designTypeModel->getAll();

        $this->view('phase2/working_plans/create-working-plan', [
            'current_route' => 'con-working-plan-create',
            'page_title'    => 'New Working Plan Entry',
            'projects'      => $projects,
            'budgets'       => $budgets,
            'designTypes'   => $designTypes,
        ]);
    }

    // =========================================================================
    // WORKING PLANS — STORE
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
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'initial_budget_id' => (int)   ($_POST['initial_budget_id'] ?? 0),
            'design_type_id'    => (int)   ($_POST['design_type_id']    ?? 0),
            'year'              => (int)   ($_POST['year']              ?? date('Y')),
            'month'             => (int)   ($_POST['month']             ?? date('n')),
            'work_description'  =>          trim($_POST['work_description'] ?? ''),
            'planned_quantity'  => (float) ($_POST['planned_quantity']  ?? 0),
            'estimated_cost'    => (float) ($_POST['estimated_cost']    ?? 0),
            'created_by'        => (int)   ($_SESSION['user']['id']     ?? 0),
        ];

        if (!$data['project_id'] || !$data['initial_budget_id']) {
            setError('Project and Budget are required.');
            header("Location: {$GLOBALS['base_url']}/con-working-plan-create");
            exit;
        }

        $result = $this->model->createPlan($data);
        if ($result['ok']) {
            setSuccess('Working plan entry added successfully.');
            header("Location: {$GLOBALS['base_url']}/con-working-plans");
        } else {
            setError($result['error']);
            header("Location: {$GLOBALS['base_url']}/con-working-plan-create");
        }
        exit;
    }

    // =========================================================================
    // WORKING PLANS — EDIT
    // =========================================================================

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $plan = $this->model->find($id);
        if (!$plan) {
            setError('Plan entry not found.');
            header("Location: {$GLOBALS['base_url']}/con-working-plans");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 22, 23])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        $budgets     = $this->budgetModel->getAll();
        $designTypes = $this->designTypeModel->getAll();

        $this->view('phase2/working_plans/edit-working-plan', [
            'current_route' => 'con-working-plans',
            'page_title'    => 'Edit Working Plan',
            'plan'          => $plan,
            'projects'      => $projects,
            'budgets'       => $budgets,
            'designTypes'   => $designTypes,
        ]);
    }

    // =========================================================================
    // WORKING PLANS — UPDATE
    // =========================================================================

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $data = [
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'initial_budget_id' => (int)   ($_POST['initial_budget_id'] ?? 0),
            'design_type_id'    => (int)   ($_POST['design_type_id']    ?? 0),
            'year'              => (int)   ($_POST['year']              ?? date('Y')),
            'month'             => (int)   ($_POST['month']             ?? date('n')),
            'work_description'  =>          trim($_POST['work_description'] ?? ''),
            'planned_quantity'  => (float) ($_POST['planned_quantity']  ?? 0),
            'estimated_cost'    => (float) ($_POST['estimated_cost']    ?? 0),
        ];

        $result = $this->model->updatePlan($id, $data);
        if ($result['ok']) {
            setSuccess('Working plan updated.');
        } else {
            setError($result['error']);
        }
        header("Location: {$GLOBALS['base_url']}/con-working-plans");
        exit;
    }

    // =========================================================================
    // WORKING PLANS — DELETE
    // =========================================================================

    public function delete($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if ($this->model->deletePlan($id)) {
            setSuccess('Plan entry deleted.');
        } else {
            setError('Cannot delete — plan may be locked or not found.');
        }
        header("Location: {$GLOBALS['base_url']}/con-working-plans");
        exit;
    }

    // =========================================================================
    // BUDGET EXTENSIONS — LIST
    // =========================================================================

    public function budgetExtends()
    {
        $extends  = $this->model->getBudgetExtends();
        $projects = $this->projectModel->getAll();
        $budgets  = $this->budgetModel->getAll();
        $report   = $this->model->getBudgetStatusReport();

        $this->view('phase2/working_plans/budget-extend-lists', [
            'current_route' => 'con-budget-extends',
            'page_title'    => 'Budget Extension Requests',
            'extends'       => $extends,
            'projects'      => $projects,
            'budgets'       => $budgets,
            'report'        => $report,
        ]);
    }

    // =========================================================================
    // BUDGET EXTENSIONS — CREATE FORM
    // =========================================================================

    public function budgetExtendCreate()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 22, 23])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];
        $budgets  = $this->budgetModel->getAll();

        $this->view('phase2/working_plans/create-budget-extend', [
            'current_route' => 'con-budget-extends',
            'page_title'    => 'Request Budget Extension',
            'projects'      => $projects,
            'budgets'       => $budgets,
        ]);
    }

    // =========================================================================
    // BUDGET EXTENSIONS — STORE
    // =========================================================================

    public function budgetExtendStore()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'        => (int)   ($_POST['project_id']        ?? 0),
            'initial_budget_id' => (int)   ($_POST['initial_budget_id'] ?? 0),
            'requested_by'      => (int)   ($_SESSION['user']['id']     ?? 0),
            'title'             =>          trim($_POST['title']         ?? ''),
            'amount'            => (float) ($_POST['amount']            ?? 0),
            'reason'            =>          trim($_POST['reason']        ?? ''),
            'docs_link'         =>          trim($_POST['docs_link']     ?? ''),
        ];

        if (!$data['project_id'] || !$data['initial_budget_id'] || empty($data['title']) || $data['amount'] <= 0) {
            setError('All fields are required and amount must be > 0.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extend-create");
            exit;
        }

        // Handle file upload (PDF/Image evidence)
        $data['attachment_file'] = uploadFile('attachment_file', 'budget_extends');

        if ($this->model->createBudgetRequest($data)) {
            setSuccess('Budget extension request submitted successfully.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extends");
        } else {
            setError('Failed to submit request. Please try again.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extend-create");
        }
        exit;
    }

    // =========================================================================
    // BUDGET EXTENSIONS — APPROVE
    // =========================================================================

    public function approveExtend($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId  = (int)($_SESSION['user']['id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        $result = $this->model->approveBudgetExtend($id, $userId, $remarks);
        if ($result['ok']) {
            setSuccess('Budget extension request approved. Revised budget updated.');
        } else {
            setError($result['error']);
        }
        header("Location: {$GLOBALS['base_url']}/con-budget-extends");
        exit;
    }

    // =========================================================================
    // BUDGET EXTENSIONS — REJECT
    // =========================================================================

    public function rejectExtend($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId  = (int)($_SESSION['user']['id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($remarks)) {
            setError('Remarks are required when rejecting a request.');
            header("Location: {$GLOBALS['base_url']}/con-budget-extends");
            exit;
        }

        $result = $this->model->rejectBudgetExtend($id, $userId, $remarks);
        if ($result['ok']) {
            setSuccess('Budget extension request rejected.');
        } else {
            setError($result['error']);
        }
        header("Location: {$GLOBALS['base_url']}/con-budget-extends");
        exit;
    }

    // =========================================================================
    // AJAX — Project Data (working plans + budgets for a given project)
    // GET: ?project_id=N
    // Returns JSON: { work_plans: [...], budgets: [...] }
    // =========================================================================

    public function ajaxGetProjectData()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode(['work_plans' => [], 'budgets' => []]);
            exit;
        }

        // Working plans for this project
        $plans = $this->model->getByProject($projectId);
        $workPlans = array_map(function ($p) {
            return [
                'id'                => $p['id'],
                'initial_budget_id' => $p['initial_budget_id'],
                'label'             => ($p['year'] ?? '') . '/' . str_pad($p['month'] ?? '', 2, '0', STR_PAD_LEFT)
                                       . ' — ' . ($p['design_type_name'] ?? 'Plan')
                                       . ' — ' . mb_substr($p['work_description'] ?? '', 0, 40),
                'budget_label'      => '৳ ' . number_format($p['budget_total'] ?? 0, 2),
                'estimated_cost'    => $p['estimated_cost'] ?? 0,
                'work_description'  => $p['work_description'] ?? '',
                'design_type'       => $p['design_type_name'] ?? '',
            ];
        }, $plans);

        // Approved budgets for this project
        $stmt = $this->db->prepare(
            "SELECT id, total_estimated_cost, status
             FROM initial_estimate_budgets
             WHERE project_id = ? AND status = 'approved'
             ORDER BY id DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $rawBudgets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $budgets = array_map(function ($b) {
            return [
                'id'    => $b['id'],
                'label' => 'Budget #' . $b['id'] . ' — ৳ ' . number_format($b['total_estimated_cost'], 2),
                'total' => $b['total_estimated_cost'],
            ];
        }, $rawBudgets);

        echo json_encode(['work_plans' => $workPlans, 'budgets' => $budgets]);
        exit;
    }

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit; }
        $header = array_map('trim', fgetcsv($handle));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM (Excel CSV)
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);

            $projectName = trim($row['Project_Name'] ?? '');
            $year        = (int)($row['Year'] ?? 0);
            $month       = (int)($row['Month'] ?? 0);
            $workDesc    = trim($row['Work_Description'] ?? '');
            $plannedQty  = (float)($row['Planned_Quantity'] ?? 0);
            $estCost     = (float)($row['Estimated_Cost'] ?? 0);

            if (empty($projectName) || !$year || !$month || empty($workDesc)) {
                $errorCount++; $errors[] = "Missing required fields in row"; continue;
            }

            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            // Get first initial_budget for this project
            $stmtB = $db->prepare("SELECT id FROM initial_estimate_budgets WHERE project_id = ? LIMIT 1");
            $stmtB->bind_param('i', $projectId); $stmtB->execute();
            $budgetRow = $stmtB->get_result()->fetch_assoc();
            $budgetId = $budgetRow ? $budgetRow['id'] : 0;

            $ins = $db->prepare("INSERT INTO project_working_plans
                (project_id, initial_budget_id, design_type_id, year, month, work_description, planned_quantity, estimated_cost, created_by, approved_status)
                VALUES (?,?,1,?,?,?,?,?,?,'Draft')");
            $ins->bind_param('iiissddsi', $projectId, $budgetId, $year, $month, $workDesc, $plannedQty, $estCost, $userId);

            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "DB error: " . $db->error; }
        }
        fclose($handle);
        if ($errorCount > 0) { setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5))); }
        else { setSuccess("Successfully imported $successCount records."); }
        header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
    }
}
