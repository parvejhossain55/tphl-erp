<?php
namespace Phase2;

use Controller;
use Con_BOQ;
use Project;
use Con_Material;

class Con_BOQController extends Controller
{
    protected $db;
    protected $boqModel;
    protected $projectModel;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->boqModel      = new Con_BOQ($this->db);
        $this->projectModel  = new Project($this->db);
        $this->materialModel = new Con_Material($this->db);
    }

    // =========================================================================
    // BOQ — LIST
    // =========================================================================

    public function index()
    {
        $boqs = $this->boqModel->getAllWithConsumption();

        $this->view('phase2/boq/boq-lists', [
            'current_route' => 'con-boq',
            'page_title'    => 'Bill of Quantities (BOQ)',
            'boqs'          => $boqs,
        ]);
    }

    // =========================================================================
    // BOQ — CREATE
    // =========================================================================
    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $target_id        = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
        $phaseTarget      = null;
        $targetPhaseItems = [];
        $weeklyProgress   = [];
        $existingBoqCount = 0;

        if ($target_id) {
            // ── A. Fetch phase target header (phase_monthly_targets) ──────────
            $stmt = $this->db->prepare("
                SELECT pmt.*,
                       pp.phase_name,
                       pp.weight_percentage  AS phase_weight,
                       pp.planned_start_date,
                       pp.planned_end_date,
                       pp.status             AS phase_status,
                       p.name                AS project_name,
                       p.block_no            AS project_block,
                       p.plot_no             AS project_plot,
                       u.full_name           AS created_by_name
                FROM phase_monthly_targets pmt
                INNER JOIN project_phases pp ON pp.id = pmt.phase_id
                INNER JOIN projects p        ON p.id  = pmt.project_id
                LEFT  JOIN users u           ON u.id  = pmt.created_by
                WHERE pmt.id = ?
            ");
            $stmt->bind_param('i', $target_id);
            $stmt->execute();
            $phaseTarget = $stmt->get_result()->fetch_assoc();

            if ($phaseTarget) {
                // ── Count existing BOQs for this target ───────────────────────
                $cntStmt = $this->db->prepare(
                    "SELECT COUNT(*) AS cnt FROM boq_master WHERE target_id = ?"
                );
                $cntStmt->bind_param('i', $target_id);
                $cntStmt->execute();
                $existingBoqCount = (int)($cntStmt->get_result()->fetch_assoc()['cnt'] ?? 0);

                // ── B. Fetch phase monthly target items ───────────────────────
                $piStmt = $this->db->prepare("
                    SELECT pmti.*,
                           wt.work_type_name,
                           c.name AS contractor_name
                    FROM phase_monthly_target_items pmti
                    LEFT JOIN work_types  wt ON wt.id = pmti.work_type_id
                    LEFT JOIN contractors c  ON c.id  = pmti.contractor_id
                    WHERE pmti.target_id = ?
                    ORDER BY pmti.sort_order ASC, pmti.id ASC
                ");
                $piStmt->bind_param('i', $target_id);
                $piStmt->execute();
                $targetPhaseItems = $piStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // ── C. Fetch weekly progress sessions (weeks 1–4) ─────────────
                $wkStmt = $this->db->prepare("
                    SELECT pwp.*
                    FROM phase_weekly_progress pwp
                    WHERE pwp.target_id = ?
                    ORDER BY pwp.week_number ASC
                ");
                $wkStmt->bind_param('i', $target_id);
                $wkStmt->execute();
                $weekSessions = $wkStmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Index by week_number for easy access in view (key = 1,2,3,4)
                foreach ($weekSessions as $ws) {
                    $weeklyProgress[(int)$ws['week_number']] = $ws;
                }
            }
        }

        $projects  = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $categoryModel = new \Con_MaterialCategory($this->db);
        $categories    = $categoryModel->getAll();

        $projectFloors = [];
        if ($target_id && $phaseTarget && !empty($phaseTarget['project_id'])) {
            $stmtFl = $this->db->prepare("SELECT id, floor_no, floor_type, slab_area_sqft FROM project_floors WHERE project_id = ? ORDER BY floor_no ASC");
            $stmtFl->bind_param('i', $phaseTarget['project_id']);
            $stmtFl->execute();
            $projectFloors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);
        }

        $this->view('phase2/boq/create-boq', [
            'current_route'     => 'con-boq-create',
            'page_title'        => 'New Bill of Quantities',
            'projects'          => $projects,
            'materials'         => $materials,
            'categories'        => $categories,
            'phaseTarget'       => $phaseTarget,
            'target_id'         => $target_id,
            'targetPhaseItems'  => $targetPhaseItems,
            'weeklyProgress'    => $weeklyProgress,
            'existingBoqCount'  => $existingBoqCount,
            'projectFloors'     => $projectFloors,
        ]);
    }


    // =========================================================================
    // BOQ — STORE
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
            'project_id'     => (int)($_POST['project_id']    ?? 0),
            'target_id'      => !empty($_POST['target_id'])    ? (int)$_POST['target_id']    : null,
            'design_type_id' => !empty($_POST['design_type_id']) ? (int)$_POST['design_type_id'] : null,
            'version'        => trim($_POST['version']         ?? '1.0'),
            'status'         => $_POST['status']               ?? 'Draft',
            'created_by'     => (int)($_SESSION['user']['id']  ?? 0),
        ];

        if (!$data['project_id']) {
            setError('Project is required.');
            header("Location: {$GLOBALS['base_url']}/con-boq-create");
            exit;
        }

        $boqId = $this->boqModel->createMaster($data);
        if ($boqId) {
            $materialIds   = $_POST['material_id'] ?? [];
            $quantities    = $_POST['quantity']    ?? [];
            $requiredDates = $_POST['required_date'] ?? [];
            $floorTags     = $_POST['floor_tag'] ?? [];

            $items = [];
            foreach ($materialIds as $index => $mid) {
                if (!empty($mid) && ($quantities[$index] ?? 0) > 0) {
                    $floorTag = trim($floorTags[$index] ?? '');
                    $floorType = 'Typical_Floor';
                    $floorNo = null;
                    if ($floorTag && strpos($floorTag, '|') !== false) {
                        list($floorType, $floorNoStr) = explode('|', $floorTag);
                        $floorNo = ($floorNoStr !== '') ? (int)$floorNoStr : null;
                    }
                    $items[] = [
                        'material_id'   => (int)$mid,
                        'floor_type'    => $floorType,
                        'floor_no'      => $floorNo,
                        'quantity'      => (float)$quantities[$index],
                        'unit_cost'     => 0.00,
                        'required_date' => !empty($requiredDates[$index]) ? $requiredDates[$index] : null,
                    ];
                }
            }

            if (!empty($items)) {
                $this->boqModel->addItems($boqId, $items);
            }

            // ── Send Email Notification ──────────────────────────────────────────
            if ($data['status'] === 'Submitted') {
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                $project = $this->projectModel->find($data['project_id']);
                if ($project) {
                    $mailService->notifyApproversForNewBOQ($data['project_id'], $project['name'], $boqId);
                }
            }

            setSuccess('BOQ created successfully.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        setError('Failed to create BOQ.');
        header("Location: {$GLOBALS['base_url']}/con-boq-create");
        exit;
    }

    // =========================================================================
    // BOQ — SHOW
    // =========================================================================

    public function show($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $boq = $this->boqModel->find($id);
        if (!$boq) {
            setError('BOQ not found.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        $items = $this->boqModel->getItems($id);

        // Per-material consumption: ordered + received vs BOQ qty
        $consumption = $this->boqModel->getBoqConsumption($id, (int)$boq['project_id']);

        // Merge consumption into items
        foreach ($items as &$item) {
            $cons = $consumption[$item['material_id']] ?? null;
            $item['ordered_qty']   = $cons ? $cons['ordered_qty']   : 0;
            $item['received_qty']  = $cons ? $cons['received_qty']  : 0;
            $item['remaining_qty'] = $cons ? $cons['remaining_qty'] : $item['quantity'];
            $item['consumed_pct']  = $cons ? $cons['consumed_pct']  : 0;
        }
        unset($item);

        // Total summary
        $totalBoqQty      = array_sum(array_column($items, 'quantity'));
        $totalOrdered     = array_sum(array_column($items, 'ordered_qty'));
        $totalReceived    = array_sum(array_column($items, 'received_qty'));
        $totalRemaining   = array_sum(array_column($items, 'remaining_qty'));
        $overallConsumedPct = $totalBoqQty > 0
            ? min(100, round($totalReceived / $totalBoqQty * 100, 1)) : 0;

        $currentUserId = (int)($_SESSION['user']['id']   ?? 0);
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // ── Approval permission ─────────────────────────────────────────────
        // Roles 1 (MD), 3 (Director), 15 (PM), 22 (PM Head) can approve BOQ.
        $createdBy  = (int)($boq['created_by'] ?? 0);
        $canApprove = in_array($currentRoleId, [1, 3, 15, 22]);

        // Only PM (role 15) can edit even after approval
        $canEdit = ($currentRoleId === 15);

        // Fetch creator info (name + designation)
        $creatorInfo = [];
        if ($createdBy) {
            $stmt = $this->db->prepare("
                SELECT u.full_name,
                       COALESCE(d.name, '') AS designation
                FROM   users u
                LEFT JOIN employees   e ON e.user_id = u.id
                LEFT JOIN designations d ON d.id = e.designation_id
                WHERE  u.id = ? LIMIT 1");
            $stmt->bind_param('i', $createdBy);
            $stmt->execute();
            $creatorInfo = $stmt->get_result()->fetch_assoc() ?? [];
        }

        // Fetch linked Purchase Request details (if any)
        $stmtPr = $this->db->prepare("
            SELECT pr.id AS pr_id, pr.status AS pr_status, pr.created_at AS pr_created_at, pr.assigned_executive_id,
                   exec.full_name AS executive_name
            FROM purchase_requests pr
            LEFT JOIN users exec ON exec.id = pr.assigned_executive_id
            WHERE pr.bom_id = ?
            ORDER BY pr.id DESC LIMIT 1
        ");
        $stmtPr->bind_param('i', $id);
        $stmtPr->execute();
        $linkedPr = $stmtPr->get_result()->fetch_assoc();

        $linkedPo = null;
        $vprDetails = [];
        $totalPaid = 0.00;
        $vendors = [];
        $nextStepInfo = 'Create Purchase Request';

        if ($linkedPr) {
            // Find linked PO
            $stmtPo = $this->db->prepare("
                SELECT po.id AS po_id, po.status AS po_status, po.order_date AS po_date,
                       po.approved_by_pm
                FROM purchase_orders po
                WHERE po.request_id = ?
                ORDER BY po.id DESC LIMIT 1
            ");
            $stmtPo->bind_param('i', $linkedPr['pr_id']);
            $stmtPo->execute();
            $linkedPo = $stmtPo->get_result()->fetch_assoc();

            if ($linkedPo) {
                // Get vendors for this PO
                $stmtVendors = $this->db->prepare("
                    SELECT DISTINCT v.name
                    FROM purchase_order_items poi
                    JOIN vendors v ON v.id = poi.selected_vendor_id
                    WHERE poi.purchase_order_id = ?
                ");
                $stmtVendors->bind_param('i', $linkedPo['po_id']);
                $stmtVendors->execute();
                $vendorRows = $stmtVendors->get_result()->fetch_all(MYSQLI_ASSOC);
                $vendors = array_column($vendorRows, 'name');

                // Get payment requests and actual amount paid for this PO
                $stmtVpr = $this->db->prepare("
                    SELECT vpr.id, vpr.payment_type, vpr.net_payable, vpr.status AS vpr_status,
                           COALESCE(SUM(cft.amount_paid), 0) AS paid_amount
                    FROM vendor_payment_requests vpr
                    LEFT JOIN con_financial_transactions cft ON cft.ref_id = vpr.id AND cft.ref_type = 'vendor_payment'
                    WHERE vpr.purchase_order_id = ?
                    GROUP BY vpr.id
                ");
                $stmtVpr->bind_param('i', $linkedPo['po_id']);
                $stmtVpr->execute();
                $vprDetails = $stmtVpr->get_result()->fetch_all(MYSQLI_ASSOC);
                
                foreach ($vprDetails as $vpr) {
                    $totalPaid += (float)$vpr['paid_amount'];
                }
            }

            // Determine next step / block info
            if ($linkedPr['pr_status'] === 'Draft') {
                $nextStepInfo = 'Submit Purchase Request for review (Action required by Site Engineer / Creator).';
            } elseif ($linkedPr['pr_status'] === 'Submitted') {
                $nextStepInfo = 'Purchase Request is submitted. Awaiting approval by Project Manager / Management.';
            } elseif ($linkedPr['pr_status'] === 'Rejected') {
                $nextStepInfo = 'Purchase Request was Rejected. Need to revise details or create a new PR.';
            } elseif ($linkedPr['pr_status'] === 'Approved') {
                if (empty($linkedPr['assigned_executive_id'])) {
                    $nextStepInfo = 'Awaiting Purchase Executive assignment by Purchase Head or Admin.';
                } else {
                    // Check if quotations count is at least 2
                    $stmtQuotes = $this->db->prepare("SELECT COUNT(id) AS count FROM quotations WHERE request_id = ?");
                    $stmtQuotes->bind_param('i', $linkedPr['pr_id']);
                    $stmtQuotes->execute();
                    $qCount = $stmtQuotes->get_result()->fetch_assoc()['count'] ?? 0;

                    if ($qCount < 2) {
                        $nextStepInfo = 'Purchase Executive (' . htmlspecialchars($linkedPr['executive_name']) . ') is collecting quotations. Currently ' . $qCount . ' quote(s) collected. (Min. 2 required for CST)';
                    } else {
                        // Check if CST winners are chosen
                        $stmtWinners = $this->db->prepare("
                            SELECT COUNT(ci.id) AS count 
                            FROM comparison_items ci
                            JOIN quotation_comparisons qc ON qc.id = ci.comparison_id
                            WHERE qc.request_id = ? AND ci.is_selected = 1
                        ");
                        $stmtWinners->bind_param('i', $linkedPr['pr_id']);
                        $stmtWinners->execute();
                        $winCount = $stmtWinners->get_result()->fetch_assoc()['count'] ?? 0;

                        if ($winCount == 0) {
                            $nextStepInfo = 'CST Comparison in progress. Awaiting winning vendor selection.';
                        } else {
                            $nextStepInfo = 'CST winner selected. Awaiting Purchase Order generation.';
                        }
                    }
                }
            } elseif ($linkedPr['pr_status'] === 'PO Issued' || $linkedPo) {
                if ($linkedPo) {
                    if ($linkedPo['po_status'] === 'Issued' || empty($linkedPo['approved_by_pm'])) {
                        $nextStepInfo = 'Purchase Order generated. Awaiting approval by Management/Project Manager.';
                    } elseif ($linkedPo['po_status'] === 'Approved') {
                        // Check material receiving status
                        $totalRecv = (float)$totalReceived;
                        $totalBq = (float)$totalBoqQty;
                        if ($totalRecv <= 0) {
                            $nextStepInfo = 'Purchase Order approved. Awaiting material dispatch and receiving at site.';
                        } elseif ($totalRecv < $totalBq) {
                            $nextStepInfo = 'Materials partially received at site (' . number_format($totalRecv, 2) . ' / ' . number_format($totalBq, 2) . '). Awaiting remaining delivery.';
                        } else {
                            // Check payment status
                            if (empty($vprDetails)) {
                                $nextStepInfo = 'Materials fully received. Awaiting Vendor Payment Request submission.';
                            } else {
                                $pendingVpr = false;
                                $approvedVpr = false;
                                foreach ($vprDetails as $v) {
                                    if ($v['vpr_status'] === 'Submitted') {
                                        $pendingVpr = true;
                                    } elseif ($v['vpr_status'] === 'Approved' || $v['vpr_status'] === 'Partially_Paid') {
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
                } else {
                    $nextStepInfo = 'Purchase Request updated to PO Issued. Awaiting PO retrieval.';
                }
            }
        }

        $this->view('phase2/boq/show-boq', [
            'current_route'       => 'con-boq',
            'page_title'          => 'BOQ Details — ' . $boq['project_name'],
            'boq'                 => $boq,
            'items'               => $items,
            'canApprove'          => $canApprove,
            'canEdit'             => $canEdit,
            'creatorInfo'         => $creatorInfo,
            'totalBoqQty'         => $totalBoqQty,
            'totalOrdered'        => $totalOrdered,
            'totalReceived'       => $totalReceived,
            'totalRemaining'      => $totalRemaining,
            'overallConsumedPct'  => $overallConsumedPct,
            'linkedPr'            => $linkedPr,
            'linkedPo'            => $linkedPo,
            'vprDetails'          => $vprDetails,
            'totalPaid'           => $totalPaid,
            'vendors'             => $vendors,
            'nextStepInfo'        => $nextStepInfo,
        ]);
    }

    // =========================================================================
    // BOQ — EDIT
    // =========================================================================

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $boq = $this->boqModel->find($id);
        if (!$boq) {
            setError('BOQ not found.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Role 15 (PM) can edit even approved BOQ — others cannot
        if ($boq['status'] === 'Approved' && $roleId !== 15) {
            setError('Cannot edit an Approved BOQ. Only Project Manager can edit approved BOQs.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        $items     = $this->boqModel->getItems($id);
        $projects  = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $stmtFl = $this->db->prepare("SELECT id, floor_no, floor_type, slab_area_sqft FROM project_floors WHERE project_id = ? ORDER BY floor_no ASC");
        $stmtFl->bind_param('i', $boq['project_id']);
        $stmtFl->execute();
        $projectFloors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/boq/edit-boq', [
            'current_route' => 'con-boq',
            'page_title'    => 'Edit BOQ — ' . $boq['project_name'],
            'boq'           => $boq,
            'items'         => $items,
            'projects'      => $projects,
            'materials'     => $materials,
            'projectFloors' => $projectFloors,
        ]);
    }

    // =========================================================================
    // BOQ — UPDATE
    // =========================================================================

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $data = [
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'version'    => trim($_POST['version'] ?? '1.0'),
        ];

        $this->boqModel->updateMaster($id, $data);

        // Replace items
        $materialIds   = $_POST['material_id'] ?? [];
        $quantities    = $_POST['quantity']    ?? [];
        $requiredDates = $_POST['required_date'] ?? [];
        $floorTags     = $_POST['floor_tag'] ?? [];

        $items = [];
        foreach ($materialIds as $index => $mid) {
            if (!empty($mid) && ($quantities[$index] ?? 0) > 0) {
                $floorTag = trim($floorTags[$index] ?? '');
                $floorType = 'Typical_Floor';
                $floorNo = null;
                if ($floorTag && strpos($floorTag, '|') !== false) {
                    list($floorType, $floorNoStr) = explode('|', $floorTag);
                    $floorNo = ($floorNoStr !== '') ? (int)$floorNoStr : null;
                }
                $items[] = [
                    'material_id'   => (int)$mid,
                    'floor_type'    => $floorType,
                    'floor_no'      => $floorNo,
                    'quantity'      => (float)$quantities[$index],
                    'unit_cost'     => 0.00,
                    'required_date' => !empty($requiredDates[$index]) ? $requiredDates[$index] : null,
                ];
            }
        }

        if (!empty($items)) {
            $this->boqModel->addItems($id, $items);
        }

        setSuccess('BOQ updated successfully.');
        header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
        exit;
    }

    // =========================================================================
    // BOQ — APPROVE / SUBMIT / REJECT
    // =========================================================================

    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id']   ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $status = $_POST['status'] ?? 'Approved';

        if (!in_array($status, ['Submitted', 'Approved', 'Rejected'])) {
            setError('Invalid status.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        $boq = $this->boqModel->find($id);

        if ($status === 'Submitted') {
            // Only creator or PM/SE can submit
            if (!$boq || ((int)($boq['created_by'] ?? 0) !== $userId && !in_array($roleId, [1, 3, 12, 15, 22]))) {
                setError('You do not have permission to submit this BOQ.');
                header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
                exit;
            }
        } else {
            // 'Approved' or 'Rejected'
            // Role guard: only 1, 3, 15, 22 can approve/reject
            if (!in_array($roleId, [1, 3, 15, 22])) {
                setError('Only authorized approvers can approve/reject a BOQ.');
                header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
                exit;
            }

            // Creator cannot approve their own BOQ
            if ($boq && (int)($boq['created_by'] ?? 0) === $userId) {
                setError('You cannot approve a BOQ that you created.');
                header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
                exit;
            }
        }


        if ($this->boqModel->updateStatus($id, $status, $userId)) {
            // ── Send Email Notification on Submission ────────────────────────
            if ($status === 'Submitted' && $boq) {
                require_once __DIR__ . '/../../core/ProjectWorkflowMailService.php';
                $mailService = new \ProjectWorkflowMailService($this->db);
                $project = $this->projectModel->find($boq['project_id']);
                if ($project) {
                    $mailService->notifyApproversForNewBOQ($boq['project_id'], $project['name'], $id);
                }
            }
            setSuccess("BOQ status updated to {$status}.");
        } else {
            setError('Failed to update status.');
        }
        header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
        exit;
    }

    // =========================================================================
    // BOQ — DELETE
    // =========================================================================

    public function delete()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 15, 20, 22])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($this->boqModel->delete($id)) {
            setSuccess('BOQ deleted.');
        } else {
            setError('Cannot delete — BOQ may be Approved or not found.');
        }
        header("Location: {$GLOBALS['base_url']}/con-boq");
        exit;
    }

    // =========================================================================
    // AJAX — Monthly Targets by Project (for BOQ create form)
    // GET: ?project_id=N
    // Returns JSON: { targets: [{id, target_title, year, month, week_number, design_type_id, design_type_name, boq_id}] }
    // =========================================================================

    public function ajaxGetTargetsByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode(['targets' => []]);
            exit;
        }

        // Migrated: now queries phase_monthly_targets via project_phases
        $stmt = $this->db->prepare("
            SELECT pmt.id,
                   COALESCE(pmt.title, CONCAT(pp.phase_name,' - Month ',pmt.month_number)) AS target_title,
                   YEAR(pmt.created_at)  AS year,
                   pmt.month_number      AS month,
                   NULL                  AS week_number,
                   pmt.target_percentage,
                   NULL                  AS design_type_id,
                   NULL                  AS design_type_name,
                   (SELECT id FROM boq_master WHERE target_id = pmt.id LIMIT 1) AS boq_id
            FROM phase_monthly_targets pmt
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            WHERE pp.project_id = ?
            ORDER BY pp.phase_order ASC, pmt.month_number ASC");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $targets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch project floors
        $stmtFl = $this->db->prepare("SELECT id, floor_no, floor_type, slab_area_sqft FROM project_floors WHERE project_id = ? ORDER BY floor_no ASC");
        $stmtFl->bind_param('i', $projectId);
        $stmtFl->execute();
        $floors = $stmtFl->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['targets' => $targets, 'floors' => $floors]);
        exit;
    }

    // =========================================================================
    // AJAX — Material Stock by Project & Material (for BOQ create form)
    // GET: ?project_id=X&material_id=Y
    // Returns JSON
    // =========================================================================
    public function ajaxGetMaterialStock()
    {
        header('Content-Type: application/json');
        $projectId  = (int)($_GET['project_id'] ?? 0);
        $materialId = (int)($_GET['material_id'] ?? 0);

        if (!$projectId || !$materialId) {
            echo json_encode([
                'success' => false,
                'message' => 'Project and Material are required.',
                'total_stock' => 0,
                'unit' => '',
                'warehouses' => []
            ]);
            exit;
        }

        // Fetch project name
        $projectName = '';
        $stmtProj = $this->db->prepare("SELECT name FROM projects WHERE id = ? LIMIT 1");
        $stmtProj->bind_param('i', $projectId);
        $stmtProj->execute();
        $projRow = $stmtProj->get_result()->fetch_assoc();
        if ($projRow) {
            $projectName = $projRow['name'];
        }

        // Fetch material name and unit
        $materialName = '';
        $unit = '';
        $stmtMat = $this->db->prepare("SELECT name, unit FROM con_materials WHERE id = ? LIMIT 1");
        $stmtMat->bind_param('i', $materialId);
        $stmtMat->execute();
        $matRow = $stmtMat->get_result()->fetch_assoc();
        if ($matRow) {
            $materialName = $matRow['name'];
            $unit         = $matRow['unit'];
        }

        // Fetch all warehouses for the project, LEFT JOINing their stock levels for the selected material
        $stmtStock = $this->db->prepare("
            SELECT w.name AS warehouse_name, COALESCE(s.current_quantity, 0) AS current_quantity
            FROM con_warehouses w
            LEFT JOIN con_stocks s ON s.warehouse_id = w.id AND s.material_id = ?
            WHERE w.project_id = ?
            ORDER BY w.name ASC
        ");
        $stmtStock->bind_param('ii', $materialId, $projectId);
        $stmtStock->execute();
        $rows = $stmtStock->get_result()->fetch_all(MYSQLI_ASSOC);

        $totalStock = 0.0;
        $warehouses = [];
        foreach ($rows as $row) {
            $qty = (float)$row['current_quantity'];
            $totalStock += $qty;
            $warehouses[] = [
                'warehouse_name'   => $row['warehouse_name'],
                'current_quantity' => $qty
            ];
        }

        echo json_encode([
            'success'       => true,
            'project_name'  => $projectName,
            'material_name' => $materialName,
            'total_stock'   => $totalStock,
            'unit'          => $unit,
            'warehouses'    => $warehouses
        ]);
        exit;
    }

    // =========================================================================
    // AJAX — Check BOQ requested quantities against Initial Estimate floor budgets
    // POST: project_id, boq_id (optional), material_id[], quantity[], floor_tag[]
    // Returns JSON: { success: true, exceeded: bool, items: [...] }
    // =========================================================================
    public function ajaxCheckBOQLimits()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_POST['project_id'] ?? 0);
        $boqId     = (int)($_POST['boq_id'] ?? 0);
        
        $materialIds = $_POST['material_id'] ?? [];
        $floorTags   = $_POST['floor_tag'] ?? [];
        $quantities  = $_POST['quantity'] ?? [];

        if (!$projectId) {
            echo json_encode(['success' => true, 'exceeded' => false, 'items' => []]);
            exit;
        }

        // Find the active budget (prefer approved, fallback to latest)
        $stmtB = $this->db->prepare("
            SELECT id FROM initial_estimate_budgets 
            WHERE project_id = ? 
            ORDER BY CASE WHEN status = 'approved' THEN 1 ELSE 2 END ASC, id DESC 
            LIMIT 1
        ");
        $stmtB->bind_param('i', $projectId);
        $stmtB->execute();
        $budgetRow = $stmtB->get_result()->fetch_assoc();
        $budgetId = $budgetRow ? (int)$budgetRow['id'] : 0;

        $exceededItems = [];
        $hasExceeded = false;

        // Group requests by material_id + floor to handle duplicate rows
        $groupedRequests = [];
        foreach ($materialIds as $index => $mid) {
            if (empty($mid)) continue;
            $qty = (float)($quantities[$index] ?? 0);
            if ($qty <= 0) continue;

            $floorTag = trim($floorTags[$index] ?? '');
            $floorType = 'Typical_Floor';
            $floorNo = null;
            if ($floorTag && strpos($floorTag, '|') !== false) {
                list($floorType, $floorNoStr) = explode('|', $floorTag);
                $floorNo = ($floorNoStr !== '') ? (int)$floorNoStr : null;
            }

            $key = $mid . '_' . $floorType . '_' . ($floorNo !== null ? $floorNo : 'null');
            if (!isset($groupedRequests[$key])) {
                $groupedRequests[$key] = [
                    'material_id' => (int)$mid,
                    'floor_type'  => $floorType,
                    'floor_no'    => $floorNo,
                    'quantity'    => 0.0
                ];
            }
            $groupedRequests[$key]['quantity'] += $qty;
        }

        foreach ($groupedRequests as $req) {
            $mid = $req['material_id'];
            $floorType = $req['floor_type'];
            $floorNo = $req['floor_no'];
            $reqQty = $req['quantity'];

            // Fetch material name and unit
            $stmtMat = $this->db->prepare("SELECT name, unit FROM con_materials WHERE id = ?");
            $stmtMat->bind_param('i', $mid);
            $stmtMat->execute();
            $mat = $stmtMat->get_result()->fetch_assoc();
            $matName = $mat ? $mat['name'] : 'Unknown';
            $unitName = $mat ? $mat['unit'] : '';

            // Fetch budget limit for this specific floor and material
            $budgetLimit = 0.0;
            if ($budgetId > 0) {
                if ($floorNo === null) {
                    $stmtLimit = $this->db->prepare("
                        SELECT SUM(estimated_qty) AS lim 
                        FROM initial_estimate_items 
                        WHERE budget_id = ? AND material_id = ? AND floor_type = ? AND floor_no IS NULL
                    ");
                    $stmtLimit->bind_param('iis', $budgetId, $mid, $floorType);
                } else {
                    $stmtLimit = $this->db->prepare("
                        SELECT SUM(estimated_qty) AS lim 
                        FROM initial_estimate_items 
                        WHERE budget_id = ? AND material_id = ? AND floor_type = ? AND floor_no = ?
                    ");
                    $stmtLimit->bind_param('iisi', $budgetId, $mid, $floorType, $floorNo);
                }
                $stmtLimit->execute();
                $limitRow = $stmtLimit->get_result()->fetch_assoc();
                $budgetLimit = $limitRow ? (float)$limitRow['lim'] : 0.0;
            }

            // Fetch quantity already requested in other non-rejected BOQs
            if ($floorNo === null) {
                $stmtPast = $this->db->prepare("
                    SELECT SUM(bi.quantity) AS past 
                    FROM boq_items bi
                    JOIN boq_master bm ON bm.id = bi.boq_id
                    WHERE bm.project_id = ? 
                      AND bm.id != ? 
                      AND bm.status != 'Rejected' 
                      AND bi.material_id = ? 
                      AND bi.floor_type = ? 
                      AND bi.floor_no IS NULL
                ");
                $stmtPast->bind_param('iiis', $projectId, $boqId, $mid, $floorType);
            } else {
                $stmtPast = $this->db->prepare("
                    SELECT SUM(bi.quantity) AS past 
                    FROM boq_items bi
                    JOIN boq_master bm ON bm.id = bi.boq_id
                    WHERE bm.project_id = ? 
                      AND bm.id != ? 
                      AND bm.status != 'Rejected' 
                      AND bi.material_id = ? 
                      AND bi.floor_type = ? 
                      AND bi.floor_no = ?
                ");
                $stmtPast->bind_param('iiiisi', $projectId, $boqId, $mid, $floorType, $floorNo);
            }
            $stmtPast->execute();
            $pastRow = $stmtPast->get_result()->fetch_assoc();
            $pastQty = $pastRow ? (float)$pastRow['past'] : 0.0;

            // Check if limit exceeded
            if (($reqQty + $pastQty) > $budgetLimit) {
                $hasExceeded = true;
                
                // Construct Floor Label
                $floorLabel = 'General';
                if ($floorType === 'Roof') {
                    $floorLabel = 'Roof (Lvl ' . $floorNo . ')';
                } elseif ($floorType === 'Ground_Floor') {
                    $floorLabel = 'Ground Floor';
                } elseif ($floorType === 'Basement') {
                    $floorLabel = 'Basement ' . abs($floorNo);
                } elseif ($floorNo !== null && $floorNo !== '') {
                    $n = $floorNo;
                    $s = ["th", "st", "nd", "rd"];
                    $v = $n % 100;
                    $ord = $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
                    $floorLabel = $ord . ' Floor';
                }

                $exceededItems[] = [
                    'material_name' => $matName,
                    'unit'          => $unitName,
                    'floor_label'   => $floorLabel,
                    'budget_limit'  => $budgetLimit,
                    'past_qty'      => $pastQty,
                    'requested_qty' => $reqQty,
                    'total_qty'     => $reqQty + $pastQty,
                    'excess_qty'    => ($reqQty + $pastQty) - $budgetLimit
                ];
            }
        }

        echo json_encode([
            'success'  => true,
            'exceeded' => $hasExceeded,
            'items'    => $exceededItems
        ]);
        exit;
    }
}
