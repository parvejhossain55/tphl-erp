<?php
class PlatformBudget extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO platform_budgets (source_id, month, budget, spent, created_by) 
            VALUES (?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            setError("Prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "isddi",
            $data['source_id'],
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

    public function getAllWithSources($monthFilter = null)
    {
        $sql = "
            SELECT pb.*, ls.name as source_name 
            FROM platform_budgets pb
            LEFT JOIN lead_sources ls ON pb.source_id = ls.id
        ";
        
        $params = [];
        $types = "";

        if ($monthFilter) {
            $sql .= " WHERE pb.month = ?";
            $params[] = $monthFilter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY pb.month DESC, pb.id DESC";
        
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
                COUNT(id) as active_platforms
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
}