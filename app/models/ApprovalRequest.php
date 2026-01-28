<?php

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    protected $allowedTables = [
        'lead_bookings',
        'employee_leaves',
        'salary_requests'
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




    public function getPendingForRole(array $roles, $limit = 5)
    {
        // convert all roles to lowercase
        $roles = array_map('strtolower', $roles);

        // Prepare placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($roles), '?'));

        $sql = "SELECT * FROM {$this->table}
            WHERE status = 'pending'
            AND LOWER(approve_role) IN ($placeholders)
            ORDER BY created_at DESC
            LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(str_repeat('s', count($roles)), ...$roles);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }





    public function updateStatus($id, $status, $approvedAmount = null, $approved_days = null)
    {
        $approvedBy = $_SESSION['user']['id'] ?? null;

        /* ===============================
       1. Update approval_requests table
    =============================== */
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

        /* ===============================
       2. Fetch source info
    =============================== */
        $request = $this->find($id);
        if (!$request || empty($request['source_table']) || empty($request['source_id'])) {
            return false;
        }

        $sourceTable = $request['source_table'];
        $sourceId    = (int) $request['source_id'];

        /* ===============================
       3. Validate source table
    =============================== */
        if (!in_array($sourceTable, $this->allowedTables, true)) {
            throw new Exception("Invalid source table: {$sourceTable}");
        }

        /* ===============================
       4. Detect columns dynamically
    =============================== */
        $columns = [];
        $result = $this->db->query("SHOW COLUMNS FROM {$sourceTable}");
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }

        /* ===============================
       5. Status mapping
    =============================== */
        $sourceStatus = match ($status) {
            'partial', 'partially_approved' => 'partial',
            'approved' => 'approved',
            'rejected' => 'rejected',
            default => 'pending',
        };

        /* ===============================
       6. Dynamic update
    =============================== */
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

        /* ===============================
       7. Execute source update
    =============================== */
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
