<?php

class Department extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Get all Departments with paginate
    public function getPaginated($limit, $offset, $search = '')
    {
        $sql = "SELECT * FROM departments WHERE 1";
        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (name LIKE ?)";
            $params[] = "%$search%";
            $types .= "s";
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getAll()
    {
        $stmt = $this->db->prepare("SELECT * FROM departments");

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
            "INSERT INTO departments
        (name, status, created_by)
        VALUES (?, ?, ?)"
        );

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->bind_param(
            "sii",
            $data['name'],
            $data['status'],
            $data['created_by']
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
        $stmt = $this->db->prepare(
            "UPDATE departments 
        SET name = ?, status = ?, created_by = ?
        WHERE id = ?"
        );

        if (!$stmt) {
            echo "Prepare failed: " . $this->db->error;
            return false;
        }

        $stmt->bind_param(
            "siii",
            $data['name'],
            $data['status'],
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
        // 1. Delete payslip_details for employees in this department
        $stmt = $this->db->prepare("
            DELETE pd FROM payslip_details pd
            JOIN payslips p ON pd.payslip_id = p.id
            JOIN employees e ON p.employee_id = e.id
            WHERE e.department_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // 2. Delete payslips for employees in this department
        $stmt = $this->db->prepare("
            DELETE p FROM payslips p
            JOIN employees e ON p.employee_id = e.id
            WHERE e.department_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // 3. Delete tasks of employees in this department
        $stmt = $this->db->prepare("
            DELETE t FROM tasks t
            JOIN employees e ON t.user_id = e.id
            WHERE e.department_id = ?
        ");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // 4. Delete employees in this department
        $stmt = $this->db->prepare("DELETE FROM employees WHERE department_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // 5. Delete designations linked to this department
        $stmt = $this->db->prepare("DELETE FROM designations WHERE department_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        // 6. Delete the department
        $stmt = $this->db->prepare("DELETE FROM departments WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    }
    public function getTotalDept()
    {
        $sql = $this->db->prepare("SELECT COUNT(*) AS total FROM departments");
        $sql->execute();
        // $sql->bind_result($total);
        $sql->fetch();
        $sql->close();
        // return $total;

    }
}
