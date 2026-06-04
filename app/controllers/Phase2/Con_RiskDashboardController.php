<?php
namespace Phase2;

use Controller;

class Con_RiskDashboardController extends Controller {

    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login"); exit;
        }
        if (!in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 22])) {
            setError("Access denied.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
        }
    }

    // =========================================================================
    // INDEX — Risk Dashboard for a project
    // =========================================================================
    public function index($projectId) {
        $projectId = (int)$projectId;
        $_SESSION['active_project_id'] = $projectId;

        // Project info
        $pStmt = $this->db->prepare("SELECT id, name FROM projects WHERE id=?");
        $pStmt->bind_param('i', $projectId);
        $pStmt->execute();
        $project = $pStmt->get_result()->fetch_assoc();
        if (!$project) {
            setError("Project not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }
        $_SESSION['active_project_name'] = $project['name'];

        // Phases
        $phStmt = $this->db->prepare("SELECT id, phase_name, phase_order, planned_start_date, planned_end_date, actual_progress, planned_progress, status FROM project_phases WHERE project_id=? ORDER BY phase_order ASC");
        $phStmt->bind_param('i', $projectId);
        $phStmt->execute();
        $phases = $phStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── Auto-detect missed weekly targets across all months ──────────────
        // We scan all monthly targets, all weekly progress items, and find
        // weeks where achieved_qty < planned week_N_qty for any target item.
        $missedTargets = $this->getMissedWeeklyTargets($projectId);

        // Auto-generate risk records for newly detected misses (idempotent)
        $this->autoGenerateRisks($projectId, $missedTargets);

        // ── Fetch identified risks from project_risks ────────────────────────
        $rStmt = $this->db->prepare("
            SELECT pr.*,
                   pp.phase_name
            FROM project_risks pr
            LEFT JOIN project_phases pp ON pp.id = pr.phase_id
            WHERE pr.project_id = ?
            ORDER BY pr.created_at DESC
        ");
        $rStmt->bind_param('i', $projectId);
        $rStmt->execute();
        $risks = $rStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── KPI Metrics ──────────────────────────────────────────────────────
        $totalRisks    = count($risks);
        $criticalRisks = 0;
        $openRisks     = 0;
        $resolvedRisks = 0;
        foreach ($risks as $r) {
            if (in_array($r['severity'], ['High', 'Critical'])) $criticalRisks++;
            if ($r['status'] === 'Open')     $openRisks++;
            if (in_array($r['status'], ['Resolved', 'Closed'])) $resolvedRisks++;
        }
        $missedWeekCount = count($missedTargets);

        $this->view('phase2/risk-dashboard/index', [
            'current_route'   => 'con-risk-dashboard',
            'page_title'      => 'Risk Dashboard',
            'project'         => $project,
            'phases'          => $phases,
            'risks'           => $risks,
            'missedTargets'   => $missedTargets,
            'totalRisks'      => $totalRisks,
            'criticalRisks'   => $criticalRisks,
            'openRisks'       => $openRisks,
            'resolvedRisks'   => $resolvedRisks,
            'missedWeekCount' => $missedWeekCount,
        ]);
    }

    // =========================================================================
    // STORE RISK — manually log a risk
    // =========================================================================
    public function storeRisk() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }

        $projectId   = (int)($_POST['project_id'] ?? 0);
        $phaseId     = !empty($_POST['phase_id']) ? (int)$_POST['phase_id'] : null;
        $category    = trim($_POST['category'] ?? 'Progress');
        $description = trim($_POST['description'] ?? '');
        $impact      = $_POST['impact'] ?? 'Medium';
        $likelihood  = $_POST['likelihood'] ?? 'Medium';
        $severity    = $_POST['severity'] ?? 'Medium';
        $owner       = trim($_POST['owner'] ?? '');
        $dueDate     = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if (!$projectId || empty($description)) {
            setError("Project and description are required.");
            header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
        }

        // Generate risk code
        $riskCode = 'RK-' . strtoupper(substr(md5(uniqid()), 0, 6));

        $stmt = $this->db->prepare("
            INSERT INTO project_risks
              (project_id, phase_id, risk_code, category, description, impact, likelihood, severity, owner, due_date, status, is_auto)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Open', 0)
        ");
        $stmt->bind_param('iissssssss', $projectId, $phaseId, $riskCode, $category, $description, $impact, $likelihood, $severity, $owner, $dueDate);
        $stmt->execute();

        setSuccess("Risk logged successfully.");
        header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
    }

    // =========================================================================
    // UPDATE RISK STATUS
    // =========================================================================
    public function updateRiskStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-projects"); exit;
        }

        $riskId    = (int)($_POST['risk_id'] ?? 0);
        $status    = $_POST['status'] ?? 'Open';
        $projectId = (int)($_POST['project_id'] ?? 0);

        $allowed = ['Open', 'In Progress', 'Resolved', 'Closed'];
        if (!in_array($status, $allowed)) $status = 'Open';

        $stmt = $this->db->prepare("UPDATE project_risks SET status=? WHERE id=? AND project_id=?");
        $stmt->bind_param('sii', $status, $riskId, $projectId);
        $stmt->execute();

        setSuccess("Risk status updated.");
        header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
    }

    // =========================================================================
    // DELETE RISK
    // =========================================================================
    public function deleteRisk($riskId) {
        $riskId = (int)$riskId;
        $projectId = (int)($_GET['project_id'] ?? $_POST['project_id'] ?? 0);

        $stmt = $this->db->prepare("DELETE FROM project_risks WHERE id=? AND project_id=?");
        $stmt->bind_param('ii', $riskId, $projectId);
        $stmt->execute();

        setSuccess("Risk deleted.");
        header("Location: " . $GLOBALS['base_url'] . "/con-risk-dashboard/$projectId"); exit;
    }

    // =========================================================================
    // PRIVATE — get missed weekly targets
    // =========================================================================
    private function getMissedWeeklyTargets($projectId) {
        // For each target item, check each week: if planned qty > 0 and a
        // weekly progress session exists but achieved < planned → missed.
        $stmt = $this->db->prepare("
            SELECT
                pmti.id                  AS target_item_id,
                pmti.work_item_name      AS item_name,
                pmti.work_type_id,
                pmti.start_date,
                pmti.end_date,
                wt.work_type_name,
                pmti.unit,
                pmti.monthly_target_qty,
                pmti.week_1_qty,
                pmti.week_2_qty,
                pmti.week_3_qty,
                pmti.week_4_qty,
                pmt.target_month,
                pmt.id                   AS target_id,
                pmt.phase_id,
                pp.phase_name,
                pwp.week_number,
                pwp.week_start_date,
                pwp.week_end_date,
                COALESCE(pwpi.achieved_qty, 0) AS achieved_qty
            FROM phase_monthly_target_items pmti
            JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            JOIN phase_weekly_progress pwp
                ON pwp.target_id = pmt.id AND pwp.project_id = pmt.project_id
            LEFT JOIN phase_weekly_progress_items pwpi
                ON pwpi.weekly_progress_id = pwp.id AND pwpi.target_item_id = pmti.id
            WHERE pmt.project_id = ?
            ORDER BY pmt.target_month ASC, pmti.id ASC, pwp.week_number ASC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $missed = [];
        foreach ($rows as $row) {
            $wk = (int)$row['week_number'];
            $plannedQty = (float)($row['week_' . $wk . '_qty'] ?? 0);
            $achievedQty = (float)$row['achieved_qty'];

            // Calculate sub-item deadline
            $itemEndDate = $row['end_date'] ?? null;
            if (empty($itemEndDate) || $itemEndDate === '0000-00-00') {
                $targetMonth = $row['target_month'] ?? '';
                if (strlen($targetMonth) === 7) {
                    $deadlineDate = date('Y-m-t', strtotime($targetMonth . '-01'));
                } else {
                    $deadlineDate = date('Y-m-t', strtotime($targetMonth));
                }
            } else {
                $deadlineDate = $itemEndDate;
            }

            $today = date('Y-m-d');
            // Only flag as missed risk if:
            // 1. Planned qty was > 0
            // 2. Achieved < planned
            // 3. The overall sub-item deadline has passed (deadlineDate < today)
            if ($plannedQty > 0 && $achievedQty < $plannedQty && $deadlineDate < $today) {
                $missed[] = [
                    'target_item_id' => $row['target_item_id'],
                    'item_name'      => $row['item_name'],
                    'work_type_name' => $row['work_type_name'],
                    'unit'           => $row['unit'],
                    'phase_id'       => $row['phase_id'],
                    'phase_name'     => $row['phase_name'],
                    'target_id'      => $row['target_id'],
                    'target_month'   => $row['target_month'],
                    'week_number'    => $wk,
                    'week_start'     => $row['week_start_date'],
                    'week_end'       => $row['week_end_date'],
                    'planned_qty'    => $plannedQty,
                    'achieved_qty'   => $achievedQty,
                    'missed_qty'     => $plannedQty - $achievedQty,
                ];
            }
        }
        return $missed;
    }

    // =========================================================================
    // PRIVATE — auto-generate risk records for missed targets (idempotent)
    // =========================================================================
    private function autoGenerateRisks($projectId, array $missedTargets) {
        // Clean up any auto-generated risks that are no longer flagged as missed (e.g., deadline is in the future)
        $stmt = $this->db->prepare("SELECT id, target_item_id, missed_week FROM project_risks WHERE project_id = ? AND is_auto = 1");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $existingAuto = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $validKeys = [];
        foreach ($missedTargets as $m) {
            $validKeys[] = $m['target_item_id'] . '-' . $m['week_number'];
        }

        foreach ($existingAuto as $r) {
            $key = $r['target_item_id'] . '-' . $r['missed_week'];
            if (!in_array($key, $validKeys)) {
                $del = $this->db->prepare("DELETE FROM project_risks WHERE id = ?");
                $del->bind_param('i', $r['id']);
                $del->execute();
            }
        }

        if (empty($missedTargets)) return;

        foreach ($missedTargets as $m) {
            // Check if an auto-risk already exists for this item+week
            $chk = $this->db->prepare("
                SELECT id FROM project_risks
                WHERE project_id=? AND target_item_id=? AND missed_week=? AND is_auto=1
                LIMIT 1
            ");
            $chk->bind_param('iii', $projectId, $m['target_item_id'], $m['week_number']);
            $chk->execute();
            $exists = $chk->get_result()->fetch_assoc();
            if ($exists) {
                // Update missed_qty and achieved_qty in case values changed
                $upd = $this->db->prepare("
                    UPDATE project_risks
                    SET missed_qty=?, achieved_qty=?, planned_qty=?, updated_at=NOW()
                    WHERE id=?
                ");
                $upd->bind_param('dddi', $m['missed_qty'], $m['achieved_qty'], $m['planned_qty'], $exists['id']);
                $upd->execute();
                continue;
            }

            // Determine severity based on miss ratio
            $missRatio = $m['planned_qty'] > 0 ? ($m['missed_qty'] / $m['planned_qty']) : 0;
            $severity  = $missRatio >= 0.5 ? 'High' : ($missRatio >= 0.25 ? 'Medium' : 'Low');
            $riskCode  = 'AUTO-W' . $m['week_number'] . '-' . $m['target_item_id'];
            $monthLabel = date('M Y', strtotime($m['target_month']));
            $description = "Week {$m['week_number']} target missed for '{$m['item_name']}'";
            if (!empty($m['work_type_name'])) {
                $description .= " ({$m['work_type_name']})";
            }
            $description .= " in $monthLabel. Planned: {$m['planned_qty']} {$m['unit']}, Achieved: {$m['achieved_qty']} {$m['unit']}, Shortfall: {$m['missed_qty']} {$m['unit']}.";

            $category = 'Progress';

            $ins = $this->db->prepare("
                INSERT INTO project_risks
                  (project_id, phase_id, risk_code, category, description, impact, likelihood, severity,
                   missed_week, target_item_id, missed_qty, planned_qty, achieved_qty, is_auto, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'Open')
            ");
            $impact = $severity;
            $likelihood = 'High';
            $ins->bind_param(
                'iissssssiiddd',
                $projectId,
                $m['phase_id'],
                $riskCode,
                $category,
                $description,
                $impact,
                $likelihood,
                $severity,
                $m['week_number'],
                $m['target_item_id'],
                $m['missed_qty'],
                $m['planned_qty'],
                $m['achieved_qty']
            );
            $ins->execute();
        }
    }
}
