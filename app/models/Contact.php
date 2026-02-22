<?php

class Contact extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all communities with paginate

public function getAll() {
    $stmt = $this->db->prepare("SELECT * FROM contacts");
    
    if (!$stmt) {
        die("Prepare failed: " . $this->db->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}
public function updateStatus($id, $status)
{
    $stmt = $this->db->prepare("UPDATE contacts SET contact_status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    return $stmt->execute();
}

    // Create a new role


 // Update role name + permissions


public function delete($id)
{
    // 1️⃣ Delete all designations linked to this communitie
    $stmt1 = $this->db->prepare("DELETE FROM projects WHERE community_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    // 2️⃣ Delete the communitie
    $stmt2 = $this->db->prepare("DELETE FROM communities WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    return true;
}

}