<?php
namespace Phase2;

use Controller;
use Con_PurchaseRequest;
use Con_BOQ;
use Project;
use Con_Material;
use Con_MaterialCategory;
use Con_Quotation;

class Con_PurchaseRequestController extends Controller
{
    protected $db;
    protected $prModel;
    protected $boqModel;
    protected $projectModel;
    protected $materialModel;
    protected $categoryModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->prModel       = new Con_PurchaseRequest($this->db);
        $this->boqModel      = new Con_BOQ($this->db);
        $this->projectModel  = new Project($this->db);
        $this->materialModel = new Con_Material($this->db);
        $this->categoryModel = new Con_MaterialCategory($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId   = (int)($_SESSION['user']['id'] ?? 0);

        // Approver & Procurement roles see ALL PRs; regular creators only see their own PRs
        $approverRoles = [1, 2, 3, 6, 13, 15, 17, 20, 22, 23, 24, 25, 30, 31, 32];
        $isApprover    = in_array($userRole, $approverRoles);
        $createdBy     = !$isApprover ? $userId : null;

        $requests      = $this->prModel->getAll($createdBy);

        // Fetch Purchase Executives (Role ID 23) and Senior Purchase Executives (Role ID 31) for assignment dropdown
        $executives = [];
        if (in_array($userRole, [1, 3, 17, 22, 31])) { // Admin, Management, Purchase Head, Senior Purchase Executive
            $stmt = $this->db->prepare("SELECT id, full_name FROM users WHERE role_id IN (23, 31) ORDER BY full_name");
            $stmt->execute();
            $executives = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/purchase-requests/purchase-request-lists', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Purchase Requests',
            'requests'      => $requests,
            'executives'    => $executives,
            'userRole'      => $userRole,
        ]);
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId   = (int)($_SESSION['user']['id'] ?? 0);

