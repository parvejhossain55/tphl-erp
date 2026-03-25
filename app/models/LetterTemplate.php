<?php

class LetterTemplate extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all letter_templates with paginate

    // All employees
  public function getByTypeAndDesignation($type, $designation_id = null) {
        if ($designation_id) {
            $stmt = $this->db->prepare("SELECT * FROM letter_templates 
                                       WHERE type = ? AND (designation_id = ? OR designation_id IS NULL)
                                       ORDER BY designation_id DESC");
            $stmt->bind_param("si", $type, $designation_id);
        } else {
            $stmt = $this->db->prepare("SELECT * FROM letter_templates WHERE type = ? AND designation_id IS NULL");
            $stmt->bind_param("s", $type);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Get single template by ID
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT lt.*, d.name AS designation_name 
                                   FROM letter_templates lt
                                   LEFT JOIN designations d ON lt.designation_id = d.id
                                   WHERE lt.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT lt.*, d.name AS designation_name 
                                   FROM letter_templates lt
                                   LEFT JOIN designations d ON lt.designation_id = d.id
                                   ORDER BY lt.id DESC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO letter_templates (type, designation_id, subject, body) VALUES (?, ?, ?, ?)");
        $designation_id = !empty($data['designation_id']) ? $data['designation_id'] : null;
        $stmt->bind_param("siss", $data['type'], $designation_id, $data['subject'], $data['body']);
        if ($stmt->execute()) {
            return $this->db->insert_id;
        }
        return false;
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE letter_templates SET type = ?, designation_id = ?, subject = ?, body = ? WHERE id = ?");
        $designation_id = !empty($data['designation_id']) ? $data['designation_id'] : null;
        $stmt->bind_param("sissi", $data['type'], $designation_id, $data['subject'], $data['body'], $id);
        return $stmt->execute();
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM letter_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
   
}
