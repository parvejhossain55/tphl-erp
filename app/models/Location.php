<?php

class Location extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all locations with paginate

public function getAll() {
    $stmt = $this->db->prepare("SELECT * FROM locations");
    
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
        "INSERT INTO locations(name) VALUES (?)");


$stmt->bind_param(
    "s",
    $data['name']);
    if ($stmt->execute()) {
        return true;
    } else {
        echo "ERROR: " . $stmt->error;
        return false;
    }
}

public function update($data)
{
    // Prepare update statement
    $stmt = $this->db->prepare("UPDATE locations SET name = ? WHERE id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "si",
        $data['name'],     
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
    // 1️⃣ Delete all designations linked to this department
    $stmt1 = $this->db->prepare("DELETE FROM locations WHERE department_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    // 2️⃣ Delete the department
    $stmt2 = $this->db->prepare("DELETE FROM locations WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    return true;
}





}