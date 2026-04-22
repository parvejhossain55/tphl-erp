<?php

class Con_ContractorBill extends Model
{
    protected $table = 'contractor_bill_requests';

    public function getAllWithRelations()
    {
        $sql = "SELECT cbr.*,
                       oo.name  AS operator_name,
                       oo.role  AS operator_role,
                       p.name   AS project_name,
                       u.full_name AS created_by_name,
                       v.full_name AS verified_by_name
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p  ON p.id  = cbr.project_id
                LEFT JOIN users u     ON u.id  = cbr.created_by
                LEFT JOIN users v     ON v.id  = cbr.verified_by
                ORDER BY cbr.created_at DESC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function findWithRelations($id)
    {
        $sql = "SELECT cbr.*,
                       oo.name  AS operator_name,
                       oo.phone AS operator_phone,
                       oo.trade AS operator_trade,
                       p.name   AS project_name,
                       u.full_name AS created_by_name,
                       v.full_name AS verified_by_name
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p  ON p.id  = cbr.project_id
                LEFT JOIN users u     ON u.id  = cbr.created_by
                LEFT JOIN users v     ON v.id  = cbr.verified_by
                WHERE cbr.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (project_id, operator_id, target_id, bill_date, work_description, bill_amount, attachment_path, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'iiissdss i',
            $data['project_id'],
            $data['operator_id'],
            $data['target_id'],
            $data['bill_date'],
            $data['work_description'],
            $data['bill_amount'],
            $data['attachment_path'],
            $data['status'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    /* updateStatus — called by verify / approve / pay actions */
    public function updateStatus($id, $data)
    {
        $fields = [];
        $types  = '';
        $params = [];

        foreach ($data as $col => $val) {
            if ($col === 'id') continue;
            $fields[] = "`$col` = ?";
            if (is_int($val))   { $types .= 'i'; }
            elseif (is_float($val)) { $types .= 'd'; }
            else                { $types .= 's'; }
            $params[] = $val;
        }

        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?";
        $types  .= 'i';
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    public function getByStatus($status)
    {
        $sql = "SELECT cbr.*, oo.name AS operator_name, p.name AS project_name
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p ON p.id = cbr.project_id
                WHERE cbr.status = ?
                ORDER BY cbr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $status);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getByTarget($targetId)
    {
        $sql = "SELECT cbr.*, oo.name AS operator_name, p.name AS project_name
                FROM {$this->table} cbr
                LEFT JOIN contractors oo ON oo.id = cbr.operator_id
                LEFT JOIN projects p ON p.id = cbr.project_id
                WHERE cbr.target_id = ?
                ORDER BY cbr.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
