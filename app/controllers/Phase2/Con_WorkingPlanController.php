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

        // Pre-load work_types grouped by project_id for instant dropdown fill (no extra AJAX)
        $wtQuery = $this->db->query(
            "SELECT DISTINCT b.project_id, wt.id, wt.work_type_name, dt.name AS design_type_name
             FROM work_types wt
             LEFT JOIN design_types dt ON dt.id = wt.design_type_id
             JOIN initial_estimate_items i ON i.work_type_id = wt.id
             JOIN initial_estimate_budgets b ON b.id = i.budget_id
             UNION
             SELECT DISTINCT b.project_id, wt.id, wt.work_type_name, dt.name AS design_type_name
             FROM work_types wt
             LEFT JOIN design_types dt ON dt.id = wt.design_type_id
             JOIN initial_estimate_labors l ON l.work_type_id = wt.id
             JOIN initial_estimate_budgets b ON b.id = l.budget_id
             ORDER BY work_type_name ASC"
        );
        $workTypesByProject = [];
        if ($wtQuery) {
            while ($row = $wtQuery->fetch_assoc()) {
                $pid = $row['project_id'];
                $workTypesByProject[$pid][] = [
                    'id'    => $row['id'],
                    'label' => $row['work_type_name'] . ' (' . ($row['design_type_name'] ?: 'N/A') . ')'
                ];
            }
        }

        $this->view('phase2/working_plans/create-working-plan', [
            'current_route'       => 'con-working-plan-create',
            'page_title'          => 'New Working Plan Entry',
            'projects'            => $projects,
            'budgets'             => $budgets,
            'workTypesByProject'  => $workTypesByProject,
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
            'work_type_id'      => (int)   ($_POST['work_type_id']      ?? 0),
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
            'work_type_id'      => (int)   ($_POST['work_type_id']      ?? 0),
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

        // Work Types from estimate items & labors for this project
        $stmtWt = $this->db->prepare(
            "SELECT DISTINCT wt.id, wt.work_type_name AS work_type_name, dt.name AS design_type_name
             FROM work_types wt
             LEFT JOIN design_types dt ON dt.id = wt.design_type_id
             WHERE wt.id IN (
                 SELECT i.work_type_id FROM initial_estimate_items i JOIN initial_estimate_budgets b ON i.budget_id = b.id WHERE b.project_id = ?
                 UNION
                 SELECT l.work_type_id FROM initial_estimate_labors l JOIN initial_estimate_budgets b ON l.budget_id = b.id WHERE b.project_id = ?
             )
             ORDER BY wt.work_type_name ASC"
        );
        $stmtWt->bind_param('ii', $projectId, $projectId);
        $stmtWt->execute();
        $workTypesRaw = $stmtWt->get_result()->fetch_all(MYSQLI_ASSOC);

        $workTypes = array_map(function($w) {
            return [
                'id' => $w['id'],
                'label' => $w['work_type_name'] . ' (' . ($w['design_type_name'] ?: 'No Design') . ')'
            ];
        }, $workTypesRaw);

        echo json_encode([
            'work_plans' => $workPlans, 
            'budgets'    => $budgets,
            'work_types' => $workTypes
        ]);
        exit;
    }

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
        }

        // Support Sample Download
        if (isset($_GET['sample'])) {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="sample_working_plans.csv"');
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            echo "Project_Name,Work_Type_Name,Year,Month,Work_Description,Planned_Quantity,Estimated_Cost,Created_By_Email,Approved_Status,Approved_By_Email\n";
            echo "The Prime Heights,Soil Analysis & Footing Design,2025,6,Foundation layout completion,1,55000,manager@example.com,approved,admin@example.com\n";
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
        }

        // ── Read file with UTF-8 support (Bengali fix) ────────────────────────
        $raw = file_get_contents($_FILES['csv_file']['tmp_name']);
        $raw = ltrim($raw, "\xEF\xBB\xBF"); // strip BOM
        $raw = str_replace("\r\n", "\n", $raw);
        $raw = str_replace("\r", "\n", $raw);
        $lines = array_filter(explode("\n", $raw), fn($l) => trim($l) !== '');
        $lines = array_values($lines);

        if (empty($lines)) {
            setError('CSV file is empty.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); exit;
        }

        $headerRow = array_map('trim', str_getcsv(array_shift($lines)));
        
        $db = $this->db;
        $db->set_charset('utf8mb4');
        $sessionUserId = $_SESSION['user']['id'] ?? 0;

        $successCount = 0; $errorCount = 0; $errors = [];

        // Helpers
        $resolveProject = function ($name) use ($db) {
            if (!$name) return null;
            $s = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $s->bind_param('s', $name); $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : null;
        };

        $resolveBudget = function ($projectId) use ($db) {
            if (!$projectId) return null;
            $s = $db->prepare("SELECT id FROM initial_estimate_budgets WHERE project_id = ? LIMIT 1");
            $s->bind_param('i', $projectId); $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : null;
        };

        $resolveWorkType = function ($name) use ($db) {
            if (!$name) return null;
            $s = $db->prepare("SELECT id FROM work_types WHERE name = ? LIMIT 1");
            $s->bind_param('s', $name); $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : null;
        };

        $resolveUserEmail = function ($email) use ($db) {
            if (!$email) return null;
            $s = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $s->bind_param('s', $email); $s->execute();
            $r = $s->get_result()->fetch_assoc();
            return $r ? (int)$r['id'] : null;
        };

        foreach ($lines as $lineNo => $line) {
            $cols = array_map('trim', str_getcsv($line));
            $hCount = count($headerRow);
            if (count($cols) < $hCount) $cols = array_pad($cols, $hCount, '');
            elseif (count($cols) > $hCount) $cols = array_slice($cols, 0, $hCount);
            
            $row = array_combine($headerRow, $cols);
            if ($row === false) continue;

            $projectName    = trim($row['Project_Name'] ?? '');
            $workTypeName   = trim($row['Work_Type_Name'] ?? '');
            $year           = (int)($row['Year'] ?? 0);
            $month          = (int)($row['Month'] ?? 0);
            $workDesc       = trim($row['Work_Description'] ?? '');
            $plannedQty     = (float)($row['Planned_Quantity'] ?? 0);
            $estCost        = (float)($row['Estimated_Cost'] ?? 0);
            $createdEmail   = trim($row['Created_By_Email'] ?? '');
            $approvedStatus = trim($row['Approved_Status'] ?? 'Draft');
            $approvedEmail  = trim($row['Approved_By_Email'] ?? '');

            if (!$projectName || !$workDesc) {
                $errorCount++; $errors[] = "Line " . ($lineNo + 2) . ": Missing Project_Name or Work_Description"; continue;
            }

            $projectId = $resolveProject($projectName);
            if (!$projectId) {
                $errorCount++; $errors[] = "Line " . ($lineNo + 2) . ": Project '$projectName' not found"; continue;
            }

            $budgetId = $resolveBudget($projectId);
            if (!$budgetId) {
                $errorCount++; $errors[] = "Line " . ($lineNo + 2) . ": No initial budget found for project '$projectName'"; continue;
            }

            $workTypeId = $resolveWorkType($workTypeName) ?: 1; // fallback if not found

            $createdById  = $resolveUserEmail($createdEmail) ?: $sessionUserId;
            $approvedById = $resolveUserEmail($approvedEmail) ?: null;

            $ins = $db->prepare("INSERT INTO project_working_plans
                (project_id, initial_budget_id, work_type_id, year, month, work_description, planned_quantity, estimated_cost, created_by, approved_status, approved_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
            $ins->bind_param('iiiissddisi', 
                $projectId, $budgetId, $workTypeId, $year, $month, 
                $workDesc, $plannedQty, $estCost, $createdById, $approvedStatus, $approvedById
            );

            if ($ins->execute()) {
                $successCount++;
            } else {
                $errorCount++; $errors[] = "Line " . ($lineNo + 2) . ": DB error: " . $ins->error;
            }
        }

        if ($errorCount > 0) { 
            setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5))); 
        } else { 
            setSuccess("Successfully imported $successCount records."); 
        }
        header('Location: ' . $GLOBALS['base_url'] . '/con-working-plans'); 
        exit;
    }
}
