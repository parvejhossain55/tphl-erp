<?php

class ShiftController extends Controller
{
    protected $db;
    protected $shiftModel;
    protected $employeeModel;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        require_once dirname(__DIR__) . '/models/Shift.php';
        require_once dirname(__DIR__) . '/models/Employee.php';

        $this->shiftModel = new Shift($this->db);
        $this->employeeModel = new Employee($this->db);
    }

    /**
     * View all shifts & assignments
     */
    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $shifts = $this->shiftModel->getAll();

        // Fetch departments for department-level shift assignment
        $departments = [];
        $dRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        if ($dRes) {
            $departments = $dRes->fetch_all(MYSQLI_ASSOC);
        }

        // Fetch employees list with their current shift
        $employeesSql = "
            SELECT 
                e.id, 
                e.employee_id, 
                e.full_name, 
                dept.name AS department_name, 
                desg.name AS designation_name,
                s.name AS shift_name,
                s.start_time,
                s.end_time,
                e.shift_id
            FROM employees e
            LEFT JOIN departments dept ON e.department_id = dept.id
            LEFT JOIN designations desg ON e.designation_id = desg.id
            LEFT JOIN shifts s ON e.shift_id = s.id
            WHERE e.deleted_at IS NULL
            ORDER BY e.full_name ASC
        ";
        $empRes = $this->db->query($employeesSql);
        $employees = $empRes ? $empRes->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('hrm/shifts/shift-lists', [
            'current_route' => 'shift-lists',
            'page_title'    => 'Shift Management',
            'shifts'        => $shifts,
            'departments'   => $departments,
            'employees'     => $employees
        ]);
    }

    /**
     * Store new shift
     */
    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $graceMinutes = (int)($_POST['grace_minutes'] ?? 10);
        $halfDayTime = trim($_POST['half_day_threshold_time'] ?? '13:00:00');

        if (empty($name) || empty($startTime) || empty($endTime)) {
            $_SESSION['error'] = 'Shift name, start time, and end time are required.';
            header("Location: {$GLOBALS['base_url']}/shift-lists");
            exit;
        }

        $data = [
            'name'                    => $name,
            'start_time'              => strlen($startTime) === 5 ? $startTime . ':00' : $startTime,
            'end_time'                => strlen($endTime) === 5 ? $endTime . ':00' : $endTime,
            'grace_minutes'           => $graceMinutes,
            'half_day_threshold_time' => strlen($halfDayTime) === 5 ? $halfDayTime . ':00' : $halfDayTime,
            'status'                  => 1
        ];

        if ($this->shiftModel->create($data)) {
            $_SESSION['success'] = 'Shift created successfully.';
        } else {
            $_SESSION['error'] = 'Failed to create shift.';
        }

        header("Location: {$GLOBALS['base_url']}/shift-lists");
        exit;
    }

    /**
     * Update existing shift
     */
    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)$id;

        $name = trim($_POST['name'] ?? '');
        $startTime = trim($_POST['start_time'] ?? '');
        $endTime = trim($_POST['end_time'] ?? '');
        $graceMinutes = (int)($_POST['grace_minutes'] ?? 10);
        $halfDayTime = trim($_POST['half_day_threshold_time'] ?? '13:00:00');

        if (empty($name) || empty($startTime) || empty($endTime)) {
            $_SESSION['error'] = 'Shift name, start time, and end time are required.';
            header("Location: {$GLOBALS['base_url']}/shift-lists");
            exit;
        }

        $data = [
            'name'                    => $name,
            'start_time'              => strlen($startTime) === 5 ? $startTime . ':00' : $startTime,
            'end_time'                => strlen($endTime) === 5 ? $endTime . ':00' : $endTime,
            'grace_minutes'           => $graceMinutes,
            'half_day_threshold_time' => strlen($halfDayTime) === 5 ? $halfDayTime . ':00' : $halfDayTime,
            'status'                  => (int)($_POST['status'] ?? 1)
        ];

        if ($this->shiftModel->update($id, $data)) {
            $_SESSION['success'] = 'Shift updated successfully.';
        } else {
            $_SESSION['error'] = 'Failed to update shift.';
        }

        header("Location: {$GLOBALS['base_url']}/shift-lists");
        exit;
    }

    /**
     * Delete shift
     */
    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $id = (int)$id;

        $res = $this->shiftModel->delete($id);
        if ($res['success']) {
            $_SESSION['success'] = $res['message'];
        } else {
            $_SESSION['error'] = $res['message'];
        }

        header("Location: {$GLOBALS['base_url']}/shift-lists");
        exit;
    }

    /**
     * Assign shift to an employee or entire department
     */
    public function assignShift()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $assignType = $_POST['assign_type'] ?? 'employee'; // employee or department
        $shiftId = (int)($_POST['shift_id'] ?? 0);

        if ($shiftId <= 0) {
            $_SESSION['error'] = 'Please select a valid shift.';
            header("Location: {$GLOBALS['base_url']}/shift-lists");
            exit;
        }

        if ($assignType === 'department') {
            $deptId = (int)($_POST['department_id'] ?? 0);
            if ($deptId <= 0) {
                $_SESSION['error'] = 'Please select a valid department.';
                header("Location: {$GLOBALS['base_url']}/shift-lists");
                exit;
            }
            $this->shiftModel->assignDepartmentShift($deptId, $shiftId);
            $_SESSION['success'] = 'Shift assigned to department successfully.';
        } else {
            $empId = (int)($_POST['employee_id'] ?? 0);
            if ($empId <= 0) {
                $_SESSION['error'] = 'Please select a valid employee.';
                header("Location: {$GLOBALS['base_url']}/shift-lists");
                exit;
            }
            $this->shiftModel->assignEmployeeShift($empId, $shiftId);
            $_SESSION['success'] = 'Shift assigned to employee successfully.';
        }

        header("Location: {$GLOBALS['base_url']}/shift-lists");
        exit;
    }
}
