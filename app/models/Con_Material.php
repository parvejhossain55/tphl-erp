<?php

class Con_Material extends Model
{
    protected $table = 'con_materials';

    public function getAll()
    {
        $sql = "SELECT m.*, u.full_name AS created_by_name
                FROM {$this->table} m
                LEFT JOIN users u ON u.id = m.created_by
                ORDER BY m.name ASC";
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
        $sql = "INSERT INTO {$this->table} (name, unit, min_stock_level, created_by)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssdi',
            $data['name'],
            $data['unit'],
            $data['min_stock_level'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET name=?, unit=?, min_stock_level=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssdi', $data['name'], $data['unit'], $data['min_stock_level'], $id);
        return $stmt->execute();
    }
}
