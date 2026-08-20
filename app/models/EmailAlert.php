<?php

class EmailAlert extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    public function getAllWith(string $table, string $foreignKey, string $localKey = 'id')
    {
        // 1. Sanitize SQL identifiers (table/column names cannot be bound via bind_param)
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $foreignKey = preg_replace('/[^a-zA-Z0-9_]/', '', $foreignKey);
        $localKey = preg_replace('/[^a-zA-Z0-9_]/', '', $localKey);

        // 2. Perform LEFT JOIN and alias target fields to avoid ID collisions
        $sql = "SELECT email_alerts.*, {$table}.name AS template_name 
                FROM email_alerts 
                LEFT JOIN {$table} ON email_alerts.{$foreignKey} = {$table}.{$localKey}";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM email_alerts");

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    // CHANGED: Implemented create method using PDO prepared statements
    public function create($data = [])
    {
        // 1. SQL Query matching email_alerts columns
        $sql = "INSERT INTO email_alerts (
                    email_template_id, 
                    subject, 
                    body, 
                    target_type, 
                    status,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        // 2. Check prepare failure
        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        // 3. Extract and sanitize variables from form input
        $email_template_id = (int) ($data['email_template'] ?? $data['email_template_id'] ?? 0);
        $subject = trim($data['subject'] ?? '');
        $body = trim($data['body'] ?? '');
        $target_type = $data['filter_type'] ?? $data['target_type'] ?? 'all_employees';
        $status = $data['status'] ?? 'draft';
        $createdBy = $data['created_by'] ?? null;

        // 4. Bind parameters ("issss": 1 Integer, 4 Strings)
        $stmt->bind_param(
            "issssi",
            $email_template_id,
            $subject,
            $body,
            $target_type,
            $status,
            $createdBy
        );

        // 5. Execute query
        if (!$stmt->execute()) {
            die("Email Alert Create Failed: " . $stmt->error);
        }

        // 6. Get inserted ID
        $id = $stmt->insert_id ?? $this->db->insert_id;
        $stmt->close();

        return $id;
    }

    public function updateStatus($id, $status)
    {
        $sql = "UPDATE email_alerts
            SET status = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $id = (int) $id;
        $status = trim($status);

        $stmt->bind_param("si", $status, $id);

        if (!$stmt->execute()) {
            die("Email Alert Status Update Failed: " . $stmt->error);
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        return $affectedRows;
    }


}