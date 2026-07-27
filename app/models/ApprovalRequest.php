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
        
        // Phase 2 / Construction & Client refund tables
        'client_refund_requests',
        'operational_fund_requests',
        'con_work_orders',
        'vendor_payment_requests',
        'contractor_bill_requests',
        'lead_payments',
        'project_incentive_claims',
        'fund_requisitions'
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
                $details['lead'] = $this->getLeadById((int)$meta['lead_id']);
            }

            if (!empty($meta['flat_id'])) {
                $details['flat'] = $this->getFlatById((int)$meta['flat_id']);
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
                $details['employee'] = $this->getEmployeeById((int)$meta['employee_id']);
                $details['salary'] = $this->getSalaryDetails(
                    (int)$meta['employee_id'],
                    $meta['month'] ?? null
                );
            }
        }

        /* =====================
       HR → LEAVE
       ===================== */
        if ($approval['request_type'] === 'leave') {

            if (!empty($meta['user_id'])) {
                $details['employee'] = $this->getUserById((int)$meta['user_id']);
            }

            if (!empty($approval['source_id'])) {
                $leave = $this->getLeaveDetails((int)$approval['source_id']);

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
                (int)$approval['source_id']
            );

            if ($approval['request_type'] === 'Cash In Handover' && $approval['source_table'] === 'lead_payments') {
                $handoverToId = $details['account']['handover_to'] ?? null;
                if ($handoverToId) {
                    $employee = $this->getUserById((int)$handoverToId);
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
                        WHERE mr.id = " . (int)$approval['source_id'])
                ->fetch_assoc();
        }

        // Fetch requester info
        if (!empty($approval['requested_by'])) {
            $details['requester'] = $this->getUserById((int)$approval['requested_by']);
        }


        return $details;
    }

    public function getPendingForRole(array $roles, $limit = 5)
    {
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

        $limit = (int)$limit;

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
        $sourceId    = (int) $request['source_id'];

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
        }

        $updateFields = [];
        $params = [];
        $types  = '';

        if (in_array('claim_status', $columns)) {
            $updateFields[] = "claim_status = ?";
            $types .= 's';
            $params[] = $sourceStatus;
        } elseif (in_array('approval_status', $columns)) {
            $updateFields[] = "approval_status = ?";
            $types .= 's';
            $params[] = $sourceStatus;
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
        } elseif ($sourceTable === 'lead_payments' && in_array('handover_to', $columns)) { // Wait, handover_to is who it was requested to? Actually approved_by goes nowhere if there's no approved_by column. We can add approved_by if needed. Let's just leave handover_to alone here.
            // Do not override handover_to, it's the requested approver, not the actual one.
        }

        if ($sourceTable === 'lead_payments' && $status === 'rejected' && in_array('handover_rejection_reason', $columns)) {
            $updateFields[] = "handover_rejection_reason = ?";
            $types .= 's';
            $params[] = $approvalNote;
        }

        if (in_array('approved_at', $columns)) {
            $updateFields[] = "approved_at = NOW()";
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
            $stmt2->bind_param($types, ...$params);
            $stmt2->execute();
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

        // Check Accounts total disbursed across incentive payments, vendor payment requests & fund requisitions
        $sqlDisbursed = "SELECT 
                            (SELECT IFNULL(SUM(payment_amount), 0) FROM project_incentive_payments) + 
                            (SELECT IFNULL(SUM(approved_amount), 0) FROM vendor_payment_requests WHERE status = 'Approved') +
                            (SELECT IFNULL(SUM(amount), 0) FROM fund_requisitions WHERE status = 'paid') AS total_disbursed";
        $resD = $this->db->query($sqlDisbursed);
        $totalDisbursed = $resD ? (float)($resD->fetch_assoc()['total_disbursed'] ?? 0) : 0.00;

        return [
            'total_requests'  => (int)($row['total_requests'] ?? 0),
            'pending_count'   => (int)($row['pending_count'] ?? 0),
            'pending_amount'  => (float)($row['pending_amount'] ?? 0.00),
            'approved_count'  => (int)($row['approved_count'] ?? 0),
            'approved_amount' => (float)($row['approved_amount'] ?? 0.00),
            'rejected_count'  => (int)($row['rejected_count'] ?? 0),
            'total_disbursed' => $totalDisbursed
        ];
    }

    public function getAllApprovalHistory($filters = [])
    {
        $where = ["1=1"];
        $params = [];
        $types = "";

        if (!empty($filters['status'])) {
            $where[] = "ar.status = ?";
            $params[] = $filters['status'];
            $types .= "s";
        }

        if (!empty($filters['department'])) {
            $where[] = "(LOWER(ar.department) LIKE ? OR LOWER(d_req.name) LIKE ?)";
            $deptParam = '%' . strtolower($filters['department']) . '%';
            $params[] = $deptParam;
            $params[] = $deptParam;
            $types .= "ss";
        }

        if (!empty($filters['source_table'])) {
            $where[] = "ar.source_table = ?";
            $params[] = $filters['source_table'];
            $types .= "s";
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT 
                    ar.id,
                    ar.department,
                    ar.request_type,
                    ar.title,
                    ar.source_table,
                    ar.source_id,
                    IFNULL(ar.request_amount, 0.00) AS request_amount,
                    IFNULL(ar.approved_amount, ar.request_amount) AS approved_amount,
                    ar.status,
                    ar.created_at AS requested_at,
                    ar.approved_at,
                    ar.approval_note,
                    u_req.full_name AS requester_name,
                    IFNULL(e_req.employee_id, CONCAT('EMP-', u_req.id)) AS requester_code,
                    d_req.name AS requester_dept,
                    u_app.full_name AS approver_name,
                    u_app.email AS approver_email,
                    IFNULL(des_app.name, 'Management Executive') AS approver_designation
                FROM approval_requests ar
                LEFT JOIN users u_req ON ar.requested_by = u_req.id
                LEFT JOIN employees e_req ON u_req.id = e_req.user_id
                LEFT JOIN departments d_req ON e_req.department_id = d_req.id
                LEFT JOIN users u_app ON ar.approved_by = u_app.id
                LEFT JOIN employees e_app ON u_app.id = e_app.user_id
                LEFT JOIN designations des_app ON e_app.designation_id = des_app.id
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
            $sourceId = (int)$row['source_id'];

            // Payout status resolution
            $payoutStatus = 'N/A';
            $payoutAmount = 0.00;
            $payoutDate = null;

            if ($sourceTable === 'project_incentive_claims') {
                $paySql = "SELECT SUM(payment_amount) AS total_paid, MAX(payment_date) AS pay_date 
                           FROM project_incentive_payments WHERE claim_id = ?";
                $pStmt = $this->db->prepare($paySql);
                if ($pStmt) {
                    $pStmt->bind_param("i", $sourceId);
                    $pStmt->execute();
                    $pRow = $pStmt->get_result()->fetch_assoc();
                    $pStmt->close();
                    $totalPaid = (float)($pRow['total_paid'] ?? 0.00);
                    if ($totalPaid > 0) {
                        $payoutStatus = 'Paid & Settled';
                        $payoutAmount = $totalPaid;
                        $payoutDate = $pRow['pay_date'];
                    } else if ($row['status'] === 'approved') {
                        $payoutStatus = 'Pending Accounts Payout';
                    }
                }
            } else if ($sourceTable === 'vendor_payment_requests') {
                $vSql = "SELECT status, IFNULL(approved_amount, 0.00) AS app_amt, updated_at 
                         FROM vendor_payment_requests WHERE id = ? LIMIT 1";
                $vStmt = $this->db->prepare($vSql);
                if ($vStmt) {
                    $vStmt->bind_param("i", $sourceId);
                    $vStmt->execute();
                    $vRow = $vStmt->get_result()->fetch_assoc();
                    $vStmt->close();
                    if ($vRow) {
                        if (in_array(strtolower($vRow['status']), ['approved', 'paid', 'completed'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$vRow['app_amt'];
                            $payoutDate = $vRow['updated_at'];
                        } else if ($row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                        }
                    }
                }
            } else if ($sourceTable === 'fund_requisitions') {
                $frSql = "SELECT status, amount, paid_at, paid_by FROM fund_requisitions WHERE id = ? LIMIT 1";
                $frStmt = $this->db->prepare($frSql);
                if ($frStmt) {
                    $frStmt->bind_param("i", $sourceId);
                    $frStmt->execute();
                    $frRow = $frStmt->get_result()->fetch_assoc();
                    $frStmt->close();
                    if ($frRow) {
                        if ($frRow['status'] === 'paid' || !empty($frRow['paid_by'])) {
                            $payoutStatus = 'Paid & Settled';
                            $payoutAmount = (float)$frRow['amount'];
                            $payoutDate = $frRow['paid_at'];
                        } else if ($frRow['status'] === 'approved' || $row['status'] === 'approved') {
                            $payoutStatus = 'Pending Accounts Payout';
                        }
                    }
                }
            } else if ($row['status'] === 'approved') {
                $payoutStatus = 'Approved';
            }

            $row['payout_status'] = $payoutStatus;
            $row['payout_amount'] = $payoutAmount;
            $row['payout_date'] = $payoutDate;

            $list[] = $row;
        }

        if (isset($stmt) && $stmt) {
            $stmt->close();
        }

        return $list;
    }
}
