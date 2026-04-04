<?php
class DigitalMarketingController extends Controller
{
    protected $db;
    protected $leadModel;
    protected $teamsModel;
    protected $leadSourceModel;
    protected $platformBudgetModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->leadModel = new Lead($this->db);
        $this->teamsModel = new Team($this->db);
        $this->leadSourceModel = new LeadSource($this->db);
        $this->platformBudgetModel = new PlatformBudget($this->db);
    }
    public function index()
    {
        $stats = $this->leadModel->getDigitalMarketingStats();
        $teams = $this->teamsModel->getAll();
        $leads = [];
        $teamId = $_GET['team_id'] ?? null;

        $currentMonthFilter = $_GET['budget_month'] ?? date('Y-m') . '-01'; // Default correctly using -01
        if (strlen($currentMonthFilter) === 7) { 
            // If submitted as YYYY-MM
            $currentMonthFilter .= '-01';
        }

        $sources = $this->leadSourceModel->getAll();
        $platformBudgets = $this->platformBudgetModel->getAllWithSources($currentMonthFilter);
        $budgetSummary = $this->platformBudgetModel->getMonthlySummary($currentMonthFilter);

        if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 10) {
            if ($teamId) {
                $leads = $this->leadModel->getLeadsByTeamId($teamId);
            } else {
                $leads = $this->leadModel->getAllMarketingLeads(30); // 30 days default
            }
        }

        return $this->view("digital-marketing-team/digital-marketing-head-dashboard", [
            "current_route"       => "digital-marketing-dashboard",
            "page_title"          => "Digital Marketing Dashboard",
            "teams"               => $teams,
            "stats"               => $stats,
            "leads"               => $leads,
            "selected_team_id"    => $teamId,
            "sources"             => $sources,
            "platformBudgets"     => $platformBudgets,
            "budgetSummary"       => $budgetSummary,
            "budget_month"        => substr($currentMonthFilter, 0, 7) // back to YYYY-MM for the form
        ]);
    }

    public function storeBudget()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'source_id'  => (int)$_POST['source_id'],
                'month'      => trim($_POST['month']) . '-01',
                'budget'     => (float)$_POST['budget'],
                'spent'      => (float)$_POST['spent'],
                'created_by' => $_SESSION['user']['id'] ?? 0
            ];

            if ($this->platformBudgetModel->create($data)) {
                $_SESSION['success'] = "Budget added successfully!";
            } else {
                $_SESSION['error'] = "Failed to add budget.";
            }

            header("Location: {$GLOBALS['base_url']}/digital-marketing-dashboard");
            exit;
        }
    }

    public function executiveDashboard()
    {
         $stats = $this->leadModel->getDigitalMarketingStats();
        $teams = $this->teamsModel->getAll();
        $leads = [];
        $teamId = $_GET['team_id'] ?? null;

        if (isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 11) {
            if ($teamId) {
                $leads = $this->leadModel->getLeadsByTeamId($teamId);
            } else {
                $leads = $this->leadModel->getLeadsByCreatedByLast7Days($_SESSION['user']['id']);
            }
        }

        return $this->view("digital-marketing-team/digital-marketing-executive", [
            "current_route"       => "digital-marketing-executive-dashboard",
            "page_title"          => "Digital Marketing Executive Dashboard",
            "teams"               => $teams,
            "stats"               => $stats,
            "leads"               => $leads,
            "selected_team_id"    => $teamId
        ]);
    }

    public function getLeadsByTeam()
{
    $teamId = $_GET['team_id'] ?? null;

    if (!$teamId) {
        echo json_encode([]);
        return;
    }

    $leads = $this->leadModel->getLeadsByTeamId($teamId);

    header('Content-Type: application/json');
    echo json_encode($leads);
}
}