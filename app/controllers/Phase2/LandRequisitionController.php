<?php
namespace Phase2;

use Controller;

class LandRequisitionController extends Controller {
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

        // Fetch query based on role
        // Management (role 1, 3, 33), Operation Head (role 20, 32), Accounts (role 6, 30), Land/PM (14, 15, 22)
        $where = "1=1";
        if (!in_array($roleId, [1, 2, 3, 14, 15, 20, 22, 32, 33, 6, 30])) {
            $where = "lfr.requested_by = $userId";
        }

        $selectedProjectId = (int)($_GET['project_id'] ?? 0);
        $selectedAcqId = (int)($_GET['land_acquisition_id'] ?? 0);
        $statusFilter = trim($_GET['status_filter'] ?? '');

        if ($selectedProjectId > 0) {
            $where .= " AND la.project_id = $selectedProjectId";
        }
        if ($selectedAcqId > 0) {
            $where .= " AND lfr.land_acquisition_id = $selectedAcqId";
        }
        if ($statusFilter === 'pending_mgmt') {
            $where .= " AND lfr.mgmt_status = 'Pending'";
        } elseif ($statusFilter === 'pending_accounts') {
            $where .= " AND lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Pending'";
        } elseif ($statusFilter === 'paid') {
            $where .= " AND lfr.accounts_status = 'Paid'";
        } elseif ($statusFilter === 'rejected') {
            $where .= " AND (lfr.mgmt_status = 'Rejected' OR lfr.accounts_status = 'Rejected')";
        }

        $sql = "
            SELECT lfr.*, 
                   la.land_project_name, la.land_owner_name, la.land_receiver_name, la.media_broker_info, la.power_of_attorney,
                   la.total_area, la.area_unit, la.location, la.project_id, la.total_amount as land_total_amount,
                   COALESCE(NULLIF(TRIM(la.land_project_name), ''), p.name, 'Tentative Land Deal') as effective_project_name,
                   p.name as project_name,
                   u_req.full_name as requester_name,
                   u_mgmt.full_name as mgmt_name,
                   u_acc.full_name as accounts_name
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            LEFT JOIN projects p ON la.project_id = p.id
            LEFT JOIN users u_req ON lfr.requested_by = u_req.id
            LEFT JOIN users u_mgmt ON lfr.mgmt_by = u_mgmt.id
            LEFT JOIN users u_acc ON lfr.accounts_by = u_acc.id
            WHERE $where
            ORDER BY lfr.id DESC
        ";
        
        $requisitions = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

