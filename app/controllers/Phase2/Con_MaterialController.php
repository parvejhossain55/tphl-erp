<?php
namespace Phase2;

use Controller;
use Con_Material;

class Con_MaterialController extends Controller
{
    protected $db;
    protected $materialModel;

    public function __construct($db)
    {
        $this->db            = $db;
        $this->materialModel = new Con_Material($this->db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $materials = $this->materialModel->getAll();

        $this->view('phase2/materials/con-materials', [
            'current_route' => 'con-materials',
            'page_title'    => 'Construction Materials',
            'materials'     => $materials,
        ]);
    }

    public function create()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $this->view('phase2/materials/create-material', [
            'current_route' => 'con-material-create',
            'page_title'    => 'Add Material',
        ]);
    }

    public function store()
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();

        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'unit'            => trim($_POST['unit'] ?? ''),
            'min_stock_level' => $_POST['min_stock_level'] ?? 0,
            'created_by'      => $_SESSION['user']['id'] ?? null,
        ];

        if (empty($data['name']) || empty($data['unit'])) {
            setError('Name and Unit are required.');
            header("Location: {$GLOBALS['base_url']}/con-material-create");
            exit;
        }

        if ($this->materialModel->create($data)) {
            setSuccess('Material added successfully!');
            header("Location: {$GLOBALS['base_url']}/con-materials");
            exit;
        }

        setError('Failed to add material.');
        header("Location: {$GLOBALS['base_url']}/con-material-create");
        exit;
    }

    public function edit($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $material = $this->materialModel->find($id);
        $this->view('phase2/materials/edit-material', [
            'current_route' => 'con-materials',
            'page_title'    => 'Edit Material',
            'material'      => $material,
        ]);
    }

    public function update($id)
    {
        if (in_array($_SESSION['user']['role_id'] ?? 0, [1, 3, 23])) {
            setError('You do not have permission to add, edit, or delete records in Construction Phase 2.');
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $data = [
            'name'            => trim($_POST['name'] ?? ''),
            'unit'            => trim($_POST['unit'] ?? ''),
            'min_stock_level' => $_POST['min_stock_level'] ?? 0,
        ];

        if ($this->materialModel->update($id, $data)) {
            setSuccess('Material updated!');
        } else {
            setError('Update failed.');
        }

        header("Location: {$GLOBALS['base_url']}/con-materials");
        exit;
    }

    /* =====================
       Inventory Stocks View
    ====================== */
    public function stocks()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $stockModel     = new \Con_Stock($this->db);
        $stocks         = $stockModel->getCurrentStock();
        $stats          = $stockModel->getStockSummaryStats();
        $availableStock = $stockModel->getAvailableStock();
        $lowStockItems  = $stockModel->getLowStockItems();
        $byProject      = $stockModel->getStockByProject();
        $budgetVsReceived = $stockModel->getBudgetVsReceived();

        $this->view('phase2/stocks/con-stocks', [
            'current_route'  => 'con-stocks',
            'page_title'     => 'Inventory Stock Levels',
            'stocks'         => $stocks,
            'stats'          => $stats,
            'availableStock' => $availableStock,
            'lowStockItems'  => $lowStockItems,
            'byProject'      => $byProject,
            'budgetVsReceived'=> $budgetVsReceived,
        ]);
    }
}
