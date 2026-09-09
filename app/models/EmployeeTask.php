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
            "issdss",
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
        if (empty($month) || $month === 'all') {
            return $this->getTasksByEmployee($employee_id, ['year' => 'all', 'month' => 'all']);
        }
        return $this->getTasksByEmployee($employee_id, ['month' => $month]);
    }

    public function getTasksByEmployee($employee_id, array $filters = [])
    {
        $ids = is_array($employee_id) ? array_filter($employee_id) : [$employee_id];

        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $empStmt = $this->db->prepare("SELECT id, employee_id FROM employees WHERE id IN ($placeholders) OR employee_id IN ($placeholders)");
            if ($empStmt) {
                $types = str_repeat('s', count($ids) * 2);
                $allParams = array_merge($ids, $ids);
                $empStmt->bind_param($types, ...$allParams);
                $empStmt->execute();
                $empRes = $empStmt->get_result();
                while ($empRow = $empRes->fetch_assoc()) {
                    if (!empty($empRow['id'])) $ids[] = (int)$empRow['id'];
                    if (!empty($empRow['employee_id'])) $ids[] = $empRow['employee_id'];
                }
                $empStmt->close();
            }
        }
        $ids = array_values(array_unique(array_filter($ids)));

        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM employee_tasks WHERE employee_id IN ($placeholders)";
        $types = str_repeat('s', count($ids));
        $params = $ids;

        // 1. Date Range filter
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            if (!empty($filters['date_from'])) {
                $sql .= " AND work_date >= ?";
                $types .= "s";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND work_date <= ?";
                $types .= "s";
                $params[] = $filters['date_to'];
            }
        } else {
            // 2. Month and Year filter
            $month = $filters['month'] ?? null;
            $year = $filters['year'] ?? null;

            // Legacy 'YYYY-MM' format in month
            if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
                $sql .= " AND DATE_FORMAT(work_date, '%Y-%m') = ?";
                $types .= "s";
                $params[] = $month;
            } else {
                $hasYear = !empty($year) && $year !== 'all';
                $hasMonth = !empty($month) && $month !== 'all';

                if ($hasYear && $hasMonth) {
                    $ym = sprintf('%04d-%02d', (int)$year, (int)$month);
                    $sql .= " AND DATE_FORMAT(work_date, '%Y-%m') = ?";
                    $types .= "s";
                    $params[] = $ym;
                } elseif ($hasYear && !$hasMonth) {
                    $sql .= " AND YEAR(work_date) = ?";
                    $types .= "i";
                    $params[] = (int)$year;
                } elseif (!$hasYear && $hasMonth) {
                    $sql .= " AND MONTH(work_date) = ?";
                    $types .= "i";
                    $params[] = (int)$month;
                }
                // If both are 'all' or empty, no date constraint is added (returns all tasks)
            }
        }

        if (!empty($filters['status'])) {
            $sql .= " AND LOWER(status) = ?";
            $types .= "s";
            $params[] = strtolower(trim($filters['status']));
        }

        $sql .= " ORDER BY work_date DESC, id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

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
            "issdssi",
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

    /**
     * Get active employees with valid employee_id (excluding inactive, resigned, terminated)
     */
    public function getActiveEmployeesForFilter($department_id = null)
    {
        $sql = "SELECT e.id, e.full_name, e.employee_id, e.department_id, d.name AS department_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                WHERE e.deleted_at IS NULL
                  AND (e.employee_status IS NULL OR LOWER(e.employee_status) NOT IN ('inactive', 'resigned', 'terminated'))
                  AND e.employee_id IS NOT NULL 
                  AND TRIM(e.employee_id) != ''";

        $types = "";
        $params = [];

        if (!empty($department_id) && $department_id !== 'all') {
            $sql .= " AND e.department_id = ?";
            $types .= "i";
            $params[] = (int)$department_id;
        }

        $sql .= " ORDER BY e.full_name ASC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get all active departments ordered alphabetically
     */
    public function getAllActiveDepartments()
    {
        $res = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Get tasks for all employees matching the filter criteria
     */
    public function getAllEmployeesTasks(array $filters = [])
    {
        $sql = "SELECT 
                    t.*,
                    e.id AS emp_db_id,
                    e.employee_id AS emp_code,
                    e.full_name AS emp_name,
                    d.id AS dept_id,
                    d.name AS department_name,
                    des.name AS designation_name
                FROM employee_tasks t
                JOIN employees e ON (t.employee_id = e.id OR (e.employee_id != '' AND t.employee_id = e.employee_id))
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN designations des ON e.designation_id = des.id
                WHERE e.deleted_at IS NULL";

        $types = "";
        $params = [];

        // Exclude inactive employees if configured
        $sql .= " AND (e.employee_status IS NULL OR LOWER(e.employee_status) NOT IN ('inactive', 'resigned', 'terminated'))";
        $sql .= " AND e.employee_id IS NOT NULL AND TRIM(e.employee_id) != ''";

        // Filter by Department
        if (!empty($filters['department_id']) && $filters['department_id'] !== 'all') {
            $sql .= " AND e.department_id = ?";
            $types .= "i";
            $params[] = (int)$filters['department_id'];
        }

        // Filter by Employee
        if (!empty($filters['employee_id']) && $filters['employee_id'] !== 'all') {
            $empVal = trim($filters['employee_id']);
            $sql .= " AND (e.id = ? OR e.employee_id = ?)";
            $types .= "ss";
            $params[] = $empVal;
            $params[] = $empVal;
        }

        // Filter by Date Range
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            if (!empty($filters['date_from'])) {
                $sql .= " AND t.work_date >= ?";
                $types .= "s";
                $params[] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $sql .= " AND t.work_date <= ?";
                $types .= "s";
                $params[] = $filters['date_to'];
            }
        } else {
            // Month and Year filter
            $month = $filters['month'] ?? null;
            $year = $filters['year'] ?? null;

            if ($month && preg_match('/^\d{4}-\d{2}$/', $month)) {
                $sql .= " AND DATE_FORMAT(t.work_date, '%Y-%m') = ?";
                $types .= "s";
                $params[] = $month;
            } else {
                $hasYear = !empty($year) && $year !== 'all';
                $hasMonth = !empty($month) && $month !== 'all';

                if ($hasYear && $hasMonth) {
                    $ym = sprintf('%04d-%02d', (int)$year, (int)$month);
                    $sql .= " AND DATE_FORMAT(t.work_date, '%Y-%m') = ?";
                    $types .= "s";
                    $params[] = $ym;
                } elseif ($hasYear && !$hasMonth) {
                    $sql .= " AND YEAR(t.work_date) = ?";
                    $types .= "i";
                    $params[] = (int)$year;
                } elseif (!$hasYear && $hasMonth) {
                    $sql .= " AND MONTH(t.work_date) = ?";
                    $types .= "i";
                    $params[] = (int)$month;
                }
            }
        }

        if (!empty($filters['status'])) {
            $sql .= " AND LOWER(t.status) = ?";
            $types .= "s";
            $params[] = strtolower(trim($filters['status']));
        }

        $sql .= " ORDER BY t.work_date DESC, t.id DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
