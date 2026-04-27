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
        $projects  = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $target_id = isset($_GET['target_id']) ? (int)$_GET['target_id'] : 0;
        $targetData = null;
        if ($target_id) {
            $targetModel = new \Phase2\Con_TargetFixed($this->db);
            $targetData = $targetModel->find($target_id);
        }

        $this->view('phase2/boq/create-boq', [
            'current_route' => 'con-boq-create',
            'page_title'    => 'New Bill of Quantities',
            'projects'      => $projects,
            'materials'     => $materials,
            'targetData'    => $targetData,
            'target_id'     => $target_id,
        ]);
    }

    // =========================================================================
    // BOQ — STORE
    // =========================================================================

    public function store()
    {
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
            $materialIds = $_POST['material_id'] ?? [];
            $quantities  = $_POST['quantity']    ?? [];
            $unitCosts   = $_POST['unit_cost']   ?? [];

            $items = [];
            foreach ($materialIds as $index => $mid) {
                if (!empty($mid) && ($quantities[$index] ?? 0) > 0) {
                    $items[] = [
                        'material_id' => (int)$mid,
                        'quantity'    => (float)$quantities[$index],
                        'unit_cost'   => (float)($unitCosts[$index] ?? 0),
                    ];
                }
            }

            if (!empty($items)) {
                $this->boqModel->addItems($boqId, $items);
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
        // Only PM (15) can approve BOQ. SE (12) creates but cannot approve.
        // Creator cannot approve their own submission.
        $createdBy  = (int)($boq['created_by'] ?? 0);
        $canApprove = ($currentRoleId === 15)
                      && ($currentUserId !== $createdBy);

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

        $this->view('phase2/boq/show-boq', [
            'current_route'       => 'con-boq',
            'page_title'          => 'BOQ Details — ' . $boq['project_name'],
            'boq'                 => $boq,
            'items'               => $items,
            'canApprove'          => $canApprove,
            'creatorInfo'         => $creatorInfo,
            'totalBoqQty'         => $totalBoqQty,
            'totalOrdered'        => $totalOrdered,
            'totalReceived'       => $totalReceived,
            'totalRemaining'      => $totalRemaining,
            'overallConsumedPct'  => $overallConsumedPct,
        ]);
    }

    // =========================================================================
    // BOQ — EDIT
    // =========================================================================

    public function edit($id)
    {
        $boq = $this->boqModel->find($id);
        if (!$boq) {
            setError('BOQ not found.');
            header("Location: {$GLOBALS['base_url']}/con-boq");
            exit;
        }

        if ($boq['status'] === 'Approved') {
            setError('Cannot edit an Approved BOQ.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        $items     = $this->boqModel->getItems($id);
        $projects  = $this->projectModel->getAll();
        $materials = $this->materialModel->getAll();

        $this->view('phase2/boq/edit-boq', [
            'current_route' => 'con-boq',
            'page_title'    => 'Edit BOQ — ' . $boq['project_name'],
            'boq'           => $boq,
            'items'         => $items,
            'projects'      => $projects,
            'materials'     => $materials,
        ]);
    }

    // =========================================================================
    // BOQ — UPDATE
    // =========================================================================

    public function update($id)
    {
        $data = [
            'project_id' => (int)($_POST['project_id'] ?? 0),
            'version'    => trim($_POST['version'] ?? '1.0'),
        ];

        $this->boqModel->updateMaster($id, $data);

        // Replace items
        $materialIds = $_POST['material_id'] ?? [];
        $quantities  = $_POST['quantity']    ?? [];
        $unitCosts   = $_POST['unit_cost']   ?? [];

        $items = [];
        foreach ($materialIds as $index => $mid) {
            if (!empty($mid) && ($quantities[$index] ?? 0) > 0) {
                $items[] = [
                    'material_id' => (int)$mid,
                    'quantity'    => (float)$quantities[$index],
                    'unit_cost'   => (float)($unitCosts[$index] ?? 0),
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

        // Role guard: only PM (15) can approve
        if ($roleId !== 15) {
            setError('Only the Project Manager can approve a BOQ.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        // Creator cannot approve their own BOQ
        $boq = $this->boqModel->find($id);
        if ($boq && (int)($boq['created_by'] ?? 0) === $userId) {
            setError('You cannot approve a BOQ that you created.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        $status = $_POST['status'] ?? 'Approved';

        if (!in_array($status, ['Submitted', 'Approved', 'Rejected'])) {
            setError('Invalid status.');
            header("Location: {$GLOBALS['base_url']}/con-boq-show/{$id}");
            exit;
        }

        if ($this->boqModel->updateStatus($id, $status, $userId)) {
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
    // AJAX — Targets by Project (for BOQ create form)
    // GET: ?project_id=N
    // Returns JSON: { targets: [{id, target_title, year, month, week_number, design_type_id, design_type_name}] }
    // =========================================================================

    public function ajaxGetTargetsByProject()
    {
        header('Content-Type: application/json');
        $projectId = (int)($_GET['project_id'] ?? 0);

        if (!$projectId) {
            echo json_encode(['targets' => []]);
            exit;
        }

        $stmt = $this->db->prepare("
            SELECT tf.id, tf.target_title, tf.year, tf.month, tf.week_number,
                   tf.target_percentage,
                   wp.design_type_id,
                   dt.name AS design_type_name
            FROM target_fixed tf
            LEFT JOIN project_working_plans wp ON wp.id = tf.work_plan_id
            LEFT JOIN design_types          dt ON dt.id = wp.design_type_id
            WHERE tf.project_id = ?
            ORDER BY tf.year DESC, tf.month DESC, tf.week_number ASC");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $targets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        echo json_encode(['targets' => $targets]);
        exit;
    }
}
