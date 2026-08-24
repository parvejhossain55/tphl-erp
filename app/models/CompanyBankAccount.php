<?php

class CompanyBankAccount extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all communities with paginate



public function getAll() {
    $stmt = $this->db->prepare("SELECT * FROM company_bank_accounts");
    
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
    $stmt = $this->db->prepare(
        "INSERT INTO company_bank_accounts
        (account_name, account_number, bank_name, status,created_by)
        VALUES (?, ?, ?, ?, ?)"
    );


$stmt->bind_param(
    "ssssi",
    $data['account_name'],
    $data['account_number'],
    $data['bank_name'],
    $data['status'],
    $data['created_by']
);
    if ($stmt->execute()) {
        return $this->db->insert_id;
    } else {
        echo "ERROR: " . $this->db->error;
        return false;
    }
}



 // Update role name + permissions
public function update($data)
{
    $stmt = $this->db->prepare("
        UPDATE company_bank_accounts 
        SET account_name = ?, 
            account_number = ?, 
            bank_name = ?, 
            status = ?, 
            created_by = ?
        WHERE id = ?
    ");

    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "ssssii", // 🔥 status is STRING (ENUM)
        $data['account_name'],
        $data['account_number'],
        $data['bank_name'],
        $data['status'],
        $data['created_by'],
        $data['id']
    );

    $result = $stmt->execute();
    $stmt->close();

    return $result;
}


public function delete($id)
{


    // 2 Delete the communitie
    $stmt2 = $this->db->prepare("DELETE FROM company_bank_accounts WHERE id = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $stmt2->close();

    return true;
}





}