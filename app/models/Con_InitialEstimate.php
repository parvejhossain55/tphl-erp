<?php

class Con_InitialEstimate extends Model
{
    protected $db;
    protected $budgetTable = 'initial_estimate_budgets';
    protected $itemsTable  = 'initial_estimate_items';
    protected $laborsTable = 'initial_estimate_labors';

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db ?? $GLOBALS['db'];
    }

    // =========================================================================
    // READ
    // =========================================================================

    /** List all budgets with project + user info */
    public function getAll()
    {
        $sql = "SELECT b.*,
                       p.name        AS project_name,
                       ae.id         AS arch_id,
                       u.full_name   AS created_by_name,
                       ab.full_name  AS approved_by_name,
                       ab_r.name     AS approved_by_role,
                       dt.name       AS design_type_name,
                       wt.work_type_name,
                       wt.design_type_id AS design_type_id,
                       CONCAT(dt.name, ' (', wt.work_type_name, ')') AS work_type_label
                FROM {$this->budgetTable} b
                LEFT JOIN projects            p    ON p.id    = b.project_id
                LEFT JOIN architect_engineering ae  ON ae.id  = b.architecture_id
                LEFT JOIN users               u    ON u.id   = b.created_by
                LEFT JOIN users               ab   ON ab.id  = b.approved_by
                LEFT JOIN roles               ab_r ON ab_r.id = ab.role_id
                LEFT JOIN work_types          wt   ON wt.id  = b.work_type_id
                LEFT JOIN design_types        dt   ON dt.id  = wt.design_type_id
                ORDER BY b.id DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Fetch all work_types with design_type name for dropdowns */
    public function getWorkTypes()
    {
        $sql = "SELECT wt.id, wt.work_type_name, wt.design_type_id,
                       dt.name AS design_type_name,
                       CONCAT(dt.name, ' (', wt.work_type_name, ')') AS label
                FROM work_types wt
                LEFT JOIN design_types dt ON dt.id = wt.design_type_id
                ORDER BY dt.name, wt.work_type_name";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /** Single budget header with relations */
    public function find($id)
    {
        $sql = "SELECT b.*,
                       p.name        AS project_name,
                       u.full_name   AS created_by_name,
                       ab.full_name  AS approved_by_name,
                       ab_r.name     AS approved_by_role,
                       dt.name       AS design_type_name,
                       wt.work_type_name,
                       CONCAT(dt.name, ' (', wt.work_type_name, ')') AS work_type_label
                FROM {$this->budgetTable} b
                LEFT JOIN projects p    ON p.id    = b.project_id
                LEFT JOIN users    u    ON u.id    = b.created_by
                LEFT JOIN users    ab   ON ab.id   = b.approved_by
                LEFT JOIN roles    ab_r ON ab_r.id = ab.role_id
                LEFT JOIN work_types   wt ON wt.id = b.work_type_id
                LEFT JOIN design_types dt ON dt.id = wt.design_type_id
                WHERE b.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** All material items for a budget (with work type + design type + material name) */
    public function getItemsByBudget($budgetId)
    {
        $sql = "SELECT i.id,
                       i.budget_id,
                       i.design_type_id,
                       i.work_type_id,
                       i.material_id,
                       i.estimated_qty,
                       i.unit_cost_approx,
                       i.remarks,
                       COALESCE(i.material_unit, m.unit, '')   AS material_unit,
                       COALESCE(m.name, CONCAT('Material #', i.material_id)) AS material_name,
                       COALESCE(dt.name, CONCAT('Type #', i.design_type_id)) AS design_type_name,
                       wt.work_type_name,
                       CONCAT(dt.name, ' (', wt.work_type_name, ')') AS work_type_label
                FROM {$this->itemsTable} i
                LEFT JOIN work_types    wt ON wt.id = i.work_type_id
                LEFT JOIN design_types  dt ON dt.id = COALESCE(wt.design_type_id, i.design_type_id)
                LEFT JOIN con_materials m  ON m.id  = i.material_id
                WHERE i.budget_id = ?
                ORDER BY i.work_type_id ASC, i.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** All labor items for a budget (with work type + design type) */
    public function getLaborsByBudget($budgetId)
    {
        $sql = "SELECT l.*,
                       dt.name AS design_type_name,
                       wt.work_type_name,
                       CONCAT(dt.name, ' (', wt.work_type_name, ')') AS work_type_label
                FROM {$this->laborsTable} l
                LEFT JOIN work_types    wt ON wt.id = l.work_type_id
                LEFT JOIN design_types  dt ON dt.id = COALESCE(wt.design_type_id, l.design_type_id)
                WHERE l.budget_id = ?
                ORDER BY wt.work_type_name, l.labor_type_name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Design-wise GROUPED material cost breakdown
     * Returns: [ design_type_id, design_type_name, total_material_cost, item_count ]
     */
    public function getItemsGroupedByDesignType($budgetId)
    {
        $sql = "SELECT i.design_type_id,
                       dt.name                         AS design_type_name,
                       SUM(i.estimated_qty * i.unit_cost_approx) AS total_material_cost,
                       COUNT(i.id)                     AS item_count
                FROM {$this->itemsTable} i
                LEFT JOIN design_types dt ON dt.id = i.design_type_id
                WHERE i.budget_id = ?
                GROUP BY i.design_type_id, dt.name
                ORDER BY dt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Design-wise GROUPED labor cost breakdown
     * Returns: [ design_type_id, design_type_name, total_labor_cost, labor_count ]
     */
    public function getLaborsGroupedByDesignType($budgetId)
    {
        $sql = "SELECT l.design_type_id,
                       dt.name                              AS design_type_name,
                       SUM(l.rate_per_sqft * l.area_sqft)  AS total_labor_cost,
                       COUNT(l.id)                          AS labor_count
                FROM {$this->laborsTable} l
                LEFT JOIN design_types dt ON dt.id = l.design_type_id
                WHERE l.budget_id = ?
                GROUP BY l.design_type_id, dt.name
                ORDER BY dt.name";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $budgetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Full design-wise breakdown (items + labors merged by design type)
     * Useful for show-initial-budget.php tabs
     */
    public function getFullBreakdownByDesignType($budgetId)
    {
        $items  = $this->getItemsGroupedByDesignType($budgetId);
        $labors = $this->getLaborsGroupedByDesignType($budgetId);

        // Index labors by design_type_id for easy merge
        $laborMap = [];
        foreach ($labors as $l) {
            $laborMap[$l['design_type_id']] = $l;
        }

        $breakdown = [];
        foreach ($items as $i) {
            $dtId = $i['design_type_id'];
            $breakdown[$dtId] = [
                'design_type_id'    => $dtId,
                'design_type_name'  => $i['design_type_name'],
                'total_material'    => $i['total_material_cost'],
                'item_count'        => $i['item_count'],
                'total_labor'       => $laborMap[$dtId]['total_labor_cost']  ?? 0,
                'labor_count'       => $laborMap[$dtId]['labor_count']        ?? 0,
            ];
            $breakdown[$dtId]['design_total'] =
                $breakdown[$dtId]['total_material'] + $breakdown[$dtId]['total_labor'];
            unset($laborMap[$dtId]);
        }

        // Labors that have no matching items (labor-only design types)
        foreach ($laborMap as $dtId => $l) {
            $breakdown[$dtId] = [
                'design_type_id'   => $dtId,
                'design_type_name' => $l['design_type_name'],
                'total_material'   => 0,
                'item_count'       => 0,
                'total_labor'      => $l['total_labor_cost'],
                'labor_count'      => $l['labor_count'],
                'design_total'     => $l['total_labor_cost'],
            ];
        }

        return array_values($breakdown);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    /** Insert header row → returns new budget ID */
    public function createBudget($data)
    {
        $sql = "INSERT INTO {$this->budgetTable}
                    (project_id, architecture_id, work_type_id, start_date, end_date, total_area_sqft, other_costs, other_cost_detail, budget_link, work_details, weight_percentage, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiissddsssdi',
            $data['project_id'],
            $data['architecture_id'],
            $data['work_type_id'],
            $data['start_date'],
            $data['end_date'],
            $data['total_area_sqft'],
            $data['other_costs'],
            $data['other_cost_detail'],
            $data['budget_link'],
            $data['work_details'],
            $data['weight_percentage'],
            $data['created_by']
        );
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    /** Batch-insert material items */
    public function addItems($budgetId, array $items)
    {
        if (empty($items)) return true;

        $sql  = "INSERT INTO {$this->itemsTable}
                     (budget_id, work_type_id, design_type_id, material_id, material_unit,
                      estimated_qty, unit_cost_approx, remarks)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('addItems prepare failed: ' . $this->db->error);
            return false;
        }

        foreach ($items as $item) {
            $stmt->bind_param(
                'iiiisdds',
                $budgetId,
                $item['work_type_id'],
                $item['design_type_id'],
                $item['material_id'],
                $item['material_unit'],
                $item['estimated_qty'],
                $item['unit_cost_approx'],
                $item['remarks']
            );
            $stmt->execute();
        }
        return true;
    }

    /** Batch-insert labor rows */
    public function addLabors($budgetId, array $labors)
    {
        if (empty($labors)) return true;

        $sql  = "INSERT INTO {$this->laborsTable}
                     (budget_id, work_type_id, design_type_id, labor_type_name, rate_per_sqft, area_sqft, description)
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('addLabors prepare failed: ' . $this->db->error);
            // Fallback: insert without work_type_id if column doesn't exist yet
            $sql2  = "INSERT INTO {$this->laborsTable}
                         (budget_id, design_type_id, labor_type_name, rate_per_sqft, area_sqft, description)
                     VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql2);
            if (!$stmt) {
                error_log('addLabors fallback prepare failed: ' . $this->db->error);
                return false;
            }
            foreach ($labors as $l) {
                $dtId = $l['design_type_id'] ?? ($l['work_type_id'] ?? 1);
                $stmt->bind_param(
                    'iisdds',
                    $budgetId,
                    $dtId,
                    $l['labor_type_name'],
                    $l['rate_per_sqft'],
                    $l['area_sqft'],
                    $l['description']
                );
                $stmt->execute();
            }
            return true;
        }

        foreach ($labors as $l) {
            $stmt->bind_param(
                'iiisdds',
                $budgetId,
                $l['work_type_id'],
                $l['design_type_id'],
                $l['labor_type_name'],
                $l['rate_per_sqft'],
                $l['area_sqft'],
                $l['description']
            );
            $stmt->execute();
        }
        return true;
    }

    // =========================================================================
    // SYNC TOTALS  (transaction-safe)
    // =========================================================================

    /**
     * Recalculates material_cost_total, labor_cost_total, total_estimated_cost
     * in the header table.  Must be called after any insert/update/delete
     * on items or labors.
     */
    public function syncTotals($budgetId)
    {
        $this->db->begin_transaction();
        try {
            // 1. Sum material items
            $r1  = $this->db->query(
                "SELECT COALESCE(SUM(estimated_qty * unit_cost_approx), 0) AS mat_total
                 FROM {$this->itemsTable}
                 WHERE budget_id = $budgetId"
            );
            $matTotal = (float) $r1->fetch_assoc()['mat_total'];

            // 2. Sum labor rows
            $r2  = $this->db->query(
                "SELECT COALESCE(SUM(rate_per_sqft * area_sqft), 0) AS lab_total
                 FROM {$this->laborsTable}
                 WHERE budget_id = $budgetId"
            );
            $labTotal = (float) $r2->fetch_assoc()['lab_total'];

            // 3. Fetch other_costs from header
            $r3  = $this->db->query(
                "SELECT COALESCE(other_costs, 0) AS other FROM {$this->budgetTable}
                 WHERE id = $budgetId"
            );
            $otherCosts = (float) $r3->fetch_assoc()['other'];

            // 4. Grand total
            $grandTotal = $matTotal + $labTotal + $otherCosts;

            // 5. Update header
            $stmt = $this->db->prepare(
                "UPDATE {$this->budgetTable}
                 SET material_cost_total  = ?,
                     labor_cost_total     = ?,
                     total_estimated_cost = ?
                 WHERE id = ?"
            );
            $stmt->bind_param('dddi', $matTotal, $labTotal, $grandTotal, $budgetId);
            $stmt->execute();

            $this->db->commit();
            return true;

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log('syncTotals error: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // APPROVAL WORKFLOW
    // =========================================================================

    /** Returns true if the record is locked (approved or cancelled) */
    public function isLocked($id)
    {
        $stmt = $this->db->prepare(
            "SELECT status FROM {$this->budgetTable} WHERE id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row && in_array($row['status'], ['approved', 'cancelled']);
    }

    /** Approve: set status, record who approved and when */
    public function approveBudget($id, $userId)
    {
        if ($this->isLocked($id)) return false;

        $stmt = $this->db->prepare(
            "UPDATE {$this->budgetTable}
             SET status = 'approved', approved_by = ?, approval_date = NOW()
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    /** Cancel: set status + mandatory rejection note */
    public function cancelBudget($id, $note)
    {
        if ($this->isLocked($id)) return false;

        $stmt = $this->db->prepare(
            "UPDATE {$this->budgetTable}
             SET status = 'cancelled', rejection_note = ?
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->bind_param('si', $note, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    // =========================================================================
    // EDIT (header + replace items/labors)
    // =========================================================================

    /** Update the budget header fields (only if pending) */
    public function updateBudget($id, $data)
    {
        if ($this->isLocked($id)) return false;

        $stmt = $this->db->prepare(
            "UPDATE {$this->budgetTable}
             SET project_id = ?, architecture_id = ?, work_type_id = ?, start_date = ?, end_date = ?, total_area_sqft = ?, other_costs = ?, other_cost_detail = ?, budget_link = ?, work_details = ?, weight_percentage = ?
             WHERE id = ? AND status = 'pending'"
        );
        $stmt->bind_param(
            'iiissddsssdi',
            $data['project_id'],
            $data['architecture_id'],
            $data['work_type_id'],
            $data['start_date'],
            $data['end_date'],
            $data['total_area_sqft'],
            $data['other_costs'],
            $data['other_cost_detail'],
            $data['budget_link'],
            $data['work_details'],
            $data['weight_percentage'],
            $id
        );
        return $stmt->execute();
    }

    /** Delete all material items for a budget (used before re-inserting on edit) */
    public function deleteItemsByBudget($budgetId)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->itemsTable} WHERE budget_id = ?");
        $stmt->bind_param('i', $budgetId);
        return $stmt->execute();
    }

    /** Delete all labor rows for a budget (used before re-inserting on edit) */
    public function deleteLaborsByBudget($budgetId)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->laborsTable} WHERE budget_id = ?");
        $stmt->bind_param('i', $budgetId);
        return $stmt->execute();
    }
}
