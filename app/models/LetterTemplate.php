<?php

class LetterTemplate extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->db = $db;
    }

    public function getAll()
    {
        return $this->db->query("SELECT * FROM letter_templates ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM letter_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO letter_templates (name, type, subject, body) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssss",
            $data['name'],
            $data['type'],
            $data['subject'],
            $data['body']
        );
        return $stmt->execute();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE letter_templates 
            SET name = ?, type = ?, subject = ?, body = ? 
            WHERE id = ?
        ");
        $stmt->bind_param(
            "ssssi",
            $data['name'],
            $data['type'],
            $data['subject'],
            $data['body'],
            $id
        );
        return $stmt->execute();
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM letter_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
