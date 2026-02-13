<?php
class LeaveController extends Controller
{
    protected $db;
    protected $leaveModel;
    protected $userModel;
    protected $employeeModel;
    protected $leaveTypeModel;
    protected $approvalRequestModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leaveModel = new EmployeeLeave($this->db);
        $this->employeeModel = new Employee($this->db);
        $this->leaveTypeModel = new LeaveType($db);
        $this->approvalRequestModel = new ApprovalRequest($db);
    }
    public function index()
    {


        $leaveData = $this->leaveModel->getAll();
        $leaveSatatusCounts = $this->leaveModel->getLeaveCount();
        $totalLeave = $this->leaveModel->totalLeaveCount();

        $this->view("hrm/emp-leaves/leave-lists", [
            "current_route"   => "leave-lists",
            "page_title"      => "Leave List",
            "leaveData"    => $leaveData,
            "leaveSatatusCounts"     => $leaveSatatusCounts,
            "totalLeave"     => $totalLeave,

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
    header('Content-Type: application/json');

    if (!isset($_SESSION['user']['id'])) {
        echo json_encode(['remaining_days' => 0]);
        exit;
    }

    $employee_id   = (int)($_GET['employee_id'] ?? 0);
    $leave_type_id = (int)($_GET['leave_type_id'] ?? 0);

    if ($employee_id <= 0 || $leave_type_id <= 0) {
        echo json_encode(['remaining_days' => 0]);
        exit;
    }

    $stmt = $this->db->prepare("SELECT total_days, is_paid FROM leave_types WHERE id = ?");
    $stmt->bind_param("i", $leave_type_id);
    $stmt->execute();
    $leaveType = $stmt->get_result()->fetch_assoc();

    if (!$leaveType) {
        echo json_encode(['remaining_days' => 0]);
        exit;
    }

    if ((int)$leaveType['is_paid'] === 0) {
        $remaining = 'N/A';
    } else {
        $remaining = $this->leaveModel
            ->getRemainingLeave($employee_id, $leave_type_id);
    }

    echo json_encode(['remaining_days' => $remaining]);
    exit;
}

   




public function createSelf()
{
    if (!isset($_SESSION['user']['employee_id'])) {
        setError("Unauthorized");
        header("Location: {$GLOBALS['base_url']}/admin-login");
        exit;
    }

    $employee_id = (int)$_SESSION['user']['employee_id'];

    if ($employee_id <= 0) {
        setError("Employee ID not found");
        header("Location: {$GLOBALS['base_url']}/admin-login");
        exit;
    }

    $leaves     = $this->leaveModel->getMyLeaves($employee_id);
    $leaveTypes = $this->leaveTypeModel->getAll();

    $this->view("hrm/emp-leaves/create-self-leave", [
        "current_route" => 'create-self-leave-request',
        "page_title"    => "Request Leave",
        "leaveTypes"    => $leaveTypes,
        "leaves"        => $leaves
    ]);
}

 public function store()
{
    if (!isset($_SESSION['user']['id'])) {
        setError("Unauthorized");
        header("Location: {$GLOBALS['base_url']}/login");
        exit;
    }

    $sessionUser = $_SESSION['user'];
    $roleId      = (int)($sessionUser['role_id'] ?? 0);

    $privilegedRoles = [1, 3, 4]; // Admin, HR, Management

    /**
     * Decide employee_id properly
     */
    if (in_array($roleId, $privilegedRoles, true)) {

        $employee_id = (int)($_POST['employee_id'] ?? 0);

        if ($employee_id <= 0) {
            setError("Employee is required");
            header("Location: {$GLOBALS['base_url']}/create-employee-leave");
            exit;
        }

    } else {

        // 🔐 self leave must use session employee_id only
        $employee_id = (int)($sessionUser['employee_id'] ?? 0);

        if ($employee_id <= 0) {
            setError("Employee ID not found");
            header("Location: {$GLOBALS['base_url']}/create-self-leave-request");
            exit;
        }
    }

    // Validate input
    $leave_type_id  = (int)($_POST['leave_type_id'] ?? 0);
    $start_date     = $_POST['start_date'] ?? '';
    $end_date       = $_POST['end_date'] ?? '';
    $requested_days = (int)($_POST['number_days'] ?? 0);
    $reason         = trim($_POST['reason'] ?? '');

    if (!$leave_type_id || !$start_date || !$end_date || !$requested_days) {
        setError("All fields are required.");
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ($end_date < $start_date) {
        setError("End date cannot be before start date.");
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    /**
     * 🔐 Backend Remaining Leave Validation
     */
    $stmt = $this->db->prepare("SELECT is_paid FROM leave_types WHERE id = ?");
    $stmt->bind_param("i", $leave_type_id);
    $stmt->execute();
    $leaveType = $stmt->get_result()->fetch_assoc();

    if (!$leaveType) {
        setError("Invalid leave type.");
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ((int)$leaveType['is_paid'] === 1) {

        $remaining = $this->leaveModel
            ->getRemainingLeave($employee_id, $leave_type_id);

        if ($requested_days > $remaining) {
            setError("You cannot request more than remaining leave days.");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }

    // Prepare data
    $data = [
        'employee_id'   => $employee_id,
        'leave_type_id' => $leave_type_id,
        'start_date'    => $start_date,
        'end_date'      => $end_date,
        'requested_days'=> $requested_days,
        'approved_days' => 0,
        'approval_status'=> 'pending',
        'reason'        => $reason,
    ];

    $leave_id = $this->leaveModel->create($data);

    if (!$leave_id) {
        setError("Leave create failed.");
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Approval request
    $this->approvalRequestModel->create([
        'department'     => 'HR',
        'request_type'   => 'leave',
        'title'          => 'Leave Approval Request',
        'source_table'   => 'employee_leaves',
        'source_id'      => $leave_id,
        'request_amount' => 0,
        'requested_by'   => $sessionUser['id'], // 🔥 correct user id
        'requested_days' => $requested_days,
        'approve_role'   => 'management',
        'meta_data'      => json_encode([
            'employee_id'   => $employee_id,
            'leave_type_id' => $leave_type_id,
            'requested_days'=> $requested_days,
        ])
    ]);

    setSuccess("Leave request submitted successfully.");

    if (in_array($roleId, $privilegedRoles, true)) {
        header("Location: {$GLOBALS['base_url']}/leave-lists");
    } else {
        header("Location: {$GLOBALS['base_url']}/create-self-leave-request");
    }

    exit;
}





public function remainingDaysSelf()
{
    header('Content-Type: application/json');

    if (!isset($_SESSION['user']['employee_id'])) {
        echo json_encode(['remaining_days' => 0]);
        exit;
    }

    $employee_id   = (int)$_SESSION['user']['employee_id'];
    $leave_type_id = (int)($_GET['leave_type_id'] ?? 0);

    if ($employee_id <= 0 || $leave_type_id <= 0) {
        echo json_encode(['remaining_days' => 0]);
        exit;
    }

    $stmt = $this->db->prepare("SELECT total_days, is_paid FROM leave_types WHERE id = ?");
    $stmt->bind_param("i", $leave_type_id);
    $stmt->execute();
    $leaveType = $stmt->get_result()->fetch_assoc();

    if (!$leaveType) {
        echo json_encode(['remaining_days' => 0]);
        exit;
    }

    if ((int)$leaveType['is_paid'] === 0) {
        $remaining = 'N/A';
    } else {
        $remaining = $this->leaveModel
            ->getRemainingLeave($employee_id, $leave_type_id);
    }

    echo json_encode(['remaining_days' => $remaining]);
    exit;
}

}
