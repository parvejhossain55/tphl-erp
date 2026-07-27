<?php

class Employee extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }
    public function getPaginated($limit, $offset, $search = '', $department_id = '', $team_id = '')
    {
        $sql = "SELECT e.*, 
                   desg.name AS designation_name, 
                   dept.name AS department_name ,
                     t.name AS team_name
            FROM employees AS e
            LEFT JOIN designations AS desg ON e.designation_id = desg.id
           LEFT JOIN departments AS dept ON e.department_id = dept.id
           LEFT JOIN teams AS t ON e.team_id = t.id
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

        if ($team_id) {
            $sql .= " AND e.team_id = ?";
            $params[] = $team_id;
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
            desg.id As designation_id, 
            dept.id AS department_id,
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

    public function getTotalCountByPeriod($period)
    {
        switch ($period) {
            case 'daily':
                $where = "DATE(created_at) = CURDATE()";
                break;
            case 'weekly':
                $where = "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                break;
            case 'monthly':
                $where = "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                break;
            case 'yearly':
                $where = "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
                break;
            default:
                return $this->getTotalCount();
        }

        $sql = "SELECT COUNT(*) AS total FROM employees WHERE $where";
        $result = $this->db->query($sql);
        return (int)$result->fetch_assoc()['total'];
    }


    public function countAll($search = '', $department_id = '', $team_id = '')
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

        if ($team_id) {
            $sql .= " AND team_id = ?";
            $params[] = $team_id;
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
    public function create($data, $photoInputName = '')
    {
        $photo = '';

        if (!empty($_FILES[$photoInputName]['name'])) {
            $photo = uploadImage($photoInputName, 'employees', 'fixed', 300, 300);
        }

        $stmt = $this->db->prepare("
        INSERT INTO employees (
            employee_id, full_name, gross_salary, basic_salary, username, email,
            father_name, phone, emergency_contact, qualification,
            experience, address, pass_num, nid,
            department_id, designation_id, dob, joining_date,
            account_holder_name, account_number, bank_name, branch_name,
            social_media1, social_media2, social_media3,
            photo, make_user, password, created_by,
            employee_type, employee_status, workplace, responsibilities, team_id, is_sales_co_leader,
            is_dept_head, reporting_user_id
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

        $dob = !empty($data['dob']) ? $data['dob'] : null;
        $joining_date = !empty($data['joining_date']) ? $data['joining_date'] : null;

        $password = !empty($data['password'])
            ? password_hash($data['password'], PASSWORD_DEFAULT)
            : '';

        $is_dept_head = (int)($data['is_dept_head'] ?? 0);
        $reporting_user_id = ($is_dept_head == 1) ? null : (!empty($data['reporting_user_id']) ? (int)$data['reporting_user_id'] : null);

        $stmt->bind_param(
            "sssdssssssssssiissssssssssisisssiiiii",
            $data['employee_id'],
            $data['full_name'],
            $data['gross_salary'],
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
            $dob,
            $joining_date,
            $data['account_holder_name'],
            $data['account_number'],
            $data['bank_name'],
            $data['branch_name'],
            $data['social_media1'],
            $data['social_media2'],
            $data['social_media3'],
            $photo,
            $data['make_user'],
            $password,
            $data['created_by'],
            $data['employee_type'],
            $data['employee_status'],
            $data['workplace'],
            $data['responsibilities'],
            $data['team_id'],
            $data['is_sales_co_leader'],
            $is_dept_head,
            $reporting_user_id
        );

        if (!$stmt->execute()) {
            die("Employee Create Failed: " . $stmt->error);
        }

        $id = $this->db->insert_id;

        return $id;
    }



    public function getAllWithDesignation()
    {
        $stmt = $this->db->prepare("
        SELECT 
            e.id,
            e.full_name,
            e.email,
            e.gross_salary,
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
    u.id as user_id,
    u.role_id as role_id, 
    desg.name AS designation_name, 
    dept.name AS department_name,
    t.name AS team_name,
    rb.full_name AS reporting_boss_name
FROM employees AS e
LEFT JOIN designations AS desg ON e.designation_id = desg.id
LEFT JOIN departments AS dept ON e.department_id = dept.id
LEFT JOIN teams AS t ON e.team_id = t.id
LEFT JOIN users AS u ON u.id = e.user_id
LEFT JOIN users AS rb ON rb.id = e.reporting_user_id
WHERE e.id = ?
");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

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

        // 5 Update query (WITH employee_id)
        $sql = "UPDATE employees SET
        employee_id = ?,
        full_name = ?, 
        gross_salary = ?,
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
        make_user = ?,
        password = ?,
        created_by = ?,
        employee_type = ?,
        employee_status = ?,
        responsibilities = ?,
        team_id = ?,
        is_sales_co_leader = ?,
        is_dept_head = ?,
        reporting_user_id = ?
    WHERE id = ?";

        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            error_log("Employee update prepare failed: " . $this->db->error);
            return false;
        }

        $is_dept_head = (int)($data['is_dept_head'] ?? 0);
        $reporting_user_id = ($is_dept_head == 1) ? null : (!empty($data['reporting_user_id']) ? (int)$data['reporting_user_id'] : null);

        $stmt->bind_param(
            "ssddsssssssssiissssssssssssisssiiiii",
            $data['employee_id'],         // s  1
            $data['full_name'],           // s  2
            $data['gross_salary'],        // d  3
            $data['basic_salary'],        // d  4
            $data['username'],            // s  5
            $data['email'],               // s  6
            $data['father_name'],         // s  7
            $data['phone'],               // s  8
            $data['emergency_contact'],   // s  9
            $data['qualification'],       // s  10
            $data['experience'],          // s  11
            $data['address'],             // s  12
            $data['pass_num'],            // s  13
            $data['department_id'],       // i  14
            $data['designation_id'],      // i  15
            $data['dob'],                 // s  16
            $data['joining_date'],        // s  17
            $data['account_holder_name'], // s  18
            $data['account_number'],      // s  19
            $data['bank_name'],           // s  20
            $data['branch_name'],         // s  21
            $data['social_media1'],       // s  22
            $data['social_media2'],       // s  23
            $data['social_media3'],       // s  24
            $finalPhoto,                  // s  25
            $data['make_user'],           // s  26
            $data['password'],            // s  27
            $data['created_by'],          // i  28
            $data['employee_type'],       // s  29
            $data['employee_status'],     // s  30
            $data['responsibilities'],    // s  31
            $data['team_id'],             // i  32
            $data['is_sales_co_leader'],  // i  33
            $is_dept_head,                // i  34
            $reporting_user_id,           // i  35
            $id                           // i  36
        );


        if (!$stmt->execute()) {
            die("EMPLOYEE UPDATE FAILED<br>" .
                "ERROR: " . $stmt->error . "<br>" .
                "ERRNO: " . $stmt->errno);
        }
        return true;
    }


    public function delete($id)
    {
        foreach (['payslips', 'tasks', 'employees'] as $table) {

            if ($table == 'employees') {
                $sql = "DELETE FROM employees WHERE id = ?";
            } elseif ($table == 'tasks') {
                $sql = "DELETE FROM tasks WHERE user_id = ?"; // 
            } else {
                $sql = "DELETE FROM payslips WHERE employee_id = ?";
            }

            $stmt = $this->db->prepare($sql);

            if (!$stmt) {
                error_log("Prepare failed for table $table: " . $this->db->error);
                return false;
            }

            $stmt->bind_param("i", $id);

            if (!$stmt->execute()) {
                error_log("Delete failed in $table: " . $stmt->error);
                return false;
            }

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
        WHERE n.user_id = ?
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


    public function birthdayNotification()
    {
        $stmt = $this->db->prepare(" SELECT e.id, e.full_name, e.dob, d.name AS designation_name, dep.name as dept_name
        FROM employees e
        LEFT JOIN designations d ON e.designation_id = d.id
        LEFT JOIN departments dep ON e.department_id = dep.id
        WHERE DATE_FORMAT(dob, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')
    ");
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT e.*, d.name as department_name, des.name as designation_name
                                    FROM employees e
                                    LEFT JOIN departments d ON e.department_id = d.id
                                    LEFT JOIN designations des ON e.designation_id = des.id
                                    WHERE e.id = ? AND e.deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    //     public function getAllForExport()
    // {
    //     $sql = "SELECT e.employee_id, e.full_name, e.email, e.phone,
    //                    dept.name AS department_name, desg.name AS designation_name,
    //                    e.joining_date, e.gross_salary
    //             FROM employees AS e
    //             LEFT JOIN departments AS dept ON e.department_id = dept.id
    //             LEFT JOIN designations AS desg ON e.designation_id = desg.id
    //             ORDER BY e.id DESC";

    //     $result = $this->db->query($sql);
    //     return $result->fetch_all(MYSQLI_ASSOC);
    // }
    public function getAllForExport()
    {
        $sql = "SELECT e.*, 
                   dept.name AS department_name, 
                   desg.name AS designation_name
            FROM employees AS e
            LEFT JOIN departments AS dept ON e.department_id = dept.id
            LEFT JOIN designations AS desg ON e.designation_id = desg.id
            ORDER BY e.id DESC";

        $result = $this->db->query($sql);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
