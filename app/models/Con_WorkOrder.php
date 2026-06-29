<?php

class Con_WorkOrder extends Model
{
    protected $table      = 'con_work_orders';
    protected $itemsTable = 'con_work_order_items';

    // =========================================================================
    // READ — All with relations
    // =========================================================================
    public function getAllWithRelations($userId = null, $roleId = null)
    {
        $allowedRoles = [1, 3, 15, 22];
        $where = "1=1";

        if ($userId && !in_array($roleId, $allowedRoles)) {
            $where = "wo.created_by = " . (int)$userId;
        }

        $sql = "SELECT wo.*,
                       p.name          AS project_name,
                       c.name          AS contractor_name,
                       c.trade         AS contractor_trade,
                       wt.work_type_name AS work_type_name,
                       dt2.name        AS design_type_name,
                       u.full_name     AS created_by_name,
                       ab.full_name    AS approved_by_name,
                       qc.full_name    AS qc_approved_by_name,
                       cm.full_name    AS completed_by_name,
                       (SELECT r.name 
                        FROM con_work_order_approvals wa 
                        INNER JOIN roles r ON r.id = wa.role_id 
                        WHERE wa.work_order_id = wo.id AND wa.status = 'pending' 
                        ORDER BY FIELD(wa.role_id, 12, 15, 3) 
                        LIMIT 1) AS pending_role_name
                FROM {$this->table} wo
                LEFT JOIN projects p       ON p.id  = wo.project_id
                LEFT JOIN contractors c    ON c.id  = wo.contractor_id
                LEFT JOIN work_types wt    ON wt.id = wo.work_type_id
                LEFT JOIN project_drawings pd ON pd.id = wo.design_id
                LEFT JOIN design_types dt2 ON dt2.id = pd.design_type_id
                LEFT JOIN users u          ON u.id  = wo.created_by
                LEFT JOIN users ab         ON ab.id = wo.approved_by
                LEFT JOIN users qc         ON qc.id = wo.qc_approved_by
                LEFT JOIN users cm         ON cm.id = wo.completed_by
                WHERE {$where}
                ORDER BY wo.created_at DESC";

        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function countAllWithRelations($userId = null, $roleId = null)
    {
        $allowedRoles = [1, 3, 15, 22];
        $where = "1=1";

        if ($userId && !in_array($roleId, $allowedRoles)) {
            $where = "wo.created_by = " . (int)$userId;
        }

        $sql = "SELECT COUNT(*) AS total FROM {$this->table} wo WHERE {$where}";
        $result = $this->db->query($sql);
        $row = $result ? $result->fetch_assoc() : null;
        return $row ? (int)$row['total'] : 0;
    }

    public function getPaginatedWithRelations($limit, $offset, $userId = null, $roleId = null)
    {
        $allowedRoles = [1, 3, 15, 22];
        $where = "1=1";

        if ($userId && !in_array($roleId, $allowedRoles)) {
            $where = "wo.created_by = " . (int)$userId;
        }

        $sql = "SELECT wo.*,
                       p.name          AS project_name,
                       c.name          AS contractor_name,
                       c.trade         AS contractor_trade,
                       wt.work_type_name AS work_type_name,
                       dt2.name        AS design_type_name,
                       u.full_name     AS created_by_name,
                       ab.full_name    AS approved_by_name,
                       qc.full_name    AS qc_approved_by_name,
                       cm.full_name    AS completed_by_name,
                       (SELECT r.name 
                        FROM con_work_order_approvals wa 
                        INNER JOIN roles r ON r.id = wa.role_id 
                        WHERE wa.work_order_id = wo.id AND wa.status = 'pending' 
                        ORDER BY FIELD(wa.role_id, 12, 15, 3) 
                        LIMIT 1) AS pending_role_name
                FROM {$this->table} wo
                LEFT JOIN projects p       ON p.id  = wo.project_id
                LEFT JOIN contractors c    ON c.id  = wo.contractor_id
                LEFT JOIN work_types wt    ON wt.id = wo.work_type_id
                LEFT JOIN project_drawings pd ON pd.id = wo.design_id
                LEFT JOIN design_types dt2 ON dt2.id = pd.design_type_id
                LEFT JOIN users u          ON u.id  = wo.created_by
                LEFT JOIN users ab         ON ab.id = wo.approved_by
                LEFT JOIN users qc         ON qc.id = wo.qc_approved_by
                LEFT JOIN users cm         ON cm.id = wo.completed_by
                WHERE {$where}
                ORDER BY wo.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStatusCounts($userId = null, $roleId = null)
    {
        $allowedRoles = [1, 3, 15, 22];
        $where = "1=1";

        if ($userId && !in_array($roleId, $allowedRoles)) {
            $where = "wo.created_by = " . (int)$userId;
        }

        $sql = "SELECT status, COUNT(*) AS count FROM {$this->table} wo WHERE {$where} GROUP BY status";
        $result = $this->db->query($sql);
        $counts = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $counts[$row['status']] = (int)$row['count'];
            }
        }
        return $counts;
    }

