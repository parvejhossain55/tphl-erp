<?php

class Permission extends Model
{
     public function __construct($db = null)
    {
        parent::__construct($db); 
    }
    // Get all permissions
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions ORDER BY name ASC");
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Get single permission
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM permissions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Create permission
public function create($data)
{
    $stmt = $this->db->prepare(
        "INSERT INTO permissions (name, status,created_by) VALUES (?, ?,?)"
    );

    if (!$stmt) {
        echo "❌ Prepare Failed: " . $this->db->error;
        return false;
    }

    if (!$stmt->bind_param("sii", $data['name'], $data['status'],$data['created_by'])) {
        echo "Bind Failed: " . $stmt->error;
        return false;
    }

    if (!$stmt->execute()) {
        echo " Execute Failed: " . $stmt->error;
        return false;
    }

    return true;
}



    // Update permission
public function update($data)
{
    $stmt = $this->db->prepare("
        UPDATE permissions 
        SET name = ?, status = ?,created_by = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        die($this->db->error);
    }

    $stmt->bind_param(
        "siii",
        $data['name'],   
        $data['status'],  
        $data['created_by'],  
        $data['id']       
    );

    return $stmt->execute();
}

    // Delete permission
public function delete($id)
{
    // Delete all designations linked to this department
    $stmt1 = $this->db->prepare("DELETE FROM role_permissions WHERE permission_id = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $stmt1->close();

    //  Delete the department
    $stmt2 = $this->db->prepare("DELETE FROM permissions WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    return true;
}

}
