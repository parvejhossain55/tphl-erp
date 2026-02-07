<?php
class LeaveController extends Controller
{
    protected $db;
    protected $leaveModel;
    protected $userModel;
    protected $leaveTypeModel;
    protected $approvalRequestModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leaveModel = new EmployeeLeave($this->db);
        $this->userModel = new User($this->db);
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

        $users = $this->userModel->getAll();
        $leaveTypes = $this->leaveTypeModel->getAll();
        $this->view("hrm/emp-leaves/create-emp-leave", [
            "current_route"   => "create-employee-leave",
            "active"          => "create-employee-leave",
            "page_title"      => "Add Leave",
            "show_breadcrumb" => true,
            "users" => $users,
            "leaveTypes" => $leaveTypes,

            // "assignedUsers" => $assignedUsers,
            // "sources" => $sources,

        ]);
    }


    //     // Handle form submission
    public function remainingDays()
    {
        $user_id = $_GET['user_id'] ?? 0;
        $leave_type_id = $_GET['leave_type_id'] ?? 0;

        if ($user_id == 0 || $leave_type_id == 0) {
            echo json_encode(['remaining_days' => 0]);
            exit;
        }

        // Check if leave type is paid
        $stmt = $this->db->prepare("SELECT total_days, is_paid FROM leave_types WHERE id = ?");
        $stmt->bind_param("i", $leave_type_id);
        $stmt->execute();
        $leaveType = $stmt->get_result()->fetch_assoc();

        if (!$leaveType) {
            echo json_encode(['remaining_days' => 0]);
            exit;
        }

        if ($leaveType['is_paid'] == 0) {
            $remaining = 'N/A'; // unpaid leave
        } else {
            $remaining = $this->leaveModel->getRemainingLeave($user_id, $leave_type_id);
        }

        header('Content-Type: application/json');
        echo json_encode(['remaining_days' => $remaining]);
        exit;
    }

    //     public function store()
    //     {
    //         $data = [
    //             'user_id'   => $_POST['user_id'] ?? '',
    //             'leave_type_id' => $_POST['leave_type_id'] ?? '',
    //             'start_date'    => $_POST['start_date'] ?? '',
    //             'end_date'      => $_POST['end_date'] ?? '',
    //             'requested_days' => $_POST['number_days'] ?? 0,
    //             'approved_days' => 0,
    //             'approval_status' => 'pending',
    //             'reason'        => $_POST['reason'] ?? '',
    //         ];
    // //    echo "<pre>";
    // //     print_r($data);
    // //     echo "</pre>";
    // //     exit; 
    //         // 1. Create leave
    //         $leave_id = $this->leaveModel->create($data);

    //         if (!$leave_id) {
    //             setError("Failed to create leave.");
    //             header("Location: {$GLOBALS['base_url']}/create-employee-leave");
    //             exit;
    //         }



    //         // 2. Create approval request for management
    //         $approvalData = [
    //             'department'     => 'HR',
    //             'request_type'   => 'leave',
    //             'title'          => 'Leave Approval Request',
    //             'source_table'   => 'employee_leaves',
    //             'source_id'      => $leave_id,
    //             'request_amount' => 0, // leave amount not needed
    //             'requested_by'   => $_SESSION['user']['id'] ?? 0,
    //             'requested_days'   =>  $_POST['number_days'] ?? 0,
    //             'approve_role'   => 'management',
    //             'meta_data' => json_encode([
    //                 'user_id' => $data['user_id'],
    //                 'leave_type_id' => $data['leave_type_id'],
    //                 'requested_days' => $data['requested_days'],
    //             ])

    //         ];

    //         $this->approvalRequestModel->create($approvalData);

    //         setSuccess("Leave created successfully and sent for approval.");
    //         header("Location: {$GLOBALS['base_url']}/leave-lists");
    //         exit;
    //     }







    public function createSelf()
    {
        if (!isset($_SESSION['user']['id'])) {
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }
        $user_id = $_SESSION['user']['id'];

        $leaves = $this->leaveModel->getMyLeaves($user_id);
        $leaveTypes = $this->leaveTypeModel->getAll();

        $this->view("hrm/emp-leaves/create-self-leave", [
            "current_route" => 'create-self-leave-request',
            "page_title" => "Request Leave",
            "leaveTypes" => $leaveTypes,
            "leaves"     => $leaves
        ]);
    }
    public function store()
    {
        if (!isset($_SESSION['user']['id'])) {
            setError("Unauthorized");
            header("Location: {$GLOBALS['base_url']}/login");
            exit;
        }

        $sessionUser   = $_SESSION['user'];
        $sessionUserId = (int)$sessionUser['id'];
        $roleId        = (int)($sessionUser['role_id'] ?? 0);

        // Roles who can create leave for others
        $privilegedRoles = [1, 3, 5]; // Admin, HR, Management

        /**
         * Decide user_id
         * - HR/Admin/Management → from form
         * - Employee → from session (self)
         */
        if (in_array($roleId, $privilegedRoles, true)) {
            $user_id = (int)($_POST['user_id'] ?? 0);

            if ($user_id === 0) {
                setError("Employee is required");
                header("Location: {$GLOBALS['base_url']}/create-employee-leave");
                exit;
            }
        } else {
            $user_id = $sessionUserId; // 🔐 force self leave
        }

        // Leave data
        $data = [
            'user_id'         => $user_id,
            'leave_type_id'   => $_POST['leave_type_id'] ?? '',
            'start_date'      => $_POST['start_date'] ?? '',
            'end_date'        => $_POST['end_date'] ?? '',
            'requested_days'  => (int)($_POST['number_days'] ?? 0),
            'approved_days'   => 0,
            'approval_status' => 'pending',
            'reason'          => $_POST['reason'] ?? '',
        ];

        $leave_id = $this->leaveModel->create($data);

        if (!$leave_id) {
            setError("Leave create failed");
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Create approval request
        $this->approvalRequestModel->create([
            'department'     => 'HR',
            'request_type'   => 'leave',
            'title'          => 'Leave Approval Request',
            'source_table'   => 'employee_leaves',
            'source_id'      => $leave_id,
            'request_amount' => 0,
            'requested_by'   => $sessionUserId,
            'requested_days' => $data['requested_days'],
            'approve_role'   => 'management',
            'meta_data' => json_encode([
                'user_id'        => $user_id,
                'leave_type_id'  => $data['leave_type_id'],
                'requested_days' => $data['requested_days'],
            ])

        ]);

        setSuccess("Leave request submitted successfully.");

        // Redirect based on role
        if (in_array($roleId, $privilegedRoles, true)) {
            header("Location: {$GLOBALS['base_url']}/leave-lists");
        } else {
            header("Location: {$GLOBALS['base_url']}/create-self-leave-request");
        }

        exit;
    }




    public function remainingDaysSelf()
    {
        $user_id = $_SESSION['user']['id'] ?? 0;
        $leave_type_id = $_GET['leave_type_id'] ?? 0;

        if ($user_id == 0 || $leave_type_id == 0) {
            echo json_encode(['remaining_days' => 0]);
            exit;
        }

        $stmt = $this->db->prepare("SELECT total_days, is_paid FROM leave_types WHERE id = ?");
        $stmt->bind_param("i", $leave_type_id);
        $stmt->execute();
        $leaveType = $stmt->get_result()->fetch_assoc();

        if ($leaveType['is_paid'] == 0) {
            $remaining = 'N/A';
        } else {
            $remaining = $this->leaveModel->getRemainingLeave($user_id, $leave_type_id);
        }

        echo json_encode(['remaining_days' => $remaining]);
        exit;
    }
}
