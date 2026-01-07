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
// Update the index method in DashboardController.php
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
        $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashin');
        $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashin');
    } else {
        $statistics = $this->dashboardModel->getCashOutStatistics();
        $recentTransactions = $this->dashboardModel->getRecentCashOutTransactions(10);
        $monthlyData = $this->dashboardModel->getMonthlyCashOutData();
        $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashout');
        $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashout');
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
        "weeklyData" => $weeklyData,
        "yearlyData" => $yearlyData,
        "overviewStats" => $overviewStats
    ]);
}

// Update the getStatistics method similarly
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
        $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashin');
        $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashin');
    } else {
        $statistics = $this->dashboardModel->getCashOutStatistics();
        $recentTransactions = $this->dashboardModel->getRecentCashOutTransactions(10);
        $monthlyData = $this->dashboardModel->getMonthlyCashOutData();
        $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashout');
        $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashout');
    }

    echo json_encode([
        'success' => true,
        'statistics' => $statistics,
        'recentTransactions' => $recentTransactions,
        'monthlyData' => $monthlyData,
        'weeklyData' => $weeklyData,
        'yearlyData' => $yearlyData
    ]);
    exit;
}
}