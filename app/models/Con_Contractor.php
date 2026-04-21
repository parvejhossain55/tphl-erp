<?php

class Con_Contractor extends Model
{
    protected $table = 'contractors';

    public function getAll()
    {
        $sql = "SELECT oo.*, u.full_name AS created_by_name
                FROM {$this->table} oo
                LEFT JOIN users u ON u.id = oo.created_by
                ORDER BY oo.name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function getActiveContractors()
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status = 'active'
                  AND role IN ('Contractor','Subcontractor')
                ORDER BY name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $sql = "INSERT INTO {$this->table}
                (name, role, trade, phone, nid_number, address, daily_rate, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssssssdsi',
            $data['name'],
            $data['role'],
            $data['trade'],
            $data['phone'],
            $data['nid_number'],
            $data['address'],
            $data['daily_rate'],
            $data['status'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table}
                SET name=?, role=?, trade=?, phone=?, nid_number=?, address=?, daily_rate=?, status=?
                WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param(
            'ssssssdsi',
            $data['name'],
            $data['role'],
            $data['trade'],
            $data['phone'],
            $data['nid_number'],
            $data['address'],
            $data['daily_rate'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id=?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
