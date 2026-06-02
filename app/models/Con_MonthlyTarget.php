<?php

class Con_MonthlyTarget {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================================
    // HEADER (phase_monthly_targets)
    // =========================================================================

    // Get all targets for a project (with phase name, item count, progress)
    public function getByProject(int $project_id): array {
        $stmt = $this->db->prepare("
            SELECT pmt.*,
                   pp.phase_name,
                   pp.weight_percentage AS phase_weight,
                   COUNT(pmti.id)       AS item_count,
                   COALESCE(AVG(pmti.actual_progress_pct),0) AS avg_actual_pct
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
    public function getByPhase(int $phase_id): array {
        $stmt = $this->db->prepare("
            SELECT pmt.*,
                   COUNT(pmti.id) AS item_count,
                   COALESCE(SUM(pmti.actual_qty),0) AS total_actual_qty,
                   COALESCE(AVG(pmti.actual_progress_pct),0) AS avg_actual_pct
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
    public function find(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT pmt.*, 
                   pp.phase_name, 
                   pp.weight_percentage AS phase_weight,
                   pp.planned_start_date,
                   pp.planned_end_date,
                   pp.status AS phase_status,
                   p.name AS project_name,
                   p.block_no AS project_block,
                   p.plot_no AS project_plot
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
    public function existsForMonth(int $project_id, int $phase_id, string $month): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS c FROM phase_monthly_targets
            WHERE project_id=? AND phase_id=? AND target_month=?
        ");
        $stmt->bind_param('iis', $project_id, $phase_id, $month);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['c'] > 0;
    }

    public function create(array $d): int {
        $stmt = $this->db->prepare("
            INSERT INTO phase_monthly_targets
                (project_id, phase_id, target_month, target_month_number, target_year,
                 planning_status, responsible_team, created_by)
            VALUES (?,?,?,?,?,?,?,?)
        ");
        $dt   = new DateTime($d['target_month']);
        $mnum = (int)$dt->format('n');
        $yr   = (int)$dt->format('Y');

        $planningStatus = $d['planning_status'] ?? 'Draft';
        $responsibleTeam = $d['responsible_team'] ?? null;
        $createdBy = $d['created_by'] ?? null;

        $stmt->bind_param('iisiissi',
            $d['project_id'], $d['phase_id'], $d['target_month'],
            $mnum, $yr,
            $planningStatus,
            $responsibleTeam,
            $createdBy
        );
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function update(int $id, array $d): bool {
        $stmt = $this->db->prepare("
            UPDATE phase_monthly_targets
            SET planning_status=?, responsible_team=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->bind_param('ssi', $d['planning_status'], $d['responsible_team'], $id);
        return $stmt->execute();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM phase_monthly_targets WHERE id=?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    // =========================================================================
    // ITEMS (phase_monthly_target_items)
    // =========================================================================

    public function getItems(int $target_id): array {
        $stmt = $this->db->prepare("
            SELECT pmti.*,
                   wt.work_type_name,
                   dt.name AS design_type_name,
                   ppi.weight_pct AS sub_item_weight
            FROM phase_monthly_target_items pmti
            INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
            LEFT JOIN project_phase_items ppi ON ppi.phase_id = pmt.phase_id AND ppi.item_name = pmti.work_item_name COLLATE utf8mb4_general_ci
            LEFT JOIN work_types  wt ON wt.id = pmti.work_type_id
            LEFT JOIN design_types dt ON dt.id = wt.design_type_id
            WHERE pmti.target_id = ?
            ORDER BY pmti.sort_order ASC, pmti.id ASC
        ");
        $stmt->bind_param('i', $target_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function findItem(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM phase_monthly_target_items WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function createItem(array $d): int {
        $stmt = $this->db->prepare("
            INSERT INTO phase_monthly_target_items
                (target_id, work_type_id, work_item_name, unit,
                 monthly_target_qty, actual_qty,
                 planned_progress_pct, actual_progress_pct,
                 start_date, end_date, responsible_team,
                 priority, status,
                 week_1_qty, week_2_qty, week_3_qty, week_4_qty,
                 sort_order)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ");
        $nextSort = $this->nextSort((int)$d['target_id']);

        $workTypeId         = $d['work_type_id']         ?? null;
        $workItemName       = $d['work_item_name'];
        $unit               = $d['unit']                 ?? null;
        $monthlyTargetQty   = $d['monthly_target_qty']   ?? 0;
        $actualQty          = $d['actual_qty']           ?? 0;
        $plannedProgressPct = $d['planned_progress_pct'] ?? 0;
        $actualProgressPct  = $d['actual_progress_pct']  ?? 0;
        $startDate          = $d['start_date']           ?? null;
        $endDate            = $d['end_date']             ?? null;
        $responsibleTeam    = $d['responsible_team']     ?? null;
        $priority           = $d['priority']             ?? 'Medium';
        $status             = $d['status']               ?? 'Draft';
        $week1Qty           = $d['week_1_qty']           ?? 0;
        $week2Qty           = $d['week_2_qty']           ?? 0;
        $week3Qty           = $d['week_3_qty']           ?? 0;
        $week4Qty           = $d['week_4_qty']           ?? 0;

        $stmt->bind_param('iissddddsssssddddi',
            $d['target_id'],
            $workTypeId,
            $workItemName,
            $unit,
            $monthlyTargetQty,
            $actualQty,
            $plannedProgressPct,
            $actualProgressPct,
            $startDate,
            $endDate,
            $responsibleTeam,
            $priority,
            $status,
            $week1Qty,
            $week2Qty,
            $week3Qty,
            $week4Qty,
            $nextSort
        );
        $stmt->execute();
        return (int)$this->db->insert_id;
    }

    public function batchUpdateItems(array $items): bool {
        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare("
                UPDATE phase_monthly_target_items SET
                    work_type_id        = ?,
                    work_item_name      = ?,
                    unit                = ?,
                    monthly_target_qty  = ?,
                    actual_qty          = ?,
                    planned_progress_pct= ?,
                    actual_progress_pct = ?,
                    start_date          = ?,
                    end_date            = ?,
                    week_1_qty          = ?,
                    week_2_qty          = ?,
                    week_3_qty          = ?,
                    week_4_qty          = ?,
                    responsible_team    = ?,
                    priority            = ?,
                    status              = ?
                WHERE id = ?
            ");
            foreach ($items as $i) {
                $id           = (int)$i['id'];
                $wid          = (!empty($i['work_type_id']) && $i['work_type_id'] != '') ? (int)$i['work_type_id'] : null;
                $name         = trim($i['work_item_name']);
                $unit         = $i['unit'] ?? null;
                $qty          = (float)($i['monthly_target_qty'] ?? 0);
                $plannedPct   = (float)($i['planned_progress_pct'] ?? 0);
                $startDate    = !empty($i['start_date']) ? $i['start_date'] : null;
                $endDate      = !empty($i['end_date']) ? $i['end_date'] : null;
                $w1           = (float)($i['week_1_qty'] ?? 0);
                $w2           = (float)($i['week_2_qty'] ?? 0);
                $w3           = (float)($i['week_3_qty'] ?? 0);
                $w4           = (float)($i['week_4_qty'] ?? 0);
                $respTeam     = $i['responsible_team'] ?? null;
                $prio         = $i['priority'] ?? 'Medium';
                $stat         = $i['status'] ?? 'Draft';

                // Fetch existing actual values (read-only, do not overwrite from form)
                $existStmt = $this->db->prepare("SELECT actual_qty, actual_progress_pct FROM phase_monthly_target_items WHERE id=?");
                $existStmt->bind_param('i', $id);
                $existStmt->execute();
                $existRow = $existStmt->get_result()->fetch_assoc();
                $actQty    = (float)($existRow['actual_qty'] ?? 0);
                $actualPct = (float)($existRow['actual_progress_pct'] ?? 0);

                $stmt->bind_param('issddddssddddsssi',
                    $wid,
                    $name,
                    $unit,
                    $qty,
                    $actQty,
                    $plannedPct,
                    $actualPct,
                    $startDate,
                    $endDate,
                    $w1,
                    $w2,
                    $w3,
                    $w4,
                    $respTeam,
                    $prio,
                    $stat,
                    $id
                );
                $stmt->execute();
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    public function deleteItem(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM phase_monthly_target_items WHERE id=?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    // ── Progress summary for a target ────────────────────────────────────────
    public function getProgressSummary(int $target_id): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                              AS total_items,
                COALESCE(SUM(monthly_target_qty),0)   AS total_planned_qty,
                COALESCE(SUM(actual_qty),0)           AS total_actual_qty,
                COALESCE(AVG(planned_progress_pct),0) AS avg_planned_pct,
                COALESCE(AVG(actual_progress_pct),0)  AS avg_actual_pct,
                SUM(CASE WHEN status='Draft'     THEN 1 ELSE 0 END) AS cnt_draft,
                SUM(CASE WHEN status='Ready'     THEN 1 ELSE 0 END) AS cnt_ready,
                SUM(CASE WHEN status='Approved'  THEN 1 ELSE 0 END) AS cnt_approved,
                SUM(CASE WHEN status='At Risk'   THEN 1 ELSE 0 END) AS cnt_at_risk,
                SUM(CASE WHEN status='Critical'  THEN 1 ELSE 0 END) AS cnt_critical
            FROM phase_monthly_target_items
            WHERE target_id = ?
        ");
        $stmt->bind_param('i', $target_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?? [];
    }

    // ── Get work_types for a given design_type ───────────────────────────────
    public function getWorkTypes(?int $design_type_id = null): array {
        if ($design_type_id) {
            $stmt = $this->db->prepare("
                SELECT wt.id, wt.work_type_name, wt.default_phase_name, dt.name AS design_type_name
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

    private function nextSort(int $target_id): int {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(sort_order),0)+1 AS n FROM phase_monthly_target_items WHERE target_id=?");
        $stmt->bind_param('i', $target_id);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['n'];
    }
}
