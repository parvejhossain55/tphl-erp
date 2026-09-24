<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/FinancialAccount.php';

class AccountFinancialController extends Controller
{
    private $financialModel;
    private $db;

    public function __construct($db = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$db) {
            global $db;
        }
        $this->db = $db;
        $this->financialModel = new FinancialAccount($db);
    }

    public function index()
    {
        if (function_exists('isAccountsExecutive') && isAccountsExecutive()) {
            header("Location: {$GLOBALS['base_url']}/accounts-dashboard");
            exit;
        }

        $projects = $this->financialModel->getProjects();

        require_once __DIR__ . '/../models/GeneralJournal.php';
        $generalJournalModel = new GeneralJournal($this->db);
        $projectsWithJournals = $generalJournalModel->getProjectsWithJournals();

        $data = [
            'page_title' => "Financial Dashboard",
            'page' => "Accounts",
            'projects' => $projects,
            'projectsWithJournals' => $projectsWithJournals
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

    public function getPendingPayables()
    {
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $data = $this->financialModel->getPendingPayables($projectId);
        echo json_encode(['data' => $data]);
        exit;
    }
}
