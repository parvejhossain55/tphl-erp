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

        // 🟢 ACCOUNTS OPERATIONS STATS 🟢
        $operationsStats = $this->getOperationsStats();
        
        $accountsNotifications = [];
        if (method_exists($this, 'getAccountsNotifications')) {
            $accountsNotifications = $this->getAccountsNotifications(true);
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
            "yearlyLabels" => $yearlyLabels,
            "yearlyChartData" => $yearlyChartData,
            "weeklyCashInDataArray" => $weeklyCashInDataArray,
            "weeklyCashOutDataArray" => $weeklyCashOutDataArray,
            "weeklyData" => $weeklyData,
            "yearlyData" => $yearlyData,
            "overviewStats" => $overviewStats,
            "operationsStats" => $operationsStats,
            "accountsNotifications" => $accountsNotifications
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

        // 7. Land Requisitions (approved by Management, pending or paid)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(requested_amount), 0) AS amt
            FROM land_fund_requisitions
            WHERE mgmt_status = 'Approved' AND accounts_status IN ('Pending', 'Paid')
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

        // 9. Pending Operational Fund Requests (approved but not fully paid)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(COALESCE(approved_amount, amount)), 0) AS amt
            FROM operational_fund_requests
            WHERE status IN ('approved', 'partially_paid')
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_operational_funds'] = (int)$row['c'];
            $stats['pending_operational_fund_amt'] = (float)$row['amt'];
        }

        // 10. Pending Expense Adjustments (approved but not fully paid)
        $r = $this->db->query("
            SELECT COUNT(*) AS c, COALESCE(SUM(approved_amount), 0) AS amt
            FROM expense_adjustments
            WHERE status IN ('approved', 'partially_paid')
        ");
        if ($r && $row = $r->fetch_assoc()) {
            $stats['pending_adjustments'] = (int)$row['c'];
            $stats['pending_adjustment_amt'] = (float)$row['amt'];
        }

        return $stats;
    }

    /**
     * Fetch all actionable notifications for Accounts (Cashouts, Funds, Vendors, Contractors, Expenses)
     */
    public function getAccountsNotifications($triggerDismissal = false)
    {
        $notifications = [];
        $userId = $_SESSION['user']['id'] ?? 0;

        // 1. Cashout Requests
        $sql = "
            SELECT
                cr.id,
                'cashout_requests' AS source_table,
                'Cashout Request' AS request_type,
                cr.purpose_paid_to AS purpose,
                u.full_name AS requested_by_name,
                cr.amount,
                cr.approved_amount,
                cr.due_amount,
                cr.status,
                cr.created_at,
                'admin-cashout-requests' AS view_url
            FROM cashout_requests cr
            LEFT JOIN users u ON u.id = cr.requested_by
            WHERE cr.status IN ('pending', 'qc_approved', 'approved', 'partially_paid', 'fully_paid', 'rejected')
            ORDER BY cr.created_at DESC
            LIMIT 50
        ";
        $r = $this->db->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        // 2. Operational Fund Requests
        $sql = "
            SELECT
                ofr.id,
                'operational_fund_requests' AS source_table,
                'Fund Requisition' AS request_type,
                ofr.purpose AS purpose,
                u.full_name AS requested_by_name,
                ofr.amount,
                ofr.approved_amount,
                ofr.due_amount,
                ofr.status,
                ofr.created_at,
                'con-fund-requests' AS view_url
            FROM operational_fund_requests ofr
            LEFT JOIN users u ON u.id = ofr.requester_id
            WHERE ofr.status IN ('pending', 'approved', 'partially_paid', 'fully_paid', 'rejected')
            ORDER BY ofr.created_at DESC
            LIMIT 50
        ";
        $r = $this->db->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        // 3. Contractor Bills
        $sql = "
            SELECT
                cbr.id,
                'contractor_bill_requests' AS source_table,
                'Contractor Bill' AS request_type,
                CONCAT(c.name, ' — ', LEFT(cbr.work_description, 60)) AS purpose,
                u.full_name AS requested_by_name,
                cbr.bill_amount AS amount,
                cbr.approved_amount,
                cbr.due_amount,
                cbr.status,
                cbr.created_at,
                'con-contractor-bills' AS view_url
            FROM contractor_bill_requests cbr
            LEFT JOIN contractors c ON c.id = cbr.operator_id
            LEFT JOIN users u ON u.id = cbr.created_by
            WHERE cbr.status IN ('submitted', 'verified', 'qc_approved', 'approved', 'partially_paid', 'fully_paid', 'rejected')
            ORDER BY cbr.created_at DESC
            LIMIT 50
        ";
        $r = $this->db->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        // 4. Vendor Payment Requests
        $sql = "
            SELECT
                vpr.id,
                'vendor_payment_requests' AS source_table,
                'Vendor Payment' AS request_type,
                CONCAT(v.name, ' — ', COALESCE(vpr.invoice_number, 'No Invoice')) AS purpose,
                u.full_name AS requested_by_name,
                vpr.net_payable AS amount,
                vpr.approved_amount,
                vpr.due_amount,
                vpr.status,
                vpr.created_at,
                'con-vendor-payment-requests' AS view_url
            FROM vendor_payment_requests vpr
            LEFT JOIN vendors v ON v.id = vpr.vendor_id
            LEFT JOIN users u ON u.id = vpr.created_by
            WHERE vpr.status IN ('Submitted', 'QC Approved', 'Approved', 'Partially_Paid', 'Fully_Paid', 'Rejected')
            ORDER BY vpr.created_at DESC
            LIMIT 50
        ";
        $r = $this->db->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        // 5. Expense Adjustments
        $sql = "
            SELECT
                ea.id,
                'expense_adjustments' AS source_table,
                'Expense Adjustment' AS request_type,
                ea.title AS purpose,
                u.full_name AS requested_by_name,
                ea.amount,
                ea.approved_amount,
                ea.due_amount,
                ea.status,
                ea.created_at,
                'expense-adjustments' AS view_url
            FROM expense_adjustments ea
            LEFT JOIN users u ON u.id = ea.user_id
            WHERE ea.status IN ('pending', 'approved', 'partially_paid', 'fully_paid', 'rejected')
            ORDER BY ea.created_at DESC
            LIMIT 50
        ";
        $r = $this->db->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        // 6. Fund Requisitions (Petty Cash / General)
        $sql = "
            SELECT
                fr.id,
                'fund_requisitions' AS source_table,
                'Fund Requisition' AS request_type,
                fr.purpose AS purpose,
                u.full_name AS requested_by_name,
                fr.amount,
                fr.amount AS approved_amount,
                0 AS due_amount,
                IF(fr.status = 'paid', 'fully_paid', fr.status) AS status,
                fr.created_at,
                'admin-fund-requests' AS view_url
            FROM fund_requisitions fr
            LEFT JOIN users u ON u.id = fr.requested_by
            WHERE fr.status IN ('pending', 'approved', 'rejected', 'paid')
            ORDER BY fr.created_at DESC
            LIMIT 50
        ";
        $r = $this->db->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        // 7. Land Fund Requisitions
        $sql = "
            SELECT
                lfr.id,
                'land_fund_requisitions' AS source_table,
                'Land Requisition' AS request_type,
                lfr.purpose AS purpose,
                u.full_name AS requested_by_name,
                lfr.requested_amount AS amount,
                lfr.requested_amount AS approved_amount,
                0 AS due_amount,
                IF(lfr.accounts_status = 'Paid', 'fully_paid', lfr.accounts_status) AS status,
                lfr.created_at,
                'land-requisitions' AS view_url
            FROM land_fund_requisitions lfr
            LEFT JOIN users u ON u.id = lfr.requested_by
            WHERE lfr.mgmt_status = 'Approved' 
              AND lfr.accounts_status IN ('Pending', 'Paid', 'Rejected')
            ORDER BY lfr.created_at DESC
            LIMIT 50
        ";
        $r = $this->db->query($sql);
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $notifications[] = $row;
            }
        }

        // Sort by created_at DESC
        usort($notifications, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Dismiss logic for Paid / Rejected items
        $finalNotifications = [];
        $toDismiss = [];

        foreach ($notifications as $n) {
            // Auto-detect fully paid if due amount is 0
            if (isset($n['due_amount']) && (float)$n['amount'] > 0 && (float)$n['due_amount'] <= 0) {
                $n['status'] = 'fully_paid';
            }

            $st = strtolower($n['status']);
            if (in_array($st, ['fully_paid', 'rejected', 'paid'])) {
                // Check if already dismissed
                $stmt = $this->db->prepare("SELECT id FROM notification_dismissals WHERE user_id = ? AND source_table = ? AND source_id = ? AND status = ?");
                $stmt->bind_param("isss", $userId, $n['source_table'], $n['id'], $st);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $stmt->close();
                    continue; // Skip, already seen
                }
                $stmt->close();
                
                // Add one-time flag so the UI can animate it fading away
                $n['is_one_time'] = true;
                $toDismiss[] = $n;
            } else {
                $n['is_one_time'] = false;
            }
            $finalNotifications[] = $n;
        }

        if ($triggerDismissal && !empty($toDismiss)) {
            $this->dismissCompletedNotifications($userId, $toDismiss);
        }

        return $finalNotifications;
    }

    private function dismissCompletedNotifications($userId, $toDismiss)
    {
        $stmt = $this->db->prepare("INSERT INTO notification_dismissals (user_id, source_table, source_id, status) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            foreach ($toDismiss as $item) {
                $st = strtolower($item['status']);
                $stmt->bind_param("isss", $userId, $item['source_table'], $item['id'], $st);
                $stmt->execute();
            }
            $stmt->close();
        }
    }
}