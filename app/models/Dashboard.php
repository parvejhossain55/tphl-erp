<?php
class Dashboard
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get date range based on period
     */
    private function getDateRangeByPeriod($period)
    {
        $today = date('Y-m-d');
        $startDate = $today;
        $endDate = $today;

        switch ($period) {
            case 'daily':
                // Today
                $startDate = $today;
                $endDate = $today;
                break;
            case 'weekly':
                // This week (Monday to Sunday)
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'monthly':
                // This month
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                break;
            case 'yearly':
                // This year
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
                break;
            default:
                // All time (default to current year)
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
                break;
        }

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * Get SQL WHERE condition based on period for a specific date column
     */
    private function getPeriodCondition($period, $dateColumn)
    {
        if ($period === 'all_time') {
            return '';
        }

        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        return "WHERE {$dateColumn} BETWEEN '{$startDate}' AND '{$endDate}'";
    }

    /**
     * Get Cash In breakdown by payment method with period filter
     */
    public function getCashInByPaymentMethod($period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $sql = "
            SELECT 
                payment_method,
                SUM(amount) as total
            FROM lead_payments
            WHERE payment_status = 'paid'
            AND payment_date BETWEEN ? AND ?
            GROUP BY payment_method
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        // Initialize with all payment methods as 0
        $breakdown = [
            'cash' => 0,
            'bank' => 0,
            'cheque' => 0,
            'online' => 0
        ];

        // Fill in the actual values
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $method = strtolower($row['payment_method']);
                if (isset($breakdown[$method])) {
                    $breakdown[$method] = floatval($row['total']);
                }
            }
        }

        $stmt->close();
        return $breakdown;
    }

    /**
     * Get overview statistics (Cash In + Cash Out combined) with period filter
     */
    public function getOverviewStatistics($period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $stats = [
            'total_cashin' => 0,
            'total_cashout' => 0,
            'net_balance' => 0,
            'total_clients' => 0
        ];

        // Total Cash In (Lead payments + Management Cash In)
        $sqlCashIn = "
            SELECT 
                COALESCE(SUM(lp.amount), 0) as lead_payments,
                COALESCE((SELECT SUM(amount) FROM management_cashin 
                         WHERE payment_status = 'paid' 
                         AND transaction_date BETWEEN ? AND ?), 0) as management_cashin
            FROM lead_payments lp
            WHERE lp.payment_status = 'paid'
            AND lp.payment_date BETWEEN ? AND ?
        ";

        $stmt = $this->db->prepare($sqlCashIn);
        $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_cashin'] = $row['lead_payments'] + $row['management_cashin'];
        }
        $stmt->close();

        // Total Cash Out (ALL THREE sources)
        $sqlCashOut = "
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM (
                -- Management cashout
                SELECT amount as total_amount
                FROM management_cashout
                WHERE payment_status = 'paid'
                AND transaction_date BETWEEN ? AND ?
                
                UNION ALL
                
                -- Cashout request payments
                SELECT cp.amount as total_amount
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                AND cp.payment_date BETWEEN ? AND ?
                
                UNION ALL
                
                -- Salary payments (payslips)
                SELECT net_salary as total_amount
                FROM payslips
                WHERE status = 'paid'
                AND payslip_month BETWEEN ? AND ?
            ) combined
        ";

        $stmt = $this->db->prepare($sqlCashOut);
        $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_cashout'] = $row['total'];
        }
        $stmt->close();

        // Net Balance
        $stats['net_balance'] = $stats['total_cashin'] - $stats['total_cashout'];

        // Total Clients (unique bookings within period)
        $sqlClients = "
            SELECT COUNT(DISTINCT booking_id) as total 
            FROM lead_payments 
            WHERE payment_date BETWEEN ? AND ?
        ";

        $stmt = $this->db->prepare($sqlClients);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_clients'] = $row['total'];
        }
        $stmt->close();

        return $stats;
    }

    /**
     * Get Cash In Statistics with period filter
     */
    public function getCashInStatistics($period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $stats = [
            'total_amount' => 0,
            'lead_payments' => 0,
            'management_cashin' => 0,
            'booking_money' => 0,
            'registration_money' => 0,
            'emi_payments' => 0,
            'total_transactions' => 0,
            'this_month' => 0,
            'pending_amount' => 0
        ];

        // Lead Payments breakdown with period filter
        $sqlClient = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN payment_type = 'booking_money' AND payment_status = 'paid' THEN amount ELSE 0 END), 0) as booking_money,
                COALESCE(SUM(CASE WHEN payment_type = 'registration' AND payment_status = 'paid' THEN amount ELSE 0 END), 0) as registration_money,
                COALESCE(SUM(CASE WHEN payment_type = 'emi' AND payment_status = 'paid' THEN amount ELSE 0 END), 0) as emi_payments,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COUNT(*) as total_count
            FROM lead_payments
            WHERE payment_date BETWEEN ? AND ?
        ";

        $stmt = $this->db->prepare($sqlClient);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['lead_payments'] = $row['total_paid'];
            $stats['booking_money'] = $row['booking_money'];
            $stats['registration_money'] = $row['registration_money'];
            $stats['emi_payments'] = $row['emi_payments'];
            $stats['pending_amount'] = $row['pending'];
            $stats['total_transactions'] = $row['total_count'];
        }
        $stmt->close();

        // Management Cash In with period filter
        $sqlManagement = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as total,
                COUNT(*) as count
            FROM management_cashin
            WHERE transaction_date BETWEEN ? AND ?
        ";

        $stmt = $this->db->prepare($sqlManagement);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['management_cashin'] = $row['total'];
            $stats['total_transactions'] += $row['count'];
        }
        $stmt->close();

        // This Month (always current month, not based on period filter)
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');
        $sqlMonth = "
            SELECT 
                COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount, payment_date as date FROM lead_payments WHERE payment_status = 'paid'
                UNION ALL
                SELECT amount, transaction_date as date FROM management_cashin WHERE payment_status = 'paid'
            ) as combined
            WHERE date BETWEEN ? AND ?
        ";

        $stmt = $this->db->prepare($sqlMonth);
        $stmt->bind_param("ss", $currentMonthStart, $currentMonthEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = $row['total'];
        }
        $stmt->close();

        // Total Amount
        $stats['total_amount'] = $stats['lead_payments'] + $stats['management_cashin'];

        return $stats;
    }

    /**
     * Get Cash Out Statistics with period filter
     */
    public function getCashOutStatistics($period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $stats = [
            'total_amount' => 0,
            'total_transactions' => 0,
            'this_month' => 0,
            'pending_amount' => 0,
            'paid_amount' => 0,
            'by_payment_method' => [
                'cash' => 0,
                'bank' => 0,
                'cheque' => 0,
                'online' => 0
            ]
        ];

        // Overall statistics with period filter
        $sqlOverall = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COUNT(*) as total_count
            FROM management_cashout
            WHERE transaction_date BETWEEN ? AND ?
        ";

        $stmt = $this->db->prepare($sqlOverall);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['paid_amount'] = $row['paid'];
            $stats['pending_amount'] = $row['pending'];
            $stats['total_amount'] = $row['paid'] + $row['pending'];
            $stats['total_transactions'] = $row['total_count'];
        }
        $stmt->close();

        // This Month (always current month)
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');
        $sqlMonth = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE transaction_date BETWEEN ? AND ?
            AND payment_status = 'paid'
        ";

        $stmt = $this->db->prepare($sqlMonth);
        $stmt->bind_param("ss", $currentMonthStart, $currentMonthEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = $row['total'];
        }
        $stmt->close();

        // By Payment Method with period filter
        $sqlMethod = "
            SELECT 
                payment_method,
                COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE payment_status = 'paid'
            AND transaction_date BETWEEN ? AND ?
            GROUP BY payment_method
        ";

        $stmt = $this->db->prepare($sqlMethod);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $method = $row['payment_method'];
                if (isset($stats['by_payment_method'][$method])) {
                    $stats['by_payment_method'][$method] = $row['total'];
                }
            }
        }
        $stmt->close();

        return $stats;
    }

    /**
     * Get Recent Cash In Transactions with period filter
     */
    public function getRecentCashInTransactions($limit = 10, $period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $sql = "
            SELECT 
                'client' as source,
                lp.id,
                lp.payment_date as date,
                lp.payment_type as type,
                lp.amount,
                lp.payment_method,
                lp.payment_status,
                CONCAT(l.full_name, ' - ', p.name) as description
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            WHERE lp.payment_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT 
                'management' as source,
                mci.id,
                mci.transaction_date as date,
                'management' as type,
                mci.amount,
                mci.payment_method,
                mci.payment_status,
                CONCAT(mci.role_name, ' - ', mci.source) as description
            FROM management_cashin mci
            WHERE mci.transaction_date BETWEEN ? AND ?
            
            ORDER BY date DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssssi", $startDate, $endDate, $startDate, $endDate, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        $stmt->close();
        return $transactions;
    }

    /**
     * Get Recent Cash Out Transactions with period filter
     */
    public function getRecentCashOutTransactions($limit = 10, $period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $sql = "
            SELECT 
                id,
                transaction_date as date,
                role_name,
                person_name,
                paid_to,
                amount,
                payment_method,
                payment_status,
                description
            FROM management_cashout
            WHERE transaction_date BETWEEN ? AND ?
            ORDER BY transaction_date DESC, id DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ssi", $startDate, $endDate, $limit);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        $stmt->close();
        return $transactions;
    }

    /**
     * Get Monthly Cash In Data (for charts) with period filter
     */
    public function getMonthlyCashInData($months = 6, $period = 'all_time')
    {
        // If period is specified, use it; otherwise use default 6 months
        if ($period !== 'all_time') {
            $dateRange = $this->getDateRangeByPeriod($period);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            $sql = "
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT payment_date as date, amount FROM lead_payments WHERE payment_status = 'paid'
                    UNION ALL
                    SELECT transaction_date as date, amount FROM management_cashin WHERE payment_status = 'paid'
                ) as combined
                WHERE date BETWEEN ? AND ?
                GROUP BY month
                ORDER BY month ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
        } else {
            $sql = "
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT payment_date as date, amount FROM lead_payments WHERE payment_status = 'paid'
                    UNION ALL
                    SELECT transaction_date as date, amount FROM management_cashin WHERE payment_status = 'paid'
                ) as combined
                WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
                GROUP BY month
                ORDER BY month ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $months);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
        return $data;
    }

    /**
     * Get Monthly Cash Out Data (for charts) with period filter
     */
    public function getMonthlyCashOutData($months = 6, $period = 'all_time')
    {
        // If period is specified, use it; otherwise use default 6 months
        if ($period !== 'all_time') {
            $dateRange = $this->getDateRangeByPeriod($period);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            $sql = "
                SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total
                FROM management_cashout
                WHERE payment_status = 'paid'
                AND transaction_date BETWEEN ? AND ?
                GROUP BY month
                ORDER BY month ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
        } else {
            $sql = "
                SELECT 
                    DATE_FORMAT(transaction_date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total
                FROM management_cashout
                WHERE payment_status = 'paid'
                AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
                GROUP BY month
                ORDER BY month ASC
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $months);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
        return $data;
    }

    /**
     * Get Weekly Cash In/Out Data with period filter
     */
    public function getWeeklyTransactionData($viewType = 'cashin', $period = 'all_time')
    {
        if ($period === 'all_time') {
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
        } else {
            $dateRange = $this->getDateRangeByPeriod($period);
            $startOfWeek = $dateRange['start'];
            $endOfWeek = $dateRange['end'];
        }

        if ($viewType === 'cashin') {
            $sql = "
                SELECT 
                    DAYNAME(date) as day,
                    DAYOFWEEK(date) as day_num,
                    COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT payment_date as date, amount 
                    FROM lead_payments 
                    WHERE payment_status = 'paid'
                    UNION ALL
                    SELECT transaction_date as date, amount 
                    FROM management_cashin 
                    WHERE payment_status = 'paid'
                ) as combined
                WHERE date BETWEEN ? AND ?
                GROUP BY DAYOFWEEK(date), DAYNAME(date)
                ORDER BY DAYOFWEEK(date)
            ";
        } else {
            $sql = "
                SELECT 
                    DAYNAME(transaction_date) as day,
                    DAYOFWEEK(transaction_date) as day_num,
                    COALESCE(SUM(amount), 0) as total
                FROM management_cashout
                WHERE payment_status = 'paid'
                AND transaction_date BETWEEN ? AND ?
                GROUP BY DAYOFWEEK(transaction_date), DAYNAME(transaction_date)
                ORDER BY DAYOFWEEK(transaction_date)
            ";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startOfWeek, $endOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        // Initialize all days with 0
        foreach ($days as $day) {
            $data[$day] = 0;
        }

        // Fill with actual data
        while ($row = $result->fetch_assoc()) {
            $data[$row['day']] = floatval($row['total']);
        }

        $stmt->close();
        return array_values($data);
    }

    /**
     * Get Yearly Transaction Data with period filter
     */
    public function getYearlyTransactionData($viewType = 'cashin', $years = 3, $period = 'all_time')
    {
        if ($period !== 'all_time') {
            $dateRange = $this->getDateRangeByPeriod($period);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            if ($viewType === 'cashin') {
                $sql = "
                    SELECT 
                        YEAR(date) as year,
                        COALESCE(SUM(amount), 0) as total
                    FROM (
                        SELECT payment_date as date, amount 
                        FROM lead_payments 
                        WHERE payment_status = 'paid'
                        UNION ALL
                        SELECT transaction_date as date, amount 
                        FROM management_cashin 
                        WHERE payment_status = 'paid'
                    ) as combined
                    WHERE date BETWEEN ? AND ?
                    GROUP BY YEAR(date)
                    ORDER BY YEAR(date) ASC
                ";
            } else {
                $sql = "
                    SELECT 
                        YEAR(transaction_date) as year,
                        COALESCE(SUM(amount), 0) as total
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    AND transaction_date BETWEEN ? AND ?
                    GROUP BY YEAR(transaction_date)
                    ORDER BY YEAR(transaction_date) ASC
                ";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ss", $startDate, $endDate);
        } else {
            if ($viewType === 'cashin') {
                $sql = "
                    SELECT 
                        YEAR(date) as year,
                        COALESCE(SUM(amount), 0) as total
                    FROM (
                        SELECT payment_date as date, amount 
                        FROM lead_payments 
                        WHERE payment_status = 'paid'
                        UNION ALL
                        SELECT transaction_date as date, amount 
                        FROM management_cashin 
                        WHERE payment_status = 'paid'
                    ) as combined
                    WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? YEAR)
                    GROUP BY YEAR(date)
                    ORDER BY YEAR(date) ASC
                ";
            } else {
                $sql = "
                    SELECT 
                        YEAR(transaction_date) as year,
                        COALESCE(SUM(amount), 0) as total
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    AND transaction_date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? YEAR)
                    GROUP BY YEAR(transaction_date)
                    ORDER BY YEAR(transaction_date) ASC
                ";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $years);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
        return $data;
    }


// ADD THIS METHOD TO YOUR Dashboard MODEL CLASS

/**
 * Get recent cashout requests with period filter
 */
public function getRecentCashOutRequests($limit = 5, $period = 'all_time')
{
    $dateCondition = $this->getPeriodCondition($period, 'cr.created_at');
    
    $sql = "SELECT cr.*, 
            u.full_name as requester_name,
            ar.status as approval_status,
            ar.approved_amount as approval_approved_amount,
            ar.approved_at as approval_approved_at,
            ar.approved_by as approval_user_id,
            ar.id as approval_id,
            ar.approval_note,
            approver.full_name as approved_by_name,
            rejector.full_name as rejected_by_name
            FROM cashout_requests cr
            LEFT JOIN users u ON cr.requested_by = u.id
            LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
            LEFT JOIN users approver ON ar.approved_by = approver.id
            LEFT JOIN users rejector ON cr.rejected_by = rejector.id
            {$dateCondition}
            ORDER BY cr.created_at DESC
            LIMIT ?";
    
    $stmt = $this->db->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        // Determine display status
        $row['display_status'] = $this->syncStatusWithApproval($row);
        
        // Sync approved amount from approval system
        if (!empty($row['approval_approved_amount'])) {
            $row['approved_amount'] = $row['approval_approved_amount'];
        }
        
        $requests[] = $row;
    }
    
    $stmt->close();
    return $requests;
}

/**
 * Sync status with approval system
 */
private function syncStatusWithApproval($request)
{
    $approvalStatus = $request['approval_status'] ?? null;
    $cashoutStatus = $request['status'] ?? 'pending';
    
    // Priority: approval_requests status determines the real status
    if (!empty($approvalStatus)) {
        switch ($approvalStatus) {
            case 'approved':
            case 'partial':
                // If approved or partial, check payment status
                if (isset($request['due_amount']) && $request['due_amount'] <= 0) {
                    return 'fully_paid';
                } elseif (isset($request['due_amount']) && $request['due_amount'] < $request['amount']) {
                    return 'partially_paid';
                }
                return 'approved';
                
            case 'rejected':
                return 'rejected';
                
            case 'pending':
            default:
                return 'pending';
        }
    }
    
    // If no approval record, use cashout status
    return $cashoutStatus;
}

}
