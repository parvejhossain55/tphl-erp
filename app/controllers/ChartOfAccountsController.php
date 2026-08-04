<?php

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ChartOfAccount.php';

class ChartOfAccountsController extends Controller
{
    private $coaModel;

    public function __construct($db)
    {
        $this->coaModel = new ChartOfAccount($db);
    }

    public function index()
    {
        $accounts = $this->coaModel->getAllAccounts();
        $treeAccounts = $this->coaModel->getTreeAccounts();

        $data = [
            'page_title' => 'Chart of Accounts Management (4-Tier Standard)',
            'page' => 'Accounts',
            'accounts' => $accounts,
            'treeAccounts' => $treeAccounts
        ];
        $this->view('accounts/coa/index', $data);
    }

    public function getNextCode()
    {
        $parentId = $_GET['parent_id'] ?? null;
        $level = $_GET['level'] ?? 'main';
        $code = $this->coaModel->generateNextCode($parentId, $level);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'code' => $code]);
        exit;
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['account_code'] ?? '');
            $name = trim($_POST['account_name'] ?? '');
            $level = $_POST['level_add'] ?? 'ledger';
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $status = $_POST['status'] ?? 'active';
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

            $type = 'Asset';
            $rootType = 'asset';

            if ($parent_id) {
                $parent = $this->coaModel->getAccountById($parent_id);
                if ($parent) {
                    $type = $parent['account_type'];
                    $rootType = $parent['root_type'] ?? strtolower($parent['account_type']);
                }
            } else {
                $type = $_POST['account_type'] ?? 'Asset';
                $rootType = strtolower($type);
                if ($rootType === 'revenue') $rootType = 'income';
            }

            if (empty($code) || empty($name)) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error'] = "Account Code and Name are required.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            if ($this->coaModel->checkAccountCodeExists($code)) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error'] = "Account Code '$code' already exists.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            $data = [
                'parent_id' => $parent_id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'level' => $level,
                'root_type' => $rootType,
                'is_system_account' => 0,
                'project_id' => $projectId,
                'status' => $status
            ];

            if (session_status() === PHP_SESSION_NONE) session_start();
            if ($this->coaModel->createAccount($data)) {
                $_SESSION['success'] = "Account created successfully.";
            } else {
                $_SESSION['error'] = "Failed to create account.";
            }
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }
    }

    public function storeAjax()
    {
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['account_code'] ?? '');
            $name = trim($_POST['account_name'] ?? '');
            $level = $_POST['level_add'] ?? 'ledger';
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $status = $_POST['status'] ?? 'active';

            $type = 'Asset';
            $rootType = 'asset';

            if ($parent_id) {
                $parent = $this->coaModel->getAccountById($parent_id);
                if ($parent) {
                    $type = $parent['account_type'];
                    $rootType = $parent['root_type'] ?? strtolower($parent['account_type']);
                }
            } else {
                $type = $_POST['account_type'] ?? 'Asset';
                $rootType = strtolower($type);
                if ($rootType === 'revenue') $rootType = 'income';
            }

            if (empty($code) || empty($name)) {
                echo json_encode(['success' => false, 'message' => 'Account Code and Name are required.']);
                exit;
            }

            if ($this->coaModel->checkAccountCodeExists($code)) {
                echo json_encode(['success' => false, 'message' => "Account Code '$code' already exists."]);
                exit;
            }

            $data = [
                'parent_id' => $parent_id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'level' => $level,
                'root_type' => $rootType,
                'is_system_account' => 0,
                'status' => $status
            ];

            if ($this->coaModel->createAccount($data)) {
                $newAccId = $this->coaModel->getAccountByCode($code)['id'] ?? null;
                $newAcc = $newAccId ? $this->coaModel->getAccountById($newAccId) : null;
                echo json_encode(['success' => true, 'account' => $newAcc, 'message' => 'New Chart of Account created successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create account.']);
            }
            exit;
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)$_POST['account_id'];
            $code = trim($_POST['account_code'] ?? '');
            $name = trim($_POST['account_name'] ?? '');
            $level = $_POST['level_edit'] ?? 'ledger';
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $status = $_POST['status'] ?? 'active';
            $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

            $existing = $this->coaModel->getAccountById($id);
            if (!$existing) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error'] = "Account not found.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            if ($existing['is_system_account'] == 1) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error'] = "System protected accounts cannot be edited.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            $type = $existing['account_type'];
            $rootType = $existing['root_type'];

            if ($parent_id) {
                $parent = $this->coaModel->getAccountById($parent_id);
                if ($parent) {
                    $type = $parent['account_type'];
                    $rootType = $parent['root_type'] ?? strtolower($parent['account_type']);
                }
            }

            if ($id == $parent_id) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error'] = "An account cannot be its own parent.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            if ($this->coaModel->checkAccountCodeExists($code, $id)) {
                if (session_status() === PHP_SESSION_NONE) session_start();
                $_SESSION['error'] = "Account Code '$code' is already in use by another account.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            $data = [
                'parent_id' => $parent_id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'level' => $level,
                'root_type' => $rootType,
                'is_system_account' => 0,
                'project_id' => $projectId,
                'status' => $status
            ];

            if (session_status() === PHP_SESSION_NONE) session_start();
            if ($this->coaModel->updateAccount($id, $data)) {
                $_SESSION['success'] = "Account updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update account.";
            }
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }
    }

    public function delete($id = null)
    {
        $id = $id ? (int)$id : (!empty($_POST['account_id']) ? (int)$_POST['account_id'] : (!empty($_GET['id']) ? (int)$_GET['id'] : null));

        if (!$id) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "Invalid account ID.";
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }

        $account = $this->coaModel->getAccountById($id);
        if (!$account) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "Account not found.";
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }

        if (($account['is_system_account'] ?? 0) == 1) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "System protected accounts cannot be deleted.";
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($this->coaModel->deleteAccount($id)) {
            $_SESSION['success'] = "Account deleted successfully.";
        } else {
            $_SESSION['error'] = "Cannot delete account: It has active transaction records or sub-accounts.";
        }
        header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
        exit;
    }

    public function ledger($id = null)
    {
        $id = (int)$id;
        $allAccounts = $this->coaModel->getActiveAccountsWithTxnCount();

        if (!$id && !empty($allAccounts)) {
            $defaultAcc = null;
            foreach ($allAccounts as $accItem) {
                if ($accItem['account_code'] === '1110-001' || $accItem['account_code'] === '1001') {
                    $defaultAcc = $accItem;
                    break;
                }
            }
            if (!$defaultAcc) {
                $defaultAcc = $allAccounts[0];
            }
            $id = (int)$defaultAcc['id'];
        }

        $account = $this->coaModel->getAccountById($id);
        if (!$account) {
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['error'] = "Account not found.";
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }

        $startDate = $_GET['start_date'] ?? '';
        $endDate = $_GET['end_date'] ?? '';
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $partyId = !empty($_GET['party_id']) ? (int)$_GET['party_id'] : null;
        $searchQuery = !empty($_GET['search']) ? trim($_GET['search']) : null;

        $ledgerData = $this->coaModel->getAccountLedger($id, $startDate, $endDate, $projectId, $partyId, $searchQuery);
        $parties = $this->coaModel->getAccountParties($id);
        $projects = $this->coaModel->getProjectsWithJournalsForAccount($id);

        $data = [
            'page_title' => "General Ledger: " . $account['account_code'] . " - " . $account['account_name'],
            'page' => "Accounts",
            'account' => $account,
            'ledgerData' => $ledgerData,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'projectId' => $projectId,
            'partyId' => $partyId,
            'searchQuery' => $searchQuery,
            'parties' => $parties,
            'projects' => $projects,
            'allAccounts' => $allAccounts
        ];
        $this->view('accounts/coa/ledger', $data);
    }
}

