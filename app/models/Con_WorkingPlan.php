<?php
/**
 * Con_WorkingPlan Model
 * Tables: project_working_plans, budget_extend_requests
 *
 * Schema (from Readme.md):
 * project_working_plans: id, project_id, initial_budget_id, work_type_id, year, month,
 *                        work_description, planned_quantity, estimated_cost, created_by,
 *                        approved_status (Pending|Approved|Rejected), approved_by
 * budget_extend_requests: id, project_id, initial_budget_id, requested_by, title, amount,
 *                         reason, status, approved_by, approved_at, remarks
 */
class Con_WorkingPlan extends Model
{
    protected $db;
    protected $planTable   = 'project_working_plans';
    protected $extendTable = 'budget_extend_requests';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // WORKING PLANS — READ
    // =========================================================================

    /** All working plans with project + budget + work type + user info */
    public function getAll()
    {
        $sql = "SELECT w.*,
                       p.name        AS project_name,
                       b.total_estimated_cost AS budget_total,
                       wt.work_type_name       AS work_type_name,
                       dt.name       AS design_type_name,
                       u.full_name   AS created_by_name,
                       ap.full_name  AS approved_by_name,
                       ap_r.name     AS approved_by_role
                FROM {$this->planTable} w
                LEFT JOIN projects                 p    ON p.id    = w.project_id
                LEFT JOIN initial_estimate_budgets b    ON b.id    = w.initial_budget_id
                LEFT JOIN work_types               wt   ON wt.id   = w.work_type_id
                LEFT JOIN design_types             dt   ON dt.id   = wt.design_type_id
                LEFT JOIN users                    u    ON u.id    = w.created_by
                LEFT JOIN users                    ap   ON ap.id   = w.approved_by
                LEFT JOIN roles                    ap_r ON ap_r.id = ap.role_id
                ORDER BY w.year DESC, w.month DESC, p.name ASC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Single working plan */
    public function find($id)
    {
        $stmt = $this->db->prepare(
            "SELECT w.*,
                    p.name AS project_name,
                    b.total_estimated_cost AS budget_total,
                    wt.work_type_name AS work_type_name,
                    dt.name       AS design_type_name,
                    u.full_name  AS created_by_name,
                    ap.full_name AS approved_by_name,
                    ap_r.name    AS approved_by_role
             FROM {$this->planTable} w
             LEFT JOIN projects                 p    ON p.id    = w.project_id
             LEFT JOIN initial_estimate_budgets b    ON b.id    = w.initial_budget_id
             LEFT JOIN work_types               wt   ON wt.id   = w.work_type_id
             LEFT JOIN design_types             dt   ON dt.id   = wt.design_type_id
             LEFT JOIN users                    u    ON u.id    = w.created_by
             LEFT JOIN users                    ap   ON ap.id   = w.approved_by
             LEFT JOIN roles                    ap_r ON ap_r.id = ap.role_id
             WHERE w.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** All working plans for a specific initial_estimate_budget */
    public function getByBudget($budgetId)
    {
        $stmt = $this->db->prepare(
            "SELECT w.*, p.name AS project_name, wt.work_type_name AS work_type_name, dt.name AS design_type_name,
                    u.full_name AS created_by_name, ap.full_name AS approved_by_name, ap_r.name AS approved_by_role
             FROM {$this->planTable} w
             LEFT JOIN projects    p  ON p.id  = w.project_id
             LEFT JOIN work_types  wt ON wt.id = w.work_type_id
             LEFT JOIN design_types dt ON dt.id = wt.design_type_id
             LEFT JOIN users        u  ON u.id  = w.created_by
             LEFT JOIN users       ap  ON ap.id = w.approved_by
             LEFT JOIN roles     ap_r  ON ap_r.id = ap.role_id
             WHERE w.initial_budget_id = ?
             ORDER BY w.year ASC, w.month ASC"
        );
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Returns array of already-planned {year, month} pairs for a budget.
     * Used by the create form to determine the next available month.
     */
    public function getPlannedMonthsByBudget($budgetId)
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT year, month FROM {$this->planTable}
             WHERE initial_budget_id = ?
             ORDER BY year ASC, month ASC"
        );
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Year+Month wise cost summary for a budget — used on show-initial-budget page.
     * Returns [{year, month, total_cost, plan_count}]
     */
    public function getMonthlyPlanSummary($budgetId)
    {
        $stmt = $this->db->prepare(
            "SELECT year, month,
                    SUM(estimated_cost)   AS total_cost,
                    COUNT(*)              AS plan_count,
                    SUM(planned_quantity) AS total_qty
             FROM {$this->planTable}
             WHERE initial_budget_id = ?
             GROUP BY year, month
             ORDER BY year ASC, month ASC"
        );
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** All working plans for a specific project (with budget total + design type + approval info) */
    public function getByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT w.*,
                    p.name  AS project_name,
                    b.total_estimated_cost AS budget_total,
                    wt.work_type_name AS work_type_name,
                    dt.name       AS design_type_name,
                    u.full_name  AS created_by_name,
                    ap.full_name AS approved_by_name,
                    ap_r.name    AS approved_by_role
             FROM {$this->planTable} w
             LEFT JOIN projects                 p    ON p.id    = w.project_id
             LEFT JOIN initial_estimate_budgets b    ON b.id    = w.initial_budget_id
             LEFT JOIN work_types               wt   ON wt.id   = w.work_type_id
             LEFT JOIN design_types             dt   ON dt.id   = wt.design_type_id
             LEFT JOIN users                    u    ON u.id    = w.created_by
             LEFT JOIN users                    ap   ON ap.id   = w.approved_by
             LEFT JOIN roles                    ap_r ON ap_r.id = ap.role_id
             WHERE w.project_id = ?
             ORDER BY w.year ASC, w.month ASC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // BUDGET SUMMARY CALCULATIONS
    // =========================================================================

    /**
     * Revised Total = Initial Budget + All Approved Extensions
     */
    public function getRevisedTotal($budgetId)
    {
        // Initial budget total
        $r1 = $this->db->prepare(
            "SELECT total_estimated_cost FROM initial_estimate_budgets WHERE id = ?"
        );
        $r1->bind_param('i', $budgetId);
        $r1->execute();
        $row = $r1->get_result()->fetch_assoc();
        $initial = (float)($row['total_estimated_cost'] ?? 0);

        // Sum of approved extensions
        $r2 = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS ext_total
             FROM {$this->extendTable}
             WHERE initial_budget_id = ? AND status = 'Approved'"
        );
        $r2->bind_param('i', $budgetId);
        $r2->execute();
        $ext = (float)$r2->get_result()->fetch_assoc()['ext_total'];

        return $initial + $ext;
    }

    /**
     * Budget summary: total_planned, remaining_to_plan, monthly breakdown
     */
    public function getBudgetSummary($budgetId)
    {
        $revised = $this->getRevisedTotal($budgetId);

        $r = $this->db->prepare(
            "SELECT COALESCE(SUM(estimated_cost), 0) AS total_planned
             FROM {$this->planTable}
             WHERE initial_budget_id = ?"
        );
        $r->bind_param('i', $budgetId);
        $r->execute();
        $totalPlanned = (float)$r->get_result()->fetch_assoc()['total_planned'];

        // Monthly breakdown
        $r2 = $this->db->prepare(
            "SELECT year, month, estimated_cost
             FROM {$this->planTable}
             WHERE initial_budget_id = ?
             ORDER BY year ASC, month ASC"
        );
        $r2->bind_param('i', $budgetId);
        $r2->execute();
        $monthly = $r2->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'initial_budget'    => $this->getInitialBudgetTotal($budgetId),
            'revised_total'     => $revised,
            'total_planned'     => $totalPlanned,
            'remaining_to_plan' => $revised - $totalPlanned,
            'monthly'           => $monthly,
        ];
    }

    private function getInitialBudgetTotal($budgetId)
    {
        $stmt = $this->db->prepare(
            "SELECT total_estimated_cost FROM initial_estimate_budgets WHERE id = ?"
        );
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return (float)($row['total_estimated_cost'] ?? 0);
    }

    // =========================================================================
    // WORKING PLANS — WRITE
    // =========================================================================

    /**
     * Create working plan entry with budget validation.
     */
    public function createPlan($data)
    {
        $budgetId = (int)$data['initial_budget_id'];

        // Validate: cost won't exceed revised budget
        $summary = $this->getBudgetSummary($budgetId);
        if ($data['estimated_cost'] > $summary['remaining_to_plan']) {
            return [
                'ok'    => false,
                'error' => sprintf(
                    'Planned cost (৳%s) exceeds remaining budget (৳%s).',
                    number_format($data['estimated_cost'], 2),
                    number_format($summary['remaining_to_plan'], 2)
                ),
            ];
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO {$this->planTable}
                     (project_id, initial_budget_id, work_type_id, year, month,
                      work_description, planned_quantity, estimated_cost, weight_percentage, created_by,
                      approved_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')"
            );
            $stmt->bind_param(
                'iiiiisdddi',
                $data['project_id'],
                $data['initial_budget_id'],
                $data['work_type_id'],
                $data['year'],
                $data['month'],
                $data['work_description'],
                $data['planned_quantity'],
                $data['estimated_cost'],
                $data['weight_percentage'],
                $data['created_by']
            );
            $stmt->execute();
            $this->db->commit();
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /** Update working plan */
    public function updatePlan($id, $data)
    {
        $budgetId = (int)$data['initial_budget_id'];

        // Re-validate remaining (exclude current row's cost)
        $current = $this->find($id);
        $summary = $this->getBudgetSummary($budgetId);
        $availableForThisRow = $summary['remaining_to_plan'] + (float)$current['estimated_cost'];

        if ($data['estimated_cost'] > $availableForThisRow) {
            return [
                'ok'    => false,
                'error' => sprintf(
                    'Planned cost exceeds available budget (৳%s available).',
                    number_format($availableForThisRow, 2)
                ),
            ];
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->planTable}
             SET project_id = ?, initial_budget_id = ?, work_type_id = ?,
                 year = ?, month = ?, work_description = ?,
                 planned_quantity = ?, estimated_cost = ?, weight_percentage = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            'iiiiisdddi',
            $data['project_id'],
            $data['initial_budget_id'],
            $data['work_type_id'],
            $data['year'],
            $data['month'],
            $data['work_description'],
            $data['planned_quantity'],
            $data['estimated_cost'],
            $data['weight_percentage'],
            $id
        );
        return $stmt->execute()
            ? ['ok' => true, 'error' => null]
            : ['ok' => false, 'error' => 'Update failed.'];
    }

    /** Delete a plan entry */
    public function deletePlan($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->planTable} WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    /** Check if parent initial_estimate_budget is locked */
    private function isBudgetLocked($planId)
    {
        $stmt = $this->db->prepare(
            "SELECT b.status
             FROM {$this->planTable} w
             JOIN initial_estimate_budgets b ON b.id = w.initial_budget_id
             WHERE w.id = ?"
        );
        $stmt->bind_param('i', $planId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && in_array($row['status'], ['approved', 'cancelled']);
    }

    // =========================================================================
    // WORKING PLAN APPROVAL
    // =========================================================================

    /**
     * Approve a working plan — sets approved_status = 'Approved', approved_by = $userId
     */
    public function approvePlan($id, $userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->planTable}
             SET approved_status = 'Approved', approved_by = ?
             WHERE id = ? AND (approved_status = 'Pending' OR approved_status IS NULL)"
        );
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /**
     * Reject a working plan — sets approved_status = 'Rejected', approved_by = $userId
     */
    public function rejectPlan($id, $userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->planTable}
             SET approved_status = 'Rejected', approved_by = ?
             WHERE id = ? AND (approved_status = 'Pending' OR approved_status IS NULL)"
        );
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /**
     * Get approved plans count for a project
     */
    public function getApprovedCount($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM {$this->planTable}
             WHERE project_id = ? AND approved_status = 'Approved'"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return (int)$stmt->get_result()->fetch_assoc()['c'];
    }

    // =========================================================================
    // BUDGET EXTENSION REQUESTS — READ
    // =========================================================================

    public function getBudgetExtends()
    {
        $sql = "SELECT e.*,
                       p.name      AS project_name,
                       b.total_estimated_cost AS initial_budget,
                       u.full_name AS requested_by_name,
                       a.full_name AS approved_by_name,
                       a_r.name    AS approved_by_role
                FROM {$this->extendTable} e
                LEFT JOIN projects                 p   ON p.id   = e.project_id
                LEFT JOIN initial_estimate_budgets b   ON b.id   = e.initial_budget_id
                LEFT JOIN users                    u   ON u.id   = e.requested_by
                LEFT JOIN users                    a   ON a.id   = e.approved_by
                LEFT JOIN roles                    a_r ON a_r.id = a.role_id
                ORDER BY e.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function findExtend($id)
    {
        $stmt = $this->db->prepare(
            "SELECT e.*,
                    p.name      AS project_name,
                    b.total_estimated_cost AS initial_budget,
                    u.full_name AS requested_by_name,
                    a.full_name AS approved_by_name,
                    a_r.name    AS approved_by_role
             FROM {$this->extendTable} e
             LEFT JOIN projects                 p   ON p.id   = e.project_id
             LEFT JOIN initial_estimate_budgets b   ON b.id   = e.initial_budget_id
             LEFT JOIN users                    u   ON u.id   = e.requested_by
             LEFT JOIN users                    a   ON a.id   = e.approved_by
             LEFT JOIN roles                    a_r ON a_r.id = a.role_id
             WHERE e.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // BUDGET EXTENSION REQUESTS — WRITE
    // =========================================================================

    public function createBudgetRequest($data)
    {
        $attachmentFile = $data['attachment_file'] ?? null;
        $docsLink       = $data['docs_link']       ?? null;

        $stmt = $this->db->prepare(
            "INSERT INTO {$this->extendTable}
                 (project_id, initial_budget_id, requested_by, title, amount, reason,
                  attachment_file, docs_link)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'iiisdsss',
            $data['project_id'],
            $data['initial_budget_id'],
            $data['requested_by'],
            $data['title'],
            $data['amount'],
            $data['reason'],
            $attachmentFile,
            $docsLink
        );
        return $stmt->execute();
    }

    public function approveBudgetExtend($id, $userId, $remarks = '')
    {
        if (!$this->isExtendPending($id)) {
            return ['ok' => false, 'error' => 'Request is not Pending.'];
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->extendTable}
                 SET status = 'Approved', approved_by = ?, approved_at = NOW(), remarks = ?
                 WHERE id = ? AND status = 'Pending'"
            );
            $stmt->bind_param('isi', $userId, $remarks, $id);
            $stmt->execute();
            $this->db->commit();
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function rejectBudgetExtend($id, $userId, $remarks = '')
    {
        if (!$this->isExtendPending($id)) {
            return ['ok' => false, 'error' => 'Request is not Pending.'];
        }

        $this->db->begin_transaction();
        try {
            $stmt = $this->db->prepare(
                "UPDATE {$this->extendTable}
                 SET status = 'Rejected', approved_by = ?, approved_at = NOW(), remarks = ?
                 WHERE id = ? AND status = 'Pending'"
            );
            $stmt->bind_param('isi', $userId, $remarks, $id);
            $stmt->execute();
            $this->db->commit();
            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function isExtendPending($id)
    {
        $stmt = $this->db->prepare(
            "SELECT status FROM {$this->extendTable} WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && $row['status'] === 'Pending';
    }

    // =========================================================================
    // REPORT: Initial vs Revised vs Planned
    // =========================================================================

    public function getBudgetStatusReport()
    {
        $sql = "SELECT
                    b.id,
                    p.name                   AS project_name,
                    b.total_estimated_cost   AS initial_budget,
                    b.status                 AS budget_status,
                    COALESCE(ext.ext_total, 0) AS approved_extensions,
                    (b.total_estimated_cost + COALESCE(ext.ext_total, 0)) AS revised_total,
                    COALESCE(wp.planned_total, 0) AS total_planned,
                    (b.total_estimated_cost + COALESCE(ext.ext_total, 0)
                     - COALESCE(wp.planned_total, 0)) AS remaining
                FROM initial_estimate_budgets b
                LEFT JOIN projects p ON p.id = b.project_id
                LEFT JOIN (
                    SELECT initial_budget_id, SUM(amount) AS ext_total
                    FROM {$this->extendTable}
                    WHERE status = 'Approved'
                    GROUP BY initial_budget_id
                ) ext ON ext.initial_budget_id = b.id
                LEFT JOIN (
                    SELECT initial_budget_id, SUM(estimated_cost) AS planned_total
                    FROM {$this->planTable}
                    GROUP BY initial_budget_id
                ) wp ON wp.initial_budget_id = b.id
                ORDER BY b.id DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
