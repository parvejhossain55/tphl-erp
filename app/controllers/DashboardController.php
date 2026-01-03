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

        // Get dashboard statistics based on view type
        if ($viewType === 'cashin') {
            $statistics = $this->dashboardModel->getCashInStatistics();
            
            // Add payment method breakdown for Cash In
            $statistics['by_payment_method'] = $this->dashboardModel->getCashInByPaymentMethod();
            
            $recentTransactions = $this->dashboardModel->getRecentCashInTransactions(10);
            $monthlyData = $this->dashboardModel->getMonthlyCashInData();
        } else {
            $statistics = $this->dashboardModel->getCashOutStatistics();
            $recentTransactions = $this->dashboardModel->getRecentCashOutTransactions(10);
            $monthlyData = $this->dashboardModel->getMonthlyCashOutData();
        }

        // Get combined overview statistics (always shown)
        $overviewStats = $this->dashboardModel->getOverviewStatistics();

        $this->view("accounts/dashboard/accounts-dashboard", [
            "current_route" => "dashboard",
            "page_title" => "Dashboard",
            "viewType" => $viewType,
            "statistics" => $statistics,
            "recentTransactions" => $recentTransactions,
            "monthlyData" => $monthlyData,
            "overviewStats" => $overviewStats
        ]);
    }

    /**
     * AJAX: Get statistics by type
     */
    public function getStatistics()
    {
        header('Content-Type: application/json');
        
        $viewType = $_GET['view'] ?? 'cashin';
        
        if (!in_array($viewType, ['cashin', 'cashout'])) {
            echo json_encode(['error' => 'Invalid view type']);
            exit;
        }

        if ($viewType === 'cashin') {
            $statistics = $this->dashboardModel->getCashInStatistics();
            
            // Add payment method breakdown for Cash In
            $statistics['by_payment_method'] = $this->dashboardModel->getCashInByPaymentMethod();
            
            $recentTransactions = $this->dashboardModel->getRecentCashInTransactions(10);
            $monthlyData = $this->dashboardModel->getMonthlyCashInData();
        } else {
            $statistics = $this->dashboardModel->getCashOutStatistics();
            $recentTransactions = $this->dashboardModel->getRecentCashOutTransactions(10);
            $monthlyData = $this->dashboardModel->getMonthlyCashOutData();
        }

        echo json_encode([
            'success' => true,
            'statistics' => $statistics,
            'recentTransactions' => $recentTransactions,
            'monthlyData' => $monthlyData
        ]);
        exit;
    }
}