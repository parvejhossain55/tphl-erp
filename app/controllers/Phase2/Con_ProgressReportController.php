<?php
namespace Phase2;

use Controller;

class Con_ProgressReportController extends Controller
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        // Authorize roles: 1 (Super Admin), 3 (Admin), 12 (Site Engineer), 15 (Project Manager), 20 (Operation Head), 22 (PM Head)
        if (!in_array($roleId, [1, 3, 33, 12, 15, 20, 22])) {
            header("Location: " . $GLOBALS['base_url'] . "/home");
            exit;
        }
    }

    /**
     * Helper to trace recovery details for a weekly target item
     */
    private function getRecoveryExplanation($targetId, $targetItemId, $currentWeekNo, $recoveredQty, $targetMode = 'qty')
    {
        if ($recoveredQty <= 0) {
            return '';
        }

        // Fetch previous weeks for the same target item in the same monthly target
        $sql = "
            SELECT pwp.week_number, pwpi.gap_qty, pwpi.gap_pct, pmti.target_mode
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            WHERE pwp.target_id = ? AND pwpi.target_item_id = ? AND pwp.week_number < ?
            ORDER BY pwp.week_number ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('iii', $targetId, $targetItemId, $currentWeekNo);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $explanations = [];
        $remainingRecovery = (float)$recoveredQty;

        foreach ($rows as $row) {
            $gap = ($row['target_mode'] === 'pct') ? (float)$row['gap_pct'] : (float)$row['gap_qty'];
            if ($gap > 0) {
                $covered = min($remainingRecovery, $gap);
                $remainingRecovery -= $covered;

                $unit = ($row['target_mode'] === 'pct') ? '%' : '';
                $explanations[] = "Week {$row['week_number']}-এর বকেয়া " . number_format($covered, 2) . "{$unit} কাজ Week {$currentWeekNo}-এ সম্পন্ন হয়েছে";

                if ($remainingRecovery <= 0) {
                    break;
                }
            }
        }

        if (empty($explanations)) {
            $unit = ($targetMode === 'pct') ? '%' : '';
            return "আগের বকেয়া " . number_format($recoveredQty, 2) . "{$unit} কাজ Week {$currentWeekNo}-এ সম্পন্ন হয়েছে।";
        }

        return implode(", ", $explanations) . "।";
    }

    /**
     * Main action to display report page
     */
    public function index()
    {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Fetch query parameters
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
        $interval = isset($_GET['interval']) ? trim($_GET['interval']) : 'monthly';
        if (!in_array($interval, ['weekly', '15-day', 'monthly', 'quarterly', 'yearly'])) {
            $interval = 'monthly';
        }
        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

        // Fetch site engineers for filter option (for role_id 1, 3, 15, 20, 22)
        $selSiteEngineerId = 0;
        $siteEngineersList = [];
        if (in_array($roleId, [1, 3, 33, 15, 20, 22])) {
            $selSiteEngineerId = isset($_GET['site_engineer_id']) ? (int)$_GET['site_engineer_id'] : 0;
            $engSql = "SELECT id, full_name FROM users WHERE role_id = 12 ORDER BY full_name ASC";
            $engRes = $this->db->query($engSql);
            $siteEngineersList = $engRes ? $engRes->fetch_all(MYSQLI_ASSOC) : [];
        }

        // 1. Fetch authorized projects for dropdown list (only under_construction = 1)
        $projectSql = "SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1";
        if (in_array($roleId, [1, 2, 3, 33, 17, 20, 22])) {
            // Privileged roles see all under_construction = 1 projects
        } elseif ($roleId === 12) {
            $projectSql .= " AND (site_engineer_id = {$userId} OR created_by = {$userId})";
        } elseif ($roleId === 15) {
            $projectSql .= " AND (pm_id = {$userId} OR created_by = {$userId})";
        }
        $projectSql .= " ORDER BY name ASC";
        $projectsRes = $this->db->query($projectSql);
        $projectsList = $projectsRes ? $projectsRes->fetch_all(MYSQLI_ASSOC) : [];

        // Fallback: If no projects specifically assigned to user, fetch all under_construction = 1 projects
        if (empty($projectsList)) {
            $fallbackSql = "SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC";
            $fallbackRes = $this->db->query($fallbackSql);
            $projectsList = $fallbackRes ? $fallbackRes->fetch_all(MYSQLI_ASSOC) : [];
        }

        // Build list of project IDs the user has access to
        $authorizedProjectIds = array_column($projectsList, 'id');
        if (empty($authorizedProjectIds)) {
            // Handle case where user is assigned to no projects
            $this->view('phase2/progress_report/index', [
                'projects' => [],
                'siteEngineers' => $siteEngineersList,
                'reportData' => [],
                'filters' => [
                    'project_id' => $projectId,
                    'site_engineer_id' => $selSiteEngineerId,
                    'interval' => $interval,
                    'year' => $year,
                    'month' => $month
                ]
            ]);
            return;
        }

        // Apply project filter
        $projectFilterStr = "";
        if ($projectId > 0 && in_array($projectId, $authorizedProjectIds)) {
            $projectFilterStr = "AND p.id = " . $projectId;
        } else {
            $projectFilterStr = "AND p.id IN (" . implode(',', $authorizedProjectIds) . ")";
        }

        // Apply site engineer filter
        $engineerFilterStr = "";
        if ($selSiteEngineerId > 0) {
            $engineerFilterStr = "AND p.site_engineer_id = " . $selSiteEngineerId;
        }

        // Apply year and month filter
        $yearFilterStr = "AND pmt.target_year = " . $year;
        $monthFilterStr = "";
        // Month filter only applies to weekly, 15-day and monthly views
        if (in_array($interval, ['weekly', '15-day', 'monthly']) && $month > 0) {
            $monthFilterStr = "AND pmt.target_month_number = " . $month;
        }

        // 2. Query data based on interval type
        $reportData = [];

        if ($interval === 'weekly') {
            $sql = "
                SELECT 
                    p.id AS project_id,
                    p.name AS project_name,
                    u.full_name AS site_engineer_name,
                    pmt.id AS target_id,
                    pmt.target_month,
                    pmt.target_year,
                    pmt.target_month_number,
                    pmti.id AS target_item_id,
                    pmti.work_item_name,
                    pmti.unit,
                    pmti.target_mode,
                    pwp.week_number,
                    pwpi.planned_qty,
                    pwpi.planned_pct,
                    pwpi.achieved_qty,
                    pwpi.achieved_pct,
                    pwpi.carryover_qty,
                    pwpi.carryover_pct,
                    pwpi.gap_qty,
                    pwpi.gap_pct,
                    pwpi.recovered_qty,
                    pwpi.recovered_pct,
                    pwpi.earned_star,
                    pwpi.status
                FROM phase_weekly_progress_items pwpi
                INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
                INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                INNER JOIN projects p ON p.id = pwp.project_id
                LEFT JOIN users u ON u.id = p.site_engineer_id
                WHERE p.deleted_at IS NULL
                  $projectFilterStr
                  $engineerFilterStr
                  $yearFilterStr
                  $monthFilterStr
                ORDER BY p.id, pmt.target_year, pmt.target_month_number, pwp.week_number, pmti.sort_order
            ";
            $res = $this->db->query($sql);
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

            // Compile recovery descriptions for weekly view
            foreach ($rows as &$row) {
                $recoveredVal = ($row['target_mode'] === 'pct') ? (float)$row['recovered_pct'] : (float)$row['recovered_qty'];
                $row['recovery_details'] = $this->getRecoveryExplanation(
                    (int)$row['target_id'],
                    (int)$row['target_item_id'],
                    (int)$row['week_number'],
                    $recoveredVal,
                    $row['target_mode']
                );
            }
            unset($row);
            $reportData = $rows;

        } else {
            // Aggregated intervals (15-Day, Monthly, Quarterly, Yearly)
            $groupByStr = "";
            $periodSelectStr = "";

            if ($interval === '15-day') {
                $periodSelectStr = "IF(pwp.week_number <= 2, 1, 2) AS period_number";
            } elseif ($interval === 'monthly') {
                $periodSelectStr = "pmt.target_month_number AS period_number";
            } elseif ($interval === 'quarterly') {
                $periodSelectStr = "CEIL(pmt.target_month_number / 3) AS period_number";
            } else { // yearly
                $periodSelectStr = "pmt.target_year AS period_number";
            }
            $groupByStr = "p.id, p.name, pmt.id, pmt.target_month, pmt.target_year, pmt.target_month_number, pmti.id, pmti.work_item_name, pmti.unit, pmti.target_mode, period_number, pmti.sort_order";

            $sql = "
                SELECT 
                    p.id AS project_id,
                    p.name AS project_name,
                    MAX(u.full_name) AS site_engineer_name,
                    pmt.id AS target_id,
                    pmt.target_month,
                    pmt.target_year,
                    pmt.target_month_number,
                    pmti.id AS target_item_id,
                    pmti.work_item_name,
                    pmti.unit,
                    pmti.target_mode,
                    $periodSelectStr,
                    SUM(pwpi.planned_qty) AS planned_qty,
                    SUM(pwpi.planned_pct) AS planned_pct,
                    SUM(pwpi.achieved_qty) AS achieved_qty,
                    SUM(pwpi.achieved_pct) AS achieved_pct,
                    SUM(pwpi.carryover_qty) AS carryover_qty,
                    SUM(pwpi.carryover_pct) AS carryover_pct,
                    SUM(pwpi.gap_qty) AS gap_qty,
                    SUM(pwpi.gap_pct) AS gap_pct,
                    SUM(pwpi.recovered_qty) AS recovered_qty,
                    SUM(pwpi.recovered_pct) AS recovered_pct,
                    MAX(pwpi.earned_star) AS earned_star,
                    MAX(pwpi.status) AS status
                FROM phase_weekly_progress_items pwpi
                INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
                INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                INNER JOIN projects p ON p.id = pwp.project_id
                LEFT JOIN users u ON u.id = p.site_engineer_id
                WHERE p.deleted_at IS NULL
                  $projectFilterStr
                  $engineerFilterStr
                  $yearFilterStr
                  $monthFilterStr
                GROUP BY $groupByStr
                ORDER BY p.id, pmt.target_year, pmt.target_month_number, period_number, pmti.sort_order
            ";
            $res = $this->db->query($sql);
            $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

            // Compile aggregated recovery details
            foreach ($rows as &$row) {
                $recoveredVal = ($row['target_mode'] === 'pct') ? (float)$row['recovered_pct'] : (float)$row['recovered_qty'];
                $row['recovery_details'] = "";
                if ($recoveredVal > 0) {
                    // Find all weeks contributing to this period's recovery
                    $weekCondition = "";
                    if ($interval === '15-day') {
                        $weekCondition = ((int)$row['period_number'] === 1) ? "AND pwp.week_number <= 2" : "AND pwp.week_number > 2";
                    } elseif ($interval === 'monthly') {
                        $weekCondition = "AND pmt.target_month_number = " . (int)$row['period_number'];
                    } elseif ($interval === 'quarterly') {
                        $q = (int)$row['period_number'];
                        $weekCondition = "AND pmt.target_month_number BETWEEN " . (($q - 1) * 3 + 1) . " AND " . ($q * 3);
                    }
                    // For yearly, it checks all weeks in that year (no extra week filter needed)

                    $detailSql = "
                        SELECT pmt.id AS target_id, pwp.week_number, pwpi.recovered_qty, pwpi.recovered_pct
                        FROM phase_weekly_progress_items pwpi
                        INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                        INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                        WHERE pwpi.target_item_id = ? AND pmt.target_year = ? $weekCondition
                          AND (pwpi.recovered_qty > 0 OR pwpi.recovered_pct > 0)
                        ORDER BY pwp.week_number ASC
                    ";
                    $detailStmt = $this->db->prepare($detailSql);
                    $detailStmt->bind_param('ii', $row['target_item_id'], $row['target_year']);
                    $detailStmt->execute();
                    $weekRecoveries = $detailStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                    $weekTexts = [];
                    foreach ($weekRecoveries as $wr) {
                        $wrRecovered = ($row['target_mode'] === 'pct') ? (float)$wr['recovered_pct'] : (float)$wr['recovered_qty'];
                        $txt = $this->getRecoveryExplanation(
                            (int)$wr['target_id'],
                            (int)$row['target_item_id'],
                            (int)$wr['week_number'],
                            $wrRecovered,
                            $row['target_mode']
                        );
                        if (!empty($txt)) {
                            $weekTexts[] = $txt;
                        }
                    }
                    $row['recovery_details'] = implode(" ", $weekTexts);
                }
            }
            unset($row);
            $reportData = $rows;
        }

        // 3. Render view page with variables
        $this->view('phase2/progress_report/index', [
            'projects' => $projectsList,
            'siteEngineers' => $siteEngineersList,
            'reportData' => $reportData,
            'filters' => [
                'project_id' => $projectId,
                'site_engineer_id' => $selSiteEngineerId,
                'interval' => $interval,
                'year' => $year,
                'month' => $month
            ]
        ]);
    }
}
