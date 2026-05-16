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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        // Fetch projects that have Accepted material_receiving GRNs
        // material_receiving has no project_id — chain through PO → PR → project
        $sqlProjects = "SELECT DISTINCT p.*
                        FROM projects p
                        JOIN purchase_requests pr ON pr.project_id = p.id
                        JOIN purchase_orders   po ON po.request_id  = pr.id
                        JOIN material_receiving mr ON mr.purchase_order_id = po.id
                        WHERE mr.overall_status IN ('Accepted', 'Partially Accepted')
                        ORDER BY p.name ASC";
        $pRes    = $this->db->query($sqlProjects);
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
                SELECT pr.project_id, m.name AS material_name, m.unit,
                       SUM(mri.received_quantity) AS total_received
                FROM material_receiving_items mri
                JOIN material_receiving mr  ON mr.id  = mri.receiving_id
                JOIN purchase_orders   po  ON po.id  = mr.purchase_order_id
                JOIN purchase_requests pr  ON pr.id  = po.request_id
                JOIN con_materials     m   ON m.id   = mri.material_id
                WHERE mri.status = 'Accepted'
                  AND mr.overall_status IN ('Accepted', 'Partially Accepted')
                GROUP BY pr.project_id, m.id
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
            'current_route' => 'con-material-issue-create',
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
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
            'issued_to'    => (int)($_POST['issued_to'] ?? 0),
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
            // Trigger email to SE and Operator
            require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
            $mailService = new \ProjectWorkflowMailService($this->db);
            $pInfo = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
            $pInfo->bind_param('i', $data['project_id']);
            $pInfo->execute();
            $pRow = $pInfo->get_result()->fetch_assoc();
            $cInfo = $this->db->prepare("SELECT name FROM contractors WHERE id = ?");
            $cInfo->bind_param('i', $data['issued_to']);
            $cInfo->execute();
            $cRow = $cInfo->get_result()->fetch_assoc();
            
            if ($pRow) {
                $contractorName = $cRow ? $cRow['name'] : 'Contractor';
                $mailService->notifyAfterMaterialIssue($data['project_id'], $pRow['name'], $id, $contractorName);
            }

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

    // =========================================================================
    // CSV IMPORT
    // =========================================================================

    public function importCsv()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission.');
            header("Location: {$GLOBALS['base_url']}/con-material-issues");
            exit;
        }

        if (empty($_FILES['csv_file']['tmp_name'])) {
            setError('No CSV file uploaded.');
            header("Location: {$GLOBALS['base_url']}/con-material-issues");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        fgetcsv($handle); // skip header row

        // Pre-load lookup maps
        $projectMap    = [];
        $warehouseMap  = [];
        $materialMap   = [];
        $contractorMap = [];

        foreach ($this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL")->fetch_all(MYSQLI_ASSOC) as $r)
            $projectMap[$r['name']] = $r['id'];
        foreach ($this->db->query("SELECT id, name FROM con_warehouses")->fetch_all(MYSQLI_ASSOC) as $r)
            $warehouseMap[$r['name']] = $r['id'];
        foreach ($this->db->query("SELECT id, name FROM con_materials")->fetch_all(MYSQLI_ASSOC) as $r)
            $materialMap[$r['name']] = $r['id'];
        foreach ($this->db->query("SELECT id, name FROM contractors")->fetch_all(MYSQLI_ASSOC) as $r)
            $contractorMap[$r['name']] = $r['id'];

        $inserted = 0;
        $errors   = [];
        $rowNum   = 1;

        $stockModel = new \Con_Stock($this->db);

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $row[0] = ltrim($row[0], "\xEF\xBB\xBF");
            if (count($row) < 11) {
                $errors[] = "Row {$rowNum}: need 11 columns (got " . count($row) . ").";
                continue;
            }

            [$projName, $whName, $matName, $qty, $contractorName,
             $purpose, $targetTitle, $year, $month, $weekNum, $issueDate] = array_map('trim', $row);

            // ── Resolve IDs ───────────────────────────────────────────────────
            $projectId    = $projectMap[$projName]       ?? null;
            $warehouseId  = $warehouseMap[$whName]       ?? null;
            $materialId   = $materialMap[$matName]       ?? null;
            $contractorId = $contractorMap[$contractorName] ?? null;

            if (!$projectId)    { $errors[] = "Row {$rowNum}: Project '{$projName}' not found."; continue; }
            if (!$warehouseId)  { $errors[] = "Row {$rowNum}: Warehouse '{$whName}' not found."; continue; }
            if (!$materialId)   { $errors[] = "Row {$rowNum}: Material '{$matName}' not found."; continue; }
            if (!$contractorId) { $errors[] = "Row {$rowNum}: Contractor '{$contractorName}' not found."; continue; }

            $qty     = (float)$qty;
            $year    = (int)$year;
            $month   = (int)$month;
            $weekNum = (int)$weekNum;

            // ── Normalize issue_date → YYYY-MM-DD ─────────────────────────────
            // Handles: 5/7/2026 | 07/05/2026 | 2026-05-07 | 07-05-2026
            $issueDate = trim($issueDate);
            if (preg_match('#^(\d{1,2})[/\-](\d{1,2})[/\-](\d{4})$#', $issueDate, $m)) {
                // M/D/YYYY or D/M/YYYY → treat as M/D/YYYY (as Excel exports)
                $issueDate = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[1], (int)$m[2]);
            } elseif (preg_match('#^(\d{4})[/\-](\d{1,2})[/\-](\d{1,2})$#', $issueDate, $m)) {
                // Already YYYY-MM-DD or YYYY/MM/DD
                $issueDate = sprintf('%04d-%02d-%02d', (int)$m[1], (int)$m[2], (int)$m[3]);
            } else {
                // Try PHP's strtotime as fallback
                $ts = strtotime($issueDate);
                if ($ts && $ts > 0) {
                    $issueDate = date('Y-m-d', $ts);
                } else {
                    $errors[] = "Row {$rowNum}: Invalid issue_date '{$issueDate}' — use YYYY-MM-DD or M/D/YYYY.";
                    continue;
                }
            }

            // ── Stock availability check ──────────────────────────────────────
            $stockRow = $this->db->query(
                "SELECT current_quantity FROM con_stocks
                 WHERE material_id = {$materialId} AND warehouse_id = {$warehouseId}
                 LIMIT 1"
            );
            $available = $stockRow && ($sr = $stockRow->fetch_assoc()) ? (float)$sr['current_quantity'] : 0;

            if ($available < $qty) {
                $errors[] = "Row {$rowNum}: Not enough stock for '{$matName}' "
                           . "in '{$whName}' — available: " . number_format($available, 3)
                           . ", requested: " . number_format($qty, 3) . ".";
                continue;
            }

            // ── BEGIN TRANSACTION per row ─────────────────────────────────────
            $this->db->begin_transaction();
            try {
                // Find latest working_plan for this project
                $wpRes      = $this->db->query(
                    "SELECT id FROM project_working_plans
                     WHERE project_id = {$projectId}
                     ORDER BY id DESC LIMIT 1"
                );
                $workPlanId = ($wpRes && $wp = $wpRes->fetch_assoc()) ? (int)$wp['id'] : null;

                // Find or create target_fixed
                $safeTitle = $this->db->real_escape_string($targetTitle);
                $tRes = $this->db->query(
                    "SELECT id FROM target_fixed
                     WHERE project_id = {$projectId} AND target_title = '{$safeTitle}'
                     LIMIT 1"
                );
                if ($tRes && $tRes->num_rows > 0) {
                    $targetId = (int)$tRes->fetch_assoc()['id'];
                } else {
                    $tStmt = $this->db->prepare(
                        "INSERT INTO target_fixed
                            (project_id, work_plan_id, target_title, user_id, year, month, week_number, target_percentage)
                         VALUES (?, ?, ?, ?, ?, ?, ?, 0)"
                    );
                    $tStmt->bind_param('iisiiii',
                        $projectId, $workPlanId, $targetTitle,
                        $userId, $year, $month, $weekNum
                    );
                    $tStmt->execute();
                    $targetId = $this->db->insert_id;
                }

                // Insert con_material_issues
                $iStmt = $this->db->prepare(
                    "INSERT INTO con_material_issues
                        (project_id, warehouse_id, material_id, quantity, issued_to,
                         purpose, issued_by, issue_date, target_id, work_plan_id)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                $iStmt->bind_param('iiidisisii',
                    $projectId, $warehouseId, $materialId,
                    $qty, $contractorId, $purpose,
                    $userId, $issueDate, $targetId, $workPlanId
                );
                $iStmt->execute();
                $issueId = $this->db->insert_id;

                // Deduct stock + log movement (con_stock_movements + con_stocks)
                $stockModel->logMovement([
                    'material_id'  => $materialId,
                    'warehouse_id' => $warehouseId,
                    'type'         => 'OUT',
                    'quantity'     => $qty,
                    'ref_type'     => 'material_issue',
                    'ref_id'       => $issueId,
                    'moved_by'     => $userId,
                ]);

                $this->db->commit();
                $inserted++;

            } catch (\Throwable $e) {
                $this->db->rollback();
                $errors[] = "Row {$rowNum} ({$matName}): " . $e->getMessage();
            }
        }
        fclose($handle);

        if ($inserted > 0) {
            setSuccess("{$inserted} material issue(s) imported successfully." .
                       (!empty($errors) ? ' Some rows skipped: ' . implode('; ', array_slice($errors, 0, 3)) : ''));
        } else {
            setError('No rows imported. ' . implode(' | ', array_slice($errors, 0, 5)));
        }

        header("Location: {$GLOBALS['base_url']}/con-material-issues");
        exit;
    }
}
