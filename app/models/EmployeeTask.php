<?php

class EmployeeTask extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        parent::__construct($db);
    }
    /* =========================
       Get All Projects
    ========================== */

    public function createTask($data)
    {
        $stmt = $this->db->prepare("INSERT INTO employee_tasks 
        (employee_id, work_date, task_description, hours_spent, status, remarks) 
        VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "ississ",
            $data['employee_id'],
            $data['work_date'],
            $data['task_description'],
            $data['hours_spent'],
            $data['status'],
            $data['remarks']
        );
        return $stmt->execute();
    }

    public function getTasksByEmployeeAndMonth($employee_id, $month)
    {
        $stmt = $this->db->prepare("
        SELECT * FROM employee_tasks 
        WHERE employee_id = ? AND DATE_FORMAT(work_date, '%Y-%m') = ?
        ORDER BY work_date DESC
    ");
        $stmt->bind_param("is", $employee_id, $month);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function updateTask($id, $data)
    {
        $stmt = $this->db->prepare("UPDATE employee_tasks SET 
        employee_id=?, work_date=?, task_description=?, hours_spent=?, status=?, remarks=? 
        WHERE id=?");
        $stmt->bind_param(
            "ississi",
            $data['employee_id'],
            $data['work_date'],
            $data['task_description'],
            $data['hours_spent'],
            $data['status'],
            $data['remarks'],
            $id
        );
        return $stmt->execute();
    }



public function getDepartmentKPI($department_id, $employee_id, $status, $date_from, $date_to, $month)
{
    $sql = "SELECT 
                t.*,
                e.id As employee_id,
                e.full_name AS emp_name,
                d.name AS department_name
            FROM employee_tasks t
            JOIN employees e ON t.employee_id = e.id
            JOIN departments d ON e.department_id = d.id
            WHERE 1=1";

    $types = "";
    $params = [];

    if (!empty($department_id)) {
        $sql .= " AND d.id = ?";
        $types .= "i";
        $params[] = $department_id;
    }

    if (!empty($employee_id)) {
        $sql .= " AND e.id = ?";
        $types .= "i";
        $params[] = $employee_id;
    }

    if (!empty($status)) {
        $sql .= " AND LOWER(t.status) = ?";
        $types .= "s";
        $params[] = strtolower($status);
    }

    if (!empty($date_from)) {
        $sql .= " AND t.work_date >= ?";
        $types .= "s";
        $params[] = $date_from;
    }

    if (!empty($date_to)) {
        $sql .= " AND t.work_date <= ?";
        $types .= "s";
        $params[] = $date_to;
    }

    if (!empty($month)) {
        $sql .= " AND DATE_FORMAT(t.work_date, '%Y-%m') = ?";
        $types .= "s";
        $params[] = $month;
    }

    $sql .= " ORDER BY t.work_date DESC";

    $stmt = $this->db->prepare($sql);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}


public function getAllDepartments() {
    $res = $this->db->query("SELECT id, name FROM departments");
    return $res->fetch_all(MYSQLI_ASSOC);
}

public function getAllEmployees() {
    $res = $this->db->query("SELECT id, full_name, employee_id FROM employees");
    return $res->fetch_all(MYSQLI_ASSOC);
}

    public function getFilteredTasks($department_id, $employee_id, $status, $date_from, $date_to)
    {
        $sql = "SELECT 
                t.*, 
                e.full_name AS emp_name,
                d.name AS department_name
            FROM employee_tasks t
            JOIN employees e ON t.employee_id = e.id
            JOIN departments d ON e.department_id = d.id
            WHERE 1=1";

        $types = "";
        $params = [];

        if ($department_id) {
            $sql .= " AND d.id = ?";
            $types .= "i";
            $params[] = $department_id;
        }

        if ($employee_id) {
            $sql .= " AND e.id = ?";
            $types .= "i";
            $params[] = $employee_id;
        }

        if ($status) {
            $sql .= " AND t.status = ?";
            $types .= "s";
            $params[] = strtolower($status); // DB te lowercase thakle
        }

        if ($date_from) {
            $sql .= " AND t.work_date >= ?";
            $types .= "s";
            $params[] = $date_from;
        }

        if ($date_to) {
            $sql .= " AND t.work_date <= ?";
            $types .= "s";
            $params[] = $date_to;
        }

        $sql .= " ORDER BY t.work_date DESC";

        $stmt = $this->db->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
