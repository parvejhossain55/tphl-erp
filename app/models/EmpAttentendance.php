<?php

class EmpAttentendance extends Model{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }


    public function getAll()
    {
       $stmp = $this->db->prepare("SELECT a.*, e.id, e.full_name AS employee_name
                FROM emp_attendances a
                LEFT JOIN employees e ON a.employee_id = e.id
                ORDER BY a.id DESC");
                $stmp->execute();
                $result = $stmp->get_result();
                return $result->fetch_all(MYSQLI_ASSOC);
    }


    public function create($data)
    {
        // Module name দিয়ে photo upload
    
        $stmt = $this->db->prepare(
            "INSERT INTO emp_attendances
            (employee_id,punch_in_time,punch_out_time,attendance_date,today_status,created_by,)
            VALUES (?, ?,?,?,?,?)"
        );
    $stmt->bind_param(
        "isssi",
        $data['employee_id'],
    
        $data['punch_in_time'],
        $data['punch_out_time'],
        $data['attendance_date'],
        $data['today_status'],
        $data['created_by']
    
    );
        if ($stmt->execute()) {
            return true;
        } else {
            echo "ERROR: " . $stmt->error;
            return false;
        }
    }



public function update($id, $data)
{
    $stmt = $this->db->prepare("
        UPDATE emp_attendances 
        SET employee_id = ?,punch_in_time,punch_out_time,attendance_date,today_status=?, created_by = ? 
        WHERE id = ?
    ");

    $stmt->bind_param("isssii", $data['employee_id'], $data['punch_in_time'], $data['punch_out_time'],$data['attendance_date'],$data['today_status'],$data['created_at'], $id);

    return $stmt->execute();
}

public function delete($id)
{
    $stmt = $this->db->prepare("DELETE FROM emp_attendances WHERE id = ?");
    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param("i", $id);

    if (!$stmt->execute()) {
        echo "Execute failed: " . $stmt->error;
        return false;
    }

    $stmt->close();
    return true;
}


}