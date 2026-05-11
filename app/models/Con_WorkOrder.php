<?php

class Con_WorkOrder extends Model
{
    protected $table = 'con_work_orders';

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
                       cm.full_name    AS completed_by_name
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
                       tf.target_title AS target_title,
                       tf.target_percentage AS target_percentage,
                       tf.year         AS target_year,
                       tf.month        AS target_month,
                       tf.week_number  AS target_week,
                       wp.work_description AS work_plan_title,
                       wp.year         AS plan_year,
                       wp.month        AS plan_month
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
                LEFT JOIN target_fixed tf           ON tf.id = wo.target_id
                LEFT JOIN project_working_plans wp  ON wp.id = wo.work_plan_id
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
                       c.name AS contractor_name,
                       (SELECT COALESCE(SUM(IF(approved_amount IS NOT NULL AND approved_amount > 0, approved_amount, bill_amount)), 0)
                        FROM contractor_bill_requests
                        WHERE work_order_id = wo.id AND status != 'rejected') AS billed_amount
                FROM {$this->table} wo
                LEFT JOIN contractors c ON c.id = wo.contractor_id
                WHERE wo.project_id = ? AND wo.status IN ({$placeholders})
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
                 work_plan_id,
                 status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft', ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiiiiisssssssdddssddddiii',
            $data['project_id'],
            $data['contractor_id'],
            $data['design_id'],
            $data['work_type_id'],
            $data['boq_id'],
            $woNumber,
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
            $data['work_plan_id'],
            $data['created_by']
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
                    work_plan_id=?
                WHERE id=? AND status='draft'";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiiiiisssssdddssddddii',
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
            $data['work_plan_id'],
            $id
        );
        return $stmt->execute();
    }

    // =========================================================================
    // APPROVE (Director / PM Head)
    // =========================================================================
    public function approve($id, $userId)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status='approved', approved_by=?, approved_at=NOW()
             WHERE id=? AND status='draft'"
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
        $sql = "SELECT wr.*,
                       u.full_name  AS engineer_name,
                       tf.target_title,
                       tf.target_percentage
                FROM weekly_progress_report wr
                LEFT JOIN users        u  ON u.id  = wr.site_engineer_id
                LEFT JOIN target_fixed tf ON tf.id = wr.target_id
                WHERE wr.work_order_id = ?
                ORDER BY wr.report_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $woId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // CASCADE HELPERS for dropdowns
    // =========================================================================

    /** Working Plans for a project */
    public function getWorkPlansByProject($projectId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, work_description, year, month
             FROM project_working_plans
             WHERE project_id = ?
             ORDER BY year DESC, month DESC"
        );
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Targets (weekly) for a Working Plan */
    public function getTargetsByWorkPlan($workPlanId)
    {
        $stmt = $this->db->prepare(
            "SELECT id, target_title, year, month, week_number, target_percentage
             FROM target_fixed
             WHERE work_plan_id = ?
             ORDER BY year DESC, month DESC, week_number ASC"
        );
        $stmt->bind_param('i', $workPlanId);
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
}
