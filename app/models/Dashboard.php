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
                $startDate = $today;
                $endDate = $today;
                break;
            case 'weekly':
                $startDate = date('Y-m-d', strtotime('monday this week'));
                $endDate = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'monthly':
                $startDate = date('Y-m-01');
                $endDate = date('Y-m-t');
                break;
            case 'yearly':
                $startDate = date('Y-01-01');
                $endDate = date('Y-12-31');
                break;
            case 'all_time':
                $startDate = '1970-01-01';
                $endDate = '2099-12-31';
                break;
            default:
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

        $breakdown = [
            'cash' => 0,
            'bank' => 0,
            'cheque' => 0,
            'online' => 0
        ];

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
     * Get overview statistics
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
            'total_clients' => 0,
            'total_revenue' => 0,
            'total_landshare' => 0,
            'total_registration' => 0,
            'total_discount' => 0,
            'total_agreement_value' => 0,
            'total_emi_amount' => 0,
            'total_service_fees' => 0
        ];

        // Get land share
        $sqlLandShare = "
            SELECT COALESCE(SUM(land_share_amount), 0) as total
            FROM lead_payments
            WHERE payment_status = 'paid'
            AND payment_type = 'land_share'
            AND payment_date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlLandShare);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_landshare'] = floatval($row['total']);
        }
        $stmt->close();

        // Get registration
        $sqlRegistration = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM lead_payments
            WHERE payment_status = 'paid'
            AND payment_type = 'registration'
            AND payment_date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlRegistration);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_registration'] = floatval($row['total']);
        }
        $stmt->close();

        // Get discount
        $sqlDiscount = "
            SELECT COALESCE(SUM(discount_amount), 0) as total
            FROM lead_bookings
            WHERE booking_date BETWEEN ? AND ?
            AND booking_status = 'booked'
            AND landshare_status = 'active'
        ";
        $stmt = $this->db->prepare($sqlDiscount);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_discount'] = floatval($row['total']);
        }
        $stmt->close();

        // Get agreement value
        $sqlAgreement = "
            SELECT COALESCE(SUM(agreement_value), 0) as total
            FROM lead_bookings
            WHERE booking_date BETWEEN ? AND ?
            AND booking_status = 'booked'
            AND landshare_status = 'active'
        ";
        $stmt = $this->db->prepare($sqlAgreement);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_agreement_value'] = floatval($row['total']);
        }
        $stmt->close();

        // Get EMI amount
        $sqlEmi = "
            SELECT COALESCE(SUM(emi_amount), 0) as total
            FROM emi_schedules
            WHERE due_date BETWEEN ? AND ?
            AND status = 'paid'
        ";
        $stmt = $this->db->prepare($sqlEmi);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_emi_amount'] = floatval($row['total']);
        }
        $stmt->close();

        // Get service fees
        $sqlServiceFees = "
            SELECT COALESCE(SUM(service_fee), 0) as total
            FROM emi_schedules
            WHERE due_date BETWEEN ? AND ?
            AND status = 'paid'
        ";
        $stmt = $this->db->prepare($sqlServiceFees);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_service_fees'] = floatval($row['total']);
        }
        $stmt->close();

        // Calculate Total Cash In
        $stats['total_cashin'] = $stats['total_landshare'] + $stats['total_registration'] + 
                                 $stats['total_agreement_value'] + $stats['total_emi_amount'] - 
                                 $stats['total_discount'];

        // Calculate Total Revenue
        $stats['total_revenue'] = $stats['total_landshare'] - $stats['total_discount'] + 
                                  $stats['total_agreement_value'] + $stats['total_service_fees'];

        // Get Cash Out
        $sqlCashOut = "
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM (
                SELECT amount as total_amount, transaction_date as date
                FROM management_cashout
                WHERE payment_status = 'paid'
                
                UNION ALL
                
                SELECT cp.amount as total_amount, cp.payment_date as date
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                
                UNION ALL
                
                SELECT net_salary as total_amount, 
                       STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date
                FROM payslips
                WHERE status = 'paid'
            ) combined
            WHERE date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlCashOut);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_cashout'] = floatval($row['total']);
        }
        $stmt->close();

        // Net Balance
        $stats['net_balance'] = $stats['total_cashin'] - $stats['total_cashout'];

        // Total Clients
        $sqlClients = "
            SELECT COUNT(DISTINCT booking_id) as total 
            FROM lead_payments 
            WHERE payment_date BETWEEN ? AND ?
            AND payment_status = 'paid'
        ";
        $stmt = $this->db->prepare($sqlClients);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_clients'] = intval($row['total']);
        }
        $stmt->close();

        return $stats;
    }

    /**
     * Get Cash In Statistics
     */
    public function getCashInStatistics($period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $stats = [
            'total_amount' => 0,
            'lead_payments' => 0,
            'land_share_payments' => 0,
            'management_cashin' => 0,
            'booking_money' => 0,
            'registration_money' => 0,
            'emi_payments' => 0,
            'total_transactions' => 0,
            'this_month' => 0,
            'pending_amount' => 0,
            'total_revenue' => 0,
            'total_service_fees' => 0,
            'total_landshare' => 0,
            'total_registration' => 0,
            'total_discount' => 0,
            'total_agreement_value' => 0,
            'total_emi_amount' => 0
        ];

        // Get all payment types from lead_payments
        $sqlClient = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as all_paid,
                COALESCE(SUM(CASE WHEN payment_type = 'land_share' AND payment_status = 'paid' THEN land_share_amount ELSE 0 END), 0) as land_share_paid,
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
            $stats['lead_payments'] = floatval($row['all_paid']);
            $stats['land_share_payments'] = floatval($row['land_share_paid']);
            $stats['booking_money'] = floatval($row['booking_money']);
            $stats['registration_money'] = floatval($row['registration_money']);
            $stats['emi_payments'] = floatval($row['emi_payments']);
            $stats['pending_amount'] = floatval($row['pending']);
            $stats['total_transactions'] = intval($row['total_count']);
        }
        $stmt->close();

        // Get component values for revenue calculation
        $sqlLandShare = "
            SELECT COALESCE(SUM(land_share_amount), 0) as total_landshare
            FROM lead_payments
            WHERE payment_status = 'paid' AND payment_type = 'land_share'
            AND payment_date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlLandShare);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_landshare'] = floatval($row['total_landshare']);
        }
        $stmt->close();

        $sqlReg = "
            SELECT COALESCE(SUM(amount), 0) as total_registration
            FROM lead_payments
            WHERE payment_status = 'paid' AND payment_type = 'registration'
            AND payment_date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlReg);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_registration'] = floatval($row['total_registration']);
        }
        $stmt->close();

        $sqlDiscount = "
            SELECT COALESCE(SUM(discount_amount), 0) as total_discount
            FROM lead_bookings
            WHERE booking_date BETWEEN ? AND ?
            AND booking_status = 'booked' AND landshare_status = 'active'
        ";
        $stmt = $this->db->prepare($sqlDiscount);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_discount'] = floatval($row['total_discount']);
        }
        $stmt->close();

        $sqlAgreement = "
            SELECT COALESCE(SUM(agreement_value), 0) as total_agreement
            FROM lead_bookings
            WHERE booking_date BETWEEN ? AND ?
            AND booking_status = 'booked' AND landshare_status = 'active'
        ";
        $stmt = $this->db->prepare($sqlAgreement);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_agreement_value'] = floatval($row['total_agreement']);
        }
        $stmt->close();

        $sqlEmiAmount = "
            SELECT COALESCE(SUM(emi_amount), 0) as total_emi
            FROM emi_schedules
            WHERE due_date BETWEEN ? AND ?
            AND status = 'paid'
        ";
        $stmt = $this->db->prepare($sqlEmiAmount);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_emi_amount'] = floatval($row['total_emi']);
        }
        $stmt->close();

        // Management Cash In
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
            $stats['management_cashin'] = floatval($row['total']);
            $stats['total_transactions'] += intval($row['count']);
        }
        $stmt->close();

        // Service fees
        $sqlServiceFees = "
            SELECT COALESCE(SUM(service_fee), 0) as total_fees
            FROM emi_schedules
            WHERE due_date BETWEEN ? AND ?
            AND status = 'paid'
        ";
        $stmt = $this->db->prepare($sqlServiceFees);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_service_fees'] = floatval($row['total_fees']);
        }
        $stmt->close();

        // This Month
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');
        $sqlMonth = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount, payment_date as date 
                FROM lead_payments 
                WHERE payment_status = 'paid'
                UNION ALL
                SELECT amount, transaction_date as date 
                FROM management_cashin 
                WHERE payment_status = 'paid'
            ) as combined
            WHERE date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlMonth);
        $stmt->bind_param("ss", $currentMonthStart, $currentMonthEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = floatval($row['total']);
        }
        $stmt->close();

        $stats['total_amount'] = $stats['lead_payments'] + $stats['management_cashin'];
        
        $totalCashIn = $stats['total_landshare'] + $stats['total_registration'] + 
                       $stats['total_agreement_value'] + $stats['total_emi_amount'] - 
                       $stats['total_discount'];
        $stats['total_revenue'] = $totalCashIn - ($stats['total_landshare'] - $stats['total_discount'] + 
                                  $stats['total_agreement_value'] + $stats['total_service_fees']);

        return $stats;
    }

    /**
     * FIXED: Get Cash Out Statistics with complete payment method breakdown
     */
    public function getCashOutStatistics($period = 'all_time')
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $stats = [
            'total_amount' => 0,
            'management_cashout' => 0,
            'cashout_payments' => 0,
            'salary_payments' => 0,
            'total_transactions' => 0,
            'this_month' => 0,
            'pending_amount' => 0,
            'paid_amount' => 0
        ];

        // Management Cashout
        $sqlManagement = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COUNT(*) as total_count
            FROM management_cashout
            WHERE transaction_date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlManagement);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['management_cashout'] = floatval($row['paid']);
            $stats['pending_amount'] += floatval($row['pending']);
            $stats['total_transactions'] = intval($row['total_count']);
        }
        $stmt->close();

        // Cashout Request Payments
        $sqlCashoutPayments = "
            SELECT 
                COALESCE(SUM(cp.amount), 0) as total,
                COUNT(*) as count
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
            AND cp.payment_date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlCashoutPayments);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['cashout_payments'] = floatval($row['total']);
            $stats['total_transactions'] += intval($row['count']);
        }
        $stmt->close();

        // Salary Payments
        $sqlSalary = "
            SELECT 
                COALESCE(SUM(net_salary), 0) as total,
                COUNT(*) as count
            FROM payslips
            WHERE status = 'paid'
            AND STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlSalary);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['salary_payments'] = floatval($row['total']);
            $stats['total_transactions'] += intval($row['count']);
        }
        $stmt->close();

        $stats['paid_amount'] = $stats['management_cashout'] + $stats['cashout_payments'] + $stats['salary_payments'];
        $stats['total_amount'] = $stats['paid_amount'] + $stats['pending_amount'];

        // This Month
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');
        $sqlMonth = "
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM (
                SELECT amount as total_amount, transaction_date as date
                FROM management_cashout
                WHERE payment_status = 'paid'
                
                UNION ALL
                
                SELECT cp.amount as total_amount, cp.payment_date as date
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                
                UNION ALL
                
                SELECT net_salary as total_amount, 
                       STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date
                FROM payslips
                WHERE status = 'paid'
            ) combined
            WHERE date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sqlMonth);
        $stmt->bind_param("ss", $currentMonthStart, $currentMonthEnd);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = floatval($row['total']);
        }
        $stmt->close();

        // FIXED: Payment Method Breakdown - Include ALL sources with comprehensive mapping
        // Initialize all payment methods to 0
        $stats['by_payment_method'] = [
            'cash' => 0,
            'bank' => 0,
            'cheque' => 0,
            'online' => 0
        ];
        
        $sqlMethod = "
            SELECT 
                payment_method,
                COALESCE(SUM(amount), 0) as total
            FROM (
                -- Management Cashout
                SELECT payment_method, amount
                FROM management_cashout
                WHERE payment_status = 'paid'
                AND transaction_date BETWEEN ? AND ?
                
                UNION ALL
                
                -- Cashout Request Payments (THIS IS THE MAIN SOURCE)
                SELECT cp.payment_method, cp.amount
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                AND cp.payment_date BETWEEN ? AND ?
                
                UNION ALL
                
                -- Salary Payments (assume bank transfer)
                SELECT 'Bank' as payment_method, net_salary as amount
                FROM payslips
                WHERE status = 'paid'
                AND STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') BETWEEN ? AND ?
            ) combined
            GROUP BY payment_method
        ";
        
        $stmt = $this->db->prepare($sqlMethod);
        $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $method = trim($row['payment_method']);
                $methodLower = strtolower($method);
                $amount = floatval($row['total']);
                
                // Comprehensive mapping - check both original case and lowercase
                if (in_array($methodLower, ['cash', 'cash payment']) || $method === 'Cash') {
                    $stats['by_payment_method']['cash'] += $amount;
                } 
                elseif (in_array($methodLower, ['bank', 'bank transfer', 'bank_transfer']) || $method === 'Bank') {
                    $stats['by_payment_method']['bank'] += $amount;
                } 
                elseif (in_array($methodLower, ['cheque', 'check']) || $method === 'Cheque') {
                    $stats['by_payment_method']['cheque'] += $amount;
                } 
                elseif (in_array($methodLower, ['online', 'online payment', 'mobile banking', 'bkash', 'nagad', 'rocket']) || $method === 'Online') {
                    $stats['by_payment_method']['online'] += $amount;
                } 
                else {
                    // If unrecognized, add to cash (or you can log this for debugging)
                    $stats['by_payment_method']['cash'] += $amount;
                }
            }
        }
        $stmt->close();

        return $stats;
    }

    /**
     * Get Recent Cash In Transactions
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
            AND lp.payment_status = 'paid'
            
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
     * Get Recent Cash Out Transactions
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
     * Get Monthly Cash In Data
     */
    public function getMonthlyCashInData($months = 6, $period = 'all_time')
    {
        if ($period !== 'all_time') {
            $dateRange = $this->getDateRangeByPeriod($period);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            $sql = "
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
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
                    SELECT payment_date as date, amount 
                    FROM lead_payments 
                    WHERE payment_status = 'paid'
                    UNION ALL
                    SELECT transaction_date as date, amount 
                    FROM management_cashin 
                    WHERE payment_status = 'paid'
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
     * Get Monthly Cash Out Data
     */
    public function getMonthlyCashOutData($months = 6, $period = 'all_time')
    {
        if ($period !== 'all_time') {
            $dateRange = $this->getDateRangeByPeriod($period);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            $sql = "
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT transaction_date as date, amount
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    
                    UNION ALL
                    
                    SELECT cp.payment_date as date, cp.amount
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    
                    UNION ALL
                    
                    SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, net_salary as amount
                    FROM payslips
                    WHERE status = 'paid'
                ) combined
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
                    SELECT transaction_date as date, amount
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    
                    UNION ALL
                    
                    SELECT cp.payment_date as date, cp.amount
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    
                    UNION ALL
                    
                    SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, net_salary as amount
                    FROM payslips
                    WHERE status = 'paid'
                ) combined
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
     * Get Weekly Transaction Data
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
                    DAYNAME(date) as day,
                    DAYOFWEEK(date) as day_num,
                    COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT transaction_date as date, amount
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    
                    UNION ALL
                    
                    SELECT cp.payment_date as date, cp.amount
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    
                    UNION ALL
                    
                    SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, net_salary as amount
                    FROM payslips
                    WHERE status = 'paid'
                ) combined
                WHERE date BETWEEN ? AND ?
                GROUP BY DAYOFWEEK(date), DAYNAME(date)
                ORDER BY DAYOFWEEK(date)
            ";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startOfWeek, $endOfWeek);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            $data[$day] = 0;
        }

        while ($row = $result->fetch_assoc()) {
            $data[$row['day']] = floatval($row['total']);
        }

        $stmt->close();
        return array_values($data);
    }

    /**
     * Get Yearly Transaction Data
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
                        YEAR(date) as year,
                        COALESCE(SUM(amount), 0) as total
                    FROM (
                        SELECT transaction_date as date, amount
                        FROM management_cashout
                        WHERE payment_status = 'paid'
                        
                        UNION ALL
                        
                        SELECT cp.payment_date as date, cp.amount
                        FROM cashout_payments cp
                        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                        WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                        
                        UNION ALL
                        
                        SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, net_salary as amount
                        FROM payslips
                        WHERE status = 'paid'
                    ) combined
                    WHERE date BETWEEN ? AND ?
                    GROUP BY YEAR(date)
                    ORDER BY YEAR(date) ASC
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
                        YEAR(date) as year,
                        COALESCE(SUM(amount), 0) as total
                    FROM (
                        SELECT transaction_date as date, amount
                        FROM management_cashout
                        WHERE payment_status = 'paid'
                        
                        UNION ALL
                        
                        SELECT cp.payment_date as date, cp.amount
                        FROM cashout_payments cp
                        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                        WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                        
                        UNION ALL
                        
                        SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, net_salary as amount
                        FROM payslips
                        WHERE status = 'paid'
                    ) combined
                    WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? YEAR)
                    GROUP BY YEAR(date)
                    ORDER BY YEAR(date) ASC
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

    /**
     * Get recent cashout requests
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
            $row['display_status'] = $this->syncStatusWithApproval($row);

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

        if (!empty($approvalStatus)) {
            switch ($approvalStatus) {
                case 'approved':
                case 'partial':
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

        return $cashoutStatus;
    }
}