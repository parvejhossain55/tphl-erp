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
        "INSERT INTO locations (name,created_by) VALUES (?,?)");


$stmt->bind_param(
    "si",
    $data['name'],
    $data['created_by']


);
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
    $stmt = $this->db->prepare("UPDATE locations SET name = ?,created_by=? WHERE id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "sii",
        $data['name'],     
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
    // 1️⃣ Delete all designations linked to this department
        // Delete all designations linked to this department
    $stmt = $this->db->prepare("DELETE FROM projects WHERE location_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    $stmt1 = $this->db->prepare("DELETE FROM locations WHERE id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    return true;
}





}