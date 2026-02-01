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

    protected $bookingModel;
    protected $dashboardModel;
    protected $approvalModel;
    protected $leaveModel;
    protected $attendancesModel;
    public function __construct($db)
    {

        $this->db = $db;
        $this->employeeModel = new Employee($this->db);
        $this->departmetModel = new Department($this->db);
        $this->departmetModel = new Designation($this->db);
        $this->dashboardModel = new Dashboard($this->db);
        $this->approvalModel = new ApprovalRequest($this->db);
        $this->taskModel = new Task($this->db);
        $this->payslipModel = new Payslip($this->db);
        $this->projectModel = new Project($this->db);
        $this->leadModel = new Lead($this->db);
        $this->bookingModel = new BookingLead($this->db);
        $this->attendancesModel = new EmpAttentendance($this->db);
        $this->leaveModel = new EmployeeLeave($this->db);
    }



    public function index()
    {
        //  $role = $_SESSION['user']['role_name'] ?? 'management';
        $totalProjects = $this->projectModel->countProject();
        $completedTasks  = $this->taskModel->getTaskCount("status = 'completed'");
        // Get period from GET (optional)
        $period = $_GET['period'] ?? null;

        // Employees
        $totalEmployees = $period
            ? $this->employeeModel->getTotalCountByPeriod($period)
            : $this->employeeModel->getTotalCount();

        // Leads
        $totalLeads = $this->leadModel->getAll($period);
        $totalLeadsCount = count($totalLeads);

        // Leads % change calculation
        if ($period) {
            switch ($period) {
                case 'daily':
                    $prevPeriodLeads = $this->leadModel->getAllByDate('daily', true); // yesterday
                    break;
                case 'weekly':
                    $prevPeriodLeads = $this->leadModel->getAllByDate('weekly', true); // last week
                    break;
                case 'monthly':
                    $prevPeriodLeads = $this->leadModel->getAllByDate('monthly', true); // last month
                    break;
                case 'yearly':
                    $prevPeriodLeads = $this->leadModel->getAllByDate('yearly', previous: true); // last year
                    break;
            }
            $prevCount = count($prevPeriodLeads);

            if ($prevCount == 0) {
                $leadsPercentChange = $totalLeadsCount > 0 ? 100 : 0;
            } else {
                $leadsPercentChange = round((($totalLeadsCount - $prevCount) / $prevCount) * 100, 2);
            }
        } else {
            // lifetime comparison vs previous month
            $currentMonth = date('m');
            $currentYear  = date('Y');
            $currentMonthLeads = $this->leadModel->countLeadsByMonth($currentYear, $currentMonth);

            $lastMonth = date('m', strtotime("-1 month"));
            $lastMonthYear = date('Y', strtotime("-1 month"));
            $lastMonthLeads = $this->leadModel->countLeadsByMonth($lastMonthYear, $lastMonth);

            if ($lastMonthLeads == 0) {
                $leadsPercentChange = $currentMonthLeads > 0 ? 100 : 0;
            } else {
                $leadsPercentChange = round((($currentMonthLeads - $lastMonthLeads) / $lastMonthLeads) * 100, 2);
            }
        }
        // Leads % change calculation



        $completedTasks  = $this->taskModel->getTaskCount("status = 'completed'");
        $totalVisitedCount = $this->leadModel->getVisitedLeadCount($period);
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


        $period = $_GET['period'] ?? null;
        $totalFlatsBooking = $this->bookingModel->getTotalBookings($period);

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

        $bookingData = $this->bookingModel->getAllBooking();

        //    $pendingRequests = $this->approvalModel->getPending(10); // latest 10
        $sessionRole = $_SESSION['user']['role_name'] ?? 'management';

        // Role mapping: DB তে কোন session role কে কোন DB approve_role দিয়ে দেখাবো
        $roleMap = [
            'super admin' => ['management'],
            'admin'       => ['management'],
            'hr'          => ['hr'],
            'accounts'    => ['accounts']
        ];

        $roles = $roleMap[strtolower($sessionRole)] ?? [strtolower($sessionRole)];

        $pendingRequests = $this->approvalModel->getPendingForRole($roles, 10);

        $this->view("home", [
            "current_route" => "home",
            "page_title"   => "Dashboard",
            "show_breadcrumb" => false,
            "totalEmployees" => $totalEmployees,
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
            "flatSalesSeries" => $flatSalesSeries,
            "totalVisitedCount" => $totalVisitedCount,
            "bookingData" => $bookingData,
            "period" => $period,
            'pendingRequests' => $pendingRequests //
        ]);
        //   print_r($pendingRequests);

    }



