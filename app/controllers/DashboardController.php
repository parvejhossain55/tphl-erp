<?php
class DashboardController extends Controller
{
    protected $db;
    protected $dashboardModel;

    public function __construct($db)
    {
        $this->db = $db;
        $this->dashboardModel = new Dashboard($this->db);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    /**
     * Main Dashboard Index
     */
    public function index()
    {
        // Get the selected view type from query parameter (default: cashin)
        $viewType = $_GET['view'] ?? 'cashin';

        // Validate view type
        if (!in_array($viewType, ['cashin', 'cashout'])) {
            $viewType = 'cashin';
        }

        // Get period from query parameter (default: all_time)
        $period = $_GET['period'] ?? 'all_time';

        // Validate period
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly', 'all_time'])) {
            $period = 'all_time';
        }

        // Get project filter from query parameter
        $projectId = $_GET['project_id'] ?? null;
        if ($projectId === '') {
            $projectId = null;
        } elseif ($projectId) {
            $projectId = (int)$projectId;
        }

        // Get all projects for dropdown
        $projects = $this->dashboardModel->getAllProjects();

        // Get selected project name if any
        $selectedProjectName = null;
        if ($projectId) {
            foreach ($projects as $project) {
                if ($project['id'] == $projectId) {
                    $selectedProjectName = $project['name'];
                    break;
                }
            }
        }

        // Get dashboard statistics based on view type, period and project
        if ($viewType === 'cashin') {
            $statistics = $this->dashboardModel->getCashInStatistics($period, $projectId);

            // Add payment method breakdown for Cash In with period and project filter
            $statistics['by_payment_method'] = $this->dashboardModel->getCashInByPaymentMethod($period, $projectId);

            $recentTransactions = $this->dashboardModel->getRecentCashInTransactions(10, $period, $projectId);
            $monthlyData = $this->dashboardModel->getMonthlyCashInData(6, $period, $projectId);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashin', $period, $projectId);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashin', 3, $period, $projectId);
        } else {
            $statistics = $this->dashboardModel->getCashOutStatistics($period, $projectId);
            // Get cashout transactions instead of requests
            $recentTransactions = $this->dashboardModel->getRecentCashOutTransactions(10, $period, $projectId);
            $monthlyData = $this->dashboardModel->getMonthlyCashOutData(6, $period, $projectId);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashout', $period, $projectId);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashout', 3, $period, $projectId);
        }

        // Get combined overview statistics with period and project filter
        $overviewStats = $this->dashboardModel->getOverviewStatistics($period, $projectId);

        // Prepare chart data
        $chartLabels = [];
        $chartData = [];
        
        if (!empty($monthlyData)) {
            foreach ($monthlyData as $data) {
                $chartLabels[] = date('M Y', strtotime($data['month'] . '-01'));
                $chartData[] = floatval($data['total']);
            }
        }

        $this->view("accounts/dashboard/accounts-dashboard", [
            "current_route" => "accounts-dashboard",
            "page_title" => "Dashboard" . ($selectedProjectName ? " - " . $selectedProjectName : ""),
            "viewType" => $viewType,
            "period" => $period,
            "projectId" => $projectId,
            "projects" => $projects,
            "selectedProjectName" => $selectedProjectName,
            "statistics" => $statistics,
            "recentTransactions" => $recentTransactions,
            "monthlyData" => $monthlyData,
            "chartLabels" => $chartLabels,
            "chartData" => $chartData,
            "weeklyData" => $weeklyData,
            "yearlyData" => $yearlyData,
            "overviewStats" => $overviewStats
        ]);
    }
}