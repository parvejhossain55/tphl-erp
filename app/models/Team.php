<?php

class Team extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM teams ORDER BY id DESC");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function create($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO teams (name, status, created_by) VALUES (?, ?, ?)"
        );
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->bind_param("sii", $data['name'], $data['status'], $data['created_by']);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function update($data)
    {
        $stmt = $this->db->prepare(
            "UPDATE teams SET name = ?, status = ? WHERE id = ?"
        );
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->bind_param("sii", $data['name'], $data['status'], $data['id']);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM teams WHERE id = ?");
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }
}
