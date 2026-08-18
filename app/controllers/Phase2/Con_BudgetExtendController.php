<?php
namespace Phase2;

use Controller;
use Con_InitialEstimate;

class Con_BudgetExtendController extends Controller
{
    protected $db;
    protected $estimateModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->estimateModel = new Con_InitialEstimate($db);
    }

    public function index()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // Fetch budget extension requests
        // PM (15,22) and Management (1,3) see all, others see their own
        $filter = "1=1";
        if (!in_array($userRole, [1, 3, 33, 15, 22])) {
            $filter = "r.requested_by = " . $userId;
        }

        $query = "
            SELECT r.*, p.name AS project_name, u.full_name AS requester_name,
                   app.full_name AS approved_by_name
            FROM budget_extend_requests r
            LEFT JOIN projects p ON p.id = r.project_id
            LEFT JOIN users u ON u.id = r.requested_by
            LEFT JOIN users app ON app.id = r.approved_by
            WHERE {$filter}
            ORDER BY r.id DESC
        ";

        $res = $this->db->query($query);
        $requests = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/budget_extends/list', [
            'current_route' => 'con-budget-extends',
            'page_title'    => 'Budget Adjustments',
            'requests'      => $requests,
            'userRole'      => $userRole,
            'userId'        => $userId
        ]);
    }

    public function create()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // PM and Site Engineers and Management can request
        // Get projects with approved initial budgets
        $projQuery = "
            SELECT p.id, p.name, b.id AS initial_budget_id, b.total_estimated_cost
            FROM projects p
            INNER JOIN initial_estimate_budgets b ON b.project_id = p.id
            WHERE p.deleted_at IS NULL AND b.status = 'approved'
            ORDER BY p.name ASC
        ";
        $rProjs = $this->db->query($projQuery);
        $projects = $rProjs ? $rProjs->fetch_all(MYSQLI_ASSOC) : [];

        $this->view('phase2/budget_extends/create', [
            'current_route' => 'con-budget-extend-create',
            'page_title'    => 'Request Budget Extension',
            'projects'      => $projects
        ]);
    }

    public function store()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $projectId = (int)($_POST['project_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $rawAmount = (float)($_POST['amount'] ?? 0);
        $adjustmentType = trim($_POST['adjustment_type'] ?? 'increase');
        $reason = trim($_POST['reason'] ?? '');
        $docsLink = trim($_POST['docs_link'] ?? '');

        if (!$projectId || empty($title) || $rawAmount <= 0 || empty($reason)) {
            setError('Please fill in all required fields and enter a valid amount.');
            header("Location: " . $GLOBALS['base_url'] . "/con-budget-extend-create");
            exit;
        }

        $amount = ($adjustmentType === 'decrease') ? -abs($rawAmount) : abs($rawAmount);


        // Get the approved initial budget ID for this project
        $stmt = $this->db->prepare("SELECT id FROM initial_estimate_budgets WHERE project_id = ? AND status = 'approved' LIMIT 1");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $budgetRow = $stmt->get_result()->fetch_assoc();
        $initialBudgetId = $budgetRow ? (int)$budgetRow['id'] : 0;

        if (!$initialBudgetId) {
            setError('This project does not have an approved initial estimate budget.');
            header("Location: " . $GLOBALS['base_url'] . "/con-budget-extend-create");
            exit;
        }

        // Handle file upload
        require_once dirname(__DIR__, 2) . '/helpers/helper.php';
        $attachment = uploadFile('attachment_file', 'budget_extends');

        $stmtInsert = $this->db->prepare("
            INSERT INTO budget_extend_requests (project_id, initial_budget_id, requested_by, title, amount, reason, docs_link, attachment_file, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ");
        $stmtInsert->bind_param('iiisdsss', $projectId, $initialBudgetId, $userId, $title, $amount, $reason, $docsLink, $attachment);

        if ($stmtInsert->execute()) {
            setSuccess('Budget adjustment request submitted successfully.');
            header("Location: " . $GLOBALS['base_url'] . "/con-budget-extends");
        } else {
            setError('Failed to submit budget adjustment request.');
            header("Location: " . $GLOBALS['base_url'] . "/con-budget-extend-create");
        }
        exit;
    }

    public function approve($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);

        // Only Management (1, 3) and PM (15, 22) can approve
        if (!in_array($userRole, [1, 3, 33, 15, 22])) {
            setError('You do not have permission to approve budget adjustments.');
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $id = (int)$id;

        // Fetch request
        $stmt = $this->db->prepare("SELECT * FROM budget_extend_requests WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();

        if (!$req || $req['status'] !== 'Pending') {
            setError('Request not found or already processed.');
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $this->db->begin_transaction();
        try {
            // Update request status
            $stmtUpdate = $this->db->prepare("
                UPDATE budget_extend_requests 
                SET status = 'Approved', approved_by = ?, approved_at = NOW() 
                WHERE id = ?
            ");
            $stmtUpdate->bind_param('ii', $userId, $id);
            $stmtUpdate->execute();

            // Update initial budget's other_costs
            $initialBudgetId = (int)$req['initial_budget_id'];
            $amount = (float)$req['amount'];
            $extTitle = $req['title'];

            $stmtBudget = $this->db->prepare("
                UPDATE initial_estimate_budgets 
                SET other_costs = other_costs + ?,
                    other_cost_detail = CONCAT(COALESCE(other_cost_detail, ''), '\n[Budget Adjustment: ', ?, ' - ', ?, ']')
                WHERE id = ?
            ");
            $stmtBudget->bind_param('dsdi', $amount, $extTitle, $amount, $initialBudgetId);
            $stmtBudget->execute();

            // Sync total_estimated_cost for the budget
            $this->estimateModel->syncTotals($initialBudgetId);

            $this->db->commit();
            setSuccess('Budget adjustment request approved successfully.');
        } catch (\Exception $e) {
            $this->db->rollback();
            setError('Error: ' . $e->getMessage());
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    public function reject($id)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $userRole = (int)($_SESSION['user']['role_id'] ?? 0);

        // Only Management (1, 3) and PM (15, 22) can reject
        if (!in_array($userRole, [1, 3, 33, 15, 22])) {
            setError('You do not have permission to reject budget adjustments.');
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $id = (int)$id;
        $remarks = trim($_POST['remarks'] ?? '');

        if (empty($remarks)) {
            setError('Rejection remarks are required.');
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Fetch request
        $stmt = $this->db->prepare("SELECT * FROM budget_extend_requests WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();

        if (!$req || $req['status'] !== 'Pending') {
            setError('Request not found or already processed.');
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $stmtUpdate = $this->db->prepare("
            UPDATE budget_extend_requests 
            SET status = 'Rejected', approved_by = ?, approved_at = NOW(), remarks = ? 
            WHERE id = ?
        ");
        $stmtUpdate->bind_param('iisi', $userId, $remarks, $id);

        if ($stmtUpdate->execute()) {
            setSuccess('Budget adjustment request rejected.');
        } else {
            setError('Failed to reject budget adjustment request.');
        }

        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }
}
