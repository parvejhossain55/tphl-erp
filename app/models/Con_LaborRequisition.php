
<?php
/**
 * Con_LaborRequisition Model
 * Table: con_labor_requisitions
 *
 * Status Flow:
 *  pending -> pm_approved / rejected
 *  pm_approved -> work_completed (SE marks done)
 *  work_completed -> payment_requested
 *  payment_requested -> qc_approved / rejected
 *  qc_approved -> pm_payment_approved / rejected
 *  pm_payment_approved -> management_approved / rejected
 *  management_approved -> paid (Accounts)
 */
class Con_LaborRequisition extends Model
{
    protected $table = 'con_labor_requisitions';

    public function getAll($projectId = null, $status = null, $requestedBy = null)
    {
        $sql = "SELECT lr.*,
                       p.name               AS project_name,
                       ph.phase_name        AS phase_name,
                       u_req.full_name      AS requested_by_name,
                       u_pm.full_name       AS pm_approved_by_name,
                       u_qc.full_name       AS qc_approved_by_name,
                       u_pmp.full_name      AS pm_payment_approved_by_name,
                       u_mgmt.full_name     AS management_approved_by_name,
                       u_paid.full_name     AS paid_by_name
                FROM {$this->table} lr
                LEFT JOIN projects p              ON p.id   = lr.project_id
                LEFT JOIN project_phases ph       ON ph.id  = lr.phase_id
                LEFT JOIN users u_req             ON u_req.id  = lr.requested_by
                LEFT JOIN users u_pm              ON u_pm.id   = lr.pm_approved_by
                LEFT JOIN users u_qc              ON u_qc.id   = lr.qc_approved_by
                LEFT JOIN users u_pmp             ON u_pmp.id  = lr.pm_payment_approved_by
                LEFT JOIN users u_mgmt            ON u_mgmt.id = lr.management_approved_by
                LEFT JOIN users u_paid            ON u_paid.id = lr.paid_by
                WHERE 1=1";

        if ($projectId)   $sql .= " AND lr.project_id = ".(int)$projectId;
        if ($status)      $sql .= " AND lr.status = '".$this->db->real_escape_string($status)."'";
        if ($requestedBy) $sql .= " AND lr.requested_by = ".(int)$requestedBy;

        $sql .= " ORDER BY lr.id DESC";
        $r = $this->db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function find($id)
    {
        $sql = "SELECT lr.*,
                       p.name               AS project_name,
                       ph.phase_name        AS phase_name,
                       u_req.full_name      AS requested_by_name,
                       u_pm.full_name       AS pm_approved_by_name,
                       u_qc.full_name       AS qc_approved_by_name,
                       u_pmp.full_name      AS pm_payment_approved_by_name,
                       u_mgmt.full_name     AS management_approved_by_name,
                       u_paid.full_name     AS paid_by_name,
                       u_disb.full_name     AS labor_disbursed_by_name
                FROM {$this->table} lr
                LEFT JOIN projects p              ON p.id   = lr.project_id
                LEFT JOIN project_phases ph       ON ph.id  = lr.phase_id
                LEFT JOIN users u_req             ON u_req.id  = lr.requested_by
                LEFT JOIN users u_pm              ON u_pm.id   = lr.pm_approved_by
                LEFT JOIN users u_qc              ON u_qc.id   = lr.qc_approved_by
                LEFT JOIN users u_pmp             ON u_pmp.id  = lr.pm_payment_approved_by
                LEFT JOIN users u_mgmt            ON u_mgmt.id = lr.management_approved_by
                LEFT JOIN users u_paid            ON u_paid.id = lr.paid_by
                LEFT JOIN users u_disb            ON u_disb.id = lr.labor_disbursed_by
                WHERE lr.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPendingListForDashboard($statusIn, $projectId = null, $limit = 10)
    {
        $statuses = implode("','", array_map(fn($s) => $this->db->real_escape_string($s), $statusIn));
        $extra = $projectId ? " AND lr.project_id = ".(int)$projectId : "";
        $sql = "SELECT lr.id, lr.requisition_no, lr.status, lr.requisition_date,
                       lr.labor_count, lr.per_labor_rate, lr.working_days, lr.total_amount,
                       lr.purpose, lr.labor_type,
                       p.name AS project_name,
                       u.full_name AS requested_by_name
                FROM {$this->table} lr
                LEFT JOIN projects p ON p.id = lr.project_id
                LEFT JOIN users u    ON u.id = lr.requested_by
                WHERE lr.status IN ('$statuses') $extra
                ORDER BY lr.created_at DESC LIMIT $limit";
        $r = $this->db->query($sql);
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function create($data)
    {
        $year  = date('Y');
        $r     = $this->db->query("SELECT COUNT(*) AS cnt FROM {$this->table} WHERE YEAR(created_at) = $year");
        $count = (int)($r->fetch_assoc()['cnt'] ?? 0) + 1;
        $reqNo = 'LR-'.$year.'-'.str_pad($count, 4, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO {$this->table}
                (requisition_no, project_id, phase_id, phase_target_id, requested_by,
                 requisition_date, work_date_from, work_date_to, labor_type,
                 labor_count, per_labor_rate, working_days, purpose, remarks, attachment,
                 status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
        $stmt = $this->db->prepare($sql);
        $phaseId   = $data['phase_id']       ?: null;
        $targetId  = $data['phase_target_id'] ?: null;
        $from      = $data['work_date_from']  ?: null;
        $to        = $data['work_date_to']    ?: null;
        $attach    = $data['attachment']      ?: null;
        $stmt->bind_param('siiiissssiddsssi',
            $reqNo, $data['project_id'], $phaseId, $targetId,
            $data['requested_by'], $data['requisition_date'], $from, $to,
            $data['labor_type'], $data['labor_count'], $data['per_labor_rate'],
            $data['working_days'], $data['purpose'], $data['remarks'],
            $attach, $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    // -- Approval Steps --

    public function pmApprove($id, $userId, $note = null)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='pm_approved', pm_approved_by=?, pm_approved_at=NOW(), pm_note=?, updated_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    public function pmReject($id, $userId, $reason)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='rejected', pm_approved_by=?, pm_approved_at=NOW(), rejection_reason=?, updated_at=NOW() WHERE id=? AND status='pending'");
        $stmt->bind_param('isi', $userId, $reason, $id);
        return $stmt->execute();
    }

    public function markWorkComplete($id, $userId)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='work_completed', updated_at=NOW() WHERE id=? AND status='pm_approved'");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function requestPayment($id, $userId)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='payment_requested', payment_requested_by=?, payment_requested_at=NOW(), updated_at=NOW() WHERE id=? AND status='work_completed'");
        $stmt->bind_param('ii', $userId, $id);
        return $stmt->execute();
    }

    public function qcApprove($id, $userId, $note = null)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='qc_approved', qc_approved_by=?, qc_approved_at=NOW(), qc_note=?, updated_at=NOW() WHERE id=? AND status='payment_requested'");
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    public function qcReject($id, $userId, $reason)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='rejected', qc_approved_by=?, rejection_reason=?, updated_at=NOW() WHERE id=? AND status='payment_requested'");
        $stmt->bind_param('isi', $userId, $reason, $id);
        return $stmt->execute();
    }

    public function pmPaymentApprove($id, $userId, $note = null)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='pm_payment_approved', pm_payment_approved_by=?, pm_payment_approved_at=NOW(), pm_payment_note=?, updated_at=NOW() WHERE id=? AND status='qc_approved'");
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    public function pmPaymentReject($id, $userId, $reason)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='rejected', pm_payment_approved_by=?, rejection_reason=?, updated_at=NOW() WHERE id=? AND status='qc_approved'");
        $stmt->bind_param('isi', $userId, $reason, $id);
        return $stmt->execute();
    }

    public function managementApprove($id, $userId, $approvedAmount, $note = null)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='management_approved', management_approved_by=?, management_approved_at=NOW(), approved_amount=?, management_note=?, updated_at=NOW() WHERE id=? AND status='pm_payment_approved'");
        $stmt->bind_param('idsi', $userId, $approvedAmount, $note, $id);
        return $stmt->execute();
    }

    public function managementReject($id, $userId, $reason)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='rejected', management_approved_by=?, rejection_reason=?, updated_at=NOW() WHERE id=? AND status='pm_payment_approved'");
        $stmt->bind_param('isi', $userId, $reason, $id);
        return $stmt->execute();
    }


