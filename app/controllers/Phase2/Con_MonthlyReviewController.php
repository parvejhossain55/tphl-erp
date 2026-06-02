<?php
namespace Phase2;
use Controller;

class Con_MonthlyReviewController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/admin-login"); exit;
        }
    }

    public function index(int $projectId) {
        $base        = $GLOBALS['base_url'];
        $selectedMonth = $_GET['month'] ?? '';
        $selectedPhase = (int)($_GET['phase_id'] ?? 0);

        // Project info
        $proj = $this->db->query("SELECT id, name FROM projects WHERE id = $projectId")->fetch_assoc();
        if (!$proj) { echo "Project not found."; return; }

        // All phases for this project
        $phasesRes = $this->db->query("
            SELECT id, phase_name, phase_weight, phase_status
            FROM project_phases WHERE project_id = $projectId ORDER BY phase_order ASC
        ");
        $phases = $phasesRes ? $phasesRes->fetch_all(MYSQLI_ASSOC) : [];

        // Monthly targets with items
        $wherePhase = $selectedPhase ? "AND pmt.phase_id = $selectedPhase" : '';
        $whereMonth = $selectedMonth ? "AND DATE_FORMAT(pmt.target_month,'%Y-%m') = '" . $this->db->real_escape_string($selectedMonth) . "'" : '';

        $targetsRes = $this->db->query("
            SELECT pmt.id AS target_id, pmt.target_month, pmt.planning_status,
                   pmt.responsible_team,
                   pp.id AS phase_id, pp.phase_name, pp.phase_weight,
                   COUNT(pmti.id) AS total_items,
                   SUM(pmti.target_qty) AS total_planned_qty,
                   SUM(pmti.actual_qty) AS total_actual_qty,
                   AVG(pmti.planned_progress_pct) AS avg_planned_pct,
                   AVG(pmti.actual_progress_pct) AS avg_actual_pct
            FROM phase_monthly_targets pmt
            JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN phase_monthly_target_items pmti ON pmti.target_id = pmt.id
            WHERE pmt.project_id = $projectId $wherePhase $whereMonth
            GROUP BY pmt.id
            ORDER BY pp.phase_order ASC, pmt.target_month ASC
        ");
        $targets = $targetsRes ? $targetsRes->fetch_all(MYSQLI_ASSOC) : [];

        // For each target fetch its items
        $targetItems = [];
        foreach ($targets as $t) {
            $tid = (int)$t['target_id'];
            $itemsRes = $this->db->query("
                SELECT pmti.*, wi.item_name AS work_item_name, wi.unit,
                       wt.work_type_name
                FROM phase_monthly_target_items pmti
                LEFT JOIN project_phase_items wi ON wi.id = pmti.phase_item_id
                LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
                WHERE pmti.target_id = $tid
                ORDER BY pmti.id ASC
            ");
            $targetItems[$tid] = $itemsRes ? $itemsRes->fetch_all(MYSQLI_ASSOC) : [];
        }

        // Material issues per target
        $matByTarget = [];
        $matRes = $this->db->query("
            SELECT mi.target_id, COUNT(*) AS issue_count, SUM(mi.quantity) AS total_qty,
                   GROUP_CONCAT(DISTINCT m.name SEPARATOR ', ') AS materials
            FROM con_material_issues mi
            LEFT JOIN con_materials m ON m.id = mi.material_id
            WHERE mi.target_id IN (SELECT id FROM phase_monthly_targets WHERE project_id = $projectId)
            GROUP BY mi.target_id
        ");
        if ($matRes) {
            while ($row = $matRes->fetch_assoc()) {
                $matByTarget[(int)$row['target_id']] = $row;
            }
        }

        $this->view('phase2/monthly-review/index', [
            'current_route' => 'con-monthly-review',
            'page_title'    => 'Monthly Review',
            'base'          => $base,
            'project'       => $proj,
            'projectId'     => $projectId,
            'phases'        => $phases,
            'targets'       => $targets,
            'targetItems'   => $targetItems,
            'matByTarget'   => $matByTarget,
            'selectedMonth' => $selectedMonth,
            'selectedPhase' => $selectedPhase,
        ]);
    }
}
