<?php
class LeaveController extends Controller
{
    protected $db;
    protected $leaveModel;
    protected $employeeModel;
    protected $leaveTypeModel;
    protected $approvalRequestModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leaveModel = new EmployeeLeave($this->db);
        $this->employeeModel = new Employee();
        $this->leaveTypeModel = new LeaveType($db);
        $this->approvalRequestModel = new ApprovalRequest($db);
    }
    public function index()
    {
        // Get all designations with department names (no pagination)
        // $designations = $this->designationModel->getAll();


        // // Get paginated departments
        // $departments = $this->deparmentModel->getAll();
        $leaveData = $this->leaveModel->getAll();
        $this->view("hrm/emp-leaves/leave-lists", [
            "current_route"   => "leave-lists",
            "page_title"      => "Leave List",
            "leaveData"    => $leaveData,
            // "departments"     => $departments,

        ]);
    }

    public function create()
    {
        // $assignedUsers = $this->userModel->getAll();
        // $sources = $this->leadSource->getAll();

        $employees = $this->employeeModel->getAll();
        $leaveTypes = $this->leaveTypeModel->getAll();
        $this->view("hrm/emp-leaves/create-emp-leave", [
            "current_route"   => "create-employee-leave",
            "active"          => "create-employee-leave",
            "page_title"      => "Add Leave",
            "show_breadcrumb" => true,
            "employees" => $employees,
            "leaveTypes" => $leaveTypes,

            // "assignedUsers" => $assignedUsers,
            // "sources" => $sources,

        ]);
    }


    //     // Handle form submission
    public function remainingDays()
    {
        $employee_id = $_GET['employee_id'] ?? 0;
        $leave_type_id = $_GET['leave_type_id'] ?? 0;

        if ($leave_type_id == 0 || $employee_id == 0) {
            echo json_encode(['remaining_days' => 0]);
            exit;
        }

        // Check if unpaid
        $stmt = $this->db->prepare("SELECT total_days, is_paid FROM leave_types WHERE id = ?");
        $stmt->bind_param("i", $leave_type_id);
        $stmt->execute();
        $lt = $stmt->get_result()->fetch_assoc();

        if (!$lt) {
            echo json_encode(['remaining_days' => 0]);
            exit;
        }

        if ($lt['is_paid'] == 0) {
            $remaining = 'N/A'; // unpaid leave
        } else {
            $remaining = $this->leaveModel->getRemainingLeave($employee_id, $leave_type_id);
        }

        header('Content-Type: application/json');
        echo json_encode(['remaining_days' => $remaining]);
        exit;
    }


    /**
     * Store employee leave
     */
    public function store()
    {
        $data = [
            'employee_id'   => $_POST['employee_id'] ?? '',
            'leave_type_id' => $_POST['leave_type_id'] ?? '',
            'start_date'    => $_POST['start_date'] ?? '',
            'end_date'      => $_POST['end_date'] ?? '',
            'requested_days' => $_POST['number_days'] ?? 0,
            'approved_days' => 0,
            'approval_status' => 'pending',
            'reason'        => $_POST['reason'] ?? '',
        ];

        // 1. Create leave
        $leave_id = $this->leaveModel->create($data);

        if (!$leave_id) {
            setError("Failed to create leave.");
            header("Location: {$GLOBALS['base_url']}/create-employee-leave");
            exit;
        }

        // 2. Create approval request for management
        $approvalData = [
            'department'     => 'HR',
            'request_type'   => 'leave',
            'title'          => 'Leave Approval Request',
            'source_table'   => 'employee_leaves',
            'source_id'      => $leave_id,
            'request_amount' => 0, // leave amount not needed
            'requested_by'   => $_SESSION['user']['id'] ?? 0,
            'requested_days'   =>  $_POST['number_days'] ?? 0,
            'approve_role'   => 'management',
            'meta_data' => json_encode([
                'employee_id' => $data['employee_id'],
                'leave_type_id' => $data['leave_type_id'],
                'requested_days' => $data['requested_days'],
            ])

        ];

        $this->approvalRequestModel->create($approvalData);

        setSuccess("Leave created successfully and sent for approval.");
        header("Location: {$GLOBALS['base_url']}/leave-lists");
        exit;
    }



    // public function update()
    // {

    //         $id = $_POST['id'];
    //         $name = $_POST['name'];
    //         $department_id = $_POST['department_id'];

    //    $updated = $this->designationModel->update($id, $name, $department_id);

    //         if ($updated) {
    //             setSuccess("Updated successfully.");
    //              header("Location: {$GLOBALS['base_url']}/designations");
    //         } else {
    //             setError("Update failed!");
    //         }
    //         exit;

    // }




    //     public function show()
    //     {
    //         $this->view("tasks/view-task", [
    //             "current_route" => "show-task",
    //             "active" => "show-task",
    //             "psge_title" => "Show Task",
    //             "show_breadcrumb" => true,
    //         ]);
    //     }

    //     public function delete()
    // {
    //     $id = $_POST['id'] ?? 0;

    //     if ($id && $this->designationModel->delete($id)) {
    //         header("Location: {$GLOBALS['base_url']}/designations");
    //         exit;
    //     } else {
    //         echo "<h3 style='color:red'>Failed to delete department</h3>";
    //     }
    // }





}
