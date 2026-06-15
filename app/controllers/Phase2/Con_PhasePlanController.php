<?php
namespace Phase2;

use Controller;
use Con_ProjectPhase;
use Con_PhaseTemplate;
use Project;

class Con_PhasePlanController extends Controller {
    protected $db;
    protected $phaseModel;
    protected $templateModel;
    protected $projectModel;

    public function __construct($db) {
        $this->db            = $db;
        $this->phaseModel    = new Con_ProjectPhase($db);
        $this->templateModel = new Con_PhaseTemplate($db);
        $this->projectModel  = new Project($db);

        if (session_status() === PHP_SESSION_NONE) session_start();

        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }

        // Role gate: only 1 (Admin), 3 (Director), 15 (PM), 22 (PM Head)
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])) {
            setError("You do not have permission to access Phase Plan.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }
    }

    // =========================================================================
    // INDEX — list phases for a project; auto-clone template on first visit
    // =========================================================================
    public function index($project_id) {
        $project_id = (int)$project_id;
        $project    = $this->projectModel->find($project_id);

        if (!$project) {
            setError("Project not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects");
            exit;
        }

        // Set sidebar context
        $_SESSION['active_project_id']               = $project['id'];
        $_SESSION['active_project_name']             = $project['name'];
        $_SESSION['active_project_workflow_version'] = $project['workflow_version'];

        // Timeline guard — Phase Plan requires timeline to exist first
        $timelineStmt = $this->db->prepare(
            "SELECT id, project_start_date, construction_start_date, construction_end_date,
                    handover_months
             FROM project_timelines WHERE project_id = ?"
        );
        $timelineStmt->bind_param('i', $project_id);
        $timelineStmt->execute();
        $timeline = $timelineStmt->get_result()->fetch_assoc();

        if (!$timeline) {
            setError("Please set up the Project Timeline first before creating the Phase Plan.");
            header("Location: " . $GLOBALS['base_url'] . "/con-project-timeline/" . $project_id);
            exit;
        }

        // Auto-clone template on first access
        $phases = $this->phaseModel->getByProject($project_id);
        if (empty($phases)) {
            $templates  = $this->templateModel->getAll();

            $projStart    = new \DateTime($timeline['project_start_date']);
            $conStart     = new \DateTime($timeline['construction_start_date']);
            $conEnd       = new \DateTime($timeline['construction_end_date']);
            $totalConDays = (int)$conStart->diff($conEnd)->days;

            // ── Pre-Construction (phase_order=1): project_start → construction_start ──
            $preCon    = array_values(array_filter($templates, fn($t) => (int)$t['phase_order'] === 1));
            $workPhases = array_values(array_filter($templates, fn($t) => (int)$t['phase_order'] > 1));

            foreach ($preCon as $t) {
                $this->phaseModel->insertFromTemplate(
                    $project_id, $t,
                    $projStart->format('Y-m-d'),
                    $conStart->format('Y-m-d')
                );
            }

            // ── Work-type phases: spread across construction period in 3 bands ────
            // Early  (0–50%) : Structure, Brick, Plumbing, Electrical, Metal
            // Mid    (30–80%): Wood, Plaster, EME, Others Civil
            // Late   (60–100%): Tiles, Aluminium, Paint, Utility
            $bands = [
                'early' => ['start_pct' => 0,   'end_pct' => 75],
                'mid'   => ['start_pct' => 30,  'end_pct' => 90],
                'late'  => ['start_pct' => 55,  'end_pct' => 100],
            ];
            $phaseband = [
                'Structure'                    => 'early',
                'Brick Work'                   => 'early',
                'Plumbing & Sanitary'          => 'early',
                'Electrical Work'              => 'early',
                'Metal Work'                   => 'early',
                'Wood Work'                    => 'mid',
                'Plaster Work'                 => 'mid',
                'EME'                          => 'mid',
                'Others Civil Work'            => 'mid',
                'General Floor Tiles'          => 'late',
                'Toilet & Kitchen Wall Tiles'  => 'late',
                'Aluminium Work'               => 'late',
                'Paint Work'                   => 'late',
                'Utility Connection & Bill'    => 'late',
            ];

            foreach ($workPhases as $t) {
                $bandKey = $phaseband[$t['phase_name']] ?? 'mid';
                $band    = $bands[$bandKey];

                $startOff = (int)round($band['start_pct'] / 100 * $totalConDays);
                $endOff   = (int)round($band['end_pct']   / 100 * $totalConDays);

                $phaseStart = clone $conStart;
                $phaseStart->modify("+{$startOff} days");

                $phaseEnd = clone $conStart;
                $phaseEnd->modify("+{$endOff} days");
                // Clamp to construction end
                if ($phaseEnd > $conEnd) $phaseEnd = clone $conEnd;

                $this->phaseModel->insertFromTemplate(
                    $project_id, $t,
                    $phaseStart->format('Y-m-d'),
                    $phaseEnd->format('Y-m-d')
                );
            }

            $phases = $this->phaseModel->getByProject($project_id);
            setSuccess("Phase plan initialized with 15 work-type phases. Adjust months as needed.");
        }

        $totalWeight = $this->phaseModel->totalWeight($project_id);

        // Fetch drawings uploaded for this project with design type names
        $drawingsStmt = $this->db->prepare("
            SELECT pd.id, pd.phase_id, dt.name AS design_name, pd.version
            FROM project_drawings pd
            LEFT JOIN design_types dt ON dt.id = pd.design_type_id
            WHERE pd.project_id = ?
            ORDER BY dt.name ASC, pd.version ASC
        ");
        $drawingsStmt->bind_param('i', $project_id);
        $drawingsStmt->execute();
        $drawings = $drawingsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/phase_plan/index', [
            'current_route' => 'con-phase-plan',
            'page_title'    => 'Phase Plan — ' . ($project['name'] ?? ''),
            'project'       => $project,
            'timeline'      => $timeline,
            'phases'        => $phases,
            'drawings'      => $drawings,
            'totalWeight'   => round($totalWeight, 2),
        ]);
    }

    // =========================================================================
    // UPDATE — save all phases in bulk (POST)
    // =========================================================================
    public function update($project_id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-phase-plan/" . (int)$project_id);
            exit;
        }

        $project_id = (int)$project_id;

        // Get project_start_date for month→date conversion
        $tlStmt = $this->db->prepare("SELECT project_start_date FROM project_timelines WHERE project_id = ?");
        $tlStmt->bind_param('i', $project_id);
        $tlStmt->execute();
        $tl = $tlStmt->get_result()->fetch_assoc();
        $projStart = new \DateTime($tl['project_start_date'] ?? date('Y-m-d'));

        // Collect phases array from POST
        $raw = $_POST['phases'] ?? [];
        if (empty($raw)) {
            setError("No phase data received.");
            header("Location: " . $GLOBALS['base_url'] . "/con-phase-plan/" . $project_id);
            exit;
        }

        // Validate total weight = 100
        $totalWeight = 0;
        foreach ($raw as $p) {
            $totalWeight += (float)($p['weight_percentage'] ?? 0);
        }
        if (abs($totalWeight - 100) > 0.01) {
            setError("Total phase weight must equal 100%. Current total: " . round($totalWeight, 2) . "%");
            header("Location: " . $GLOBALS['base_url'] . "/con-phase-plan/" . $project_id);
            exit;
        }

        // Build update array — convert start_month / end_month → dates
        $phases = [];
        foreach ($raw as $p) {
            $startM = max(1, (int)($p['start_month'] ?? 1));
            $endM   = max(1, (int)($p['end_month']   ?? 1));

            $startDate = clone $projStart;
            $startDate->modify('+' . ($startM - 1) . ' months');

            $endDate = clone $projStart;
            $endDate->modify('+' . ($endM - 1) . ' months');

            $phases[] = [
                'id'                => (int)$p['id'],
                'phase_name'        => trim($p['phase_name']        ?? ''),
                'phase_order'       => (int)($p['phase_order']      ?? 0),
                'weight_percentage' => (float)($p['weight_percentage'] ?? 0),
                'planned_start_date'=> $startDate->format('Y-m-d'),
                'planned_end_date'  => $endDate->format('Y-m-d'),
                'status'            => $p['status']                 ?? 'Not Started',
                'drawing_id'        => !empty($p['drawing_id'])     ? (int)$p['drawing_id'] : null,
            ];
        }

        if ($this->phaseModel->batchUpdate($phases, $project_id)) {
            setSuccess("Phase plan saved successfully.");
        } else {
            setError("Failed to save phase plan. Please try again.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-phase-plan/" . $project_id);
        exit;
    }


    // =========================================================================
    // ADD — add a new custom phase (POST via AJAX or form)
    // =========================================================================
    public function addPhase() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

        $project_id = (int)($_POST['project_id'] ?? 0);
        $phase_name = trim($_POST['phase_name']  ?? '');

        if (!$project_id || !$phase_name) {
            $this->jsonError("Project ID and Phase Name are required.");
        }

        $data = [
            'project_id'         => $project_id,
            'phase_name'         => $phase_name,
            'phase_order'        => $this->phaseModel->nextOrder($project_id),
            'weight_percentage'  => 0.00,
            'planned_start_date' => $_POST['planned_start_date'] ?? null,
            'planned_end_date'   => $_POST['planned_end_date']   ?? null,
            'status'             => 'Not Started',
        ];

        if ($this->phaseModel->create($data)) {
            $newPhaseId = $this->db->insert_id;
            $drawing_id = (int)($_POST['drawing_id'] ?? 0);
            if ($drawing_id > 0) {
                $drawStmt = $this->db->prepare("UPDATE project_drawings SET phase_id = ? WHERE id = ? AND project_id = ?");
                $drawStmt->bind_param('iii', $newPhaseId, $drawing_id, $project_id);
                $drawStmt->execute();
            }

            // Return updated phases JSON
            $phases = $this->phaseModel->getByProject($project_id);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'phases' => $phases, 'total_weight' => $this->phaseModel->totalWeight($project_id)]);
        } else {
            $this->jsonError("Failed to add phase.");
        }
        exit;
    }

    // =========================================================================
    // DELETE — delete a single phase (POST)
    // =========================================================================
    public function delete($id) {
        $id = (int)$id;
        $phase = $this->phaseModel->find($id);

        if (!$phase) {
            setError("Phase not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects");
            exit;
        }

        $project_id = $phase['project_id'];

        if ($this->phaseModel->delete($id)) {
            setSuccess("Phase deleted successfully.");
        } else {
            setError("Cannot delete this phase — it has linked Monthly Targets or other data.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-phase-plan/" . $project_id);
        exit;
    }

    // =========================================================================
    // AJAX — get global template JSON
    // =========================================================================
    public function ajaxGetTemplate() {
        $templates = $this->templateModel->getAll();
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'templates' => $templates]);
        exit;
    }

    // =========================================================================
    // Helper
    // =========================================================================
    private function jsonError($msg) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}
