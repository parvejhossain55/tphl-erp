<?php

class Employee extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }
    public function getPaginated($limit, $offset, $search = '', $department_id = '')
    {
        $sql = "SELECT e.*, 
                   desg.name AS designation_name, 
                   dept.name AS department_name  
            FROM employees AS e
            JOIN designations AS desg ON e.designation_id = desg.id
            JOIN departments AS dept ON e.department_id = dept.id
            WHERE 1";

        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (e.full_name LIKE ? 
                    OR e.phone LIKE ? 
                    OR e.email LIKE ? 
                    OR e.username LIKE ?
                    OR e.employee_id LIKE ?)";

            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $types .= "sssss";
        }

        if ($department_id) {
            $sql .= " AND e.department_id = ?";
            $params[] = $department_id;
            $types .= "i";
        }

        $sql .= " ORDER BY e.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getAll()
    {
        $sql = $this->db->prepare("
        SELECT 
            e.id, 
            e.full_name, 
            e.photo, 
            e.employee_id,  
            desg.name AS designation_name, 
            dept.name AS department_name
        FROM employees AS e
        LEFT JOIN designations AS desg ON e.designation_id = desg.id
        LEFT JOIN departments AS dept ON e.department_id = dept.id
    ");

        if (!$sql->execute()) {
            return [];
        }

        $result = $sql->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function getTotalCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total FROM employees");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int)$result['total'];
    }

    public function countAll($search = '', $department_id = 'employeePhoto')
    {
        $sql = "SELECT COUNT(*) as total FROM employees WHERE 1";
        $params = [];
        $types = "";

        if ($search) {
            $sql .= " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ? OR username LIKE ? OR employee_id LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";

            $params[] = "%$search%";
            $types .= "sssss";
        }

        if ($department_id) {
            $sql .= " AND department_id = ?";
            $params[] = $department_id;
            $types .= "i";
        }

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    // models/Employee.php
    public function create($data, $photoInputName = '')
    {
        // Upload photo
        $photo = '';
        if (!empty($_FILES[$photoInputName]['name'])) {
            $photo = uploadImage($photoInputName, 'employees', 'fixed', 300, 300);
        }

        // Prepare variables (NO employee_id here)
        $stmt = $this->db->prepare(
            "INSERT INTO employees 
        (full_name,basic_salary, username, email, father_name, phone, emergency_contact, 
         qualification, experience, address, pass_num, nid, department_id, 
         designation_id, dob, joining_date, account_holder_name, account_number, 
         bank_name, branch_name, social_media1, social_media2, social_media3, 
         photo, make_user, password,created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)"
        );

        $stmt->bind_param(
            "sissssssssssiissssssssssisi",
            $data['full_name'],
            $data['basic_salary'],
            $data['username'],
            $data['email'],
            $data['father_name'],
            $data['phone'],
            $data['emergency_contact'],
            $data['qualification'],
            $data['experience'],
            $data['address'],
            $data['pass_num'],
            $data['nid'],
            $data['department_id'],
            $data['designation_id'],
            $data['dob'],
            $data['joining_date'],
            $data['account_holder_name'],
            $data['account_number'],
            $data['bank_name'],
            $data['branch_name'],
            $data['social_media1'],
            $data['social_media2'],
            $data['social_media3'],
            $photo,
            $data['make_user'],
            $data['password'],
            $data['created_by']
        );

        if (!$stmt->execute()) {
            error_log("Employee insert failed: " . $this->db->error);
            return false;
        }

        //  Get auto ID
        $id = $this->db->insert_id;

        //  Generate employee_id (EMP-00001)
        $employeeCode = 'EMP-' . str_pad($id, 5, '0', STR_PAD_LEFT);

        //  Update employee_id
        $update = $this->db->prepare(
            "UPDATE employees SET employee_id = ? WHERE id = ?"
        );
        $update->bind_param("si", $employeeCode, $id);
        $update->execute();

        return true;
    }

    public function getAllWithDesignation()
    {
        $stmt = $this->db->prepare("
        SELECT 
            e.id,
            e.full_name,
            e.email,
            e.basic_salary,
            e.employee_id,
            e.joining_date,
            e.designation_id,         
            d.name AS designation
        FROM employees e
        LEFT JOIN designations d ON e.designation_id = d.id
        ORDER BY e.full_name ASC
    ");

        if (!$stmt) {
            die("Prepare failed: " . $this->db->error);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $data;
    }

    //employee id find for edit
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT 
            e.*, 
            desg.name AS designation_name, 
            dept.name AS department_name
        FROM employees AS e
        LEFT JOIN designations AS desg ON e.designation_id = desg.id
        LEFT JOIN departments AS dept ON e.department_id = dept.id
        WHERE e.id = ?
");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    /**
     * Update employee
     */
    public function update($id, $data, $photoInputName = null)
    {
        // 1 Get old employee
        $oldEmployee = $this->find($id);
        if (!$oldEmployee) {
            return false;
        }

        $oldPhoto = $oldEmployee['photo'] ?? '';

        // 2 Upload new photo (if any)
        $newPhoto = null;
        if ($photoInputName && !empty($_FILES[$photoInputName]['name'])) {
            $newPhoto = uploadImage($photoInputName, 'employees', 'fixed', 300, 300);
        }

        // 3 Decide final photo
        $finalPhoto = $newPhoto ?: $oldPhoto;

        // 4 Delete old photo if new uploaded
        if (
            $newPhoto &&
            !empty($oldPhoto) &&
            file_exists(dirname(__DIR__, 2) . "/public/uploads/employees/" . $oldPhoto)
        ) {
            unlink(dirname(__DIR__, 2) . "/public/uploads/employees/" . $oldPhoto);
        }

        // 5 Update query (NO employee_id)
        $sql = "UPDATE employees SET
        full_name = ?, 
        basic_salary = ?, 
        username = ?, 
        email = ?, 
        father_name = ?, 
        phone = ?, 
        emergency_contact = ?,
        qualification = ?, 
        experience = ?, 
        address = ?, 
        pass_num = ?, 
        department_id = ?, 
        designation_id = ?, 
        dob = ?, 
        joining_date = ?,
        account_holder_name = ?, 
        account_number = ?, 
        bank_name = ?, 
        branch_name = ?, 
        social_media1 = ?, 
        social_media2 = ?, 
        social_media3 = ?, 
        photo = ?,
        created_by = ?
    WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            error_log("Employee update prepare failed: " . $this->db->error);
            return false;
        }

        $stmt->bind_param(
            "sdsssssssssiiisssssssssii",
            $data['full_name'],
            $data['basic_salary'],
            $data['username'],
            $data['email'],
            $data['father_name'],
            $data['phone'],
            $data['emergency_contact'],
            $data['qualification'],
            $data['experience'],
            $data['address'],
            $data['pass_num'],
            $data['department_id'],
            $data['designation_id'],
            $data['dob'],
            $data['joining_date'],
            $data['account_holder_name'],
            $data['account_number'],
            $data['bank_name'],
            $data['branch_name'],
            $data['social_media1'],
            $data['social_media2'],
            $data['social_media3'],
            $finalPhoto,
            $data['created_by'],
            $id
        );

        return $stmt->execute();
    }


    public function delete($id)
    {
        $tables = ['payslips', 'tasks', 'employees'];

        foreach ($tables as $table) {
            $stmt = $this->db->prepare($table == 'employees'
                ? "DELETE FROM employees WHERE id = ?"
                : "DELETE FROM $table WHERE employee_id = ?");

            if (!$stmt) {
                error_log("Prepare failed for table $table: " . $this->db->error);
                continue;
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }

        return true;
    }

    public function taskNotification()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $loggedUserId = $_SESSION['user']['id'] ?? null;
        if (!$loggedUserId) return [];

        //  Get employee ID
        $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $loggedUserId);
        $stmt->execute();
        $emp = $stmt->get_result()->fetch_assoc();
        if (!$emp) return [];

        $employeeId = (int) $emp['id'];

        //  Fetch notifications ONLY for incomplete tasks
        $stmt = $this->db->prepare("
        SELECT n.*, u.full_name AS assigned_by_name, t.progress, t.status
        FROM notifications n
        LEFT JOIN tasks t ON n.task_id = t.id
        LEFT JOIN users u ON n.assigned_by = u.id
        WHERE n.employee_id = ?
          AND n.is_read = 0
          AND (t.status IS NULL OR t.status != 'completed')
        ORDER BY n.created_at DESC
    ");
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $notifications;
    }



    public function getDepartmentWiseEmp()
    {
        $stmt = $this->db->prepare("SELECT d.id,d.name AS dept_name,
        COUNT(e.id) AS total_employee
    FROM departments d
    LEFT JOIN designations desg ON desg.department_id = d.id
    LEFT JOIN employees e ON e.designation_id = desg.id
    GROUP BY d.id
    ORDER BY d.name ASC
    ");

        if (!$stmt) {
            return [];
        }
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
}
