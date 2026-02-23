<?php

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $allowedTables = [
        'lead_bookings',
        'employee_leaves',
        'payslips',

        'salary_requests',
        'cashout_requests'
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


        /* =====================
       ACCOUNTS → CASH IN / OUT
       ===================== */
        if ($approval['department'] === 'Accounts') {

            $details['account'] = $this->getAccountDetails(
                $approval['source_table'],
                (int)$approval['source_id']
            );

            $details['meta'] = $meta; // purpose, payment_method etc
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

        $updateFields = [];
        $params = [];
        $types  = '';

        if (in_array('approval_status', $columns)) {
            $updateFields[] = "approval_status = ?";
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

        //    7. Execute source update 
        if (!empty($updateFields)) {
            $sql2 = "UPDATE {$sourceTable} SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $types .= 'i';
            $params[] = $sourceId;

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
}
