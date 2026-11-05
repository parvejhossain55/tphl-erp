<?php

class Con_MonthlyTarget
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // HEADER (phase_monthly_targets)
    // =========================================================================

    // Get all targets for a project (with phase name, item count, progress)
    public function getByProject(int $project_id): array
    {
        $stmt = $this->db->prepare("SELECT pmt.*,
                   pp.phase_name,
                   pp.weight_percentage AS phase_weight,
                   COUNT(pmti.id)       AS item_count,
                   COALESCE(
                       SUM(
                           CASE
                               WHEN pmti.target_mode = 'pct' THEN
                                   COALESCE((
                                       SELECT SUM(dwil.pct_done_today)
                                       FROM daily_work_item_logs dwil
                                       INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                       WHERE dwil.target_item_id = pmti.id
                                         AND dpl.qc_status = 'Approved'
                                         AND dpl.pm_status = 'Approved'
                                   ), 0)
                               ELSE
                                   COALESCE(
                                       (
                                           (
                                               COALESCE((
                                                   SELECT SUM(pwpi.achieved_qty)
                                                   FROM phase_weekly_progress_items pwpi
                                                   INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                                                   WHERE pwpi.target_item_id = pmti.id
                                                     AND pwp.qc_status = 'Approved'
                                                     AND pwp.pm_status = 'Approved'
                                               ), 0) +
                                               COALESCE((
                                                   SELECT SUM(dwil.qty_done_today)
                                                   FROM daily_work_item_logs dwil
                                                   INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                                   INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                                                   WHERE dwil.target_item_id = pmti.id
                                                     AND dpl.qc_status = 'Approved'
                                                     AND dpl.pm_status = 'Approved'
                                                     AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                                               ), 0)
                                           ) / NULLIF(pmti.monthly_target_qty, 0)
                                       ) * 100, 0
                                   )
                           END
                       ), 0
                   ) AS avg_actual_pct
            FROM phase_monthly_targets pmt
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN  phase_monthly_target_items pmti ON pmti.target_id = pmt.id
            WHERE pmt.project_id = ?
            GROUP BY pmt.id
            ORDER BY pmt.target_month ASC, pp.phase_order ASC
        ");
        $stmt->bind_param('i', $project_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get targets for a specific phase
    public function getByPhase(int $phase_id): array
    {
        $stmt = $this->db->prepare("
            SELECT pmt.*,
                   COUNT(pmti.id) AS item_count,
                   COALESCE(
                       SUM(
                           COALESCE((
                               SELECT SUM(pwpi.achieved_qty)
                               FROM phase_weekly_progress_items pwpi
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                               WHERE pwpi.target_item_id = pmti.id
                                 AND pwp.qc_status = 'Approved'
                                 AND pwp.pm_status = 'Approved'
                           ), 0) +
                           COALESCE((
                               SELECT SUM(dwil.qty_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                                 AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                           ), 0)
                       ), 0
                   ) AS total_actual_qty,
                   COALESCE(
                       SUM(
                           CASE
                               WHEN pmti.target_mode = 'pct' THEN
                                   COALESCE((
                                       SELECT SUM(dwil.pct_done_today)
                                       FROM daily_work_item_logs dwil
                                       INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                       WHERE dwil.target_item_id = pmti.id
                                         AND dpl.qc_status = 'Approved'
                                         AND dpl.pm_status = 'Approved'
                                   ), 0)
                               ELSE
                                   COALESCE(
                                       (
                                           (
                                               COALESCE((
                                                   SELECT SUM(pwpi.achieved_qty)
                                                   FROM phase_weekly_progress_items pwpi
                                                   INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                                                   WHERE pwpi.target_item_id = pmti.id
                                                     AND pwp.qc_status = 'Approved'
                                                     AND pwp.pm_status = 'Approved'
                                               ), 0) +
                                               COALESCE((
                                                   SELECT SUM(dwil.qty_done_today)
                                                   FROM daily_work_item_logs dwil
                                                   INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                                   INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                                                   WHERE dwil.target_item_id = pmti.id
                                                     AND dpl.qc_status = 'Approved'
                                                     AND dpl.pm_status = 'Approved'
                                                     AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                                               ), 0)
                                           ) / NULLIF(pmti.monthly_target_qty, 0)
                                       ) * 100, 0
                                   )
                           END
                       ), 0
                   ) AS avg_actual_pct
            FROM phase_monthly_targets pmt
            LEFT JOIN phase_monthly_target_items pmti ON pmti.target_id = pmt.id
            WHERE pmt.phase_id = ?
            GROUP BY pmt.id
            ORDER BY pmt.target_month ASC
        ");
        $stmt->bind_param('i', $phase_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Find single target
    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT pmt.*, 
                   pp.phase_name, 
                   pp.weight_percentage AS phase_weight,
                   pp.planned_start_date,
                   pp.planned_end_date,
                   pp.status AS phase_status,
                   p.name AS project_name,
                   p.block_no AS project_block,
                   p.plot_no AS project_plot,
                   p.building_type AS building_type
            FROM phase_monthly_targets pmt
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            INNER JOIN projects p ON p.id = pmt.project_id
            WHERE pmt.id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    // Check duplicate month for same phase
    public function existsForMonth(int $project_id, int $phase_id, string $month): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS c FROM phase_monthly_targets
            WHERE project_id=? AND phase_id=? AND target_month=?
        ");
        $stmt->bind_param('iis', $project_id, $phase_id, $month);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['c'] > 0;
    }

    public function create(array $d): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO phase_monthly_targets
                (project_id, phase_id, target_month, target_month_number, target_year,
                 planning_status, responsible_team, contractor_id, created_by)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        $dt = new DateTime($d['target_month']);
        $mnum = (int) $dt->format('n');
        $yr = (int) $dt->format('Y');

        $planningStatus = $d['planning_status'] ?? 'Draft';
        $responsibleTeam = $d['responsible_team'] ?? null;
        $contractorId = !empty($d['contractor_id']) ? (int) $d['contractor_id'] : null;
        $createdBy = $d['created_by'] ?? null;

        $stmt->bind_param(
            'iisiissii',
            $d['project_id'],
            $d['phase_id'],
            $d['target_month'],
            $mnum,
            $yr,
            $planningStatus,
            $responsibleTeam,
            $contractorId,
            $createdBy
        );
        $stmt->execute();
        return (int) $this->db->insert_id;
    }

    public function update(int $id, array $d): bool
    {
        $stmt = $this->db->prepare("
            UPDATE phase_monthly_targets
            SET planning_status=?, responsible_team=?, contractor_id=?, updated_at=NOW()
            WHERE id=?
        ");
        $contractorId = !empty($d['contractor_id']) ? (int) $d['contractor_id'] : null;
        $stmt->bind_param('ssii', $d['planning_status'], $d['responsible_team'], $contractorId, $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM phase_monthly_targets WHERE id=?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    // =========================================================================
    // ITEMS (phase_monthly_target_items)
    // =========================================================================

    public function getItems(int $target_id): array
    {
        $stmt = $this->db->prepare("
            SELECT pmti.*,
                   (
                       COALESCE((
                           SELECT SUM(pwpi.achieved_qty)
                           FROM phase_weekly_progress_items pwpi
                           INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                           WHERE pwpi.target_item_id = pmti.id
                             AND pwp.qc_status = 'Approved'
                             AND pwp.pm_status = 'Approved'
                       ), 0) +
                       COALESCE((
                           SELECT SUM(dwil.qty_done_today)
                           FROM daily_work_item_logs dwil
                           INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                           INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                           WHERE dwil.target_item_id = pmti.id
                             AND dpl.qc_status = 'Approved'
                             AND dpl.pm_status = 'Approved'
                             AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                       ), 0)
                   ) AS actual_qty,
                   
                   -- Week 1 Achieved
                   CASE
                       WHEN pmti.target_mode = 'pct' THEN
                           COALESCE((
                               SELECT SUM(dwil.pct_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 1
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                           ), 0)
                       ELSE
                           COALESCE((
                               SELECT SUM(pwpi.achieved_qty)
                               FROM phase_weekly_progress_items pwpi
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                               WHERE pwpi.target_item_id = pmti.id
                                 AND pwp.week_number = 1
                                 AND pwp.qc_status = 'Approved'
                                 AND pwp.pm_status = 'Approved'
                           ), 0) +
                           COALESCE((
                               SELECT SUM(dwil.qty_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 1
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                                 AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                           ), 0)
                   END AS week_1_achieved,
                   
                   -- Week 2 Achieved
                   CASE
                       WHEN pmti.target_mode = 'pct' THEN
                           COALESCE((
                               SELECT SUM(dwil.pct_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 2
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                           ), 0)
                       ELSE
                           COALESCE((
                               SELECT SUM(pwpi.achieved_qty)
                               FROM phase_weekly_progress_items pwpi
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                               WHERE pwpi.target_item_id = pmti.id
                                 AND pwp.week_number = 2
                                 AND pwp.qc_status = 'Approved'
                                 AND pwp.pm_status = 'Approved'
                           ), 0) +
                           COALESCE((
                               SELECT SUM(dwil.qty_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 2
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                                 AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                           ), 0)
                   END AS week_2_achieved,
                   
                   -- Week 3 Achieved
                   CASE
                       WHEN pmti.target_mode = 'pct' THEN
                           COALESCE((
                               SELECT SUM(dwil.pct_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 3
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                           ), 0)
                       ELSE
                           COALESCE((
                               SELECT SUM(pwpi.achieved_qty)
                               FROM phase_weekly_progress_items pwpi
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                               WHERE pwpi.target_item_id = pmti.id
                                 AND pwp.week_number = 3
                                 AND pwp.qc_status = 'Approved'
                                 AND pwp.pm_status = 'Approved'
                           ), 0) +
                           COALESCE((
                               SELECT SUM(dwil.qty_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 3
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                                 AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                           ), 0)
                   END AS week_3_achieved,
                   
                   -- Week 4 Achieved
                   CASE
                       WHEN pmti.target_mode = 'pct' THEN
                           COALESCE((
                               SELECT SUM(dwil.pct_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 4
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                           ), 0)
                       ELSE
                           COALESCE((
                               SELECT SUM(pwpi.achieved_qty)
                               FROM phase_weekly_progress_items pwpi
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                               WHERE pwpi.target_item_id = pmti.id
                                 AND pwp.week_number = 4
                                 AND pwp.qc_status = 'Approved'
                                 AND pwp.pm_status = 'Approved'
                           ), 0) +
                           COALESCE((
                               SELECT SUM(dwil.qty_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND pwp.week_number = 4
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                                 AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                           ), 0)
                   END AS week_4_achieved,
                   CASE
                       WHEN pmti.target_mode = 'pct' THEN
                           COALESCE(
                               (
                                   SELECT (pmti.monthly_target_pct / 100.0) * ppid.weight_pct
                                   FROM project_phase_item_details ppid
                                   WHERE ppid.phase_item_id = pmti.phase_item_id
                                     AND ppid.work_type_id = pmti.work_type_id
                               ),
                               (pmti.monthly_target_pct / 100.0) * ppi.weight_pct,
                               0
                           )
                       ELSE
                           COALESCE((
                               SELECT (pmti.monthly_target_qty / NULLIF(ppid.total_boq_qty, 0)) * ppid.weight_pct
                               FROM project_phase_item_details ppid
                               WHERE ppid.phase_item_id = pmti.phase_item_id
                                 AND ppid.work_type_id = pmti.work_type_id
                           ), 0)
                   END AS planned_progress_pct,
                   CASE
                       WHEN pmti.target_mode = 'pct' THEN
                           COALESCE((
                               SELECT SUM(dwil.pct_done_today)
                               FROM daily_work_item_logs dwil
                               INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                               WHERE dwil.target_item_id = pmti.id
                                 AND dpl.qc_status = 'Approved'
                                 AND dpl.pm_status = 'Approved'
                           ), 0)
                       ELSE
                           COALESCE(
                               (
                                   (
                                       COALESCE((
                                           SELECT SUM(pwpi.achieved_qty)
                                           FROM phase_weekly_progress_items pwpi
                                           INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                                           WHERE pwpi.target_item_id = pmti.id
                                             AND pwp.qc_status = 'Approved'
                                             AND pwp.pm_status = 'Approved'
                                       ), 0) +
                                       COALESCE((
                                           SELECT SUM(dwil.qty_done_today)
                                           FROM daily_work_item_logs dwil
                                           INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                           INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                                           WHERE dwil.target_item_id = pmti.id
                                             AND dpl.qc_status = 'Approved'
                                             AND dpl.pm_status = 'Approved'
                                             AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                                       ), 0)
                                   ) / NULLIF(pmti.monthly_target_qty, 0)
                               ) * 100, 0
                           )
                   END AS actual_progress_pct,
                   wt.work_type_name,
                   dt.name AS design_type_name,
                   ppi.weight_pct AS sub_item_weight,
                   c.name AS contractor_name,
                   wo.wo_number AS work_order_number,
                   wo.title AS work_order_title,
                   woi.description AS work_order_item_desc
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            LEFT JOIN project_phase_items ppi ON ppi.phase_id = pmt.phase_id AND ppi.item_name = pmti.work_item_name COLLATE utf8mb4_general_ci
            LEFT JOIN work_types  wt ON wt.id = pmti.work_type_id
            LEFT JOIN design_types dt ON dt.id = wt.design_type_id
            LEFT JOIN contractors c ON c.id = pmti.contractor_id
            LEFT JOIN con_work_orders wo ON wo.id = pmti.work_order_id
            LEFT JOIN con_work_order_items woi ON woi.id = pmti.work_order_item_id
            WHERE pmti.target_id = ?
            ORDER BY pmti.sort_order ASC, pmti.id ASC
        ");
        $stmt->bind_param('i', $target_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findItem(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM phase_monthly_target_items WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function createItem(array $d): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO phase_monthly_target_items
                (target_id, work_type_id, work_item_name, phase_item_id, floor_type, floor_no, unit, target_mode,
                 monthly_target_qty, monthly_target_pct, actual_qty,
                 planned_progress_pct, actual_progress_pct,
                 start_date, end_date, responsible_team, contractor_id, work_order_id, work_order_item_id,
                 priority, status,
                 week_1_qty, week_2_qty, week_3_qty, week_4_qty,
                 week_1_pct, week_2_pct, week_3_pct, week_4_pct,
                 recovery_notes, sort_order)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $nextSort = $this->nextSort((int) $d['target_id']);

        $workTypeId = $d['work_type_id'] ?? null;
        $workItemName = $d['work_item_name'];

        $phaseItemId = !empty($d['phase_item_id']) ? (int) $d['phase_item_id'] : null;
        if (empty($phaseItemId)) {
            // Find target's phase_id
            $targetSql = "SELECT phase_id FROM phase_monthly_targets WHERE id = ?";
            $tStmt = $this->db->prepare($targetSql);
            $tStmt->bind_param('i', $d['target_id']);
            $tStmt->execute();
            $tRow = $tStmt->get_result()->fetch_assoc();
            $phaseId = (int) ($tRow['phase_id'] ?? 0);

            if ($phaseId > 0) {
                $ppiSql = "SELECT id FROM project_phase_items WHERE phase_id = ? AND item_name = ? LIMIT 1";
                $pStmt = $this->db->prepare($ppiSql);
                $pStmt->bind_param('is', $phaseId, $workItemName);
                $pStmt->execute();
                $ppiRow = $pStmt->get_result()->fetch_assoc();
                if ($ppiRow) {
                    $phaseItemId = (int) $ppiRow['id'];
                }
            }
        }

        $floorType = $d['floor_type'] ?? 'Typical_Floor';
        $floorNo = isset($d['floor_no']) && $d['floor_no'] !== '' ? (int) $d['floor_no'] : null;
        $unit = $d['unit'] ?? null;
        
        $targetMode = $d['target_mode'] ?? 'qty';
        if (trim($unit) === '%') {
            $targetMode = 'pct';
        }

        $monthlyTargetQty = $targetMode === 'pct' ? 0.00 : (float) ($d['monthly_target_qty'] ?? 0);
        $monthlyTargetPct = $targetMode === 'pct' ? ((float) ($d['monthly_target_pct'] ?? 0) ?: (float) ($d['monthly_target_qty'] ?? 0)) : 0.00;
        $actualQty = $d['actual_qty'] ?? 0;
        $plannedProgressPct = $d['planned_progress_pct'] ?? 0;
        $actualProgressPct = $d['actual_progress_pct'] ?? 0;
        $startDate = $d['start_date'] ?? null;
        $endDate = $d['end_date'] ?? null;
        $responsibleTeam = $d['responsible_team'] ?? null;
        $contractorId = !empty($d['contractor_id']) ? (int) $d['contractor_id'] : null;
        $workOrderId = !empty($d['work_order_id']) ? (int) $d['work_order_id'] : null;
        $workOrderItemId = !empty($d['work_order_item_id']) ? (int) $d['work_order_item_id'] : null;
        $priority = $d['priority'] ?? 'Medium';
        $status = $d['status'] ?? 'Draft';

        $week1Qty = $targetMode === 'pct' ? 0.00 : (float) ($d['week_1_qty'] ?? 0);
        $week2Qty = $targetMode === 'pct' ? 0.00 : (float) ($d['week_2_qty'] ?? 0);
        $week3Qty = $targetMode === 'pct' ? 0.00 : (float) ($d['week_3_qty'] ?? 0);
        $week4Qty = $targetMode === 'pct' ? 0.00 : (float) ($d['week_4_qty'] ?? 0);

        $week1Pct = $targetMode === 'pct' ? ((float) ($d['week_1_pct'] ?? 0) ?: (float) ($d['week_1_qty'] ?? 0)) : 0.00;
        $week2Pct = $targetMode === 'pct' ? ((float) ($d['week_2_pct'] ?? 0) ?: (float) ($d['week_2_qty'] ?? 0)) : 0.00;
        $week3Pct = $targetMode === 'pct' ? ((float) ($d['week_3_pct'] ?? 0) ?: (float) ($d['week_3_qty'] ?? 0)) : 0.00;
        $week4Pct = $targetMode === 'pct' ? ((float) ($d['week_4_pct'] ?? 0) ?: (float) ($d['week_4_qty'] ?? 0)) : 0.00;

        $recoveryNotes = $d['recovery_notes'] ?? null;

        // Validation for pct mode
        if ($targetMode === 'pct') {
            $weekTotal = $week1Pct + $week2Pct + $week3Pct + $week4Pct;
            if (round($weekTotal, 2) > round($monthlyTargetPct, 2)) {
                throw new \Exception("Weekly % total ({$weekTotal}%) exceeds monthly target ({$monthlyTargetPct}%).");
            }
        }

        $stmt->bind_param(
            'iisisissdddddsssiiissddddddddsi',
            $d['target_id'],
            $workTypeId,
            $workItemName,
            $phaseItemId,
            $floorType,
            $floorNo,
            $unit,
            $targetMode,
            $monthlyTargetQty,
            $monthlyTargetPct,
            $actualQty,
            $plannedProgressPct,
            $actualProgressPct,
            $startDate,
            $endDate,
            $responsibleTeam,
            $contractorId,
            $workOrderId,
            $workOrderItemId,
            $priority,
            $status,
            $week1Qty,
            $week2Qty,
            $week3Qty,
            $week4Qty,
            $week1Pct,
            $week2Pct,
            $week3Pct,
            $week4Pct,
            $recoveryNotes,
            $nextSort
        );
        $stmt->execute();
        return (int) $this->db->insert_id;
    }

    public function batchUpdateItems(array $items): bool
    {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE phase_monthly_target_items SET
                    work_type_id        = ?,
                    work_item_name      = ?,
                    phase_item_id       = ?,
                    floor_type          = ?,
                    floor_no            = ?,
                    unit                = ?,
                    target_mode         = ?,
                    monthly_target_qty  = ?,
                    monthly_target_pct  = ?,
                    actual_qty          = ?,
                    planned_progress_pct= ?,
                    actual_progress_pct = ?,
                    start_date          = ?,
                    end_date            = ?,
                    week_1_qty          = ?,
                    week_2_qty          = ?,
                    week_3_qty          = ?,
                    week_4_qty          = ?,
                    week_1_pct          = ?,
                    week_2_pct          = ?,
                    week_3_pct          = ?,
                    week_4_pct          = ?,
                    responsible_team    = ?,
                    contractor_id       = ?,
                    work_order_id       = ?,
                    work_order_item_id  = ?,
                    priority            = ?,
                    status              = ?,
                    recovery_notes      = ?
                WHERE id = ?
            ");
            foreach ($items as $i) {
                $id = (int) $i['id'];
                $wid = (!empty($i['work_type_id']) && $i['work_type_id'] != '') ? (int) $i['work_type_id'] : null;
                $name = trim($i['work_item_name']);
                $unit = $i['unit'] ?? null;
                $targetMode = $i['target_mode'] ?? 'qty';
                if (trim($unit) === '%') {
                    $targetMode = 'pct';
                }

                $qty = $targetMode === 'pct' ? 0.00 : (float) ($i['monthly_target_qty'] ?? 0);
                $monthlyPct = $targetMode === 'pct' ? ((float) ($i['monthly_target_pct'] ?? 0) ?: (float) ($i['monthly_target_qty'] ?? 0)) : 0.00;

                $plannedPct = (float) ($i['planned_progress_pct'] ?? 0);
                $startDate = !empty($i['start_date']) ? $i['start_date'] : null;
                $endDate = !empty($i['end_date']) ? $i['end_date'] : null;

                $w1 = $targetMode === 'pct' ? 0.00 : (float) ($i['week_1_qty'] ?? 0);
                $w2 = $targetMode === 'pct' ? 0.00 : (float) ($i['week_2_qty'] ?? 0);
                $w3 = $targetMode === 'pct' ? 0.00 : (float) ($i['week_3_qty'] ?? 0);
                $w4 = $targetMode === 'pct' ? 0.00 : (float) ($i['week_4_qty'] ?? 0);

                $w1pct = $targetMode === 'pct' ? ((float) ($i['week_1_pct'] ?? 0) ?: (float) ($i['week_1_qty'] ?? 0)) : 0.00;
                $w2pct = $targetMode === 'pct' ? ((float) ($i['week_2_pct'] ?? 0) ?: (float) ($i['week_2_qty'] ?? 0)) : 0.00;
                $w3pct = $targetMode === 'pct' ? ((float) ($i['week_3_pct'] ?? 0) ?: (float) ($i['week_3_qty'] ?? 0)) : 0.00;
                $w4pct = $targetMode === 'pct' ? ((float) ($i['week_4_pct'] ?? 0) ?: (float) ($i['week_4_qty'] ?? 0)) : 0.00;

                $respTeam = $i['responsible_team'] ?? null;
                $contractorId = (!empty($i['contractor_id']) && $i['contractor_id'] != '') ? (int) $i['contractor_id'] : null;
                $workOrderId = (!empty($i['work_order_id']) && $i['work_order_id'] != '') ? (int) $i['work_order_id'] : null;
                $workOrderItemId = (!empty($i['work_order_item_id']) && $i['work_order_item_id'] != '') ? (int) $i['work_order_item_id'] : null;
                $prio = $i['priority'] ?? 'Medium';
                $stat = $i['status'] ?? 'Draft';
                $recNotes = !empty($i['recovery_notes']) ? trim($i['recovery_notes']) : null;
                $floorType = $i['floor_type'] ?? 'Typical_Floor';
                $floorNo = isset($i['floor_no']) && $i['floor_no'] !== '' ? (int) $i['floor_no'] : null;

                // Validation logic for pct mode
                if ($targetMode === 'pct') {
                    $weekTotal = $w1pct + $w2pct + $w3pct + $w4pct;
                    if (round($weekTotal, 2) > round($monthlyPct, 2)) {
                        throw new \Exception("Weekly % total ({$weekTotal}%) exceeds monthly target ({$monthlyPct}%).");
                    }
                }

                // Fetch existing actual values (read-only, do not overwrite from form)
                $existStmt = $this->db->prepare("SELECT target_id, actual_qty, actual_progress_pct FROM phase_monthly_target_items WHERE id=?");
                $existStmt->bind_param('i', $id);
                $existStmt->execute();
                $existRow = $existStmt->get_result()->fetch_assoc();
                $actQty = (float) ($existRow['actual_qty'] ?? 0);
                $actProgPct = (float) ($existRow['actual_progress_pct'] ?? 0);

                // Recalculate actual progress percentage based on updated monthly target qty
                $actualPct = 0.00;
                if ($targetMode === 'pct') {
                    $actualPct = $actProgPct;
                } else {
                    if ($qty > 0) {
                        $actualPct = min(100.0, ($actQty / $qty) * 100);
                    }
                }

                $phaseItemId = !empty($i['phase_item_id']) ? (int) $i['phase_item_id'] : null;
                if (empty($phaseItemId)) {
                    // Find target's phase_id
                    $pmtiQuery = $this->db->prepare("
                        SELECT pmti.target_id, pmt.phase_id 
                        FROM phase_monthly_target_items pmti
                        JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
                        WHERE pmti.id = ?
                    ");
                    $pmtiQuery->bind_param('i', $id);
                    $pmtiQuery->execute();
                    $pmtiRow = $pmtiQuery->get_result()->fetch_assoc();
                    $phaseId = (int) ($pmtiRow['phase_id'] ?? 0);

                    if ($phaseId > 0) {
                        $ppiSql = "SELECT id FROM project_phase_items WHERE phase_id = ? AND item_name = ? LIMIT 1";
                        $pStmt = $this->db->prepare($ppiSql);
                        $pStmt->bind_param('is', $phaseId, $name);
                        $pStmt->execute();
                        $ppiRow = $pStmt->get_result()->fetch_assoc();
                        if ($ppiRow) {
                            $phaseItemId = (int) $ppiRow['id'];
                        }
                    }
                }

                $stmt->bind_param(
                    'isisissdddddssddddddddsiiisssi',
                    $wid,
                    $name,
                    $phaseItemId,
                    $floorType,
                    $floorNo,
                    $unit,
                    $targetMode,
                    $qty,
                    $monthlyPct,
                    $actQty,
                    $plannedPct,
                    $actualPct,
                    $startDate,
                    $endDate,
                    $w1,
                    $w2,
                    $w3,
                    $w4,
                    $w1pct,
                    $w2pct,
                    $w3pct,
                    $w4pct,
                    $respTeam,
                    $contractorId,
                    $workOrderId,
                    $workOrderItemId,
                    $prio,
                    $stat,
                    $recNotes,
                    $id
                );
                $stmt->execute();
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function deleteItem(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM phase_monthly_target_items WHERE id=?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    // ── Progress summary for a target ────────────────────────────────────────
    public function getProgressSummary(int $target_id): array
    {
        $stmt = $this->db->prepare("SELECT
                COUNT(*) AS total_items,
                COALESCE(SUM(pmti.monthly_target_qty),0)   AS total_planned_qty,
                COALESCE(
                    SUM(
                        COALESCE((
                            SELECT SUM(pwpi.achieved_qty)
                            FROM phase_weekly_progress_items pwpi
                            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                            WHERE pwpi.target_item_id = pmti.id
                              AND pwp.qc_status = 'Approved'
                              AND pwp.pm_status = 'Approved'
                        ), 0) +
                        COALESCE((
                            SELECT SUM(dwil.qty_done_today)
                            FROM daily_work_item_logs dwil
                            INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                            INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                            WHERE dwil.target_item_id = pmti.id
                              AND dpl.qc_status = 'Approved'
                              AND dpl.pm_status = 'Approved'
                              AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                        ), 0)
                    ), 0
                ) AS total_actual_qty,
                COALESCE(
                    SUM(
                        CASE
                            WHEN pmti.target_mode = 'pct' THEN
                                COALESCE(
                                    (
                                        SELECT (pmti.monthly_target_pct / 100.0) * ppid.weight_pct
                                        FROM project_phase_item_details ppid
                                        WHERE ppid.phase_item_id = pmti.phase_item_id
                                          AND ppid.work_type_id = pmti.work_type_id
                                    ),
                                    (pmti.monthly_target_pct / 100.0) * ppi.weight_pct,
                                    0
                                )
                            ELSE
                                COALESCE((
                                    SELECT (pmti.monthly_target_qty / NULLIF(ppid.total_boq_qty, 0)) * ppid.weight_pct
                                    FROM project_phase_item_details ppid
                                    WHERE ppid.phase_item_id = pmti.phase_item_id
                                      AND ppid.work_type_id = pmti.work_type_id
                                ), 0)
                        END
                    ), 0
                ) AS avg_planned_pct,
                COALESCE(
                    SUM(
                        CASE
                            WHEN pmti.target_mode = 'pct' THEN
                                COALESCE((
                                    SELECT SUM(dwil.pct_done_today)
                                    FROM daily_work_item_logs dwil
                                    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                    WHERE dwil.target_item_id = pmti.id
                                      AND dpl.qc_status = 'Approved'
                                      AND dpl.pm_status = 'Approved'
                                ), 0)
                            ELSE
                                COALESCE(
                                    (
                                        (
                                            COALESCE((
                                                SELECT SUM(pwpi.achieved_qty)
                                                FROM phase_weekly_progress_items pwpi
                                                INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                                                WHERE pwpi.target_item_id = pmti.id
                                                  AND pwp.qc_status = 'Approved'
                                                  AND pwp.pm_status = 'Approved'
                                            ), 0) +
                                            COALESCE((
                                                SELECT SUM(dwil.qty_done_today)
                                                FROM daily_work_item_logs dwil
                                                INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                                                INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                                                WHERE dwil.target_item_id = pmti.id
                                                  AND dpl.qc_status = 'Approved'
                                                  AND dpl.pm_status = 'Approved'
                                                  AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
                                            ), 0)
                                        ) / NULLIF(pmti.monthly_target_qty, 0)
                                    ) * 100, 0
                                )
                        END
                    ), 0
                ) AS avg_actual_pct,
                SUM(CASE WHEN pmti.status='Draft'     THEN 1 ELSE 0 END) AS cnt_draft,
                SUM(CASE WHEN pmti.status='Ready'     THEN 1 ELSE 0 END) AS cnt_ready,
                SUM(CASE WHEN pmti.status='Approved'  THEN 1 ELSE 0 END) AS cnt_approved,
                SUM(CASE WHEN pmti.status='At Risk'   THEN 1 ELSE 0 END) AS cnt_at_risk,
                SUM(CASE WHEN pmti.status='Critical'  THEN 1 ELSE 0 END) AS cnt_critical
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            LEFT JOIN project_phase_items ppi ON ppi.phase_id = pmt.phase_id AND ppi.item_name = pmti.work_item_name COLLATE utf8mb4_general_ci
            WHERE pmti.target_id = ?
        ");
        $stmt->bind_param('i', $target_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?? [];
    }

    // ── Get work_types for a given design_type ───────────────────────────────
    public function getWorkTypes(?int $design_type_id = null): array
    {
        if ($design_type_id) {
            $stmt = $this->db->prepare("SELECT wt.id, wt.work_type_name, wt.default_phase_name, dt.name AS design_type_name
                FROM work_types wt
                LEFT JOIN design_types dt ON dt.id = wt.design_type_id
                WHERE wt.design_type_id = ?
                ORDER BY wt.work_type_name ASC
            ");
            $stmt->bind_param('i', $design_type_id);
        } else {
            $stmt = $this->db->prepare("
                SELECT wt.id, wt.work_type_name, wt.default_phase_name, dt.name AS design_type_name
                FROM work_types wt
                LEFT JOIN design_types dt ON dt.id = wt.design_type_id
                ORDER BY dt.name ASC, wt.work_type_name ASC
            ");
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getShortfallItemsForProject(int $projectId): array
    {
        $stmt = $this->db->prepare("
            SELECT pmti.*, 
                   pmt.phase_id,
                   pmt.target_month,
                   pp.phase_name,
                   (pmti.monthly_target_qty - pmti.actual_qty) AS shortfall_qty,
                   rp.id AS recovery_plan_id,
                   rp.status AS recovery_plan_status
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            LEFT JOIN recovery_plans rp 
                ON rp.project_id = pmt.project_id 
               AND rp.phase_id = pmt.phase_id 
               AND rp.plan_month = pmt.target_month
            WHERE pmt.project_id = ? 
              AND pmti.monthly_target_qty > 0
              AND pmti.actual_qty < pmti.monthly_target_qty
            ORDER BY pmt.target_month ASC, pmti.id ASC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    private function nextSort(int $target_id): int
    {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM phase_monthly_target_items WHERE target_id=?");
        $stmt->bind_param('i', $target_id);
        $stmt->execute();
        return (int) $stmt->get_result()->fetch_assoc()['n'];
    }

    public function saveDailyPctBreakdown(int $item_id, array $grid): bool
    {
        $this->db->begin_transaction();
        try {
            // Delete existing breakdown for this item
            $delStmt = $this->db->prepare("DELETE FROM daily_target_pct_breakdown WHERE target_item_id = ?");
            $delStmt->bind_param('i', $item_id);
            $delStmt->execute();

            if (!empty($grid)) {
                $insStmt = $this->db->prepare("
                    INSERT INTO daily_target_pct_breakdown (target_item_id, week_number, day_number, target_pct)
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE target_pct = VALUES(target_pct)
                ");
                foreach ($grid as $row) {
                    $week = (int) $row['week'];
                    $day = (int) $row['day'];
                    $pct = (float) $row['pct'];
                    $insStmt->bind_param('iiid', $item_id, $week, $day, $pct);
                    $insStmt->execute();
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getDailyPctBreakdown(int $item_id): array
    {
        $stmt = $this->db->prepare("SELECT week_number,day_number, target_pct
            FROM daily_target_pct_breakdown
            WHERE target_item_id = ?
            ORDER BY week_number, day_number
        ");
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $breakdown = [];
        foreach ($res as $row) {
            $w = (int) $row['week_number'];
            $d = (int) $row['day_number'];
            $breakdown[$w][$d] = (float) $row['target_pct'];
        }
        return $breakdown;
    }
}

