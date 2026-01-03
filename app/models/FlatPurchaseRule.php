<?php
class FlatPurchaseRule extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db; // $db = mysqli connection
    }

    // Get all rules
    public function getAll()
    {
        $result = $this->db->query("SELECT * FROM flat_purchase_rules ORDER BY id DESC");

        $rules = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rules[] = $row;
            }
        }
        return $rules;
    }

    // Find rule by ID
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM flat_purchase_rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }

    // Create rule
    public function create($data)
    {
        $stmt = $this->db->prepare("INSERT INTO flat_purchase_rules (rules_description, status, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->bind_param("si", $data['rules_description'], $data['status']);
        return $stmt->execute();
    }

    // Update rule
    public function update($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE flat_purchase_rules SET rules_description = ?, status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param("sii", $data['rules_description'], $data['status'], $id);
        return $stmt->execute();
    }

    // Delete rule
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM flat_purchase_rules WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
}
