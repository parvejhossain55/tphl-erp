<?php

class PayslipItem extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all Departments with paginate

public function getAll() {
    $stmt = $this->db->prepare("SELECT * FROM payslip_items");
    
    if (!$stmt) {
        die("Prepare failed: " . $this->db->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}


    // Create a new role

public function create($data)
{
    $percentage = isset($data['percentage']) && $data['percentage'] !== '' ? (float)$data['percentage'] : null;
    $stmt = $this->db->prepare(
        "INSERT INTO payslip_items
        (name, type, percentage, created_by)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssdi",
        $data['name'],
        $data['type'],
        $percentage,
        $data['created_by']
    );

    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        echo "ERROR: " . $stmt->error;
        return false;
    }
}

public function update($data)
{
    $percentage = isset($data['percentage']) && $data['percentage'] !== '' ? (float)$data['percentage'] : null;
    $stmt = $this->db->prepare("UPDATE payslip_items SET name = ?, type = ?, percentage = ? WHERE id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "ssdi",
        $data['name'],    
        $data['type'],  
        $percentage,
        $data['id']       
    );

    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error;
        return false;
    }

    $stmt->close();
    return true;
}

public function delete($id)
{
    // Delete all designations linked to this department
    // $stmt1 = $this->db->prepare("DELETE FROM payslip_details WHERE payslip_item_id = ?");
    // $stmt1->bind_param("i", $id);
    // $stmt1->execute();
    // $stmt1->close();

    //  Delete the department
    $stmt2 = $this->db->prepare("DELETE FROM payslip_items WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    return true;
}





}