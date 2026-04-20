<?php
namespace Phase2;

use Controller;
use Con_TargetFixed;
use Con_WorkingPlan;

class Con_TargetFixedController extends Controller

{
    protected $db;
    protected $targetModel;
    protected $workPlanModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->targetModel   = new Con_TargetFixed($this->db);
        $this->workPlanModel = new Con_WorkingPlan($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $targets = $this->targetModel->getAll();

        $this->view('phase2/target-fixed/target-lists', [
            'current_route' => 'con-targets',
            'page_title'    => 'Site Targets',
            'targets'       => $targets,
        ]);
    }

    // =========================================================================
    // CREATE FORM
    // =========================================================================
    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        $workPlans = $this->workPlanModel->getAll();

        $this->view('phase2/target-fixed/create-target', [
            'current_route' => 'con-targets',
            'page_title'    => 'New Site Target',
            'projects'      => $projects,
            'workPlans'     => $workPlans,
        ]);
    }

    // =========================================================================
    // STORE (POST)
    // =========================================================================
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'         => $_POST['project_id']         ?? null,
            'work_plan_id'       => $_POST['work_plan_id']       ?? null,
            'target_title'       => trim($_POST['target_title']  ?? ''),
            'user_id'            => $_SESSION['user']['id']      ?? null,
            'year'               => $_POST['year']               ?? date('Y'),
            'month'              => $_POST['month']              ?? date('n'),
            'week_number'        => !empty($_POST['week_number']) ? $_POST['week_number'] : null,
            'target_percentage'  => $_POST['target_percentage']  ?? 0,
        ];

        if (empty($data['project_id']) || empty($data['work_plan_id']) || empty($data['target_title'])) {
            setError('Project, Working Plan and Target Title are required.');
            header("Location: {$GLOBALS['base_url']}/con-target-create");
            exit;
        }

        $id = $this->targetModel->create($data);

        if ($id) {
            setSuccess('Target created successfully!');
            header("Location: {$GLOBALS['base_url']}/con-target/{$id}");
        } else {
            setError('Failed to create target.');
            header("Location: {$GLOBALS['base_url']}/con-target-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW DETAIL
    // =========================================================================
    public function show($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $target = $this->targetModel->find($id);
        if (!$target) {
            setError('Target not found.');
            header("Location: {$GLOBALS['base_url']}/con-targets");
            exit;
        }

        $this->view('phase2/target-fixed/show-target', [
            'current_route' => 'con-targets',
            'page_title'    => 'Target Detail',
            'target'        => $target,
        ]);
    }

    // =========================================================================
    // EDIT FORM
    // =========================================================================
    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $target    = $this->targetModel->find($id);
        if (!$target) {
            setError('Target not found.');
            header("Location: {$GLOBALS['base_url']}/con-targets");
            exit;
        }

        $projects  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name")->fetch_all(MYSQLI_ASSOC);
        $workPlans = $this->workPlanModel->getAll();

        $this->view('phase2/target-fixed/edit-target', [
            'current_route' => 'con-targets',
            'page_title'    => 'Edit Target',
            'target'        => $target,
            'projects'      => $projects,
            'workPlans'     => $workPlans,
        ]);
    }

    // =========================================================================
    // UPDATE (POST)
    // =========================================================================
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id'        => $_POST['project_id']        ?? null,
            'work_plan_id'      => $_POST['work_plan_id']      ?? null,
            'target_title'      => trim($_POST['target_title'] ?? ''),
            'year'              => $_POST['year']              ?? date('Y'),
            'month'             => $_POST['month']             ?? date('n'),
            'week_number'       => !empty($_POST['week_number']) ? $_POST['week_number'] : null,
            'target_percentage' => $_POST['target_percentage'] ?? 0,
        ];

        if (empty($data['project_id']) || empty($data['work_plan_id']) || empty($data['target_title'])) {
            setError('All required fields must be filled.');
            header("Location: {$GLOBALS['base_url']}/con-target-edit/{$id}");
            exit;
        }

        if ($this->targetModel->update($id, $data)) {
            setSuccess('Target updated successfully!');
        } else {
            setError('Update failed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-target/{$id}");
        exit;
    }

    // =========================================================================
    // UPDATE GAIN (QC / Site Engineer updates actual progress)
    // POST: gain_percentage, photo (file), field_test_details, lab_test_result
    // =========================================================================
    public function updateGain($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $gain          = $_POST['gain_percentage']   ?? 0;
        $fieldDetails  = trim($_POST['field_test_details'] ?? '');
        $labResult     = trim($_POST['lab_test_result']    ?? '');
        $photoPath     = null;

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $uploadDir = __DIR__ . '/../../../public/uploads/target-photos/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename  = time() . '_' . basename($_FILES['photo']['name']);
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename)) {
                $photoPath = 'uploads/target-photos/' . $filename;
            }
        }

        if ($this->targetModel->updateGain($id, $gain, $photoPath, $fieldDetails ?: null, $labResult ?: null)) {
            setSuccess('Gain percentage updated!');
        } else {
            setError('Failed to update gain.');
        }
        header("Location: {$GLOBALS['base_url']}/con-target/{$id}");
        exit;
    }

    // =========================================================================
    // APPROVE A STEP
    // POST: step = 'site_eng' | 'qc' | 'pc' | 'pd' | 'manager'
    // =========================================================================
    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $step   = $_POST['step'] ?? '';
        $userId = $_SESSION['user']['id'] ?? null;

        $allowed = ['site_eng', 'qc', 'pc', 'pd', 'manager'];
        if (!in_array($step, $allowed)) {
            setError('Invalid approval step.');
            header("Location: {$GLOBALS['base_url']}/con-target/{$id}");
            exit;
        }

        if ($this->targetModel->approveStep($id, $step, $userId)) {
            setSuccess('Approval recorded successfully!');
        } else {
            setError('Approval failed.');
        }
        header("Location: {$GLOBALS['base_url']}/con-target/{$id}");
        exit;
    }

    // =========================================================================
    // AJAX — Targets by Project
    // GET: ?project_id=N
    // Returns JSON: { targets: [{id, target_title, year, week_number, gain_percentage}] }
    // =========================================================================
    public function ajaxGetProjectTargets()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode(['targets' => []]);
            exit;
        }

        $stmt = $this->db->prepare(
            "SELECT id, target_title, year, month, week_number, gain_percentage, status
             FROM target_fixed
             WHERE project_id = ?
             ORDER BY year DESC, month DESC, week_number ASC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['targets' => $rows]);
        exit;
    }
}
