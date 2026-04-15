<?php

class Con_Vendor extends Model
{
    protected $table = 'vendors';

    public function getAll()
    {
        $sql = "SELECT v.*, u.full_name AS created_by_name
                FROM {$this->table} v
                LEFT JOIN users u ON u.id = v.created_by
                ORDER BY v.name ASC";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
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
        $sql = "INSERT INTO {$this->table} (name, phone, email, address, rating, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssdsi',
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['rating'],
            $data['status'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET name=?, phone=?, email=?, address=?, status=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssssi',
            $data['name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }
}
