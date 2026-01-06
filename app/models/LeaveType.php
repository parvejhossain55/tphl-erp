<?php

class LeaveType extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all Departments with paginate

public function getAll() {
    $stmt = $this->db->prepare("SELECT * FROM leave_types");
    
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
    // Module name দিয়ে photo upload
   

    $stmt = $this->db->prepare(
        "INSERT INTO leave_types
        (name, is_active,created_by)
        VALUES (?, ?,?)"
    );


$stmt->bind_param(
    "sii",
    $data['name'],
    $data['is_active'],
    $data['created_by'],
    
);
    if ($stmt->execute()) {
        return true;
    } else {
        echo "ERROR: " . $stmt->error;
        return false;
    }
}



 // Update role name + permissions
public function update($data)
{
    // Prepare update statement
    $stmt = $this->db->prepare("UPDATE leave_types SET name = ?, is_active = ?,created_by=? WHERE id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "siii",
        $data['name'],    
        $data['is_active'],  
        $data['created_by'],  
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
    // 1 Delete all designations linked to this department
    $stmt1 = $this->db->prepare("DELETE FROM leave_types WHERE id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();
    return true;
}



}