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
        // Management (role 3) and Admin (role 1) see everything
        // Accounts (role 6, 30) see approved and paid ones
        // Land Officers (role 14) and PMs (15, 22) see all
        $where = "1=1";
        if (!in_array($roleId, [1, 2, 3, 14, 15, 22, 33, 6, 30])) {
            $where = "lfr.requested_by = $userId";
        }

        $selectedProjectId = (int)($_GET['project_id'] ?? 0);
        if ($selectedProjectId > 0) {
            $where .= " AND la.project_id = $selectedProjectId";
        }

        $sql = "
            SELECT lfr.*, 
                   la.land_owner_name, la.total_area, la.area_unit, la.location, la.project_id,
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

        // Fetch all active projects from project lists
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

        // Fetch land acquisitions for dropdown
        $projectFilter = in_array($roleId, [1, 3, 14, 15, 22, 33, 6, 30]) ? "1=1" : "(p.landman_id = $userId OR la.created_by = $userId)";
        $acquisitions = $this->db->query("
            SELECT la.id, la.project_id, la.land_owner_name, p.name as project_name
            FROM land_acquisitions la
            LEFT JOIN projects p ON la.project_id = p.id
            WHERE la.status != 'cancelled' AND $projectFilter
            ORDER BY p.name ASC, la.id DESC
        ")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/land_requisition/index', [
            'current_route' => 'con-land-requisitions',
            'page_title' => 'Land Fund Requisitions',
            'requisitions' => $requisitions,
            'acquisitions' => $acquisitions,
            'projects' => $projects,
            'selectedProjectId' => $selectedProjectId,
            'roleId' => $roleId
        ]);
    }

    public function create() {
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $projectFilter = in_array($roleId, [1, 3, 14, 15, 22, 33, 6, 30]) ? "1=1" : "(p.landman_id = $userId OR la.created_by = $userId)";
        $acquisitions = $this->db->query("
            SELECT la.id, la.project_id, la.land_owner_name, p.name as project_name
            FROM land_acquisitions la
            LEFT JOIN projects p ON la.project_id = p.id
            WHERE la.status != 'cancelled' AND $projectFilter
            ORDER BY p.name ASC, la.id DESC
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
                $stmtNewLa = $this->db->prepare("INSERT INTO land_acquisitions (project_id, land_owner_name, total_area, area_unit, price_per_unit, total_amount, status, created_by) VALUES (?, ?, 0, 'decimal', 0, 0, 'selected', ?)");
                $stmtNewLa->bind_param("isi", $project_id, $ownerName, $requested_by);
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

        // Budget check for Bayana and Registration types
        if (in_array($requisition_type, ['Bayana', 'Registration'])) {
            $acqResult = $this->db->query("SELECT total_amount, land_owner_name FROM land_acquisitions WHERE id = $land_acquisition_id");
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
                        "Cannot request BDT %s. The remaining budget for Bayana/Registration for landowner '%s' is BDT %s (Total Valuation: BDT %s, Already requested/active: BDT %s).",
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
            (land_acquisition_id, requisition_type, requested_amount, purpose, req_attachment_path, requested_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isdssi", $land_acquisition_id, $requisition_type, $requested_amount, $purpose, $req_attachment_path, $requested_by);

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
        if (!in_array($roleId, [1, 3, 33])) {
            setError('Only Management can approve requisitions.');
            header("Location: " . $GLOBALS['base_url'] . "/con-land-requisitions");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending'; // 'Approved' or 'Rejected'
        $note = $_POST['mgmt_note'] ?? '';
        $mgmt_by = (int)$_SESSION['user']['id'];

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