    // =========================================================================
    // FIND single WO with full relations
    // =========================================================================
    public function findWithRelations($id)
    {
        $sql = "SELECT wo.*,
                       p.name          AS project_name,
                       c.name          AS contractor_name,
                       c.phone         AS contractor_phone,
                       c.trade         AS contractor_trade,
                       c.address       AS contractor_address,
                       wt.work_type_name AS work_type_name,
                       dt2.name        AS design_type_name,
                       u.full_name     AS created_by_name,
                       ab.full_name    AS approved_by_name,
                       qc.full_name    AS qc_approved_by_name,
                       cm.full_name    AS completed_by_name,
                       CONCAT(pp.phase_name, ' - ', DATE_FORMAT(tf.target_month, '%M %Y')) AS target_title,
                       tf.total_target_weight_pct AS target_percentage,
                       tf.target_year         AS target_year,
                       tf.target_month_number AS target_month,
                       NULL            AS target_week,
                       pp_draw.phase_name     AS drawing_phase_name,
                       (SELECT COALESCE(SUM(IF(approved_amount IS NOT NULL AND approved_amount > 0, approved_amount, bill_amount)), 0)
                        FROM contractor_bill_requests
                        WHERE work_order_id = wo.id AND status != 'rejected') AS billed_amount,
                       (SELECT COALESCE(SUM(amount_paid), 0)
                        FROM con_financial_transactions cft
                        INNER JOIN contractor_bill_requests cbr ON cft.ref_id = cbr.id
                        WHERE cft.ref_type = 'contractor_bill' AND cbr.work_order_id = wo.id) AS paid_amount
                FROM {$this->table} wo
                LEFT JOIN projects p                ON p.id  = wo.project_id
                LEFT JOIN contractors c             ON c.id  = wo.contractor_id
                LEFT JOIN work_types wt             ON wt.id = wo.work_type_id
                LEFT JOIN project_drawings pd       ON pd.id = wo.design_id
                LEFT JOIN design_types dt2          ON dt2.id = pd.design_type_id
                LEFT JOIN users u                   ON u.id  = wo.created_by
                LEFT JOIN users ab                  ON ab.id = wo.approved_by
                LEFT JOIN users qc                  ON qc.id = wo.qc_approved_by
                LEFT JOIN users cm                  ON cm.id = wo.completed_by
                LEFT JOIN phase_monthly_targets tf  ON tf.id = wo.target_id
                LEFT JOIN project_phases pp         ON pp.id = tf.phase_id
                LEFT JOIN project_phases pp_draw    ON pp_draw.id = pd.phase_id
                WHERE wo.id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // GET by project (for AJAX in contractor bill)
    // =========================================================================
    public function getByProject($projectId, $statusFilter = ['approved', 'active', 'work_completed', 'qc_approved', 'closed'])
    {
        $placeholders = implode(',', array_fill(0, count($statusFilter), '?'));
        $types = str_repeat('s', count($statusFilter));
        $params = array_merge([(int)$projectId], $statusFilter);

        $sql = "SELECT wo.id, wo.wo_number, wo.title, wo.status, wo.total_amount, 
                       wo.advance_percent, wo.advance_amount,
                       c.name AS contractor_name,
                       (SELECT COALESCE(SUM(IF(approved_amount IS NOT NULL AND approved_amount > 0, approved_amount, bill_amount)), 0)
                        FROM contractor_bill_requests
                        WHERE work_order_id = wo.id AND status != 'rejected') AS billed_amount,
                       (SELECT COALESCE(SUM(amount_paid), 0)
                        FROM con_financial_transactions cft
                        INNER JOIN contractor_bill_requests cbr ON cft.ref_id = cbr.id
                        WHERE cft.ref_type = 'contractor_bill' AND cbr.work_order_id = wo.id) AS paid_amount
                FROM {$this->table} wo
                LEFT JOIN contractors c ON c.id = wo.contractor_id
                WHERE wo.project_id = ?
                  AND (wo.status IN ({$placeholders}) OR wo.approved_by IS NOT NULL)
                ORDER BY wo.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i' . $types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // CREATE
    // =========================================================================
    public function create($data)
    {
        $woNumber = $this->generateWONumber();

        $sql = "INSERT INTO {$this->table}
                (project_id, contractor_id, design_id, work_type_id, boq_id, wo_number, title,
                 scope_of_work, notes, description, unit, unit_rate, quantity, total_amount,
                 start_date, end_date,
                 advance_percent, running_percent, retention_percent,
                 advance_amount, retention_amount,
                 status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiiiissssssdddssdddddi',
            $data['project_id'],      // i
            $data['contractor_id'],   // i
            $data['design_id'],       // i
            $data['work_type_id'],    // i
            $data['boq_id'],          // i
            $woNumber,                // s
            $data['title'],           // s
            $data['scope_of_work'],   // s
            $data['notes'],           // s
            $data['description'],     // s
            $data['unit'],            // s
            $data['unit_rate'],       // d
            $data['quantity'],        // d
            $data['total_amount'],    // d
            $data['start_date'],      // s
            $data['end_date'],        // s
            $data['advance_percent'], // d
            $data['running_percent'], // d
            $data['retention_percent'],// d
            $data['advance_amount'],  // d
            $data['retention_amount'],// d
            $data['created_by']       // i
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    // =========================================================================
    // UPDATE
    // =========================================================================
    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table}
                SET project_id=?, contractor_id=?, design_id=?, work_type_id=?, boq_id=?, title=?,
                    scope_of_work=?, notes=?, description=?, unit=?, unit_rate=?, quantity=?, total_amount=?,
                    start_date=?, end_date=?,
                    advance_percent=?, running_percent=?, retention_percent=?,
                    advance_amount=?, retention_amount=?,
                    status = 'draft', rejection_reason = NULL
                WHERE id=? AND status IN ('draft', 'rejected')";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiiiisssssdddssdddddi',
            $data['project_id'],
            $data['contractor_id'],
            $data['design_id'],
            $data['work_type_id'],
            $data['boq_id'],
            $data['title'],
            $data['scope_of_work'],
            $data['notes'],
            $data['description'],
            $data['unit'],
            $data['unit_rate'],
            $data['quantity'],
            $data['total_amount'],
            $data['start_date'],
            $data['end_date'],
            $data['advance_percent'],
            $data['running_percent'],
            $data['retention_percent'],
            $data['advance_amount'],
            $data['retention_amount'],
            $id
        );
        return $stmt->execute();
    }

    // =========================================================================
    // APPROVE (Director / PM Head)
    // =========================================================================
    public function approve($id, $userId)
    {
        // Approve if status is 'draft' OR empty/null (handles legacy data edge cases)
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status='approved', approved_by=?, approved_at=NOW()
             WHERE id=? AND (status='draft' OR status='' OR status IS NULL OR status NOT IN ('approved','active','work_completed','qc_approved','closed','cancelled'))"
        );
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    // =========================================================================
    // REJECT
    // =========================================================================
    public function reject($id, $userId, $reason = '')
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status='rejected', approved_by=?, approved_at=NOW(), rejection_reason=?
             WHERE id=? AND status='draft'"
        );
        $stmt->bind_param('isi', $userId, $reason, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    // =========================================================================
    // MARK COMPLETE (PM)
    // =========================================================================
    public function markComplete($id, $userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status='work_completed', completed_by=?, completed_at=NOW()
             WHERE id=? AND status IN ('approved','active')"
        );
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    // =========================================================================
    // QC APPROVE → auto close
    // =========================================================================
    public function qcApprove($id, $userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status='closed', qc_approved_by=?, qc_approved_at=NOW()
             WHERE id=? AND status='work_completed'"
        );
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute() && $stmt->affected_rows > 0;
    }

