<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/FinancialAccount.php';

class AccountFinancialController extends Controller
{
    private $financialModel;

    public function __construct($db = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->financialModel = new FinancialAccount($db);
    }

    public function index()
    {
        $projects = $this->financialModel->getProjects();
        $data = [
            'page_title' => "Financial Dashboard",
            'page' => "Accounts",
            'projects' => $projects
        ];
        $this->view('accounts/financial-dashboard', $data);
    }

    public function getExpenseVouchers()
    {
        $type = $_GET['type'] ?? 'fund';
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $data = $this->financialModel->getExpenseVouchers($type, $projectId);
        echo json_encode(['data' => $data]);
        exit;
    }

    public function getAdjustments()
    {
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $data = $this->financialModel->getAdjustments($projectId);
        echo json_encode(['data' => $data]);
        exit;
    }

    public function getReceipts()
    {
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $data = $this->financialModel->getReceipts($projectId);
        echo json_encode(['data' => $data]);
        exit;
    }

    public function getClients()
    {
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $data = $this->financialModel->getClientsWithPendingPayments($projectId);
        echo json_encode(['data' => $data]);
        exit;
    }

    public function getRefunds()
    {
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $data = $this->financialModel->getRefunds($projectId);
        echo json_encode(['data' => $data]);
        exit;
    }
}
