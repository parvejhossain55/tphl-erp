<?php

class Con_Warehouse extends Model
{
    protected $table = 'con_warehouses';

    public function getAll()
    {
        $sql = "SELECT w.*, p.name as project_name, u.full_name AS created_by_name
                FROM {$this->table} w
                LEFT JOIN projects p ON p.id = w.project_id
                LEFT JOIN users u ON u.id = w.created_by
                ORDER BY w.name ASC";
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
        $sql = "INSERT INTO {$this->table} (name, project_id, location, created_by)
                VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sisi',
            $data['name'],
            $data['project_id'],
            $data['location'],
            $data['created_by']
        );
        $stmt->execute();
        return $stmt->insert_id;
    }

    public function update($id, $data)
    {
        $sql = "UPDATE {$this->table} SET name=?, project_id=?, location=? WHERE id=?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sisi', $data['name'], $data['project_id'], $data['location'], $id);
        return $stmt->execute();
    }
}
