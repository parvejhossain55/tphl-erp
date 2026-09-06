<?php

require_once __DIR__ . '/../services/TrackerCalculationService.php';

class SalesActivityReportController extends Controller
{
    protected $trackerService;
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
        if (!isset($_SESSION['user'])) {
            header('Location: /');
            exit;
        }
        $this->trackerService = new TrackerCalculationService($this->db);
    }

    public function index()
    {
        $loggedInUser = $_SESSION['user'];

        // Get filter inputs
        $year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
        $month = isset($_GET['month']) ? (int)$_GET['month'] : date('m');
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

        // Monthly Data
        $monthlyData = $this->trackerService->getTrackerReport($year, $month, $loggedInUser);
        
        // Daily Data
        $dailyData = $this->trackerService->getDailyReport($date, $loggedInUser);

        $this->view('sales-activity-report/index', [
            'monthlyData' => $monthlyData,
            'dailyData' => $dailyData,
            'year' => $year,
            'month' => $month,
            'date' => $date,
            'activeTab' => $activeTab
        ]);
    }
}
