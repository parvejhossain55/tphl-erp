<?php
class PlatformBudget extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    // Get allowed platforms (hardcoded)
    public function getAllowedPlatforms()
    {
        return [
            'Meta' => 'Meta',
            'Google' => 'Google',
            'LinkedIn' => 'LinkedIn'
        ];
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_budgets (platform_name, month, budget, spent, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            setError("Prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "ssddi",
            $data['platform_name'],
            $data['month'],
            $data['budget'],
            $data['spent'],
            $data['created_by']
        );

        if ($stmt->execute()) {
            return true;
        }

        setError("Insert failed: " . $stmt->error);
        return false;
    }

    public function getAllWithPlatforms($monthFilter = null)
    {
        $sql = "
            SELECT * FROM platform_budgets
        ";
        
        $params = [];
        $types = "";

        if ($monthFilter) {
            $sql .= " WHERE month = ?";
            $params[] = $monthFilter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY month DESC, id DESC";
        
        $stmt = $this->db->prepare($sql);
        
        if ($monthFilter) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getMonthlySummary($monthFilter = null)
    {
        $sql = "
            SELECT 
                SUM(budget) as total_budget, 
                SUM(spent) as total_spent,
                COUNT(DISTINCT platform_name) as active_platforms
            FROM platform_budgets
        ";
        
        $params = [];
        $types = "";

        if ($monthFilter) {
            $sql .= " WHERE month = ?";
            $params[] = $monthFilter;
            $types .= "s";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($monthFilter) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : [
            'total_budget' => 0,
            'total_spent' => 0,
            'active_platforms' => 0
        ];
    }

    // Get budget by platform and month (for checking duplicates)
    public function getByPlatformAndMonth($platform_name, $month)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM platform_budgets 
            WHERE platform_name = ? AND month = ?
        ");
        
        $stmt->bind_param("ss", $platform_name, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }

    // Update existing budget
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("
            UPDATE platform_budgets 
            SET budget = ?, spent = ? 
            WHERE id = ?
        ");

        if (!$stmt) {
            setError("Prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param("ddi", $data['budget'], $data['spent'], $id);

        if ($stmt->execute()) {
            return true;
        }

        setError("Update failed: " . $stmt->error);
        return false;
    }

    // Delete budget
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM platform_budgets WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            return true;
        }
        
        setError("Delete failed: " . $stmt->error);
        return false;
    }
}
?>