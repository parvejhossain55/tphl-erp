<?php
class CentralStatement extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Get monthly statement (daily breakdown)
     */
    public function getMonthlyStatement($year, $month, $projectId = null, $category = 'all')
    {
        // Ensure proper formatting
        $year = (int)$year;
        $month = (int)$month;

        // Get all dates in the month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $monthPadded = sprintf("%02d", $month);
        $daysPadded = sprintf("%02d", $daysInMonth);

        $startDate = "$year-$monthPadded-01";
        $endDate = "$year-$monthPadded-$daysPadded";

        // Get opening balance (all transactions before this month)
        $openingBalance = $this->getOpeningBalance($startDate, $projectId, $category);

        // Get daily landshare totals (from lead_payments - booking_money)
        $landshareQuery = "
            SELECT 
                DATE(payment_date) as transaction_date,
                SUM(amount) as total_amount
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(payment_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
            AND payment_type = 'booking_money'
        ";

        // Get daily EMI totals for service fee calculation FROM EMI SCHEDULES
        $emiQuery = "
            SELECT 
                DATE(es.due_date) as transaction_date,
                SUM(es.emi_amount) as total_amount
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE DATE(es.due_date) BETWEEN ? AND ?
            AND es.status = 'paid'
        ";

        // Get daily cash-out totals (BOTH management_cashout + cashout_request payments)
        $cashOutQuery = "
            SELECT 
                transaction_date,
                SUM(total_amount) as total_amount
            FROM (
                -- Management cashout
                SELECT 
                    DATE(transaction_date) as transaction_date,
                    SUM(amount) as total_amount
                FROM management_cashout
                WHERE DATE(transaction_date) BETWEEN ? AND ?
                AND payment_status = 'paid'
                
                UNION ALL
                
                -- Cashout request payments
                SELECT 
                    DATE(cp.payment_date) as transaction_date,
                    SUM(cp.amount) as total_amount
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE DATE(cp.payment_date) BETWEEN ? AND ?
                AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            ) combined
            GROUP BY transaction_date
        ";

        // Add project filter if specified
        if ($projectId) {
            $landshareQuery .= " AND lb.project_id = ?";
            $emiQuery .= " AND lb.project_id = ?";
        }

        // Execute queries
        $landshareStmt = $this->db->prepare($landshareQuery);
        if ($projectId) {
            $landshareStmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $landshareStmt->bind_param("ss", $startDate, $endDate);
        }
        $landshareStmt->execute();
        $landshareResult = $landshareStmt->get_result();
        $landshareData = [];
        while ($row = $landshareResult->fetch_assoc()) {
            $landshareData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        $emiStmt = $this->db->prepare($emiQuery);
        if ($projectId) {
            $emiStmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $emiStmt->bind_param("ss", $startDate, $endDate);
        }
        $emiStmt->execute();
        $emiResult = $emiStmt->get_result();
        $emiData = [];
        while ($row = $emiResult->fetch_assoc()) {
            // Service fee is 10% of PAID EMI amounts
            $emiData[$row['transaction_date']] = (float)$row['total_amount'] * 0.10;
        }

        // Get cashout data with category filter
        $cashOutData = $this->getCashOutData($startDate, $endDate, $category, $projectId);

        // Build daily statement
        $dailyStatement = [];
        $runningBalance = $openingBalance;
        $totalLandshare = 0;
        $totalServiceFee = 0;
        $totalCashOut = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayPadded = sprintf("%02d", $day);
            $date = "$year-$monthPadded-$dayPadded";
            $landshare = $landshareData[$date] ?? 0;
            $serviceFee = $emiData[$date] ?? 0;
            $totalRevenue = $landshare + $serviceFee;
            $cashOut = $cashOutData[$date] ?? 0;
            $netAmount = $totalRevenue - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totalLandshare += $landshare;
            $totalServiceFee += $serviceFee;
            $totalCashOut += $cashOut;

            $dailyStatement[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', strtotime($date)),
                'day_name' => date('l', strtotime($date)),
                'landshare' => $landshare,
                'service_fee' => $serviceFee,
                'total_revenue' => $totalRevenue,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        $totalRevenue = $totalLandshare + $totalServiceFee;

        return [
            'period' => date('F Y', strtotime($startDate)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'daily_data' => $dailyStatement,
            'summary' => [
                'total_landshare' => $totalLandshare,
                'total_service_fee' => $totalServiceFee,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalRevenue - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Helper: Get cashout data (management_cashout + cashout_request payments)
     */
private function getCashOutData($startDate, $endDate, $category = 'all', $projectId = null)
{
    // Get management cashout
    $managementQuery = "
        SELECT 
            DATE(transaction_date) as transaction_date,
            SUM(amount) as total_amount
        FROM management_cashout
        WHERE DATE(transaction_date) BETWEEN ? AND ?
        AND payment_status = 'paid'
    ";

    $managementParams = [$startDate, $endDate];
    $managementTypes = "ss";
    
    if ($category !== 'all') {
        $categoryId = $this->getCategoryIdByName($category);
        if ($categoryId) {
            $managementQuery .= " AND category_id = ?";
            $managementParams[] = $categoryId;
            $managementTypes .= "i";
        }
    }
    
    $managementQuery .= " GROUP BY DATE(transaction_date)";

    $stmt = $this->db->prepare($managementQuery);
    if (!empty($managementParams)) {
        $stmt->bind_param($managementTypes, ...$managementParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $managementData = [];
    while ($row = $result->fetch_assoc()) {
        $managementData[$row['transaction_date']] = (float)$row['total_amount'];
    }

    // Get cashout request payments
    $cashoutRequestQuery = "
        SELECT 
            DATE(cp.payment_date) as transaction_date,
            SUM(cp.amount) as total_amount
        FROM cashout_payments cp
        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
        WHERE DATE(cp.payment_date) BETWEEN ? AND ?
        AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
        GROUP BY DATE(cp.payment_date)
    ";

    $stmt = $this->db->prepare($cashoutRequestQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $cashoutRequestData = [];
    while ($row = $result->fetch_assoc()) {
        $cashoutRequestData[$row['transaction_date']] = (float)$row['total_amount'];
    }
    
    // Get salary payments (payslips)
    $payslipsQuery = "
        SELECT 
            DATE(updated_at) as transaction_date,
            SUM(net_salary) as total_amount
        FROM payslips
        WHERE DATE(updated_at) BETWEEN ? AND ?
        AND status = 'paid'
        GROUP BY DATE(updated_at)
    ";

    $stmt = $this->db->prepare($payslipsQuery);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $payslipsData = [];
    while ($row = $result->fetch_assoc()) {
        $payslipsData[$row['transaction_date']] = (float)$row['total_amount'];
    }

    // Combine all three sources
    $allDates = array_unique(array_merge(
        array_keys($managementData), 
        array_keys($cashoutRequestData),
        array_keys($payslipsData)
    ));
    
    $combinedData = [];
    foreach ($allDates as $date) {
        $combinedData[$date] = 
            ($managementData[$date] ?? 0) + 
            ($cashoutRequestData[$date] ?? 0) + 
            ($payslipsData[$date] ?? 0);
    }
    
    return $combinedData;
}

    /**
     * Get 6-month statement (monthly breakdown)
     */
    public function getSixMonthStatement($endYear, $endMonth, $projectId = null, $category = 'all')
    {
        $monthlyData = [];

        // Calculate 6 months back
        $startDate = date('Y-m-01', strtotime("-5 months", strtotime("$endYear-$endMonth-01")));
        $endDate = date('Y-m-t', strtotime("$endYear-$endMonth-01"));

        // Get opening balance before the 6-month period
        $runningBalance = $this->getOpeningBalance($startDate, $projectId, $category);
        $totalLandshare = 0;
        $totalServiceFee = 0;
        $totalCashOut = 0;

        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-$i months", strtotime("$endYear-$endMonth-01")));
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            $monthStart = "$year-$month-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            // Get monthly totals
            $landshare = $this->getMonthlyLandshare($monthStart, $monthEnd, $projectId);
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category);
            $totalRevenue = $landshare + $serviceFee;
            $netAmount = $totalRevenue - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totalLandshare += $landshare;
            $totalServiceFee += $serviceFee;
            $totalCashOut += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', strtotime($date)),
                'year' => $year,
                'month_num' => $month,
                'landshare' => $landshare,
                'service_fee' => $serviceFee,
                'total_revenue' => $totalRevenue,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        $totalRevenue = $totalLandshare + $totalServiceFee;

        return [
            'period' => 'Last 6 Months',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_landshare' => $totalLandshare,
                'total_service_fee' => $totalServiceFee,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalRevenue - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Get yearly statement (monthly breakdown for a year)
     */
    public function getYearlyStatement($year, $projectId = null, $category = 'all')
    {
        $monthlyData = [];
        $startDate = "$year-01-01";

        // Get opening balance before the year
        $runningBalance = $this->getOpeningBalance($startDate, $projectId, $category);
        $totalLandshare = 0;
        $totalServiceFee = 0;
        $totalCashOut = 0;

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = sprintf("%s-%02d-01", $year, $month);
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            $landshare = $this->getMonthlyLandshare($monthStart, $monthEnd, $projectId);
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category);
            $totalRevenue = $landshare + $serviceFee;
            $netAmount = $totalRevenue - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totalLandshare += $landshare;
            $totalServiceFee += $serviceFee;
            $totalCashOut += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', strtotime($monthStart)),
                'year' => $year,
                'month_num' => $month,
                'landshare' => $landshare,
                'service_fee' => $serviceFee,
                'total_revenue' => $totalRevenue,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        $totalRevenue = $totalLandshare + $totalServiceFee;

        return [
            'period' => "Year $year",
            'start_date' => "$year-01-01",
            'end_date' => "$year-12-31",
            'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_landshare' => $totalLandshare,
                'total_service_fee' => $totalServiceFee,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalRevenue - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Get custom range statement (daily if <= 31 days, monthly if > 31 days)
     */
    public function getCustomRangeStatement($startDate, $endDate, $projectId = null, $category = 'all')
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $daysDiff = ($end - $start) / (60 * 60 * 24);

        if ($daysDiff <= 31) {
            return $this->getCustomDailyStatement($startDate, $endDate, $projectId, $category);
        } else {
            return $this->getCustomMonthlyStatement($startDate, $endDate, $projectId, $category);
        }
    }

    /**
     * Get custom daily statement
     */
    private function getCustomDailyStatement($startDate, $endDate, $projectId = null, $category = 'all')
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $dailyData = [];

        // Get opening balance before the period
        $runningBalance = $this->getOpeningBalance($startDate, $projectId, $category);
        $totalLandshare = 0;
        $totalServiceFee = 0;
        $totalCashOut = 0;

        // Get data for each day
        for ($current = $start; $current <= $end; $current += 86400) {
            $date = date('Y-m-d', $current);
            $landshare = $this->getDailyLandshare($date, $projectId);
            $serviceFee = $this->getDailyServiceFee($date, $projectId);
            $cashOut = $this->getDailyCashOut($date, $category);
            $totalRevenue = $landshare + $serviceFee;
            $netAmount = $totalRevenue - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totalLandshare += $landshare;
            $totalServiceFee += $serviceFee;
            $totalCashOut += $cashOut;

            $dailyData[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', strtotime($date)),
                'day_name' => date('l', strtotime($date)),
                'landshare' => $landshare,
                'service_fee' => $serviceFee,
                'total_revenue' => $totalRevenue,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        $totalRevenue = $totalLandshare + $totalServiceFee;

        return [
            'period' => 'Custom Range (Daily)',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
            'daily_data' => $dailyData,
            'summary' => [
                'total_landshare' => $totalLandshare,
                'total_service_fee' => $totalServiceFee,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalRevenue - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Get custom monthly statement
     */
    private function getCustomMonthlyStatement($startDate, $endDate, $projectId = null, $category = 'all')
    {
        $monthlyData = [];

        $start = strtotime(date('Y-m-01', strtotime($startDate)));
        $end = strtotime(date('Y-m-t', strtotime($endDate)));

        // Get opening balance before the period
        $runningBalance = $this->getOpeningBalance(date('Y-m-01', strtotime($startDate)), $projectId, $category);
        $totalLandshare = 0;
        $totalServiceFee = 0;
        $totalCashOut = 0;

        $current = $start;
        while ($current <= $end) {
            $monthStart = date('Y-m-01', $current);
            $monthEnd = date('Y-m-t', $current);

            $landshare = $this->getMonthlyLandshare($monthStart, $monthEnd, $projectId);
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category);
            $totalRevenue = $landshare + $serviceFee;
            $netAmount = $totalRevenue - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totalLandshare += $landshare;
            $totalServiceFee += $serviceFee;
            $totalCashOut += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', $current),
                'year' => date('Y', $current),
                'month_num' => date('m', $current),
                'landshare' => $landshare,
                'service_fee' => $serviceFee,
                'total_revenue' => $totalRevenue,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];

            $current = strtotime('+1 month', $current);
        }

        $totalRevenue = $totalLandshare + $totalServiceFee;

        return [
            'period' => 'Custom Range (Monthly)',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance(date('Y-m-01', strtotime($startDate)), $projectId, $category),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_landshare' => $totalLandshare,
                'total_service_fee' => $totalServiceFee,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalRevenue - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Helper: Get opening balance before a specific date
     */
    private function getOpeningBalance($startDate, $projectId = null, $category = 'all')
    {
        // Get all landshare before start date
        $landshareQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(payment_date) < ? 
            AND payment_status = 'paid'
            AND payment_type = 'booking_money'
        ";

        // Get all EMI before start date for service fee calculation FROM EMI SCHEDULES
        $emiQuery = "
            SELECT COALESCE(SUM(es.emi_amount), 0) as total
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE DATE(es.due_date) < ? 
            AND es.status = 'paid'
        ";

        // Get all cash-out before start date (BOTH management_cashout + cashout_request payments)
        $cashOutQuery = "
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM (
                -- Management cashout
                SELECT amount as total_amount
                FROM management_cashout
                WHERE DATE(transaction_date) < ? 
                AND payment_status = 'paid'
                
                UNION ALL
                
                -- Cashout request payments
                SELECT cp.amount as total_amount
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE DATE(cp.payment_date) < ?
                AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            ) combined
        ";

        // Add project filter if specified
        if ($projectId) {
            $landshareQuery .= " AND lb.project_id = ?";
            $emiQuery .= " AND lb.project_id = ?";
        }

        // Add category filter if specified (applies only to management_cashout)
        if ($category !== 'all') {
            $categoryId = $this->getCategoryIdByName($category);
            if ($categoryId) {
                $cashOutQuery = "
                    SELECT COALESCE(SUM(total_amount), 0) as total
                    FROM (
                        -- Management cashout with category filter
                        SELECT amount as total_amount
                        FROM management_cashout
                        WHERE DATE(transaction_date) < ? 
                        AND payment_status = 'paid'
                        AND category_id = ?
                        
                        UNION ALL
                        
                        -- Cashout request payments (no category filter)
                        SELECT cp.amount as total_amount
                        FROM cashout_payments cp
                        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                        WHERE DATE(cp.payment_date) < ?
                        AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    ) combined
                ";
            }
        }

        $totalLandshare = 0;
        $stmt = $this->db->prepare($landshareQuery);
        if ($projectId) {
            $stmt->bind_param("si", $startDate, $projectId);
        } else {
            $stmt->bind_param("s", $startDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalLandshare = (float)$row['total'];
        }

        $totalEmi = 0;
        $stmt = $this->db->prepare($emiQuery);
        if ($projectId) {
            $stmt->bind_param("si", $startDate, $projectId);
        } else {
            $stmt->bind_param("s", $startDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalEmi = (float)$row['total'];
        }

        $totalCashOut = 0;
        $stmt = $this->db->prepare($cashOutQuery);
        if ($category !== 'all' && $categoryId) {
            $stmt->bind_param("sis", $startDate, $categoryId, $startDate);
        } else {
            $stmt->bind_param("ss", $startDate, $startDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalCashOut = (float)$row['total'];
        }

        // Calculate service fee as 10% of total EMI
        $totalServiceFee = $totalEmi * 0.10;
        $openingBalance = ($totalLandshare + $totalServiceFee) - $totalCashOut;

        return $openingBalance;
    }

    /**
     * Helper: Get daily landshare total
     */
    private function getDailyLandshare($date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(payment_date) = ? 
            AND payment_status = 'paid'
            AND payment_type = 'booking_money'
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("si", $date, $projectId);
        } else {
            $stmt->bind_param("s", $date);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get daily service fee total (10% of EMI payments)
     */
    private function getDailyServiceFee($date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(es.emi_amount), 0) as total
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE DATE(es.due_date) = ?
            AND es.status = 'paid'
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("si", $date, $projectId);
        } else {
            $stmt->bind_param("s", $date);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalEmi = (float)($row['total'] ?? 0);
        return $totalEmi * 0.10; // 10% service fee
    }

    /**
     * Helper: Get daily cash-out total (BOTH management_cashout + cashout_request payments)
     */
private function getDailyCashOut($date, $category = 'all')
{
    // Get category ID if a specific category is selected
    $categoryId = null;
    if ($category !== 'all') {
        $categoryId = $this->getCategoryIdByName($category);
    }
    
    // Query combining ALL THREE sources
    $query = "
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM (
            -- Management cashout
            SELECT amount as total_amount
            FROM management_cashout
            WHERE DATE(transaction_date) = ? 
            AND payment_status = 'paid'
            " . ($categoryId ? "AND category_id = ?" : "") . "
            
            UNION ALL
            
            -- Cashout request payments
            SELECT cp.amount as total_amount
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE DATE(cp.payment_date) = ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            
            UNION ALL
            
            -- Salary payments (payslips)
            SELECT net_salary as total_amount
            FROM payslips
            WHERE DATE(updated_at) = ?
            AND status = 'paid'
        ) combined
    ";

    $stmt = $this->db->prepare($query);
    if (!$stmt) {
        throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
    }

    // Bind parameters: 3 dates total
    if ($categoryId) {
        // With category: date, categoryId, date, date
        $stmt->bind_param("siss", $date, $categoryId, $date, $date);
    } else {
        // Without category: date, date, date
        $stmt->bind_param("sss", $date, $date, $date);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (float)($row['total'] ?? 0);
}

    /**
     * Helper: Get monthly landshare total
     */
    private function getMonthlyLandshare($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(payment_date) BETWEEN ? AND ? 
            AND payment_status = 'paid'
            AND payment_type = 'booking_money'
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get monthly service fee total (10% of EMI payments)
     */
    private function getMonthlyServiceFee($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(es.emi_amount), 0) as total
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE DATE(es.due_date) BETWEEN ? AND ?
            AND es.status = 'paid'
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalEmi = (float)($row['total'] ?? 0);
        return $totalEmi * 0.10; // 10% service fee
    }

    /**
     * Helper: Get monthly cash-out total (BOTH management_cashout + cashout_request payments)
     */
private function getMonthlyCashOut($startDate, $endDate, $category = 'all')
{
    // Get category ID if a specific category is selected
    $categoryId = null;
    if ($category !== 'all') {
        $categoryId = $this->getCategoryIdByName($category);
    }
    
    // Query combining ALL THREE sources
    $query = "
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM (
            -- Management cashout
            SELECT amount as total_amount
            FROM management_cashout
            WHERE DATE(transaction_date) BETWEEN ? AND ? 
            AND payment_status = 'paid'
            " . ($categoryId ? "AND category_id = ?" : "") . "
            
            UNION ALL
            
            -- Cashout request payments
            SELECT cp.amount as total_amount
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE DATE(cp.payment_date) BETWEEN ? AND ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            
            UNION ALL
            
            -- Salary payments (payslips)
            SELECT net_salary as total_amount
            FROM payslips
            WHERE DATE(updated_at) BETWEEN ? AND ?
            AND status = 'paid'
        ) combined
    ";

    $stmt = $this->db->prepare($query);
    if (!$stmt) {
        throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
    }

    // Bind parameters: 6 dates total (2 for management, 2 for cashout, 2 for payslips)
    if ($categoryId) {
        // With category: start, end, categoryId, start, end, start, end
        $stmt->bind_param("ssissss", $startDate, $endDate, $categoryId, $startDate, $endDate, $startDate, $endDate);
    } else {
        // Without category: start, end, start, end, start, end
        $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (float)($row['total'] ?? 0);
}


    /**
     * Get overall statistics with period filter - FIXED VERSION
     */
    public function getOverallStatistics($periodType = 'all_time', $year = null, $month = null, $startDate = null, $endDate = null, $projectId = null, $category = 'all')
    {
        // Build date conditions based on period type
        $dateConditions = $this->buildDateConditions($periodType, $year, $month, $startDate, $endDate);
        
        // 1. Get landshare data
        $landshareQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE payment_status = 'paid'
            AND payment_type = 'booking_money'
            {$dateConditions['landshare']}
        ";

        $landshareParams = $dateConditions['params'];
        $landshareTypes = $dateConditions['types'];

        if ($projectId) {
            $landshareQuery .= " AND lb.project_id = ?";
            $landshareParams[] = $projectId;
            $landshareTypes .= "i";
        }

        $stmt = $this->db->prepare($landshareQuery);
        if (!empty($landshareParams)) {
            $stmt->bind_param($landshareTypes, ...$landshareParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $totalLandshare = 0;
        if ($row = $result->fetch_assoc()) {
            $totalLandshare = (float)$row['total'];
        }

        // 2. Get EMI data for service fee FROM EMI SCHEDULES
        $emiQuery = "
            SELECT COALESCE(SUM(es.emi_amount), 0) as total
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE es.status = 'paid'
            {$dateConditions['emi']}
        ";

        $emiParams = $dateConditions['params'];
        $emiTypes = $dateConditions['types'];

        if ($projectId) {
            $emiQuery .= " AND lb.project_id = ?";
            $emiParams[] = $projectId;
            $emiTypes .= "i";
        }

        $stmt = $this->db->prepare($emiQuery);
        if (!empty($emiParams)) {
            $stmt->bind_param($emiTypes, ...$emiParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $totalEmi = 0;
        if ($row = $result->fetch_assoc()) {
            $totalEmi = (float)$row['total'];
        }

        // Calculate service fee as 10% of total EMI
        $totalServiceFee = $totalEmi * 0.10;

        // 3. Get cash-out data
        $cashOutData = $this->getCashOutForStatistics($periodType, $year, $month, $startDate, $endDate, $category, $projectId);
        $totalCashOut = $cashOutData['total'] ?? 0;

        $totalRevenue = $totalLandshare + $totalServiceFee;

        return [
            'total_landshare' => $totalLandshare,
            'total_emi' => $totalEmi, // Keep raw EMI for reference
            'total_service_fee' => $totalServiceFee,
            'total_revenue' => $totalRevenue,
            'total_cash_out' => $totalCashOut,
            'net_balance' => $totalRevenue - $totalCashOut
        ];
    }

    /**
     * Helper: Build date conditions for queries
     */
/**
 * Helper: Build date conditions for queries - FIXED to include payslips
 */
/**
 * Helper: Build date conditions for queries - FIXED with correct payslips column
 */
private function buildDateConditions($periodType, $year, $month, $startDate, $endDate)
{
    $conditions = [
        'landshare' => '',
        'emi' => '',
        'management_cashout' => '',
        'cashout_request' => '',
        'payslips' => '', // For salary payments
        'management_cashout_params' => [],
        'management_cashout_types' => '',
        'cashout_request_params' => [],
        'cashout_request_types' => '',
        'payslips_params' => [],
        'payslips_types' => '',
        'params' => [],
        'types' => ''
    ];

    if ($periodType === 'all_time') {
        return $conditions;
    }

    if ($periodType === 'monthly' && $year && $month) {
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));
        
        $conditions['landshare'] = " AND DATE(lp.payment_date) BETWEEN ? AND ?";
        $conditions['emi'] = " AND DATE(es.due_date) BETWEEN ? AND ?";
        $conditions['management_cashout'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
        $conditions['cashout_request'] = " AND DATE(cp.payment_date) BETWEEN ? AND ?";
        $conditions['payslips'] = " AND payslip_month BETWEEN ? AND ?"; // CHANGED to payslip_month
        
        $conditions['management_cashout_params'] = [$startDate, $endDate];
        $conditions['management_cashout_types'] = "ss";
        $conditions['cashout_request_params'] = [$startDate, $endDate];
        $conditions['cashout_request_types'] = "ss";
        $conditions['payslips_params'] = [$startDate, $endDate];
        $conditions['payslips_types'] = "ss";
        
        $conditions['params'] = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
        $conditions['types'] = "ssssss";
    }
    elseif ($periodType === 'yearly' && $year) {
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";
        
        $conditions['landshare'] = " AND DATE(lp.payment_date) BETWEEN ? AND ?";
        $conditions['emi'] = " AND DATE(es.due_date) BETWEEN ? AND ?";
        $conditions['management_cashout'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
        $conditions['cashout_request'] = " AND DATE(cp.payment_date) BETWEEN ? AND ?";
        $conditions['payslips'] = " AND payslip_month BETWEEN ? AND ?"; // CHANGED to payslip_month
        
        $conditions['management_cashout_params'] = [$startDate, $endDate];
        $conditions['management_cashout_types'] = "ss";
        $conditions['cashout_request_params'] = [$startDate, $endDate];
        $conditions['cashout_request_types'] = "ss";
        $conditions['payslips_params'] = [$startDate, $endDate];
        $conditions['payslips_types'] = "ss";
        
        $conditions['params'] = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
        $conditions['types'] = "ssssss";
    }
    elseif ($periodType === 'custom' && $startDate && $endDate) {
        $conditions['landshare'] = " AND DATE(lp.payment_date) BETWEEN ? AND ?";
        $conditions['emi'] = " AND DATE(es.due_date) BETWEEN ? AND ?";
        $conditions['management_cashout'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
        $conditions['cashout_request'] = " AND DATE(cp.payment_date) BETWEEN ? AND ?";
        $conditions['payslips'] = " AND payslip_month BETWEEN ? AND ?"; // CHANGED to payslip_month
        
        $conditions['management_cashout_params'] = [$startDate, $endDate];
        $conditions['management_cashout_types'] = "ss";
        $conditions['cashout_request_params'] = [$startDate, $endDate];
        $conditions['cashout_request_types'] = "ss";
        $conditions['payslips_params'] = [$startDate, $endDate];
        $conditions['payslips_types'] = "ss";
        
        $conditions['params'] = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
        $conditions['types'] = "ssssss";
    }

    return $conditions;
}
    /**
     * Helper: Get cash-out data for statistics
     */
/**
 * Helper: Get cash-out total for statistics with period & category filters
 */
/**
 * Helper: Get cash-out total for statistics with period & category filters - FIXED to include payslips
 */
private function getCashOutForStatistics($periodType = 'all_time', $year = null, $month = null, $startDate = null, $endDate = null, $category = 'all', $projectId = null)
{
    // Build date conditions based on period type
    $dateConditions = $this->buildDateConditions($periodType, $year, $month, $startDate, $endDate);

    // Get category ID if a specific category is selected
    $categoryId = null;
    if ($category !== 'all') {
        $categoryId = $this->getCategoryIdByName($category);
    }

    // FIXED QUERY: Properly combines ALL THREE sources
    $query = "
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM (
            -- Management cashout
            SELECT amount as total_amount
            FROM management_cashout
            WHERE payment_status = 'paid'
            {$dateConditions['management_cashout']}
            " . ($categoryId ? " AND category_id = ?" : "") . "
            
            UNION ALL
            
            -- Cashout request payments (no category filter)
            SELECT cp.amount as total_amount
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
            {$dateConditions['cashout_request']}
            
            UNION ALL
            
            -- Salary payments (payslips) - ONLY include paid salaries
            SELECT net_salary as total_amount
            FROM payslips
            WHERE status = 'paid'
            {$dateConditions['payslips']}
        ) combined
    ";

    $stmt = $this->db->prepare($query);
    if (!$stmt) {
        throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
    }

    // Build the parameters dynamically
    $params = [];
    $types = "";

    // Management cashout dates
    if (!empty($dateConditions['management_cashout_params'])) {
        $params = array_merge($params, $dateConditions['management_cashout_params']);
        $types .= $dateConditions['management_cashout_types'];
    }

    // Category (only for management_cashout)
    if ($categoryId) {
        $params[] = $categoryId;
        $types .= "i";
    }

    // Cashout request dates
    if (!empty($dateConditions['cashout_request_params'])) {
        $params = array_merge($params, $dateConditions['cashout_request_params']);
        $types .= $dateConditions['cashout_request_types'];
    }

    // Payslips dates
    if (!empty($dateConditions['payslips_params'])) {
        $params = array_merge($params, $dateConditions['payslips_params']);
        $types .= $dateConditions['payslips_types'];
    }

    // Bind parameters if any
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return [
        'total' => (float)($row['total'] ?? 0)
    ];
}


    /**
     * Helper: Get category ID by name
     */
    private function getCategoryIdByName($categoryName)
    {
        // Map category names to IDs based on your management_categories table
        $categoryMap = [
            'cashout' => 2,
            'landshare' => null,
            'utilities' => 1,
            'other' => null
        ];

        return $categoryMap[$categoryName] ?? null;
    }

    /**
     * NEW: Get service fee from EMI for any period
     */
    public function getServiceFeeFromEmi($fromDate = null, $toDate = null, $projectId = null, $category = null)
    {
        $query = "
            SELECT COALESCE(SUM(es.emi_amount * 0.10), 0) as service_fee
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE es.status = 'paid'
        ";

        $params = [];
        $types = "";

        if ($fromDate && $toDate) {
            $query .= " AND DATE(es.due_date) BETWEEN ? AND ?";
            $params[] = $fromDate;
            $params[] = $toDate;
            $types .= "ss";
        }

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
            $params[] = $projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (float)($row['service_fee'] ?? 0);
    }
}
?>