    // =========================================================================
    // PAYMENT (supports partial payments)
    // =========================================================================

    /**
     * Log a payment and update requisition status.
     * If total_paid >= approved_amount → status = 'paid'
     * Otherwise                        → status = 'partially_paid'
     */
    public function markPaid($id, $data)
    {
        $this->db->begin_transaction();
        try {
            // 1. Get current total_paid & approved_amount
            $rr = $this->db->query(
                "SELECT approved_amount, total_paid FROM {$this->table} WHERE id = " . (int)$id
            );
            $row = $rr ? $rr->fetch_assoc() : null;
            if (!$row) throw new \Exception('Record not found');

            $approvedAmount = (float)$row['approved_amount'];
            $newTotalPaid   = (float)$row['total_paid'] + (float)$data['paid_amount'];
            $newStatus      = $newTotalPaid >= $approvedAmount ? 'paid' : 'partially_paid';
            $proofImage     = $data['proof_image'] ?? null;

            // 2. Insert into payment log
            $stmt = $this->db->prepare(
                "INSERT INTO con_labor_payment_logs
                 (requisition_id, paid_amount, paid_by, payment_date, payment_method,
                  payment_reference, payment_note, proof_image)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('idisssss',
                $id, $data['paid_amount'], $data['paid_by'],
                $data['payment_date'], $data['payment_method'],
                $data['payment_reference'], $data['payment_note'], $proofImage
            );
            $stmt->execute();

            // 3. Update main requisition
            $stmt2 = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = ?, total_paid = ?, paid_amount = ?,
                     paid_by = ?, payment_date = ?, payment_method = ?,
                     payment_reference = ?, payment_note = ?, updated_at = NOW()
                 WHERE id = ? AND status IN ('management_approved','partially_paid')"
            );
            $stmt2->bind_param('sddississi',
                $newStatus, $newTotalPaid, $data['paid_amount'],
                $data['paid_by'], $data['payment_date'], $data['payment_method'],
                $data['payment_reference'], $data['payment_note'], $id
            );
            $stmt2->execute();

            $this->db->commit();
            return $newStatus;
        } catch (\Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Management approves the remaining balance (for partially_paid requisitions).
     * Resets status to management_approved so accounts can pay again.
     */
    public function managementApproveRemaining($id, $userId, $note = null)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'management_approved',
                 management_approved_by = ?, management_approved_at = NOW(),
                 management_note = ?, updated_at = NOW()
             WHERE id = ? AND status = 'partially_paid'"
        );
        $stmt->bind_param('isi', $userId, $note, $id);
        return $stmt->execute();
    }

    /**
     * Get all payment log entries for a requisition.
     */
    public function getPaymentLogs($requisitionId)
    {
        $sql = "SELECT pl.*, u.full_name AS paid_by_name
                FROM con_labor_payment_logs pl
                LEFT JOIN users u ON u.id = pl.paid_by
                WHERE pl.requisition_id = ?
                ORDER BY pl.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $requisitionId);
        $stmt->execute();
        $r = $stmt->get_result();
        return $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * SE confirms they have disbursed the cash to labourers.
     * Accepts proof image path and note. Status → 'labor_disbursed'.
     */
    public function markLaborDisbursed($id, $userId, $proof = null, $note = null)
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table}
             SET status = 'labor_disbursed',
                 labor_disbursed_by = ?,
                 labor_disbursed_at = NOW(),
                 labor_disbursement_proof = ?,
                 labor_disbursement_note = ?,
                 updated_at = NOW()
             WHERE id = ? AND status = 'paid'"
        );
        $stmt->bind_param('issi', $userId, $proof, $note, $id);
        return $stmt->execute();
    }
}
