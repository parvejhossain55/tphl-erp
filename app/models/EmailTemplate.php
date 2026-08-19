<?php

class EmailTemplate extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    // CHANGED: Implemented create method using PDO prepared statements
    public function create($data = [])
    {
        // 1. Use ? placeholders instead of named :placeholders for MySQLi
        $sql = "INSERT INTO email_templates (name, subject, body, status) VALUES (?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        // 2. Check if prepare failed to prevent fatal errors
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        // 3. Extract values to variables (MySQLi bind_param requires pass-by-reference)
        $name = $data['template_name'] ?? $data['name'] ?? '';
        $subject = $data['subject'] ?? '';
        $body = $data['body'] ?? '';
        $status = $data['status'] ?? 'active';

        // 4. Bind parameters as strings ("ssss")
        $stmt->bind_param("ssss", $name, $subject, $body, $status);

        if (!$stmt->execute()) {
            die("Email Template Create Failed: " . $stmt->error);
        }

        $id = $stmt->insert_id ?? $this->db->insert_id;
        $stmt->close();

        return $id;
    }
}