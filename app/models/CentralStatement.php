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
     * Shows each day's cash-in, cash-out, and running balance
     */
    public function getMonthlyStatement($year, $month)
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
        $openingBalance = $this->getOpeningBalance($startDate);

        // Get daily cash-in totals (from lead_payments - clients)
        $cashInQuery = "
            SELECT 
                DATE(payment_date) as transaction_date,
                SUM(amount) as total_amount
            FROM lead_payments
            WHERE DATE(payment_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
            GROUP BY DATE(payment_date)
        ";

        // Get daily cash-in totals (from management_cashin)
        $managementCashInQuery = "
            SELECT 
                DATE(transaction_date) as transaction_date,
                SUM(amount) as total_amount
            FROM management_cashin
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
            GROUP BY DATE(transaction_date)
        ";

        // Get daily cash-out totals
        $cashOutQuery = "
            SELECT 
                DATE(transaction_date) as transaction_date,
                SUM(amount) as total_amount
            FROM management_cashout
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
            GROUP BY DATE(transaction_date)
        ";

        // Execute queries and build data arrays
        $cashInStmt = $this->db->prepare($cashInQuery);
        $cashInStmt->bind_param("ss", $startDate, $endDate);
        $cashInStmt->execute();
        $cashInResult = $cashInStmt->get_result();
        $cashInData = [];
        while ($row = $cashInResult->fetch_assoc()) {
            $cashInData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        $mgmtCashInStmt = $this->db->prepare($managementCashInQuery);
        $mgmtCashInStmt->bind_param("ss", $startDate, $endDate);
        $mgmtCashInStmt->execute();
        $mgmtCashInResult = $mgmtCashInStmt->get_result();
        while ($row = $mgmtCashInResult->fetch_assoc()) {
            $date = $row['transaction_date'];
            $cashInData[$date] = ($cashInData[$date] ?? 0) + (float)$row['total_amount'];
        }

        $cashOutStmt = $this->db->prepare($cashOutQuery);
        $cashOutStmt->bind_param("ss", $startDate, $endDate);
        $cashOutStmt->execute();
        $cashOutResult = $cashOutStmt->get_result();
        $cashOutData = [];
        while ($row = $cashOutResult->fetch_assoc()) {
            $cashOutData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        // Build daily statement
        $dailyStatement = [];
        $runningBalance = $openingBalance; // Start with opening balance
        $totalCashIn = 0;
        $totalCashOut = 0;

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayPadded = sprintf("%02d", $day);
            $date = "$year-$monthPadded-$dayPadded";
            $cashIn = $cashInData[$date] ?? 0;
            $cashOut = $cashOutData[$date] ?? 0;
            $netAmount = $cashIn - $cashOut;
            $runningBalance += $netAmount;
            
            // Accumulate totals
            $totalCashIn += $cashIn;
            $totalCashOut += $cashOut;

            $dailyStatement[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', strtotime($date)),
                'day_name' => date('l', strtotime($date)),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        return [
            'period' => date('F Y', strtotime($startDate)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'daily_data' => $dailyStatement,
            'summary' => [
                'total_cash_in' => $totalCashIn,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalCashIn - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Get 6-month statement (monthly breakdown)
     */
    public function getSixMonthStatement($endYear, $endMonth)
    {
        $monthlyData = [];
        
        // Calculate 6 months back
        $startDate = date('Y-m-01', strtotime("-5 months", strtotime("$endYear-$endMonth-01")));
        $endDate = date('Y-m-t', strtotime("$endYear-$endMonth-01"));
        
        // Get opening balance before the 6-month period
        $runningBalance = $this->getOpeningBalance($startDate);
        $totalCashIn = 0;
        $totalCashOut = 0;

        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-$i months", strtotime("$endYear-$endMonth-01")));
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            $monthStart = "$year-$month-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            // Get monthly totals
            $cashIn = $this->getMonthlyCashIn($monthStart, $monthEnd);
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd);
            $netAmount = $cashIn - $cashOut;
            $runningBalance += $netAmount;
            
            // Accumulate totals
            $totalCashIn += $cashIn;
            $totalCashOut += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', strtotime($date)),
                'year' => $year,
                'month_num' => $month,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        return [
            'period' => 'Last 6 Months',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance($startDate),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_cash_in' => $totalCashIn,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalCashIn - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Get yearly statement (monthly breakdown for a year)
     */
    public function getYearlyStatement($year)
    {
        $monthlyData = [];
        $startDate = "$year-01-01";
        
        // Get opening balance before the year
        $runningBalance = $this->getOpeningBalance($startDate);
        $totalCashIn = 0;
        $totalCashOut = 0;

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = sprintf("%s-%02d-01", $year, $month);
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            $cashIn = $this->getMonthlyCashIn($monthStart, $monthEnd);
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd);
            $netAmount = $cashIn - $cashOut;
            $runningBalance += $netAmount;
            
            // Accumulate totals
            $totalCashIn += $cashIn;
            $totalCashOut += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', strtotime($monthStart)),
                'year' => $year,
                'month_num' => $month,
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        return [
            'period' => "Year $year",
            'start_date' => "$year-01-01",
            'end_date' => "$year-12-31",
            'opening_balance' => $this->getOpeningBalance($startDate),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_cash_in' => $totalCashIn,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalCashIn - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Get custom range statement (daily if <= 31 days, monthly if > 31 days)
     */
    public function getCustomRangeStatement($startDate, $endDate)
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $daysDiff = ($end - $start) / (60 * 60 * 24);

        if ($daysDiff <= 31) {
            return $this->getCustomDailyStatement($startDate, $endDate);
        } else {
            return $this->getCustomMonthlyStatement($startDate, $endDate);
        }
    }

    /**
     * Get custom daily statement
     */
    private function getCustomDailyStatement($startDate, $endDate)
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $dailyData = [];
        
        // Get opening balance before the period
        $runningBalance = $this->getOpeningBalance($startDate);
        $totalCashIn = 0;
        $totalCashOut = 0;

        // Get data for each day
        for ($current = $start; $current <= $end; $current += 86400) {
            $date = date('Y-m-d', $current);
            $cashIn = $this->getDailyCashIn($date);
            $cashOut = $this->getDailyCashOut($date);
            $netAmount = $cashIn - $cashOut;
            $runningBalance += $netAmount;
            
            // Accumulate totals
            $totalCashIn += $cashIn;
            $totalCashOut += $cashOut;

            $dailyData[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', strtotime($date)),
                'day_name' => date('l', strtotime($date)),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];
        }

        return [
            'period' => 'Custom Range (Daily)',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance($startDate),
            'daily_data' => $dailyData,
            'summary' => [
                'total_cash_in' => $totalCashIn,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalCashIn - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Get custom monthly statement
     */
    private function getCustomMonthlyStatement($startDate, $endDate)
    {
        $monthlyData = [];
        
        $start = strtotime(date('Y-m-01', strtotime($startDate)));
        $end = strtotime(date('Y-m-t', strtotime($endDate)));
        
        // Get opening balance before the period
        $runningBalance = $this->getOpeningBalance(date('Y-m-01', strtotime($startDate)));
        $totalCashIn = 0;
        $totalCashOut = 0;

        $current = $start;
        while ($current <= $end) {
            $monthStart = date('Y-m-01', $current);
            $monthEnd = date('Y-m-t', $current);

            $cashIn = $this->getMonthlyCashIn($monthStart, $monthEnd);
            $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd);
            $netAmount = $cashIn - $cashOut;
            $runningBalance += $netAmount;
            
            // Accumulate totals
            $totalCashIn += $cashIn;
            $totalCashOut += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', $current),
                'year' => date('Y', $current),
                'month_num' => date('m', $current),
                'cash_in' => $cashIn,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance
            ];

            $current = strtotime('+1 month', $current);
        }

        return [
            'period' => 'Custom Range (Monthly)',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance(date('Y-m-01', strtotime($startDate))),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_cash_in' => $totalCashIn,
                'total_cash_out' => $totalCashOut,
                'net_balance' => $totalCashIn - $totalCashOut,
                'final_balance' => $runningBalance
            ]
        ];
    }

    /**
     * Helper: Get opening balance before a specific date
     */
    private function getOpeningBalance($startDate)
    {
        // Get all cash-in before start date
        $cashInQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount FROM lead_payments 
                WHERE DATE(payment_date) < ? AND payment_status = 'paid'
                UNION ALL
                SELECT amount FROM management_cashin 
                WHERE DATE(transaction_date) < ? AND payment_status = 'paid'
            ) as combined
        ";
        
        // Get all cash-out before start date
        $cashOutQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE DATE(transaction_date) < ? AND payment_status = 'paid'
        ";
        
        $totalCashIn = 0;
        $stmt = $this->db->prepare($cashInQuery);
        $stmt->bind_param("ss", $startDate, $startDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalCashIn = (float)$row['total'];
        }
        
        $totalCashOut = 0;
        $stmt = $this->db->prepare($cashOutQuery);
        $stmt->bind_param("s", $startDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalCashOut = (float)$row['total'];
        }
        
        $openingBalance = $totalCashIn - $totalCashOut;
        
        // Debug log
        error_log("Opening Balance Calculation for $startDate:");
        error_log("Total Cash In before start: " . $totalCashIn);
        error_log("Total Cash Out before start: " . $totalCashOut);
        error_log("Opening Balance: " . $openingBalance);
        
        return $openingBalance;
    }

    /**
     * Helper: Get daily cash-in total
     */
    private function getDailyCashIn($date)
    {
        $query = "
            SELECT 
                COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount FROM lead_payments 
                WHERE DATE(payment_date) = ? AND payment_status = 'paid'
                UNION ALL
                SELECT amount FROM management_cashin 
                WHERE DATE(transaction_date) = ? AND payment_status = 'paid'
            ) as combined
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $date, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get daily cash-out total
     */
    private function getDailyCashOut($date)
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE DATE(transaction_date) = ? AND payment_status = 'paid'
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get monthly cash-in total
     */
    private function getMonthlyCashIn($startDate, $endDate)
    {
        $query = "
            SELECT 
                COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount FROM lead_payments 
                WHERE DATE(payment_date) BETWEEN ? AND ? AND payment_status = 'paid'
                UNION ALL
                SELECT amount FROM management_cashin 
                WHERE DATE(transaction_date) BETWEEN ? AND ? AND payment_status = 'paid'
            ) as combined
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssss", $startDate, $endDate, $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get monthly cash-out total
     */
    private function getMonthlyCashOut($startDate, $endDate)
    {
        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE DATE(transaction_date) BETWEEN ? AND ? AND payment_status = 'paid'
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get overall statistics
     */
    public function getOverallStatistics()
    {
        // Total cash-in (all time)
        $cashInQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount FROM lead_payments WHERE payment_status = 'paid'
                UNION ALL
                SELECT amount FROM management_cashin WHERE payment_status = 'paid'
            ) as combined
        ";

        // Total cash-out (all time)
        $cashOutQuery = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE payment_status = 'paid'
        ";

        $totalCashIn = 0;
        $result = $this->db->query($cashInQuery);
        if ($result && $row = $result->fetch_assoc()) {
            $totalCashIn = (float)$row['total'];
        }

        $totalCashOut = 0;
        $result = $this->db->query($cashOutQuery);
        if ($result && $row = $result->fetch_assoc()) {
            $totalCashOut = (float)$row['total'];
        }

        return [
            'total_cash_in' => $totalCashIn,
            'total_cash_out' => $totalCashOut,
            'net_balance' => $totalCashIn - $totalCashOut
        ];
    }
}