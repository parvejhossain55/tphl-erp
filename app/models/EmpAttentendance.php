<?php

class EmpAttentendance extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        return parent::__construct($db);
    }


    public function getByDate($date)
    {
        $sql = "SELECT 
        a.*, 
        e.full_name AS employee_name,
        dept.name AS department_name
    FROM emp_attendances a
    LEFT JOIN employees e ON a.employee_id = e.id
    LEFT JOIN designations d ON e.designation_id = d.id
    LEFT JOIN departments dept ON d.department_id = dept.id
    WHERE a.attendance_date = ?
      AND a.deleted_at IS NULL
    ORDER BY e.full_name ASC";


        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }



   public function create($data)
{
    $punchIn  = !empty($data['punch_in_time']) ? $data['punch_in_time'] : null;
    $punchOut = !empty($data['punch_out_time']) ? $data['punch_out_time'] : null;

    $status = 'Absent'; // default

    if (!empty($punchIn)) {

        $time = date('H:i:s', strtotime($punchIn));

        if ($time > '09:40:00') {
            $status = 'Late';
        } else {
            $status = 'Present';
        }

        if ($time > '13:00:00') {
            $status = 'Half-Day';
        }
    }

    if (!empty($data['today_status']) && $data['today_status'] != 'Present') {
        $status = $data['today_status'];
    }

    $stmt = $this->db->prepare("
        INSERT INTO emp_attendances
        (employee_id, punch_in_time, punch_out_time, attendance_date, today_status, created_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        echo "Prepare failed: " . $this->db->error;
        return false;
    }

    $stmt->bind_param(
        "issssi",
        $data['employee_id'],
        $punchIn,
        $punchOut,
        $data['attendance_date'],
        $status,
        $data['created_by']
    );

    if ($stmt->execute()) {
        try {
            require_once __DIR__ . '/MealRecord.php';
            $mealRecordModel = new MealRecord($this->db);
            $mealRecordModel->syncSingleAttendanceRecord($data['employee_id'], $data['attendance_date'], $status);
        } catch (\Throwable $mErr) {
            error_log("Meal sync on attendance create error: " . $mErr->getMessage());
        }
        return true;
    } else {
        echo "Execute error: " . $stmt->error;
        return false;
    }
}


    // Fetch a single attendance record by ID
    public function find($id)
    {
        $sql = "SELECT a.*, e.full_name AS employee_name
            FROM emp_attendances a
            LEFT JOIN employees e ON a.employee_id = e.id
            WHERE a.id = ?
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // single record
    }

    // Update attendance record
    public function update($id, $data)
    {
        $punchIn  = !empty($data['punch_in_time']) ? $data['punch_in_time'] : null;
        $punchOut = !empty($data['punch_out_time']) ? $data['punch_out_time'] : null;

        $stmt = $this->db->prepare("
        UPDATE emp_attendances
        SET employee_id = ?, punch_in_time = ?, punch_out_time = ?, attendance_date = ?, today_status = ?, created_by = ?
        WHERE id = ?
    ");

        $employeeId     = $data['employee_id'];
        $attendanceDate = $data['attendance_date'];
        $todayStatus    = $data['today_status'];
        $createdBy      = $data['created_by'];

        $stmt->bind_param(
            "issssii",
            $employeeId,
            $punchIn,
            $punchOut,
            $attendanceDate,
            $todayStatus,
            $createdBy,
            $id
        );

        $success = $stmt->execute();
        if ($success) {
            try {
                require_once __DIR__ . '/MealRecord.php';
                $mealRecordModel = new MealRecord($this->db);
                $mealRecordModel->syncSingleAttendanceRecord($employeeId, $attendanceDate, $todayStatus);
            } catch (\Throwable $mErr) {
                error_log("Meal sync on attendance update error: " . $mErr->getMessage());
            }
        }
        return $success;
    }


    public function getByEmployeeAndMonth($employeeId, $month = null)
    {
        $month = $month ?? date('Y-m');
        list($year, $mon) = explode('-', $month);

        $startDate = "$year-$mon-01";
        $endDate   = date('Y-m-t', strtotime($startDate));

        $sql = "SELECT a.*, e.id AS employee_id, e.full_name AS employee_name
            FROM emp_attendances a
            LEFT JOIN employees e ON a.employee_id = e.id
            WHERE a.employee_id = ? AND a.attendance_date BETWEEN ? AND ?
            ORDER BY a.attendance_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iss", $employeeId, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function delete($id)
    {
        // get attendance date
        $stmt = $this->db->prepare("SELECT attendance_date FROM emp_attendances WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if (!$row) {
            return false; // record not found or already deleted
        }

        $attendanceDate = $row['attendance_date'];
        $twoMonthsAgo = date('Y-m-d', strtotime('-2 months'));

        if ($attendanceDate <= $twoMonthsAgo) {
            // Safe delete allowed
            $stmt = $this->db->prepare("DELETE FROM emp_attendances WHERE id = ?");
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        } else {
            // Soft delete: just update deleted_at
            $now = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare("UPDATE emp_attendances SET deleted_at = ? WHERE id = ?");
            $stmt->bind_param("si", $now, $id);
            return $stmt->execute();
        }
    }




    public function getTodaySummary($date = null)
    {
        $date = $date ?? date('Y-m-d');

        $sql = " SELECT 
    COUNT(emp.id) AS total_employee,

    SUM(CASE WHEN a.today_status = 'Present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN a.today_status = 'Late' OR TIME(a.punch_in_time) > '09:40:00' THEN 1 ELSE 0 END) AS late_count,
    SUM(CASE WHEN a.today_status = 'Absent' OR a.punch_in_time IS NULL THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN a.today_status = 'On Leave' THEN 1 ELSE 0 END) AS on_leave_count,
    SUM(CASE WHEN a.today_status = 'Half-Day' THEN 1 ELSE 0 END) AS half_day_count,

    SUM(CASE WHEN wdo.day_off_name = DAYNAME(?) THEN 1 ELSE 0 END) AS day_off_count

FROM employees emp
LEFT JOIN emp_attendances a 
    ON a.employee_id = emp.id 
    AND a.attendance_date = ?

LEFT JOIN emp_weekly_day_off wdo
    ON wdo.employee_id = emp.id
";





        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $date, $date);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        return [
            'Total'   => $result['total_employee'] ?? 0,
            'Present' => $result['present_count'] ?? 0,
            'Late'    => $result['late_count'] ?? 0,
            'Half-Day' => $result['half_day_count'] ?? 0,
            'Absent'  => $result['absent_count'] ?? 0,
            'On Leave' => $result['leave_count'] ?? 0,
            'Day Off' => $result['day_off_count'] ?? 0
        ];
    }
    public function getTodayAttendanceSummary($date = null)
    {
        // Default: today
        $date = $date ?? date('Y-m-d');

        $sql = "SELECT 
                today_status,
                COUNT(*) AS total
            FROM emp_attendances
            WHERE attendance_date = ?
            GROUP BY today_status";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();

        $summary = [
            'Present'   => 0,
            'Absent'    => 0,
            'Half-Day'  => 0,
            'On Leave'  => 0,
            'Holiday'   => 0,
            'Day Off'   => 0,
            'Late'      => 0
        ];

        while ($row = $result->fetch_assoc()) {
            $summary[$row['today_status']] = (int)$row['total'];
        }

        // Total employees today
        $summary['Total'] = array_sum($summary);

        return $summary;
    }

    //for download pdf
    public function getAllForReport($month = null)
    {
        $month = $month ?? date('Y-m');
        $startDate = $month . '-01';
        $endDate   = date('Y-m-t', strtotime($startDate));

        $sql = "SELECT a.*, e.full_name AS employee_name
            FROM emp_attendances a
            LEFT JOIN employees e ON a.employee_id = e.id
            WHERE a.attendance_date BETWEEN ? AND ? AND a.deleted_at IS NULL
            ORDER BY e.full_name ASC, a.attendance_date ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }


    // for chart 
    public function getAttendanceSummaryByPeriod($period = 'month')
    {
        $employees = [];
        $result = [];

        // Employees list
        $empSql = "SELECT id, full_name FROM employees WHERE deleted_at IS NULL";
        $empRes = $this->db->query($empSql);
        while ($row = $empRes->fetch_assoc()) {
            $employees[$row['id']] = $row['full_name'];
        }

        // Date range
        if ($period === 'week') {
            $start = date('Y-m-d', strtotime('monday this week'));
            $end   = date('Y-m-d', strtotime('friday this week'));
        } elseif ($period === 'year') {
            $start = date('Y-01-01');
            $end   = date('Y-12-31');
        } else { // month
            $start = date('Y-m-01');
            $end   = date('Y-m-t');
        }

        $sql = "
        SELECT employee_id,
               SUM(CASE WHEN today_status = 'Present' THEN 1 ELSE 0 END) AS present_days
        FROM emp_attendances
        WHERE attendance_date BETWEEN ? AND ?
          AND today_status != 'Holiday'
          AND deleted_at IS NULL
        GROUP BY employee_id
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($row = $res->fetch_assoc()) {
            $result[] = [
                'employee' => $employees[$row['employee_id']] ?? 'Unknown',
                'present'  => (int) $row['present_days']
            ];
        }

        return $result;
    }

    public function myAttendance($date)
    {
        // Check if employee is logged in
        if (!isset($_SESSION['user']['employee_id'])) {
            return []; // or throw an error / redirect if you prefer
        }

        $employee_id = (int)$_SESSION['user']['employee_id'];

        $sql = "SELECT 
                a.*, 
                e.full_name AS employee_name,
                dept.name AS department_name
            FROM emp_attendances a
            LEFT JOIN employees e ON a.employee_id = e.id
            LEFT JOIN designations d ON e.designation_id = d.id
            LEFT JOIN departments dept ON d.department_id = dept.id
            WHERE a.attendance_date = ?
              AND a.employee_id = ?
              AND a.deleted_at IS NULL
            ORDER BY e.full_name ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $date, $employee_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }



    public function getDailyReport($date)
    {
        $sql = "
    SELECT 
        dept.id AS department_id,
        dept.name AS department_name,
        COUNT(emp.id) AS total_employee,

        SUM(CASE 
            WHEN a.today_status = 'Present' THEN 1 
            ELSE 0 
        END) AS present_count,

        SUM(CASE 
            WHEN a.today_status = 'Late' 
            OR TIME(a.punch_in_time) > '09:10:00' 
        THEN 1 ELSE 0 END) AS late_count,

        SUM(CASE 
            WHEN a.today_status = 'Absent' 
            OR a.punch_in_time IS NULL 
        THEN 1 ELSE 0 END) AS absent_count,

        SUM(CASE 
            WHEN a.today_status = 'On Leave' 
        THEN 1 ELSE 0 END) AS leave_count,

        SUM(CASE 
            WHEN wdo.day_off_name = DAYNAME(?) 
        THEN 1 ELSE 0 END) AS day_off_count

    FROM employees emp

    LEFT JOIN departments dept 
        ON emp.department_id = dept.id

    LEFT JOIN emp_attendances a 
        ON a.employee_id = emp.id 
        AND a.attendance_date = ?

    LEFT JOIN emp_weekly_day_off wdo
        ON wdo.employee_id = emp.id

    GROUP BY dept.id, dept.name
    ORDER BY dept.name ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $date, $date);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getDailyLateReport($date)
    {
        $sql = "
    SELECT 
        emp.id AS emp_id,
        emp.full_name AS emp_name,
        des.name AS designation_name,
        dep.name AS dept_name,
        emp.workplace AS workplace,
        a.today_status,
        TIME(a.punch_in_time) AS punch_in_time
    FROM employees emp
    LEFT JOIN departments dep 
        ON emp.department_id = dep.id
    LEFT JOIN designations des 
        ON emp.designation_id = des.id
    LEFT JOIN emp_attendances a 
        ON a.employee_id = emp.id 
        AND a.attendance_date = ?
    ORDER BY dep.name ASC, emp.full_name ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("s", $date);
        $stmt->execute();

        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
