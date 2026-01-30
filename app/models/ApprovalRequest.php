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
        return $this->db
            ->query("SELECT id,flat_name, sqft FROM flats WHERE id = {$id}")
            ->fetch_assoc();
    }

    private function resolveApprovalDetails(array $approval)
    {
        $meta = $this->parseMetaData($approval['meta_data']);

        $details = [];

        // Lead info
        if (!empty($meta['lead_id'])) {
            $details['lead'] = $this->getLeadById((int)$meta['lead_id']);
        }

        // Flat info
        if (!empty($meta['flat_id'])) {
            $details['flat'] = $this->getFlatById((int)$meta['flat_id']);
        }

        // Employee info
        // if (!empty($meta['employee_id'])) {
        //     $details['employee'] = $this->getEmployeeById((int)$meta['employee_id']);
        // }

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
        return $this->db
            ->query("SELECT id,flat_name, sqft FROM flats WHERE id = {$id}")
            ->fetch_assoc();
    }

    private function resolveApprovalDetails(array $approval)
    {
        $meta = $this->parseMetaData($approval['meta_data']);

        $details = [];

        // Lead info
        if (!empty($meta['lead_id'])) {
            $details['lead'] = $this->getLeadById((int)$meta['lead_id']);
        }

        // Flat info
        if (!empty($meta['flat_id'])) {
            $details['flat'] = $this->getFlatById((int)$meta['flat_id']);
        }

        // Employee info
        // if (!empty($meta['employee_id'])) {
        //     $details['employee'] = $this->getEmployeeById((int)$meta['employee_id']);
        // }

        return $details;
    }

    public function getPendingForRole(array $roles, $limit = 5)
    {
        $roles = array_map('strtolower', $roles);

        if (empty($roles)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $limit = (int)$limit;

        $sql = "SELECT * FROM {$this->table}
            WHERE status = 'pending'
            AND LOWER(approve_role) IN ($placeholders)
            ORDER BY created_at DESC
            LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($roles)), ...$roles);
        $stmt->execute();

        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // 🔥 THIS PART WAS MISSING
        foreach ($rows as &$row) {
            $row['context'] = $this->resolveApprovalDetails($row);
        }

        return $rows;
    }


    public function updateStatus($id, $status, $approvedAmount = null, $approved_days = null){
        $approvedBy = $_SESSION['user']['id'] ?? null;


        $sql = "UPDATE {$this->table}
            SET 
                status = ?, 
                approved_amount = ?, 
                approved_days = ?, 
                approved_by = ?, 
                approved_at = NOW()
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'sdiii',
            $status,
            $approvedAmount,
            $approved_days,
            $approvedBy,
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
