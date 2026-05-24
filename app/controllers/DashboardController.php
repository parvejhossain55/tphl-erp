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

        // ── FETCH BOTH WEEKLY DATA FOR THE LINE CHART ──
        $weeklyCashInDataArray = $this->dashboardModel->getWeeklyTransactionData('cashin', $period, $projectId);
        $weeklyCashOutDataArray = $this->dashboardModel->getWeeklyTransactionData('cashout', $period, $projectId);

        // Prepare chart data
        $chartLabels = [];
        $chartData = [];
        
        if (!empty($monthlyData)) {
            foreach ($monthlyData as $data) {
                $chartLabels[] = date('M Y', strtotime($data['month'] . '-01'));
                $chartData[] = floatval($data['total']);
            }
        }
        
        $yearlyLabels = [];
        $yearlyChartData = [];
        if (!empty($yearlyData)) {
            foreach ($yearlyData as $data) {
                $yearlyLabels[] = $data['year'];
                $yearlyChartData[] = floatval($data['total']);
            }
        }

        // ── ACCOUNTS OPERATIONS STATS ─────────────────────────────────────
        $operationsStats = $this->getOperationsStats();

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
            "yearlyLabels" => $yearlyLabels,
            "yearlyChartData" => $yearlyChartData,
            "weeklyCashInDataArray" => $weeklyCashInDataArray,
            "weeklyCashOutDataArray" => $weeklyCashOutDataArray,
            "weeklyData" => $weeklyData,
            "yearlyData" => $yearlyData,
            "overviewStats" => $overviewStats,
            "operationsStats" => $operationsStats
        ]);
    }

    /**
     * Fetch quick-glance operations stats for the Accounts dashboard.
     * These are always "all time" counts of actionable items.
     */
    private function getOperationsStats()
    {
        $stats = [
            'pending_verifications'     => 0,
            'pending_verification_amt'  => 0,
            'pending_vendor_payments'   => 0,
            'pending_vendor_amt'        => 0,
            'pending_contractor_bills'  => 0,
            'pending_contractor_amt'    => 0,
            'pending_cashout_requests'  => 0,
            'pending_cashout_amt'       => 0,
            'pending_refunds'           => 0,
            'pending_refund_amt'        => 0,
            'total_clients'             => 0,
        ];

        // 1. Pending Verifications (lead_payments waiting for Accounts to verify)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS amt
            FROM lead_payments
            WHERE verification_status = 'pending'
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_verifications']    = (int)$row['c'];
            $stats['pending_verification_amt'] = (float)$row['amt'];
        }

        // 2. Pending Vendor Payment Requests (Submitted or Approved but not yet Paid)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(net_payable), 0) AS amt
            FROM vendor_payment_requests
            WHERE status IN ('Submitted', 'Approved')
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_vendor_payments'] = (int)$row['c'];
            $stats['pending_vendor_amt']      = (float)$row['amt'];
        }

        // 3. Pending Contractor Bill Requests (submitted or verified, not yet paid)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(bill_amount), 0) AS amt
            FROM contractor_bill_requests
            WHERE status IN ('submitted', 'verified', 'approved')
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_contractor_bills'] = (int)$row['c'];
            $stats['pending_contractor_amt']   = (float)$row['amt'];
        }

        // 4. Pending Cashout Requests
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS amt
            FROM cashout_requests
            WHERE status = 'pending'
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_cashout_requests'] = (int)$row['c'];
            $stats['pending_cashout_amt']      = (float)$row['amt'];
        }

        // 5. Pending Client Refunds
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(refund_amount), 0) AS amt
            FROM client_refund_requests
            WHERE status = 'pending'
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_refunds']    = (int)$row['c'];
            $stats['pending_refund_amt'] = (float)$row['amt'];
        }

        // 6. Total active clients (bookings)
        $r = $this->db->query("
            SELECT COUNT(*) AS c FROM lead_bookings WHERE booking_status != 'cancelled'
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['total_clients'] = (int)$row['c'];
        }

        // 7. Pending Land Requisitions (approved by Management, pending Accounts payment)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(requested_amount), 0) AS amt
            FROM land_fund_requisitions
            WHERE mgmt_status = 'Approved' AND accounts_status = 'Pending'
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_land_requisitions'] = (int)$row['c'];
            $stats['pending_land_requisition_amt'] = (float)$row['amt'];
        }

        // 8. Pending Fund Requisitions (pending Accounts disbursement)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(amount), 0) AS amt
            FROM fund_requisitions
            WHERE status = 'approved'
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_petty_cash'] = (int)$row['c'];
            $stats['pending_petty_cash_amt'] = (float)$row['amt'];
        }

        return $stats;
    }
}