    // =========================================================================
    // DELETE (only draft)
    // =========================================================================
    public function delete($id)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE id=? AND status='draft'"
        );
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    // =========================================================================
    // LINKED BILLS
    // =========================================================================
    public function getLinkedBills($woId)
    {
        $sql = "SELECT cbr.*, c.name AS contractor_name, p.name AS project_name
                FROM contractor_bill_requests cbr
                LEFT JOIN contractors c ON c.id = cbr.operator_id
                LEFT JOIN projects p    ON p.id = cbr.project_id
                WHERE cbr.work_order_id = ?
                ORDER BY cbr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $woId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // LINKED WEEKLY REPORTS
    // =========================================================================
    public function getLinkedWeeklyReports($woId)
    {
        // Now uses phase_weekly_progress (new system) linked via target_id
        $sql = "SELECT pwp.*,
                       u.full_name  AS engineer_name,
                       pp.phase_name AS target_title,
                       pmt.total_target_weight_pct AS target_percentage
                FROM phase_weekly_progress pwp
                LEFT JOIN users               u   ON u.id   = pwp.submitted_by
                LEFT JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
                LEFT JOIN project_phases        pp  ON pp.id  = pmt.phase_id
                WHERE pwp.target_id = (SELECT target_id FROM con_work_orders WHERE id = ?)
                ORDER BY pwp.week_start_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $woId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // CASCADE HELPERS for dropdowns
    // =========================================================================

    /** Project Phases for a project (replaces getWorkPlansByProject) */
    public function getWorkPlansByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, phase_name AS work_description, phase_order AS sort_order
             FROM project_phases
             WHERE project_id = ?
             ORDER BY phase_order ASC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Monthly Targets for a Phase (replaces getTargetsByWorkPlan) */
    public function getTargetsByWorkPlan($phaseId)
    {
        $stmt = $this->db->prepare(
            "SELECT pmt.id,
                    CONCAT(pp.phase_name, ' - ', DATE_FORMAT(pmt.target_month, '%M %Y')) AS target_title,
                    pmt.target_year AS year,
                    pmt.target_month_number AS month,
                    NULL AS week_number,
                    pmt.total_target_weight_pct AS target_percentage
             FROM phase_monthly_targets pmt
             LEFT JOIN project_phases pp ON pp.id = pmt.phase_id
             WHERE pmt.phase_id = ?
             ORDER BY pmt.target_month_number ASC"
        );
        $stmt->bind_param('i', $phaseId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // AUTO-GENERATE WO NUMBER  e.g. WO-2025-0001
    // =========================================================================
    private function generateWONumber()
    {
        $year = date('Y');
        $result = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM {$this->table} WHERE YEAR(created_at) = {$year}"
        );
        $count = (int)($result->fetch_assoc()['cnt'] ?? 0);
        return 'WO-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    // =========================================================================
    // WORK ORDER ITEMS — database-driven rate schedule rows
    // =========================================================================

    public function getItemsByWorkOrder($woId)
    {
        $stmt = $this->db->prepare(
            "SELECT i.*, pi.item_name AS phase_item_name, wt.work_type_name AS work_type_name 
             FROM {$this->itemsTable} i
             LEFT JOIN project_phase_items pi ON i.phase_item_id = pi.id
             LEFT JOIN work_types wt ON i.work_type_id = wt.id
             WHERE i.work_order_id = ?
             ORDER BY i.sort_order ASC, i.id ASC"
        );
        $stmt->bind_param('i', $woId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Batch-insert rate schedule items for a WO */
    public function addItems($woId, array $items)
    {
        if (empty($items)) return true;

        $sql  = "INSERT INTO {$this->itemsTable}
                     (work_order_id, phase_item_id, work_type_id, sl_no, description, location, unit, quantity, rate, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('WO addItems prepare failed: ' . $this->db->error);
            return false;
        }
        foreach ($items as $i => $item) {
            $sortOrder = $item['sort_order'] ?? $i;
            $phaseItemId = !empty($item['phase_item_id']) ? (int)$item['phase_item_id'] : null;
            $workTypeId = !empty($item['work_type_id']) ? (int)$item['work_type_id'] : null;
            $stmt->bind_param(
                'iiissssddi',
                $woId,
                $phaseItemId,
                $workTypeId,
                $item['sl_no'],
                $item['description'],
                $item['location'],
                $item['unit'],
                $item['quantity'],
                $item['rate'],
                $sortOrder
            );
            $stmt->execute();
        }
        return true;
    }

    /** Delete all items then re-insert (used on edit/update) */
    public function replaceItems($woId, array $items)
    {
        $del = $this->db->prepare("DELETE FROM {$this->itemsTable} WHERE work_order_id = ?");
        $del->bind_param('i', $woId);
        $del->execute();

        return $this->addItems($woId, $items);
    }

    /** Recalculate total_amount from items (SUM of amount) + update header */
    public function syncTotal($woId)
    {
        $r = $this->db->query(
            "SELECT COALESCE(SUM(quantity * rate), 0) AS grand
             FROM {$this->itemsTable} WHERE work_order_id = {$woId}"
        );
        if (!$r) return false;
        $grand = (float)$r->fetch_assoc()['grand'];

        // Fetch current percent fields to recalculate amounts
        $h = $this->find($woId);
        if (!$h) return false;

        $advAmt = round($grand * (float)($h['advance_percent']   ?? 0) / 100, 2);
        $retAmt = round($grand * (float)($h['retention_percent'] ?? 0) / 100, 2);

        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET total_amount = ?, advance_amount = ?, retention_amount = ?
             WHERE id = ?"
        );
        $stmt->bind_param('dddi', $grand, $advAmt, $retAmt, $woId);
        return $stmt->execute();
    }

    // =========================================================================
    // LOOKUP: find existing WO by project + design for AJAX check
    // =========================================================================

    /**
     * Find the most recent WO for a given project + design_id combo.
     * Returns the WO row (with contractor name) or null.
     */
    public function findByProjectAndDesign($projectId, $designId)
    {
        $stmt = $this->db->prepare(
            "SELECT wo.*, c.name AS contractor_name
             FROM {$this->table} wo
             LEFT JOIN contractors c ON c.id = wo.contractor_id
             WHERE wo.project_id = ? AND wo.design_id = ?
             ORDER BY wo.id DESC
             LIMIT 1"
        );
        $stmt->bind_param('ii', $projectId, $designId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // =========================================================================
    // UPDATE HISTORY LOG
    // =========================================================================
    protected $updatesTable = 'con_work_order_updates';

    /**
     * Insert one update log entry when a WO is edited.
     */
    public function addUpdateLog($woId, $updateDate, $notes, $userId)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->updatesTable}
                 (work_order_id, update_date, update_notes, updated_by)
             VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('issi', $woId, $updateDate, $notes, $userId);
        return $stmt->execute();
    }

    /**
     * Fetch all update logs for a WO, newest first.
     */
    public function getUpdateLogs($woId)
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, a.full_name AS updated_by_name
             FROM {$this->updatesTable} u
             LEFT JOIN users a ON a.id = u.updated_by
             WHERE u.work_order_id = ?
             ORDER BY u.update_date DESC, u.id DESC"
        );
        $stmt->bind_param('i', $woId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // NEW SEQUENTIAL APPROVAL SYSTEM METHODS
    // =========================================================================

    /**
     * Initialize approvals for a Work Order (site operator/moperation=20, PM=22, management=3)
     */
    public function initApprovals($woId)
    {
        $stmtDel = $this->db->prepare("DELETE FROM con_work_order_approvals WHERE work_order_id = ?");
        $stmtDel->bind_param('i', $woId);
        $stmtDel->execute();

        $stmtIns = $this->db->prepare("INSERT INTO con_work_order_approvals (work_order_id, role_id, status) VALUES (?, ?, 'pending')");
        $roles = [12, 15, 3];
        foreach ($roles as $roleId) {
            $stmtIns->bind_param('ii', $woId, $roleId);
            $stmtIns->execute();
        }
    }

    /**
     * Fetch approvals list for a Work Order
     */
    public function getApprovals($woId)
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name AS user_name, r.name AS role_name
             FROM con_work_order_approvals a
             LEFT JOIN users u ON u.id = a.action_by
             LEFT JOIN roles r ON r.id = a.role_id
             WHERE a.work_order_id = ?
             ORDER BY FIELD(a.role_id, 12, 15, 3)"
        );
        $stmt->bind_param('i', $woId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Update approval status for a specific role
     */
    public function updateApproval($woId, $roleId, $userId, $status, $remarks)
    {
        $stmt = $this->db->prepare(
            "UPDATE con_work_order_approvals 
             SET status = ?, action_by = ?, action_at = NOW(), remarks = ?
             WHERE work_order_id = ? AND role_id = ?"
        );
        $stmt->bind_param('sisis', $status, $userId, $remarks, $woId, $roleId);
        $stmt->execute();

        if ($status === 'rejected') {
            if (in_array($roleId, [1, 3])) {
                // Set overall work order status to 'rejected' for Management
                $stmtWO = $this->db->prepare(
                    "UPDATE {$this->table}
                     SET status = 'rejected', rejection_reason = ?, approved_by = ?, approved_at = NOW()
                     WHERE id = ?"
                );
                $stmtWO->bind_param('sii', $remarks, $userId, $woId);
                $stmtWO->execute();
            } else {
                // If MOperation/PM rejects, keep status as 'draft' but record the rejection reason
                $stmtWO = $this->db->prepare(
                    "UPDATE {$this->table}
                     SET rejection_reason = ?, approved_by = ?, approved_at = NOW()
                     WHERE id = ?"
                );
                $stmtWO->bind_param('sii', $remarks, $userId, $woId);
                $stmtWO->execute();
            }
        } else if ($roleId == 3 || $roleId == 1) {
            // If Management/Admin approves, it is finally approved
            if ($status === 'approved') {
                $stmtWO = $this->db->prepare(
                    "UPDATE {$this->table}
                     SET status = 'approved', approved_by = ?, approved_at = NOW()
                     WHERE id = ?"
                );
                $stmtWO->bind_param('ii', $userId, $woId);
                $stmtWO->execute();
            }
        }
        return true;
    }
}
