<?php
namespace Phase2;

use Controller;

class FundRequisitionController extends Controller {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login");
            exit;
        }
    }

    public function index() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Management (role 3), Admin (role 1), Accounts (role 6, 30), Operations (role 20, 12, 19, 23, 32) see everything
        $where = "1=1";
        if (!in_array($roleId, [1, 3, 33, 6, 12, 19, 20, 23, 30, 32])) {
            // Start with: own requisitions
            $conditions = ["fr.requested_by = $userId"];

            // Include requisitions where this user is the designated Reporting Boss
            $conditions[] = "e_req.reporting_user_id = $userId";

            // Include General / Office / Non-project requisitions (where project_id is NULL)
            $conditions[] = "fr.project_id IS NULL";

            // Also include requisitions for projects they are assigned to
            $assignedIds = $this->getAssignedProjectIds($userId);
            if (!empty($assignedIds)) {
                $idList = implode(',', $assignedIds);
                $conditions[] = "fr.project_id IN ($idList)";
            }

            $where = '(' . implode(' OR ', $conditions) . ')';
        }

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $searchQuery = trim($_GET['search'] ?? '');

        $params = [];
        $types = "";

        if (!empty($startDate)) {
            $where .= " AND DATE(fr.created_at) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if (!empty($endDate)) {
            $where .= " AND DATE(fr.created_at) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }
        if (!empty($statusFilter)) {
            if ($statusFilter === 'pending') {
                $where .= " AND fr.status IN ('pending', 'pending_dept', 'dept_approved')";
            } else {
                $where .= " AND fr.status = ?";
                $params[] = $statusFilter;
                $types .= "s";
            }
        }
        if (!empty($searchQuery)) {
            $where .= " AND (fr.id LIKE ? OR u_req.full_name LIKE ? OR fr.purpose LIKE ? OR p.name LIKE ?)";
            $searchParam = '%' . $searchQuery . '%';
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ssss";
        }

        // Limit to 10 by default if no filter is applied
        $limitClause = "";
        $isDefaultLimit = false;
        if (empty($startDate) && empty($endDate) && empty($statusFilter) && empty($searchQuery)) {
            $limitClause = " LIMIT 10";
            $isDefaultLimit = true;
        }

        // Auto-check and add asset_id to fund_requisitions if missing
        $checkCol = $this->db->query("SHOW COLUMNS FROM fund_requisitions LIKE 'asset_id'");
        $hasAssetId = ($checkCol && $checkCol->num_rows > 0);
        if (!$hasAssetId) {
            @$this->db->query("ALTER TABLE fund_requisitions ADD COLUMN asset_id INT(11) NULL AFTER project_id");
            $checkCol = $this->db->query("SHOW COLUMNS FROM fund_requisitions LIKE 'asset_id'");
            $hasAssetId = ($checkCol && $checkCol->num_rows > 0);
        }

        $assetSelect = $hasAssetId ? "oa.asset_code, oa.item_name as asset_name" : "NULL as asset_code, NULL as asset_name";
        $assetJoin = $hasAssetId ? "LEFT JOIN office_assets oa ON fr.asset_id = oa.id" : "";

        $sql = "
            SELECT fr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   des_req.name as requester_designation_name,
                   u_app.full_name as approved_by_name,
                   des_app.name as approved_by_designation_name,
                   u_paid.full_name as paid_by_name,
                   des_paid.name as paid_by_designation_name,
                   u_dept_app.full_name as dept_head_approved_by_name,
                   des_dept_app.name as dept_head_approved_by_designation_name,
                   u_boss.full_name as reporting_boss_name,
                   des_boss.name as reporting_boss_designation_name,
                   e_req.reporting_user_id as requester_reporting_boss_id,
                   e_req.is_dept_head as requester_is_dept_head,
                   {$assetSelect}
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN users u_req ON fr.requested_by = u_req.id
            LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
            LEFT JOIN designations des_req ON des_req.id = COALESCE(e_req.designation_id, u_req.designation_id)
            LEFT JOIN users u_app ON fr.approved_by = u_app.id
            LEFT JOIN designations des_app ON u_app.designation_id = des_app.id
            LEFT JOIN users u_paid ON fr.paid_by = u_paid.id
            LEFT JOIN designations des_paid ON u_paid.designation_id = des_paid.id
            LEFT JOIN users u_dept_app ON fr.dept_head_approved_by = u_dept_app.id
            LEFT JOIN designations des_dept_app ON u_dept_app.designation_id = des_dept_app.id
            LEFT JOIN users u_boss ON e_req.reporting_user_id = u_boss.id
            LEFT JOIN employees e_boss ON (e_boss.user_id = u_boss.id OR e_boss.id = u_boss.employee_id)
            LEFT JOIN designations des_boss ON des_boss.id = COALESCE(e_boss.designation_id, u_boss.designation_id)
            {$assetJoin}
            WHERE $where
            ORDER BY fr.id DESC
            $limitClause
        ";

        $requests = [];
        if (!empty($params)) {
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res) {
                    $requests = $res->fetch_all(MYSQLI_ASSOC);
                }
            }
        } else {
            $res = $this->db->query($sql);
            if ($res) {
                $requests = $res->fetch_all(MYSQLI_ASSOC);
            } else {
                error_log("FundRequisition Index SQL Error: " . $this->db->error);
            }
        }

        // Projects for create form — restrict to assigned projects for site engineers
        $assignedIds = isset($assignedIds) ? $assignedIds : $this->getAssignedProjectIds($userId);
        if (!in_array($roleId, [1, 3, 33, 6, 30]) && !empty($assignedIds)) {
            $idList = implode(',', $assignedIds);
            $pRes = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND id IN ($idList) ORDER BY name ASC");
            $projects = $pRes ? $pRes->fetch_all(MYSQLI_ASSOC) : [];
        } else {
            $pRes = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
            $projects = $pRes ? $pRes->fetch_all(MYSQLI_ASSOC) : [];
        }

        $dRes = $this->db->query("SELECT id, name FROM departments ORDER BY name ASC");
        $departments = $dRes ? $dRes->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/fund_requisition/index', [
            'current_route' => 'fund-requisitions',
            'page_title' => 'Fund Requisitions',
            'requests' => $requests,
            'projects' => $projects,
            'departments' => $departments,
            'roleId' => $roleId,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'statusFilter' => $statusFilter,
            'searchQuery' => $searchQuery,
            'isDefaultLimit' => $isDefaultLimit
        ]);
    }

    /**
     * Returns an array of project IDs that a user is assigned to
     * (site_engineer_id, pm_id, operation_head_id, qc_id, struct_engineer_id, draftsman_id).
     */
    private function getAssignedProjectIds(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT id FROM projects
            WHERE deleted_at IS NULL
              AND (site_engineer_id   = ?
                OR pm_id              = ?
                OR operation_head_id  = ?
                OR qc_id              = ?
                OR struct_engineer_id = ?
                OR draftsman_id       = ?
                OR landman_id         = ?
                OR architect_id       = ?
                OR estimator_id       = ?
                OR operator_id        = ?
                OR purchase_user_id   = ?)
        ");
        $stmt->bind_param('iiiiiiiiiii', $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ids = [];
        while ($row = $result->fetch_assoc()) {
            $ids[] = (int)$row['id'];
        }
        return $ids;
    }

    /**
     * Full view page for a single fund requisition (not AJAX — renders a PHP view).
     */
    public function showPage($id) {
        $id            = (int)$id;
        $sessionUserId = (int)$_SESSION['user']['id'];
        $sessionRoleId = (int)$_SESSION['user']['role_id'];

        $sql = "
            SELECT fr.*,
                   p.name      AS project_name,
                   d.name      AS department_name,
                   u_req.full_name  AS requester_name,
                   des_req.name AS requester_designation_name,
                   u_app.full_name  AS approved_by_name,
                   des_app.name AS approved_by_designation_name,
                   u_paid.full_name AS paid_by_name,
                   des_paid.name AS paid_by_designation_name,
                   u_dept_app.full_name AS dept_head_approved_by_name,
                   des_dept_app.name AS dept_head_approved_by_designation_name,
                   u_boss.full_name AS reporting_boss_name,
                   des_boss.name AS reporting_boss_designation_name,
                   e_req.reporting_user_id AS requester_reporting_boss_id,
                   e_req.is_dept_head AS requester_is_dept_head,
                   oa.asset_code,
                   oa.item_name AS asset_item_name
            FROM fund_requisitions fr
            LEFT JOIN projects p    ON fr.project_id    = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN office_assets oa ON fr.asset_id   = oa.id
            LEFT JOIN users u_req   ON fr.requested_by  = u_req.id
            LEFT JOIN employees e_req ON (e_req.user_id = u_req.id OR e_req.id = u_req.employee_id)
            LEFT JOIN designations des_req ON des_req.id = COALESCE(e_req.designation_id, u_req.designation_id)
            LEFT JOIN users u_app   ON fr.approved_by   = u_app.id
            LEFT JOIN designations des_app ON u_app.designation_id = des_app.id
            LEFT JOIN users u_paid  ON fr.paid_by       = u_paid.id
            LEFT JOIN designations des_paid ON u_paid.designation_id = des_paid.id
            LEFT JOIN users u_dept_app ON fr.dept_head_approved_by = u_dept_app.id
            LEFT JOIN designations des_dept_app ON u_dept_app.designation_id = des_dept_app.id
            LEFT JOIN users u_boss ON e_req.reporting_user_id = u_boss.id
            LEFT JOIN employees e_boss ON (e_boss.user_id = u_boss.id OR e_boss.id = u_boss.employee_id)
            LEFT JOIN designations des_boss ON des_boss.id = COALESCE(e_boss.designation_id, u_boss.designation_id)
            WHERE fr.id = $id
        ";
        $req = $this->db->query($sql)->fetch_assoc();
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Expenses
        $expenses = $this->db->query("
            SELECT fre.*, p.name AS project_name, u.full_name AS creator_name
            FROM fund_requisition_expenses fre
            LEFT JOIN projects p ON fre.project_id = p.id
            LEFT JOIN users u    ON fre.created_by = u.id
            WHERE fre.fund_requisition_id = $id
            ORDER BY fre.id DESC
        ")->fetch_all(MYSQLI_ASSOC);

        $totalSpent = array_sum(array_column($expenses, 'amount'));
        $balance    = max(0, (float)$req['amount'] - $totalSpent);

        // Projects dropdown — restrict for restricted roles
        if (!in_array($sessionRoleId, [1, 3, 33, 6, 30])) {
            $assignedIds = $this->getAssignedProjectIds($sessionUserId);
            if (!empty($assignedIds)) {
                $idList   = implode(',', $assignedIds);
                $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND id IN ($idList) ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
            } else {
                $projects = [];
            }
        } else {
            $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/fund_requisition/show', [
            'current_route'  => 'fund-requisitions',
            'page_title'     => 'Requisition Details — REQ-' . $id,
            'req'            => $req,
            'expenses'       => $expenses,
            'projects'       => $projects,
            'totalSpent'     => $totalSpent,
            'balance'        => $balance,
            'sessionUserId'  => $sessionUserId,
            'sessionRoleId'  => $sessionRoleId,
        ]);
    }

    public function store() {
        $department_id = (int)($_POST['department_id'] ?? 0);
        $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
        $requisition_type = $_POST['requisition_type'] ?? 'general';
        $category = $_POST['category'] ?? 'Other Expense';
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = $_POST['purpose'] ?? '';
        $requested_by = (int)$_SESSION['user']['id'];

        if (!$department_id || $amount <= 0 || empty($purpose)) {
            setError('Please select department, provide a valid amount and specify the purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $designationId = (int)($_SESSION['user']['designation_id'] ?? 0);

        // Validation for Petty Cash: Only Site Engineer (role 12 or designation 38)
        if ($requisition_type === 'petty_cash') {
            if ($roleId !== 12 && $designationId !== 38) {
                setError('Petty Cash requisitions can only be requested by Site Engineers.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
                exit;
            }
            if ($amount > 10000) {
                setError('Maximum Petty Cash request limit is BDT 10,000.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
                exit;
            }
            $category = 'Petty Cash';
        }

        // Receipt Upload
        $receipt_path = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['receipt']['name'], PATHINFO_EXTENSION);
            $filename = 'receipt_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['receipt']['tmp_name'], $upload_dir . $filename)) {
                $receipt_path = 'uploads/receipts/' . $filename;
            }
        }

        // Check employee record for requested_by user to see if Dept Head approval is required
        $empRes = $this->db->query("
            SELECT e.is_dept_head, e.reporting_user_id 
            FROM employees e 
            LEFT JOIN users u ON (u.employee_id = e.id OR e.user_id = u.id)
            WHERE u.id = $requested_by 
            LIMIT 1
        ");
        $empData = ($empRes && $empRes->num_rows > 0) ? $empRes->fetch_assoc() : null;

        $isDeptHead = (int)($empData['is_dept_head'] ?? 0);
        $reportingUserId = !empty($empData['reporting_user_id']) ? (int)$empData['reporting_user_id'] : null;

        if ($isDeptHead === 0 && !empty($reportingUserId)) {
            $isDeptApprovalRequired = 1;
            $deptHeadStatus = 'pending';
            $status = 'pending_dept';
        } else {
            $isDeptApprovalRequired = 0;
            $deptHeadStatus = 'not_required';
            $status = 'pending';
        }

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisitions 
            (department_id, project_id, requested_by, requisition_type, category, amount, purpose, receipt_path, status, is_dept_approval_required, dept_head_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiissdsssis", $department_id, $project_id, $requested_by, $requisition_type, $category, $amount, $purpose, $receipt_path, $status, $isDeptApprovalRequired, $deptHeadStatus);

        if ($stmt->execute()) {
            $newReqId = $stmt->insert_id;
            $this->syncApprovalRequest($newReqId);
            setSuccess("Fund Requisition submitted successfully.");
        } else {
            setError("Failed to submit request: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function approveDept() {
        $sessionUserId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'approved'; // 'approved' or 'rejected'
        $approval_note = trim($_POST['approval_note'] ?? '');

        if (!$id || !in_array($status, ['approved', 'rejected'])) {
            setError('Invalid request or status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Fetch requisition & check if user is allowed (reporting boss OR admin/management)
        $sql = "
            SELECT fr.*, e.reporting_user_id
            FROM fund_requisitions fr
            LEFT JOIN users u ON u.id = fr.requested_by
            LEFT JOIN employees e ON (e.user_id = u.id OR u.employee_id = e.id)
            WHERE fr.id = $id
        ";
        $req = $this->db->query($sql)->fetch_assoc();
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $reportingBossId = (int)($req['reporting_user_id'] ?? 0);
        $isAllowed = ($sessionUserId === $reportingBossId || in_array($roleId, [1, 3, 33]));

        if (!$isAllowed) {
            setError('Only the designated Reporting Boss or Admin can approve this stage.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $newDeptStatus = ($status === 'approved') ? 'approved' : 'rejected';
        $newMainStatus = ($status === 'approved') ? 'pending' : 'rejected';

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions 
            SET dept_head_status = ?, 
                dept_head_approved_by = ?, 
                dept_head_approval_note = ?, 
                dept_head_approved_at = CURRENT_TIMESTAMP,
                status = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sissi", $newDeptStatus, $sessionUserId, $approval_note, $newMainStatus, $id);

        if ($stmt->execute()) {
            $this->syncApprovalRequest($id);
            setSuccess("Department Head status updated: " . $newDeptStatus);
        } else {
            setError("Failed to update status: " . $this->db->error);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/fund-requisitions");
        header("Location: " . $referer);
        exit;
    }

    public function approve() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 33, 20])) {
            setError('Only Management, Admins, or Operation can approve requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'approved'; // 'approved', 'partially_approved', or 'rejected'
        $approval_note = $_POST['approval_note'] ?? '';
        $approved_by = (int)$_SESSION['user']['id'];
        $new_approved_amount = isset($_POST['approved_amount']) ? (float)$_POST['approved_amount'] : null;

        if (!in_array($status, ['approved', 'partially_approved', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $frData = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$id}")->fetch_assoc();
        if (!$frData) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $cumulative_approved_amount = (float)($frData['approved_amount'] ?? 0);
        $posted_amount = 0;

        if ($status === 'approved') {
            $posted_amount = $frData['amount'] - $cumulative_approved_amount; 
            $cumulative_approved_amount = $frData['amount'];
        } elseif ($status === 'partially_approved') {
            if ($new_approved_amount <= 0 || ($cumulative_approved_amount + $new_approved_amount) > $frData['amount']) {
                setError('Invalid approved amount.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            }
            $cumulative_approved_amount += $new_approved_amount;
            $posted_amount = $new_approved_amount;
            if ($cumulative_approved_amount >= $frData['amount']) {
                $status = 'approved';
            }
        } elseif ($status === 'rejected') {
            // No change to approved_amount
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions 
            SET status = ?, approved_by = ?, approval_note = ?, approved_at = CURRENT_TIMESTAMP, approved_amount = ?
            WHERE id = ?
        ");
        $stmt->bind_param("sisdi", $status, $approved_by, $approval_note, $cumulative_approved_amount, $id);

        if ($stmt->execute()) {
            $this->syncApprovalRequest($id);

            // ====== AUTO POSTING JOURNAL ENTRY (Batch 2/3) ======
            if (in_array($status, ['approved', 'partially_approved']) && $posted_amount > 0) {
                try {
                    require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                    $autoService = new \AutoPostingService($this->db);
                    $officeExpenseAccId = $autoService->getAccountIdByCode('5220');
                    $salaryPayableAccId = $autoService->getAccountIdByCode('2130');
                    $autoService->postAccrualEntry(
                        'fund_requisition',
                        $id,
                        $officeExpenseAccId,
                        $salaryPayableAccId,
                        $posted_amount,
                        "Fund Requisition (FR-{$id}): " . ($frData['purpose'] ?: 'Operational Expense'),
                        $frData['project_id'],
                        'employee',
                        $frData['requested_by'],
                        $approved_by
                    );
                } catch (\Exception $e) {
                    error_log("AutoPosting Exception (Fund Requisition Approve): " . $e->getMessage());
                }
            }
            // ====================================================

            setSuccess("Requisition status updated to: " . $status);
        } else {
            setError("Failed to update requisition: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function pay() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 6, 30])) {
            setError('Only Accounts or Admins can pay requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'paid'; // 'paid', 'partially_paid', 'rejected'
        $payment_method = $_POST['payment_method'] ?? 'Cash';
        $accounts_note = $_POST['accounts_note'] ?? '';
        $paid_by = (int)$_SESSION['user']['id'];
        $new_paid_amount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : null;

        if (!in_array($status, ['paid', 'partially_paid', 'rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $frData = $this->db->query("SELECT * FROM fund_requisitions WHERE id = {$id}")->fetch_assoc();
        if (!$frData) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $cumulative_paid_amount = (float)($frData['paid_amount'] ?? 0);
        $posted_amount = 0;

        if ($status === 'paid') {
            $posted_amount = $frData['approved_amount'] - $cumulative_paid_amount;
            $cumulative_paid_amount = $frData['approved_amount'];
            if ($cumulative_paid_amount < $frData['amount']) {
                $status = 'partially_paid';
            }
        } elseif ($status === 'partially_paid') {
            if ($new_paid_amount <= 0 || ($cumulative_paid_amount + $new_paid_amount) > $frData['approved_amount']) {
                setError('Invalid paid amount.');
                header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $id);
                exit;
            }
            $cumulative_paid_amount += $new_paid_amount;
            $posted_amount = $new_paid_amount;
            if ($cumulative_paid_amount >= $frData['amount']) {
                $status = 'paid';
            }
        }

        // Handle payment attachment upload
        $payment_attachment_path = null;
        if (isset($_FILES['payment_attachment']) && $_FILES['payment_attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/payments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['payment_attachment']['name'], PATHINFO_EXTENSION);
            $filename = 'payment_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['payment_attachment']['tmp_name'], $upload_dir . $filename)) {
                $payment_attachment_path = 'uploads/payments/' . $filename;
            }
        }

        if ($payment_attachment_path) {
            $stmt = $this->db->prepare("
                UPDATE fund_requisitions 
                SET status = ?, paid_by = ?, payment_method = ?, accounts_note = ?, payment_attachment_path = ?, paid_at = CURRENT_TIMESTAMP, paid_amount = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sisssdi", $status, $paid_by, $payment_method, $accounts_note, $payment_attachment_path, $cumulative_paid_amount, $id);
        } else {
            $stmt = $this->db->prepare("
                UPDATE fund_requisitions 
                SET status = ?, paid_by = ?, payment_method = ?, accounts_note = ?, paid_at = CURRENT_TIMESTAMP, paid_amount = ?
                WHERE id = ?
            ");
            $stmt->bind_param("sissdi", $status, $paid_by, $payment_method, $accounts_note, $cumulative_paid_amount, $id);
        }

        if ($stmt->execute()) {
            $this->syncApprovalRequest($id);

            // ====== AUTO POSTING DISBURSEMENT VOUCHER (Batch 2/3) ======
            if (in_array($status, ['paid', 'partially_paid']) && $posted_amount > 0) {
                try {
                    require_once dirname(dirname(__DIR__)) . '/services/AutoPostingService.php';
                    $autoService = new \AutoPostingService($this->db);
                    
                    $bankAccId = $autoService->getBankOrCashAccountId($payment_method);
                    $advanceAccId = $autoService->getAccountIdByCode('110002'); // Advance to Employees against works/ Others
                    $autoService->postDisbursementEntry(
                        'fund_requisition',
                        $id,
                        $advanceAccId,
                        $bankAccId,
                        $posted_amount,
                        "Payment Disbursement for Fund Requisition (FR-{$id})",
                        $frData['project_id'],
                        'employee',
                        $frData['requested_by'],
                        $paid_by
                    );
                } catch (\Exception $e) {
                    error_log("AutoPosting Exception (Fund Requisition Pay): " . $e->getMessage());
                }
            }
            // ==========================================================

            setSuccess("Requisition payment status updated to: " . $status);
        } else {
            setError("Failed to update payment status: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (!$id) {
            setError("Invalid record ID.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Fetch request to check owner and approval status
        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req) {
            setError("Request not found.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Check if any approval has already been granted
        $isApproved = (
            $req['dept_head_status'] === 'approved' ||
            !empty($req['dept_head_approved_by']) ||
            in_array($req['status'], ['approved', 'paid']) ||
            !empty($req['approved_by']) ||
            !empty($req['paid_by'])
        );

        if ($isApproved) {
            setError("This requisition cannot be deleted because an approval has already been granted.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Only creator or Admin/Super Admin can delete before approval
        $allow = ($req['requested_by'] == $userId || in_array($roleId, [1, 3, 33]));

        if ($allow) {
            $this->db->query("DELETE FROM fund_requisitions WHERE id = $id");
            $this->db->query("DELETE FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = $id");
            setSuccess("Request deleted successfully.");
        } else {
            setError("Unauthorized to delete this request.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    public function show($id) {
        $id = (int)$id;
        
        // Fetch requisition details
        $sql = "
            SELECT fr.*, 
                   p.name as project_name,
                   d.name as department_name,
                   u_req.full_name as requester_name,
                   u_app.full_name as approved_by_name,
                   u_paid.full_name as paid_by_name
            FROM fund_requisitions fr
            LEFT JOIN projects p ON fr.project_id = p.id
            LEFT JOIN departments d ON fr.department_id = d.id
            LEFT JOIN users u_req ON fr.requested_by = u_req.id
            LEFT JOIN users u_app ON fr.approved_by = u_app.id
            LEFT JOIN users u_paid ON fr.paid_by = u_paid.id
            WHERE fr.id = $id
        ";
        $req = $this->db->query($sql)->fetch_assoc();
        if (!$req) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Requisition not found']);
            exit;
        }

        // Fetch total spent globally (without date filter) for correct balance calculation
        $sumSql = "SELECT SUM(amount) as total_spent FROM fund_requisition_expenses WHERE fund_requisition_id = $id";
        $sumRes = $this->db->query($sumSql)->fetch_assoc();
        $totalSpentGlobal = (float)($sumRes['total_spent'] ?? 0);

        // Fetch expenses (with date range filter if specified, or limit to 10 latest by default)
        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';

        $expenseWhere = "fre.fund_requisition_id = $id";
        $params = [];
        $types = "";

        if (!empty($startDate)) {
            $expenseWhere .= " AND DATE(fre.created_at) >= ?";
            $params[] = $startDate;
            $types .= "s";
        }
        if (!empty($endDate)) {
            $expenseWhere .= " AND DATE(fre.created_at) <= ?";
            $params[] = $endDate;
            $types .= "s";
        }

        $limitClause = "";
        $isDefaultLimit = false;
        if (empty($startDate) && empty($endDate)) {
            $limitClause = " LIMIT 10";
            $isDefaultLimit = true;
        }

        $expenseSql = "
            SELECT fre.*, 
                   p.name as project_name,
                   u.full_name as creator_name
            FROM fund_requisition_expenses fre
            LEFT JOIN projects p ON fre.project_id = p.id
            LEFT JOIN users u ON fre.created_by = u.id
            WHERE $expenseWhere
            ORDER BY fre.id DESC
            $limitClause
        ";

        if (!empty($params)) {
            $stmt = $this->db->prepare($expenseSql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $expenses = $this->db->query($expenseSql)->fetch_all(MYSQLI_ASSOC);
        }

        // Fetch projects for the add expense form
        // For restricted roles, only show their assigned projects in the dropdown
        $sessionUserId  = (int)$_SESSION['user']['id'];
        $sessionRoleId  = (int)$_SESSION['user']['role_id'];
        if (!in_array($sessionRoleId, [1, 3, 33, 6, 30])) {
            $assignedIds = $this->getAssignedProjectIds($sessionUserId);
            if (!empty($assignedIds)) {
                $idList   = implode(',', $assignedIds);
                $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND id IN ($idList) ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
            } else {
                $projects = [];
            }
        } else {
            $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success'            => true,
            'requisition'        => $req,
            'expenses'           => $expenses,
            'projects'           => $projects,
            'session_user_id'    => (int)$_SESSION['user']['id'],
            'session_role_id'    => (int)$_SESSION['user']['role_id'],
            'is_default_limit'   => $isDefaultLimit,
            'total_spent_global' => $totalSpentGlobal,
            'balance_remaining'  => max(0, (float)$req['amount'] - $totalSpentGlobal),
        ]);
        exit;
    }

    public function storeExpense() {
        $fund_requisition_id = (int)($_POST['fund_requisition_id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = $_POST['purpose'] ?? '';
        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $fund_requisition_id")->fetch_assoc();
        
        $project_id = $req ? $req['project_id'] : null;
        $other_project_name = null;

        $userId = (int)$_SESSION['user']['id'];

        // Validate that the requisition exists and is paid
        if (!$req || $req['status'] !== 'paid') {
            setError('Expenses can only be logged for paid requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        if ($amount <= 0 || empty($purpose)) {
            setError('Please specify a valid amount and purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Limit checking
        $sumSql = "SELECT SUM(amount) as total_spent FROM fund_requisition_expenses WHERE fund_requisition_id = $fund_requisition_id";
        $sumRes = $this->db->query($sumSql)->fetch_assoc();
        $total_spent = (float)($sumRes['total_spent'] ?? 0);
        $remaining = $req['amount'] - $total_spent;
        if ($amount > $remaining) {
            setError('Expense amount (BDT ' . number_format($amount, 2) . ') exceeds the remaining requisition balance (BDT ' . number_format($remaining, 2) . ').');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $fund_requisition_id);
            exit;
        }

        // Handle attachment upload
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/expenses/';
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0777, true)) {
                        error_log("Failed to create directory: " . $upload_dir);
                        setError("Failed to create upload directory.");
                    }
                }
                if (is_dir($upload_dir)) {
                    $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
                    $filename = 'expense_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
                    if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename)) {
                        $attachment_path = 'uploads/expenses/' . $filename;
                    } else {
                        $err = error_get_last();
                        error_log("Failed to move uploaded expense attachment. Error: " . ($err['message'] ?? 'Unknown'));
                        setError("Failed to save uploaded attachment.");
                    }
                }
            } else {
                $upload_errors = [
                    UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
                    UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.'
                ];
                $errorCode = $_FILES['attachment']['error'];
                $errorMessage = $upload_errors[$errorCode] ?? 'Unknown upload error.';
                error_log("Expense upload error: " . $errorMessage);
                setError("Attachment upload failed: " . $errorMessage);
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisition_expenses 
            (fund_requisition_id, project_id, other_project_name, amount, purpose, attachment_path, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisdssi", $fund_requisition_id, $project_id, $other_project_name, $amount, $purpose, $attachment_path, $userId);

        if ($stmt->execute()) {
            setSuccess("Expense logged successfully.");
        } else {
            setError("Failed to log expense: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $fund_requisition_id);
        exit;
    }

    public function deleteExpense() {
        $id = (int)($_POST['id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        if (!$id) {
            setError("Invalid expense ID.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $expense = $this->db->query("SELECT * FROM fund_requisition_expenses WHERE id = $id")->fetch_assoc();
        if (!$expense) {
            setError("Expense not found.");
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $fundReqId = (int)$expense['fund_requisition_id'];
        $req = $this->db->query("SELECT requested_by FROM fund_requisitions WHERE id = $fundReqId")->fetch_assoc();
        $isReqOwner = $req && $req['requested_by'] == $userId;

        // Only owner of expense, owner of requisition, or Admin/Management can delete
        if ($expense['created_by'] === $userId || $isReqOwner || in_array($roleId, [1, 3, 33])) {
            // Delete file if exists
            if (!empty($expense['attachment_path'])) {
                $file = dirname(dirname(dirname(__DIR__))) . '/public/' . $expense['attachment_path'];
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            $this->db->query("DELETE FROM fund_requisition_expenses WHERE id = $id");
            setSuccess("Expense deleted successfully.");
        } else {
            setError("Unauthorized to delete this expense.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions/view/" . $fundReqId);
        exit;
    }

    // =========================================================================
    // SETTLEMENT METHODS
    // =========================================================================

    /**
     * Requester marks the requisition as settled (all expenses logged).
     */
    public function submitSettlement($id) {
        $id     = (int)$id;
        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req || $req['status'] !== 'paid') {
            setError('Settlement can only be submitted for paid requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }
        if ($req['requested_by'] != $userId && !in_array($roleId, [1, 3, 33])) {
            setError('Only the requester or admin can submit settlement.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions
            SET settlement_status = 'settled', settled_at = NOW(), settled_by = ?
            WHERE id = ? AND settlement_status = 'pending'
        ");
        $stmt->bind_param('ii', $userId, $id);
        if ($stmt->execute() && $this->db->affected_rows > 0) {
            setSuccess('Requisition marked as settled. Accounts will verify.');
        } else {
            setError('Could not submit settlement. It may have already been submitted.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    /**
     * Requester marks that they have returned the remaining balance to accounts.
     */
    public function markBalanceReturned($id) {
        $id     = (int)$id;
        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }
        if ($req['requested_by'] != $userId && !in_array($roleId, [1, 3, 33])) {
            setError('Unauthorized.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions
            SET balance_returned = 1, balance_returned_at = NOW()
            WHERE id = ? AND balance_returned = 0
        ");
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $this->db->affected_rows > 0) {
            setSuccess('Balance return acknowledged. Accounts will verify.');
        } else {
            setError('Balance already marked as returned.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    /**
     * Accounts verifies the settlement (confirms everything is settled).
     */
    public function verifySettlement($id) {
        $id     = (int)$id;
        $userId = (int)$_SESSION['user']['id'];
        $roleId = (int)$_SESSION['user']['role_id'];

        if (!in_array($roleId, [1, 6])) {
            setError('Only Accounts can verify settlements.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req || $req['status'] !== 'paid') {
            setError('Only paid requisitions can be verified.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE fund_requisitions
            SET accounts_settlement_verified = 1,
                accounts_verified_by = ?,
                accounts_verified_at = NOW(),
                settlement_status = 'settled'
            WHERE id = ?
        ");
        $stmt->bind_param('ii', $userId, $id);
        if ($stmt->execute()) {
            setSuccess('Settlement verified successfully by Accounts.');
        } else {
            setError('Failed to verify settlement.');
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    /**
     * Requester submits an extra fund request when actual spend exceeds requisition amount.
     * Creates a new linked requisition with parent_requisition_id set.
     */
    public function requestExtraFund($id) {
        $id           = (int)$id;
        $userId       = (int)$_SESSION['user']['id'];
        $extraAmount  = (float)($_POST['extra_amount'] ?? 0);
        $reason       = trim($_POST['extra_reason'] ?? '');

        if ($extraAmount <= 0 || empty($reason)) {
            setError('Extra amount and reason are required.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        $req = $this->db->query("SELECT * FROM fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req || $req['status'] !== 'paid') {
            setError('Extra fund request is only allowed for paid requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
            exit;
        }

        // Mark parent as extra_requested
        $this->db->query("UPDATE fund_requisitions SET settlement_status = 'extra_requested' WHERE id = $id");

        $stmt = $this->db->prepare("
            INSERT INTO fund_requisitions
            (department_id, project_id, requested_by, requisition_type, category,
             amount, purpose, status, parent_requisition_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)
        ");
        $stmt->bind_param(
            'iiisssdsi',
            $req['department_id'],
            $req['project_id'],
            $userId,
            $req['requisition_type'],
            $req['category'],
            $extraAmount,
            $reason,
            $id
        );

        if ($stmt->execute()) {
            setSuccess('Extra fund request submitted. Awaiting management approval.');
        } else {
            setError('Failed to create extra fund request: ' . $this->db->error);
        }
        header("Location: " . $GLOBALS['base_url'] . "/fund-requisitions");
        exit;
    }

    private function syncApprovalRequest($fundReqId) {
        $fundReqId = (int)$fundReqId;
        $sql = "
            SELECT fr.*, d.name AS dept_name
            FROM fund_requisitions fr
            LEFT JOIN departments d ON fr.department_id = d.id
            WHERE fr.id = $fundReqId
            LIMIT 1
        ";
        $res = $this->db->query($sql);
        if (!$res || $res->num_rows == 0) {
            $this->db->query("DELETE FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = $fundReqId");
            return;
        }
        $row = $res->fetch_assoc();

        $check = $this->db->query("SELECT id FROM approval_requests WHERE source_table = 'fund_requisitions' AND source_id = $fundReqId");
        
        $deptName = !empty($row['dept_name']) ? $row['dept_name'] : 'General';
        $requestType = $row['requisition_type'];
        $title = "Fund Requisition: " . (mb_strlen($row['purpose']) > 80 ? mb_substr($row['purpose'], 0, 80) . '...' : $row['purpose']);
        $amount = (float)$row['amount'];
        $approvedAmount = !empty($row['approved_amount']) ? (float)$row['approved_amount'] : (in_array($row['status'], ['approved', 'paid']) ? $amount : 0);
        if ($approvedAmount == 0 && $row['status'] === 'pending_dept') {
            $approvedAmount = $amount;
        }
        $requestedBy = (int)$row['requested_by'];
        $status = ($row['status'] === 'pending_dept') ? 'pending' : $row['status'];
        $approvedBy = !empty($row['approved_by']) ? (int)$row['approved_by'] : null;
        $approvedAt = !empty($row['approved_at']) ? $row['approved_at'] : null;
        $approvalNote = !empty($row['approval_note']) ? $row['approval_note'] : null;
        $metaData = json_encode(['purpose' => $row['purpose'], 'category' => $row['category']]);
        $createdAt = $row['created_at'];

        if ($check && $check->num_rows == 0) {
            $stmt = $this->db->prepare("
                INSERT INTO approval_requests 
                (department, request_type, title, source_table, source_id, request_amount, approved_amount, requested_by, approve_role, status, approved_by, approved_at, approval_note, meta_data, created_at)
                VALUES (?, ?, ?, 'fund_requisitions', ?, ?, ?, ?, 'management', ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("sssiddisissss", $deptName, $requestType, $title, $fundReqId, $amount, $approvedAmount, $requestedBy, $status, $approvedBy, $approvedAt, $approvalNote, $metaData, $createdAt);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $this->db->prepare("
                UPDATE approval_requests 
                SET department = ?, request_type = ?, title = ?, request_amount = ?, approved_amount = ?, status = ?, approved_by = ?, approved_at = ?, approval_note = ?
                WHERE source_table = 'fund_requisitions' AND source_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("sssddsissi", $deptName, $requestType, $title, $amount, $approvedAmount, $status, $approvedBy, $approvedAt, $approvalNote, $fundReqId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}
