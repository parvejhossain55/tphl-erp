<?php
namespace Phase2;

use Controller;
use Con_Wastage;

class Con_WastageController extends Controller
{
    protected $db;
    protected $wastageModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->wastageModel = new \Con_Wastage($this->db);
    }

    private function handleFileUpload($fileInputName)
    {
        if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../public/uploads/wastage/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = time() . '_' . basename($_FILES[$fileInputName]['name']);
            $targetPath = $uploadDir . $fileName;
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $targetPath)) {
                return 'uploads/wastage/' . $fileName;
            }
        }
        return null;
    }

    public function index()
    {
        $wastages = $this->wastageModel->getAll();

        $this->view('phase2/wastage/index', [
            'current_route' => 'con-wastage',
            'page_title'    => 'Wastage Management',
            'wastages'      => $wastages
        ]);
    }

    public function create()
    {
        // Get projects and materials for dropdowns
        $projects = $this->db->query("SELECT id, name FROM projects WHERE deleted_at IS NULL AND under_construction = 1 ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $warehouses = $this->db->query("SELECT id, name, project_id FROM con_warehouses ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
        $materials = $this->db->query("SELECT m.id, m.name, m.unit, c.name AS category_name FROM con_materials m LEFT JOIN con_material_categories c ON c.id = m.category_id ORDER BY m.name ASC")->fetch_all(MYSQLI_ASSOC);

        $this->view('phase2/wastage/create', [
            'current_route' => 'con-wastage-create',
            'page_title'    => 'Record Wastage',
            'projects'      => $projects,
            'warehouses'    => $warehouses,
            'materials'     => $materials
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: {$GLOBALS['base_url']}/con-wastage");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $projectId = (int)$_POST['project_id'];
        $warehouseId = (int)$_POST['warehouse_id'];
        
        $materialIds = $_POST['material_id'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $conditions = $_POST['condition_note'] ?? [];
        $sellingAmounts = $_POST['selling_amount'] ?? [];

        $totalSellingAmount = 0;
        $items = [];

        // Determine number of items
        $count = count($materialIds);
        for ($i = 0; $i < $count; $i++) {
            $amount = (int)($sellingAmounts[$i] ?? 0);
            $qty = (int)($quantities[$i] ?? 0);
            
            if ($qty <= 0 || $amount <= 0) {
                setError('Quantity and Selling Amount must be valid whole numbers greater than 0.');
                header("Location: {$GLOBALS['base_url']}/con-wastage-create");
                exit;
            }

            $totalSellingAmount += $amount;
            
            $docPath = null;
            if (isset($_FILES['document']['name'][$i]) && $_FILES['document']['error'][$i] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../../public/uploads/wastage/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . $i . '_' . basename($_FILES['document']['name'][$i]);
                if (move_uploaded_file($_FILES['document']['tmp_name'][$i], $uploadDir . $fileName)) {
                    $docPath = 'uploads/wastage/' . $fileName;
                }
            }

            $items[] = [
                'material_id' => (int)$materialIds[$i],
                'quantity' => $qty,
                'condition_note' => $conditions[$i] ?? '',
                'selling_amount' => $amount,
                'document' => $docPath
            ];
        }

        $data = [
            'project_id' => $projectId,
            'warehouse_id' => $warehouseId,
            'wastage_type' => $_POST['wastage_type'] ?? 'selling_scrap',
            'recorded_by' => $userId,
            'total_selling_amount' => $totalSellingAmount
        ];

        $res = $this->wastageModel->create($data, $items);
        if ($res['success']) {
            setSuccess('Wastage record created successfully. Awaiting QC approval.');
            header("Location: {$GLOBALS['base_url']}/con-wastage-show/{$res['id']}");
        } else {
            setError('Failed to create wastage record: ' . $res['error']);
            header("Location: {$GLOBALS['base_url']}/con-wastage-create");
        }
        exit;
    }

    public function show($id)
    {
        $wastage = $this->wastageModel->find($id);
        if (!$wastage) {
            setError('Wastage record not found.');
            header("Location: {$GLOBALS['base_url']}/con-wastage");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $currentRoleId = (int)($_SESSION['user']['role_id'] ?? 0);

        $items = $this->wastageModel->getItems($id);

        $status = $wastage['status'];
        // Approvals mapping
        $canApproveQC = ($status === 'Pending QC' && $currentRoleId == 13);
        $canApprovePurchase = ($status === 'Pending Purchase' && $currentRoleId == 17);
        $canReceivePayment = ($status === 'Pending Payment' && $currentRoleId == 6);

        $this->view('phase2/wastage/show', [
            'current_route'      => 'con-wastage',
            'page_title'         => 'Wastage Record #' . $id,
            'wastage'            => $wastage,
            'items'              => $items,
            'canApproveQC'       => $canApproveQC,
            'canApprovePurchase' => $canApprovePurchase,
            'canReceivePayment'  => $canReceivePayment,
        ]);
    }

    public function approveQC($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        
        $note = trim($_POST['qc_note'] ?? '');
        $docPath = $this->handleFileUpload('qc_doc');

        if ($this->wastageModel->approveQC($id, $userId, $note, $docPath)) {
            setSuccess('Wastage approved by QC. Awaiting Purchase approval.');
        } else {
            setError('Failed to approve.');
        }
        header("Location: {$GLOBALS['base_url']}/con-wastage-show/{$id}");
        exit;
    }

    public function approvePurchase($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        
        $note = trim($_POST['purchase_note'] ?? '');
        $docPath = $this->handleFileUpload('purchase_doc');

        if ($this->wastageModel->approvePurchase($id, $userId, $note, $docPath)) {
            setSuccess('Wastage selling request approved by Purchase. Awaiting Accounts payment.');
        } else {
            setError('Failed to approve.');
        }
        header("Location: {$GLOBALS['base_url']}/con-wastage-show/{$id}");
        exit;
    }

    public function receivePayment($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        
        $paymentMethod = trim($_POST['payment_method'] ?? 'Cash');
        $docPath = $this->handleFileUpload('payment_doc');

        if ($this->wastageModel->receivePayment($id, $userId, $paymentMethod, $docPath)) {
            setSuccess('Payment received successfully. Amount added to Management Cash-in and stock deducted.');
        } else {
            setError('Failed to receive payment.');
        }
        header("Location: {$GLOBALS['base_url']}/con-wastage-show/{$id}");
        exit;
    }
}
