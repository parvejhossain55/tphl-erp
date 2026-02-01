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

        // Get period from query parameter (default: monthly)
        $period = $_GET['period'] ?? 'all_time';

        // Validate period
        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly', 'all_time'])) {
            $period = 'all_time';
        }

        // Get dashboard statistics based on view type and period
        if ($viewType === 'cashin') {
            $statistics = $this->dashboardModel->getCashInStatistics($period);

            // Add payment method breakdown for Cash In with period filter
            $statistics['by_payment_method'] = $this->dashboardModel->getCashInByPaymentMethod($period);

            $recentTransactions = $this->dashboardModel->getRecentCashInTransactions(10, $period);
            $monthlyData = $this->dashboardModel->getMonthlyCashInData(6, $period);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashin', $period);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashin', 3, $period);
        } else {
            $statistics = $this->dashboardModel->getCashOutStatistics($period);
            // Get cashout requests instead of transactions (only 5 rows)
            $recentTransactions = $this->dashboardModel->getRecentCashOutRequests(5, $period);
            $monthlyData = $this->dashboardModel->getMonthlyCashOutData(6, $period);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashout', $period);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashout', 3, $period);
        }

        // Get combined overview statistics with period filter
        $overviewStats = $this->dashboardModel->getOverviewStatistics($period);

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
            "current_route" => "dashboard",
            "page_title" => "Dashboard",
            "viewType" => $viewType,
            "period" => $period,
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

    /**
     * Get Statistics API endpoint
     */
    public function getStatistics()
    {
        header('Content-Type: application/json');

        $viewType = $_GET['view'] ?? 'cashin';
        $period = $_GET['period'] ?? 'monthly';

        if (!in_array($viewType, ['cashin', 'cashout'])) {
            echo json_encode(['error' => 'Invalid view type']);
            exit;
        }

        if (!in_array($period, ['daily', 'weekly', 'monthly', 'yearly', 'all_time'])) {
            $period = 'monthly';
        }

        if ($viewType === 'cashin') {
            $statistics = $this->dashboardModel->getCashInStatistics($period);

            // Add payment method breakdown for Cash In with period filter
            $statistics['by_payment_method'] = $this->dashboardModel->getCashInByPaymentMethod($period);

            $recentTransactions = $this->dashboardModel->getRecentCashInTransactions(10, $period);
            $monthlyData = $this->dashboardModel->getMonthlyCashInData(6, $period);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashin', $period);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashin', 3, $period);
        } else {
            $statistics = $this->dashboardModel->getCashOutStatistics($period);
            // Get cashout requests instead of transactions (only 5 rows)
            $recentTransactions = $this->dashboardModel->getRecentCashOutRequests(5, $period);
            $monthlyData = $this->dashboardModel->getMonthlyCashOutData(6, $period);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashout', $period);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashout', 3, $period);
        }

        // Get combined overview statistics with period filter
        $overviewStats = $this->dashboardModel->getOverviewStatistics($period);

        // Prepare chart data for API response
        $chartLabels = [];
        $chartData = [];
        
        if (!empty($monthlyData)) {
            foreach ($monthlyData as $data) {
                $chartLabels[] = date('M Y', strtotime($data['month'] . '-01'));
                $chartData[] = floatval($data['total']);
            }
        }

        echo json_encode([
            'success' => true,
            'period' => $period,
            'viewType' => $viewType,
            'statistics' => $statistics,
            'recentTransactions' => $recentTransactions,
            'monthlyData' => $monthlyData,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
            'weeklyData' => $weeklyData,
            'yearlyData' => $yearlyData,
            'overviewStats' => $overviewStats
        ]);
        exit;
    }
}