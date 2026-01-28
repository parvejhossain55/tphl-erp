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
                DATE(es.created_at) as transaction_date,
                SUM(es.emi_amount) as total_amount
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE DATE(es.created_at) BETWEEN ? AND ?
            AND es.status IN ('paid', 'pending')
        ";

        // Get daily cash-out totals
        $cashOutQuery = "
            SELECT 
                DATE(transaction_date) as transaction_date,
                SUM(amount) as total_amount
            FROM management_cashout
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
        ";

        // Add project filter if specified
        if ($projectId) {
            $landshareQuery .= " AND lb.project_id = ?";
            $emiQuery .= " AND lb.project_id = ?";
        }

        // Add category filter if specified
        if ($category !== 'all') {
            $cashOutQuery .= " AND category_id = ?";
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
            $emiData[$row['transaction_date']] = (float)$row['total_amount'] * 0.10; // 10% service fee
        }

        $cashOutStmt = $this->db->prepare($cashOutQuery);
        if ($category !== 'all') {
            $cashOutStmt->bind_param("ssi", $startDate, $endDate, $category);
        } else {
            $cashOutStmt->bind_param("ss", $startDate, $endDate);
        }
        $cashOutStmt->execute();
        $cashOutResult = $cashOutStmt->get_result();
        $cashOutData = [];
        while ($row = $cashOutResult->fetch_assoc()) {
            $cashOutData[$row['transaction_date']] = (float)$row['total_amount'];
        }

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
            WHERE DATE(es.created_at) < ? 
            AND es.status IN ('paid', 'pending')
        ";

        // Get all cash-out before start date
        $cashOutQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE DATE(transaction_date) < ? 
            AND payment_status = 'paid'
        ";

        // Add project filter if specified
        if ($projectId) {
            $landshareQuery .= " AND lb.project_id = ?";
            $emiQuery .= " AND lb.project_id = ?";
        }

        // Add category filter if specified
        if ($category !== 'all') {
            $cashOutQuery .= " AND category_id = ?";
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
            $totalEmi = (float)$row['total'] * 0.10; // 10% service fee
        }

        $totalCashOut = 0;
        $stmt = $this->db->prepare($cashOutQuery);
        if ($category !== 'all') {
            $stmt->bind_param("si", $startDate, $category);
        } else {
            $stmt->bind_param("s", $startDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalCashOut = (float)$row['total'];
        }

        $openingBalance = ($totalLandshare + $totalEmi) - $totalCashOut;

        // Debug log
        error_log("Opening Balance Calculation for $startDate:");
        error_log("Total Landshare before start: " . $totalLandshare);
        error_log("Total Service Fee before start: " . $totalEmi);
        error_log("Total Cash Out before start: " . $totalCashOut);
        error_log("Opening Balance: " . $openingBalance);

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
     * Helper: Get daily service fee total (10% of EMI)
     */
    private function getDailyServiceFee($date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(es.emi_amount), 0) as total
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE DATE(es.created_at) = ?
            AND es.status IN ('paid', 'pending')
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
     * Helper: Get daily cash-out total
     */
    private function getDailyCashOut($date, $category = 'all')
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE DATE(transaction_date) = ? 
            AND payment_status = 'paid'
        ";

        if ($category !== 'all') {
            $query .= " AND category_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($category !== 'all') {
            $stmt->bind_param("si", $date, $category);
        } else {
            $stmt->bind_param("s", $date);
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
     * Helper: Get monthly service fee total (10% of EMI)
     */
    private function getMonthlyServiceFee($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(es.emi_amount), 0) as total
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE DATE(es.created_at) BETWEEN ? AND ?
            AND es.status IN ('paid')
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
     * Helper: Get monthly cash-out total
     */
    private function getMonthlyCashOut($startDate, $endDate, $category = 'all')
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE DATE(transaction_date) BETWEEN ? AND ? 
            AND payment_status = 'paid'
        ";

        if ($category !== 'all') {
            $query .= " AND category_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($category !== 'all') {
            $stmt->bind_param("ssi", $startDate, $endDate, $category);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get overall statistics with period filter
     */
    /**
     * Get overall statistics with period filter
     */
    public function getOverallStatistics($periodType = 'all_time', $year = null, $month = null, $startDate = null, $endDate = null, $projectId = null, $category = 'all')
    {
        // Reset variables
        $totalLandshare = 0;
        $totalEmi = 0;
        $totalCashOut = 0;

        // Debug logs to see what's being passed
        error_log("=== getOverallStatistics called ===");
        error_log("periodType: $periodType");
        error_log("year: $year");
        error_log("month: $month");
        error_log("startDate: $startDate");
        error_log("endDate: $endDate");
        error_log("projectId: $projectId");
        error_log("category: $category");

        // 1. Get landshare data
        $landshareQuery = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM lead_payments lp
        LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE payment_status = 'paid'
        AND payment_type = 'booking_money'
    ";

        $landshareParams = [];
        $landshareTypes = "";

        if ($periodType !== 'all_time' && $startDate && $endDate) {
            $landshareQuery .= " AND DATE(payment_date) BETWEEN ? AND ?";
            $landshareParams[] = $startDate;
            $landshareParams[] = $endDate;
            $landshareTypes .= "ss";
        }

        if ($projectId) {
            $landshareQuery .= " AND lb.project_id = ?";
            $landshareParams[] = $projectId;
            $landshareTypes .= "i";
        }

        // Debug landshare query
        error_log("Landshare Query: $landshareQuery");
        error_log("Landshare Params: " . print_r($landshareParams, true));

        $stmt = $this->db->prepare($landshareQuery);
        if (!empty($landshareParams)) {
            $stmt->bind_param($landshareTypes, ...$landshareParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalLandshare = (float)$row['total'];
        }
        error_log("Total Landshare: " . $totalLandshare);

        // 2. Get EMI data for service fee
        $emiQuery = "
        SELECT COALESCE(SUM(es.emi_amount), 0) as total
        FROM emi_schedules es
        LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
        WHERE 1=1
    ";

        $emiParams = [];
        $emiTypes = "";

        if ($periodType !== 'all_time' && $startDate && $endDate) {
            $emiQuery .= " AND DATE(es.created_at) BETWEEN ? AND ?";
            $emiParams[] = $startDate;
            $emiParams[] = $endDate;
            $emiTypes .= "ss";
        }

        if ($projectId) {
            $emiQuery .= " AND lb.project_id = ?";
            $emiParams[] = $projectId;
            $emiTypes .= "i";
        }

        // Debug EMI query
        error_log("EMI Query: $emiQuery");
        error_log("EMI Params: " . print_r($emiParams, true));

        $stmt = $this->db->prepare($emiQuery);
        if (!empty($emiParams)) {
            $stmt->bind_param($emiTypes, ...$emiParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalEmi = (float)$row['total'] * 0.10; // 10% service fee
        }
        error_log("Total EMI (before 10%): " . ($totalEmi / 0.10));
        error_log("Total Service Fee (10% of EMI): " . $totalEmi);

        // 3. Get cash-out data
        $cashOutQuery = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM management_cashout
        WHERE payment_status = 'paid'
    ";

        $cashOutParams = [];
        $cashOutTypes = "";

        if ($periodType !== 'all_time' && $startDate && $endDate) {
            $cashOutQuery .= " AND DATE(transaction_date) BETWEEN ? AND ?";
            $cashOutParams[] = $startDate;
            $cashOutParams[] = $endDate;
            $cashOutTypes .= "ss";
        }

        if ($category !== 'all') {
            $categoryId = $this->getCategoryIdByName($category);
            if ($categoryId) {
                $cashOutQuery .= " AND category_id = ?";
                $cashOutParams[] = $categoryId;
                $cashOutTypes .= "i";
            }
        }

        // Debug cash out query
        error_log("Cash Out Query: $cashOutQuery");
        error_log("Cash Out Params: " . print_r($cashOutParams, true));

        $stmt = $this->db->prepare($cashOutQuery);
        if (!empty($cashOutParams)) {
            $stmt->bind_param($cashOutTypes, ...$cashOutParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalCashOut = (float)$row['total'];
        }
        error_log("Total Cash Out: " . $totalCashOut);

        $totalRevenue = $totalLandshare + $totalEmi;

        error_log("=== Results ===");
        error_log("Total Landshare: " . $totalLandshare);
        error_log("Total Service Fee: " . $totalEmi);
        error_log("Total Revenue: " . $totalRevenue);
        error_log("Total Cash Out: " . $totalCashOut);
        error_log("Net Balance: " . ($totalRevenue - $totalCashOut));

        return [
            'total_landshare' => $totalLandshare,
            'total_service_fee' => $totalEmi,
            'total_revenue' => $totalRevenue,
            'total_cash_out' => $totalCashOut,
            'net_balance' => $totalRevenue - $totalCashOut
        ];
    }

    /**
     * Helper: Get category ID by name
     */

    /**
     * Helper: Get category ID by name
     */
    private function getCategoryIdByName($categoryName)
    {
        // Map category names to IDs based on your management_categories table
        $categoryMap = [
            'construction' => 2, // Based on your data: construction has id 2
            'landshare' => null, // This might not exist in management_categories
            'utilities' => 1,    // Based on your data: Utility Bill has id 1
            'other' => null      // This might not exist in management_categories
        ];

        return $categoryMap[$categoryName] ?? null;
    }
}
