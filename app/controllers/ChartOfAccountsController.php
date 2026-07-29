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
        $data = [
            'page_title' => 'Chart of Accounts Management',
            'page' => 'Accounts',
            'accounts' => $accounts
        ];
        $this->view('accounts/coa/index', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code = trim($_POST['account_code']);
            $name = trim($_POST['account_name']);
            $type = $_POST['account_type'];
            $level = $_POST['level_add'] ?? 'main';
            $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
            $status = $_POST['status'];

            if ($level === 'sub') {
                if (empty($parent_id)) {
                    $_SESSION['error'] = "Parent Category is required for Sub Categories.";
                    header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                    exit;
                }
                $parentAccount = $this->coaModel->getAccountById($parent_id);
                if ($parentAccount) {
                    $type = $parentAccount['account_type'];
                } else {
                    $_SESSION['error'] = "Invalid Parent Account selected.";
                    header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                    exit;
                }
            } else {
                // It's a main category, force parent to null
                $parent_id = null;
                if (empty($type)) {
                    $_SESSION['error'] = "Account Type is required for Main Categories.";
                    header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                    exit;
                }
            }

            if (empty($code) || empty($name) || empty($type)) {
                $_SESSION['error'] = "Account Code, Name, and Type are required.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            if ($this->coaModel->checkAccountCodeExists($code)) {
                $_SESSION['error'] = "Account Code '$code' already exists.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            $data = [
                'parent_id' => $parent_id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'status' => $status
            ];

            if ($this->coaModel->createAccount($data)) {
                $_SESSION['success'] = "Account created successfully.";
            } else {
                $_SESSION['error'] = "Failed to create account.";
            }
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['account_id'];
            $code = trim($_POST['account_code']);
            $name = trim($_POST['account_name']);
            $type = $_POST['account_type'];
            $level = $_POST['level_edit'] ?? 'main';
            $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;
            $status = $_POST['status'];

            if ($level === 'sub') {
                if (empty($parent_id)) {
                    $_SESSION['error'] = "Parent Category is required for Sub Categories.";
                    header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                    exit;
                }
                $parentAccount = $this->coaModel->getAccountById($parent_id);
                if ($parentAccount) {
                    $type = $parentAccount['account_type'];
                }
            } else {
                // It's a main category, force parent to null
                $parent_id = null;
                if (empty($type)) {
                    $_SESSION['error'] = "Account Type is required for Main Categories.";
                    header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                    exit;
                }
            }

            if ($id == $parent_id) {
                $_SESSION['error'] = "An account cannot be its own parent.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            if ($this->coaModel->checkAccountCodeExists($code, $id)) {
                $_SESSION['error'] = "Account Code '$code' is already in use by another account.";
                header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
                exit;
            }

            $data = [
                'parent_id' => $parent_id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'status' => $status
            ];

            if ($this->coaModel->updateAccount($id, $data)) {
                $_SESSION['success'] = "Account updated successfully.";
            } else {
                $_SESSION['error'] = "Failed to update account.";
            }
            header("Location: " . $GLOBALS['base_url'] . "/accounts/coa");
            exit;
        }
    }
}
