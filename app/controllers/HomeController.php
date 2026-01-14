<?php

class HomeController extends Controller
{
    protected $db;
    protected $employeeModel;
    protected $departmetModel;
    protected $taskModel;
    protected $leadModel;
    protected $payslipModel;
    protected $projectModel;
    protected $dashboardModel;
    public function __construct($db)
    {

        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmetModel = new Department($this->db);
        $this->departmetModel = new Designation($this->db);
        $this->dashboardModel = new Dashboard($this->db);
        $this->taskModel = new Task($this->db);
        $this->payslipModel = new Payslip($this->db);
        $this->projectModel = new Project($this->db);
        $this->leadModel = new Lead($this->db);
    }



    public function index()
    {
        $totalProjects = $this->projectModel->countProject();
        $completedTasks  = $this->taskModel->getTaskCount("status = 'completed'");
        $totalEmployee = $this->employeeModel->getTotalCount();
        $completedTasks  = $this->taskModel->getTaskCount("status = 'completed'");
        $totalLeads = $this->leadModel->getAll();
        $totalLeadsCount = count($totalLeads);

        // Current month
        $currentYear = date('Y');
        $currentMonth = date('m');

        $currentMonthLeads = $this->leadModel->countLeadsByMonth($currentYear, $currentMonth);

        // Last month
        $lastMonth = date('m', strtotime("-1 month"));
        $lastMonthYear = date('Y', strtotime("-1 month"));

        $lastMonthLeads = $this->leadModel->countLeadsByMonth($lastMonthYear, $lastMonth);

        // % change calculation
        if ($lastMonthLeads == 0) {
            $leadsPercentChange = $currentMonthLeads > 0 ? 100 : 0;
        } else {
            $leadsPercentChange = round((($currentMonthLeads - $lastMonthLeads) / $lastMonthLeads) * 100, 2);
        }
        // accounts part 
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

        //end accounts

                //  Onboarded Clients
        $onboardedQuery = "SELECT COUNT(*) AS onboarded FROM lead_bookings WHERE booking_status = 'booked' AND lead_bookings.deleted_at IS NULL";
        $onboardedResult = $this->db->query($onboardedQuery);
        $totalFlatsBooking = $onboardedResult->fetch_assoc()['onboarded'] ?? 0;



        // chart value get 
        
        $weeklySalesQuery = "
            SELECT DATE(payment_date) AS date, SUM(amount) AS total
            FROM lead_payments
            WHERE payment_status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(payment_date)
            ORDER BY DATE(payment_date)
        ";
        $weeklySalesResult = $this->db->query($weeklySalesQuery);
        $weeklySales = [];
        while ($row = $weeklySalesResult->fetch_assoc()) {
            $weeklySales[] = [
                'date' => $row['date'],
                'total' => (float)$row['total']
            ];
        }

        // MONTHLY SALES (Last 30 days)
        $monthlySalesQuery = "
    SELECT DATE(payment_date) AS date, SUM(amount) AS total
    FROM lead_payments
    WHERE payment_status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(payment_date)
    ORDER BY DATE(payment_date)
";
        $monthlySalesResult = $this->db->query($monthlySalesQuery);
        $monthlySales = [];
        while ($row = $monthlySalesResult->fetch_assoc()) {
            $monthlySales[] = [
                'date' => $row['date'],
                'total' => (float)$row['total']
            ];
        }

        // YEARLY SALES (Last 12 months)
        $yearlySalesQuery = "
    SELECT DATE_FORMAT(payment_date, '%Y-%m') AS month, SUM(amount) AS total
    FROM lead_payments
    WHERE payment_status='paid' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
    ORDER BY DATE_FORMAT(payment_date, '%Y-%m')
";
        $yearlySalesResult = $this->db->query($yearlySalesQuery);
        $yearlySales = [];
        while ($row = $yearlySalesResult->fetch_assoc()) {
            $yearlySales[] = [
                'month' => $row['month'],
                'total' => (float)$row['total']
            ];
        }

        $totalProjects = $this->projectModel->countProject();

$flatSalesQuery = "SELECT p.name AS project_name, COUNT(lb.id) AS flat_sold
    FROM lead_bookings lb
    JOIN projects p ON p.id = lb.project_id
    WHERE lb.booking_status = 'booked'
    GROUP BY lb.project_id
    ORDER BY flat_sold DESC
";

        $result = $this->db->query($flatSalesQuery);

        $flatSalesLabels = [];
        $flatSalesSeries = [];

        while ($row = $result->fetch_assoc()) {
            $flatSalesLabels[] = $row['project_name'];
            $flatSalesSeries[] = (int)$row['flat_sold'];
        }


        $this->view("home", [
            "current_route" => "home",
            "page_title"   => "Dashboard",
            "show_breadcrumb" => false,
            "totalEmployee" => $totalEmployee,
            "totalProjects" => $totalProjects,
            "completedTasks" => $completedTasks,
            "totalLeadsCount" => $totalLeadsCount,
            "leadsPercentChange" => $leadsPercentChange,
            "viewType" => $viewType,
            "statistics" => $statistics,
            "recentTransactions" => $recentTransactions,
            "monthlyData" => $monthlyData,
            "overviewStats" => $overviewStats,
            "totalFlatsBooking" => $totalFlatsBooking,

             "weeklySales" => $weeklySales,
            "monthlySales" => $monthlySales,
            "yearlySales" => $yearlySales,
            
            "flatSalesLabels" => $flatSalesLabels,
            "flatSalesSeries" => $flatSalesSeries
        ]);
    }
}