        // Fetch all active projects from project lists, prioritizing Planning
        $projects = $this->db->query("
            SELECT id, name, project_status 
            FROM projects 
            WHERE deleted_at IS NULL 
            ORDER BY (CASE WHEN project_status = 'Planning' THEN 0 ELSE 1 END), name ASC
        ")->fetch_all(MYSQLI_ASSOC);

        // Fetch land acquisitions with detailed financial breakdown for dropdown & modal widgets
        $projectFilter = in_array($roleId, [1, 3, 14, 15, 20, 22, 32, 33, 6, 30]) ? "1=1" : "(p.landman_id = $userId OR la.created_by = $userId)";
        $acqFinResult = $this->db->query("
            SELECT la.id, la.project_id, la.land_project_name, la.land_owner_name, la.land_receiver_name, la.media_broker_info, la.total_amount,
                   COALESCE(NULLIF(TRIM(la.land_project_name), ''), p.name, 'Tentative Land Deal') as effective_project_name,
                   p.name as project_name,
                   COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Paid' AND lfr.requisition_type IN ('Bayana', 'Registration') THEN lfr.requested_amount ELSE 0 END), 0) as paid_land,
                   COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Paid' AND lfr.requisition_type IN ('Extra Cost', 'Other') THEN lfr.requested_amount ELSE 0 END), 0) as paid_extra,
                   COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Pending' AND lfr.mgmt_status != 'Rejected' THEN lfr.requested_amount ELSE 0 END), 0) as pending_amount,
                   COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Pending' THEN lfr.requested_amount ELSE 0 END), 0) as pending_mgmt_amount,
                   COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Pending' THEN lfr.requested_amount ELSE 0 END), 0) as pending_accounts_amount,
                   COUNT(DISTINCT lfr.id) as total_reqs,
                   COUNT(DISTINCT CASE WHEN lfr.accounts_status = 'Paid' THEN lfr.id END) as paid_reqs
            FROM land_acquisitions la
            LEFT JOIN projects p ON la.project_id = p.id
            LEFT JOIN land_fund_requisitions lfr ON la.id = lfr.land_acquisition_id
            WHERE la.status != 'cancelled' AND $projectFilter
            GROUP BY la.id
            ORDER BY effective_project_name ASC, la.id DESC
        ");

        $acquisitions = [];
        $acqFinMap = [];
        $topStats = [
            'total_portfolio_valuation' => 0,
            'total_land_paid' => 0,
            'total_extra_paid' => 0,
            'total_pending' => 0,
            'total_land_due' => 0,
        ];

        if ($acqFinResult) {
            while ($row = $acqFinResult->fetch_assoc()) {
                $row['total_amount'] = (float)$row['total_amount'];
                $row['paid_land'] = (float)$row['paid_land'];
                $row['paid_extra'] = (float)$row['paid_extra'];
                $row['total_paid'] = $row['paid_land'] + $row['paid_extra'];
                $row['remaining_due'] = max(0, $row['total_amount'] - $row['paid_land']);
                $row['pending_amount'] = (float)$row['pending_amount'];
                $row['pending_mgmt_amount'] = (float)$row['pending_mgmt_amount'];
                $row['pending_accounts_amount'] = (float)$row['pending_accounts_amount'];
                $acqFinMap[$row['id']] = $row;
                $acquisitions[] = $row;

                $topStats['total_portfolio_valuation'] += $row['total_amount'];
                $topStats['total_land_paid'] += $row['paid_land'];
                $topStats['total_extra_paid'] += $row['paid_extra'];
                $topStats['total_pending'] += $row['pending_amount'];
                $topStats['total_land_due'] += $row['remaining_due'];
            }
        }

        // Global / Filtered Requisition Workflow Stats
        $statWhere = "1=1";
        if (!in_array($roleId, [1, 3, 14, 15, 20, 22, 32, 33, 6, 30])) {
            $statWhere = "(p.landman_id = $userId OR la.created_by = $userId OR lfr.requested_by = $userId)";
        }
        if ($selectedProjectId > 0) {
            $statWhere .= " AND la.project_id = $selectedProjectId";
        }
        if ($selectedAcqId > 0) {
            $statWhere .= " AND lfr.land_acquisition_id = $selectedAcqId";
        }

        $workflowStats = $this->db->query("
            SELECT 
                COUNT(*) as total_count,
                COALESCE(SUM(lfr.requested_amount), 0) as total_amount,
                COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Pending' THEN 1 ELSE 0 END), 0) as pending_mgmt_count,
                COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Pending' THEN lfr.requested_amount ELSE 0 END), 0) as pending_mgmt_amount,
                COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Pending' THEN 1 ELSE 0 END), 0) as pending_accounts_count,
                COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Approved' AND lfr.accounts_status = 'Pending' THEN lfr.requested_amount ELSE 0 END), 0) as pending_accounts_amount,
                COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Paid' THEN 1 ELSE 0 END), 0) as paid_count,
                COALESCE(SUM(CASE WHEN lfr.accounts_status = 'Paid' THEN lfr.requested_amount ELSE 0 END), 0) as paid_amount,
                COALESCE(SUM(CASE WHEN lfr.mgmt_status = 'Rejected' OR lfr.accounts_status = 'Rejected' THEN 1 ELSE 0 END), 0) as rejected_count
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            LEFT JOIN projects p ON la.project_id = p.id
            WHERE $statWhere
        ")->fetch_assoc();

        // Attach financial stats to requisitions list
        foreach ($requisitions as &$r) {
            $acqId = (int)$r['land_acquisition_id'];
            $r['land_financial'] = $acqFinMap[$acqId] ?? null;
        }
        unset($r);

        $this->view('phase2/land_requisition/index', [
            'current_route' => 'con-land-requisitions',
            'page_title' => 'Land Fund Requisitions',
            'requisitions' => $requisitions,
            'acquisitions' => $acquisitions,
            'acqFinMap' => $acqFinMap,
            'topStats' => $topStats,
            'workflowStats' => $workflowStats,
            'statusFilter' => $statusFilter,
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
            'selectedAcqId' => $selectedAcqId,
            'roleId' => $roleId
        ]);
    }

    public function create() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $projects = $this->db->query("
            SELECT id, name, project_status 
            FROM projects 
            WHERE deleted_at IS NULL 
            ORDER BY (CASE WHEN project_status = 'Planning' THEN 0 ELSE 1 END), name ASC
        ")->fetch_all(MYSQLI_ASSOC);

        $projectFilter = in_array($roleId, [1, 3, 14, 15, 22, 33, 6, 30]) ? "1=1" : "(p.landman_id = $userId OR la.created_by = $userId)";
        $acquisitions = $this->db->query("
            SELECT la.id, la.project_id, la.land_project_name, la.land_owner_name, la.land_receiver_name, la.total_amount,
                   COALESCE(NULLIF(TRIM(la.land_project_name), ''), p.name, 'Tentative Land Deal') as effective_project_name,
                   p.name as project_name
            FROM land_acquisitions la
            LEFT JOIN projects p ON la.project_id = p.id
            WHERE la.status != 'cancelled' AND $projectFilter
            ORDER BY effective_project_name ASC, la.id DESC
        ")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_requisition/create', [
            'current_route' => 'con-land-requisitions',
            'page_title' => 'Create Land Fund Requisition',
            'acquisitions' => $acquisitions,
            'projects' => $projects
        ]);
    }

    public function store() {
        $land_acquisition_id = (int)($_POST['land_acquisition_id'] ?? 0);
        $project_id = (int)($_POST['project_id'] ?? 0);
        $requisition_type = $_POST['requisition_type'] ?? 'Other';
        $cost_purpose = !empty($_POST['cost_purpose']) ? trim($_POST['cost_purpose']) : null;
        $requested_amount = (float)($_POST['requested_amount'] ?? 0);
        $purpose = $_POST['purpose'] ?? '';
        $requested_by = (int)$_SESSION['user']['id'];

        // If no land_acquisition_id was directly selected but project_id is given
        if (!$land_acquisition_id && $project_id > 0) {
            $chkLa = $this->db->query("SELECT id FROM land_acquisitions WHERE project_id = $project_id AND status != 'cancelled' ORDER BY id DESC LIMIT 1");
            if ($chkLa && $rowLa = $chkLa->fetch_assoc()) {
                $land_acquisition_id = (int)$rowLa['id'];
            } else {
                $pNameRow = $this->db->query("SELECT name FROM projects WHERE id = $project_id LIMIT 1")->fetch_assoc();
                $pName = $pNameRow['name'] ?? 'Project';
                $ownerName = !empty($_POST['new_landowner_name']) ? trim($_POST['new_landowner_name']) : "Land Acquisition - {$pName}";
                $stmtNewLa = $this->db->prepare("INSERT INTO land_acquisitions (project_id, land_project_name, land_owner_name, total_area, area_unit, price_per_unit, total_amount, status, created_by) VALUES (?, ?, ?, 0, 'decimal', 0, 0, 'selected', ?)");
                $stmtNewLa->bind_param("issi", $project_id, $pName, $ownerName, $requested_by);
                $stmtNewLa->execute();
                $land_acquisition_id = $this->db->insert_id;
                $stmtNewLa->close();
            }
        }

        if (!$land_acquisition_id || $requested_amount <= 0 || empty($purpose)) {
            setError('Please select a project / land portfolio and provide a valid amount and purpose.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        // Budget check for Bayana and Registration types (must not exceed Land Valuation remaining balance)
        if (in_array($requisition_type, ['Bayana', 'Registration'])) {
            $acqResult = $this->db->query("SELECT total_amount, land_owner_name, land_project_name FROM land_acquisitions WHERE id = $land_acquisition_id");
            $acq = $acqResult ? $acqResult->fetch_assoc() : null;
            if ($acq) {
                $total_amount = (float)$acq['total_amount'];
                $owner_name = $acq['land_owner_name'];

                // Sum all existing active (non-rejected) Bayana & Registration requisitions
                $existingResult = $this->db->query("
                    SELECT SUM(requested_amount) as total_requested 
                    FROM land_fund_requisitions 
                    WHERE land_acquisition_id = $land_acquisition_id 
                      AND requisition_type IN ('Bayana', 'Registration')
                      AND mgmt_status != 'Rejected' 
                      AND accounts_status != 'Rejected'
                ");
                $existing = $existingResult ? $existingResult->fetch_assoc() : null;
                $total_requested = (float)($existing['total_requested'] ?? 0);

                $remaining = $total_amount - $total_requested;
                if ($requested_amount > $remaining) {
                    setError(sprintf(
                        "Cannot request BDT %s. The remaining land price budget for Bayana/Registration for landowner '%s' is BDT %s (Total Valuation: BDT %s, Already requested/active: BDT %s). Extra cost/brokerage can be requested under 'Extra Cost' or 'Other'.",
                        number_format($requested_amount, 2),
                        htmlspecialchars($owner_name),
                        number_format(max(0, $remaining), 2),
                        number_format($total_amount, 2),
                        number_format($total_requested, 2)
                    ));
                    header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
                    exit;
                }
            }
        }

        // Requisition Attachment Upload
        $req_attachment_path = null;
        if (isset($_FILES['req_attachment']) && $_FILES['req_attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['req_attachment']['name'], PATHINFO_EXTENSION);
            $filename = 'land_req_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['req_attachment']['tmp_name'], $upload_dir . $filename)) {
                $req_attachment_path = 'uploads/receipts/' . $filename;
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO land_fund_requisitions 
            (land_acquisition_id, requisition_type, cost_purpose, requested_amount, purpose, req_attachment_path, requested_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issdssi", $land_acquisition_id, $requisition_type, $cost_purpose, $requested_amount, $purpose, $req_attachment_path, $requested_by);

        if ($stmt->execute()) {
            if (!class_exists('ApprovalRequest')) {
                require_once __DIR__ . '/../../models/ApprovalRequest.php';
            }
            $approvalModel = new \ApprovalRequest($this->db);
            $approvalModel->syncMissingLandFundRequisitions();
            setSuccess("Land Fund Requisition submitted successfully.");
        } else {
            setError("Failed to submit requisition: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
        exit;
    }

    public function approve() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending'; // 'Approved' or 'Rejected'
        $note = $_POST['mgmt_note'] ?? '';
        $mgmt_by = (int)$_SESSION['user']['id'];

        $isMgmt = in_array($roleId, [1, 3, 33]);
        $isOpHead = in_array($roleId, [20, 32]);

        if (!$isMgmt && !$isOpHead) {
            setError('You do not have permission to approve requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        // Fetch requisition to verify amount limit
        $chkReq = $this->db->query("SELECT requested_amount, mgmt_status FROM land_fund_requisitions WHERE id = $id LIMIT 1");
        $reqData = $chkReq ? $chkReq->fetch_assoc() : null;
        if (!$reqData) {
            setError('Requisition not found.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        $reqAmount = (float)$reqData['requested_amount'];
        if ($isOpHead && !$isMgmt && $reqAmount > 100000) {
            setError("Requests above BDT 100,000 (1 Lac) require Management approval. Operation Head (Role 20) can only approve requisitions up to BDT 100,000.");
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        if (!in_array($status, ['Approved', 'Rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        $stmt = $this->db->prepare("
            UPDATE land_fund_requisitions 
            SET mgmt_status = ?, mgmt_by = ?, mgmt_note = ?, mgmt_approved_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("sisi", $status, $mgmt_by, $note, $id);

        if ($stmt->execute()) {
            $newAppRole = ($status === 'Approved') ? 'accounts' : 'rejected';
            $newArStatus = ($status === 'Approved') ? 'approved' : 'rejected';
            $stmtAr = $this->db->prepare("
                UPDATE approval_requests 
                SET approve_role = ?, status = ?, approved_by = ?, approved_at = NOW(), approval_note = ?
                WHERE source_table IN ('land_fund_requisitions', 'con_land_requisitions') AND source_id = ?
            ");
            if ($stmtAr) {
                $stmtAr->bind_param("ssisi", $newAppRole, $newArStatus, $mgmt_by, $note, $id);
                $stmtAr->execute();
                $stmtAr->close();
            }
            setSuccess("Requisition status updated to " . $status);
        } else {
            setError("Failed to update requisition status: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
        exit;
    }

    public function pay() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 6, 30])) {
            setError('Only Accounts can record payments.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending'; // 'Paid' or 'Rejected'
        $note = $_POST['accounts_note'] ?? '';
        $payment_method = $_POST['payment_method'] ?? 'Bank';
        $transaction_no = $_POST['transaction_no'] ?? '';
        $accounts_by = (int)$_SESSION['user']['id'];

        if (!in_array($status, ['Paid', 'Rejected'])) {
            setError('Invalid status.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        // Verify it was approved by management first
        $req = $this->db->query("SELECT mgmt_status, land_acquisition_id, requested_amount, requisition_type FROM land_fund_requisitions WHERE id = $id")->fetch_assoc();
        if (!$req || $req['mgmt_status'] !== 'Approved') {
            setError('Requisition must be approved by Management before payment can be recorded.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        // Verify Accounts Head approval
        if (!class_exists('ApprovalRequest')) {
            require_once __DIR__ . '/../../models/ApprovalRequest.php';
        }
        $ahCheck = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'land_fund_requisitions', $id);
        if (empty($ahCheck['has_request'])) {
            $ahCheck = \ApprovalRequest::getAccountsHeadApprovalInfo($this->db, 'con_land_requisitions', $id);
        }
        if (!empty($ahCheck['has_request']) && empty($ahCheck['is_accounts_head_approved'])) {
            setError('Payment locked. Mandatory approval by Accounts Head (Md. Mamunoor Rashid [1309]) is required before disbursement.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        // Upload Payment Attachment
        $attachment_path = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = dirname(dirname(dirname(__DIR__))) . '/public/uploads/receipts/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION);
            $filename = 'land_receipt_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $upload_dir . $filename)) {
                $attachment_path = 'uploads/receipts/' . $filename;
            }
        }

        $stmt = $this->db->prepare("
            UPDATE land_fund_requisitions 
            SET accounts_status = ?, accounts_by = ?, accounts_note = ?, payment_method = ?, transaction_no = ?, attachment_path = ?, accounts_paid_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->bind_param("sissssi", $status, $accounts_by, $note, $payment_method, $transaction_no, $attachment_path, $id);

        if ($stmt->execute()) {
            if ($status === 'Paid') {
                if (!class_exists('AutoPostingService')) {
                    require_once __DIR__ . '/../../services/AutoPostingService.php';
                }
                $autoPoster = new \AutoPostingService($this->db);
                $autoPoster->postLandRequisitionJournal($id);
            }
            $newAppRole = ($status === 'Paid') ? 'completed' : 'rejected';
            $newArStatus = ($status === 'Paid') ? 'approved' : 'rejected';
            $stmtAr = $this->db->prepare("
                UPDATE approval_requests 
                SET approve_role = ?, status = ?, approved_by = ?, approved_at = NOW(), approval_note = ?
                WHERE source_table IN ('land_fund_requisitions', 'con_land_requisitions') AND source_id = ?
            ");
            if ($stmtAr) {
                $stmtAr->bind_param("ssisi", $newAppRole, $newArStatus, $accounts_by, $note, $id);
                $stmtAr->execute();
                $stmtAr->close();
            }
            setSuccess("Payment recorded successfully.");
        } else {
            setError("Failed to record payment: " . $this->db->error);
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
        exit;
    }

    public function delete() {
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        if (!in_array($roleId, [1, 3, 33])) {
            setError('Unauthorized.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $this->db->query("DELETE FROM land_fund_requisitions WHERE id = $id");
            $this->db->query("DELETE FROM approval_requests WHERE source_table IN ('land_fund_requisitions', 'con_land_requisitions') AND source_id = $id");
            setSuccess("Requisition deleted.");
        } else {
            setError("Invalid record ID.");
        }
        header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
        exit;
    }
}
