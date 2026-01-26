<?php

class ApprovalRequest extends Model
{
    protected $table = 'approval_requests';

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table} 
            (department, request_type, title, source_table, source_id, request_amount, requested_by, approve_role, meta_data)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "ssssiidss",
            $data['department'],
            $data['request_type'],
            $data['title'],
            $data['source_table'],
            $data['source_id'],
            $data['request_amount'],
            $data['requested_by'],
            $data['approve_role'],
            $data['meta_data']
        );

        $stmt->execute();

        if ($stmt->error) {
            die("Execute failed: " . $stmt->error);
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        return $insertId;
    }

    // public function getPendingForRole($role, $limit = 5)
    // {
    //     $sql = "SELECT * FROM {$this->table} 
    //         WHERE status = ? AND approve_role = ? 
    //         ORDER BY created_at DESC 
    //         LIMIT ?";

    //     $stmt = $this->db->prepare($sql);
    //     if (!$stmt) {
    //         die("Prepare failed: " . $this->db->error);
    //     }

    //     $status = 'pending';
    //     $stmt->bind_param("ssi", $status, $role, $limit);
    //     $stmt->execute();

    //     $result = $stmt->get_result();
    //     $data = $result->fetch_all(MYSQLI_ASSOC);

    //     $stmt->close();
    //     return $data;
    // }
    // public function getPending($limit = 5)
    // {
    //     $sql = "SELECT * FROM {$this->table}
    //             WHERE status = 'pending'
    //             ORDER BY created_at DESC
    //             LIMIT ?";

    //     $stmt = $this->db->prepare($sql);
    //     $stmt->bind_param("i", $limit);
    //     $stmt->execute();
    //     $result = $stmt->get_result();
    //     return $result->fetch_all(MYSQLI_ASSOC);
    // }


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





    public function updateStatus($id, $status, $approvedAmount = null)
    {
        // 1. Update approval_requests
        $approvedBy = $_SESSION['user']['id'] ?? null;

        $sql = "UPDATE {$this->table}
            SET status = ?, approved_amount = ?, approved_by = ?, approved_at = NOW()
            WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('siii', $status, $approvedAmount, $approvedBy, $id);
        $stmt->execute();

        // 2. Get source info
        $request = $this->find($id);
        if ($request && !empty($request['source_table']) && !empty($request['source_id'])) {
            $sourceTable = $request['source_table'];
            $sourceId = $request['source_id'];

            $statusMap = [
                'approved' => 'approved',
                'partially_approved' => 'pending', // optional
                'rejected' => 'reject'
            ];
            $newStatus = $statusMap[$status] ?? 'pending';

            // 3. Update source table
            $sql2 = "UPDATE {$sourceTable} SET status = ? WHERE id = ?";
            $stmt2 = $this->db->prepare($sql2);
            $stmt2->bind_param('si', $newStatus, $sourceId);
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
