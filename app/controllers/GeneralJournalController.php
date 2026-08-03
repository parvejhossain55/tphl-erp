<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/GeneralJournal.php';

class GeneralJournalController extends Controller
{
    private $journalModel;

    public function __construct($db = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->journalModel = new GeneralJournal($db);
    }

    public function create()
    {
        $accounts = $this->journalModel->getActiveAccounts();
        $projects = $this->journalModel->getActiveProjects();
        $journal_no = $this->journalModel->generateJournalNumber();
        
        $data = [
            'page_title' => "New Journal Entry",
            'page' => "Accounts",
            'accounts' => $accounts,
            'projects' => $projects,
            'journal_no' => $journal_no
        ];
        $this->view('accounts/general-journal/create', $data);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 1); // Get correct user ID from session
            
            $headerData = [
                'journal_number' => $_POST['journal_number'],
                'date' => $_POST['date'],
                'voucher_type' => $_POST['voucher_type'],
                'narration' => $_POST['header_narration'] ?? '',
                'total_debit' => $_POST['total_debit_val'],
                'total_credit' => $_POST['total_credit_val'],
                'created_by' => $userId,
                'project_id' => !empty($_POST['project_id']) ? $_POST['project_id'] : null,
                'office' => !empty($_POST['office']) ? $_POST['office'] : null
            ];

            $detailsData = [];
            $headerProjectId = isset($_POST['project_id']) && $_POST['project_id'] !== '' ? $_POST['project_id'] : null;
            
            foreach ($_POST['account_id'] as $index => $accountId) {
                if (!empty($accountId)) {
                    $detailsData[] = [
                        'account_id' => $accountId,
                        'project_id' => $headerProjectId,
                        'narration' => $_POST['narration'][$index] ?? '',
                        'debit' => $_POST['debit'][$index] ?? 0,
                        'credit' => $_POST['credit'][$index] ?? 0
                    ];
                }
            }

            // Simple Backend validation
            if ($headerData['total_debit'] != $headerData['total_credit'] || $headerData['total_debit'] <= 0) {
                $_SESSION['error'] = "Total Debits must equal Total Credits and be greater than zero.";
                header("Location: " . $GLOBALS['base_url'] . "/general-journal/create");
                exit;
            }

            if ($this->journalModel->storeJournal($headerData, $detailsData, $userId)) {
                $_SESSION['success'] = "Journal Entry successfully saved.";
                header("Location: " . $GLOBALS['base_url'] . "/financial-dashboard");
                exit;
            } else {
                $_SESSION['error'] = "Failed to save journal entry.";
                header("Location: " . $GLOBALS['base_url'] . "/general-journal/create");
                exit;
            }
        }
    }

    public function getJournals()
    {
        header('Content-Type: application/json');
        $projectId = !empty($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $journals = $this->journalModel->getJournals($projectId);
        echo json_encode(["data" => $journals]);
        exit;
    }

    public function show($id)
    {
        $journal = $this->journalModel->getJournalById($id);
        if (!$journal) {
            $_SESSION['error'] = "Journal entry not found.";
            header("Location: " . $GLOBALS['base_url'] . "/financial-dashboard");
            exit;
        }

        $details = $this->journalModel->getJournalDetails($id);

        $data = [
            'page_title' => "View Journal Entry",
            'page' => "Accounts",
            'journal' => $journal,
            'details' => $details
        ];
        $this->view('accounts/general-journal/view', $data);
    }

    public function approve($id)
    {
        $role_id = $_SESSION['user']['role_id'] ?? 0;
        // Allow Admin (1), Super Admin (3), Accounts Head (30)
        if (!in_array($role_id, [1, 3, 6, 30])) {
            $_SESSION['error'] = "You do not have permission to approve journals.";
            header("Location: " . $GLOBALS['base_url'] . "/general-journal/view/" . $id);
            exit;
        }

        if ($this->journalModel->approveJournal($id)) {
            $_SESSION['success'] = "Journal Entry successfully approved.";
        } else {
            $_SESSION['error'] = "Failed to approve journal entry.";
        }
        header("Location: " . $GLOBALS['base_url'] . "/general-journal/view/" . $id);
        exit;
    }

    public function edit($id)
    {
        $role_id = $_SESSION['user']['role_id'] ?? 0;
        if (!in_array($role_id, [1, 3, 6, 30])) {
            $_SESSION['error'] = "You do not have permission to edit journals.";
            header("Location: " . $GLOBALS['base_url'] . "/financial-dashboard");
            exit;
        }

        $journal = $this->journalModel->getJournalById($id);
        if (!$journal) {
            $_SESSION['error'] = "Journal entry not found.";
            header("Location: " . $GLOBALS['base_url'] . "/financial-dashboard");
            exit;
        }

        $details = $this->journalModel->getJournalDetails($id);
        $accounts = $this->journalModel->getActiveAccounts();
        $projects = $this->journalModel->getActiveProjects();

        $data = [
            'page_title' => "Edit Journal Entry",
            'page' => "Accounts",
            'journal' => $journal,
            'details' => $details,
            'accounts' => $accounts,
            'projects' => $projects
        ];
        $this->view('accounts/general-journal/edit', $data);
    }

    public function update($id)
    {
        $role_id = $_SESSION['user']['role_id'] ?? 0;
        if (!in_array($role_id, [1, 3, 6, 30])) {
            $_SESSION['error'] = "You do not have permission to edit journals.";
            header("Location: " . $GLOBALS['base_url'] . "/financial-dashboard");
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'voucher_type' => $_POST['voucher_type'] ?? 'General',
                'narration' => $_POST['main_narration'] ?? '',
                'accounts' => $_POST['account_id'] ?? [],
                'narrations' => $_POST['narration'] ?? [],
                'project_id' => !empty($_POST['project_id']) ? $_POST['project_id'] : null,
                'office' => !empty($_POST['office']) ? $_POST['office'] : null,
                'debits' => $_POST['debit'] ?? [],
                'credits' => $_POST['credit'] ?? []
            ];

            $total_debit = 0;
            $total_credit = 0;

            foreach ($data['debits'] as $debit) {
                $total_debit += (float)$debit;
            }
            foreach ($data['credits'] as $credit) {
                $total_credit += (float)$credit;
            }

            if (round($total_debit, 2) != round($total_credit, 2)) {
                $_SESSION['error'] = "Debits and Credits must be equal.";
                header("Location: " . $GLOBALS['base_url'] . "/general-journal/edit/" . $id);
                exit;
            }

            if ($total_debit == 0) {
                $_SESSION['error'] = "Journal entry must have a value greater than 0.";
                header("Location: " . $GLOBALS['base_url'] . "/general-journal/edit/" . $id);
                exit;
            }

            $data['total_debit'] = $total_debit;
            $data['total_credit'] = $total_credit;

            if ($this->journalModel->updateJournal($id, $data)) {
                $_SESSION['success'] = "Journal Entry updated successfully.";
                header("Location: " . $GLOBALS['base_url'] . "/general-journal/view/" . $id);
                exit;
            } else {
                $_SESSION['error'] = "Failed to update journal entry.";
                header("Location: " . $GLOBALS['base_url'] . "/general-journal/edit/" . $id);
                exit;
            }
        }
    }
}
