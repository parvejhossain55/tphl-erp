<?php

class Payslip extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all Departments with paginate

public function getAllWithEmployee() {
    $stmt = $this->db->prepare("SELECT p.*,e.id AS employee_id,e.full_name AS employee_name ,e.email,e.joining_date,d.name AS desgination_name 
    FROM payslips AS p 
    LEFT JOIN employees AS e ON p.employee_id = e.id 
    LEFT JOIN designations AS d ON p.designation_id = d.id
    ORDER BY p.id DESC");
    
    if (!$stmt) {
        die("Prepare failed: " . $this->db->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $data;
}

public function getPaidCount(){
    $stmt = $this->db->prepare("SELECT count(*) as paid_count FROM payslips WHERE status ='paid'");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    return $data['paid_count'];
}
public function getPendingCount(){
    $stmt = $this->db->prepare("SELECT count(*) as pending_count FROM payslips WHERE status ='pending'");
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    return $data['pending_count'];
}
    // Create a new role

  public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO payslips
            (employee_id, designation_id, payslip_month, total_earning, total_deduction, net_salary, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iisddds",
            $data['employee_id'],
            $data['designation_id'],
            $data['payslip_month'],
            $data['total_earning'],
            $data['total_deduction'],
            $data['net_salary'],
            $data['status']
        );

        $stmt->execute();
        return $this->db->insert_id;
    }



 // Update role name + permissions
public function update($data)
{
    // Prepare update statement
    $stmt = $this->db->prepare("UPDATE payslips SET name = ?, type = ? WHERE id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "ssi",
        $data['name'],    
        $data['type'],  
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
    $stmt2 = $this->db->prepare("DELETE FROM payslips WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    return true;
}





}