<?php
class EmpAttendanceController extends Controller
{
    protected $db;
    protected $EmpAttendanceModel;
    protected $employeeModel;
    public function __construct($db)
    {
        $this->db = $db;
        $this->EmpAttendanceModel = new EmpAttentendance($this->db);
        $this->employeeModel = new Employee($this->db);
    }
    public function index()
    {
        // Get all designations with department names (no pagination)
        $attendances = $this->EmpAttendanceModel->getAll();
        // Get paginated departments
        $employees = $this->employeeModel->getAll();
        $this->view("hrm/attendances/attendance-lists", [
            "current_route"   => "attendance-list",
            "page_title"      => "Attendance List",
            "employees"    => $employees,
            "attendances"     => $attendances,
        ]);
    }

        public function create()
    {
        $employees = $this->employeeModel ;
        //         $flatCounts = $this->flatModel->getFlatStatusCount();
        $this->view("hrm/attendances/create-attendance", [
            "current_route"   => "create-attendance",
            "active"          => "create-attendance",
            "page_title"      => "Add Flat",
            "show_breadcrumb" => true,
            "employees"       => $employees,
        ]);
    }


    // Handle form submission
    public function store()
    {
        $data = [
            'employee_id'   => $_POST['employee_id'] ?? '',
            'punch_in_time'   => $_POST['punch_in_time'] ?? '',
            'punch_out_time'   => $_POST['punch_out_time'] ?? '',
            'attendance_date'   => $_POST['attendance_date'] ?? '',
            'today_status'   => $_POST['today_status'] ?? '',
            'created_by'      => $_SESSION['user']['id'] ?? null,

        ];

        if ($this->EmpAttendanceModel->create($data)) {
            setSuccess("Designation created successfully.");
            header("Location: {$GLOBALS['base_url']}/designations");
            exit;
        } else {
            setError("Failed to create designation.");
            echo "<h3 style='color:red'>Failed to create designation</h3>";
        }
    }


    public function update()
    {

        $id = $_POST['id'];
        $name = $_POST['name'];
        $department_id = $_POST['department_id'];

        $updated = $this->EmpAttendanceModel->update($id, $name, $department_id);

        if ($updated) {
            setSuccess("Updated successfully.");
            header("Location: {$GLOBALS['base_url']}/designations");
        } else {
            setError("Update failed!");
        }
        exit;
    }




    public function show()
    {
        $this->view("tasks/view-task", [
            "current_route" => "show-task",
            "active" => "show-task",
            "psge_title" => "Show Task",
            "show_breadcrumb" => true,
        ]);
    }

    public function delete()
    {
        $id = $_POST['id'] ?? 0;

        if ($id && $this->EmpAttendanceModel->delete($id)) {
            header("Location: {$GLOBALS['base_url']}/designations");
            exit;
        } else {
            echo "<h3 style='color:red'>Failed to delete department</h3>";
        }
    }
}
