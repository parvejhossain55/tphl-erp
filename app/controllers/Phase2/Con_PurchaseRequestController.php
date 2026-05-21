<?php
namespace Phase2;

use Controller;
use Con_PurchaseRequest;
use Con_BOQ;
use Project;
use Con_Material;
use Con_Quotation;

class Con_PurchaseRequestController extends Controller
{
    protected $db;
    protected $prModel;
    protected $boqModel;
    protected $projectModel;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->prModel       = new Con_PurchaseRequest($this->db);
        $this->boqModel      = new Con_BOQ($this->db);
        $this->projectModel  = new Project($this->db);
        $this->materialModel = new Con_Material($this->db);
    }

    // =========================================================================
    // LIST
    // =========================================================================

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId   = (int)($_SESSION['user']['id'] ?? 0);

        // Filter for Purchase Executive
        $executiveId = ($userRole === 23) ? $userId : null;
        $requests = $this->prModel->getAll($executiveId);

        // Fetch Purchase Executives (Role ID 23) for assignment dropdown
        $executives = [];
        if (in_array($userRole, [1, 3, 17, 22])) { // Admin, Management, Purchase Head
            $stmt = $this->db->prepare("SELECT id, full_name FROM users WHERE role_id = 23 ORDER BY full_name");
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
        if (in_array($userRole, [1, 3, 15, 20, 22])) {
            setError('You do not have permission to create Purchase Requests. You can only approve them.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        // ── Project list based on role ────────────────────────────────────────
        if (in_array($userRole, [1, 3, 17, 22])) {
            // Admin / Super Admin / Purchase Team Leader / PM Head → all projects
            $pResult  = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name ASC");
            $projects = $pResult ? $pResult->fetch_all(MYSQLI_ASSOC) : [];

        } elseif ($userRole === 23) {
            // Purchase Executive → only projects assigned via purchase_user_id
            $pStmt = $this->db->prepare("
                SELECT id, name FROM projects
                WHERE deleted_at IS NULL
                  AND purchase_user_id = ?
                ORDER BY name ASC
            ");
            $pStmt->bind_param('i', $userId);
            $pStmt->execute();
            $projects = $pStmt->get_result()->fetch_all(MYSQLI_ASSOC);

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

        $this->view('phase2/purchase-requests/create-purchase-request', [
            'current_route' => 'con-purchase-request-create',
            'page_title'    => 'New Purchase Request',
            'projects'      => $projects,
            'materials'     => $materials,
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
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'bom_id'     => !empty($_POST['bom_id']) ? (int)$_POST['bom_id'] : null,
            'created_by' => (int)($_SESSION['user']['id'] ?? 0),
            'status'     => $_POST['status'] ?? 'Draft',
            'notes'      => trim($_POST['notes'] ?? ''),
        ];

        $materialIds = $_POST['material_id'] ?? [];
        $quantities  = $_POST['quantity']    ?? [];

        $items = [];
        foreach ($materialIds as $i => $mid) {
            if (!empty($mid) && ($quantities[$i] ?? 0) > 0) {
                $items[] = [
                    'material_id' => (int)$mid,
                    'quantity'    => (float)$quantities[$i],
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

        $this->view('phase2/purchase-requests/show-purchase-request', [
            'current_route' => 'con-purchase-requests',
            'page_title'    => 'Purchase Request #' . $id,
            'pr'            => $pr,
            'items'         => $items,
            'quotations'    => $quotations,
            'hasCST'        => $hasCST,
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
        $note = trim($_POST['approval_note'] ?? '');

        // Update status + track approver
        $stmt = $this->db->prepare(
            "UPDATE purchase_requests SET status = ?, approved_by_id = ?, approved_at = NOW(), management_note = ? WHERE id = ?"
        );
        $stmt->bind_param('sisi', $status, $userId, $note, $id);

        if ($stmt->execute()) {
            setSuccess("Purchase Request status updated to {$status}.");
            
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

        // Only Admin, Management, or Purchase Head can assign
        if (!in_array($userRole, [1, 3, 17, 22])) {
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

        // Get all approved BOQs for this project (with target info)
        $stmtBoq = $this->db->prepare("
            SELECT bm.id, bm.version, bm.target_id, bm.design_type_id,
                   tf.target_title,
                   tf.year  AS target_year,
                   tf.month AS target_month,
                   tf.week_number AS target_week,
                   dt.name AS design_type_name,
                   COUNT(bi.id) AS item_count
            FROM boq_master bm
            LEFT JOIN target_fixed  tf ON tf.id = bm.target_id
            LEFT JOIN design_types  dt ON dt.id = bm.design_type_id
            LEFT JOIN boq_items     bi ON bi.boq_id = bm.id
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
}
