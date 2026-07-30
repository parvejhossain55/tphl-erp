<?php

class PayslipItem extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureColumnsExist();
    }

    private function ensureColumnsExist()
    {
        if (!isset($this->db)) return;
        
        // Check if percentage column exists
        $checkCol = $this->db->query("SHOW COLUMNS FROM payslip_items LIKE 'percentage'");
        if ($checkCol && $checkCol->num_rows == 0) {
            @$this->db->query("ALTER TABLE payslip_items ADD COLUMN percentage DECIMAL(10,2) NULL DEFAULT 0.00 AFTER type");
        }

        // Check if description column exists
        $checkDesc = $this->db->query("SHOW COLUMNS FROM payslip_items LIKE 'description'");
        if ($checkDesc && $checkDesc->num_rows == 0) {
            @$this->db->query("ALTER TABLE payslip_items ADD COLUMN description TEXT NULL AFTER percentage");
        }
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM payslip_items ORDER BY id DESC");
        if (!$stmt) {
            $res = $this->db->query("SELECT * FROM payslip_items ORDER BY id DESC");
            return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }

    public function create($data)
    {
        $name       = trim($data['name'] ?? '');
        $type       = trim($data['type'] ?? 'earning');
        $percentage = (isset($data['percentage']) && $data['percentage'] !== '') ? (float)$data['percentage'] : 0.00;
        $createdBy  = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        // Try inserting with percentage first
        $stmt = $this->db->prepare("INSERT INTO payslip_items (name, type, percentage, created_by) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssdi", $name, $type, $percentage, $createdBy);
            $res = $stmt->execute();
            if (!$res) {
                error_log("PayslipItem create execute error: " . $stmt->error);
            }
            $stmt->close();
            if ($res) return true;
        }

        // Fallback insert without percentage if table structure varies
        $stmt2 = $this->db->prepare("INSERT INTO payslip_items (name, type, created_by) VALUES (?, ?, ?)");
        if ($stmt2) {
            $stmt2->bind_param("ssi", $name, $type, $createdBy);
            $res2 = $stmt2->execute();
            if (!$res2) {
                error_log("PayslipItem create fallback error: " . $stmt2->error);
            }
            $stmt2->close();
            return $res2;
        }

        return false;
    }

    public function update($data)
    {
        $id         = (int)($data['id'] ?? 0);
        $name       = trim($data['name'] ?? '');
        $type       = trim($data['type'] ?? 'earning');
        $percentage = (isset($data['percentage']) && $data['percentage'] !== '') ? (float)$data['percentage'] : 0.00;

        // Try updating with percentage
        $stmt = $this->db->prepare("UPDATE payslip_items SET name = ?, type = ?, percentage = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssdi", $name, $type, $percentage, $id);
            $res = $stmt->execute();
            if (!$res) {
                error_log("PayslipItem update execute error: " . $stmt->error);
            }
            $stmt->close();
            if ($res) return true;
        }

        // Fallback update without percentage
        $stmt2 = $this->db->prepare("UPDATE payslip_items SET name = ?, type = ? WHERE id = ?");
        if ($stmt2) {
            $stmt2->bind_param("ssi", $name, $type, $id);
            $res2 = $stmt2->execute();
            if (!$res2) {
                error_log("PayslipItem update fallback error: " . $stmt2->error);
            }
            $stmt2->close();
            return $res2;
        }

        return false;
    }

    public function delete($id)
    {
        $id = (int)$id;
        $stmt = $this->db->prepare("DELETE FROM payslip_items WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $res = $stmt->execute();
            $stmt->close();
            return $res;
        }
        return false;
    }
}