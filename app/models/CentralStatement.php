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

        // Get daily agreement value totals (from lead_bookings)
        $agreementQuery = "
            SELECT 
                DATE(booking_date) as transaction_date,
                SUM(agreement_value) as total_amount
            FROM lead_bookings
            WHERE DATE(booking_date) BETWEEN ? AND ?
            AND booking_status = 'booked'
            AND landshare_status = 'active'
        ";

        // Get daily service fee totals from emi_schedules
        $serviceFeeQuery = "
            SELECT 
                DATE(due_date) as transaction_date,
                SUM(service_fee) as total_amount
            FROM emi_schedules
            WHERE DATE(due_date) BETWEEN ? AND ?
            AND status = 'paid'
        ";

        // Get daily management cashin totals
        $cashinQuery = "
            SELECT 
                DATE(transaction_date) as transaction_date,
                SUM(amount) as total_amount
            FROM management_cashin
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
        ";

        // Get daily cash-out totals based on category
        $cashOutData = $this->getCashOutData($startDate, $endDate, $category, $projectId);

        // Add project filter if specified
        if ($projectId) {
            $agreementQuery .= " AND project_id = ?";
            // For service fee, we need to join with lead_bookings
            $serviceFeeQuery = "
                SELECT 
                    DATE(es.due_date) as transaction_date,
                    SUM(es.service_fee) as total_amount
                FROM emi_schedules es
                LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE DATE(es.due_date) BETWEEN ? AND ?
                AND es.status = 'paid'
                AND lb.project_id = ?
            ";
            // Cashin doesn't have project filter as it's company-wide
        }

        // Execute queries
        // 1. Agreement values
        $agreementStmt = $this->db->prepare($agreementQuery);
        if ($projectId) {
            $agreementStmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $agreementStmt->bind_param("ss", $startDate, $endDate);
        }
        $agreementStmt->execute();
        $agreementResult = $agreementStmt->get_result();
        $agreementData = [];
        while ($row = $agreementResult->fetch_assoc()) {
            $agreementData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        // 2. Service fees
        $serviceFeeStmt = $this->db->prepare($serviceFeeQuery);
        if ($projectId) {
            $serviceFeeStmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $serviceFeeStmt->bind_param("ss", $startDate, $endDate);
        }
        $serviceFeeStmt->execute();
        $serviceFeeResult = $serviceFeeStmt->get_result();
        $serviceFeeData = [];
        while ($row = $serviceFeeResult->fetch_assoc()) {
            $serviceFeeData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        // 3. Management cashin
        $cashinStmt = $this->db->prepare($cashinQuery);
        $cashinStmt->bind_param("ss", $startDate, $endDate);
        $cashinStmt->execute();
        $cashinResult = $cashinStmt->get_result();
        $cashinData = [];
        while ($row = $cashinResult->fetch_assoc()) {
            $cashinData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        // Build daily statement
        $dailyStatement = [];
        $runningBalance = $openingBalance;
        $totalAgreement = 0;
        $totalServiceFee = 0;
        $totalCashin = 0;
        $totalCashOut = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayPadded = sprintf("%02d", $day);
            $date = "$year-$monthPadded-$dayPadded";
            $agreement = $agreementData[$date] ?? 0;
            $serviceFee = $serviceFeeData[$date] ?? 0;
            $managementCashin = $cashinData[$date] ?? 0;
            $cashin = $agreement + $serviceFee + $managementCashin; // Total cash in
            $cashOut = $cashOutData[$date] ?? 0;
            $netAmount = $cashin - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totalAgreement += $agreement;
            $totalServiceFee += $serviceFee;
            $totalCashin += $cashin;
            $totalCashOut += $cashOut;

            // In monMonthlyStatement() method:
            $dailyStatement[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', strtotime($date)),
                'day_name' => date('l', strtotime($date)),
                // Backward compatibility keys
                'landshare' => $agreement,
                'total_revenue' => $cashin,
                // New keys
                'agreement_value' => $agreement,
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin,
                'total_cashin' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        $totalRevenue = $totalAgreement + $totalServiceFee; // Business revenue (agreement + service fee)

        return [
            'period' => date('F Y', strtotime($startDate)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'daily_data' => $dailyStatement,
            'summary' => [
                'total_agreement' => $totalAgreement,
                'total_service_fee' => $totalServiceFee,
                'total_management_cashin' => $totalCashin - $totalRevenue, // Only management cashin
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalCashin - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Helper: Get cashout data (management_cashout + cashout_request payments) with category filtering
     */
    private function getCashOutData($startDate, $endDate, $category = 'all', $projectId = null)
    {
        if ($category === 'utilities') {
            return $this->getUtilitiesCashOutData($startDate, $endDate);
        }

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

        // Get cashout request payments (only for 'cashout' category or 'all')
        $cashoutRequestData = [];
        if ($category === 'all' || $category === 'cashout') {
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
            while ($row = $result->fetch_assoc()) {
                $cashoutRequestData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Get salary payments (payslips) - only for 'all' category
        $payslipsData = [];
        if ($category === 'all') {
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
            while ($row = $result->fetch_assoc()) {
                $payslipsData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Combine all sources based on category
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
     * Helper: Get Utilities cashout data from cashout_requests table based on keywords
     */
    private function getUtilitiesCashOutData($startDate, $endDate)
    {
        // Keywords to identify utility bills
        $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

        // Build keyword condition
        $keywordConditions = [];
        foreach ($utilityKeywords as $keyword) {
            $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
        }
        $keywordCondition = implode(' OR ', $keywordConditions);

        $query = "
            SELECT 
                DATE(cp.payment_date) as transaction_date,
                SUM(cp.amount) as total_amount
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE DATE(cp.payment_date) BETWEEN ? AND ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            AND ($keywordCondition)
            GROUP BY DATE(cp.payment_date)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $utilitiesData = [];
        while ($row = $result->fetch_assoc()) {
            $utilitiesData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        return $utilitiesData;
    }

    /**
     * Helper: Get cashout query based on category
     */
    private function getCashOutQueryByCategory($category, $startDate, $endDate)
    {
        if ($category === 'utilities') {
            $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

            $keywordConditions = [];
            foreach ($utilityKeywords as $keyword) {
                $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
            }
            $keywordCondition = implode(' OR ', $keywordConditions);

            return "
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM (
                    SELECT cp.amount as total_amount
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE DATE(cp.payment_date) BETWEEN ? AND ?
                    AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    AND ($keywordCondition)
                ) combined
            ";
        }

        // For other categories, use the existing logic
        return "
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM (
                -- Management cashout
                SELECT amount as total_amount
                FROM management_cashout
                WHERE DATE(transaction_date) BETWEEN ? AND ? 
                AND payment_status = 'paid'
                
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
    }

    /**
     * Get 6-month statement (monthly breakdown)
     */
/**
 * Get 6-month statement (monthly breakdown)
 */
public function getSixMonthStatement($endYear, $endMonth, $projectId = null, $category = 'all')
{
    // Convert empty string to null
    if ($projectId === '') {
        $projectId = null;
    }
    
    $monthlyData = [];

    // Calculate 6 months back
    $startDate = date('Y-m-01', strtotime("-5 months", strtotime("$endYear-$endMonth-01")));
    $endDate = date('Y-m-t', strtotime("$endYear-$endMonth-01"));

    // Get opening balance before the 6-month period
    $runningBalance = $this->getOpeningBalance($startDate, $projectId, $category);
    $totalAgreement = 0;
    $totalServiceFee = 0;
    $totalManagementCashin = 0;
    $totalCashOut = 0;

    for ($i = 5; $i >= 0; $i--) {
        $date = date('Y-m-01', strtotime("-$i months", strtotime("$endYear-$endMonth-01")));
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        $monthStart = "$year-$month-01";
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        // Get monthly totals
        $agreement = $this->getMonthlyAgreementValue($monthStart, $monthEnd, $projectId);
        $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
        $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
        
        // Only get cashout for expense categories, not for revenue categories
        $cashOut = 0;
        if (in_array($category, ['all', 'cashout', 'utilities', 'other'])) {
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category);
        }
        
        $cashin = $agreement + $serviceFee + $managementCashin;
        $netAmount = $cashin - $cashOut;
        $runningBalance += $netAmount;

        // Accumulate totals
        $totalAgreement += $agreement;
        $totalServiceFee += $serviceFee;
        $totalManagementCashin += $managementCashin;
        $totalCashOut += $cashOut;

        $monthlyData[] = [
            'month' => date('F Y', strtotime($date)),
            'year' => $year,
            'month_num' => $month,
            // Backward compatibility
            'landshare' => $agreement,
            'total_revenue' => $cashin,
            // New keys
            'agreement_value' => $agreement,
            'service_fee' => $serviceFee,
            'management_cashin' => $managementCashin,
            'total_cashin' => $cashin,
            'cash_out' => $cashOut,
            'net_amount' => $netAmount,
            'running_balance' => $runningBalance
        ];
    }

    $totalRevenue = $totalAgreement + $totalServiceFee;
    $totalCashin = $totalAgreement + $totalServiceFee + $totalManagementCashin;

    return [
        'period' => 'Last 6 Months',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
        'monthly_data' => $monthlyData,
        'summary' => [
            // New keys
            'total_agreement' => $totalAgreement,
            'total_service_fee' => $totalServiceFee,
            'total_management_cashin' => $totalManagementCashin,
            'total_cashin' => $totalCashin,
            'total_revenue' => $totalRevenue,
            'total_cash_out' => $totalCashOut,
            'net_balance' => $totalCashin - $totalCashOut,
            'final_balance' => $runningBalance,
            // Backward compatibility
            'total_landshare' => $totalAgreement
        ]
    ];
}

    /**
     * Get yearly statement (monthly breakdown for a year)
     */
/**
 * Get yearly statement (monthly breakdown for a year)
 */
public function getYearlyStatement($year, $projectId = null, $category = 'all')
{
    // Convert empty string to null
    if ($projectId === '') {
        $projectId = null;
    }
    
    $monthlyData = [];
    $startDate = "$year-01-01";

    // Get opening balance before the year
    $runningBalance = $this->getOpeningBalance($startDate, $projectId, $category);
    $totalAgreement = 0;
    $totalServiceFee = 0;
    $totalManagementCashin = 0;
    $totalCashOut = 0;

    for ($month = 1; $month <= 12; $month++) {
        $monthStart = sprintf("%s-%02d-01", $year, $month);
        $monthEnd = date('Y-m-t', strtotime($monthStart));

        $agreement = $this->getMonthlyAgreementValue($monthStart, $monthEnd, $projectId);
        $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
        $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
        
        // Only get cashout for expense categories, not for revenue categories
        $cashOut = 0;
        if (in_array($category, ['all', 'cashout', 'utilities', 'other'])) {
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category);
        }
        
        $cashin = $agreement + $serviceFee + $managementCashin;
        $netAmount = $cashin - $cashOut;
        $runningBalance += $netAmount;

        // Accumulate totals
        $totalAgreement += $agreement;
        $totalServiceFee += $serviceFee;
        $totalManagementCashin += $managementCashin;
        $totalCashOut += $cashOut;

        $monthlyData[] = [
            'month' => date('F Y', strtotime($monthStart)),
            'year' => $year,
            'month_num' => $month,
            // Backward compatibility
            'landshare' => $agreement,
            'total_revenue' => $cashin,
            // New keys
            'agreement_value' => $agreement,
            'service_fee' => $serviceFee,
            'management_cashin' => $managementCashin,
            'total_cashin' => $cashin,
            'cash_out' => $cashOut,
            'net_amount' => $netAmount,
            'running_balance' => $runningBalance
        ];
    }

    $totalRevenue = $totalAgreement + $totalServiceFee;
    $totalCashin = $totalAgreement + $totalServiceFee + $totalManagementCashin;

    return [
        'period' => "Year $year",
        'start_date' => "$year-01-01",
        'end_date' => "$year-12-31",
        'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
        'monthly_data' => $monthlyData,
        'summary' => [
            // New keys
            'total_agreement' => $totalAgreement,
            'total_service_fee' => $totalServiceFee,
            'total_management_cashin' => $totalManagementCashin,
            'total_cashin' => $totalCashin,
            'total_revenue' => $totalRevenue,
            'total_cash_out' => $totalCashOut,
            'net_balance' => $totalCashin - $totalCashOut,
            'final_balance' => $runningBalance,
            // Backward compatibility
            'total_landshare' => $totalAgreement
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
    private function getDailyManagementCashin($date)
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashin
            WHERE DATE(transaction_date) = ?
            AND payment_status = 'paid'
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
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
/**
 * Get custom monthly statement
 */
private function getCustomMonthlyStatement($startDate, $endDate, $projectId = null, $category = 'all')
{
    // Convert empty string to null
    if ($projectId === '') {
        $projectId = null;
    }
    
    $monthlyData = [];

    $start = strtotime(date('Y-m-01', strtotime($startDate)));
    $end = strtotime(date('Y-m-t', strtotime($endDate)));

    // Get opening balance before the period
    $runningBalance = $this->getOpeningBalance(date('Y-m-01', strtotime($startDate)), $projectId, $category);
    $totalAgreement = 0;
    $totalServiceFee = 0;
    $totalManagementCashin = 0;
    $totalCashOut = 0;

    $current = $start;
    while ($current <= $end) {
        $monthStart = date('Y-m-01', $current);
        $monthEnd = date('Y-m-t', $current);

        $agreement = $this->getMonthlyAgreementValue($monthStart, $monthEnd, $projectId);
        $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
        $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
        
        // Only get cashout for expense categories, not for revenue categories
        $cashOut = 0;
        if (in_array($category, ['all', 'cashout', 'utilities', 'other'])) {
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category);
        }
        
        $cashin = $agreement + $serviceFee + $managementCashin;
        $netAmount = $cashin - $cashOut;
        $runningBalance += $netAmount;

        // Accumulate totals
        $totalAgreement += $agreement;
        $totalServiceFee += $serviceFee;
        $totalManagementCashin += $managementCashin;
        $totalCashOut += $cashOut;

        $monthlyData[] = [
            'month' => date('F Y', $current),
            'year' => date('Y', $current),
            'month_num' => date('m', $current),
            // Backward compatibility
            'landshare' => $agreement,
            'total_revenue' => $cashin,
            // New keys
            'agreement_value' => $agreement,
            'service_fee' => $serviceFee,
            'management_cashin' => $managementCashin,
            'total_cashin' => $cashin,
            'cash_out' => $cashOut,
            'net_amount' => $netAmount,
            'running_balance' => $runningBalance
        ];

        $current = strtotime('+1 month', $current);
    }

    $totalRevenue = $totalAgreement + $totalServiceFee;
    $totalCashin = $totalAgreement + $totalServiceFee + $totalManagementCashin;

    return [
        'period' => 'Custom Range (Monthly)',
        'start_date' => $startDate,
        'end_date' => $endDate,
        'opening_balance' => $this->getOpeningBalance(date('Y-m-01', strtotime($startDate)), $projectId, $category),
        'monthly_data' => $monthlyData,
        'summary' => [
            // New keys
            'total_agreement' => $totalAgreement,
            'total_service_fee' => $totalServiceFee,
            'total_management_cashin' => $totalManagementCashin,
            'total_cashin' => $totalCashin,
            'total_revenue' => $totalRevenue,
            'total_cash_out' => $totalCashOut,
            'net_balance' => $totalCashin - $totalCashOut,
            'final_balance' => $runningBalance,
            // Backward compatibility
            'total_landshare' => $totalAgreement
        ]
    ];
}

    /**
     * Helper: Get opening balance before a specific date
     */
    private function getOpeningBalance($startDate, $projectId = null, $category = 'all')
    {
        // Get all agreement values before start date
        $agreementQuery = "
                   SELECT COALESCE(SUM(agreement_value), 0) as total
                   FROM lead_bookings
                   WHERE DATE(booking_date) < ? 
                   AND (booking_status = 'booked' OR booking_status = '')
                   AND landshare_status = 'active'
               ";

        // Get all service fee before start date from emi_schedules
        $serviceFeeQuery = "
            SELECT COALESCE(SUM(service_fee), 0) as total
            FROM emi_schedules
            WHERE DATE(due_date) < ? 
            AND status = 'paid'
        ";

        // Get all management cashin before start date
        $cashinQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashin
            WHERE DATE(transaction_date) < ? 
            AND payment_status = 'paid'
        ";

        // Get all cash-out before start date
        $cashOutQuery = $this->getOpeningCashOutQuery($category, $startDate);

        // Add project filter if specified
        if ($projectId) {
            $agreementQuery .= " AND project_id = ?";
            // For service fee with project filter
            $serviceFeeQuery = "
                SELECT COALESCE(SUM(es.service_fee), 0) as total
                FROM emi_schedules es
                LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE DATE(es.due_date) < ?
                AND es.status = 'paid'
                AND lb.project_id = ?
            ";
        }

        $totalAgreement = 0;
        $stmt = $this->db->prepare($agreementQuery);
        if ($projectId) {
            $stmt->bind_param("si", $startDate, $projectId);
        } else {
            $stmt->bind_param("s", $startDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalAgreement = (float)$row['total'];
        }

        $totalServiceFee = 0;
        $stmt = $this->db->prepare($serviceFeeQuery);
        if ($projectId) {
            $stmt->bind_param("si", $startDate, $projectId);
        } else {
            $stmt->bind_param("s", $startDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalServiceFee = (float)$row['total'];
        }

        $totalManagementCashin = 0;
        $stmt = $this->db->prepare($cashinQuery);
        $stmt->bind_param("s", $startDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalManagementCashin = (float)$row['total'];
        }

        $totalCashOut = 0;
        $stmt = $this->db->prepare($cashOutQuery);

        if ($category === 'utilities') {
            $stmt->bind_param("s", $startDate);
        } else {
            $stmt->bind_param("ss", $startDate, $startDate);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalCashOut = (float)$row['total'];
        }

        $openingBalance = ($totalAgreement + $totalServiceFee + $totalManagementCashin) - $totalCashOut;

        return $openingBalance;
    }

    /**
     * Helper: Get opening cashout query based on category
     */
    private function getOpeningCashOutQuery($category, $startDate)
    {
        if ($category === 'utilities') {
            $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

            $keywordConditions = [];
            foreach ($utilityKeywords as $keyword) {
                $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
            }
            $keywordCondition = implode(' OR ', $keywordConditions);

            return "
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM (
                    SELECT cp.amount as total_amount
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE DATE(cp.payment_date) < ?
                    AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    AND ($keywordCondition)
                ) combined
            ";
        }

        return "
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
        if ($projectId) {
            $query = "
                SELECT COALESCE(SUM(es.service_fee), 0) as total
                FROM emi_schedules es
                LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE DATE(es.due_date) = ?
                AND es.status = 'paid'
                AND lb.project_id = ?
            ";
        } else {
            $query = "
                SELECT COALESCE(SUM(service_fee), 0) as total
                FROM emi_schedules
                WHERE DATE(due_date) = ?
                AND status = 'paid'
            ";
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
    private function getDailyAgreementValue($date, $projectId = null)
    {
        $query = "
                   SELECT COALESCE(SUM(agreement_value), 0) as total
                   FROM lead_bookings
                   WHERE DATE(booking_date) = ? 
                   AND (booking_status = 'booked' OR booking_status = '')
                   AND landshare_status = 'active'
          ";

        if ($projectId) {
            $query .= " AND project_id = ?";
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
     * Helper: Get daily cash-out total
     */
    private function getDailyCashOut($date, $category = 'all')
    {
        if ($category === 'utilities') {
            return $this->getDailyUtilitiesCashOut($date);
        }

        // Get category ID if a specific category is selected (for management_cashout)
        $categoryId = null;
        if ($category !== 'all' && $category !== 'cashout') {
            $categoryId = $this->getCategoryIdByName($category);
        }

        // Query for other categories
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
                
                -- Cashout request payments (only for 'cashout' or 'all' categories)
                " . (($category === 'all' || $category === 'cashout') ? "
                SELECT cp.amount as total_amount
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE DATE(cp.payment_date) = ?
                AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
                " : "SELECT 0 as total_amount") . "
                
                UNION ALL
                
                -- Salary payments (payslips) - only for 'all' category
                " . ($category === 'all' ? "
                SELECT net_salary as total_amount
                FROM payslips
                WHERE DATE(updated_at) = ?
                AND status = 'paid'
                " : "SELECT 0 as total_amount") . "
            ) combined
        ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
        }

        // Bind parameters based on category
        if ($categoryId) {
            // With category: date, categoryId, date, date
            $stmt->bind_param("siss", $date, $categoryId, $date, $date);
        } else if ($category === 'all') {
            // Without category: date, date, date
            $stmt->bind_param("sss", $date, $date, $date);
        } else if ($category === 'cashout') {
            // Cashout only: date, date
            $stmt->bind_param("ss", $date, $date);
        } else {
            // Other management categories: date, categoryId
            $stmt->bind_param("si", $date, $categoryId);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get daily utilities cashout from cashout_requests
     */
    private function getDailyUtilitiesCashOut($date)
    {
        $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

        $keywordConditions = [];
        foreach ($utilityKeywords as $keyword) {
            $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
        }
        $keywordCondition = implode(' OR ', $keywordConditions);

        $query = "
            SELECT COALESCE(SUM(cp.amount), 0) as total
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE DATE(cp.payment_date) = ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            AND ($keywordCondition)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $date);
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
        if ($projectId) {
            $query = "
                SELECT COALESCE(SUM(es.service_fee), 0) as total
                FROM emi_schedules es
                LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE DATE(es.due_date) BETWEEN ? AND ?
                AND es.status = 'paid'
                AND lb.project_id = ?
            ";
        } else {
            $query = "
                SELECT COALESCE(SUM(service_fee), 0) as total
                FROM emi_schedules
                WHERE DATE(due_date) BETWEEN ? AND ?
                AND status = 'paid'
            ";
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
     * Helper: Get monthly cash-out total
     */
    private function getMonthlyManagementCashin($startDate, $endDate)
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashin
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }
    /**
     * Helper: Get monthly agreement value total
     */
    private function getMonthlyAgreementValue($startDate, $endDate, $projectId = null)
    {
        $query = "
        SELECT COALESCE(SUM(agreement_value), 0) as total
        FROM lead_bookings
        WHERE DATE(booking_date) BETWEEN ? AND ? 
        AND (booking_status = 'booked' OR booking_status = '')
        AND landshare_status = 'active'
    ";

        if ($projectId) {
            $query .= " AND project_id = ?";
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
     * Helper: Get monthly cash-out total
     */
private function getMonthlyCashOut($startDate, $endDate, $category = 'all', $projectId = null)
{
    // Convert empty string to null
    if ($projectId === '') {
        $projectId = null;
    }
    
    if ($category === 'utilities') {
        return $this->getMonthlyUtilitiesCashOut($startDate, $endDate);
    }
    
    // Get category ID if a specific category is selected (for management_cashout)
    $categoryId = null;
    if ($category !== 'all' && $category !== 'cashout') {
        $categoryId = $this->getCategoryIdByName($category);
    }
    
    // Query for other categories
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
            
            -- Cashout request payments (only for 'cashout' or 'all' categories)
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT cp.amount as total_amount
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE DATE(cp.payment_date) BETWEEN ? AND ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Salary payments (payslips) - only for 'all' category
            " . ($category === 'all' ? "
            SELECT net_salary as total_amount
            FROM payslips
            WHERE DATE(updated_at) BETWEEN ? AND ?
            AND status = 'paid'
            " : "SELECT 0 as total_amount") . "
        ) combined
    ";

    $stmt = $this->db->prepare($query);
    if (!$stmt) {
        throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
    }

    // Bind parameters based on category
    if ($categoryId) {
        // With category: start, end, categoryId, start, end, start, end
        $params = [$startDate, $endDate, $categoryId];
        $types = "ssi";
        
        if ($category === 'all' || $category === 'cashout') {
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }
        
        if ($category === 'all') {
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }
        
        $stmt->bind_param($types, ...$params);
    } else if ($category === 'all') {
        // Without category: start, end, start, end, start, end
        $stmt->bind_param("ssssss", $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
    } else if ($category === 'cashout') {
        // Cashout only: start, end, start, end
        $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
    } else {
        // Other management categories: start, end, categoryId
        // But landshare, service_fee don't have category IDs, so just return 0
        return 0;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return (float)($row['total'] ?? 0);
}

    /**
     * Helper: Get monthly utilities cashout from cashout_requests
     */
    private function getMonthlyUtilitiesCashOut($startDate, $endDate)
    {
        $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

        $keywordConditions = [];
        foreach ($utilityKeywords as $keyword) {
            $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
        }
        $keywordCondition = implode(' OR ', $keywordConditions);

        $query = "
            SELECT COALESCE(SUM(cp.amount), 0) as total
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE DATE(cp.payment_date) BETWEEN ? AND ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            AND ($keywordCondition)
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (float)($row['total'] ?? 0);
    }

    /**
     * Get overall statistics with period filter
     */
    public function getOverallStatistics($periodType = 'all_time', $year = null, $month = null, $startDate = null, $endDate = null, $projectId = null, $category = 'all')
    {
        // Build date conditions based on period type
        $dateConditions = $this->buildDateConditions($periodType, $year, $month, $startDate, $endDate);

        // 1. Get agreement value data (from lead_bookings)
        $agreementQuery = "
    SELECT COALESCE(SUM(agreement_value), 0) as total
    FROM lead_bookings
    WHERE (booking_status = 'booked' OR booking_status = '')
    AND landshare_status = 'active'
    {$dateConditions['agreement']}
";

        $agreementParams = $dateConditions['params'] ?? [];
        $agreementTypes = $dateConditions['types'] ?? "";

        if ($projectId) {
            $agreementQuery .= " AND project_id = ?";
            $agreementParams[] = $projectId;
            $agreementTypes .= "i";
        }

        $stmt = $this->db->prepare($agreementQuery);
        if (!empty($agreementParams)) {
            $stmt->bind_param($agreementTypes, ...$agreementParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $totalAgreement = 0;
        if ($row = $result->fetch_assoc()) {
            $totalAgreement = (float)$row['total'];
        }

        // 2. Get service fee from emi_schedules
        if ($projectId) {
            $serviceFeeQuery = "
                SELECT COALESCE(SUM(es.service_fee), 0) as total
                FROM emi_schedules es
                LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE es.status = 'paid'
                {$dateConditions['emi']}
                AND lb.project_id = ?
            ";
        } else {
            $serviceFeeQuery = "
                SELECT COALESCE(SUM(service_fee), 0) as total
                FROM emi_schedules
                WHERE status = 'paid'
                {$dateConditions['emi']}
            ";
        }

        $serviceFeeParams = $dateConditions['params'] ?? [];
        $serviceFeeTypes = $dateConditions['types'] ?? "";

        if ($projectId) {
            $serviceFeeParams[] = $projectId;
            $serviceFeeTypes .= "i";
        }

        $stmt = $this->db->prepare($serviceFeeQuery);
        if (!empty($serviceFeeParams)) {
            $stmt->bind_param($serviceFeeTypes, ...$serviceFeeParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $totalServiceFee = 0;
        if ($row = $result->fetch_assoc()) {
            $totalServiceFee = (float)$row['total'];
        }

        // 3. Get management cashin
        $cashinQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashin
            WHERE payment_status = 'paid'
            {$dateConditions['management_cashin']}
        ";

        $cashinParams = $dateConditions['params'] ?? [];
        $cashinTypes = $dateConditions['types'] ?? "";

        $stmt = $this->db->prepare($cashinQuery);
        if (!empty($cashinParams)) {
            $stmt->bind_param($cashinTypes, ...$cashinParams);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $totalManagementCashin = 0;
        if ($row = $result->fetch_assoc()) {
            $totalManagementCashin = (float)$row['total'];
        }

        // 4. Get cash-out data
        $cashOutData = $this->getCashOutForStatistics($periodType, $year, $month, $startDate, $endDate, $category, $projectId);
        $totalCashOut = $cashOutData['total'] ?? 0;

        // Calculate totals
        $totalRevenue = $totalAgreement + $totalServiceFee; // Business revenue
        $totalCashin = $totalAgreement + $totalServiceFee + $totalManagementCashin; // Total cash inflow

        return [
            'total_agreement' => $totalAgreement,
            'total_service_fee' => $totalServiceFee,
            'total_management_cashin' => $totalManagementCashin,
            'total_revenue' => $totalRevenue,
            'total_cashin' => $totalCashin,
            'total_cash_out' => $totalCashOut,
            'net_balance' => $totalCashin - $totalCashOut
        ];
    }

    /**
     * Helper: Build date conditions for queries
     */
    private function buildDateConditions($periodType, $year, $month, $startDate, $endDate)
    {
        $conditions = [
            'agreement' => '',
            'emi' => '',
            'management_cashin' => '',
            'management_cashout' => '',
            'cashout_request' => '',
            'payslips' => '',
            'params' => [],
            'types' => ''
        ];

        if ($periodType === 'all_time') {
            return $conditions;
        }

        if ($periodType === 'monthly' && $year && $month) {
            $startDate = "$year-$month-01";
            $endDate = date('Y-m-t', strtotime($startDate));

            $conditions['agreement'] = " AND DATE(booking_date) BETWEEN ? AND ?";
            $conditions['emi'] = " AND DATE(due_date) BETWEEN ? AND ?";
            $conditions['management_cashin'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
            $conditions['management_cashout'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
            $conditions['cashout_request'] = " AND DATE(cp.payment_date) BETWEEN ? AND ?";

            $conditions['params'] = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $conditions['types'] = "ssssss";
        } elseif ($periodType === 'yearly' && $year) {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";

            $conditions['agreement'] = " AND DATE(booking_date) BETWEEN ? AND ?";
            $conditions['emi'] = " AND DATE(due_date) BETWEEN ? AND ?";
            $conditions['management_cashin'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
            $conditions['management_cashout'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
            $conditions['cashout_request'] = " AND DATE(cp.payment_date) BETWEEN ? AND ?";

            $conditions['params'] = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $conditions['types'] = "ssssss";
        } elseif ($periodType === 'custom' && $startDate && $endDate) {
            $conditions['agreement'] = " AND DATE(booking_date) BETWEEN ? AND ?";
            $conditions['emi'] = " AND DATE(due_date) BETWEEN ? AND ?";
            $conditions['management_cashin'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
            $conditions['management_cashout'] = " AND DATE(transaction_date) BETWEEN ? AND ?";
            $conditions['cashout_request'] = " AND DATE(cp.payment_date) BETWEEN ? AND ?";

            $conditions['params'] = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $conditions['types'] = "ssssss";
        }

        return $conditions;
    }

    /**
     * Helper: Get cash-out total for statistics with period & category filters
     */
    private function getCashOutForStatistics($periodType = 'all_time', $year = null, $month = null, $startDate = null, $endDate = null, $category = 'all', $projectId = null)
    {
        // Build date conditions based on period type
        $dateConditions = $this->buildDateConditions($periodType, $year, $month, $startDate, $endDate);

        // Get category ID if a specific category is selected (for management_cashout)
        $categoryId = null;
        if ($category !== 'all' && $category !== 'cashout' && $category !== 'utilities') {
            $categoryId = $this->getCategoryIdByName($category);
        }

        // For utilities category, we need a special query
        if ($category === 'utilities') {
            $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

            $keywordConditions = [];
            foreach ($utilityKeywords as $keyword) {
                $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
            }
            $keywordCondition = implode(' OR ', $keywordConditions);

            $query = "
                SELECT COALESCE(SUM(cp.amount), 0) as total
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                AND ($keywordCondition)
                {$dateConditions['cashout_request']}
            ";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
            }

            $params = [];
            $types = "";

            // Cashout request dates
            if (!empty($dateConditions['cashout_request_params'])) {
                $params = array_merge($params, $dateConditions['cashout_request_params']);
                $types .= $dateConditions['cashout_request_types'];
            }

            // Bind parameters if any
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
        } else {
            // For other categories
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
                    
                    -- Cashout request payments (only for 'cashout' or 'all' categories)
                    " . (($category === 'all' || $category === 'cashout') ? "
                    SELECT cp.amount as total_amount
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    {$dateConditions['cashout_request']}
                    " : "SELECT 0 as total_amount") . "
                    
                    UNION ALL
                    
                    -- Salary payments (payslips) - ONLY for 'all' category
                    " . ($category === 'all' ? "
                    SELECT net_salary as total_amount
                    FROM payslips
                    WHERE status = 'paid'
                    {$dateConditions['payslips']}
                    " : "SELECT 0 as total_amount") . "
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

            // Cashout request dates (only for 'all' or 'cashout' categories)
            if (($category === 'all' || $category === 'cashout') && !empty($dateConditions['cashout_request_params'])) {
                $params = array_merge($params, $dateConditions['cashout_request_params']);
                $types .= $dateConditions['cashout_request_types'];
            }

            // Payslips dates (only for 'all' category)
            if ($category === 'all' && !empty($dateConditions['payslips_params'])) {
                $params = array_merge($params, $dateConditions['payslips_params']);
                $types .= $dateConditions['payslips_types'];
            }

            // Bind parameters if any
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
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
            'service_fee' => null,
            'utilities' => 1,
            'other' => null
        ];

        return $categoryMap[$categoryName] ?? null;
    }

    /**
     * Get detailed utilities transactions for view
     */
    public function getUtilitiesTransactions($startDate, $endDate)
    {
        $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

        $keywordConditions = [];
        foreach ($utilityKeywords as $keyword) {
            $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
        }
        $keywordCondition = implode(' OR ', $keywordConditions);

        $query = "
            SELECT 
                cp.id,
                cp.cashout_request_id,
                cp.amount,
                cp.payment_date,
                cp.payment_method,
                cp.transaction_ref,
                cr.purpose_paid_to,
                cr.description,
                u.full_name as requester_name
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            LEFT JOIN users u ON cr.requested_by = u.id
            WHERE DATE(cp.payment_date) BETWEEN ? AND ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            AND ($keywordCondition)
            ORDER BY cp.payment_date DESC
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        return $transactions;
    }
}
