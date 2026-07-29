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
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;

        $projects = $this->reportModel->getActiveProjects();
        $reportData = $this->reportModel->getBalanceSheet($asOfDate, $projectId, $office);

        $data = [
            'page_title' => 'Balance Sheet',
            'page' => 'Accounts',
            'projects' => $projects,
            'report' => $reportData,
            'filters' => [
                'as_of_date' => $asOfDate,
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
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
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
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        $projectId = $_GET['project_id'] ?? null;
        $office = $_GET['office'] ?? null;

        $projects = $this->reportModel->getActiveProjects();
        $reportData = $this->reportModel->getCashflow($dateFrom, $dateTo, $projectId, $office);

        $data = [
            'page_title' => 'Cashflow Statement',
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
        $this->view('accounts/reports/cashflow', $data);
    }
}
