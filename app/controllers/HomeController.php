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
        $totalLeadsCount = $this->leadModel->countAll($period);

        // Leads % change calculation
        if ($period) {
            $prevCount = $this->leadModel->countAllByDate($period, true);

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


        // if you have project filter

        $viewType = $_GET['view'] ?? 'cashin';
        $period   = $_GET['period'] ?? 'all_time'; // default to 'all_time'
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

        if ($viewType === 'cashin') {
            $statistics = $this->dashboardModel->getCashInStatistics($period, $projectId);
            $statistics['by_payment_method'] = $this->dashboardModel->getCashInByPaymentMethod($period, $projectId);
            $recentTransactions = $this->dashboardModel->getRecentCashInTransactions(10, $period, $projectId);
            $monthlyData = $this->dashboardModel->getMonthlyCashInData(6, $period, $projectId);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashin', $period, $projectId);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashin', 3, $period, $projectId);
        } else {
            $statistics = $this->dashboardModel->getCashOutStatistics($period, $projectId);
            $recentTransactions = $this->dashboardModel->getRecentCashOutTransactions(10, $period, $projectId);
            $monthlyData = $this->dashboardModel->getMonthlyCashOutData(6, $period, $projectId);
            $weeklyData = $this->dashboardModel->getWeeklyTransactionData('cashout', $period, $projectId);
            $yearlyData = $this->dashboardModel->getYearlyTransactionData('cashout', 3, $period, $projectId);
        }
        // Get combined overview statistics (always shown)
        $overviewStats = $this->dashboardModel->getOverviewStatistics($period, $projectId);


        // Weekly data for charts
        $weeklyCashInData = $weeklyData ?? [0, 0, 0, 0, 0, 0, 0];
        $weeklyCashOutData = $weeklyData ?? [0, 0, 0, 0, 0, 0, 0];

        $period = $_GET['period'] ?? null;
        $totalFlatsBooking = $this->bookingModel->getTotalBookings($period);
        // Prepare chart data
        $chartLabels = [];
        $chartData = [];

        if (!empty($monthlyData)) {
            foreach ($monthlyData as $data) {
                $chartLabels[] = date('M Y', strtotime($data['month'] . '-01'));
                $chartData[] = floatval($data['total']);
            }
        }


        // chart value get 

        $weeklySalesQuery = "
            SELECT DATE(payment_date) AS date, SUM(amount) AS total
            FROM lead_payments
            WHERE payment_status IN ('paid','partially_paid') AND verification_status = 'verified' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
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
            WHERE payment_status IN ('paid','partially_paid') AND verification_status = 'verified' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
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
    WHERE payment_status IN ('paid','partially_paid') AND verification_status = 'verified' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
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

        $roleMap = [
            'super admin' => ['management'],
            'admin'       => ['management'],
            'hr'          => ['hr'],
            'accounts'    => ['accounts']
        ];

        $roles = $roleMap[strtolower($sessionRole)] ?? [strtolower($sessionRole)];

        $constructionProjectsCount = 0;
        $res = $this->db->query("
            SELECT COUNT(DISTINCT p.id) AS c 
            FROM projects p
            LEFT JOIN project_phases pp ON pp.project_id = p.id
            WHERE p.deleted_at IS NULL 
            AND (p.under_construction = 1 OR pp.status = 'In Progress')
        ");
        if ($res) {
            $constructionProjectsCount = (int)$res->fetch_assoc()['c'];
        }

        $pendingManpowerCount = 0;
        $resManpower = $this->db->query("SELECT COUNT(*) AS total FROM manpower_requisitions WHERE status = 'pending'");
        if ($resManpower) {
            $pendingManpowerCount = (int)($resManpower->fetch_assoc()['total'] ?? 0);
        }

        $pendingRequests = $this->approvalModel->getPendingForRole($roles, 10);

        // Get Collection Summary & Project Breakdown for Home Dashboard
        $accountClientModel = new AccountClient($this->db);
        $collectionStats = $accountClientModel->getCollectionStatsSummary($projectId);
        $projectCollectionBreakdown = $accountClientModel->getProjectCollectionBreakdown();

        $this->view("home", [
            "constructionProjectsCount" => $constructionProjectsCount,
            "pendingManpowerCount"      => $pendingManpowerCount,
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
            "collectionStats" => $collectionStats,
            "projectCollectionBreakdown" => $projectCollectionBreakdown,
            "recentTransactions" => $recentTransactions,
            "monthlyData" => $monthlyData,
            "weeklyData" => $weeklyData,
            "yearlyData" => $yearlyData,
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
            "chartLabels" => $chartLabels,
            "chartData" => $chartData,
            "weeklyCashInData" => $weeklyCashInData,
            "weeklyCashOutData" => $weeklyCashOutData,

            'pendingRequests' => $pendingRequests
        ]);
        //   print_r($pendingRequests);

    }



    public function approveFull($id)
    {
        $this->db->begin_transaction();

        try {
            $request = $this->approvalModel->find($id);
            $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $isManagement = in_array($userRoleId, [1, 3], true);

            if (($request['approve_role'] ?? 'management') === 'management' && !$isManagement) {
                throw new Exception("Unauthorized action: Only Management users can approve this request.");
            }

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
            $redirectUrl = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/home");
            header("Location: " . $redirectUrl);
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
            $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
            $isManagement = in_array($userRoleId, [1, 3], true);

            if (($request['approve_role'] ?? 'management') === 'management' && !$isManagement) {
                throw new Exception("Unauthorized action: Only Management users can approve this request.");
            }

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
                //  amount based partial approval
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
            $redirectUrl = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/home");
            header("Location: " . $redirectUrl);
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

        // 2 Insert if missing
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

        // 3 Validate balance
        if ($remaining_days < $approved_days) {
            throw new Exception('Insufficient leave balance');
        }

        // 4 Deduct
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
        $request = $this->approvalModel->find($id);
        $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
        $isManagement = in_array($userRoleId, [1, 3], true);

        if ($request && ($request['approve_role'] ?? 'management') === 'management' && !$isManagement) {
            die("Unauthorized action: Only Management users can reject this request.");
        }

        $this->approvalModel->updateStatus($id, 'rejected');
        $redirectUrl = $_SERVER['HTTP_REFERER'] ?? ($GLOBALS['base_url'] . "/home");
        header("Location: " . $redirectUrl);
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

    /**
     * Run Live Chart of Accounts & Journal Re-mapping Migration
     */
    public function runLiveCoaMigration()
    {
        if (file_exists(BASE_PATH . '/live_coa_and_journal_migration.php')) {
            require_once BASE_PATH . '/live_coa_and_journal_migration.php';
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo "<h2>=== LIVE SERVER COA & JOURNAL MIGRATION ===</h2>";
        $db = $this->db;
        $db->query("SET FOREIGN_KEY_CHECKS = 0");

        $legacyDetails = [];
        $resL = $db->query("
            SELECT jd.id as detail_id, coa.account_code, coa.account_name 
            FROM journal_details jd 
            JOIN chart_of_accounts coa ON jd.account_id = coa.id
        ");
        if ($resL) {
            while ($row = $resL->fetch_assoc()) {
                $legacyDetails[] = $row;
            }
        }
        echo "<p style='color:#00e1ff;'>[STEP 1] Found " . count($legacyDetails) . " existing journal_details records on live database.</p>";

        $db->query("TRUNCATE TABLE chart_of_accounts");
        echo "<p style='color:#00e1ff;'>[STEP 2] Truncated legacy chart_of_accounts table.</p>";

        $insertCoa = function($code, $name, $type, $level, $rootType, $parentCode = null) use ($db) {
            $parentId = null;
            if ($parentCode) {
                $stmtP = $db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? LIMIT 1");
                $stmtP->bind_param("s", $parentCode);
                $stmtP->execute();
                $pRes = $stmtP->get_result()->fetch_assoc();
                if ($pRes) $parentId = (int)$pRes['id'];
                $stmtP->close();
            }

            $stmt = $db->prepare("
                INSERT INTO chart_of_accounts 
                (parent_id, account_code, account_name, account_type, level, root_type, status, is_system_account)
                VALUES (?, ?, ?, ?, ?, ?, 'active', 1)
            ");
            $stmt->bind_param("isssss", $parentId, $code, $name, $type, $level, $rootType);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            return $newId;
        };

        // Insert All Accounts
        $insertCoa('1000', 'Assets', 'Asset', 'main', 'asset');
        $insertCoa('10', 'Non Current Assets', 'Asset', 'sub', 'asset', '1000');
        $insertCoa('100', 'Property Plant and Equipment', 'Asset', 'parent', 'asset', '10');
        $insertCoa('100000', 'Furniture & Fixtures', 'Asset', 'ledger', 'asset', '100');
        $insertCoa('100001', 'Computer, Printer & IT Equipment', 'Asset', 'ledger', 'asset', '100');
        $insertCoa('100002', 'Office Equipment', 'Asset', 'ledger', 'asset', '100');
        $insertCoa('100003', 'Transport & Vehicle', 'Asset', 'ledger', 'asset', '100');

        $insertCoa('101', 'Intangible Assets', 'Asset', 'parent', 'asset', '10');
        $insertCoa('101001', 'Software', 'Asset', 'ledger', 'asset', '101');

        $insertCoa('11', 'Current Assets', 'Asset', 'sub', 'asset', '1000');
        $insertCoa('110', 'Advance Deposit and Prepayments', 'Asset', 'parent', 'asset', '11');
        $insertCoa('110001', 'Advance to Party/ Suppliers/ Contractors', 'Asset', 'ledger', 'asset', '110');
        $insertCoa('110002', 'Advance to Employees against works/ Others', 'Asset', 'ledger', 'asset', '110');
        $insertCoa('110003', 'Advance to Employees against salary', 'Asset', 'ledger', 'asset', '110');
        $insertCoa('110004', 'Advance Income Tax (AIT)', 'Asset', 'ledger', 'asset', '110');
        $insertCoa('110005', 'Advance Against Land Purchase', 'Asset', 'ledger', 'asset', '110');
        $insertCoa('110006', 'Security Deposit', 'Asset', 'ledger', 'asset', '110');

        $insertCoa('111', 'Accounts Receivables', 'Asset', 'parent', 'asset', '11');
        $insertCoa('111000', 'Accounts Receivables', 'Asset', 'ledger', 'asset', '111');

        $insertCoa('114', 'Cash & Bank Balances', 'Asset', 'parent', 'asset', '11');
        $insertCoa('114000', 'Fixed Deposit', 'Asset', 'ledger', 'asset', '114');
        $insertCoa('114100', 'Cash in Hand', 'Asset', 'parent', 'asset', '114');
        $insertCoa('114100-01', 'Site Office- Cash in Hand', 'Asset', 'ledger', 'asset', '114100');
        $insertCoa('114100-02', 'Gulshan Office- Cash in Hand', 'Asset', 'ledger', 'asset', '114100');
        $insertCoa('114100-03', 'Head Office- Cash in Hand', 'Asset', 'ledger', 'asset', '114100');
        $insertCoa('114400', 'Cash at Bank', 'Asset', 'ledger', 'asset', '114');

        $insertCoa('2000', 'Liabilities', 'Liability', 'main', 'liability');
        $insertCoa('20', 'Non Current Liabilities', 'Liability', 'sub', 'liability', '2000');
        $insertCoa('200', 'Loan from Director', 'Liability', 'parent', 'liability', '20');
        $insertCoa('200001', 'Loan From Chairman Sir', 'Liability', 'ledger', 'liability', '200');
        $insertCoa('200002', 'Loan From Director', 'Liability', 'ledger', 'liability', '200');

        $insertCoa('2000-L', 'Long Term Loan Parent', 'Liability', 'parent', 'liability', '20');
        $insertCoa('200000', 'Long Term Loan', 'Liability', 'ledger', 'liability', '2000-L');
        $insertCoa('200100', 'Lease Finance', 'Liability', 'ledger', 'liability', '2000-L');

        $insertCoa('21', 'Current Liabilities', 'Liability', 'sub', 'liability', '2000');
        $insertCoa('210', 'Short Term Loan', 'Liability', 'parent', 'liability', '21');
        $insertCoa('210000', 'Time Loan', 'Liability', 'ledger', 'liability', '210');
        $insertCoa('210100', 'FORCE LOAN', 'Liability', 'ledger', 'liability', '210');
        $insertCoa('210200', 'LTR', 'Liability', 'ledger', 'liability', '210');
        $insertCoa('210300', 'OFFSHORE LOAN', 'Liability', 'ledger', 'liability', '210');
        $insertCoa('210400', 'SOD LOAN', 'Liability', 'ledger', 'liability', '210');
        $insertCoa('210500', 'CC LOAN', 'Liability', 'ledger', 'liability', '210');

        $insertCoa('221', 'Accounts Payable', 'Liability', 'parent', 'liability', '21');
        $insertCoa('211000', 'Accounts Payable', 'Liability', 'ledger', 'liability', '221');

        $insertCoa('212', 'Provision for WPPF and WF', 'Liability', 'parent', 'liability', '21');
        $insertCoa('212000', 'Provision for WPPF and WF', 'Liability', 'ledger', 'liability', '212');

        $insertCoa('213', 'Provision for Doubtful Debt', 'Liability', 'parent', 'liability', '21');
        $insertCoa('213000', 'Provision for Doubtful Debt', 'Liability', 'ledger', 'liability', '213');

        $insertCoa('214', 'Income Tax Provision', 'Liability', 'parent', 'liability', '21');
        $insertCoa('214000', 'Provision for Income Tax', 'Liability', 'ledger', 'liability', '214');
        $insertCoa('214100', 'TDS on salary', 'Liability', 'ledger', 'liability', '214');

        $insertCoa('216', 'Incentive Payable', 'Liability', 'parent', 'liability', '21');
        $insertCoa('216001', 'Incentive Payable', 'Liability', 'ledger', 'liability', '216');

        $insertCoa('217', 'Liability for Expenses', 'Liability', 'parent', 'liability', '21');
        $insertCoa('217000', 'Salary Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217001', 'Wages and Other Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217002', 'Festival Bonus Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217003', 'Interest Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217004', 'Tax Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217005', 'Rent Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217006', 'Store Rent Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217007', 'Utility Bill Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217008', 'TDS Payable', 'Liability', 'ledger', 'liability', '217');
        $insertCoa('217009', 'Traveling Expense Payable', 'Liability', 'ledger', 'liability', '217');

        $insertCoa('3000', 'Equity', 'Equity', 'main', 'equity');
        $insertCoa('29', 'Paid Up Capital', 'Equity', 'sub', 'equity', '3000');
        $insertCoa('291000', 'Share Money Deposit', 'Equity', 'ledger', 'equity', '29');

        $insertCoa('352', 'Retained Earnings', 'Equity', 'sub', 'equity', '3000');
        $insertCoa('352000', 'Retained Earnings', 'Equity', 'ledger', 'equity', '352');

        $insertCoa('4000', 'Income', 'Revenue', 'main', 'income');
        $insertCoa('40', 'Operating Income', 'Revenue', 'sub', 'income', '4000');
        $insertCoa('400', 'Sales', 'Revenue', 'parent', 'income', '40');
        $insertCoa('400000', 'Land Share', 'Revenue', 'ledger', 'income', '400');
        $insertCoa('400001', 'Turf Rental Income', 'Revenue', 'ledger', 'income', '400');
        $insertCoa('400002', 'Service Revenue', 'Revenue', 'ledger', 'income', '400');

        $insertCoa('41', 'Non Operating Income', 'Revenue', 'sub', 'income', '4000');
        $insertCoa('410', 'Other Income', 'Revenue', 'parent', 'income', '41');
        $insertCoa('410000', 'Other Income', 'Revenue', 'ledger', 'income', '410');
        $insertCoa('410001', 'Turf Rental Income (Non Op)', 'Revenue', 'ledger', 'income', '410');
        $insertCoa('410002', 'Income from Wastage Sales', 'Revenue', 'ledger', 'income', '410');

        $insertCoa('414', 'Interest Income', 'Revenue', 'parent', 'income', '41');
        $insertCoa('411000', 'Interest Income', 'Revenue', 'ledger', 'income', '414');

        $insertCoa('5000', 'Expenses', 'Expense', 'main', 'expense');
        $insertCoa('510', 'Selling & Marketing Expenses', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('510000', 'Video Production', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510001', 'Influencer & Endorsement', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510002', 'Facebook & Social Media Advertising', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510003', 'Google & YouTube Advertising', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510004', 'Sales Incentive', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510005', 'Referral Incentive', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510006', 'Branding & Promotion', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510007', 'Client Entertainment & Hospitality', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510008', 'Client Visits & Transportation', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510009', 'Billboard & Outdoor Advertising', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510010', 'Events & Activations', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510011', 'Seeking Gifts & Promotional Expenses', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510012', 'Brochure Printing', 'Expense', 'ledger', 'expense', '510');
        $insertCoa('510013', 'SEO Marketing', 'Expense', 'ledger', 'expense', '510');

        $insertCoa('520', 'Contractor & Labor Expenses', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('520000', 'Piling & Foundation Works Expenses', 'Expense', 'ledger', 'expense', '520');
        $insertCoa('520001', 'Electrical Works Expenses', 'Expense', 'ledger', 'expense', '520');
        $insertCoa('520002', 'Plumbing & Sanitary Works Expenses', 'Expense', 'ledger', 'expense', '520');
        $insertCoa('520003', 'Lift & Generator Works Expenses', 'Expense', 'ledger', 'expense', '520');
        $insertCoa('520004', 'Painting Works Expenses', 'Expense', 'ledger', 'expense', '520');
        $insertCoa('520005', 'Earthworks Expenses', 'Expense', 'ledger', 'expense', '520');
        $insertCoa('520006', 'Contractor Bills', 'Expense', 'ledger', 'expense', '520');

        $insertCoa('530', 'Construction Materials', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('530001', 'Bricks', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530002', 'Sand (Local)', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530003', 'Sand (Sylhet)', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530004', 'Vitti Sand', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530005', 'Rod (Steel)', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530006', 'Cement', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530007', 'Paint & Painting Materials', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530008', 'Electrical Materials', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530009', 'Sanitary Materials', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530010', 'Stone', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530011', 'Ready-Mix Concrete', 'Expense', 'ledger', 'expense', '530');
        $insertCoa('530012', 'Indirect Construction Materials', 'Expense', 'ledger', 'expense', '530');

        $insertCoa('540', 'Architectural Design', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('540001', 'Structural Design', 'Expense', 'ledger', 'expense', '540');
        $insertCoa('540002', 'Drawing & Printing Expenses', 'Expense', 'ledger', 'expense', '540');
        $insertCoa('540003', 'Engineering Design', 'Expense', 'ledger', 'expense', '540');

        $insertCoa('550', 'Engineering Testing', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('550001', 'Soil Test', 'Expense', 'ledger', 'expense', '550');
        $insertCoa('550002', 'Cylinder Test', 'Expense', 'ledger', 'expense', '550');

        $insertCoa('560', 'Fuel & Vehicle Expenses', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('560001', 'Generator Fuel', 'Expense', 'ledger', 'expense', '560');
        $insertCoa('560002', 'Vehicle Fuel - Car', 'Expense', 'ledger', 'expense', '560');
        $insertCoa('560003', 'Vehicle Fuel - Bike', 'Expense', 'ledger', 'expense', '560');
        $insertCoa('560004', 'Vehicle Maintenance & Servicing', 'Expense', 'ledger', 'expense', '560');

        $insertCoa('570', 'Land Purchase', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('570001', 'Land Purchase', 'Expense', 'ledger', 'expense', '570');

        $insertCoa('580', 'Administrative & Operating Expenses', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('580001', 'Snacks & Refreshments Expense', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580002', 'Office Entertainment Expense', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580003', 'Salaries & Wages', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580004', 'Festival Bonus', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580005', 'Performance Bonus', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580006', 'Training & Development Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580007', 'Recruitment Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580008', 'Employee Welfare', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580009', 'Office Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580010', 'Utilities Bill', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580011', 'Mobile & Internet Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580012', 'Printing & Stationery Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580013', 'Cleaning Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580014', 'Security Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580015', 'Software Subscriptions', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580016', 'Repair & Maintenance', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580017', 'Postage & Courier', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580018', 'Legal & Documentation Fees', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580019', 'Consultancy Fee', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580020', 'Tour & Travelling Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580021', 'Office Rent', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580022', 'Store Rent', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580023', 'Employee House Rent', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580024', 'Office Conveyance', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580025', 'Board Meeting Expenses', 'Expense', 'ledger', 'expense', '580');
        $insertCoa('580026', 'Management Meeting Expense', 'Expense', 'ledger', 'expense', '580');

        $insertCoa('590', 'Site Development Expenses', 'Expense', 'parent', 'expense', '5000');
        $insertCoa('590001', 'Site Preparation Expenses', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590002', 'Safety Item Expense', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590003', 'Labour Shed Preparation Expense', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590004', 'Submersible Pump Installation Expense', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590005', 'Meals Expense (Subsidized)', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590006', 'Tax & VAT', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590007', 'Charity & Donations Expense', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590008', 'Project Development Expenses', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590009', 'Project Overheads', 'Expense', 'ledger', 'expense', '590');
        $insertCoa('590010', 'Labour Bill', 'Expense', 'ledger', 'expense', '590');

        echo "<p style='color:#00ff66;'>[STEP 3] Inserted all official company Chart of Accounts!</p>";

        $getNewIdByCode = function($code) use ($db) {
            $stmt = $db->prepare("SELECT id FROM chart_of_accounts WHERE account_code = ? LIMIT 1");
            $stmt->bind_param("s", $code);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $r ? (int)$r['id'] : null;
        };

        $bankAccId = $getNewIdByCode('114400');
        $siteCashId = $getNewIdByCode('114100-01');
        $accountsPayableId = $getNewIdByCode('211000');
        $salariesExpenseId = $getNewIdByCode('580003');
        $landShareRevenueId = $getNewIdByCode('400000');
        $utilitiesExpenseId = $getNewIdByCode('580010');
        $advanceEmployeeId = $getNewIdByCode('110003');

        $convertedCount = 0;
        foreach ($legacyDetails as $detail) {
            $dId = $detail['detail_id'];
            $oldCode = $detail['account_code'];
            $targetId = null;

            if ($oldCode === '1110-002' || $oldCode === '1110-003' || $oldCode === '1110-004' || $oldCode === '1110-10' || $oldCode === '1110') {
                $targetId = $bankAccId;
            } elseif ($oldCode === '1110-001') {
                $targetId = $siteCashId;
            } elseif ($oldCode === '2110') {
                $targetId = $accountsPayableId;
            } elseif ($oldCode === '2130') {
                $targetId = $advanceEmployeeId;
            } elseif ($oldCode === '2210-001' || $oldCode === '2210' || $oldCode === '2220' || $oldCode === '2220-001' || $oldCode === '4110' || $oldCode === '4110-001' || $oldCode === '4120') {
                $targetId = $landShareRevenueId;
            } elseif ($oldCode === '5210' || $oldCode === '5210-001') {
                $targetId = $salariesExpenseId;
            } elseif ($oldCode === '5220') {
                $targetId = $utilitiesExpenseId;
            } else {
                $targetId = $bankAccId;
            }

            if ($targetId) {
                $stmtUpd = $db->prepare("UPDATE journal_details SET account_id = ? WHERE id = ?");
                $stmtUpd->bind_param("ii", $targetId, $dId);
                $stmtUpd->execute();
                $stmtUpd->close();
                $convertedCount++;
            }
        }

        $db->query("SET FOREIGN_KEY_CHECKS = 1");

        echo "<p style='color:#00ff66;'>[STEP 4] Converted $convertedCount live journal_details records to new COA account IDs!</p>";
        echo "<h3 style='color:#00ff66;'>=== LIVE MIGRATION COMPLETED SUCCESSFULLY 100%! ===</h3>";
        exit;
    }
}