<?php

class Employee extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }
    public function ensureOfficeBranchSchema()
    {
        $res = $this->db->query("SHOW COLUMNS FROM `employees` LIKE 'office_branch_id'");
        if ($res && $res->num_rows === 0) {
            $this->db->query("ALTER TABLE `employees` ADD COLUMN `office_branch_id` INT NULL DEFAULT 2 AFTER `workplace`");
        }
        $this->db->query("UPDATE `employees` SET `office_branch_id` = 2 WHERE `office_branch_id` IS NULL OR `office_branch_id` = 0");

        $resMeal = $this->db->query("SHOW COLUMNS FROM `employees` LIKE 'is_meal_deduction'");
        if ($resMeal && $resMeal->num_rows === 0) {
            $this->db->query("ALTER TABLE `employees` ADD COLUMN `is_meal_deduction` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Subject to meal deduction, 0 = No deduction' AFTER `per_meal_amount`");
        }

        $resMealPct = $this->db->query("SHOW COLUMNS FROM `employees` LIKE 'per_meal_percentage'");
        if ($resMealPct && $resMealPct->num_rows === 0) {
            $this->db->query("ALTER TABLE `employees` ADD COLUMN `per_meal_percentage` DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Percentage of standard meal amount employee pays' AFTER `is_meal_deduction`");
        }
    }

    public function getPaginated($limit, $offset, $search = '', $department_id = '', $team_id = '', $workplace = '')
    {
        $this->ensureOfficeBranchSchema();
        $sql = "SELECT e.*, 
                   desg.name AS designation_name, 
                   dept.name AS department_name ,
                   t.name AS team_name,
                   ob.name AS office_branch_name
            FROM employees AS e
            LEFT JOIN designations AS desg ON e.designation_id = desg.id
            LEFT JOIN departments AS dept ON e.department_id = dept.id
            LEFT JOIN teams AS t ON e.team_id = t.id
            LEFT JOIN office_branches AS ob ON ob.id = e.office_branch_id
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

        if ($workplace) {
            $sql .= " AND e.workplace = ?";
            $params[] = $workplace;
            $types .= "s";
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
            e.joining_date,
            e.resign_date,
            e.email,
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
        return (int) $result['total'];
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
        return (int) $result->fetch_assoc()['total'];
    }


    public function countAll($search = '', $department_id = '', $team_id = '', $workplace = '')
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

        if ($workplace) {
            $sql .= " AND workplace = ?";
            $params[] = $workplace;
            $types .= "s";
        }

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    public function getUniqueWorkplaces()
    {
        $sql = "SELECT DISTINCT workplace FROM employees WHERE workplace IS NOT NULL AND workplace != '' ORDER BY workplace ASC";
        $res = $this->db->query($sql);
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function create($data, $photoInputName = '')
    {
        $this->ensureOfficeBranchSchema();
        $photo = '';

        if (!empty($_FILES[$photoInputName]['name'])) {
            $photo = uploadImage($photoInputName, 'employees', 'fixed', 300, 300);
        }

        $stmt = $this->db->prepare("
        INSERT INTO employees (
            employee_id, full_name, gross_salary, basic_salary, per_meal_amount, is_meal_deduction, per_meal_percentage, username, email,
            father_name, phone, emergency_contact, qualification,
            experience, address, pass_num, nid,
            department_id, designation_id, dob, joining_date, resign_date,
            account_holder_name, account_number, bank_name, branch_name,
            social_media1, social_media2, social_media3,
            photo, make_user, password, created_by,
            employee_type, employee_status, workplace, office_branch_id, responsibilities, team_id, is_sales_co_leader,
            is_dept_head, reporting_user_id
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");

        $dob = !empty($data['dob']) ? $data['dob'] : null;
        $joining_date = !empty($data['joining_date']) ? $data['joining_date'] : null;
        $resign_date = !empty($data['resign_date']) ? $data['resign_date'] : null;

        $password = !empty($data['password'])
            ? password_hash($data['password'], PASSWORD_DEFAULT)
            : '';

        $isMealDeduction = isset($data['is_meal_deduction']) ? (int)$data['is_meal_deduction'] : 0;
        $perMealPercentage = (isset($data['per_meal_percentage']) && $data['per_meal_percentage'] !== '') ? (float)$data['per_meal_percentage'] : 100.00;

        $is_dept_head = (int) ($data['is_dept_head'] ?? 0);
        $reporting_user_id = ($is_dept_head == 1) ? null : (!empty($data['reporting_user_id']) ? (int) $data['reporting_user_id'] : null);
        $workplace = !empty($data['workplace']) ? $data['workplace'] : null;
        $office_branch_id = !empty($data['office_branch_id']) ? (int) $data['office_branch_id'] : 2;

        $stmt->bind_param(
            "ssdddidssssssssssiisssssssssssisisssisiiii",
            $data['employee_id'],
            $data['full_name'],
            $data['gross_salary'],
            $data['basic_salary'],
            $data['per_meal_amount'],
            $isMealDeduction,
            $perMealPercentage,
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
            $resign_date,
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
            $workplace,
            $office_branch_id,
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
            e.per_meal_amount,
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
        $this->ensureOfficeBranchSchema();
        $stmt = $this->db->prepare("SELECT 
    e.*,
    COALESCE(u.id, u_by_emp.id) as user_id,
    COALESCE(u.role_id, u_by_emp.role_id) as role_id, 
    COALESCE(u.secondary_role_id, u_by_emp.secondary_role_id) as secondary_role_id,
    desg.name AS designation_name, 
    dept.name AS department_name,
    t.name AS team_name,
    ob.name AS office_branch_name,
    rb.full_name AS reporting_boss_name
FROM employees AS e
LEFT JOIN designations AS desg ON e.designation_id = desg.id
LEFT JOIN departments AS dept ON e.department_id = dept.id
LEFT JOIN teams AS t ON e.team_id = t.id
LEFT JOIN office_branches AS ob ON ob.id = e.office_branch_id
LEFT JOIN users AS u ON u.id = e.user_id
LEFT JOIN users AS u_by_emp ON (u_by_emp.employee_id = e.id AND e.user_id IS NULL)
LEFT JOIN users AS rb ON rb.id = e.reporting_user_id
WHERE e.id = ?
");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function update($id, $data, $photoInputName = null)
    {
        $this->ensureOfficeBranchSchema();
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
        per_meal_amount = ?,
        is_meal_deduction = ?,
        per_meal_percentage = ?,
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
        resign_date = ?,
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
        workplace = ?,
        office_branch_id = ?,
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

        $isMealDeduction = isset($data['is_meal_deduction']) ? (int)$data['is_meal_deduction'] : 0;
        $perMealPercentage = (isset($data['per_meal_percentage']) && $data['per_meal_percentage'] !== '') ? (float)$data['per_meal_percentage'] : 100.00;

        $is_dept_head = (int) ($data['is_dept_head'] ?? 0);
        $reporting_user_id = ($is_dept_head == 1) ? null : (!empty($data['reporting_user_id']) ? (int) $data['reporting_user_id'] : null);
        $workplace = !empty($data['workplace']) ? $data['workplace'] : null;
        $office_branch_id = !empty($data['office_branch_id']) ? (int) $data['office_branch_id'] : 2;

        $stmt->bind_param(
            "ssdddidsssssssssiisssssssssssisisssisiiiii",
            $data['employee_id'],         // s  1
            $data['full_name'],           // s  2
            $data['gross_salary'],        // d  3
            $data['basic_salary'],        // d  4
            $data['per_meal_amount'],     // d  5
            $isMealDeduction,             // i  6
            $perMealPercentage,           // d  7
            $data['username'],            // s  8
            $data['email'],               // s  9
            $data['father_name'],         // s  10
            $data['phone'],               // s  11
            $data['emergency_contact'],   // s  12
            $data['qualification'],       // s  13
            $data['experience'],          // s  14
            $data['address'],             // s  15
            $data['pass_num'],            // s  16
            $data['department_id'],       // i  17
            $data['designation_id'],      // i  18
            $data['dob'],                 // s  19
            $data['joining_date'],        // s  20
            $data['resign_date'],         // s  21
            $data['account_holder_name'], // s  22
            $data['account_number'],      // s  23
            $data['bank_name'],           // s  24
            $data['branch_name'],         // s  25
            $data['social_media1'],       // s  26
            $data['social_media2'],       // s  27
            $data['social_media3'],       // s  28
            $finalPhoto,                  // s  29
            $data['make_user'],           // s  30
            $data['password'],            // s  31
            $data['created_by'],          // i  32
            $data['employee_type'],       // s  33
            $data['employee_status'],     // s  34
            $workplace,                   // s  35
            $office_branch_id,            // i  36
            $data['responsibilities'],    // s  37
            $data['team_id'],             // i  38
            $data['is_sales_co_leader'],  // i  39
            $is_dept_head,                // i  40
            $reporting_user_id,           // i  41
            $id                           // i  42
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
        if (!$loggedUserId)
            return [];

        //  Get employee ID
        $stmt = $this->db->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $loggedUserId);

        $stmt->execute();
        $emp = $stmt->get_result()->fetch_assoc();
        if (!$emp)
            return [];

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
    public function getAllForExport($search = '', $department_id = '', $team_id = '', $workplace = '')
    {
        $sql = "SELECT e.*, 
                   dept.name AS department_name, 
                   desg.name AS designation_name
            FROM employees AS e
            LEFT JOIN departments AS dept ON e.department_id = dept.id
            LEFT JOIN designations AS desg ON e.designation_id = desg.id
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

        if ($workplace) {
            $sql .= " AND e.workplace = ?";
            $params[] = $workplace;
            $types .= "s";
        }

        $sql .= " ORDER BY e.id DESC";

        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $result = $this->db->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }
    }

    public function getSearchSuggestions($q)
    {
        $sql = "SELECT id, full_name, username, employee_id 
                FROM employees 
                WHERE full_name LIKE ? OR username LIKE ? OR employee_id LIKE ? 
                ORDER BY full_name ASC 
                LIMIT 15";
        $stmt = $this->db->prepare($sql);
        $likeQ = "%$q%";
        $stmt->bind_param("sss", $likeQ, $likeQ, $likeQ);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get active employees for Meal Tracker filtered by branch and is_meal_deduction = 1
     */
    public function getActiveMealEmployeesByBranch($branchId)
    {
        $sql = "SELECT e.id, e.employee_id, e.full_name, e.department_id, e.designation_id, 
                       e.per_meal_amount, e.is_meal_deduction, e.per_meal_percentage,
                       e.office_branch_id,
                       d.name AS department_name, des.name AS designation_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE e.employee_status = 'active'
                  AND e.deleted_at IS NULL
                  AND e.is_meal_deduction = 1
                  AND e.office_branch_id = ?
                ORDER BY CAST(e.employee_id AS UNSIGNED) ASC, e.employee_id ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $branchId = (int)$branchId;
        $stmt->bind_param("i", $branchId);
        $stmt->execute();
        $res = $stmt->get_result();
        $employees = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $employees;
    }

    /**
     * Get today's meal context for a specific employee
     * Checks enrollment, attendance, 12:00 PM cutoff, and opt-out status
     */
    public function getTodayMealContext($employeeId, $date = null)
    {
        $employeeId = (int)$employeeId;
        $date = $date ? trim($date) : date('Y-m-d');
        $isBefore11 = (date('H:i') < '11:00');
        $isBefore12 = $isBefore11;

        // 1. Employee meal enrollment
        $empSql = "SELECT id, full_name, employee_id, is_meal_deduction, per_meal_percentage, office_branch_id FROM employees WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($empSql);
        if (!$stmt) return null;
        $stmt->bind_param("i", $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        $employee = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$employee) return null;

        $isEnrolled = ((int)($employee['is_meal_deduction'] ?? 0) === 1);

        // 2. Opt-out status for today
        $optSql = "SELECT status, opt_out_time FROM employee_meal_opt_outs WHERE employee_id = ? AND opt_out_date = ? LIMIT 1";
        $optStmt = $this->db->prepare($optSql);
        $isOptedOut = false;
        $optOutTime = null;
        $optStatus = null;
        if ($optStmt) {
            $optStmt->bind_param("is", $employeeId, $date);
            $optStmt->execute();
            $optRes = $optStmt->get_result();
            if ($optRes && $row = $optRes->fetch_assoc()) {
                $optStatus = $row['status'];
                $isOptedOut = ($optStatus === 'opt_out');
                $optOutTime = $row['opt_out_time'];
            }
            $optStmt->close();
        }

        // 3. Attendance for today
        $attSql = "
            SELECT today_status, punch_in_time 
            FROM emp_attendances 
            WHERE employee_id = ? AND attendance_date = ? AND deleted_at IS NULL 
            LIMIT 1
        ";
        $attStmt = $this->db->prepare($attSql);
        $isPresent = false;
        $todayStatus = 'Absent';
        if ($attStmt) {
            $attStmt->bind_param("is", $employeeId, $date);
            $attStmt->execute();
            $attRes = $attStmt->get_result();
            if ($attRes && $arow = $attRes->fetch_assoc()) {
                $todayStatus = $arow['today_status'] ?? 'Absent';
                $isPresent = in_array($todayStatus, ['Present', 'Late', 'Half-Day']);
            }
            $attStmt->close();
        }

        // 4. Effective meal count for today:
        if ($isOptedOut) {
            $mealActive = false;
        } else if ($optStatus === 'opt_in') {
            $mealActive = true;
        } else {
            $mealActive = ($isEnrolled && ($isPresent || $isBefore11));
        }

        // Auto-sync meal record for today if enrolled
        if ($isEnrolled) {
            try {
                require_once __DIR__ . '/MealRecord.php';
                $mealModel = new MealRecord($this->db);
                $mealModel->syncSingleAttendanceRecord($employeeId, $date, $todayStatus);
            } catch (\Throwable $mErr) {
                // Silent catch
            }
        }

        return [
            'employee_id' => $employeeId,
            'office_id' => $employee['employee_id'],
            'full_name' => $employee['full_name'],
            'branch_id' => (int)($employee['office_branch_id'] ?? 1),
            'is_enrolled' => $isEnrolled,
            'is_opted_out' => $isOptedOut,
            'opt_out_time' => $optOutTime,
            'is_present' => $isPresent,
            'today_status' => $todayStatus,
            'is_before_11' => $isBefore11,
            'is_before_12' => $isBefore12,
            'meal_active' => $mealActive,
            'date' => $date
        ];
    }
}


