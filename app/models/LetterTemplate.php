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
        $stmt = $this->db->prepare("SELECT * FROM letter_templates WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Single employee
   
}
