<?php
namespace Phase2;

use Controller;
use Con_MonthlyTarget;
use Con_ProjectPhase;

class Con_MonthlyTargetController extends Controller {

    protected $db;
    protected $model;
    protected $phaseModel;

    public function __construct($db) {
        $this->db         = $db;
        $this->model      = new Con_MonthlyTarget($db);
        $this->phaseModel = new Con_ProjectPhase($db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login"); exit;
        }
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])) {
            setError("Access denied."); header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
        }
    }

    // =========================================================================
    // INDEX — list all monthly targets for a project
    // =========================================================================
    public function index($project_id) {
        $project_id = (int)$project_id;
        $_SESSION['active_project_id'] = $project_id;

        // Get project basic info
        $pStmt = $this->db->prepare("SELECT id, name FROM projects WHERE id=?");
        $pStmt->bind_param('i', $project_id);
        $pStmt->execute();
        $project = $pStmt->get_result()->fetch_assoc();
        if (!$project) { setError("Project not found."); header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit; }

        $targets = $this->model->getByProject($project_id);
        $phases  = $this->phaseModel->getByProject($project_id);

        // Group targets by month for display
        $byMonth = [];
        foreach ($targets as $t) {
            $key = $t['target_month'];
            if (!isset($byMonth[$key])) {
                $byMonth[$key] = ['month' => $t['target_month'], 'targets' => []];
            }
            $byMonth[$key]['targets'][] = $t;
        }
        ksort($byMonth);

        $this->view('phase2/monthly_targets/index', [
            'current_route' => 'con-monthly-targets',
            'project'       => $project,
            'byMonth'       => $byMonth,
            'phases'        => $phases,
            'project_id'    => $project_id,
        ]);
    }

    // =========================================================================
    // SHOW — detailed view of one monthly target (with items)
    // =========================================================================
    public function show($target_id) {
        $target_id = (int)$target_id;
        $target    = $this->model->find($target_id);
        if (!$target) { setError("Target not found."); header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit; }

        $_SESSION['active_project_id'] = $target['project_id'];

        $items    = $this->model->getItems($target_id);
        $summary  = $this->model->getProgressSummary($target_id);
        $workTypes = $this->model->getWorkTypes();

        // Get design_types for filter
        $dtRes    = $this->db->query("SELECT id, name FROM design_types WHERE status='active' ORDER BY name");
        $designTypes = $dtRes ? $dtRes->fetch_all(MYSQLI_ASSOC) : [];

        // Get the phase sub-items (Layer 2)
        $subItemsStmt = $this->db->prepare("
            SELECT ppi.id, ppi.item_name, ppi.weight_pct, ppi.work_type_id, wt.work_type_name
            FROM project_phase_items ppi
            LEFT JOIN work_types wt ON wt.id = ppi.work_type_id
            WHERE ppi.phase_id = ?
            ORDER BY ppi.item_order ASC
        ");
        $subItemsStmt->bind_param('i', $target['phase_id']);
        $subItemsStmt->execute();
        $phaseSubItems = $subItemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/monthly_targets/form', [
            'current_route' => 'con-monthly-targets',
            'target'        => $target,
            'items'         => $items,
            'summary'       => $summary,
            'workTypes'     => $workTypes,
            'designTypes'   => $designTypes,
            'phaseSubItems' => $phaseSubItems,
        ]);
    }

    // =========================================================================
    // CREATE — POST: create a new monthly target
    // =========================================================================
    public function create($project_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-monthly-targets/" . (int)$project_id); exit;
        }
        $project_id = (int)$project_id;
        $phase_id   = (int)($_POST['phase_id']     ?? 0);
        $monthRaw   = trim($_POST['target_month']  ?? '');

        if (!$phase_id || !$monthRaw) {
            setError("Phase and month are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-monthly-targets/" . $project_id); exit;
        }

        // Normalize to first day of month
        $targetMonth = date('Y-m-01', strtotime($monthRaw));

        if ($this->model->existsForMonth($project_id, $phase_id, $targetMonth)) {
            setError("A monthly target already exists for this phase and month.");
            header("Location: " . $GLOBALS['base_url'] . "/con-monthly-targets/" . $project_id); exit;
        }

        $id = $this->model->create([
            'project_id'      => $project_id,
            'phase_id'        => $phase_id,
            'target_month'    => $targetMonth,
            'planning_status' => 'Draft',
            'responsible_team'=> trim($_POST['responsible_team'] ?? ''),
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ]);

        if ($id) {
            setSuccess("Monthly target created. Add work items below.");
            header("Location: " . $GLOBALS['base_url'] . "/con-monthly-target/" . $id); exit;
        }
        setError("Failed to create monthly target.");
        header("Location: " . $GLOBALS['base_url'] . "/con-monthly-targets/" . $project_id); exit;
    }

    // =========================================================================
    // UPDATE TARGET STATUS — POST
    // =========================================================================
    public function updateStatus($target_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $target_id = (int)$target_id;
        $target    = $this->model->find($target_id);
        if (!$target) { setError("Not found."); header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit; }

        $this->model->update($target_id, [
            'planning_status'  => $_POST['planning_status']  ?? $target['planning_status'],
            'responsible_team' => $_POST['responsible_team'] ?? $target['responsible_team'],
        ]);

        // Update project_phases status and timeline dates if present
        if (isset($_POST['phase_status']) || isset($_POST['planned_start_date']) || isset($_POST['planned_end_date'])) {
            $phaseId = (int)$target['phase_id'];
            $phaseStatus = $_POST['phase_status'] ?? $target['phase_status'];
            $startDate = !empty($_POST['planned_start_date']) ? $_POST['planned_start_date'] : null;
            $endDate = !empty($_POST['planned_end_date']) ? $_POST['planned_end_date'] : null;

            $pStmt = $this->db->prepare("
                UPDATE project_phases SET
                    status = ?,
                    planned_start_date = ?,
                    planned_end_date = ?
                WHERE id = ?
            ");
            $pStmt->bind_param('sssi', $phaseStatus, $startDate, $endDate, $phaseId);
            $pStmt->execute();
        }

        setSuccess("Target and Phase details updated.");
        header("Location: " . $GLOBALS['base_url'] . "/con-monthly-target/" . $target_id); exit;
    }

    // =========================================================================
    // SAVE ITEMS — batch update items POST
    // =========================================================================
    public function saveItems($target_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $target_id = (int)$target_id;
        $target    = $this->model->find($target_id);
        if (!$target) { setError("Target not found."); header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit; }

        $raw = $_POST['items'] ?? [];
        if (!empty($raw)) {
            if ($this->model->batchUpdateItems($raw)) {
                setSuccess("Items saved successfully.");
            } else {
                setError("Failed to save some items.");
            }
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-monthly-target/" . $target_id); exit;
    }

    // =========================================================================
    // ADD ITEM — AJAX POST
    // =========================================================================
    public function addItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
        $target_id = (int)($_POST['target_id'] ?? 0);
        $name      = trim($_POST['work_item_name'] ?? '');

        if (!$target_id || !$name) { $this->jsonError("Target ID and item name required."); }

        $target = $this->model->find($target_id);
        if (!$target) { $this->jsonError("Target not found."); }

        $wid = ($_POST['work_type_id'] && $_POST['work_type_id'] != '') ? (int)$_POST['work_type_id'] : null;

        $startDate = !empty($_POST['start_date']) ? trim($_POST['start_date']) : null;
        $endDate   = !empty($_POST['end_date'])   ? trim($_POST['end_date'])   : null;

        $id = $this->model->createItem([
            'target_id'           => $target_id,
            'work_type_id'        => $wid,
            'work_item_name'      => $name,
            'unit'                => trim($_POST['unit']               ?? ''),
            'monthly_target_qty'  => (float)($_POST['monthly_target_qty'] ?? 0),
            'planned_progress_pct'=> (float)($_POST['planned_progress_pct'] ?? 0),
            'start_date'          => $startDate,
            'end_date'            => $endDate,
            'responsible_team'    => trim($_POST['responsible_team']   ?? ''),
            'priority'            => $_POST['priority'] ?? 'Medium',
            'status'              => 'Draft',
            'week_1_qty'          => (float)($_POST['week_1_qty'] ?? 0),
            'week_2_qty'          => (float)($_POST['week_2_qty'] ?? 0),
            'week_3_qty'          => (float)($_POST['week_3_qty'] ?? 0),
            'week_4_qty'          => (float)($_POST['week_4_qty'] ?? 0),
        ]);

        if ($id) {
            $items   = $this->model->getItems($target_id);
            $summary = $this->model->getProgressSummary($target_id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'item_id' => $id, 'summary' => $summary]);
        } else {
            $this->jsonError("Failed to add item.");
        }
        exit;
    }

    // =========================================================================
    // DELETE ITEM — GET
    // =========================================================================
    public function deleteItem($id) {
        $id   = (int)$id;
        $item = $this->model->findItem($id);
        if (!$item) { setError("Item not found."); header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit; }

        $target_id = $item['target_id'];
        $this->model->deleteItem($id);
        setSuccess("Item deleted.");
        header("Location: " . $GLOBALS['base_url'] . "/con-monthly-target/" . $target_id); exit;
    }

    // =========================================================================
    // DELETE TARGET — GET
    // =========================================================================
    public function deleteTarget($id) {
        $id     = (int)$id;
        $target = $this->model->find($id);
        if (!$target) { setError("Not found."); header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit; }

        $pid = $target['project_id'];
        $this->model->delete($id);
        setSuccess("Monthly target deleted.");
        header("Location: " . $GLOBALS['base_url'] . "/con-monthly-targets/" . $pid); exit;
    }

    // =========================================================================
    // AJAX — get work_types by design_type_id
    // =========================================================================
    public function ajaxWorkTypes() {
        $design_type_id = (int)($_GET['design_type_id'] ?? 0);
        $types = $this->model->getWorkTypes($design_type_id ?: null);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'work_types' => $types]);
        exit;
    }

    private function jsonError(string $msg): never {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}
