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
        
        // Approver roles: QC(13, 24, 25), Management(3, 33, 34, 35, 32, 15, 22), Accounts(6, 30), Admin(1), Land Manager(14)
        $approverRoles = [1, 3, 6, 13, 14, 15, 20, 22, 24, 25, 30, 32, 33, 34, 35, 38];
        
        // Check if user is a designated reporting boss for any employees
        $isReportingBoss = false;
        $bossCheck = $this->db->query("SELECT 1 FROM employees WHERE reporting_user_id = $userId AND deleted_at IS NULL LIMIT 1");
        if ($bossCheck && $bossCheck->num_rows > 0) {
            $isReportingBoss = true;
        }

        if (in_array($roleId, $approverRoles)) {
            // Show all requests to these roles so they can track the status
            $approvalRequests = $this->otherCostModel->getAll();
        } elseif ($isReportingBoss) {
            // Show requests created by subordinates or approved by them
            $approvalRequests = $this->otherCostModel->getByReportingBossId($userId);
        }

        $pendingApprovalRequests = [];
        $allApprovalRequests = $approvalRequests;

        if (!empty($approvalRequests)) {
            foreach ($approvalRequests as $row) {
                $canApprove = false;
                $creatorReportingUserId = (int)($row['creator_reporting_user_id'] ?? 0);
                $isDeptHead = ($row['dept_head_status'] === 'pending' && ($creatorReportingUserId === (int)$userId || $roleId === 1));
                
                if ($isDeptHead) $canApprove = true;
                if ($row['status'] == 'pending' && $row['dept_head_status'] !== 'pending' && in_array($roleId, [13, 24, 25, 1])) $canApprove = true;
                if ($row['status'] == 'qc_approved' && in_array($roleId, [3, 15, 20, 22, 32, 1])) $canApprove = true;
                if ($row['status'] == 'management_approved' && in_array($roleId, [6, 30, 1])) $canApprove = true;
                
                if ($canApprove) {
                    $pendingApprovalRequests[] = $row;
                }
            }
        }

        $userRole = $roleId;
        
        // Fetch all active projects for other cost creation (all projects, not restricted to under_construction or is_common)
        $pResult = $this->db->query("SELECT id, name, under_construction FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
        $projects = $pResult ? $pResult->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('project-other-costs/index', [
            'myRequests'              => $myRequests,
            'pendingApprovalRequests' => $pendingApprovalRequests,
            'allApprovalRequests'     => $allApprovalRequests,
            'roleId'                  => $roleId,
            'userId'                  => $userId,
            'isReportingBoss'         => $isReportingBoss,
            'projects'                => $projects,
            'coaList'                 => getCOAExpenseList($this->db),
            'costTypes'               => ProjectOtherCost::getCostTypes(),
        ]);
    }

    public function create()
    {
        $userId = $_SESSION['user']['id'];
        $userRole = (int)$_SESSION['user']['role_id'];
        
        // Fetch all active projects for other cost creation (all projects, not restricted to under_construction or is_common)
        $pResult = $this->db->query("SELECT id, name, under_construction FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
        $projects = $pResult ? $pResult->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('project-other-costs/create', [
            'projects'  => $projects,
            'coaList'   => getCOAExpenseList($this->db),
            'costTypes' => ProjectOtherCost::getCostTypes(),
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'];
            
            $empStmt = $this->db->prepare("SELECT is_dept_head, reporting_user_id FROM employees WHERE user_id = ? AND deleted_at IS NULL");
            $empStmt->bind_param('i', $userId);
            $empStmt->execute();
            $empResult = $empStmt->get_result()->fetch_assoc();
            
            $isDeptHead = !empty($empResult) ? (int)$empResult['is_dept_head'] : 0;
            $reporting_user_id = (!empty($empResult) && !empty($empResult['reporting_user_id'])) ? (int)$empResult['reporting_user_id'] : null;

            // Only require Dept Head approval if the creator is NOT a dept head (is_dept_head == 0) and has a designated reporting boss
            $dept_head_status = ($isDeptHead == 0 && !empty($reporting_user_id)) ? 'pending' : 'not_required';

            // Support single or multiple project selection
            $projectIds = [];
            if (!empty($_POST['project_ids'])) {
                if (is_array($_POST['project_ids'])) {
                    $projectIds = array_values(array_filter(array_map('intval', $_POST['project_ids'])));
                } else {
                    $projectIds = [(int)$_POST['project_ids']];
                }
            } elseif (!empty($_POST['project_id'])) {
                $projectIds = [(int)$_POST['project_id']];
            }

            if (empty($projectIds)) {
                setError("Please select at least one project.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }

            $totalAmount = (float)($_POST['request_amount'] ?? 0);
            if ($totalAmount <= 0) {
                setError("Please enter a valid request amount greater than zero.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }

            $numProjects = count($projectIds);
            $perProjectAmount = round($totalAmount / $numProjects, 2);

            // Handle file attachment once for all distributed projects
            $attachmentFileName = null;
            if (isset($_FILES['creator_attachment']) && $_FILES['creator_attachment']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../public/uploads/project_other_costs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_creator_' . basename($_FILES['creator_attachment']['name']);
                $targetFilePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['creator_attachment']['tmp_name'], $targetFilePath)) {
                    $attachmentFileName = $fileName;
                }
            }

            $basePurpose = trim($_POST['purpose'] ?? '');
            $rcostType = $_POST['rcost_type'] ?? '';
            $accountId = !empty($_POST['account_id']) ? (int)$_POST['account_id'] : null;

            $createdCount = 0;
            $index = 0;
            foreach ($projectIds as $pId) {
                $index++;
                // Last project gets remainder balance to ensure 100% exact penny match with totalAmount
                if ($index === $numProjects) {
                    $currentAmount = $totalAmount - ($perProjectAmount * ($numProjects - 1));
                } else {
                    $currentAmount = $perProjectAmount;
                }

                $purpose = $basePurpose;
                if ($numProjects > 1) {
                    $purpose .= " [Distributed: ৳" . number_format($currentAmount, 2) . " of Total ৳" . number_format($totalAmount, 2) . " across {$numProjects} projects]";
                }

                $data = [
                    'project_id'       => $pId,
                    'rcost_type'       => $rcostType,
                    'purpose'          => $purpose,
                    'request_amount'   => $currentAmount,
                    'created_by_id'    => $userId,
                    'dept_head_status' => $dept_head_status,
                    'account_id'       => $accountId,
                ];

                if ($attachmentFileName) {
                    $data['attachment'] = $attachmentFileName;
                    $data['creator_attachment'] = $attachmentFileName;
                }

                $newId = $this->otherCostModel->create($data);
                if ($newId) {
                    $this->syncToApprovalRequests($newId);
                    $createdCount++;
                }
            }

            if ($createdCount > 0) {
                if ($numProjects > 1) {
                    setSuccess("Other cost request created and distributed across {$createdCount} projects successfully (৳" . number_format($perProjectAmount, 2) . " per project, Total ৳" . number_format($totalAmount, 2) . ").");
                } else {
                    setSuccess("Other cost request created successfully.");
                }
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
            $userId = $_SESSION['user']['id'];
            $roleId = (int)$_SESSION['user']['role_id'];

            $request = $this->otherCostModel->getById($id);
            if (!$request) {
                setError("Request not found.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }

            $creatorReportingUserId = (int)($request['creator_reporting_user_id'] ?? 0);
            if ($userId != $creatorReportingUserId && $roleId !== 1) {
                setError("You are not authorized to approve this request as Department Head.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            if ($request['status'] === 'paid' || $request['status'] === 'rejected') {
                setError("Cannot modify an already " . $request['status'] . " request.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            $status = $_POST['dept_head_status'] ?? 'approved';
            $data = [
                'dept_head_status' => $status,
                'dept_head_approval_note' => $_POST['dept_head_approval_note'] ?? null,
                'dept_head_approved_by' => $userId,
                'dept_head_approved_at' => date('Y-m-d H:i:s'),
            ];

            if ($status === 'rejected') {
                $data['status'] = 'rejected';
            }

            if ($this->otherCostModel->update($id, $data)) {
                $this->syncToApprovalRequests($id);
                setSuccess("Request Department Head " . ucfirst($status) . " successfully.");
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
            $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
            if (in_array($roleId, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true) || !in_array($roleId, [1, 13, 24, 25])) {
                setError("You are not authorized to process this request as QC.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            $request = $this->otherCostModel->getById($id);
            if (!$request) {
                setError("Request not found.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }

            if ($request['status'] === 'paid' || $request['status'] === 'rejected') {
                setError("Cannot modify an already " . $request['status'] . " request.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            $action = $_POST['action'] ?? (($_POST['status'] ?? '') === 'rejected' ? 'reject' : 'approve');
            if ($action === 'reject') {
                $qcNote = trim($_POST['qc_audit_note'] ?? 'Rejected by QC Audit');
                $data = [
                    'qc_audit_note' => $qcNote,
                    'qc_audit_approved_id' => $_SESSION['user']['id'],
                    'qc_audit_approved_at' => date('Y-m-d H:i:s'),
                    'status' => 'rejected'
                ];
                if ($this->otherCostModel->update($id, $data)) {
                    $this->syncToApprovalRequests($id);
                    setSuccess("Request rejected by QC Audit successfully.");
                } else {
                    setError("Failed to reject request.");
                }
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }

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
            $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
            if (in_array($roleId, [33, 34, 35], true) || in_array($roleName, ['chief officer', 'management viewer', 'seal ceo'], true) || !in_array($roleId, [1, 3, 15, 20, 22, 32, 38])) {
                setError("You are not authorized to approve this request as Management.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            $req = $this->otherCostModel->getById($id);
            if (!$req) {
                setError("Request not found.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }

            if ($req['status'] === 'paid' || $req['status'] === 'rejected') {
                setError("Cannot modify an already " . $req['status'] . " request.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            $action = $_POST['action'] ?? (($_POST['status'] ?? '') === 'rejected' ? 'reject' : 'approve');
            if ($action === 'reject') {
                $note = trim($_POST['management_apporved_note'] ?? 'Rejected by Management');
                $data = [
                    'management_apporved_note' => $note,
                    'management_apporved_by_id' => $_SESSION['user']['id'],
                    'management_apporved_at' => date('Y-m-d H:i:s'),
                    'status' => 'rejected'
                ];
                if ($this->otherCostModel->update($id, $data)) {
                    $this->syncToApprovalRequests($id);
                    $this->db->query("UPDATE approval_requests SET status = 'rejected', approve_role = 'rejected' WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    setSuccess("Request rejected by Management successfully.");
                } else {
                    setError("Failed to reject request.");
                }
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }
            $approvedAmount = (float)($_POST['approved_amount'] ?? $req['request_amount'] ?? 0);
            $isOver1Lac = ($approvedAmount > 10000);
            $isOpHeadUser = (in_array($roleId, [20, 32], true) || str_contains($roleName, 'operation head') || str_contains($roleName, 'admin & operation'));
            $isMgmtUser = in_array($roleId, [1, 3, 38], true) || in_array($roleName, ['super admin', 'admin', 'management', 'board of director', 'board of directors'], true) || str_contains($roleName, 'director') || str_contains($roleName, 'dceo');
            $note = trim($_POST['management_apporved_note'] ?? '');

            // Fetch approval request record
            $arCheck = $this->db->query("SELECT id, op_head_status, accounts_head_status FROM approval_requests WHERE source_table = 'project_other_costs' AND source_id = {$id} ORDER BY id DESC LIMIT 1");
            $arRow = $arCheck ? $arCheck->fetch_assoc() : null;
            $opHeadStatus = strtolower($arRow['op_head_status'] ?? 'pending');
            $ahStatus = strtolower($arRow['accounts_head_status'] ?? 'pending');

            // Step 1 after QC (for both <= 10K and > 10K): Operation Head (Role 20/32) approves and forwards to Accounts Head (Role 30)
            if ($isOpHeadUser && !$isMgmtUser) {
                $arId = (int)($arRow['id'] ?? 0);
                $escNote = $this->db->real_escape_string($note ?: 'Verified and approved by Admin & Operation Head');
                if (!class_exists('ApprovalRequest')) {
                    require_once __DIR__ . '/../models/ApprovalRequest.php';
                }
                $approvalModel = new \ApprovalRequest($this->db);
                if ($arId > 0) {
                    $approvalModel->approveOperationHead($arId, $_SESSION['user']['id'], $escNote);
                } else {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$_SESSION['user']['id']}, op_head_at = NOW(), op_head_note = '{$escNote}', approve_role = 'accounts_head' WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                }
                $this->otherCostModel->update($id, [
                    'approved_amount' => $approvedAmount,
                    'op_head_status'  => 'approved',
                    'op_head_by'      => $_SESSION['user']['id'],
                    'op_head_at'      => date('Y-m-d H:i:s'),
                    'op_head_note'    => $note ?: 'Verified and approved by Admin & Operation Head',
                    'status'          => 'in_review'
                ]);
                setSuccess("Request verified & approved by Admin & Operation Head (Role 20). Forwarded to Accounts Head (Role 30) for payment verification.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            // Management Single-Stage Approval (approve ONLY current pending stage)
            $approveMode = $_POST['approve_mode'] ?? 'override';
            if ($isMgmtUser && $approveMode === 'single') {
                $mgmtUid = (int)$_SESSION['user']['id'];
                $escSingleNote = $this->db->real_escape_string($note ?: 'Approved by Management');
                if (($req['dept_head_status'] ?? '') === 'pending') {
                    $this->otherCostModel->update($id, [
                        'dept_head_status'        => 'approved',
                        'dept_head_approved_by'   => $mgmtUid,
                        'dept_head_approved_at'   => date('Y-m-d H:i:s'),
                        'dept_head_approval_note' => ($note ?: 'Approved') . ' (On behalf of Dept. Head)'
                    ]);
                    $this->db->query("UPDATE approval_requests SET approve_role = 'qc', status = 'pending' WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    setSuccess('Department Head stage approved by Management. Forwarded to QC & Internal Audit.');
                    header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                    exit();
                } elseif (empty($req['qc_audit_approved_id'])) {
                    $this->otherCostModel->update($id, [
                        'qc_audit_approved_id' => $mgmtUid,
                        'qc_audit_approved_at' => date('Y-m-d H:i:s'),
                        'qc_audit_note'        => ($note ?: 'Approved') . ' (On behalf of QC)',
                        'status'               => 'qc_approved',
                        'approved_amount'      => $approvedAmount
                    ]);
                    $this->db->query("UPDATE approval_requests SET approve_role = 'operations', status = 'pending' WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    setSuccess('QC & Audit stage approved by Management. Forwarded to Operation Head.');
                    header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                    exit();
                } elseif ($opHeadStatus !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = {$mgmtUid}, op_head_at = NOW(), op_head_note = '{$escSingleNote} (On behalf of Operation Head)', approve_role = 'accounts_head' WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    $this->db->query("UPDATE project_other_costs SET op_head_status = 'approved', op_head_by = {$mgmtUid}, op_head_at = NOW(), op_head_note = '{$escSingleNote} (On behalf of Operation Head)' WHERE id = {$id}");
                    setSuccess('Operation Head stage approved by Management. Forwarded to Accounts Head.');
                    header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                    exit();
                } elseif ($ahStatus !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET accounts_head_status = 'approved', accounts_head_by = {$mgmtUid}, accounts_head_at = NOW(), accounts_head_note = '{$escSingleNote} (On behalf of Accounts Head)', approve_role = 'management' WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    $this->db->query("UPDATE project_other_costs SET accounts_head_status = 'approved', accounts_head_by = {$mgmtUid}, accounts_head_at = NOW(), accounts_head_note = '{$escSingleNote} (On behalf of Accounts Head)' WHERE id = {$id}");
                    setSuccess('Accounts Head stage approved by Management. Ready for Final Management Approval.');
                    header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                    exit();
                }
            }

            if ($isMgmtUser) {
                $mgmtUid = (int)$_SESSION['user']['id'];
                $escBypassNote = $this->db->real_escape_string(($note ?: 'Approved by Management') . ' (Management Override)');
                // Management bypass: auto-fill any pending prior steps
                if ($opHeadStatus !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET op_head_status = 'approved', op_head_by = COALESCE(op_head_by, {$mgmtUid}), op_head_at = COALESCE(op_head_at, NOW()), op_head_note = COALESCE(NULLIF(op_head_note, ''), '{$escBypassNote}') WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    $this->db->query("UPDATE project_other_costs SET op_head_status = 'approved', op_head_by = COALESCE(op_head_by, {$mgmtUid}), op_head_at = COALESCE(op_head_at, NOW()), op_head_note = COALESCE(NULLIF(op_head_note, ''), '{$escBypassNote}') WHERE id = {$id}");
                }
                if ($ahStatus !== 'approved') {
                    $this->db->query("UPDATE approval_requests SET accounts_head_status = 'approved', accounts_head_by = COALESCE(accounts_head_by, {$mgmtUid}), accounts_head_at = COALESCE(accounts_head_at, NOW()), accounts_head_note = COALESCE(NULLIF(accounts_head_note, ''), '{$escBypassNote}') WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    $this->db->query("UPDATE project_other_costs SET accounts_head_status = 'approved', accounts_head_by = COALESCE(accounts_head_by, {$mgmtUid}), accounts_head_at = COALESCE(accounts_head_at, NOW()), accounts_head_note = COALESCE(NULLIF(accounts_head_note, ''), '{$escBypassNote}') WHERE id = {$id}");
                }
                if (($req['dept_head_status'] ?? '') === 'pending') {
                    $this->otherCostModel->update($id, [
                        'dept_head_status'        => 'approved',
                        'dept_head_approved_by'   => $mgmtUid,
                        'dept_head_approved_at'   => date('Y-m-d H:i:s'),
                        'dept_head_approval_note' => ($note ?: 'Approved by Management') . ' (Management Override)'
                    ]);
                }
                if (empty($req['qc_audit_approved_id'])) {
                    $this->otherCostModel->update($id, [
                        'qc_audit_approved_id' => $mgmtUid,
                        'qc_audit_approved_at' => date('Y-m-d H:i:s'),
                        'qc_audit_note'        => ($note ?: 'Approved by Management') . ' (Management Override)'
                    ]);
                }
            }

            $data = [
                'approved_amount' => $approvedAmount,
                'management_apporved_note' => $note,
                'management_apporved_by_id' => $_SESSION['user']['id'],
                'management_apporved_at' => date('Y-m-d H:i:s'),
                'pc_approved_id' => $_SESSION['user']['id'],
                'status' => 'management_approved'
            ];

            if ($this->otherCostModel->update($id, $data)) {
                $this->syncToApprovalRequests($id);
                $escNote = $this->db->real_escape_string($note ?: 'Approved by Management');
                $escOverrideNote = $this->db->real_escape_string(($note ?: 'Approved by Management') . ' (Management Override)');
                $mgmtUid = (int)($_SESSION['user']['id'] ?? 0);
                if ($isOpHeadUser && !$isMgmtUser) {
                    $this->db->query("UPDATE approval_requests 
                        SET op_head_status = 'approved', 
                            op_head_by = {$mgmtUid}, 
                            op_head_at = NOW(), 
                            op_head_note = '{$escNote}',
                            approve_role = 'accounts_head'
                        WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    setSuccess("Request approved by Admin & Operation Head successfully.");
                } else {
                    $this->db->query("UPDATE approval_requests 
                        SET status = 'approved',
                            approve_role = 'accounts',
                            op_head_status = 'approved', 
                            op_head_by = COALESCE(op_head_by, {$mgmtUid}),
                            op_head_at = COALESCE(op_head_at, NOW()),
                            op_head_note = COALESCE(op_head_note, '{$escOverrideNote}'),
                            accounts_head_status = 'approved',
                            accounts_head_by = COALESCE(accounts_head_by, {$mgmtUid}),
                            accounts_head_at = COALESCE(accounts_head_at, NOW()),
                            accounts_head_note = COALESCE(accounts_head_note, '{$escOverrideNote}'),
                            approved_by = {$mgmtUid}, 
                            approved_at = NOW(), 
                            approval_note = '{$escNote}'
                        WHERE source_table = 'project_other_costs' AND source_id = {$id}");
                    setSuccess("Request Management Approved successfully.");
                }
            } else {
                setError("Failed to approve request.");
            }
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
            exit();
        }
    }

    public function pay($id)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $roleName = strtolower($_SESSION['user']['role_name'] ?? '');
            if (in_array($roleId, [33, 34, 35], true) || (!in_array($roleId, [1, 3, 6, 30]) && strpos($roleName, 'accounts') === false && strpos($roleName, 'admin') === false)) {
                setError("Only Accounts or Admins are authorized to disburse payments.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            $request = $this->otherCostModel->getById($id);
            if (!$request) {
                setError("Request not found.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
                exit();
            }

            if (!in_array($request['status'], ['management_approved', 'approved'])) {
                setError("Payment cannot be processed! Request must be approved by Management first.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            // Mandatory Accounts Head Approval Guard
            if (!class_exists('ApprovalRequest')) {
                require_once __DIR__ . '/../models/ApprovalRequest.php';
            }
            $ahCheck = ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'project_other_costs', (int)$id);
            if ($ahCheck && !empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
                setError("Payment cannot be disbursed. Approval from Accounts Head is pending.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            $paymentAmount = (float)($_POST['payment_amount'] ?? 0);
            $rawPaymentAt = trim($_POST['payment_at'] ?? '');
            if (empty($rawPaymentAt)) {
                $paymentAt = date('Y-m-d H:i:s');
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $rawPaymentAt)) {
                // If only date is provided (YYYY-MM-DD), append the current disbursement time
                $paymentAt = $rawPaymentAt . ' ' . date('H:i:s');
            } else {
                $paymentAt = date('Y-m-d H:i:s', strtotime($rawPaymentAt));
            }
            $maxAllowed = (float)(!empty($request['approved_amount']) ? $request['approved_amount'] : $request['request_amount']);

            if ($paymentAmount <= 0) {
                setError("Payment amount must be greater than 0.");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }

            if ($paymentAmount > $maxAllowed) {
                setError("Payment amount (৳" . number_format($paymentAmount, 2) . ") cannot exceed the approved amount (৳" . number_format($maxAllowed, 2) . ").");
                header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . $id);
                exit();
            }
            
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

        // Only allow deletion if the request belongs to the user or user is admin
        $userId = $_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];
        if ($request['created_by_id'] != $userId && $roleId !== 1) {
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
        
        $purpose = $row['purpose'] ?? 'Other Cost';
        $projPart = !empty($row['project_name']) ? ' | ' . $row['project_name'] : '';
        $title = "Project Other Cost: " . (mb_strlen($purpose) > 70 ? mb_substr($purpose, 0, 70) . '...' : $purpose) . $projPart;
        $amount = (float)$row['request_amount'];
        
        $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : null;
        $requestedBy = (int)$row['created_by_id'];
        $status = 'pending'; // Default for approval_requests
        $approveRole = 'qc';
        $approvedBy = null;
        $approvedAt = null;
        $approvalNote = null;
        
        $deptName = 'Project Operations';
        $requestType = $row['rcost_type'] ?? 'Project Other Cost';
        $metaData = json_encode(['project_id' => $row['project_id']]);
        $createdAt = $row['created_at'] ?? date('Y-m-d H:i:s');


        if ($row['status'] == 'rejected' || $row['dept_head_status'] == 'rejected') {
            $status = 'rejected';
            $approveRole = 'rejected';
            $approvedBy = $row['dept_head_approved_by'] ?? $row['qc_audit_approved_id'] ?? $row['management_apporved_by_id'];
            $approvedAt = $row['dept_head_approved_at'] ?? $row['updated_at'];
            $approvalNote = $row['dept_head_approval_note'] ?? $row['qc_audit_note'] ?? $row['management_apporved_note'];
        } elseif ($row['status'] == 'pending') {
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
        } elseif (in_array($row['status'], ['qc_approved', 'in_review'], true)) {
            $status = 'pending';
            $arCheckRow = null;
            if ($check && $check->num_rows > 0) {
                $arRes = $this->db->query("SELECT op_head_status, accounts_head_status FROM approval_requests WHERE source_table = 'project_other_costs' AND source_id = $reqId LIMIT 1");
                if ($arRes) {
                    $arCheckRow = $arRes->fetch_assoc();
                }
            }
            $opDone = (strtolower($row['op_head_status'] ?? '') === 'approved' || strtolower($arCheckRow['op_head_status'] ?? '') === 'approved');
            $ahDone = (strtolower($row['accounts_head_status'] ?? '') === 'approved' || strtolower($arCheckRow['accounts_head_status'] ?? '') === 'approved');
            $effAmt = $approvedAmount ?: $amount;
            if ($effAmt > 10000.01 && $opDone && !$ahDone) {
                $approveRole = 'accounts_head';
            } else {
                $approveRole = 'management';
            }
            $approvedBy = $row['op_head_by'] ?? $row['qc_audit_approved_id'];
            $approvedAt = $row['op_head_at'] ?? $row['updated_at'];
            $approvalNote = $row['op_head_note'] ?? $row['qc_audit_note'];
        } elseif ($row['status'] == 'management_approved') {
            $isAhAppr = false;
            if ($check && $check->num_rows > 0) {
                $arRes = $this->db->query("SELECT accounts_head_status FROM approval_requests WHERE source_table = 'project_other_costs' AND source_id = $reqId LIMIT 1");
                $arCheckRow = $arRes ? $arRes->fetch_assoc() : null;
                $isAhAppr = (strtolower($arCheckRow['accounts_head_status'] ?? '') === 'approved');
            }
            $status = 'approved';
            $approveRole = $isAhAppr ? 'accounts' : 'accounts_head';
            $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : $amount;
            $approvedBy = $row['management_apporved_by_id'] ?? null;
            $approvedAt = $row['management_apporved_at'] ?? $row['updated_at'];
            $approvalNote = $row['management_apporved_note'] ?? null;
        } elseif ($row['status'] == 'paid') {
            $status = 'approved';
            $approveRole = 'completed';
            $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : (!empty($row['payment_amount']) ? (float)$row['payment_amount'] : $amount);
            $approvedBy = $row['management_apporved_by_id'] ?? null;
            $approvedAt = $row['management_apporved_at'] ?? $row['payment_at'] ?? $row['updated_at'];
            $approvalNote = $row['management_apporved_note'] ?? null;
        }

        $metaData = json_encode(['purpose' => $row['purpose'], 'project_id' => $row['project_id'], 'project_name' => $row['project_name']]);
        $createdAt = !empty($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');

        if ($check && $check->num_rows == 0) {
            $stmt = $this->db->prepare("
                INSERT INTO approval_requests 
                (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                VALUES (?, ?, ?, 'project_other_costs', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("sssiddssssssss", $deptName, $requestType, $title, $reqId, $amount, $approvedAmount, $requestedBy, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
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
                $stmt->bind_param("sssddsssssi", $deptName, $requestType, $title, $amount, $approvedAmount, $approveRole, $status, $approvedBy, $approvedAt, $approvalNote, $reqId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    public function review($id)
    {
        $userId = $_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        $request = $this->otherCostModel->getById($id);
        if (!$request) {
            setError("Request not found.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit;
        }

        // Keep approval_requests table in sync with the latest status
        $this->syncToApprovalRequests($id);

        // Fetch linked expense adjustments (Settlements)
        $adjustments = [];
        $stmtAdj = $this->db->prepare("
            SELECT ea.*, u.full_name as requester_name 
            FROM expense_adjustments ea 
            JOIN users u ON ea.user_id = u.id 
            WHERE ea.cashout_request_id = ? AND ea.parent_type = 'other_cost'
            ORDER BY ea.id DESC
        ");
        if ($stmtAdj) {
            $stmtAdj->bind_param('i', $id);
            $stmtAdj->execute();
            $adjustments = $stmtAdj->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        require_once __DIR__ . '/../services/RequisitionRevisionService.php';
        $revSvc = new RequisitionRevisionService($this->db);
        $latestRevision = $revSvc->getLatestActiveOrResubmitted('project_other_costs', (int)$id);

        $this->view('project-other-costs/review', [
            'request' => $request,
            'roleId' => $roleId,
            'userId' => $userId,
            'adjustments' => $adjustments,
            'costTypes' => ProjectOtherCost::getCostTypes(),
            'latestRevision' => $latestRevision
        ]);
    }

    /**
     * Update Project Other Cost Request — Creator or Admin ONLY before 1st approval
     */
    public function update($id)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/" . (int)$id);
            exit;
        }

        $id      = (int)$id;
        $userId  = (int)($_SESSION['user']['id'] ?? 0);
        $roleId  = (int)($_SESSION['user']['role_id'] ?? 0);
        $isAdmin = in_array($roleId, [1, 3], true);

        $request = $this->otherCostModel->getById($id);
        if (!$request) {
            setError("Request not found.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs");
            exit;
        }

        $isOwner = ((int)($request['created_by_id'] ?? 0) === $userId);
        if (!$isOwner && !$isAdmin) {
            setError("Only the creator or Admin can edit this request.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/{$id}");
            exit;
        }

        require_once __DIR__ . '/../services/RequisitionRevisionService.php';
        $revSvc  = new RequisitionRevisionService($this->db);
        $openRev = $revSvc->getOpenRevision('project_other_costs', $id);

        $hasAnyApproval = (($request['status'] ?? '') !== 'pending')
            || (($request['dept_head_status'] ?? '') === 'approved')
            || !empty($request['dept_head_approved_by'])
            || !empty($request['qc_audit_approved_id'])
            || !empty($request['management_apporved_by_id'])
            || !empty($request['payment_by_id']);

        if ($hasAnyApproval && empty($openRev)) {
            setError("This request has already been approved at one or more stages and can no longer be edited.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/{$id}");
            exit;
        }

        $rcostType     = trim($_POST['rcost_type'] ?? ($request['rcost_type'] ?? 'others'));
        $requestAmount = (float)($_POST['request_amount'] ?? 0);
        $purpose       = trim($_POST['purpose'] ?? '');

        if ($requestAmount <= 0 || $purpose === '') {
            setError("Please enter a valid amount greater than zero and a purpose.");
            header("Location: " . $GLOBALS['base_url'] . "/project-other-costs/review/{$id}");
            exit;
        }

        $updateData = [
            'rcost_type'     => $rcostType,
            'request_amount' => $requestAmount,
            'purpose'        => $purpose,
        ];

        if ($this->otherCostModel->update($id, $updateData)) {
            $this->syncToApprovalRequests($id);
            if (!empty($openRev)) {
                $revSvc->markResubmitted('project_other_costs', $id, $requestAmount, trim($_POST['resubmitted_note'] ?? ''));
                setSuccess("Project Other Cost request #{$id} updated & resubmitted directly to {$openRev['return_stage_label']}.");
            } else {
                setSuccess("Project Other Cost request #{$id} updated successfully.");
            }
        } else {
            setError("Failed to update request.");
        }

        $redirectUrl = (($_POST['redirect_to'] ?? '') === 'index')
            ? ($GLOBALS['base_url'] . "/project-other-costs")
            : ($GLOBALS['base_url'] . "/project-other-costs/review/{$id}");
        header("Location: " . $redirectUrl);
        exit;
    }
}