public function approveFull($id)
{
    $this->db->begin_transaction();

    try {
        $request = $this->approvalModel->find($id);
        $approved_days = null;
        $approved_amount = $_POST['approved_amount'] ?? null;

        if ($request['source_table'] === 'employee_leaves') {
            $leave = $this->leaveModel->find($request['source_id']);
            if ($leave && $leave['leave_type_id']) {
                $start = new DateTime($leave['start_date']);
                $end   = new DateTime($leave['end_date']);
                $approved_days = $start->diff($end)->days + 1;
                $this->deductLeaveBalance($leave, $approved_days);
            }
        } else {
            // Full approval fallback
            if ($approved_amount === null || $approved_amount <= 0) {
                $approved_amount = $request['request_amount']; // use requested amount
            }
        }

        $this->approvalModel->updateStatus($id, 'approved', $approved_amount, $approved_days);

        $this->db->commit();
        header("Location: " . $GLOBALS['base_url'] . "/home");
        exit;
    } catch (Exception $e) {
        $this->db->rollback();
        die($e->getMessage());
    }
}

    // Partial approve
    public function approvePartial($id)
    {
        $this->db->begin_transaction();

        try {
            $request = $this->approvalModel->find($id);

            $approved_amount = $_POST['approved_amount'] ?? null;
            $approved_days   = $_POST['approved_days'] ?? null;
            $approved_days   = $_POST['approved_days'] ?? null;
            $approvalNote   = $_POST['approval_note'] ?? null;

            //  Only require approved_days for leave requests
            if ($request['source_table'] === 'employee_leaves') {
                $approved_days = (int)$approved_days;

                if ($approved_days <= 0) {
                    throw new Exception('Approved days required');
                }

                $leave = $this->leaveModel->find($request['source_id']);

                if ($leave && $leave['leave_type_id']) {
                    $start = new DateTime($leave['start_date']);
                    $end   = new DateTime($leave['end_date']);
                    $total_days = $start->diff($end)->days + 1;

                    if ($approved_days > $total_days) {
                        throw new Exception('Approved days exceed requested days');
                    }

                    $this->deductLeaveBalance($leave, $approved_days);
                }
            } else {
                // 💰 amount based partial approval
                if ($approved_amount === null || $approved_amount <= 0) {
                    throw new Exception('Approved amount required');
                }

                $approved_days = null; // important
            }

            $this->approvalModel->updateStatus(
                $id,
                'partial',
                $approved_amount,
                $approved_days,
                $approvalNote
            );

            $this->db->commit();
            header("Location: " . $GLOBALS['base_url'] . "/home");
            exit;
        } catch (Exception $e) {
            $this->db->rollback();
            die($e->getMessage());
        }
    }


    private function deductLeaveBalance(array $leave, int $approved_days)
    {
        // 1 Lock row
        $stmt = $this->db->prepare("
        SELECT id, remaining_days
        FROM employee_leave_balances
        WHERE employee_id = ? AND leave_type_id = ?
        FOR UPDATE
    ");
        $stmt->bind_param("ii", $leave['employee_id'], $leave['leave_type_id']);
        $stmt->execute();
        $balance = $stmt->get_result()->fetch_assoc();

        // 2️⃣ Insert if missing
        if (!$balance) {
            $initial_days = 20;

            $stmt = $this->db->prepare("
            INSERT INTO employee_leave_balances
            (employee_id, leave_type_id, remaining_days)
            VALUES (?, ?, ?)
        ");
            $stmt->bind_param(
                "iii",
                $leave['employee_id'],
                $leave['leave_type_id'],
                $initial_days
            );
            $stmt->execute();

            $remaining_days = $initial_days;
        } else {
            $remaining_days = (int)$balance['remaining_days'];
        }

        // 3️⃣ Validate balance
        if ($remaining_days < $approved_days) {
            throw new Exception('Insufficient leave balance');
        }

        // 4️⃣ Deduct
        $stmt = $this->db->prepare("
        UPDATE employee_leave_balances
        SET remaining_days = remaining_days - ?
        WHERE employee_id = ? AND leave_type_id = ?
    ");
        $stmt->bind_param(
            "iii",
            $approved_days,
            $leave['employee_id'],
            $leave['leave_type_id']
        );
        $stmt->execute();
    }


    // Reject
    public function reject($id)
    {
        $this->approvalModel->updateStatus($id, 'rejected');
        header("Location: " . $GLOBALS['base_url'] . "/home");
        exit;
    }

    // View single request (optional)
    public function show($id)
    {
        $request = $this->approvalModel->find($id);
        $this->view('home', [
            'page_title' => 'Approval Request Details',
            'request' => $request
        ]);
    }
}
