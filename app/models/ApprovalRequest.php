<?php

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $allowedTables = [
        'lead_bookings',
        'employee_leaves',
        'payslips',

        'salary_requests',
        'cashout_requests',
        'manpower_requisitions',
        'advance_salaries',

        // Phase 2 / Construction & Client refund tables
        'client_refund_requests',
        'operational_fund_requests',
        'con_work_orders',
        'purchase_requests',
        'vendor_payment_requests',
        'contractor_bill_requests',
        'lead_payments',
        'project_incentive_claims',
        'fund_requisitions',
        'project_other_costs',
        'project_extra_assets',
        'con_labor_requisitions'
    ];
    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
        (department, request_type, title, source_table, source_id, requested_days, requested_by, approve_role, meta_data,request_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "ssssiidssd",
            $data['department'],
            $data['request_type'],
            $data['title'],
            $data['source_table'],
            $data['source_id'],
            $data['requested_days'],
            $data['requested_by'],
            $data['approve_role'],
            $data['meta_data'],
            $data['request_amount'],
        );

        $stmt->execute();

        if ($stmt->error) {
            die("Execute failed: " . $stmt->error);
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }


    private function parseMetaData(?string $meta)
    {
        return $meta ? json_decode($meta, true) : [];
    }
    private function getLeadById(int $id)
    {
        return $this->db
            ->query("SELECT id, full_name, phone FROM leads WHERE id = {$id}")
            ->fetch_assoc();
    }

    private function getFlatById(int $id)
    {
        $sql = "
        SELECT 
            f.id,
            f.flat_name,
            f.sqft,
            p.name AS project_name,
            l.name as location_name
        FROM flats f
        LEFT JOIN projects p ON p.id = f.project_id
        Left JOIN locations l ON l.id = p.location_id
        WHERE f.id = {$id}
        LIMIT 1
    ";

        return $this->db->query($sql)->fetch_assoc();
    }




    private function getEmployeeById(int $id)
    {
        $sql = "
        SELECT 
            e.id,
            e.full_name,
            e.phone,
            d.name AS designation
        FROM employees e
        LEFT JOIN designations d ON d.id = e.designation_id
        WHERE e.id = {$id}
        LIMIT 1
    ";

        return $this->db->query($sql)->fetch_assoc();
    }



    private function getSalaryDetails(int $employeeId, ?string $month = null)
    {
        if ($month) {
            $mysqlMonth = date('Y-m-d', strtotime($month));
            $stmt = $this->db->prepare("SELECT * FROM payslips WHERE employee_id = ? AND payslip_month = ?");
            $stmt->bind_param("is", $employeeId, $mysqlMonth);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM payslips WHERE employee_id = ?");
            $stmt->bind_param("i", $employeeId);
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    private function getLeaveDetails(int $leaveId)
    {
        return $this->db
            ->query("
            SELECT el.*, lt.name AS leave_type
            FROM employee_leaves el
            LEFT JOIN leave_types lt ON lt.id = el.leave_type_id
            WHERE el.id = {$leaveId}
        ")
            ->fetch_assoc();
    }

    private function getAccountDetails(string $sourceTable, int $sourceId)
    {
        return $this->db
            ->query("SELECT * FROM {$sourceTable} WHERE id = {$sourceId}")
            ->fetch_assoc();
    }
    private function getUserById(int $id)
    {
        $stmt = $this->db->prepare("
        SELECT 
            users.id, 
            users.full_name AS user_name, 
            users.email, 
            d.name AS designation
        FROM users
        LEFT JOIN designations AS d ON users.designation_id = d.id
        WHERE users.id = ?
    ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    private function resolveApprovalDetails(array $approval)
    {
        $meta = $this->parseMetaData($approval['meta_data']);
        $details = [];

        /* =====================
       SALES → LEAD DISCOUNT
       ===================== */
        if ($approval['request_type'] === 'discount') {

            if (!empty($meta['lead_id'])) {
                $details['lead'] = $this->getLeadById((int) $meta['lead_id']);
            }

            if (!empty($meta['flat_id'])) {
                $details['flat'] = $this->getFlatById((int) $meta['flat_id']);
            }

            // Universal requested date fallback
            $details['requested_on'] = $details['requested_on']
                ?? $approval['created_at'];
        }

        /* =====================
       HR → SALARY
       ===================== */
        if ($approval['request_type'] === 'salary') {

            if (!empty($meta['employee_id'])) {
                $details['employee'] = $this->getEmployeeById((int) $meta['employee_id']);
                $details['salary'] = $this->getSalaryDetails(
                    (int) $meta['employee_id'],
                    $meta['month'] ?? null
                );
            }
        }

        /* =====================
       HR → LEAVE
       ===================== */
        if ($approval['request_type'] === 'leave') {

            if (!empty($meta['user_id'])) {
                $details['employee'] = $this->getUserById((int) $meta['user_id']);
            }

            if (!empty($approval['source_id'])) {
                $leave = $this->getLeaveDetails((int) $approval['source_id']);

                if ($leave) {
                    $details['leave'] = $leave;
                    $details['requested_on'] = $leave['created_at'];
                }
            }
        }

        // Manpower request 

        // if($approval['request_type'] === 'manpower_requisition'){
        //     if(){

        //     }
        // }

        /* =====================
       ACCOUNTS → CASH IN / OUT
       ===================== */
        if ($approval['department'] === 'Accounts') {

            $details['account'] = $this->getAccountDetails(
                $approval['source_table'],
                (int) $approval['source_id']
            );

            if ($approval['request_type'] === 'Cash In Handover' && $approval['source_table'] === 'lead_payments') {
                $handoverToId = $details['account']['handover_to'] ?? null;
                if ($handoverToId) {
                    $employee = $this->getUserById((int) $handoverToId);
                    if ($employee) {
                        $meta['handover_to'] = $employee['user_name'];
                    }
                }
            }

            $details['meta'] = $meta; // purpose, payment_method etc
        }

        /* =====================
       GENERAL → MANPOWER REQUISITION
       ===================== */
        if ($approval['request_type'] === 'manpower_requisition') {
            $details['requisition'] = $this->db
                ->query("SELECT mr.*, d.name as department_name, des.name AS designation_name, p.name AS project_name
                        FROM manpower_requisitions mr
                        LEFT JOIN departments d ON mr.department_id = d.id
                        LEFT JOIN designations des ON mr.designation_id = des.id
                        LEFT JOIN projects p ON mr.project_id = p.id
                        WHERE mr.id = " . (int) $approval['source_id'])
                ->fetch_assoc();
        }

        /* =====================
       OFFICE PROCUREMENT → PROJECT EXTRA ASSETS
       ===================== */
        if (($approval['source_table'] ?? '') === 'project_extra_assets') {
            $peaRes = $this->db->query("
                SELECT pea.*, 
                       p.name AS project_name,
                       d.name AS department_name,
                       cat.name AS category_name,
                       u_req.full_name AS requested_by_name,
                       u_op.full_name AS operations_by_name,
                       u_ph.full_name AS purchase_head_by_name,
                       u_qc.full_name AS qc_audit_by_name,
                       u_mg.full_name AS mgmt_by_name
                FROM project_extra_assets pea
                LEFT JOIN projects p ON p.id = pea.project_id
                LEFT JOIN departments d ON d.id = pea.department_id
                LEFT JOIN office_procurement_categories cat ON cat.id = pea.item_category_id
                LEFT JOIN users u_req ON u_req.id = pea.requested_by
                LEFT JOIN users u_op ON u_op.id = pea.operations_by
                LEFT JOIN users u_ph ON u_ph.id = pea.purchase_head_by
                LEFT JOIN users u_qc ON u_qc.id = pea.qc_audit_by
                LEFT JOIN users u_mg ON u_mg.id = pea.mgmt_by
                WHERE pea.id = " . (int) $approval['source_id'] . "
                LIMIT 1
            ");
            if ($peaRes && $details['procurement'] = $peaRes->fetch_assoc()) {
                $details['meta'] = [
                    'item' => $details['procurement']['item_name'],
                    'quantity' => $details['procurement']['quantity'],
                    'unit_price' => number_format((float)$details['procurement']['unit_price'], 2),
                    'total_price' => number_format((float)$details['procurement']['total_price'], 2),
                    'location' => $details['procurement']['asset_location'],
                    'justification' => $details['procurement']['justification'],
                    'operations_status' => ucfirst($details['procurement']['operations_status']),
                    'purchase_head' => ucfirst($details['procurement']['purchase_head_status']),
                    'qc_audit' => ucfirst($details['procurement']['qc_audit_status']),
                    'management_status' => ucfirst($details['procurement']['mgmt_status']),
                ];
            }
        }

        /* =====================
       CONSTRUCTION → LABOR REQUISITIONS
       ===================== */
        if (($approval['source_table'] ?? '') === 'con_labor_requisitions') {
            $lrRes = $this->db->query("
                SELECT lr.*, p.name AS project_name, u.full_name AS requested_by_name
                FROM con_labor_requisitions lr
                LEFT JOIN projects p ON p.id = lr.project_id
                LEFT JOIN users u ON u.id = lr.requested_by
                WHERE lr.id = " . (int) $approval['source_id'] . "
                LIMIT 1
            ");
            if ($lrRes && $details['labor_req'] = $lrRes->fetch_assoc()) {
                $details['meta'] = [
                    'project'     => $details['labor_req']['project_name'],
                    'labor_count' => $details['labor_req']['labor_count'] . ' ' . $details['labor_req']['labor_type'],
                    'rate'        => '৳' . number_format((float)$details['labor_req']['per_labor_rate'], 2),
                    'days'        => $details['labor_req']['working_days'] . ' Days',
                    'purpose'     => $details['labor_req']['purpose'],
                ];
            }
        }

        // Fetch requester info
        if (!empty($approval['requested_by'])) {
            $details['requester'] = $this->getUserById((int) $approval['requested_by']);
        }


        return $details;
    }

    public function getPendingForRole(array $roles, $limit = 5)
    {
        $this->syncMissingFundRequisitions();
        $this->syncMissingOfficeProcurements();
        $this->syncMissingLaborRequisitions();
        $this->syncMissingVendorPaymentRequests();

        $roles = array_map('strtolower', $roles);

        if (empty($roles)) {
            return [];
        }

        $conditions = [];
        $params = [];
        $types = '';

        foreach ($roles as $role) {
            $conditions[] = "FIND_IN_SET(?, LOWER(approve_role))";
            $params[] = $role;
            $types .= 's';
        }

        $limit = (int) $limit;

        $sql = "SELECT *
            FROM {$this->table}
            WHERE status='pending'
            AND (" . implode(' OR ', $conditions) . ")
            ORDER BY created_at DESC
            LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($rows as &$row) {
            $row['context'] = $this->resolveApprovalDetails($row);
        }

        return $rows;
    }


    public function updateStatus($id, $status, $approvedAmount = null, $approved_days = null, $approvalNote = null)
    {
        $approvedBy = $_SESSION['user']['id'] ?? null;

        $request = $this->find($id);
        if (!$request) {
            return false;
        }

        $sourceTable = $request['source_table'];
        $sourceId = (int) $request['source_id'];

        if ($sourceTable === 'advance_salaries' && strtolower($request['approve_role'] ?? '') === 'hr_head' && $status === 'approved') {
            // Update source table
            $this->db->query("UPDATE advance_salaries SET status = 'pending_accounts', approved_date = NOW() WHERE id = {$sourceId}");

            // Forward to Accounts!
            $sqlForward = "UPDATE {$this->table}
                SET 
                    department = 'Accounts',
                    request_type = 'advance_salary_payment',
                    approve_role = 'management',
                    status = 'pending',
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $hrNote = "Approved by HR Head. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $hrNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'payslips' && strtolower($request['approve_role'] ?? '') === 'hr' && $status === 'approved') {
            // Update payslip hr_status
            $this->db->query("UPDATE payslips SET hr_status = 'approved' WHERE id = {$sourceId}");

            // Forward to management for final approval
            $sqlForward = "UPDATE {$this->table}
                SET 
                    approve_role = 'management',
                    status = 'pending',
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $hrNote = "Approved by HR. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('si', $hrNote, $id);
            return $stmtForward->execute();
        }

        if ($sourceTable === 'vendor_payment_requests' && (strpos(strtolower($request['approve_role'] ?? ''), 'purchase team leader') !== false || strtolower($request['approve_role'] ?? '') === 'qc') && $status === 'approved') {
            // Update source table to QC Approved
            $qcApprovedAmt = ($approvedAmount !== null && $approvedAmount > 0) ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0);
            $stmtVpr = $this->db->prepare("UPDATE vendor_payment_requests SET status = 'QC Approved', qc_verified = 1, qc_approved_by_id = ?, qc_approved_at = NOW(), qc_approved_amount = ?, qc_note = ? WHERE id = ?");
            if ($stmtVpr) {
                $stmtVpr->bind_param('idsi', $approvedBy, $qcApprovedAmt, $approvalNote, $sourceId);
                $stmtVpr->execute();
                $stmtVpr->close();
            }

            // Forward to management instead of marking as completed
            $sqlForward = "UPDATE {$this->table}
                SET 
                    approve_role = 'management',
                    status = 'pending',
                    request_amount = ?,
                    approved_amount = NULL,
                    approved_by = NULL,
                    approval_note = ?,
                    approved_at = NULL
                WHERE id = ?";
            $stmtForward = $this->db->prepare($sqlForward);
            $qcNote = "Audited & Approved by QC. Note: " . ($approvalNote ?? '');
            $stmtForward->bind_param('dsi', $qcApprovedAmt, $qcNote, $id);
            return $stmtForward->execute();
        }

        $sql = "UPDATE {$this->table}
            SET 
                status = ?, 
                approved_amount = ?, 
                approved_days = ?, 
                approved_by = ?, 
                approval_note = ?, 
                approved_at = NOW()
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'sdiisi',
            $status,
            $approvedAmount,
            $approved_days,
            $approvedBy,
            $approvalNote,
            $id
        );
        $stmt->execute();


        $request = $this->find($id);
        if (!$request || empty($request['source_table']) || empty($request['source_id'])) {
            return false;
        }

        $sourceTable = $request['source_table'];
        $sourceId = (int) $request['source_id'];

        if (!in_array($sourceTable, $this->allowedTables, true)) {
            throw new Exception("Invalid source table: {$sourceTable}");
        }

        $columns = [];
        $result = $this->db->query("SHOW COLUMNS FROM {$sourceTable}");
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }


        $sourceStatus = match ($status) {
            'partial', 'partially_approved' => 'partial',
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };

        // Table-specific adjustments to match ENUM definitions in database
        if ($sourceTable === 'vendor_payment_requests') {
            $sourceStatus = match ($status) {
                'partial', 'partially_approved' => 'Partially_Paid',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => 'Submitted',
            };
        } elseif ($sourceTable === 'contractor_bill_requests') {
            $sourceStatus = match ($status) {
                'partial', 'partially_approved' => 'partially_paid',
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'submitted',
            };
        } elseif ($sourceTable === 'operational_fund_requests') {
            $sourceStatus = match ($status) {
                'partial', 'partially_approved' => 'partially_paid',
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'pending',
            };
        } elseif ($sourceTable === 'lead_payments') {
            $sourceStatus = match ($status) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'pending',
            };
        } elseif ($sourceTable === 'advance_salaries') {
            $sourceStatus = match ($status) {
                'approved' => 'approved',
                'rejected' => 'rejected',
                default => 'pending_hr',
            };
        } elseif ($sourceTable === 'purchase_requests') {
            $sourceStatus = match ($status) {
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                default => 'Submitted',
            };
        }

        if ($sourceTable === 'con_labor_requisitions') {
            $lrStatus = ($status === 'approved') ? 'management_approved' : 'rejected';
            $lrAppAmt = ($status === 'approved') ? ($approvedAmount !== null && $approvedAmount > 0 ? (float)$approvedAmount : (float)($request['request_amount'] ?? 0)) : null;
            $stmtLr = $this->db->prepare(
                "UPDATE con_labor_requisitions
                 SET status = ?,
                     management_approved_by = ?,
                     management_approved_at = NOW(),
                     approved_amount = COALESCE(?, approved_amount, total_amount),
                     management_note = ?,
                     rejection_reason = CASE WHEN ? = 'rejected' THEN ? ELSE rejection_reason END,
                     updated_at = NOW()
                 WHERE id = ?"
            );
            if ($stmtLr) {
                $stmtLr->bind_param('sidsissi', $lrStatus, $approvedBy, $lrAppAmt, $approvalNote, $lrStatus, $approvalNote, $sourceId);
                $stmtLr->execute();
                $stmtLr->close();
            }
            return true;
        }

        if ($sourceTable === 'project_extra_assets') {
            $mgmtStatus = ($status === 'approved') ? 'approved' : 'rejected';
            $overallStatus = ($status === 'approved') ? 'approved' : 'rejected';
            $qcStatus = ($status === 'approved') ? 'approved' : 'rejected';

            $stmtPea = $this->db->prepare(
                "UPDATE project_extra_assets 
                 SET qc_audit_status = CASE WHEN qc_audit_status = 'pending' THEN ? ELSE qc_audit_status END,
                     mgmt_status = ?,
                     mgmt_by = ?,
                     mgmt_at = NOW(),
                     mgmt_note = ?,
                     overall_status = ?
                 WHERE id = ?"
            );
            if ($stmtPea) {
                $stmtPea->bind_param('ssissi', $qcStatus, $mgmtStatus, $approvedBy, $approvalNote, $overallStatus, $sourceId);
                $stmtPea->execute();
                $stmtPea->close();
            }

            if ($status === 'approved') {
                try {
                    require_once dirname(__DIR__) . '/services/AutoPostingService.php';
                    $autoPosting = new \AutoPostingService($this->db);
                    $assetExpenseAccId = $autoPosting->getAccountIdByCode('5220');
                    $vendorPayableAccId = $autoPosting->getAccountIdByCode('2110');
                    
                    $reqRes = $this->db->query("SELECT * FROM project_extra_assets WHERE id = {$sourceId} LIMIT 1");
                    if ($reqRes && $req = $reqRes->fetch_assoc()) {
                        if ((float)$req['total_price'] > 0) {
                            $procurementOffice = !empty($req['asset_location']) ? $req['asset_location'] : null;
                            $autoPosting->postAccrualEntry(
                                'asset_procurement',
                                $sourceId,
                                $assetExpenseAccId,
                                $vendorPayableAccId,
                                $req['total_price'],
                                "Office Asset Procurement (PR-{$sourceId}): " . $req['item_name'] . " (Qty: {$req['quantity']})",
                                $req['project_id'],
                                'employee',
                                $req['assigned_to'] ?? $req['requested_by'],
                                $approvedBy,
                                $procurementOffice
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    error_log("AutoPosting Exception (Procurement Approval via Central): " . $e->getMessage());
                }
            }
            return true;
        }

        $updateFields = [];
        $params = [];
        $types = '';

        $requestType = $request['request_type'] ?? '';

        if ($sourceTable === 'lead_payments' && $requestType === 'Penalty Waiver Request') {
            if (in_array('waiver_status', $columns)) {
                $updateFields[] = "waiver_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            }
            if ($approvedAmount !== null && in_array('penalty_waived', $columns)) {
                $updateFields[] = "penalty_waived = ?";
                $types .= 'd';
                $params[] = (float) $approvedAmount;
            }
            if ($approvedBy !== null && in_array('waiver_approved_by', $columns)) {
                $updateFields[] = "waiver_approved_by = ?";
                $types .= 'i';
                $params[] = (int) $approvedBy;
            }
        } else {
            if (in_array('claim_status', $columns)) {
                $updateFields[] = "claim_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            } elseif (in_array('approval_status', $columns)) {
                $updateFields[] = "approval_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            } elseif ($sourceTable === 'payslips') {
                $payStatus = ($status === 'approved') ? 'approved' : 'reject';
                if (in_array('status', $columns)) {
                    $updateFields[] = "status = ?";
                    $types .= 's';
                    $params[] = $payStatus;
                }
            } elseif (in_array('status', $columns)) {
                $updateFields[] = "status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            } elseif ($sourceTable === 'lead_payments' && in_array('handover_status', $columns)) {
                $updateFields[] = "handover_status = ?";
                $types .= 's';
                $params[] = $sourceStatus;
            }

            if (in_array('approved_amount', $columns) && $approvedAmount !== null) {
                $updateFields[] = "approved_amount = ?";
                $types .= 'd';
                $params[] = $approvedAmount;
            }

            if (in_array('approved_days', $columns) && $approved_days !== null) {
                $updateFields[] = "approved_days = ?";
                $types .= 'i';
                $params[] = $approved_days;
            }

            if (in_array('approved_by', $columns)) {
                $updateFields[] = "approved_by = ?";
                $types .= 'i';
                $params[] = $approvedBy;
            }

            if ($sourceTable === 'purchase_requests') {
                if (in_array('mgmt_approved_by_id', $columns) && $approvedBy !== null) {
                    $updateFields[] = "mgmt_approved_by_id = ?";
                    $types .= 'i';
                    $params[] = $approvedBy;
                }
                if (in_array('mgmt_approved_at', $columns) && $status === 'approved') {
                    $updateFields[] = "mgmt_approved_at = NOW()";
                }
                if (in_array('management_note', $columns) && $approvalNote !== null) {
                    $updateFields[] = "management_note = ?";
                    $types .= 's';
                    $params[] = $approvalNote;
                }
            }

            if ($sourceTable === 'lead_payments' && $status === 'rejected' && in_array('handover_rejection_reason', $columns)) {
                $updateFields[] = "handover_rejection_reason = ?";
                $types .= 's';
                $params[] = $approvalNote;
            }

            if (in_array('approved_at', $columns)) {
                $updateFields[] = "approved_at = NOW()";
            }
        }

        //    7. Execute source update 
        if (!empty($updateFields)) {
            $sql2 = "UPDATE {$sourceTable} SET " . implode(', ', $updateFields) . " WHERE id = ?";

            if ($sourceTable === 'lead_payments') {
                $sql2 = "UPDATE {$sourceTable} SET " . implode(', ', $updateFields) . " WHERE id = ? OR bulk_parent_id = ?";
                $types .= 'ii';
                $params[] = $sourceId;
                $params[] = $sourceId;
            } else {
                $types .= 'i';
                $params[] = $sourceId;
            }

            $stmt2 = $this->db->prepare($sql2);
            if ($stmt2) {
                $stmt2->bind_param($types, ...$params);
                $stmt2->execute();
                $stmt2->close();
            } else {
                error_log("ApprovalRequest updateStatus prepare failed for table {$sourceTable}: " . $this->db->error . " | SQL: " . $sql2);
            }
        }

        return true;
    }


    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result(); // get_result() needed for mysqli
        return $result->fetch_assoc(); // fetch_assoc() instead of fetch()
    }

    public function getApprovalDashboardMetrics()
    {
        $this->syncMissingFundRequisitions();
        $this->syncMissingOfficeProcurements();
        $this->syncMissingLaborRequisitions();
        $this->syncMissingProjectOtherCosts();
        $this->syncMissingVendorPaymentRequests();
        $this->syncMissingAdvanceSalaries();
        $this->syncMissingCashoutRequests();

        $sql = "SELECT 
                    COUNT(*) AS total_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                    SUM(CASE WHEN status = 'pending' THEN IFNULL(request_amount, 0) ELSE 0 END) AS pending_amount,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
                    SUM(CASE WHEN status = 'approved' THEN IFNULL(approved_amount, IFNULL(request_amount, 0)) ELSE 0 END) AS approved_amount,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count
                FROM approval_requests";

        $res = $this->db->query($sql);
        $row = $res ? $res->fetch_assoc() : [];

        // Check Accounts total disbursed across incentive payments, vendor payment requests, fund requisitions, office procurement, labor requisitions & project other costs
        $sqlDisbursed = "SELECT 
                            (SELECT IFNULL(SUM(payment_amount), 0) FROM project_incentive_payments) + 
                            (SELECT IFNULL(SUM(approved_amount), 0) FROM vendor_payment_requests WHERE status = 'Approved') +
                            (SELECT IFNULL(SUM(amount), 0) FROM fund_requisitions WHERE status = 'paid') +
                            (SELECT IFNULL(SUM(paid_amount), 0) FROM project_extra_assets WHERE payment_status = 'paid') +
                            (SELECT IFNULL(SUM(total_paid), 0) FROM con_labor_requisitions WHERE status IN ('paid', 'partially_paid', 'labor_disbursed')) +
                            (SELECT IFNULL(SUM(COALESCE(payment_amount, approved_amount, request_amount)), 0) FROM project_other_costs WHERE status = 'paid') AS total_disbursed";
        $resD = $this->db->query($sqlDisbursed);
        $totalDisbursed = $resD ? (float) ($resD->fetch_assoc()['total_disbursed'] ?? 0) : 0.00;

        return [
            'total_requests' => (int) ($row['total_requests'] ?? 0),
            'pending_count' => (int) ($row['pending_count'] ?? 0),
            'pending_amount' => (float) ($row['pending_amount'] ?? 0.00),
            'approved_count' => (int) ($row['approved_count'] ?? 0),
            'approved_amount' => (float) ($row['approved_amount'] ?? 0.00),
            'rejected_count' => (int) ($row['rejected_count'] ?? 0),
            'total_disbursed' => $totalDisbursed
        ];
    }

    public function getAllApprovalHistory($filters = [])
    {
        $this->syncMissingFundRequisitions();
        $this->syncMissingOfficeProcurements();
        $this->syncMissingLaborRequisitions();
        $this->syncMissingProjectOtherCosts();
        $this->syncMissingVendorPaymentRequests();
        $this->syncMissingAdvanceSalaries();
        $this->syncMissingCashoutRequests();

        $where = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filters['status'])) {
            $stLower = strtolower(trim($filters['status']));
            if (!in_array($stLower, ['all', 'all_pending'])) {
                if ($stLower === 'approved') {
                    $where[] = "(ar.status IN ('approved', 'paid') OR ar.approve_role = 'completed')";
                } elseif ($stLower === 'pending' || $stLower === 'my_pending') {
                    $where[] = "ar.status IN ('pending', 'partially_approved', '')";
                } elseif ($stLower === 'rejected') {
                    $where[] = "ar.status = 'rejected'";
                } else {
                    $where[] = "ar.status = ?";
                    $params[] = $filters['status'];
                    $types .= "s";
                }
            }
        }

        if (!empty($filters['department'])) {
            $where[] = "(LOWER(ar.department) LIKE ? OR LOWER(d_req.name) LIKE ?)";
            $deptParam = '%' . strtolower($filters['department']) . '%';
            $params[] = $deptParam;
            $params[] = $deptParam;
            $types .= "ss";
        }

        if (!empty($filters['source_table'])) {
            if ($filters['source_table'] === 'purchase_requests' || $filters['source_table'] === 'project_extra_assets') {
                $where[] = "ar.source_table IN ('purchase_requests', 'project_extra_assets')";
            } else {
                $where[] = "ar.source_table = ?";
                $params[] = $filters['source_table'];
                $types .= "s";
            }
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    ar.id,
                    ar.department,
                    ar.request_type,
                    ar.title,
                    ar.source_table,
                    ar.source_id,
                    ar.approve_role,
                    IFNULL(ar.request_amount, 0.00) AS request_amount,
                    IFNULL(ar.approved_amount, ar.request_amount) AS approved_amount,
                    ar.status,
                    ar.created_at AS requested_at,
                    ar.approved_at,
                    ar.approval_note,
                    u_req.full_name AS requester_name,
                    e_req.employee_id AS requester_code,
                    d_req.name AS requester_dept,
                    u_app.full_name AS approver_name,
                    u_app.email AS approver_email,
                    IFNULL(des_app.name, 'Management Executive') AS approver_designation
                FROM approval_requests ar
                LEFT JOIN users u_req ON ar.requested_by = u_req.id
                LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
                LEFT JOIN departments d_req ON e_req.department_id = d_req.id
                LEFT JOIN users u_app ON ar.approved_by = u_app.id
                LEFT JOIN employees e_app ON (e_app.user_id = u_app.id OR e_app.id = u_app.employee_id)
                LEFT JOIN designations des_app ON des_app.id = COALESCE(e_app.designation_id, u_app.designation_id)
                WHERE {$whereClause}
                ORDER BY ar.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $list = [];
        while ($row = $result->fetch_assoc()) {
            $sourceTable = $row['source_table'];
            $sourceId = (int) $row['source_id'];

            $payoutStatus = 'N/A';
            $payoutAmount = 0.00;
            $payoutDate = null;

            // Default stage label & pending with text
            $pendingStageLabel = 'Pending Review';
            $pendingForText = 'Management';
            $appRole = strtolower($row['approve_role'] ?? 'management');
            if (empty($appRole)) $appRole = 'management';

            if ($appRole === 'dept_head') {
                $pendingStageLabel = 'Pending Dept Head';
                $pendingForText = 'Department Head / Reporting Boss';
            } elseif ($appRole === 'qc') {
                $pendingStageLabel = 'Pending QC & Audit';
                $pendingForText = 'QC & Internal Audit Head (Role 24)';
            } elseif ($appRole === 'accounts') {
                $pendingStageLabel = 'Pending Accounts Payout';
                $pendingForText = 'Accounts & Finance';
            } elseif (in_array($appRole, ['hr', 'hr_head'])) {
                $pendingStageLabel = 'Pending HR Approval';
                $pendingForText = 'HR Head';
            } elseif (in_array($appRole, ['purchase', 'purchase team leader', 'purchase_lead'])) {
                $pendingStageLabel = 'Pending Purchase Lead';
                $pendingForText = 'Purchase Team Leader';
            } elseif ($appRole === 'pm') {
                $pendingStageLabel = 'Pending PM Verification';
                $pendingForText = 'Project Manager';
            } else {
                $pendingStageLabel = 'Pending Management';
                $pendingForText = 'Management / Operation Head';
            }

            if ($sourceTable === 'project_incentive_claims') {
                $paySql = "SELECT SUM(payment_amount) AS total_paid, MAX(payment_date) AS pay_date 
                           FROM project_incentive_payments WHERE claim_id = ?";
                $pStmt = $this->db->prepare($paySql);
                if ($pStmt) {
                    $pStmt->bind_param("i", $sourceId);
                    $pStmt->execute();
                    $pRow = $pStmt->get_result()->fetch_assoc();
                    $pStmt->close();
                    $totalPaid = (float) ($pRow['total_paid'] ?? 0.00);
                    if ($totalPaid > 0) {
                        $payoutStatus = 'Paid & Settled';
                        $payoutAmount = $totalPaid;
                        $payoutDate = $pRow['pay_date'];
                        $pendingStageLabel = 'Paid & Settled';
                        $pendingForText = 'Settled';
                    } else if ($row['status'] === 'approved') {
                        $payoutStatus = 'Pending Accounts Payout';
                        $pendingStageLabel = 'Pending Accounts Payout';
                        $pendingForText = 'Accounts & Finance';
                    }
                }
            } else if ($sourceTable === 'con_labor_requisitions') {
                $lrSql = "SELECT status, total_amount, IFNULL(approved_amount, total_amount) AS app_amt, 
                                 IFNULL(total_paid, 0.00) AS total_paid, payment_date 
                          FROM con_labor_requisitions WHERE id = ? LIMIT 1";
                $lrStmt = $this->db->prepare($lrSql);
                if ($lrStmt) {
                    $lrStmt->bind_param("i", $sourceId);
                    $lrStmt->execute();
                    $lrRow = $lrStmt->get_result()->fetch_assoc();
                    $lrStmt->close();
                    if ($lrRow) {
                        $lrStatus = strtolower($lrRow['status'] ?? '');
                        if ($lrStatus === 'pending') {
                            $row['approve_role'] = 'pm';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending PM Verification';
                            $pendingForText = 'Project Manager';
                        } elseif ($lrStatus === 'payment_requested') {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head (Role 24)';
                        } elseif (in_array($lrStatus, ['qc_approved', 'pm_payment_approved'])) {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management / Operation Head';
                        } elseif ($lrStatus === 'management_approved') {
                            $row['approve_role'] = 'accounts';
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } elseif (in_array($lrStatus, ['paid', 'labor_disbursed'])) {
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif ($lrStatus === 'partially_paid') {
                            $row['status'] = 'partially_paid';
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($lrStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if (in_array($lrStatus, ['paid', 'labor_disbursed'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) ($lrRow['total_paid'] > 0 ? $lrRow['total_paid'] : $lrRow['app_amt']);
                            $payoutDate = $lrRow['payment_date'];
                        } else if ($lrStatus === 'partially_paid') {
                            $payoutStatus = 'Partially Paid';
                            $payoutAmount = (float) $lrRow['total_paid'];
                            $payoutDate = $lrRow['payment_date'];
                        } else if (in_array($lrStatus, ['management_approved']) || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float) $lrRow['app_amt'];
                        }
                    }
                }
            } else if ($sourceTable === 'vendor_payment_requests') {
                $vSql = "SELECT status, IFNULL(approved_amount, 0.00) AS app_amt, updated_at, payment_type, purchase_order_id 
                         FROM vendor_payment_requests WHERE id = ? LIMIT 1";
                $vStmt = $this->db->prepare($vSql);
                if ($vStmt) {
                    $vStmt->bind_param("i", $sourceId);
                    $vStmt->execute();
                    $vRow = $vStmt->get_result()->fetch_assoc();
                    $vStmt->close();
                    if ($vRow) {
                        $vStatus = strtolower(trim(str_replace('_', ' ', $vRow['status'] ?? '')));
                        $row['vpr_payment_type'] = $vRow['payment_type'] ?? null;
                        $row['po_number'] = $vRow['purchase_order_id'] ?? null;

                        if (in_array($vStatus, ['submitted', 'pending', 'draft'])) {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head (Role 24)';
                        } elseif (in_array($vStatus, ['qc approved', 'qc_approved'])) {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management / Operation Head';
                        } elseif (in_array($vStatus, ['approved', 'management approved', 'management_approved'])) {
                            $row['approve_role'] = 'accounts';
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } elseif (in_array($vStatus, ['paid', 'completed', 'fully paid', 'fully_paid'])) {
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif (in_array($vStatus, ['partially paid', 'partially_paid'])) {
                            $row['status'] = 'partially_paid';
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($vStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if (in_array($vStatus, ['approved', 'management approved', 'management_approved', 'paid', 'completed', 'fully paid', 'fully_paid'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) $vRow['app_amt'];
                            $payoutDate = $vRow['updated_at'];
                        } else if (in_array($vStatus, ['partially paid', 'partially_paid'])) {
                            $payoutStatus = 'Partially Paid';
                            $payoutAmount = (float) $vRow['app_amt'];
                            $payoutDate = $vRow['updated_at'];
                        } else if ($row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                        }
                    }
                }
            } else if ($sourceTable === 'project_other_costs') {
                $pocSql = "SELECT poc.*, 
                                  e.reporting_user_id, 
                                  u_dh.full_name as dh_boss_name,
                                  u_mgmt.full_name as mgmt_approver_name,
                                  des_mgmt.name as mgmt_approver_des,
                                  u_payer.full_name as payer_name
                           FROM project_other_costs poc 
                           LEFT JOIN employees e ON e.user_id = poc.created_by_id AND e.deleted_at IS NULL
                           LEFT JOIN users u_dh ON u_dh.id = e.reporting_user_id
                           LEFT JOIN users u_mgmt ON u_mgmt.id = poc.management_apporved_by_id
                           LEFT JOIN employees e_mgmt ON (e_mgmt.user_id = u_mgmt.id OR e_mgmt.id = u_mgmt.employee_id)
                           LEFT JOIN designations des_mgmt ON des_mgmt.id = COALESCE(e_mgmt.designation_id, u_mgmt.designation_id)
                           LEFT JOIN users u_payer ON u_payer.id = poc.payment_by_id
                           WHERE poc.id = ? LIMIT 1";
                $pocStmt = $this->db->prepare($pocSql);
                if ($pocStmt) {
                    $pocStmt->bind_param("i", $sourceId);
                    $pocStmt->execute();
                    $pocRow = $pocStmt->get_result()->fetch_assoc();
                    $pocStmt->close();
                    if ($pocRow) {
                        if (!empty($pocRow['mgmt_approver_name'])) {
                            $row['approver_name'] = $pocRow['mgmt_approver_name'];
                            $row['approver_designation'] = $pocRow['mgmt_approver_des'] ?? 'Management';
                        }
                        if (!empty($pocRow['approved_amount'])) {
                            $row['approved_amount'] = (float)$pocRow['approved_amount'];
                        }

                        $pocStatus = strtolower($pocRow['status'] ?? '');
                        $dhStatus = strtolower($pocRow['dept_head_status'] ?? '');
                        if ($pocStatus === 'rejected' || $dhStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        } elseif ($pocStatus === 'pending' && $dhStatus === 'pending') {
                            $row['approve_role'] = 'dept_head';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Dept Head';
                            $pendingForText = !empty($pocRow['dh_boss_name']) ? 'Dept Head (' . $pocRow['dh_boss_name'] . ')' : 'Department Head';
                        } elseif (in_array($pocStatus, ['submitted', 'pending', ''])) {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head (Role 24)';
                        } elseif ($pocStatus === 'qc_approved') {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management Approval';
                        } elseif ($pocStatus === 'management_approved' || $pocStatus === 'approved') {
                            $row['approve_role'] = 'accounts';
                            $row['status'] = 'approved';
                            $payoutStatus = 'Pending Accounts Payout';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($pocStatus === 'paid') {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) (!empty($pocRow['payment_amount']) ? $pocRow['payment_amount'] : (!empty($pocRow['approved_amount']) ? $pocRow['approved_amount'] : $pocRow['request_amount']));
                            $payoutDate = $pocRow['payment_at'] ?? $pocRow['updated_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = !empty($pocRow['payer_name']) ? 'Paid by ' . $pocRow['payer_name'] : 'Settled';
                        }

                        if ($pocRow['status'] === 'paid') {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) (!empty($pocRow['payment_amount']) ? $pocRow['payment_amount'] : (!empty($pocRow['approved_amount']) ? $pocRow['approved_amount'] : $pocRow['request_amount']));
                            $payoutDate = $pocRow['payment_at'] ?? $pocRow['updated_at'];
                        } else if ($row['status'] === 'approved' || $pocStatus === 'management_approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                        }
                    }
                }
            } else if ($sourceTable === 'fund_requisitions') {
                $frSql = "SELECT status, amount, paid_amount, paid_at, paid_by FROM fund_requisitions WHERE id = ? LIMIT 1";
                $frStmt = $this->db->prepare($frSql);
                if ($frStmt) {
                    $frStmt->bind_param("i", $sourceId);
                    $frStmt->execute();
                    $frRow = $frStmt->get_result()->fetch_assoc();
                    $frStmt->close();
                    if ($frRow) {
                        $frStatus = strtolower($frRow['status'] ?? '');
                        if ($frStatus === 'pending_dept') {
                            $row['approve_role'] = 'dept_head';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Dept Head';
                            $pendingForText = 'Department Head / Reporting Boss';
                        } elseif ($frStatus === 'pending_qc') {
                            $row['approve_role'] = 'qc';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending QC & Audit';
                            $pendingForText = 'QC & Internal Audit Head (Role 24)';
                        } elseif ($frStatus === 'pending') {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management / Operation Head';
                        } elseif ($frStatus === 'approved') {
                            $row['approve_role'] = 'accounts';
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($frStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if ($frRow['status'] === 'paid' || $frRow['status'] === 'partially_paid' || !empty($frRow['paid_by'])) {
                            $payoutStatus = ($frRow['status'] === 'partially_paid') ? 'Partially Paid' : 'Paid & Settled';
                            $payoutAmount = !empty($frRow['paid_amount']) ? (float)$frRow['paid_amount'] : (float)$frRow['amount'];
                            $payoutDate = $frRow['paid_at'];
                            $pendingStageLabel = ($frRow['status'] === 'partially_paid') ? 'Partially Paid' : 'Paid & Settled';
                            $pendingForText = ($frRow['status'] === 'partially_paid') ? 'Accounts & Finance' : 'Settled';
                        } else if ($frRow['status'] === 'approved' || $frRow['status'] === 'partially_approved' || $row['status'] === 'approved' || $row['status'] === 'partial') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        }
                    }
                }
            } else if ($sourceTable === 'lead_payments') {
                $lpSql = "SELECT booking_id FROM lead_payments WHERE id = ? LIMIT 1";
                $lpStmt = $this->db->prepare($lpSql);
                if ($lpStmt) {
                    $lpStmt->bind_param("i", $sourceId);
                    $lpStmt->execute();
                    $lpRow = $lpStmt->get_result()->fetch_assoc();
                    $lpStmt->close();
                    if ($lpRow && !empty($lpRow['booking_id'])) {
                        $row['booking_id'] = $lpRow['booking_id'];
                    }
                }
                if ($row['status'] === 'approved') {
                    $payoutStatus = 'Approved';
                    $pendingStageLabel = 'Approved from Management';
                    $pendingForText = 'Management Approved';
                }
            } else if ($sourceTable === 'project_extra_assets') {
                $peaSql = "SELECT payment_status, paid_amount, paid_at, mgmt_status, overall_status FROM project_extra_assets WHERE id = ? LIMIT 1";
                $peaStmt = $this->db->prepare($peaSql);
                if ($peaStmt) {
                    $peaStmt->bind_param("i", $sourceId);
                    $peaStmt->execute();
                    $peaRow = $peaStmt->get_result()->fetch_assoc();
                    $peaStmt->close();
                    if ($peaRow) {
                        $peaMgmtStatus = strtolower($peaRow['mgmt_status'] ?? '');
                        $peaOverall = strtolower($peaRow['overall_status'] ?? '');
                        $peaPayStatus = strtolower($peaRow['payment_status'] ?? '');
                        if ($peaPayStatus === 'paid') {
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif ($peaMgmtStatus === 'approved') {
                            $row['approve_role'] = 'accounts';
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($peaMgmtStatus === 'rejected' || $peaOverall === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        } else {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management / Operation Head';
                        }

                        if ($peaRow['payment_status'] === 'paid') {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float) (!empty($peaRow['paid_amount']) ? $peaRow['paid_amount'] : $row['request_amount']);
                            $payoutDate = $peaRow['paid_at'];
                        } else if ($row['status'] === 'approved' || $peaRow['mgmt_status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                        }
                    }
                }
            } else if ($sourceTable === 'contractor_bill_requests') {
                $cbSql = "SELECT status, IFNULL(approved_amount, bill_amount) AS app_amt, updated_at FROM contractor_bill_requests WHERE id = ? LIMIT 1";
                $cbStmt = $this->db->prepare($cbSql);
                if ($cbStmt) {
                    $cbStmt->bind_param("i", $sourceId);
                    $cbStmt->execute();
                    $cbRow = $cbStmt->get_result()->fetch_assoc();
                    $cbStmt->close();
                    if ($cbRow) {
                        $cbStatus = strtolower($cbRow['status'] ?? '');
                        if (in_array($cbStatus, ['pending', 'submitted', 'verified'])) {
                            $row['approve_role'] = 'management';
                            $row['status'] = 'pending';
                            $pendingStageLabel = 'Pending Management';
                            $pendingForText = 'Management / Operation Head';
                        } elseif ($cbStatus === 'approved') {
                            $row['approve_role'] = 'accounts';
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } elseif (in_array($cbStatus, ['paid', 'fully_paid'])) {
                            $row['status'] = 'approved';
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } elseif ($cbStatus === 'partially_paid') {
                            $row['status'] = 'partially_paid';
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                        } elseif ($cbStatus === 'rejected') {
                            $row['status'] = 'rejected';
                            $pendingStageLabel = 'Rejected';
                            $pendingForText = 'Rejected';
                        }

                        if (in_array(strtolower($cbRow['status']), ['paid', 'fully_paid'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$cbRow['app_amt'];
                            $payoutDate = $cbRow['updated_at'];
                        } else if (strtolower($cbRow['status']) === 'partially_paid') {
                            $payoutStatus = 'Partially Paid';
                            $payoutAmount = (float)$cbRow['app_amt'];
                            $payoutDate = $cbRow['updated_at'];
                        } else if (in_array(strtolower($cbRow['status']), ['approved']) || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float)$cbRow['app_amt'];
                        }
                    }
                }
            } else if ($sourceTable === 'cashout_requests') {
                $coSql = "SELECT status, IFNULL(approved_amount, amount) AS app_amt, updated_at FROM cashout_requests WHERE id = ? LIMIT 1";
                $coStmt = $this->db->prepare($coSql);
                if ($coStmt) {
                    $coStmt->bind_param("i", $sourceId);
                    $coStmt->execute();
                    $coRow = $coStmt->get_result()->fetch_assoc();
                    $coStmt->close();
                    if ($coRow) {
                        $coStatus = strtolower($coRow['status'] ?? '');
                        if (in_array($coStatus, ['fully_paid', 'paid'])) {
                            $row['status'] = 'approved';
                            $row['approve_role'] = 'completed';
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$coRow['app_amt'];
                            $payoutDate = $coRow['updated_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } else if ($coStatus === 'partially_paid') {
                            $payoutStatus = 'Partially Paid';
                            $payoutAmount = (float)$coRow['app_amt'];
                            $payoutDate = $coRow['updated_at'];
                            $pendingStageLabel = 'Partially Paid';
                            $pendingForText = 'Accounts & Finance';
                        } else if ($coStatus === 'approved' || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float)$coRow['app_amt'];
                            $row['approve_role'] = 'accounts';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        }
                    }
                }
            } else if ($sourceTable === 'advance_salaries') {
                $asSql = "SELECT status, amount, updated_at FROM advance_salaries WHERE id = ? LIMIT 1";
                $asStmt = $this->db->prepare($asSql);
                if ($asStmt) {
                    $asStmt->bind_param("i", $sourceId);
                    $asStmt->execute();
                    $asRow = $asStmt->get_result()->fetch_assoc();
                    $asStmt->close();
                    if ($asRow) {
                        $asStatus = strtolower($asRow['status'] ?? '');
                        if (in_array($asStatus, ['paid', 'deducted'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$asRow['amount'];
                            $payoutDate = $asRow['updated_at'];
                            $pendingStageLabel = 'Paid & Settled';
                            $pendingForText = 'Settled';
                        } else if (in_array($asStatus, ['approved', 'pending_accounts']) || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                            $payoutAmount = (float)$asRow['amount'];
                            $row['approve_role'] = 'accounts';
                            $pendingStageLabel = 'Pending Accounts Payout';
                            $pendingForText = 'Accounts & Finance';
                        } else if ($asStatus === 'pending_hr') {
                            $row['approve_role'] = 'hr_head';
                            $pendingStageLabel = 'Pending HR Approval';
                            $pendingForText = 'HR Head';
                        }
                    }
                }
            } else if ($row['status'] === 'approved' && $sourceTable !== 'lead_payments') {
                $payoutStatus = 'Pending Accounts Payout';
                $pendingStageLabel = 'Pending Accounts Payout';
                $pendingForText = 'Accounts & Finance';
            } else if ($row['status'] === 'approved') {
                $payoutStatus = 'Approved';
                $pendingStageLabel = 'Approved from Management';
                $pendingForText = 'Approved';
            } elseif ($row['status'] === 'rejected') {
                $pendingStageLabel = 'Rejected';
                $pendingForText = 'Rejected';
            }

            $row['payout_status'] = $payoutStatus;
            $row['payout_amount'] = $payoutAmount;
            $row['payout_date'] = $payoutDate;
            $row['pending_stage_label'] = $pendingStageLabel;
            $row['pending_for_text'] = $pendingForText;

            $list[] = $row;
        }

        if (isset($stmt) && $stmt) {
            $stmt->close();
        }

        // --- Role-based Visibility Filtering ("jar jeita pending shudu she oigulo dekhbe") ---
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $sessionRoleName = strtolower(trim($_SESSION['user']['role_name'] ?? ''));

        // Full Management: Super Admin (3), Admin (1), Chief Officer (33)
        $isFullManagement = in_array($userRoleId, [1, 3, 33], true) || in_array($sessionRoleName, ['super admin', 'admin', 'management', 'chief officer'], true);

        // Site Operator / Admin & Operation Head: Role 20, Role 32
        $isRole20 = in_array($userRoleId, [20, 32], true) || strpos($sessionRoleName, 'operation head') !== false || strpos($sessionRoleName, 'admin & operation') !== false;

        // Accounts: Role 6 (Accounts), Role 30 (Accounts Head)
        $isAccounts = in_array($userRoleId, [6, 30], true) || strpos($sessionRoleName, 'account') !== false;

        // HR: Role 4
        $isHR = ($userRoleId === 4) || strpos($sessionRoleName, 'hr') !== false;

        // QC & Internal Audit Head: Role 13, 24, 25
        $isQC = in_array($userRoleId, [13, 24, 25], true) || strpos($sessionRoleName, 'qc') !== false || strpos($sessionRoleName, 'audit') !== false;

        // Purchase Lead: Role 17, 23, 31
        $isPurchase = in_array($userRoleId, [17, 23, 31], true) || strpos($sessionRoleName, 'purchase') !== false;

        // Dept Head / Reporting Boss: Role 8, 9, 10 or designation head/manager
        $isDeptHead = in_array($userRoleId, [8, 9, 10, 11, 12, 14, 15, 16, 18, 19], true) || strpos($sessionRoleName, 'head') !== false || strpos($sessionRoleName, 'manager') !== false;

        $isActionableByCurrentUser = function($item) use ($isFullManagement, $isRole20, $isAccounts, $isHR, $isQC, $isPurchase, $isDeptHead, $userId) {
            $status = strtolower($item['status'] ?? '');
            $isPending = in_array($status, ['pending', 'partially_approved', '']);
            $approveRole = strtolower($item['approve_role'] ?? 'management');
            if (empty($approveRole)) {
                $approveRole = 'management';
            }
            $payoutStatus = $item['payout_status'] ?? '';
            $amount = (float)($item['request_amount'] ?? 0);

            // 1. Accounts: Only items approved and awaiting Accounts payout/disbursement
            if ($isAccounts) {
                if ($payoutStatus === 'Paid & Settled' || $status === 'paid' || $status === 'rejected') {
                    return false;
                }
                if (in_array($payoutStatus, ['Pending Accounts Payout', 'Partially Paid'])) {
                    return true;
                }
                if ($approveRole === 'accounts' && in_array($status, ['pending', 'approved', 'partially_approved'])) {
                    return true;
                }
                return false;
            }

            // 2. Role 20 (DGM / Operation Head): Only items pending Management approval AND amount <= 100,000!
            // Items waiting on QC, Dept Head, or >100k are NOT actionable for Role 20!
            if ($isRole20) {
                return ($isPending && in_array($approveRole, ['management', '']) && $amount <= 100000.01);
            }

            // 3. Full Management (Super Admin 3, Admin 1, Chief Officer 33):
            // Only items pending Management approval!
            // Items waiting on QC or Dept Head are NOT actionable for Management!
            if ($isFullManagement) {
                return ($isPending && in_array($approveRole, ['management', '']));
            }

            // 4. QC & Internal Audit Head (Role 24, 13, 25):
            if ($isQC) {
                return ($isPending && in_array($approveRole, ['qc', 'qc_head']));
            }

            // 5. Purchase Lead (Role 17, 23, 31):
            if ($isPurchase) {
                return ($isPending && in_array($approveRole, ['purchase team leader', 'purchase', 'purchase_lead']));
            }

            // 6. HR (Role 4):
            if ($isHR) {
                return ($isPending && in_array($approveRole, ['hr', 'hr_head']));
            }

            // 7. Dept Head / Reporting Boss:
            if ($isDeptHead && $isPending && ($approveRole === 'dept_head' || $approveRole === 'dept_head_' . $userId)) {
                return true;
            }

            return false;
        };

        $statusFilter = strtolower(trim($filters['status'] ?? ''));

        // Visibility Filtering:
        // Default (or statusFilter = 'pending' or empty):
        // Show ONLY what is actionable pending for the logged-in user! Other roles' pending lists and already approved/paid records are hidden!
        if (empty($statusFilter) || $statusFilter === 'pending' || $statusFilter === 'my_pending') {
            $filteredList = [];
            foreach ($list as $item) {
                if ($isActionableByCurrentUser($item)) {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        } elseif ($statusFilter === 'approved') {
            $filteredList = [];
            foreach ($list as $item) {
                $st = strtolower($item['status'] ?? '');
                $pst = $item['payout_status'] ?? '';
                if ($st === 'approved' || $st === 'paid' || in_array($pst, ['Paid & Settled', 'Approved', 'Partially Paid'])) {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        } elseif ($statusFilter === 'rejected') {
            $filteredList = [];
            foreach ($list as $item) {
                if (strtolower($item['status'] ?? '') === 'rejected') {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        } elseif ($statusFilter === 'all_pending') {
            // Audit view for Management: All pending requests across all departments
            $filteredList = [];
            foreach ($list as $item) {
                $st = strtolower($item['status'] ?? '');
                $pst = $item['payout_status'] ?? '';
                if (in_array($st, ['pending', 'partially_approved', '']) || in_array($pst, ['Pending Accounts Payout', 'Partially Paid'])) {
                    $filteredList[] = $item;
                }
            }
            $list = $filteredList;
        }
        // If $statusFilter === 'all', keep all items for complete company audit

        // Sort: Newest requests first (id DESC)
        usort($list, function($a, $b) {
            return ((int)$b['id']) <=> ((int)$a['id']);
        });

        return $list;

        return $list;
    }

    public function syncMissingFundRequisitions()
    {
        // 1. Insert missing requisitions
        $sql = "
            SELECT fr.*, d.name AS dept_name
            FROM fund_requisitions fr
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'fund_requisitions' AND ar.source_id = fr.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId = (int) $row['id'];
                $deptName = !empty($row['dept_name']) ? $row['dept_name'] : 'General';
                $requestType = $row['requisition_type'];
                $title = "Fund Requisition: " . (mb_strlen($row['purpose']) > 80 ? mb_substr($row['purpose'], 0, 80) . '...' : $row['purpose']);
                $amount = (float) $row['amount'];
                $requestedBy = (int) $row['requested_by'];
                
                $frStatus = $row['status'];
                $approveRole = 'management';
                if ($frStatus === 'pending_dept') {
                    $approveRole = 'dept_head';
                    $status = 'pending';
                } elseif ($frStatus === 'pending_qc') {
                    $approveRole = 'qc';
                    $status = 'pending';
                } elseif ($frStatus === 'pending') {
                    $approveRole = 'management';
                    $status = 'pending';
                } elseif ($frStatus === 'approved') {
                    $approveRole = 'accounts';
                    $status = 'approved';
                } elseif (in_array($frStatus, ['paid', 'partially_paid'])) {
                    $approveRole = 'accounts';
                    $status = ($frStatus === 'paid') ? 'approved' : 'partially_paid';
                } elseif ($frStatus === 'rejected') {
                    $approveRole = 'management';
                    $status = 'rejected';
                } else {
                    $approveRole = 'management';
                    $status = 'pending';
                }

                $approvedBy = !empty($row['approved_by']) ? (int) $row['approved_by'] : null;
                $approvedAt = !empty($row['approved_at']) ? $row['approved_at'] : null;
                $approvalNote = !empty($row['approval_note']) ? $row['approval_note'] : null;
                $metaData = json_encode(['purpose' => $row['purpose'], 'category' => $row['category']]);
                $createdAt = $row['created_at'];

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'fund_requisitions', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $amount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing fund_requisitions
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN fund_requisitions fr ON (ar.source_table = 'fund_requisitions' AND ar.source_id = fr.id)
            SET ar.approve_role = CASE 
                    WHEN fr.status = 'pending_dept' THEN 'dept_head'
                    WHEN fr.status = 'pending_qc' THEN 'qc'
                    WHEN fr.status = 'pending' THEN 'management'
                    WHEN fr.status = 'approved' THEN 'accounts'
                    WHEN fr.status IN ('paid', 'partially_paid') THEN 'accounts'
                    WHEN fr.status = 'rejected' THEN 'management'
                    ELSE ar.approve_role 
                END,
                ar.status = CASE 
                    WHEN fr.status IN ('pending_dept', 'pending_qc', 'pending') THEN 'pending'
                    WHEN fr.status = 'paid' THEN 'approved'
                    WHEN fr.status = 'partially_paid' THEN 'partially_paid'
                    WHEN fr.status = 'rejected' THEN 'rejected'
                    ELSE 'pending'
                END,
                ar.approved_by = fr.approved_by,
                ar.approved_at = fr.approved_at,
                ar.approval_note = fr.approval_note,
                ar.request_amount = fr.amount,
                ar.approved_amount = IFNULL(fr.approved_amount, fr.amount)
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingOfficeProcurements()
    {
        // 1. Insert missing office procurement requests into approval_requests
        $sql = "
            SELECT pea.*, d.name AS dept_name
            FROM project_extra_assets pea
            LEFT JOIN departments d ON pea.department_id = d.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'project_extra_assets' AND ar.source_id = pea.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId = (int) $row['id'];
                $deptName = !empty($row['dept_name']) ? $row['dept_name'] : 'Procurement';
                $requestType = 'Office Procurement';
                $title = "Office Procurement: " . $row['item_name'] . " (Qty: " . $row['quantity'] . ")";
                $amount = (float) $row['total_price'];
                $requestedBy = (int) $row['requested_by'];
                
                if ($row['mgmt_status'] === 'approved') {
                    $status = 'approved';
                    $approvedAmount = $amount;
                    $approvedBy = !empty($row['mgmt_by']) ? (int)$row['mgmt_by'] : null;
                    $approvedAt = !empty($row['mgmt_at']) ? $row['mgmt_at'] : null;
                } elseif ($row['mgmt_status'] === 'rejected' || $row['overall_status'] === 'rejected') {
                    $status = 'rejected';
                    $approvedAmount = null;
                    $approvedBy = !empty($row['mgmt_by']) ? (int)$row['mgmt_by'] : null;
                    $approvedAt = !empty($row['mgmt_at']) ? $row['mgmt_at'] : null;
                } else {
                    $status = 'pending';
                    $approvedAmount = null;
                    $approvedBy = null;
                    $approvedAt = null;
                }

                $approvalNote = !empty($row['mgmt_note']) ? $row['mgmt_note'] : null;
                $metaData = json_encode([
                    'item_name' => $row['item_name'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'justification' => $row['justification']
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'project_extra_assets', ?, ?, ?, ?, 'management', ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddisissss", $deptName, $requestType, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing project_extra_assets
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN project_extra_assets pea ON (ar.source_table = 'project_extra_assets' AND ar.source_id = pea.id)
            SET ar.status = CASE 
                    WHEN pea.mgmt_status = 'approved' THEN 'approved' 
                    WHEN pea.mgmt_status = 'rejected' OR pea.overall_status = 'rejected' THEN 'rejected' 
                    ELSE 'pending' 
                END,
                ar.approved_by = pea.mgmt_by,
                ar.approved_at = pea.mgmt_at,
                ar.approval_note = pea.mgmt_note,
                ar.request_amount = pea.total_price,
                ar.approved_amount = CASE WHEN pea.mgmt_status = 'approved' THEN pea.total_price ELSE ar.approved_amount END
            WHERE ar.status != CASE 
                    WHEN pea.mgmt_status = 'approved' THEN 'approved' 
                    WHEN pea.mgmt_status = 'rejected' OR pea.overall_status = 'rejected' THEN 'rejected' 
                    ELSE 'pending' 
                END
               OR (ar.approved_by IS NULL AND pea.mgmt_by IS NOT NULL)
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingLaborRequisitions()
    {
        // 1. Insert missing labor bills (only when work_completed or beyond, so initial pending planning is not flooded)
        $sql = "
            SELECT lr.*, p.name AS project_name
            FROM con_labor_requisitions lr
            LEFT JOIN projects p ON lr.project_id = p.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'con_labor_requisitions' AND ar.source_id = lr.id)
            WHERE ar.id IS NULL
              AND lr.status IN ('work_completed', 'payment_requested', 'qc_approved', 'pm_payment_approved', 'management_approved', 'partially_paid', 'paid', 'labor_disbursed', 'rejected')
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId    = (int) $row['id'];
                $deptName    = 'Construction';
                $requestType = 'Labor Bill by SE';
                $projectName = !empty($row['project_name']) ? $row['project_name'] : 'Project #' . $row['project_id'];
                $title       = "Labor Bill — " . $row['requisition_no'] . " | " . $projectName . " (" . $row['labor_count'] . " " . $row['labor_type'] . " Labors)";
                $amount      = !empty($row['pm_verified_amount']) ? (float)$row['pm_verified_amount'] : (!empty($row['qc_approved_amount']) ? (float)$row['qc_approved_amount'] : (float)$row['total_amount']);
                $requestedBy = (int) $row['requested_by'];
                
                $lrStatus = $row['status'];
                $approveRole = 'management';
                if ($lrStatus === 'pending') {
                    $approveRole = 'pm';
                    $status = 'pending';
                } elseif ($lrStatus === 'payment_requested') {
                    $approveRole = 'qc';
                    $status = 'pending';
                } elseif (in_array($lrStatus, ['qc_approved', 'pm_payment_approved'])) {
                    $approveRole = 'management';
                    $status = 'pending';
                } elseif (in_array($lrStatus, ['management_approved', 'paid', 'labor_disbursed'])) {
                    $approveRole = 'accounts';
                    $status = 'approved';
                } elseif ($lrStatus === 'partially_paid') {
                    $approveRole = 'accounts';
                    $status = 'partially_paid';
                } elseif ($lrStatus === 'rejected') {
                    $approveRole = 'management';
                    $status = 'rejected';
                } else {
                    $approveRole = 'management';
                    $status = 'pending';
                }

                $approvedAmount = in_array($status, ['approved', 'partially_paid']) ? (!empty($row['approved_amount']) ? (float)$row['approved_amount'] : $amount) : null;
                $approvedBy = !empty($row['management_approved_by']) ? (int)$row['management_approved_by'] : null;
                $approvedAt = !empty($row['management_approved_at']) ? $row['management_approved_at'] : null;

                $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                $metaData = json_encode([
                    'requisition_no' => $row['requisition_no'],
                    'labor_count'    => $row['labor_count'],
                    'labor_type'     => $row['labor_type'],
                    'working_days'   => $row['working_days'],
                    'per_labor_rate' => $row['per_labor_rate'],
                    'purpose'        => $row['purpose'],
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'con_labor_requisitions', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing con_labor_requisitions in approval_requests
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN con_labor_requisitions lr ON (ar.source_table = 'con_labor_requisitions' AND ar.source_id = lr.id)
            SET ar.approve_role = CASE 
                    WHEN lr.status = 'pending' THEN 'pm'
                    WHEN lr.status = 'payment_requested' THEN 'qc'
                    WHEN lr.status IN ('qc_approved', 'pm_payment_approved') THEN 'management'
                    WHEN lr.status IN ('management_approved', 'paid', 'labor_disbursed', 'partially_paid') THEN 'accounts'
                    ELSE 'management'
                END,
                ar.status = CASE 
                    WHEN lr.status IN ('management_approved', 'paid', 'labor_disbursed') THEN 'approved' 
                    WHEN lr.status = 'partially_paid' THEN 'partially_paid'
                    WHEN lr.status = 'rejected' THEN 'rejected' 
                    ELSE 'pending' 
                END,
                ar.approved_by = lr.management_approved_by,
                ar.approved_at = lr.management_approved_at,
                ar.approval_note = lr.management_note,
                ar.request_amount = COALESCE(lr.pm_verified_amount, lr.qc_approved_amount, lr.total_amount),
                ar.approved_amount = CASE 
                    WHEN lr.status IN ('management_approved', 'partially_paid', 'paid', 'labor_disbursed') THEN COALESCE(lr.approved_amount, lr.total_amount) 
                    ELSE ar.approved_amount 
                END
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingProjectOtherCosts()
    {
        // 1. Insert missing project other costs into approval_requests
        $sql = "
            SELECT poc.*, p.name AS project_name, e.reporting_user_id
            FROM project_other_costs poc
            LEFT JOIN projects p ON poc.project_id = p.id
            LEFT JOIN employees e ON (e.user_id = poc.created_by_id AND e.deleted_at IS NULL)
            LEFT JOIN approval_requests ar ON (ar.source_table = 'project_other_costs' AND ar.source_id = poc.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId    = (int) $row['id'];
                $deptName    = 'Construction';
                $requestType = !empty($row['rcost_type']) ? $row['rcost_type'] : 'electricity_bill';
                $purpose     = !empty($row['purpose']) ? $row['purpose'] : ($row['project_name'] ?? 'Other Cost');
                $title       = "Project Other Cost: " . (mb_strlen($purpose) > 80 ? mb_substr($purpose, 0, 80) . '...' : $purpose);
                $amount      = (float) $row['request_amount'];
                $requestedBy = (int) $row['created_by_id'];

                $pocStatus = strtolower($row['status'] ?? '');
                $dhStatus  = strtolower($row['dept_head_status'] ?? '');

                $status = 'pending';
                $approveRole = 'qc';
                $approvedAmount = null;
                $approvedBy = null;
                $approvedAt = null;
                $approvalNote = null;

                if ($pocStatus === 'rejected' || $dhStatus === 'rejected') {
                    $status = 'rejected';
                    $approveRole = 'rejected';
                    $approvedBy = !empty($row['dept_head_approved_by']) ? (int)$row['dept_head_approved_by'] : (!empty($row['qc_audit_approved_id']) ? (int)$row['qc_audit_approved_id'] : (!empty($row['management_apporved_by_id']) ? (int)$row['management_apporved_by_id'] : null));
                    $approvedAt = !empty($row['dept_head_approved_at']) ? $row['dept_head_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['dept_head_approval_note']) ? $row['dept_head_approval_note'] : (!empty($row['qc_audit_note']) ? $row['qc_audit_note'] : (!empty($row['management_apporved_note']) ? $row['management_apporved_note'] : null));
                } elseif ($pocStatus === 'pending') {
                    if ($dhStatus === 'pending') {
                        $status = 'pending';
                        $approveRole = !empty($row['reporting_user_id']) ? 'dept_head_' . $row['reporting_user_id'] : 'dept_head';
                    } else {
                        $status = 'pending';
                        $approveRole = 'qc';
                        if ($dhStatus === 'approved') {
                            $approvedBy = !empty($row['dept_head_approved_by']) ? (int)$row['dept_head_approved_by'] : null;
                            $approvedAt = !empty($row['dept_head_approved_at']) ? $row['dept_head_approved_at'] : null;
                            $approvalNote = !empty($row['dept_head_approval_note']) ? $row['dept_head_approval_note'] : null;
                        }
                    }
                } elseif ($pocStatus === 'qc_approved') {
                    $status = 'pending';
                    $approveRole = 'management';
                    $approvedBy = !empty($row['qc_audit_approved_id']) ? (int)$row['qc_audit_approved_id'] : null;
                    $approvedAt = !empty($row['qc_audit_approved_at']) ? $row['qc_audit_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['qc_audit_note']) ? $row['qc_audit_note'] : null;
                } elseif (in_array($pocStatus, ['management_approved', 'approved'])) {
                    $status = 'approved';
                    $approveRole = 'accounts';
                    $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : $amount;
                    $approvedBy = !empty($row['management_apporved_by_id']) ? (int)$row['management_apporved_by_id'] : null;
                    $approvedAt = !empty($row['management_apporved_at']) ? $row['management_apporved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['management_apporved_note']) ? $row['management_apporved_note'] : null;
                } elseif ($pocStatus === 'paid') {
                    $status = 'approved';
                    $approveRole = 'completed';
                    $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : (!empty($row['payment_amount']) ? (float)$row['payment_amount'] : $amount);
                    $approvedBy = !empty($row['management_apporved_by_id']) ? (int)$row['management_apporved_by_id'] : null;
                    $approvedAt = !empty($row['management_apporved_at']) ? $row['management_apporved_at'] : (!empty($row['payment_at']) ? $row['payment_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null));
                    $approvalNote = !empty($row['management_apporved_note']) ? $row['management_apporved_note'] : null;
                }

                $metaData = json_encode(['purpose' => $row['purpose'], 'project_id' => $row['project_id'], 'project_name' => $row['project_name']]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'project_other_costs', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync status for existing project_other_costs in approval_requests (heals empty status and stale data)
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN project_other_costs poc ON (ar.source_table = 'project_other_costs' AND ar.source_id = poc.id)
            SET ar.approve_role = CASE 
                    WHEN poc.status = 'rejected' OR poc.dept_head_status = 'rejected' THEN 'rejected'
                    WHEN poc.status = 'pending' AND poc.dept_head_status = 'pending' THEN 'dept_head'
                    WHEN poc.status = 'pending' THEN 'qc'
                    WHEN poc.status = 'qc_approved' THEN 'management'
                    WHEN poc.status = 'management_approved' THEN 'accounts'
                    WHEN poc.status = 'paid' THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE 
                    WHEN poc.status = 'rejected' OR poc.dept_head_status = 'rejected' THEN 'rejected'
                    WHEN poc.status IN ('management_approved', 'approved', 'paid') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approved_by = COALESCE(poc.management_apporved_by_id, poc.qc_audit_approved_id, poc.dept_head_approved_by, ar.approved_by),
                ar.approved_at = COALESCE(poc.management_apporved_at, poc.qc_audit_approved_at, poc.dept_head_approved_at, poc.updated_at, ar.approved_at),
                ar.approval_note = COALESCE(poc.management_apporved_note, poc.qc_audit_note, poc.dept_head_approval_note, ar.approval_note),
                ar.request_amount = poc.request_amount,
                ar.approved_amount = CASE 
                    WHEN poc.status IN ('management_approved', 'approved', 'paid') THEN COALESCE(poc.approved_amount, poc.payment_amount, poc.request_amount)
                    ELSE ar.approved_amount
                END
            WHERE ar.status = '' 
               OR ar.status IS NULL 
               OR (poc.status = 'paid' AND ar.approve_role != 'completed')
               OR (poc.status = 'management_approved' AND ar.status != 'approved')
               OR (poc.status = 'rejected' AND ar.status != 'rejected')
               OR (poc.status = 'qc_approved' AND ar.approve_role != 'management')
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingVendorPaymentRequests()
    {
        // 1. Insert missing vendor_payment_requests into approval_requests
        $sql = "
            SELECT vpr.*, p.name AS project_name, v.name AS vendor_name
            FROM vendor_payment_requests vpr
            LEFT JOIN projects p ON vpr.project_id = p.id
            LEFT JOIN vendors v ON vpr.vendor_id = v.id
            LEFT JOIN approval_requests ar ON (ar.source_table = 'vendor_payment_requests' AND ar.source_id = vpr.id)
            WHERE ar.id IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $sourceId    = (int) $row['id'];
                $deptName    = 'Construction';
                $requestType = 'vendor_payment';
                $vendorName  = !empty($row['vendor_name']) ? $row['vendor_name'] : 'Vendor';
                $projectName = !empty($row['project_name']) ? $row['project_name'] : 'Project';
                $title       = "Vendor Payment — {$vendorName} | {$projectName}";
                $netPayable  = (float) ($row['net_payable'] ?? $row['invoice_amount'] ?? 0);
                $qcAppAmt    = (isset($row['qc_approved_amount']) && (float)$row['qc_approved_amount'] > 0) ? (float)$row['qc_approved_amount'] : $netPayable;
                $requestedBy = (int) ($row['created_by'] ?? 1);

                $vStatus = strtolower(trim(str_replace('_', ' ', $row['status'] ?? '')));

                $status = 'pending';
                $approveRole = 'qc';
                $requestAmount = $netPayable;
                $approvedAmount = null;
                $approvedBy = null;
                $approvedAt = null;
                $approvalNote = null;

                if ($vStatus === 'rejected') {
                    $status = 'rejected';
                    $approveRole = 'rejected';
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                } elseif (in_array($vStatus, ['submitted', 'pending', 'draft', ''])) {
                    $status = 'pending';
                    $approveRole = 'qc';
                } elseif (in_array($vStatus, ['qc approved', 'qc_approved'])) {
                    $status = 'pending';
                    $approveRole = 'management';
                    $requestAmount = $qcAppAmt;
                    $approvedBy = !empty($row['qc_approved_by_id']) ? (int)$row['qc_approved_by_id'] : null;
                    $approvedAt = !empty($row['qc_approved_at']) ? $row['qc_approved_at'] : (!empty($row['updated_at']) ? $row['updated_at'] : null);
                    $approvalNote = !empty($row['qc_note']) ? $row['qc_note'] : null;
                } elseif (in_array($vStatus, ['approved', 'management approved', 'management_approved'])) {
                    $status = 'approved';
                    $approveRole = 'accounts';
                    $requestAmount = $qcAppAmt;
                    $approvedAmount = (isset($row['approved_amount']) && (float)$row['approved_amount'] > 0) ? (float)$row['approved_amount'] : $netPayable;
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                    $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                } elseif (in_array($vStatus, ['paid', 'completed', 'fully paid', 'fully_paid', 'partially paid', 'partially_paid'])) {
                    $status = 'approved';
                    $approveRole = 'completed';
                    $requestAmount = $netPayable;
                    $approvedAmount = (isset($row['approved_amount']) && (float)$row['approved_amount'] > 0) ? (float)$row['approved_amount'] : $netPayable;
                    $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
                    $approvedAt = !empty($row['updated_at']) ? $row['updated_at'] : null;
                    $approvalNote = !empty($row['management_note']) ? $row['management_note'] : null;
                }

                $metaData = json_encode([
                    'vendor_id'         => $row['vendor_id'],
                    'vendor_name'       => $vendorName,
                    'project_id'        => $row['project_id'],
                    'project_name'      => $projectName,
                    'purchase_order_id' => $row['purchase_order_id'],
                    'payment_type'      => $row['payment_type'] ?? 'Final',
                    'invoice_number'    => $row['invoice_number'] ?? null
                ]);
                $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

                $stmt = $this->db->prepare("
                    INSERT INTO approval_requests 
                    (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                    VALUES (?, ?, ?, 'vendor_payment_requests', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt) {
                    $stmt->bind_param("sssiddississss", $deptName, $requestType, $title, $sourceId, $requestAmount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        // 2. Sync and self-heal existing vendor_payment_requests in approval_requests
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN vendor_payment_requests vpr ON (ar.source_table = 'vendor_payment_requests' AND ar.source_id = vpr.id)
            SET 
                ar.approve_role = CASE
                    WHEN vpr.status = 'Rejected' THEN 'rejected'
                    WHEN vpr.status IN ('Submitted', 'Draft') THEN 'qc'
                    WHEN vpr.status = 'QC Approved' THEN 'management'
                    WHEN vpr.status = 'Approved' THEN 'accounts'
                    WHEN vpr.status IN ('Paid', 'Partially_Paid', 'Fully_Paid') THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN vpr.status = 'Rejected' THEN 'rejected'
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.request_amount = CASE
                    WHEN vpr.qc_approved_amount IS NOT NULL AND vpr.qc_approved_amount > 0 THEN vpr.qc_approved_amount
                    WHEN vpr.net_payable IS NOT NULL AND vpr.net_payable > 0 THEN vpr.net_payable
                    ELSE ar.request_amount
                END,
                ar.approved_amount = CASE
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN COALESCE(vpr.approved_amount, vpr.net_payable, ar.request_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_by = CASE
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN COALESCE(vpr.approved_by, ar.approved_by)
                    WHEN vpr.status = 'QC Approved' THEN COALESCE(vpr.qc_approved_by_id, ar.approved_by)
                    ELSE ar.approved_by
                END,
                ar.approved_at = CASE
                    WHEN vpr.status IN ('Approved', 'Paid', 'Partially_Paid', 'Fully_Paid') THEN COALESCE(vpr.updated_at, ar.approved_at)
                    WHEN vpr.status = 'QC Approved' THEN COALESCE(vpr.qc_approved_at, ar.approved_at)
                    ELSE ar.approved_at
                END,
                ar.approval_note = CASE
                    WHEN vpr.status = 'QC Approved' AND vpr.qc_note IS NOT NULL AND vpr.qc_note != '' THEN vpr.qc_note
                    WHEN vpr.status = 'Approved' AND vpr.management_note IS NOT NULL AND vpr.management_note != '' THEN vpr.management_note
                    ELSE ar.approval_note
                END
            WHERE 
                -- Items where QC has approved, but approval_request is still in 'purchase team leader' or 'qc'
                (vpr.status = 'QC Approved' AND ar.approve_role != 'management')
                -- Items where Management has approved, but approval_request is not in accounts / approved
                OR (vpr.status = 'Approved' AND (ar.approve_role != 'accounts' OR ar.status != 'approved'))
                -- Items paid
                OR (vpr.status IN ('Paid', 'Partially_Paid', 'Fully_Paid') AND ar.approve_role != 'completed')
                -- Items rejected
                OR (vpr.status = 'Rejected' AND ar.status != 'rejected')
                -- Items submitted but stuck in purchase team leader
                OR (vpr.status IN ('Submitted', 'Draft') AND ar.approve_role = 'purchase team leader')
                -- Empty status
                OR ar.status = '' OR ar.status IS NULL
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingAdvanceSalaries()
    {
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN advance_salaries as_tbl ON (ar.source_table = 'advance_salaries' AND ar.source_id = as_tbl.id)
            SET 
                ar.approve_role = CASE
                    WHEN as_tbl.status = 'rejected' THEN 'rejected'
                    WHEN as_tbl.status = 'pending_hr' THEN 'hr_head'
                    WHEN as_tbl.status = 'pending' THEN 'management'
                    WHEN as_tbl.status IN ('pending_accounts', 'approved') THEN 'accounts'
                    WHEN as_tbl.status IN ('paid', 'deducted') THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN as_tbl.status = 'rejected' THEN 'rejected'
                    WHEN as_tbl.status IN ('pending_accounts', 'approved', 'paid', 'deducted') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approved_amount = CASE
                    WHEN as_tbl.status IN ('pending_accounts', 'approved', 'paid', 'deducted') THEN COALESCE(ar.approved_amount, as_tbl.amount, ar.request_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_at = CASE
                    WHEN as_tbl.approved_date IS NOT NULL THEN as_tbl.approved_date
                    ELSE ar.approved_at
                END
            WHERE 
                (as_tbl.status IN ('pending_accounts', 'approved') AND (ar.approve_role != 'accounts' OR ar.status != 'approved'))
                OR (as_tbl.status IN ('paid', 'deducted') AND ar.approve_role != 'completed')
                OR (as_tbl.status = 'rejected' AND ar.status != 'rejected')
                OR (as_tbl.status = 'pending_hr' AND ar.approve_role != 'hr_head')
                OR (as_tbl.status = 'pending' AND ar.approve_role != 'management')
        ";
        @$this->db->query($syncSql);
    }

    public function syncMissingCashoutRequests()
    {
        $syncSql = "
            UPDATE approval_requests ar
            INNER JOIN cashout_requests cr ON (ar.source_table = 'cashout_requests' AND ar.source_id = cr.id)
            SET 
                ar.approve_role = CASE
                    WHEN cr.status = 'rejected' THEN 'rejected'
                    WHEN cr.status = 'pending' THEN 'management'
                    WHEN cr.status = 'approved' THEN 'accounts'
                    WHEN cr.status IN ('fully_paid', 'paid') THEN 'completed'
                    ELSE ar.approve_role
                END,
                ar.status = CASE
                    WHEN cr.status = 'rejected' THEN 'rejected'
                    WHEN cr.status IN ('approved', 'fully_paid', 'paid') THEN 'approved'
                    ELSE 'pending'
                END,
                ar.approved_amount = CASE
                    WHEN cr.status IN ('approved', 'fully_paid', 'paid') THEN COALESCE(cr.approved_amount, cr.amount, ar.request_amount)
                    ELSE ar.approved_amount
                END,
                ar.approved_by = CASE
                    WHEN cr.approved_by IS NOT NULL THEN cr.approved_by
                    ELSE ar.approved_by
                END,
                ar.approved_at = CASE
                    WHEN cr.approved_at IS NOT NULL THEN cr.approved_at
                    ELSE ar.approved_at
                END
            WHERE 
                (cr.status IN ('fully_paid', 'paid') AND (ar.approve_role != 'completed' OR ar.status != 'approved'))
                OR (cr.status = 'approved' AND (ar.approve_role != 'accounts' OR ar.status != 'approved'))
                OR (cr.status = 'rejected' AND ar.status != 'rejected')
                OR (cr.status = 'pending' AND (ar.approve_role != 'management' OR ar.status != 'pending'))
                OR ar.status = '' OR ar.status IS NULL
        ";
        @$this->db->query($syncSql);
    }
}
