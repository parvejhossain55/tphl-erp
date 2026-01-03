<?php

class PayslipDetail extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all Departments with paginate

public function getAll() {
    $stmt = $this->db->prepare("SELECT * FROM payslips");
    
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

 public function create($payslipId, $itemId, $amount)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payslip_details
            (payslip_id, payslip_item_id, amount)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param("iid", $payslipId, $itemId, $amount);
        return $stmt->execute();
    }



 // Update role name + permissions

public function delete($id)
{
    // Delete all designations linked to this department
    // $stmt1 = $this->db->prepare("DELETE FROM payslip_details WHERE payslip_item_id = ?");
    // $stmt1->bind_param("i", $id);
    // $stmt1->execute();
    // $stmt1->close();

    //  Delete the department
    $stmt2 = $this->db->prepare("DELETE FROM payslips WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    return true;
}





}