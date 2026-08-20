<?php

class EmailAlertTarget extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM email_alert_targets");

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    /**
     * Insert a single target record
     */
    public function create($data = [])
    {
        $sql = "INSERT INTO email_alert_targets (
                    email_alert_id, 
                    target_type, 
                    target_id
                ) VALUES (?, ?, ?)";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $email_alert_id = (int) ($data['email_alert_id'] ?? 0);
        $target_type = $data['target_type'] ?? 'department';
        $target_id = (int) ($data['target_id'] ?? 0);

        // "isi": integer, string, integer
        $stmt->bind_param("isi", $email_alert_id, $target_type, $target_id);

        if (!$stmt->execute()) {
            die("Email Alert Target Create Failed: " . $stmt->error);
        }

        $id = $stmt->insert_id ?? $this->db->insert_id;
        $stmt->close();

        return $id;
    }

    /**
     * Insert multiple target IDs for an email alert at once
     * 
     * @param int $emailAlertId ID from email_alerts table
     * @param string $targetType 'department' | 'designation' | 'specific_employees'
     * @param array $targetIds Array of IDs e.g. [1, 2, 5]
     */
    public function createBatch(int $emailAlertId, string $targetType, array $targetIds)
    {
        if (empty($targetIds)) {
            return false;
        }

        $sql = "INSERT INTO email_alert_targets (email_alert_id, target_type, target_id) VALUES (?, ?, ?)";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        foreach ($targetIds as $targetId) {
            $tId = (int) $targetId;
            $stmt->bind_param("isi", $emailAlertId, $targetType, $tId);
            $stmt->execute();
        }

        $stmt->close();
        return true;
    }

    /**
     * Get all target rules associated with a specific email_alert_id
     */
    public function getByAlertId(int $emailAlertId)
    {
        $sql = "SELECT * FROM email_alert_targets WHERE email_alert_id = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param("i", $emailAlertId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    /**
     * Delete existing targets for an alert (useful before updating)
     */
    public function deleteByAlertId(int $emailAlertId)
    {
        $sql = "DELETE FROM email_alert_targets WHERE email_alert_id = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param("i", $emailAlertId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
}