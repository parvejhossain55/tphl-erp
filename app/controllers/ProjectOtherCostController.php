<?php
class ProjectOtherCostController extends Controller
{
    protected $db;
    protected $projectModel;
    protected $otherCostModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->projectModel = new Project($this->db);
        $this->otherCostModel = new ProjectOtherCost($this->db);
    }

    public function index()
    {
        $userId = $_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        $myRequests = $this->otherCostModel->getByUserId($userId);
        
        $approvalRequests = [];
        
        // Approver roles: QC(13, 24, 25), Management(3, 33, 32, 15, 22), Accounts(6, 30), Admin(1)
        $approverRoles = [1, 3, 6, 13, 15, 20, 22, 24, 25, 30, 32, 33];
        
        if (in_array($roleId, $approverRoles)) {
            // Show all requests to these roles so they can track the status
            $approvalRequests = $this->otherCostModel->getAll();
        }

        $userRole = $roleId;
        
        // Allowed roles to see all under_construction projects:
        // 1(Admin), 3(Management), 33(Chief Officer), 17(Purchase Head), 22(PM Head), 23(Purchase Exec), 31(Sr Purchase Exec), 
        // 6(Accounts), 30(Accounts Head), 24(QC Head), 25(QC Exec), 32(Admin Operation)
        if (in_array($userRole, [1, 3, 33, 17, 22, 23, 31, 6, 30, 24, 25, 32])) {
            $pResult  = $this->db->query("SELECT id, name, under_construction FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC");
            $projects = $pResult ? $pResult->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            // Other construction roles (e.g. Site Engineer, Project Manager, Site Operator) → any project they are assigned to
            $pStmt = $this->db->prepare("
                SELECT id, name, under_construction FROM projects 
                WHERE deleted_at IS NULL AND under_construction = 1
                AND (
                    purchase_user_id     = ?
                    OR pm_id             = ?
                    OR site_engineer_id  = ?
                    OR landman_id        = ?
                    OR struct_engineer_id= ?
                    OR operation_head_id = ?
                    OR draftsman_id      = ?
                    OR architect_id      = ?
                    OR estimator_id      = ?
                    OR qc_id            = ?
                    OR operator_id      = ?
                    OR created_by       = ?
                    OR is_common        = 1
                )
                ORDER BY name ASC
            ");
            $pStmt->bind_param('iiiiiiiiiiii',
                $userId, $userId, $userId, $userId,
                $userId, $userId, $userId, $userId,
                $userId, $userId, $userId, $userId
            );
            $pStmt->execute();
            $projects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('project-other-costs/index', [
            'myRequests' => $myRequests,
            'approvalRequests' => $approvalRequests,
            'roleId' => $roleId,
            'projects' => $projects
        ]);
    }

    public function create()
    {
        $userId = $_SESSION['user']['id'];
        $userRole = (int)$_SESSION['user']['role_id'];
        
        if (in_array($userRole, [1, 3, 33, 17, 22, 23, 31, 6, 30, 24, 25, 32])) {
            $pResult  = $this->db->query("SELECT id, name, under_construction FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC");
            $projects = $pResult ? $pResult->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $pStmt = $this->db->prepare("
                SELECT id, name, under_construction FROM projects 
                WHERE deleted_at IS NULL AND under_construction = 1
                AND (
                    purchase_user_id     = ?
                    OR pm_id             = ?
                    OR site_engineer_id  = ?
                    OR landman_id        = ?
                    OR struct_engineer_id= ?
                    OR operation_head_id = ?
                    OR draftsman_id      = ?
                    OR architect_id      = ?
                    OR estimator_id      = ?
                    OR qc_id            = ?
                    OR operator_id      = ?
                    OR created_by       = ?
                    OR is_common        = 1
                )
                ORDER BY name ASC
            ");
            $pStmt->bind_param('iiiiiiiiiiii',
                $userId, $userId, $userId, $userId,
                $userId, $userId, $userId, $userId,
                $userId, $userId, $userId, $userId
            );
            $pStmt->execute();
            $projects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('project-other-costs/create', [
            'projects' => $projects
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'];
            
            $empStmt = $this->db->prepare("SELECT reporting_user_id FROM employees WHERE user_id = ? AND deleted_at IS NULL");
            $empStmt->bind_param('i', $userId);
            $empStmt->execute();
            $empResult = $empStmt->get_result()->fetch_assoc();
            $reporting_user_id = !empty($empResult['reporting_user_id']) ? $empResult['reporting_user_id'] : null;
            $dept_head_status = $reporting_user_id ? 'pending' : 'not_required';

            $data = [
                'project_id' => $_POST['project_id'],
                'rcost_type' => $_POST['rcost_type'],
                'purpose' => $_POST['purpose'],
                'request_amount' => $_POST['request_amount'],
                'created_by_id' => $userId,
                'dept_head_status' => $dept_head_status
            ];

            if (isset($_FILES['creator_attachment']) && $_FILES['creator_attachment']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/project_other_costs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_creator_' . basename($_FILES['creator_attachment']['name']);
                $targetFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['creator_attachment']['tmp_name'], $targetFilePath)) {
                    $data['attachment'] = $fileName;
                }
            }

            $newId = $this->otherCostModel->create($data);
            if ($newId) {
                $this->syncToApprovalRequests($newId);
                setSuccess("Other cost request created successfully.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            } else {
                setError("Failed to create request.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/create");
                exit();
            }
        }
    }

    public function approve_dept_head($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $status = $_POST['dept_head_status'] ?? 'approved';
            $data = [
                'dept_head_status' => $status,
                'dept_head_approval_note' => $_POST['dept_head_approval_note'] ?? null,
                'dept_head_approved_by' => $_SESSION['user']['id'],
                'dept_head_approved_at' => date('Y-m-d H:i:s'),
            ];

            if ($status === 'rejected') {
                $data['status'] = 'rejected';
            }

            if ($this->otherCostModel->update($id, $data)) {
                $this->syncToApprovalRequests($id);
                setSuccess("Request Dept Head " . ucfirst($status) . " successfully.");
            } else {
                setError("Failed to process request.");
            }
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit();
        }
    }

    public function approve_qc($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'approved_amount' => $_POST['approved_amount'],
                'qc_audit_note' => $_POST['qc_audit_note'],
                'qc_audit_approved_id' => $_SESSION['user']['id'],
                'qc_audit_approved_at' => date('Y-m-d H:i:s'),
                'status' => 'qc_approved'
            ];

            if ($this->otherCostModel->update($id, $data)) {
                $this->syncToApprovalRequests($id);
                setSuccess("Request QC Approved successfully.");
            } else {
                setError("Failed to approve request.");
            }
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit();
        }
    }

    public function approve_management($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'approved_amount' => $_POST['approved_amount'],
                'management_apporved_note' => $_POST['management_apporved_note'],
                'management_apporved_by_id' => $_SESSION['user']['id'],
                'management_apporved_at' => date('Y-m-d H:i:s'),
                'pc_approved_id' => $_SESSION['user']['id'],
                'status' => 'management_approved'
            ];

            if ($this->otherCostModel->update($id, $data)) {
                $this->syncToApprovalRequests($id);
                setSuccess("Request Management Approved successfully.");
            } else {
                setError("Failed to approve request.");
            }
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit();
        }
    }

    public function pay($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $paymentAmount = $_POST['payment_amount'] ?? 0;
            $paymentAt = $_POST['payment_at'] ?? date('Y-m-d H:i:s');
            
            $data = [
                'payment_by_id' => $_SESSION['user']['id'],
                'payment_at' => $paymentAt,
                'payment_amount' => $paymentAmount,
                'status' => 'paid'
            ];

            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/project_other_costs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . basename($_FILES['attachment']['name']);
                $targetFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFilePath)) {
                    $data['attachment'] = $fileName;
                }
            }

            if ($this->otherCostModel->update($id, $data)) {
                $this->syncToApprovalRequests($id);
                setSuccess("Request Paid successfully.");
            } else {
                setError("Failed to pay request.");
            }
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit();
        }
    }

    public function destroy($id)
    {
        $request = $this->otherCostModel->getById($id);
        if (!$request) {
            setError("Request not found.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit();
        }

        // Only allow deletion if the request belongs to the user and is not fully approved (not paid, not management_approved)
        $userId = $_SESSION['user']['id'];
        if ($request['created_by_id'] != $userId) {
            setError("You don't have permission to delete this request.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit();
        }

        if (in_array($request['status'], ['pending', 'qc_approved'])) {
            if ($this->otherCostModel->delete($id)) {
                $this->db->query("DELETE FROM approval_requests WHERE source_table = 'project_other_costs' AND source_id = $id");
                setSuccess("Request deleted successfully.");
            } else {
                setError("Failed to delete request.");
            }
        } else {
            setError("Cannot delete this request as it has already been approved by management.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
        exit();
    }
    private function syncToApprovalRequests($reqId)
    {
        $res = $this->db->query("
            SELECT r.*, p.name as project_name
            FROM project_other_costs r
            LEFT JOIN projects p ON r.project_id = p.id
            WHERE r.id = $reqId
            LIMIT 1
        ");
        if (!$res || $res->num_rows == 0) {
            $this->db->query("DELETE FROM approval_requests WHERE source_table = 'project_other_costs' AND source_id = $reqId");
            return;
        }
        $row = $res->fetch_assoc();

        $check = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'project_other_costs' AND source_id = $reqId");
        
        $deptName = 'construction';
        $requestType = $row['rcost_type'];
        $title = "Project Other Cost: " . (mb_strlen($row['purpose']) > 80 ? mb_substr($row['purpose'], 0, 80) . '...' : $row['purpose']);
        $amount = (float)$row['request_amount'];
        
        $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : null;
        $requestedBy = (int)$row['created_by_id'];
        $status = 'pending'; // Default for approval_requests
        $approveRole = 'qc';
        $approvedBy = null;
        $approvedAt = null;
        $approvalNote = null;

        if ($row['status'] == 'pending') {
            if ($row['dept_head_status'] === 'pending') {
                $status = 'pending';
                // Lookup dept head
                $checkDept = $this->db->query("SELECT reporting_user_id FROM employees WHERE user_id = " . (int)$row['created_by_id'] . " AND deleted_at IS NULL");
                $deptHeadId = null;
                if ($checkDept && $checkDept->num_rows > 0) {
                    $dhRow = $checkDept->fetch_assoc();
                    $deptHeadId = $dhRow['reporting_user_id'];
                }
                $approveRole = 'dept_head_' . $deptHeadId;
            } else {
                $status = 'pending';
                $approveRole = 'qc';
                if ($row['dept_head_status'] === 'approved') {
                    $approvedBy = $row['dept_head_approved_by'];
                    $approvedAt = $row['dept_head_approved_at'];
                    $approvalNote = $row['dept_head_approval_note'];
                }
            }
        } elseif ($row['status'] == 'qc_approved') {
            $status = 'pending';
            $approveRole = 'management';
            $approvedBy = $row['qc_audit_approved_id'];
            $approvedAt = $row['updated_at'];
            $approvalNote = $row['qc_audit_note'];
        } elseif ($row['status'] == 'management_approved') {
            $status = 'approved';
            $approveRole = 'accounts';
            $approvedBy = $row['management_apporved_by_id'];
            $approvedAt = $row['updated_at'];
            $approvalNote = $row['management_apporved_note'];
        } elseif ($row['status'] == 'paid') {
            $status = 'approved';
            $approveRole = 'completed';
            $approvedBy = $row['management_apporved_by_id'];
            $approvedAt = $row['updated_at'];
            $approvalNote = $row['management_apporved_note'];
        }

        $metaData = json_encode(['purpose' => $row['purpose'], 'project_id' => $row['project_id'], 'project_name' => $row['project_name']]);
        $createdAt = $row['created_at'];

        if ($check && $check->num_rows == 0) {
            $stmt = $this->db->prepare("
                INSERT INTO approval_requests 
                (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                VALUES (?, ?, ?, 'project_other_costs', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("sssdddisisssss", $deptName, $requestType, $title, $reqId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $this->db->prepare("
                UPDATE approval_requests 
                SET department = ?, request_type = ?, title = ?, request_amount = ?, approved_amount = ?, approve_role = ?, status = ?, approved_by = ?, approved_at = ?, approval_note = ?
                WHERE source_table = 'project_other_costs' AND source_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("sssddssissi", $deptName, $requestType, $title, $amount, $approvedAmount, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $reqId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    public function review($id)
    {
        $userId = $_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        $res = $this->db->query("
            SELECT r.*, p.name as project_name, u.full_name as creator_name,
                   dh.full_name as dh_name,
                   qc.full_name as qc_name, m.full_name as mgmt_name, pa.full_name as pay_name
            FROM project_other_costs r
            LEFT JOIN projects p ON r.project_id = p.id
            LEFT JOIN users u ON r.created_by_id = u.id
            LEFT JOIN users dh ON r.dept_head_approved_by = dh.id
            LEFT JOIN users qc ON r.qc_audit_approved_id = qc.id
            LEFT JOIN users m ON r.management_apporved_by_id = m.id
            LEFT JOIN users pa ON r.payment_by_id = pa.id
            WHERE r.id = $id
        ");
        if (!$res || $res->num_rows == 0) {
            setError("Request not found.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit;
        }
        $request = $res->fetch_assoc();

        $this->view('project-other-costs/review', [
            'request' => $request,
            'roleId' => $roleId
        ]);
    }
}
