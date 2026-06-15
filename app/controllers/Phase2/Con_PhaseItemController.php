<?php
namespace Phase2;

use Controller;
use Con_ProjectPhase;
use Con_PhaseItem;

class Con_PhaseItemController extends Controller {
    protected $db;
    protected $phaseModel;
    protected $itemModel;

    public function __construct($db) {
        $this->db         = $db;
        $this->phaseModel = new Con_ProjectPhase($db);
        $this->itemModel  = new Con_PhaseItem($db);

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }

        if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])) {
            setError("You do not have permission.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }
    }

    // =========================================================================
    // INDEX — list sub-items for a specific phase; auto-clone template
    // =========================================================================
    public function index($phase_id) {
        $phase_id = (int)$phase_id;
        $phase    = $this->phaseModel->find($phase_id);

        if (!$phase) {
            setError("Phase not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects");
            exit;
        }

        // Set sidebar context
        $_SESSION['active_project_id'] = $phase['project_id'];

        // Auto-clone template items on first access
        if (!$this->itemModel->existsForPhase($phase_id)) {
            $templates = $this->itemModel->getTemplateByPhaseName($phase['phase_name']);
            foreach ($templates as $t) {
                $this->itemModel->insertFromTemplate($phase_id, $t);
            }
            if (!empty($templates)) {
                setSuccess("Sub-items initialized from template for '{$phase['phase_name']}'.");
            }
        }

        $items       = $this->itemModel->getByPhase($phase_id);
        $totalWeight = $this->itemModel->totalWeight($phase_id);
        $phaseProgress = $this->itemModel->recalcPhaseProgress($phase_id);

        // Fetch design_types for dropdown
        $dtStmt = $this->db->query("SELECT id, name FROM design_types WHERE status='active' ORDER BY name");
        $designTypes = $dtStmt ? $dtStmt->fetch_all(MYSQLI_ASSOC) : [];

        // Fetch work_types for dropdown
        $wtStmt = $this->db->query("
            SELECT wt.id, wt.work_type_name, dt.name AS design_type_name 
            FROM work_types wt
            LEFT JOIN design_types dt ON dt.id = wt.design_type_id
            ORDER BY dt.name ASC, wt.work_type_name ASC
        ");
        $workTypes = $wtStmt ? $wtStmt->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/phase_items/index', [
            'current_route' => 'con-phase-plan',
            'page_title'    => 'Phase Items — ' . ($phase['phase_name'] ?? ''),
            'phase'         => $phase,
            'items'         => $items,
            'totalWeight'   => round($totalWeight, 2),
            'phaseProgress' => $phaseProgress,
            'designTypes'   => $designTypes,
            'workTypes'     => $workTypes,
        ]);
    }

    // =========================================================================
    // UPDATE — batch save items (POST)
    // =========================================================================
    public function update($phase_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-phase-items/" . (int)$phase_id);
            exit;
        }

        $phase_id = (int)$phase_id;
        $phase    = $this->phaseModel->find($phase_id);

        if (!$phase) {
            setError("Phase not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects");
            exit;
        }

        $raw = $_POST['items'] ?? [];
        if (empty($raw)) {
            setError("No item data received.");
            header("Location: " . $GLOBALS['base_url'] . "/con-phase-items/" . $phase_id);
            exit;
        }

        // Validate total weight = 100
        $totalWeight = array_sum(array_column($raw, 'weight_pct'));
        if (abs($totalWeight - 100) > 0.01) {
            setError("Sub-item weights must sum to 100%. Current: " . round($totalWeight, 2) . "%");
            header("Location: " . $GLOBALS['base_url'] . "/con-phase-items/" . $phase_id);
            exit;
        }

        // Fetch all work types to map work_type_id -> design_type_id
        $wtMap = [];
        $wtRes = $this->db->query("SELECT id, design_type_id FROM work_types");
        if ($wtRes) {
            while ($row = $wtRes->fetch_assoc()) {
                $wtMap[(int)$row['id']] = (int)$row['design_type_id'];
            }
        }

        // Clamp progress to 0-100 and map work_type/design_type
        $items = array_map(function($i) use ($wtMap) {
            $wtId = !empty($i['work_type_id']) ? (int)$i['work_type_id'] : null;
            $dtId = $wtId ? ($wtMap[$wtId] ?? null) : null;
            return [
                'id'              => (int)$i['id'],
                'item_name'       => trim($i['item_name'] ?? ''),
                'weight_pct'      => (float)($i['weight_pct']       ?? 0),
                'actual_progress' => min(100, max(0, (float)($i['actual_progress']  ?? 0))),
                'planned_progress'=> min(100, max(0, (float)($i['planned_progress'] ?? 0))),
                'status'          => $i['status'] ?? 'Not Started',
                'work_type_id'    => $wtId,
                'design_type_id'  => $dtId,
            ];
        }, $raw);

        if ($this->itemModel->batchUpdate($items)) {
            // Sync progress back to the parent phase
            $this->itemModel->syncPhaseProgress($phase_id);
            setSuccess("Sub-items saved and phase progress updated.");
        } else {
            setError("Failed to save sub-items.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-phase-items/" . $phase_id);
        exit;
    }

    // =========================================================================
    // ADD ITEM — AJAX/POST to create a new custom sub-item
    // =========================================================================
    public function addItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        $phaseId  = (int)($_POST['phase_id'] ?? 0);
        $itemName = trim($_POST['item_name'] ?? '');

        if (!$phaseId || $itemName === '') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Required fields missing.']);
            exit;
        }

        $wtId = !empty($_POST['work_type_id']) ? (int)$_POST['work_type_id'] : null;
        $dtId = null;

        // If work_type_id is selected, query its design_type_id
        if ($wtId) {
            $stmt = $this->db->prepare("SELECT design_type_id FROM work_types WHERE id = ?");
            $stmt->bind_param('i', $wtId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            if ($res) {
                $dtId = $res['design_type_id'];
            }
        }

        $data = [
            'phase_id'         => $phaseId,
            'item_name'        => $itemName,
            'weight_pct'       => (float)($_POST['weight_pct'] ?? 0.00),
            'planned_progress' => (float)($_POST['planned_progress'] ?? 0.00),
            'actual_progress'  => (float)($_POST['actual_progress'] ?? 0.00),
            'work_type_id'     => $wtId,
            'design_type_id'   => $dtId,
            'status'           => trim($_POST['status'] ?? 'Not Started'),
        ];

        if ($this->itemModel->create($data)) {
            // Sync progress back to the parent phase
            $this->itemModel->syncPhaseProgress($phaseId);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to insert sub-item.']);
        }
        exit;
    }

    // =========================================================================
    // EDIT ITEM — AJAX/POST to update a sub-item's name and weight
    // =========================================================================
    public function editItem() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        $id        = (int)($_POST['id'] ?? 0);
        $itemName  = trim($_POST['item_name'] ?? '');
        $weightPct = (float)($_POST['weight_pct'] ?? 0);

        if (!$id || $itemName === '') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Required fields missing.']);
            exit;
        }

        $item = $this->itemModel->find($id);
        if (!$item) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Sub-item not found.']);
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE project_phase_items
            SET item_name = ?, weight_pct = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('sdi', $itemName, $weightPct, $id);

        if ($stmt->execute()) {
            // Synchronize name to target items
            $syncStmt = $this->db->prepare("
                UPDATE phase_monthly_target_items
                SET work_item_name = ?
                WHERE phase_item_id = ?
            ");
            $syncStmt->bind_param('si', $itemName, $id);
            $syncStmt->execute();

            $this->itemModel->syncPhaseProgress($item['phase_id']);
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to update sub-item.']);
        }
        exit;
    }

    // =========================================================================
    // DELETE — delete a single item (GET)
    // =========================================================================
    public function delete($id) {
        $id   = (int)$id;
        $item = $this->itemModel->find($id);

        if (!$item) {
            setError("Item not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects");
            exit;
        }

        $phase_id = $item['phase_id'];

        if ($this->itemModel->delete($id)) {
            $this->itemModel->syncPhaseProgress($phase_id);
            setSuccess("Item deleted.");
        } else {
            setError("Failed to delete item.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-phase-items/" . $phase_id);
        exit;
    }

    // =========================================================================
    // AJAX — update actual_progress for a single item
    // =========================================================================
    public function ajaxUpdateProgress() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

        $id       = (int)($_POST['id']              ?? 0);
        $progress = min(100, max(0, (float)($_POST['actual_progress'] ?? 0)));
        $status   = $_POST['status'] ?? 'Not Started';

        $item = $this->itemModel->find($id);
        if (!$item) { $this->jsonError("Item not found."); }

        // Update single item progress
        $stmt = $this->db->prepare("
            UPDATE project_phase_items
            SET actual_progress = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('dsi', $progress, $status, $id);
        $stmt->execute();

        // Sync parent phase progress
        $newPhaseProgress = $this->itemModel->recalcPhaseProgress($item['phase_id']);
        $this->itemModel->syncPhaseProgress($item['phase_id']);

        header('Content-Type: application/json');
        echo json_encode([
            'success'          => true,
            'actual_progress'  => $progress,
            'phase_progress'   => $newPhaseProgress,
        ]);
        exit;
    }

    // =========================================================================
    // Helper
    // =========================================================================
    private function jsonError(string $msg): never {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}
