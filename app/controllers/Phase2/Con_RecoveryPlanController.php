<?php
namespace Phase2;
use Controller;

class Con_RecoveryPlanController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/admin-login"); exit;
        }
    }

    public function index(int $projectId) {
        $base = $GLOBALS['base_url'];

        $proj = $this->db->query("SELECT id, name FROM projects WHERE id = $projectId")->fetch_assoc();
        if (!$proj) { echo "Project not found."; return; }

        // Fetch items that are behind: actual < planned and planned > 0
        $res = $this->db->query("
            SELECT pmti.id AS item_id,
                   pmti.work_item_name,
                   pmti.unit,
                   pmti.target_qty,
                   pmti.actual_qty,
                   pmti.planned_progress_pct,
                   pmti.actual_progress_pct,
                   pmti.recovery_notes,
                   pmt.id AS target_id,
                   pmt.target_month,
                   pmt.responsible_team,
                   pp.phase_name,
                   wt.work_type_name
            FROM phase_monthly_target_items pmti
            JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            WHERE pmt.project_id = $projectId
              AND pmti.planned_progress_pct > 0
              AND pmti.actual_progress_pct < pmti.planned_progress_pct
            ORDER BY (pmti.planned_progress_pct - pmti.actual_progress_pct) DESC, pp.phase_order ASC
        ");
        $behindItems = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

        // Calculate catch-up data per item
        foreach ($behindItems as &$item) {
            $planned  = (float)$item['planned_progress_pct'];
            $actual   = (float)$item['actual_progress_pct'];
            $gapPct   = $planned - $actual;
            $targetQty = (float)$item['target_qty'];

            // Qty gap
            $gapQty = $targetQty > 0 ? ($gapPct / 100) * $targetQty : 0;

            // Remaining days & weeks in target month
            $monthEnd  = new \DateTime(date('Y-m-t', strtotime($item['target_month'])));
            $today     = new \DateTime(date('Y-m-d'));
            $remDays   = max(1, $today <= $monthEnd ? (int)$today->diff($monthEnd)->days + 1 : 0);
            $remWeeks  = max(1, ceil($remDays / 7));

            // Suggested daily / weekly catch-up qty
            $dailyCatchup  = $remDays > 0 && $gapQty > 0 ? round($gapQty / $remDays, 2) : 0;
            $weeklyCatchup = $remWeeks > 0 && $gapQty > 0 ? round($gapQty / $remWeeks, 2) : 0;

            $item['gap_pct']        = round($gapPct, 1);
            $item['gap_qty']        = round($gapQty, 2);
            $item['rem_days']       = $remDays;
            $item['rem_weeks']      = $remWeeks;
            $item['daily_catchup']  = $dailyCatchup;
            $item['weekly_catchup'] = $weeklyCatchup;

            // Risk label
            if ($gapPct >= 50)      $item['risk'] = 'Critical';
            elseif ($gapPct >= 25)  $item['risk'] = 'High';
            elseif ($gapPct >= 10)  $item['risk'] = 'Medium';
            else                     $item['risk'] = 'Low';
        }

        $this->view('phase2/recovery-plan/index', [
            'current_route' => 'con-recovery-plan',
            'page_title'    => 'Recovery Plan',
            'base'          => $base,
            'project'       => $proj,
            'projectId'     => $projectId,
            'behindItems'   => $behindItems,
        ]);
    }

    public function saveNotes() {
        $notes  = $_POST['notes'] ?? [];
        $base   = $GLOBALS['base_url'];
        $projId = (int)($_POST['project_id'] ?? 0);

        foreach ($notes as $itemId => $note) {
            $itemId = (int)$itemId;
            $note   = $this->db->real_escape_string(trim($note));
            $this->db->query("UPDATE phase_monthly_target_items SET recovery_notes = '$note' WHERE id = $itemId");
        }

        setSuccess('Recovery notes saved.');
        header("Location: $base/con-recovery-plan/$projId");
        exit;
    }
}