        // Block management and approver roles from creating PRs
        if (in_array($userRole, [1, 3, 15, 20, 22, 32])) {
            setError('You do not have permission to create Purchase Requests. You can only approve them.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        // ── Project list based on role ────────────────────────────────────────
        if (in_array($userRole, [1, 3, 17, 22, 23, 31])) {
            // Admin / Super Admin / Purchase Team Leader / PM Head / Purchase Executive / Sr Purchase Executive → all projects
            $pResult  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC");
            $projects = $pResult ? $pResult->fetch_all(MYSQLI_ASSOC) : [];

        } else {
            // Other construction roles → any project they are assigned to
            $pStmt = $this->db->prepare("
                SELECT id, name FROM projects
                WHERE deleted_at IS NULL
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

        $materials = $this->materialModel->getAll();

        // Support ?boq_id=N coming from Procurement Dashboard "Create PR" button
        $preBoqId     = (int)($_GET['boq_id']     ?? 0);
        $preProjectId = (int)($_GET['project_id'] ?? 0);

        $preBoqData = null;
        if ($preBoqId) {
            $stmtBoq = $this->db->prepare("
                SELECT bm.*, p.name AS project_name,
                       COUNT(bi.id) AS item_count,
                       COALESCE(SUM(bi.total_cost), 0) AS grand_total
                FROM boq_master bm
                LEFT JOIN projects p ON p.id = bm.project_id
                LEFT JOIN boq_items bi ON bi.boq_id = bm.id
                WHERE bm.id = ? AND bm.status = 'Approved'
                GROUP BY bm.id");
            $stmtBoq->bind_param('i', $preBoqId);
            $stmtBoq->execute();
            $preBoqData = $stmtBoq->get_result()->fetch_assoc();

            // If BOQ found and no project_id given, derive from BOQ
            if ($preBoqData && !$preProjectId) {
                $preProjectId = (int)$preBoqData['project_id'];
            }
        }

        // ── Inject preProjectId into $projects if not already there ──────────
        // This ensures the BOQ-linked project always appears in the dropdown
        // even if the purchase_user_id assignment is not yet set
        if ($preProjectId) {
            $inList = array_filter($projects, fn($p) => (int)$p['id'] === $preProjectId);
            if (empty($inList)) {
                $stmtPrj = $this->db->prepare("SELECT id, name FROM projects WHERE id = ? AND deleted_at IS NULL LIMIT 1");
                $stmtPrj->bind_param('i', $preProjectId);
                $stmtPrj->execute();
                $prjRow = $stmtPrj->get_result()->fetch_assoc();
                if ($prjRow) {
                    array_unshift($projects, $prjRow); // add at top
                }
            }
        }

        // Fetch material categories for the "Add New Material" modal
        $categories = $this->categoryModel->getAll();

        $this->view('phase2/purchase-requests/create-purchase-request', [
            'current_route' => 'con-purchase-request-create',
            'page_title'    => 'New Purchase Request',
            'projects'      => $projects,
            'materials'     => $materials,
            'categories'    => $categories,
            'preBoqId'      => $preBoqId,
            'preProjectId'  => $preProjectId,
            'preBoqData'    => $preBoqData,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22, 32])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $prType = ($_POST['pr_type'] ?? 'boq_linked') === 'independent' ? 'independent' : 'boq_linked';

        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $uploadDir = 'public/uploads/pr_attachments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'webp', 'xlsx', 'xls'];
            if (in_array($ext, $allowed)) {
                $fileName = 'pr_doc_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                if (move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . $fileName)) {
                    $attachment = $fileName;
                }
            }
        }

        $data = [
            'project_id'   => (int)($_POST['project_id'] ?? 0),
            'bom_id'       => ($prType === 'boq_linked' && !empty($_POST['bom_id'])) ? (int)$_POST['bom_id'] : null,
            'pr_type'      => $prType,
            'required_date'=> !empty($_POST['required_date']) ? $_POST['required_date'] : null,
            'purpose'      => trim($_POST['purpose'] ?? ''),
            'created_by'   => (int)($_SESSION['user']['id'] ?? 0),
            'status'       => $_POST['status'] ?? 'Submitted',
            'notes'        => trim($_POST['notes'] ?? ''),
            'attachment'   => $attachment,
        ];

        $materialIds   = $_POST['material_id']   ?? [];
        $quantities    = $_POST['quantity']       ?? [];
        $itemDates     = $_POST['item_req_date']  ?? []; // per-item required_date (optional)

        $items = [];
        foreach ($materialIds as $i => $mid) {
            if (!empty($mid) && ($quantities[$i] ?? 0) > 0) {
                $items[] = [
                    'material_id'  => (int)$mid,
                    'quantity'     => (float)$quantities[$i],
                    'required_date'=> !empty($itemDates[$i]) ? $itemDates[$i] : null,
                ];
            }
        }

        if (empty($items)) {
            setError('At least one material item is required.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request-create");
            exit;
        }

        $result = $this->prModel->create($data, $items);

        if ($result['success']) {
            setSuccess('Purchase Request created successfully.');

            // Trigger email if submitted
            if ($data['status'] === 'Submitted') {
                try {
                    $prId = (int)$result['id'];
                    // 1. Get Project Name
                    $projStmt = $this->db->prepare("SELECT name FROM projects WHERE id = ?");
                    $projStmt->bind_param('i', $data['project_id']);
                    $projStmt->execute();
                    $projRow = $projStmt->get_result()->fetch_assoc();
                    $projectName = $projRow ? $projRow['name'] : 'Unknown Project';

                    // 2. Get Site Engineer Name
                    $engineerName = $_SESSION['user']['full_name'] ?? 'Site Engineer';

                    // 3. Requisition Month — from BOQ for boq_linked, or from required_date for independent
                    $requisitionMonth = date('F Y');
                    if ($prType === 'boq_linked' && !empty($data['bom_id'])) {
                        $boqStmt = $this->db->prepare("
                            SELECT pmt.target_month
                            FROM boq_master bm
                            LEFT JOIN phase_monthly_targets pmt ON pmt.id = bm.target_id
                            WHERE bm.id = ?
                        ");
                        $boqStmt->bind_param('i', $data['bom_id']);
                        $boqStmt->execute();
                        $boqRow = $boqStmt->get_result()->fetch_assoc();
                        if ($boqRow && !empty($boqRow['target_month'])) {
                            $requisitionMonth = date('F Y', strtotime($boqRow['target_month']));
                        }
                    } elseif ($prType === 'independent' && !empty($data['required_date'])) {
                        $requisitionMonth = date('F Y', strtotime($data['required_date']));
                    }

                    // 4. Send email
                    require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                    $mailService = new \ProjectWorkflowMailService($this->db);
                    $mailService->notifyOnPRSubmission(
                        (int)$data['project_id'],
                        $projectName,
                        $engineerName,
                        $requisitionMonth,
                        $prId,
                        $prType
                    );
                } catch (\Throwable $e) {
                    error_log("Failed to send PR submission email in store(): " . $e->getMessage());
                }
            }

            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
        } else {
            $errMsg = implode(' | ', $result['errors']);
            setError("PR creation failed: {$errMsg}");
            header("Location: {$GLOBALS['base_url']}/con-purchase-request-create");
        }
        exit;
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        $pr = $this->prModel->find($id);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $items = $this->prModel->getItems($id);

        // Check if quotations / comparison exist
        $quotModel  = new Con_Quotation($this->db);
        $quotations = $quotModel->getByRequest($id);
        $hasCST     = $quotModel->hasComparison($id);
        // ── Determine next step / block info for PR ──────────────────────────
        $nextStepInfo = '';
        if ($pr['status'] === 'Draft') {
            $nextStepInfo = 'Submit Purchase Request for review (Action required by Site Engineer / Creator).';
        } elseif ($pr['status'] === 'Submitted') {
            if (empty($pr['approved_by_id'])) {
                $nextStepInfo = 'Purchase Request is submitted. Awaiting Stage 1 approval by PM (Project Manager).';
            } elseif (empty($pr['qc_approved_by_id'])) {
                $nextStepInfo = 'Stage 1 Approved by PM. Awaiting Stage 2 approval by Operation Head.';
            } else {
                $nextStepInfo = 'Stage 2 Approved by Operation Head. Awaiting final Stage 3 approval by Management.';
            }
        } elseif ($pr['status'] === 'Rejected') {
            $nextStepInfo = 'Purchase Request was Rejected. Need to revise details or create a new PR.';
        } elseif ($pr['status'] === 'Approved') {
            if (empty($pr['assigned_executive_id'])) {
                $nextStepInfo = 'Awaiting Purchase Executive assignment by Purchase Head or Admin.';
            } else {
                // Check if quotations count is at least 2
                $stmtQuotes = $this->db->prepare("SELECT COUNT(id) AS count FROM quotations WHERE request_id = ?");
                $stmtQuotes->bind_param('i', $id);
                $stmtQuotes->execute();
                $qCount = $stmtQuotes->get_result()->fetch_assoc()['count'] ?? 0;

                if ($qCount < 2) {
                    $nextStepInfo = 'Purchase Executive (' . htmlspecialchars($pr['assigned_executive_name'] ?? 'Assigned Executive') . ') is collecting quotations. Currently ' . $qCount . ' quote(s) collected. (Min. 2 required for CST)';
                } else {
                    // Check if CST winners are chosen
                    $stmtWinners = $this->db->prepare("
                        SELECT COUNT(ci.id) AS count 
                        FROM comparison_items ci
                        JOIN quotation_comparisons qc ON qc.id = ci.comparison_id
                        WHERE qc.request_id = ? AND ci.is_selected = 1
                    ");
                    $stmtWinners->bind_param('i', $id);
                    $stmtWinners->execute();
                    $winCount = $stmtWinners->get_result()->fetch_assoc()['count'] ?? 0;

                    if ($winCount == 0) {
                        $nextStepInfo = 'CST Comparison in progress. Awaiting winning vendor selection.';
                    } else {
                        // Check if PO is generated
                        $stmtPo = $this->db->prepare("
                            SELECT po.id AS po_id, po.status AS po_status, po.approved_by_pm
                            FROM purchase_orders po
                            WHERE po.request_id = ?
                            ORDER BY po.id DESC LIMIT 1
                        ");
                        $stmtPo->bind_param('i', $id);
                        $stmtPo->execute();
                        $linkedPo = $stmtPo->get_result()->fetch_assoc();

                        if (!$linkedPo) {
                            $nextStepInfo = 'CST winner selected. Awaiting Purchase Order generation.';
                        } else {
                            if ($linkedPo['po_status'] === 'Issued' || empty($linkedPo['approved_by_pm'])) {
                                $nextStepInfo = 'Purchase Order generated. Awaiting approval by Management/Project Manager.';
                            } elseif ($linkedPo['po_status'] === 'Approved') {
                                // Check material receiving status
                                $stmtRecv = $this->db->prepare("
                                    SELECT 
                                        COALESCE(SUM(pri.quantity), 0) AS total_req,
                                        COALESCE((
                                            SELECT SUM(mri.received_quantity)
                                            FROM material_receiving_items mri
                                            JOIN material_receiving mr ON mr.id = mri.receiving_id
                                            JOIN purchase_orders po ON po.id = mr.purchase_order_id
                                            WHERE po.request_id = ? AND mri.status = 'Accepted'
                                        ), 0) AS total_recv
                                    FROM purchase_request_items pri
                                    WHERE pri.request_id = ?
                                ");
                                $stmtRecv->bind_param('ii', $id, $id);
                                $stmtRecv->execute();
                                $recvInfo = $stmtRecv->get_result()->fetch_assoc();
                                $totalReq = (float)($recvInfo['total_req'] ?? 0);
                                $totalRecv = (float)($recvInfo['total_recv'] ?? 0);

                                if ($totalRecv <= 0) {
                                    $nextStepInfo = 'Purchase Order approved. Awaiting material dispatch and receiving at site.';
                                } elseif ($totalRecv < $totalReq) {
                                    $nextStepInfo = 'Materials partially received at site (' . number_format($totalRecv, 2) . ' / ' . number_format($totalReq, 2) . '). Awaiting remaining delivery.';
                                } else {
                                    // Check VPR status
                                    $stmtVpr = $this->db->prepare("
                                        SELECT status FROM vendor_payment_requests WHERE purchase_order_id = ?
                                    ");
                                    $stmtVpr->bind_param('i', $linkedPo['po_id']);
                                    $stmtVpr->execute();
                                    $vprRows = $stmtVpr->get_result()->fetch_all(MYSQLI_ASSOC);

                                    if (empty($vprRows)) {
                                        $nextStepInfo = 'Materials fully received. Awaiting Vendor Payment Request submission.';
                                    } else {
                                        $pendingVpr = false;
                                        $approvedVpr = false;
                                        foreach ($vprRows as $v) {
                                            if ($v['status'] === 'Submitted') {
                                                $pendingVpr = true;
                                            } elseif ($v['status'] === 'Approved' || $v['status'] === 'Partially_Paid') {
                                                $approvedVpr = true;
                                            }
                                        }
                                        if ($pendingVpr) {
                                            $nextStepInfo = 'Vendor Payment Request submitted. Awaiting approval by Management.';
                                        } elseif ($approvedVpr) {
                                            $nextStepInfo = 'Vendor Payment Request approved. Awaiting payment disbursement by Accounts.';
                                        } else {
                                            $nextStepInfo = 'All vendor payments processed / fully paid.';
                                        }
                                    }
                                }
                            } elseif ($linkedPo['po_status'] === 'Cancelled') {
                                $nextStepInfo = 'Purchase Order was Cancelled/Rejected. Need to check and regenerate PO.';
                            }
                        }
                    }
                }
            }
        }

        // Fetch project manager details (assigned to project)
        $assignedPm = null;
        if (!empty($pr['project_id'])) {
            $stmtPm = $this->db->prepare("
                SELECT u.id, u.full_name, d.name AS designation 
                FROM projects p
                JOIN users u ON p.pm_id = u.id
                LEFT JOIN designations d ON u.designation_id = d.id
                WHERE p.id = ?
            ");
            if ($stmtPm) {
                $stmtPm->bind_param('i', $pr['project_id']);
                $stmtPm->execute();
                $assignedPm = $stmtPm->get_result()->fetch_assoc();
                $stmtPm->close();
            }
        }

        // Fetch Operation Head details (Role ID 20)
        $operationHeads = [];
        $stmtOh = $this->db->prepare("
            SELECT u.id, u.full_name, d.name AS designation 
            FROM users u
            LEFT JOIN designations d ON u.designation_id = d.id
            WHERE u.role_id = 20 AND u.deleted_at IS NULL
        ");
        if ($stmtOh) {
            $stmtOh->execute();
            $operationHeads = $stmtOh->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtOh->close();
        }

        $this->view('phase2/purchase-requests/show-purchase-request', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Purchase Request #' . $id,
            'pr'            => $pr,
            'items'         => $items,
            'quotations'    => $quotations,
            'hasCST'        => $hasCST,
            'nextStepInfo'  => $nextStepInfo,
            'assignedPm'    => $assignedPm,
            'operationHeads'=> $operationHeads,
        ]);
    }

    // =========================================================================
    // APPROVE
    // =========================================================================

    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $status = $_POST['status'] ?? 'Approved';
        if (!in_array($status, ['Submitted', 'Approved', 'Rejected'])) {
            setError('Invalid status.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $note = trim($_POST['approval_note'] ?? '');

        // Fetch current PR to verify status and permissions
        $pr = $this->prModel->find($id);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $stmt = null;
        $successMsg = '';

        if ($status === 'Rejected') {
            if (!in_array($userRole, [1, 3, 15, 22])) {
                setError('You do not have permission to reject this request.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
                exit;
            }

            // Fetch assigned PM for this project
            $assignedPmId = null;
            $stmtProject = $this->db->prepare("SELECT pm_id FROM projects WHERE id = ?");
            if ($stmtProject) {
                $stmtProject->bind_param('i', $pr['project_id']);
                $stmtProject->execute();
                $resProj = $stmtProject->get_result()->fetch_assoc();
                if ($resProj) {
                    $assignedPmId = $resProj['pm_id'] !== null ? (int)$resProj['pm_id'] : null;
                }
                $stmtProject->close();
            }

            if (!in_array($userRole, [1, 3, 22]) && ($userRole === 15 && !empty($assignedPmId) && $userId !== $assignedPmId)) {
                setError('You do not have permission to reject this request. Only the assigned Project Manager for this project can reject.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
                exit;
            }

            $stmt = $this->db->prepare(
                "UPDATE purchase_requests SET status = 'Rejected', approved_by_id = ?, approved_at = NOW(), management_note = ? WHERE id = ?"
            );
            $stmt->bind_param('isi', $userId, $note, $id);
            $successMsg = "Purchase Request rejected.";
        } else { // status === 'Approved'
            if (!in_array($userRole, [1, 3, 15, 22])) {
                setError('You do not have permission to approve this request.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
                exit;
            }

            // Fetch assigned PM for this project
            $assignedPmId = null;
            $stmtProject = $this->db->prepare("SELECT pm_id FROM projects WHERE id = ?");
            if ($stmtProject) {
                $stmtProject->bind_param('i', $pr['project_id']);
                $stmtProject->execute();
                $resProj = $stmtProject->get_result()->fetch_assoc();
                if ($resProj) {
                    $assignedPmId = $resProj['pm_id'] !== null ? (int)$resProj['pm_id'] : null;
                }
                $stmtProject->close();
            }

            if (!in_array($userRole, [1, 3, 22]) && ($userRole === 15 && !empty($assignedPmId) && $userId !== $assignedPmId)) {
                setError('You do not have permission to perform PM Approval. Only the assigned Project Manager for this project can approve.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
                exit;
            }

            if (in_array($pr['status'], ['Approved', 'PO Issued'])) {
                setError('This request has already been approved.');
                header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
                exit;
            }

            $stmt = $this->db->prepare(
                "UPDATE purchase_requests SET status = 'Approved', approved_by_id = ?, approved_at = NOW(), management_note = ? WHERE id = ?"
            );
            $stmt->bind_param('isi', $userId, $note, $id);
            $successMsg = "Purchase Request approved by Project Manager.";
        }

        if ($stmt && $stmt->execute()) {
            setSuccess($successMsg);
            
            // Trigger email if rejected
            if ($status === 'Rejected') {
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                // Fetch project ID and name
                $prInfo = $this->db->prepare("SELECT pr.project_id, p.name FROM purchase_requests pr JOIN projects p ON p.id = pr.project_id WHERE pr.id = ?");
                $prInfo->bind_param('i', $id);
                $prInfo->execute();
                $prRow = $prInfo->get_result()->fetch_assoc();
                if ($prRow) {
                    $mailService->notifyAfterPRRejection((int)$prRow['project_id'], $prRow['name'], $id);
                }
            }
        } else {
            setError('Failed to update status.');
        }
        header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
        exit;
    }

    // =========================================================================
    // ASSIGN EXECUTIVE
    // =========================================================================

    public function assignExecutive($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);

        // Only Admin, Management, Purchase Head, or Senior Purchase Executive can assign
        if (!in_array($userRole, [1, 3, 17, 22, 31])) {
            setError('You do not have permission to assign an executive.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $executiveId = (int)($_POST['executive_id'] ?? 0);
        if (!$executiveId) {
            setError('Please select a Purchase Executive.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $stmt = $this->db->prepare("UPDATE purchase_requests SET assigned_executive_id = ? WHERE id = ?");
        $stmt->bind_param('ii', $executiveId, $id);
        
        if ($stmt->execute()) {
            setSuccess('Purchase Executive assigned successfully.');

            // Create notification for the assigned executive
            require_once __DIR__ . '/../../models/HeadNotification.php';
            $notifModel = new \HeadNotification($this->db);
            $notifModel->create([
                'user_id'    => $executiveId,
                'type'       => 'PR Assignment',
                'title'      => 'New Purchase Request Assigned',
                'message'    => "Purchase Request PR-{$id} has been assigned to you for Quotation/CST processing.",
                'url'        => "{$GLOBALS['base_url']}/con-quotation-create/{$id}"
            ]);

        } else {
            setError('Failed to assign Purchase Executive.');
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
        exit;
    }

    // =========================================================================
    // AJAX — BOQ Remaining by Project (FIX: was missing, caused N/A)
    // GET: /con-boq-remaining/{project_id}
    // Returns JSON array of { material_id, material_name, unit, total_boq_qty, used_qty, remaining_qty }
    // =========================================================================

    public function boqRemaining($projectId)
    {
        header('Content-Type: application/json');
        $projectId = (int)$projectId;
        if (!$projectId) {
            echo json_encode([]);
            exit;
        }

        $data = $this->boqModel->getRemainingBOQ($projectId);
        echo json_encode($data);
        exit;
    }

    // =========================================================================
    // AJAX — Approved BOQs by Project with items (target-wise)
    // GET: /con-ajax-pr-boqs?project_id=N
    // Returns JSON: { boqs: [{id, boq_label, target_title, items:[{material_id,material_name,unit,boq_qty,used_qty,remaining_qty}]}] }
    // =========================================================================

    public function ajaxGetProjectBoqs()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode(['boqs' => []]);
            exit;
        }

        // Get all approved BOQs for this project (with monthly target info)
        // Migrated: phase_monthly_targets replaces target_fixed
        $stmtBoq = $this->db->prepare("
            SELECT bm.id, bm.version, bm.target_id, bm.design_type_id,
                   CONCAT(pp.phase_name, ' - ', DATE_FORMAT(pmt.target_month, '%M %Y')) AS target_title,
                   pmt.target_year       AS target_year,
                   pmt.target_month_number AS target_month,
                   NULL                  AS target_week,
                   dt.name AS design_type_name,
                   COUNT(bi.id) AS item_count
            FROM boq_master bm
            LEFT JOIN phase_monthly_targets pmt ON pmt.id  = bm.target_id
            LEFT JOIN project_phases        pp  ON pp.id   = pmt.phase_id
            LEFT JOIN design_types          dt  ON dt.id   = bm.design_type_id
            LEFT JOIN boq_items             bi  ON bi.boq_id = bm.id
            WHERE bm.project_id = ?
              AND bm.status = 'Approved'
            GROUP BY bm.id
            ORDER BY bm.id DESC");
        $stmtBoq->bind_param('i', $projectId);
        $stmtBoq->execute();
        $boqRows = $stmtBoq->get_result()->fetch_all(MYSQLI_ASSOC);

        // Month names
        $months = ['','January','February','March','April','May','June',
                   'July','August','September','October','November','December'];

        $boqs = [];
        foreach ($boqRows as $boq) {
            // BOQ label
            $label = 'BOQ #' . $boq['id'] . ' v' . ($boq['version'] ?? '1.0');
            if (!empty($boq['target_title'])) {
                $mon = $months[(int)($boq['target_month'])] ?? '';
                $wk  = $boq['target_week'] ? ' · Wk ' . $boq['target_week'] : '';
                $label .= ' — ' . $boq['target_title'] . ' [' . $mon . ' ' . $boq['target_year'] . $wk . ']';
            }
            if (!empty($boq['design_type_name'])) {
                $label .= ' (' . $boq['design_type_name'] . ')';
            }

            // Get BOQ items with remaining quantities
            $stmtItems = $this->db->prepare("
                SELECT
                    bi.material_id,
                    m.name  AS material_name,
                    m.unit,
                    COALESCE(cat.name, 'Uncategorized') AS category_name,
                    bi.quantity AS boq_qty,
                    COALESCE((
                        SELECT SUM(pri.quantity)
                        FROM purchase_request_items pri
                        JOIN purchase_requests pr ON pr.id = pri.request_id
                        WHERE pr.project_id = ?
                          AND pr.bom_id = bi.boq_id
                          AND pri.material_id = bi.material_id
                          AND pr.status NOT IN ('Rejected','Draft')
                    ), 0) AS used_qty,
                    COALESCE((
                        SELECT SUM(poi.quantity)
                        FROM purchase_order_items poi
                        JOIN purchase_orders po ON po.id = poi.purchase_order_id
                        JOIN purchase_requests pr ON pr.id = po.request_id
                        WHERE pr.project_id = ?
                          AND pr.bom_id = bi.boq_id
                          AND poi.material_id = bi.material_id
                          AND po.status NOT IN ('Draft', 'Rejected')
                    ), 0) AS po_qty,
                    COALESCE((
                        SELECT SUM(mri.received_quantity)
                        FROM material_receiving_items mri
                        JOIN material_receiving mr ON mr.id = mri.receiving_id
                        JOIN purchase_order_items poi ON poi.id = mri.purchase_order_item_id
                        JOIN purchase_orders po ON po.id = mr.purchase_order_id
                        JOIN purchase_requests pr ON pr.id = po.request_id
                        WHERE pr.project_id = ?
                          AND pr.bom_id = bi.boq_id
                          AND poi.material_id = bi.material_id
                          AND mri.status = 'Accepted'
                    ), 0) AS received_qty
                FROM boq_items bi
                JOIN con_materials m ON m.id = bi.material_id
                LEFT JOIN con_material_categories cat ON cat.id = m.category_id
                WHERE bi.boq_id = ?
                ORDER BY m.name ASC");
            $stmtItems->bind_param('iiii', $projectId, $projectId, $projectId, $boq['id']);
            $stmtItems->execute();
            $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

            // Compute remaining
            foreach ($items as &$item) {
                $item['remaining_qty'] = max(0, (float)$item['boq_qty'] - (float)$item['used_qty']);
            }
            unset($item);

            $boqs[] = [
                'id'               => $boq['id'],
                'boq_label'        => $label,
                'target_title'     => $boq['target_title'],
                'design_type_name' => $boq['design_type_name'],
                'target_year'      => $boq['target_year'],
                'target_month'     => $boq['target_month'],
                'target_week'      => $boq['target_week'],
                'item_count'       => $boq['item_count'],
                'items'            => $items,
            ];
        }

        echo json_encode(['boqs' => $boqs]);
        exit;
    }

    // =========================================================================
    // AJAX — Add New Material on-the-fly (from PR create modal)
    // POST: /con-ajax-add-material
    // Body: { category_id, name, unit }
    // Returns JSON: { success, material: { id, name, unit, category_name } }
    // =========================================================================

    public function ajaxAddMaterial()
    {
        header('Content-Type: application/json');

        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit;
        }

        $categoryId = (int)($_POST['category_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $unit       = trim($_POST['unit'] ?? '');

        if (!$categoryId || empty($name) || empty($unit)) {
            echo json_encode(['success' => false, 'error' => 'Category, name and unit are required.']);
            exit;
        }

        // Check duplicate in same category
        $dupStmt = $this->db->prepare(
            "SELECT id FROM con_materials WHERE LOWER(name) = LOWER(?) AND category_id = ? LIMIT 1"
        );
        $dupStmt->bind_param('si', $name, $categoryId);
        $dupStmt->execute();
        $dup = $dupStmt->get_result()->fetch_assoc();
        if ($dup) {
            echo json_encode(['success' => false, 'error' => "Material '{$name}' already exists in this category."]);
            exit;
        }

        // Get category name
        $catStmt = $this->db->prepare("SELECT name FROM con_material_categories WHERE id = ? LIMIT 1");
        $catStmt->bind_param('i', $categoryId);
        $catStmt->execute();
        $catRow = $catStmt->get_result()->fetch_assoc();
        if (!$catRow) {
            echo json_encode(['success' => false, 'error' => 'Category not found.']);
            exit;
        }

        // Insert
        $insStmt = $this->db->prepare(
            "INSERT INTO con_materials (category_id, name, unit, min_stock_level, created_by) VALUES (?, ?, ?, 0, ?)"
        );
        $insStmt->bind_param('issi', $categoryId, $name, $unit, $userId);
        if (!$insStmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Failed to save material.']);
            exit;
        }

        $newId = $this->db->insert_id;

        echo json_encode([
            'success'  => true,
            'material' => [
                'id'            => $newId,
                'name'          => $name,
                'unit'          => $unit,
                'category_id'   => $categoryId,
                'category_name' => $catRow['name'],
            ]
        ]);
        exit;
    }

    public function delete($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId   = (int)($_SESSION['user']['id'] ?? 0);
        $id       = (int)$id;

        $pr = $this->prModel->find($id);
        if (!$pr) {
            setError('Purchase request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        if (in_array($pr['status'], ['Approved', 'PO Issued'])) {
            setError('Approved purchase requests cannot be deleted.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $isCreator = (int)($pr['created_by'] ?? 0) === $userId;
        $isAdmin = in_array($userRole, [1, 3]);

        if (!$isCreator && !$isAdmin) {
            setError('You do not have permission to delete this purchase request.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        if ($this->prModel->delete($id)) {
            setSuccess('Purchase request deleted successfully.');
        } else {
            setError('Failed to delete purchase request.');
        }

        header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
        exit;
    }

    public function edit($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId   = (int)($_SESSION['user']['id'] ?? 0);
        $id       = (int)$id;

        $pr = $this->prModel->find($id);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        if ($pr['status'] !== 'Draft') {
            setError('Submitted or approved purchase requests cannot be edited.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
            exit;
        }

        $isCreator = (int)($pr['created_by'] ?? 0) === $userId;
        $isAdmin = in_array($userRole, [1, 3]);

        if (!$isCreator && !$isAdmin) {
            setError('You do not have permission to edit this Purchase Request.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $items = $this->prModel->getItems($id);

        $pResult = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC");
        $projects = $pResult ? $pResult->fetch_all(MYSQLI_ASSOC) : [];

        $mResult = $this->db->query("
            SELECT m.id, m.name, m.unit, m.category_id, COALESCE(c.name, 'Uncategorized') AS category_name
            FROM con_materials m
            LEFT JOIN con_material_categories c ON c.id = m.category_id
            WHERE m.deleted_at IS NULL
            ORDER BY c.name ASC, m.name ASC
        ");
        $materials = $mResult ? $mResult->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/purchase-requests/edit-purchase-request', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Edit Purchase Request #' . $id,
            'pr'            => $pr,
            'items'         => $items,
            'projects'      => $projects,
            'materials'     => $materials,
        ]);
    }

    public function update($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId   = (int)($_SESSION['user']['id'] ?? 0);
        $id       = (int)$id;

        $pr = $this->prModel->find($id);
        if (!$pr) {
            setError('Purchase Request not found.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        if ($pr['status'] !== 'Draft') {
            setError('Submitted or approved purchase requests cannot be edited.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request/{$id}");
            exit;
        }

        $isCreator = (int)($pr['created_by'] ?? 0) === $userId;
        $isAdmin = in_array($userRole, [1, 3]);

        if (!$isCreator && !$isAdmin) {
            setError('You do not have permission to edit this Purchase Request.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
            exit;
        }

        $attachment = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/purchase_requests/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName   = time() . '_' . basename($_FILES['attachment']['name']);
            $targetFile = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $targetFile)) {
                $attachment = '/public/uploads/purchase_requests/' . $fileName;
            }
        }

        $prType = $_POST['pr_type'] ?? 'boq_linked';
        $status = $_POST['status'] ?? 'Draft';

        $data = [
            'project_id'   => (int)($_POST['project_id'] ?? 0),
            'bom_id'       => ($prType === 'boq_linked' && !empty($_POST['bom_id'])) ? (int)$_POST['bom_id'] : null,
            'pr_type'      => $prType,
            'required_date'=> !empty($_POST['required_date']) ? $_POST['required_date'] : null,
            'purpose'      => trim($_POST['purpose'] ?? ''),
            'created_by'   => $userId,
            'status'       => $status,
            'notes'        => trim($_POST['notes'] ?? ''),
            'attachment'   => $attachment,
        ];

        $materialIds = $_POST['material_id'] ?? [];
        $quantities  = $_POST['quantity'] ?? [];
        $itemDates   = $_POST['item_req_date'] ?? [];

        $items = [];
        foreach ($materialIds as $i => $mid) {
            if (!empty($mid) && ($quantities[$i] ?? 0) > 0) {
                $items[] = [
                    'material_id'  => (int)$mid,
                    'quantity'     => (float)$quantities[$i],
                    'required_date'=> !empty($itemDates[$i]) ? $itemDates[$i] : null,
                ];
            }
        }

        if (empty($items)) {
            setError('At least one material item is required.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-request-edit/{$id}");
            exit;
        }

        $result = $this->prModel->update($id, $data, $items);

        if ($result['success']) {
            setSuccess($status === 'Submitted' ? 'Purchase Request submitted for PM review.' : 'Purchase Request draft updated successfully.');
            header("Location: {$GLOBALS['base_url']}/con-purchase-requests");
        } else {
            $errMsg = implode(' | ', $result['errors']);
            setError("PR update failed: {$errMsg}");
            header("Location: {$GLOBALS['base_url']}/con-purchase-request-edit/{$id}");
        }
        exit;
    }
}
