
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
    protected $lastError = null;

    public function getLastError()
    {
        return $this->lastError;
    }


    public function getAll($projectId = null, $status = null, $requestedBy = null)
    {
        $sql = "SELECT lr.*,
                       p.name               AS project_name,
                       ph.phase_name        AS phase_name,
                       u_req.full_name      AS requested_by_name,
                       r_req.name           AS requested_by_role,
                       COALESCE(d_req.name, 'Engineering, Construction, & Design') AS requested_by_dept,
                       u_pm.full_name       AS pm_approved_by_name,
                       r_pm.name            AS pm_approved_by_role,
                       COALESCE(d_pm.name, 'Engineering, Construction, & Design') AS pm_approved_by_dept,
                       u_qc.full_name       AS qc_approved_by_name,
                       r_qc.name            AS qc_approved_by_role,
                       COALESCE(d_qc.name, 'Internal Audit & QC') AS qc_approved_by_dept,
                       u_pmp.full_name      AS pm_payment_approved_by_name,
                       r_pmp.name           AS pm_payment_approved_by_role,
                       COALESCE(d_pmp.name, 'Engineering, Construction, & Design') AS pm_payment_approved_by_dept,
                       u_mgmt.full_name     AS management_approved_by_name,
                       r_mgmt.name          AS management_approved_by_role,
                       COALESCE(d_mgmt.name, 'Management / Planning & Development') AS management_approved_by_dept,
                       u_paid.full_name     AS paid_by_name,
                       r_paid.name          AS paid_by_role,
                       COALESCE(d_paid.name, 'Accounts & Finance') AS paid_by_dept
                FROM {$this->table} lr
                LEFT JOIN projects p              ON p.id   = lr.project_id
                LEFT JOIN project_phases ph       ON ph.id  = lr.phase_id
                LEFT JOIN users u_req             ON u_req.id  = lr.requested_by
                LEFT JOIN roles r_req             ON r_req.id  = u_req.role_id
                LEFT JOIN employees emp_req       ON (emp_req.user_id = u_req.id OR emp_req.id = u_req.employee_id)
                LEFT JOIN designations des_req    ON des_req.id = COALESCE(emp_req.designation_id, u_req.designation_id)
                LEFT JOIN departments d_req       ON d_req.id = COALESCE(emp_req.department_id, des_req.department_id)
                LEFT JOIN users u_pm              ON u_pm.id   = lr.pm_approved_by
                LEFT JOIN roles r_pm              ON r_pm.id   = u_pm.role_id
                LEFT JOIN employees emp_pm        ON (emp_pm.user_id = u_pm.id OR emp_pm.id = u_pm.employee_id)
                LEFT JOIN designations des_pm     ON des_pm.id = COALESCE(emp_pm.designation_id, u_pm.designation_id)
                LEFT JOIN departments d_pm        ON d_pm.id = COALESCE(emp_pm.department_id, des_pm.department_id)
                LEFT JOIN users u_qc              ON u_qc.id   = lr.qc_approved_by
                LEFT JOIN roles r_qc              ON r_qc.id   = u_qc.role_id
                LEFT JOIN employees emp_qc        ON (emp_qc.user_id = u_qc.id OR emp_qc.id = u_qc.employee_id)
                LEFT JOIN designations des_qc     ON des_qc.id = COALESCE(emp_qc.designation_id, u_qc.designation_id)
                LEFT JOIN departments d_qc        ON d_qc.id = COALESCE(emp_qc.department_id, des_qc.department_id)
                LEFT JOIN users u_pmp             ON u_pmp.id  = lr.pm_payment_approved_by
                LEFT JOIN roles r_pmp             ON r_pmp.id  = u_pmp.role_id
                LEFT JOIN employees emp_pmp       ON (emp_pmp.user_id = u_pmp.id OR emp_pmp.id = u_pmp.employee_id)
                LEFT JOIN designations des_pmp    ON des_pmp.id = COALESCE(emp_pmp.designation_id, u_pmp.designation_id)
                LEFT JOIN departments d_pmp       ON d_pmp.id = COALESCE(emp_pmp.department_id, des_pmp.department_id)
                LEFT JOIN users u_mgmt            ON u_mgmt.id = lr.management_approved_by
                LEFT JOIN roles r_mgmt            ON r_mgmt.id = u_mgmt.role_id
                LEFT JOIN employees emp_mgmt      ON (emp_mgmt.user_id = u_mgmt.id OR emp_mgmt.id = u_mgmt.employee_id)
                LEFT JOIN designations des_mgmt   ON des_mgmt.id = COALESCE(emp_mgmt.designation_id, u_mgmt.designation_id)
                LEFT JOIN departments d_mgmt      ON d_mgmt.id = COALESCE(emp_mgmt.department_id, des_mgmt.department_id)
                LEFT JOIN users u_paid            ON u_paid.id = lr.paid_by
                LEFT JOIN roles r_paid            ON r_paid.id = u_paid.role_id
                LEFT JOIN employees emp_paid      ON (emp_paid.user_id = u_paid.id OR emp_paid.id = u_paid.employee_id)
                LEFT JOIN designations des_paid   ON des_paid.id = COALESCE(emp_paid.designation_id, u_paid.designation_id)
                LEFT JOIN departments d_paid      ON d_paid.id = COALESCE(emp_paid.department_id, des_paid.department_id)
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
                       r_req.name           AS requested_by_role,
                       COALESCE(d_req.name, 'Engineering, Construction, & Design') AS requested_by_dept,
                       u_pm.full_name       AS pm_approved_by_name,
                       r_pm.name            AS pm_approved_by_role,
                       COALESCE(d_pm.name, 'Engineering, Construction, & Design') AS pm_approved_by_dept,
                       u_qc.full_name       AS qc_approved_by_name,
                       r_qc.name            AS qc_approved_by_role,
                       COALESCE(d_qc.name, 'Internal Audit & QC') AS qc_approved_by_dept,
                       u_pmp.full_name      AS pm_payment_approved_by_name,
                       r_pmp.name           AS pm_payment_approved_by_role,
                       COALESCE(d_pmp.name, 'Engineering, Construction, & Design') AS pm_payment_approved_by_dept,
                       u_mgmt.full_name     AS management_approved_by_name,
                       r_mgmt.name          AS management_approved_by_role,
                       COALESCE(d_mgmt.name, 'Management / Planning & Development') AS management_approved_by_dept,
                       u_paid.full_name     AS paid_by_name,
                       r_paid.name          AS paid_by_role,
                       COALESCE(d_paid.name, 'Accounts & Finance') AS paid_by_dept,
                       u_disb.full_name     AS labor_disbursed_by_name,
                       r_disb.name          AS labor_disbursed_by_role,
                       COALESCE(d_disb.name, 'Engineering, Construction, & Design') AS labor_disbursed_by_dept
                FROM {$this->table} lr
                LEFT JOIN projects p              ON p.id   = lr.project_id
                LEFT JOIN project_phases ph       ON ph.id  = lr.phase_id
                LEFT JOIN users u_req             ON u_req.id  = lr.requested_by
                LEFT JOIN roles r_req             ON r_req.id  = u_req.role_id
                LEFT JOIN employees emp_req       ON (emp_req.user_id = u_req.id OR emp_req.id = u_req.employee_id)
                LEFT JOIN designations des_req    ON des_req.id = COALESCE(emp_req.designation_id, u_req.designation_id)
                LEFT JOIN departments d_req       ON d_req.id = COALESCE(emp_req.department_id, des_req.department_id)
                LEFT JOIN users u_pm              ON u_pm.id   = lr.pm_approved_by
                LEFT JOIN roles r_pm              ON r_pm.id   = u_pm.role_id
                LEFT JOIN employees emp_pm        ON (emp_pm.user_id = u_pm.id OR emp_pm.id = u_pm.employee_id)
                LEFT JOIN designations des_pm     ON des_pm.id = COALESCE(emp_pm.designation_id, u_pm.designation_id)
                LEFT JOIN departments d_pm        ON d_pm.id = COALESCE(emp_pm.department_id, des_pm.department_id)
                LEFT JOIN users u_qc              ON u_qc.id   = lr.qc_approved_by
                LEFT JOIN roles r_qc              ON r_qc.id   = u_qc.role_id
                LEFT JOIN employees emp_qc        ON (emp_qc.user_id = u_qc.id OR emp_qc.id = u_qc.employee_id)
                LEFT JOIN designations des_qc     ON des_qc.id = COALESCE(emp_qc.designation_id, u_qc.designation_id)
                LEFT JOIN departments d_qc        ON d_qc.id = COALESCE(emp_qc.department_id, des_qc.department_id)
                LEFT JOIN users u_pmp             ON u_pmp.id  = lr.pm_payment_approved_by
                LEFT JOIN roles r_pmp             ON r_pmp.id  = u_pmp.role_id
                LEFT JOIN employees emp_pmp       ON (emp_pmp.user_id = u_pmp.id OR emp_pmp.id = u_pmp.employee_id)
                LEFT JOIN designations des_pmp    ON des_pmp.id = COALESCE(emp_pmp.designation_id, u_pmp.designation_id)
                LEFT JOIN departments d_pmp       ON d_pmp.id = COALESCE(emp_pmp.department_id, des_pmp.department_id)
                LEFT JOIN users u_mgmt            ON u_mgmt.id = lr.management_approved_by
                LEFT JOIN roles r_mgmt            ON r_mgmt.id = u_mgmt.role_id
                LEFT JOIN employees emp_mgmt      ON (emp_mgmt.user_id = u_mgmt.id OR emp_mgmt.id = u_mgmt.employee_id)
                LEFT JOIN designations des_mgmt   ON des_mgmt.id = COALESCE(emp_mgmt.designation_id, u_mgmt.designation_id)
                LEFT JOIN departments d_mgmt      ON d_mgmt.id = COALESCE(emp_mgmt.department_id, des_mgmt.department_id)
                LEFT JOIN users u_paid            ON u_paid.id = lr.paid_by
                LEFT JOIN roles r_paid            ON r_paid.id = u_paid.role_id
                LEFT JOIN employees emp_paid      ON (emp_paid.user_id = u_paid.id OR emp_paid.id = u_paid.employee_id)
                LEFT JOIN designations des_paid   ON des_paid.id = COALESCE(emp_paid.designation_id, u_paid.designation_id)
                LEFT JOIN departments d_paid      ON d_paid.id = COALESCE(emp_paid.department_id, des_paid.department_id)
                LEFT JOIN users u_disb            ON u_disb.id = lr.labor_disbursed_by
                LEFT JOIN roles r_disb            ON r_disb.id = u_disb.role_id
                LEFT JOIN employees emp_disb      ON (emp_disb.user_id = u_disb.id OR emp_disb.id = u_disb.employee_id)
                LEFT JOIN designations des_disb   ON des_disb.id = COALESCE(emp_disb.designation_id, u_disb.designation_id)
                LEFT JOIN departments d_disb      ON d_disb.id = COALESCE(emp_disb.department_id, des_disb.department_id)
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

    public function generateRequisitionNo()
    {
        $year = date('Y');
        $prefix = 'LR-' . $year . '-';

        $sql = "SELECT requisition_no 
                FROM {$this->table} 
                WHERE requisition_no LIKE '{$prefix}%' 
                ORDER BY CAST(SUBSTRING_INDEX(requisition_no, '-', -1) AS UNSIGNED) DESC 
                LIMIT 1";
        $r = $this->db->query($sql);
        $maxNum = 0;
        if ($r && $row = $r->fetch_assoc()) {
            $parts = explode('-', $row['requisition_no']);
            $maxNum = (int)end($parts);
        }

        $nextNum = max($maxNum + 1, 1);

        while (true) {
            $candidate = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            $check = $this->db->prepare("SELECT id FROM {$this->table} WHERE requisition_no = ? LIMIT 1");
            if ($check) {
                $check->bind_param('s', $candidate);
                $check->execute();
                $check->store_result();
                $exists = ($check->num_rows > 0);
                $check->close();
                if (!$exists) {
                    return $candidate;
                }
            } else {
                return $candidate;
            }
            $nextNum++;
        }
    }

    public function create($data)
    {
        try {
            $reqNo = $this->generateRequisitionNo();

            $sql = "INSERT INTO {$this->table}
                    (requisition_no, project_id, phase_id, phase_target_id, requested_by,
                     requisition_date, work_date_from, work_date_to, labor_type,
                     labor_count, per_labor_rate, working_days, purpose, remarks, attachment,
                     status, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                $this->lastError = "Database prepare failed: " . $this->db->error;
                error_log($this->lastError);
                return false;
            }

            $projId    = (int)($data['project_id'] ?? 0);
            $phaseId   = !empty($data['phase_id']) ? (int)$data['phase_id'] : null;
            $targetId  = !empty($data['phase_target_id']) ? (int)$data['phase_target_id'] : null;
            $reqBy     = (int)($data['requested_by'] ?? 0);
            $reqDate   = !empty($data['requisition_date']) ? $data['requisition_date'] : date('Y-m-d');
            $from      = !empty($data['work_date_from']) ? $data['work_date_from'] : null;
            $to        = !empty($data['work_date_to']) ? $data['work_date_to'] : null;
            $lType     = !empty($data['labor_type']) ? $data['labor_type'] : 'General';
            $lCount    = max(1, (int)($data['labor_count'] ?? 1));
            $lRate     = (float)($data['per_labor_rate'] ?? 0);
            $wDays     = (float)($data['working_days'] ?? 1);
            $purpose   = trim($data['purpose'] ?? '');
            $remarks   = !empty($data['remarks']) ? trim($data['remarks']) : null;
            $attach    = !empty($data['attachment']) ? $data['attachment'] : null;
            $createdBy = (int)($data['created_by'] ?? $reqBy);

            $stmt->bind_param('siiiissssiddsssi',
                $reqNo, $projId, $phaseId, $targetId,
                $reqBy, $reqDate, $from, $to,
                $lType, $lCount, $lRate,
                $wDays, $purpose, $remarks,
                $attach, $createdBy
            );

            if (!$stmt->execute()) {
                $this->lastError = "Database execute failed: " . $stmt->error;
                error_log($this->lastError);
                $stmt->close();
                return false;
            }

            $insertId = $stmt->insert_id;
            $stmt->close();
            return $insertId;
        } catch (\Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log("Con_LaborRequisition create error: " . $e->getMessage());
            return false;
        }
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

    public function qcApprove($id, $userId, $amount = null, $note = null)
    {
        $amount = $amount !== null && $amount !== '' ? (float)$amount : null;

        // Auto-check and create qc_approved_amount column if missing
        $colChk = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'qc_approved_amount'");
        $hasQcAmt = ($colChk && $colChk->num_rows > 0);
        if (!$hasQcAmt) {
            @$this->db->query("ALTER TABLE {$this->table} ADD COLUMN qc_approved_amount DECIMAL(12,2) NULL DEFAULT NULL AFTER qc_approved_at");
            $colChk = $this->db->query("SHOW COLUMNS FROM {$this->table} LIKE 'qc_approved_amount'");
            $hasQcAmt = ($colChk && $colChk->num_rows > 0);
        }

        if ($hasQcAmt && $amount !== null) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status='qc_approved', qc_approved_by=?, qc_approved_at=NOW(), qc_approved_amount=?, qc_note=?, updated_at=NOW() WHERE id=? AND status='payment_requested'");
            $stmt->bind_param('idsi', $userId, $amount, $note, $id);
        } else {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET status='qc_approved', qc_approved_by=?, qc_approved_at=NOW(), qc_note=?, updated_at=NOW() WHERE id=? AND status='payment_requested'");
            $stmt->bind_param('isi', $userId, $note, $id);
        }
        return $stmt->execute();
    }

    public function qcReject($id, $userId, $reason)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='rejected', qc_approved_by=?, rejection_reason=?, updated_at=NOW() WHERE id=? AND status='payment_requested'");
        $stmt->bind_param('isi', $userId, $reason, $id);
        return $stmt->execute();
    }

    public function pmPaymentApprove($id, $userId, $amount = null, $note = null)
    {
        $amount = $amount !== null && $amount !== '' ? (float)$amount : null;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='pm_payment_approved', pm_payment_approved_by=?, pm_payment_approved_at=NOW(), pm_verified_amount=?, pm_payment_note=?, updated_at=NOW() WHERE id=? AND status='qc_approved'");
        $stmt->bind_param('idsi', $userId, $amount, $note, $id);
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
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='management_approved', management_approved_by=?, management_approved_at=NOW(), approved_amount=?, management_note=?, updated_at=NOW() WHERE id=? AND status IN ('qc_approved', 'payment_requested', 'pm_payment_approved')");
        $stmt->bind_param('idsi', $userId, $approvedAmount, $note, $id);
        return $stmt->execute();
    }

    public function managementReject($id, $userId, $reason)
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET status='rejected', management_approved_by=?, rejection_reason=?, updated_at=NOW() WHERE id=? AND status IN ('qc_approved', 'payment_requested', 'pm_payment_approved')");
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
            $paidAmt        = (float)$data['paid_amount'];
            $paidBy         = (int)$data['paid_by'];

            // 2. Insert into payment log (check if proof_image column exists in table)
            $colCheck = $this->db->query("SHOW COLUMNS FROM con_labor_payment_logs LIKE 'proof_image'");
            $hasProofCol = ($colCheck && $colCheck->num_rows > 0);

            if ($hasProofCol) {
                $stmt = $this->db->prepare(
                    "INSERT INTO con_labor_payment_logs
                     (requisition_id, amount, paid_by, payment_date, payment_method,
                      reference, note, proof_image)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$stmt) {
                    throw new \Exception("Prepare failed for payment log: " . $this->db->error);
                }
                $stmt->bind_param('idisssss',
                    $id, $paidAmt, $paidBy,
                    $data['payment_date'], $data['payment_method'],
                    $data['payment_reference'], $data['payment_note'], $proofImage
                );
            } else {
                $stmt = $this->db->prepare(
                    "INSERT INTO con_labor_payment_logs
                     (requisition_id, amount, paid_by, payment_date, payment_method,
                      reference, note)
                     VALUES (?, ?, ?, ?, ?, ?, ?)"
                );
                if (!$stmt) {
                    throw new \Exception("Prepare failed for payment log: " . $this->db->error);
                }
                $stmt->bind_param('idissss',
                    $id, $paidAmt, $paidBy,
                    $data['payment_date'], $data['payment_method'],
                    $data['payment_reference'], $data['payment_note']
                );
            }
            $stmt->execute();

            // 3. Update main requisition
            $stmt2 = $this->db->prepare(
                "UPDATE {$this->table}
                 SET status = ?, total_paid = ?, paid_amount = ?,
                     paid_by = ?, payment_date = ?, payment_method = ?,
                     payment_reference = ?, payment_note = ?, updated_at = NOW()
                 WHERE id = ? AND status IN ('management_approved','partially_paid')"
            );
            if (!$stmt2) {
                throw new \Exception("Prepare failed for requisition update: " . $this->db->error);
            }
            $stmt2->bind_param('sddissssi',
                $newStatus, $newTotalPaid, $paidAmt,
                $paidBy, $data['payment_date'], $data['payment_method'],
                $data['payment_reference'], $data['payment_note'], $id
            );
            $stmt2->execute();

            $this->db->commit();
            return $newStatus;
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Con_LaborRequisition markPaid Error: " . $e->getMessage());
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
        $sql = "SELECT pl.*, pl.amount AS paid_amount, pl.reference AS payment_reference, pl.note AS payment_note, u.full_name AS paid_by_name
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
