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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 22])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];
        $workPlans = $this->workPlanModel->getAll();

        $rWOs = $this->db->query("
            SELECT wo.id, wo.title, wo.wo_number, wo.project_id, wo.work_plan_id, wo.description, dt.name AS design_name
            FROM con_work_orders wo
            LEFT JOIN project_drawings pd ON pd.id = wo.design_id
            LEFT JOIN design_types dt ON dt.id = pd.design_type_id
            WHERE wo.status != 'rejected' ORDER BY wo.id DESC
        ");
        $workOrders = $rWOs ? $rWOs->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/target-fixed/create-target', [
            'current_route' => 'con-targets',
            'page_title'    => 'New Site Target',
            'projects'      => $projects,
            'workPlans'     => $workPlans,
            'workOrders'    => $workOrders,
        ]);
    }

    // =========================================================================
    // STORE (POST)
    // =========================================================================
    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $isLabTest   = (int)($_POST['is_lab_test']   ?? 0);
        $labTestDate = ($isLabTest && !empty($_POST['lab_test_date']))
                        ? $_POST['lab_test_date'] : null;

        $data = [
            'project_id'        => $_POST['project_id']        ?? null,
            'work_plan_id'      => $_POST['work_plan_id']      ?? null,
            'work_order_id'     => !empty($_POST['work_order_id']) ? $_POST['work_order_id'] : null,
            'target_title'      => trim($_POST['target_title'] ?? ''),
            'user_id'           => $_SESSION['user']['id']     ?? null,
            'year'              => $_POST['year']              ?? date('Y'),
            'month'             => $_POST['month']             ?? date('n'),
            'week_number'       => !empty($_POST['week_number']) ? $_POST['week_number'] : null,
            'target_percentage' => $_POST['target_percentage'] ?? 0,
            'is_lab_test'       => $isLabTest,
            'lab_test_date'     => $labTestDate,
            'description'       => trim($_POST['description'] ?? ''),
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

        $currentUserId = (int)($_SESSION['user']['id']   ?? 0);
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $createdBy     = (int)($target['user_id'] ?? 0);

        // Roles 3 (Director), 15 (PM), 22 (PM Head) can approve as manager
        // Role 13 (QC) approves as QC step
        $managerRoles = [3, 15, 22];
        $qcRoles      = [13];

        $qcApproved      = !empty($target['approved_by_qc']);
        $approverRole    = null;
        $canApprove      = false;
        $alreadyApproved = false;

        if (in_array($currentRoleId, $managerRoles)) {
            $approverRole    = 'manager';
            $canApprove      = true;
            $alreadyApproved = !empty($target['approved_by_manager']);
            // $alreadyApproved = !empty($target['approved_by_pd']) || $alreadyApproved); // PD approval also counts for manager step
        }

        // Fetch creator info
        $creatorInfo = [];
        if ($createdBy) {
            $stmt = $this->db->prepare(" SELECT u.full_name, COALESCE(d.name,'') AS designation
                FROM   users u
                LEFT JOIN employees   e ON e.user_id = u.id
                LEFT JOIN designations d ON d.id = e.designation_id
                WHERE  u.id = ? LIMIT 1");
            $stmt->bind_param('i', $createdBy);
            $stmt->execute();
            $creatorInfo = $stmt->get_result()->fetch_assoc() ?? [];
        }

        // Fetch related weekly progress reports for this target
        $reportsStmt = $this->db->prepare(" SELECT w.*, u.full_name as reported_by_name 
            FROM weekly_progress_report w 
            LEFT JOIN users u ON u.id = w.created_by 
            WHERE w.target_id = ? 
            ORDER BY w.report_date DESC
        ");
        $reportsStmt->bind_param('i', $id);
        $reportsStmt->execute();
        $weeklyReports = $reportsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/target-fixed/show-target', [
            'current_route'   => 'con-targets',
            'page_title'      => 'Target Detail',
            'target'          => $target,
            'canApprove'      => $canApprove,
            'alreadyApproved' => $alreadyApproved,
            'approverRole'    => $approverRole,
            'qcApproved'      => $qcApproved,
            'creatorInfo'     => $creatorInfo,
            'currentRoleId'   => $currentRoleId,
            'weeklyReports'   => $weeklyReports,
        ]);
    }

    // =========================================================================
    // EDIT FORM
    // =========================================================================
    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $target    = $this->targetModel->find($id);
        if (!$target) {
            setError('Target not found.');
            header("Location: {$GLOBALS['base_url']}/con-targets");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $filter = "1=1";
        if (!in_array($roleId, [1, 3, 22])) {
            $filter = "(pm_id = $userId OR site_engineer_id = $userId OR landman_id = $userId OR struct_engineer_id = $userId OR draftsman_id = $userId OR architect_id = $userId OR estimator_id = $userId OR qc_id = $userId OR operator_id = $userId OR created_by = $userId)";
        }

        $rProjs = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND $filter ORDER BY name ASC");
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];
        $workPlans = $this->workPlanModel->getAll();

        $rWOs = $this->db->query("
            SELECT wo.id, wo.title, wo.wo_number, wo.project_id, wo.work_plan_id, wo.description, dt.name AS design_name
            FROM con_work_orders wo
            LEFT JOIN project_drawings pd ON pd.id = wo.design_id
            LEFT JOIN design_types dt ON dt.id = pd.design_type_id
            WHERE wo.status != 'rejected' ORDER BY wo.id DESC
        ");
        $workOrders = $rWOs ? $rWOs->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/target-fixed/edit-target', [
            'current_route' => 'con-targets',
            'page_title'    => 'Edit Target',
            'target'        => $target,
            'projects'      => $projects,
            'workPlans'     => $workPlans,
            'workOrders'    => $workOrders,
        ]);
    }

    // =========================================================================
    // UPDATE (POST)
    // =========================================================================
    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $isLabTest   = (int)($_POST['is_lab_test']   ?? 0);
        $labTestDate = ($isLabTest && !empty($_POST['lab_test_date']))
                        ? $_POST['lab_test_date'] : null;

        $data = [
            'project_id'        => $_POST['project_id']        ?? null,
            'work_plan_id'      => $_POST['work_plan_id']      ?? null,
            'work_order_id'     => !empty($_POST['work_order_id']) ? $_POST['work_order_id'] : null,
            'target_title'      => trim($_POST['target_title'] ?? ''),
            'year'              => $_POST['year']              ?? date('Y'),
            'month'             => $_POST['month']             ?? date('n'),
            'week_number'       => !empty($_POST['week_number']) ? $_POST['week_number'] : null,
            'target_percentage' => $_POST['target_percentage'] ?? 0,
            'is_lab_test'       => $isLabTest,
            'lab_test_date'     => $labTestDate,
            'description'       => trim($_POST['description'] ?? ''),
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
    // PM (role 22) approves step 'manager'
    // SE (role 12) creates and CANNOT approve
    // =========================================================================
    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id']   ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Role guard — PM (15), Director (3), PM Head (22) can approve
        $allowedRoles = [3, 15, 22];
        if (!in_array($roleId, $allowedRoles)) {
            setError('You do not have permission to approve targets.');
            header("Location: {$GLOBALS['base_url']}/con-target/{$id}");
            exit;
        }

        $step = 'manager';

        if ($this->targetModel->approveStep($id, $step, $userId)) {

            // Manager: store who approved and when
            $upd = $this->db->prepare(
                "UPDATE target_fixed SET manager_approved_by_id = ?, manager_approved_at = NOW() WHERE id = ?"
            );
            if ($upd) {
                $upd->bind_param('ii', $userId, $id);
                $upd->execute();
            }
                setSuccess('Approval recorded successfully!');
            }

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
            "SELECT id, target_title, year, month, week_number, gain_percentage
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

    // ─── CSV Import ────────────────────────────────────────────────────────
    public function importCsv() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit;
        }
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            setError('Please upload a valid CSV file.');
            header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit;
        }
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) { setError('Cannot open file.'); header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit; }
        $header = array_map('trim', fgetcsv($handle));
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // Strip UTF-8 BOM (Excel CSV)
        $successCount = 0; $errorCount = 0; $errors = [];
        $db = $this->db;
        $userId = $_SESSION['user']['id'] ?? 0;

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty(array_filter($data))) continue;
            if (count($header) !== count($data)) continue;
            $row = array_combine($header, $data);

            $projectName   = trim($row['Project_Name'] ?? '');
            $targetTitle   = trim($row['Target_Title'] ?? '');
            $year          = (int)($row['Year'] ?? 0);
            $month         = (int)($row['Month'] ?? 0);
            $weekNumber    = !empty($row['Week_Number']) ? (int)$row['Week_Number'] : null;
            $targetPct     = (float)($row['Target_Percentage'] ?? 0);

            if (empty($projectName) || !$year || !$month || $targetPct <= 0) {
                $errorCount++; $errors[] = "Missing required fields"; continue;
            }

            $stmt = $db->prepare("SELECT id FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->bind_param('s', $projectName); $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) { $errorCount++; $errors[] = "Project '$projectName' not found"; continue; }
            $projectId = $res->fetch_assoc()['id'];

            if ($weekNumber !== null) {
                $ins = $db->prepare("INSERT INTO target_fixed
                    (project_id, user_id, target_title, year, month, week_number, target_percentage, is_lab_test, approved_by_site_eng, approved_by_qc, approved_by_pc, approved_by_pd, approved_by_manager)
                    VALUES (?,?,?,?,?,?,?,0,0,0,0,0,0)");
                $ins->bind_param('iisiiid', $projectId, $userId, $targetTitle, $year, $month, $weekNumber, $targetPct);
            } else {
                $ins = $db->prepare("INSERT INTO target_fixed
                    (project_id, user_id, target_title, year, month, target_percentage, is_lab_test, approved_by_site_eng, approved_by_qc, approved_by_pc, approved_by_pd, approved_by_manager)
                    VALUES (?,?,?,?,?,?,0,0,0,0,0,0)");
                $ins->bind_param('iisiid', $projectId, $userId, $targetTitle, $year, $month, $targetPct);
            }

            if ($ins->execute()) { $successCount++; } else { $errorCount++; $errors[] = "DB error: " . $db->error; }
        }
        fclose($handle);
        if ($errorCount > 0) { setError("Imported $successCount. Failed $errorCount. " . implode(' | ', array_slice($errors, 0, 5))); }
        else { setSuccess("Successfully imported $successCount records."); }
        header('Location: ' . $GLOBALS['base_url'] . '/con-targets'); exit;
    }
}
