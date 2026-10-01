<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Report.php';

class ReportsController extends Controller
{
    private $reportModel;

    public function __construct($db = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->reportModel = new Report($db);
    }

    /**
     * Balance Sheet Report Page
     */
    public function balanceSheet()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 year'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;

        $projects = $this->reportModel->getActiveProjects();
        $reportData = $this->reportModel->getBalanceSheet($dateFrom, $dateTo, $projectId, $office);

        $data = [
            'page_title' => 'Balance Sheet',
            'page' => 'Accounts',
            'projects' => $projects,
            'report' => $reportData,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'project_id' => $projectId,
                'office' => $office
            ]
        ];
        $this->view('accounts/reports/balance-sheet', $data);
    }

    /**
     * Profit & Loss Report Page
     */
    public function profitLoss()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 year')); // Default to last 1 year
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;

        $projects = $this->reportModel->getActiveProjects();
        $reportData = $this->reportModel->getProfitLoss($dateFrom, $dateTo, $projectId, $office);

        $data = [
            'page_title' => 'Profit & Loss Statement',
            'page' => 'Accounts',
            'projects' => $projects,
            'report' => $reportData,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'project_id' => $projectId,
                'office' => $office
            ]
        ];
        $this->view('accounts/reports/profit-loss', $data);
    }

    /**
     * Cashflow Report Page
     */
    public function cashflow()
    {
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-1 year')); // Default to last 1 year
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;
        $layoutMode = $_GET['layout'] ?? 'vertical';

        $projects = $this->reportModel->getActiveProjects();
        $reportData = $this->reportModel->getCashflow($dateFrom, $dateTo, $projectId, $office);

        $data = [
            'page_title' => 'Cashflow Statement',
            'page' => 'Accounts',
            'projects' => $projects,
            'report' => $reportData,
            'layoutMode' => $layoutMode,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'project_id' => $projectId,
                'office' => $office,
                'layout' => $layoutMode
            ]
        ];
        $this->view('accounts/reports/cashflow', $data);
    }

    /**
     * Trial Balance Report Page
     */
    public function trialBalance()
    {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;
        $viewType = $_GET['view_type'] ?? 'summary';

        $projects = $this->reportModel->getActiveProjects();
        if ($viewType === 'detailed') {
            $reportData = $this->reportModel->getDetailedTrialBalance($asOfDate, $projectId, $office);
        } else {
            $reportData = $this->reportModel->getTrialBalance($asOfDate, $projectId, $office);
        }

        $data = [
            'page_title' => 'Trial Balance (রেওয়ামিল)',
            'page' => 'Accounts',
            'projects' => $projects,
            'report' => $reportData,
            'viewType' => $viewType,
            'filters' => [
                'as_of_date' => $asOfDate,
                'project_id' => $projectId,
                'office' => $office,
                'view_type' => $viewType
            ]
        ];
        $this->view('accounts/reports/trial-balance', $data);
    }

    /**
     * Accounts Payable (AP) Ageing Analysis Report Page
     */
    public function apAgeing()
    {
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;

        $projects = $this->reportModel->getActiveProjects();
        $reportData = $this->reportModel->getApAgeingReport($asOfDate, $projectId, $office);

        $data = [
            'page_title' => 'Accounts Payable (AP) Ageing Analysis (পাওনাদার জ্যেষ্ঠতা বিশ্লেষণ)',
            'page' => 'Accounts',
            'projects' => $projects,
            'report' => $reportData,
            'filters' => [
                'as_of_date' => $asOfDate,
                'project_id' => $projectId,
                'office' => $office
            ]
        ];
        $this->view('accounts/reports/ap-ageing', $data);
    }

    /**
     * Cash Flow Commitment Forecast Report Page
     */
    public function cashCommitmentForecast()
    {
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;

        $projects = $this->reportModel->getActiveProjects();
        $reportData = $this->reportModel->getCashCommitmentForecast($projectId, $office);

        $data = [
            'page_title' => 'Cash Flow Commitment Forecast (ক্যাশ ফ্লো পূর্বাভাস)',
            'page' => 'Accounts',
            'projects' => $projects,
            'report' => $reportData,
            'filters' => [
                'project_id' => $projectId,
                'office' => $office
            ]
        ];
        $this->view('accounts/reports/cash-commitment-forecast', $data);
    }
}
