<?php
namespace Phase2;
use Controller;

class Con_RiskDashboardController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/admin-login"); exit;
        }
    }

    public function index(int $projectId) {
        $base = $GLOBALS['base_url'];

        // Project info
        $proj = $this->db->query("SELECT id, name FROM projects WHERE id = $projectId")->fetch_assoc();
        if (!$proj) { echo "Project not found."; return; }

        // All items with planned vs actual — detect risk
        $itemsRes = $this->db->query("
            SELECT pmti.id, pmti.target_qty, pmti.actual_qty,
                   pmti.planned_progress_pct, pmti.actual_progress_pct,
                   pmti.work_item_name,
                   pmt.target_month, pmt.planning_status,
                   pmt.responsible_team,
                   pp.phase_name, pp.phase_weight,
                   wt.work_type_name,
                   pmti.unit
            FROM phase_monthly_target_items pmti
            JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            WHERE pmt.project_id = $projectId
            ORDER BY pp.phase_order ASC, pmt.target_month ASC, pmti.id ASC
        ");
        $allItems = $itemsRes ? $itemsRes->fetch_all(MYSQLI_ASSOC) : [];

        // Classify each item
        $kpi = ['total' => 0, 'completed' => 0, 'on_track' => 0, 'behind' => 0, 'not_started' => 0, 'critical' => 0];
        $riskItems = [];

        foreach ($allItems as $item) {
            $planned = (float)$item['planned_progress_pct'];
            $actual  = (float)$item['actual_progress_pct'];
            $kpi['total']++;

            if ($actual >= 100)            { $risk = 'Completed'; $kpi['completed']++; }
            elseif ($actual == 0 && $planned == 0) { $risk = 'Not Started'; $kpi['not_started']++; }
            elseif ($actual == 0 && $planned > 0)  { $risk = 'Critical';    $kpi['critical']++; $kpi['behind']++; }
            elseif ($planned > 0 && $actual < $planned * 0.5) { $risk = 'High';   $kpi['behind']++; }
            elseif ($planned > 0 && $actual < $planned)       { $risk = 'Medium';  $kpi['behind']++; }
            elseif ($planned > 0 && $actual >= $planned)      { $risk = 'On Track'; $kpi['on_track']++; }
            else                                               { $risk = 'Low';     $kpi['on_track']++; }

            $deviation = $actual - $planned;
            $item['risk_level'] = $risk;
            $item['deviation']  = $deviation;
            $riskItems[] = $item;
        }

        // Sort: most critical first
        usort($riskItems, function($a, $b) {
            $order = ['Critical'=>0,'High'=>1,'Medium'=>2,'Low'=>3,'On Track'=>4,'Not Started'=>5,'Completed'=>6];
            return ($order[$a['risk_level']] ?? 9) <=> ($order[$b['risk_level']] ?? 9);
        });

        // Phase-level aggregation
        $byPhase = [];
        foreach ($riskItems as $item) {
            $ph = $item['phase_name'];
            if (!isset($byPhase[$ph])) {
                $byPhase[$ph] = ['planned' => 0, 'actual' => 0, 'count' => 0, 'weight' => (float)$item['phase_weight']];
            }
            $byPhase[$ph]['planned'] += (float)$item['planned_progress_pct'];
            $byPhase[$ph]['actual']  += (float)$item['actual_progress_pct'];
            $byPhase[$ph]['count']++;
        }
        foreach ($byPhase as &$ph) {
            $ph['avg_planned'] = $ph['count'] ? $ph['planned'] / $ph['count'] : 0;
            $ph['avg_actual']  = $ph['count'] ? $ph['actual']  / $ph['count'] : 0;
        }

        $this->view('phase2/risk-dashboard/index', [
            'current_route' => 'con-risk-dashboard',
            'page_title'    => 'Risk Dashboard',
            'base'          => $base,
            'project'       => $proj,
            'projectId'     => $projectId,
            'kpi'           => $kpi,
            'riskItems'     => $riskItems,
            'byPhase'       => $byPhase,
        ]);
    }
}
