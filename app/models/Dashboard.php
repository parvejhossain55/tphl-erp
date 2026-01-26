<?php
class Dashboard
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
/**
 * Get Cash In breakdown by payment method
 */
public function getCashInByPaymentMethod()
{
    $sql = "
        SELECT 
            payment_method,
            SUM(amount) as total
        FROM lead_payments
        WHERE payment_status = 'paid'
        GROUP BY payment_method
    ";
    
    $result = $this->db->query($sql);
    
    // Initialize with all payment methods as 0
    $breakdown = [
        'cash' => 0,
        'bank' => 0,
        'cheque' => 0,
        'online' => 0
    ];
    
    // Fill in the actual values
    if ($result) {
        foreach ($result as $row) {
            $method = strtolower($row['payment_method']);
            if (isset($breakdown[$method])) {
                $breakdown[$method] = floatval($row['total']);
            }
        }
    }
    
    return $breakdown;
}
    /**
     * Get overview statistics (Cash In + Cash Out combined)
     */
    public function getOverviewStatistics()
    {
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
                COALESCE((SELECT SUM(amount) FROM management_cashin WHERE payment_status = 'paid'), 0) as management_cashin
            FROM lead_payments lp
            WHERE lp.payment_status = 'paid'
        ";
        $result = $this->db->query($sqlCashIn);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_cashin'] = $row['lead_payments'] + $row['management_cashin'];
        }

        // Total Cash Out
        $sqlCashOut = "SELECT COALESCE(SUM(amount), 0) as total FROM management_cashout WHERE payment_status = 'paid'";
        $result = $this->db->query($sqlCashOut);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_cashout'] = $row['total'];
        }

        // Net Balance
        $stats['net_balance'] = $stats['total_cashin'] - $stats['total_cashout'];

        // Total Clients (unique bookings)
        $sqlClients = "SELECT COUNT(DISTINCT booking_id) as total FROM lead_payments";
        $result = $this->db->query($sqlClients);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_clients'] = $row['total'];
        }

        return $stats;
    }

    /**
     * Get Cash In Statistics
     */
    public function getCashInStatistics()
    {
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

        // Lead Payments breakdown
        $sqlClient = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN payment_type = 'booking_money' AND payment_status = 'paid' THEN amount ELSE 0 END), 0) as booking_money,
                COALESCE(SUM(CASE WHEN payment_type = 'registration' AND payment_status = 'paid' THEN amount ELSE 0 END), 0) as registration_money,
                COALESCE(SUM(CASE WHEN payment_type = 'emi' AND payment_status = 'paid' THEN amount ELSE 0 END), 0) as emi_payments,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COUNT(*) as total_count
            FROM lead_payments
        ";
        $result = $this->db->query($sqlClient);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['lead_payments'] = $row['total_paid'];
            $stats['booking_money'] = $row['booking_money'];
            $stats['registration_money'] = $row['registration_money'];
            $stats['emi_payments'] = $row['emi_payments'];
            $stats['pending_amount'] = $row['pending'];
            $stats['total_transactions'] = $row['total_count'];
        }

        // Management Cash In
        $sqlManagement = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as total,
                COUNT(*) as count
            FROM management_cashin
        ";
        $result = $this->db->query($sqlManagement);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['management_cashin'] = $row['total'];
            $stats['total_transactions'] += $row['count'];
        }

        // This Month
        $sqlMonth = "
            SELECT 
                COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT amount, payment_date as date FROM lead_payments WHERE payment_status = 'paid'
                UNION ALL
                SELECT amount, transaction_date as date FROM management_cashin WHERE payment_status = 'paid'
            ) as combined
            WHERE MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE())
        ";
        $result = $this->db->query($sqlMonth);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = $row['total'];
        }

        // Total Amount
        $stats['total_amount'] = $stats['lead_payments'] + $stats['management_cashin'];

        return $stats;
    }

    /**
     * Get Cash Out Statistics
     */
    public function getCashOutStatistics()
    {
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


    

        // Overall statistics
        $sqlOverall = "
            SELECT 
                COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) as paid,
                COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) as pending,
                COUNT(*) as total_count
            FROM management_cashout
        ";
        $result = $this->db->query($sqlOverall);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['paid_amount'] = $row['paid'];
            $stats['pending_amount'] = $row['pending'];
            $stats['total_amount'] = $row['paid'] + $row['pending'];
            $stats['total_transactions'] = $row['total_count'];
        }

        // This Month
        $sqlMonth = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE MONTH(transaction_date) = MONTH(CURRENT_DATE()) 
            AND YEAR(transaction_date) = YEAR(CURRENT_DATE())
            AND payment_status = 'paid'
        ";
        $result = $this->db->query($sqlMonth);
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = $row['total'];
        }

        // By Payment Method
        $sqlMethod = "
            SELECT 
                payment_method,
                COALESCE(SUM(amount), 0) as total
            FROM management_cashout
            WHERE payment_status = 'paid'
            GROUP BY payment_method
        ";
        $result = $this->db->query($sqlMethod);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $method = $row['payment_method'];
                if (isset($stats['by_payment_method'][$method])) {
                    $stats['by_payment_method'][$method] = $row['total'];
                }
            }
        }

        return $stats;
    }

    /**
     * Get Recent Cash In Transactions
     */
    public function getRecentCashInTransactions($limit = 10)
    {
        $sql = "
            SELECT 
                'client' as source,
                lp.id,
                lp.payment_date as date,
                lp.payment_type as type,
                lp.amount,
                lp.payment_method,
                lp.payment_status,
                CONCAT(l.lead_name, ' - ', p.name) as description
            FROM lead_payments lp
            LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            
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
            
            ORDER BY date DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limit);
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
    public function getRecentCashOutTransactions($limit = 10)
    {
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
            ORDER BY transaction_date DESC, id DESC
            LIMIT ?
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $limit);
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
     * Get Monthly Cash In Data (for charts)
     */
    public function getMonthlyCashInData($months = 6)
    {
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
     * Get Monthly Cash Out Data (for charts)
     */
    public function getMonthlyCashOutData($months = 6)
    {
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
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        
        $stmt->close();
        return $data;
    }
    // Add these methods to your Dashboard.php class

/**
 * Get Weekly Cash In/Out Data
 */
public function getWeeklyTransactionData($viewType = 'cashin')
{
    $startOfWeek = date('Y-m-d', strtotime('monday this week'));
    $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
    
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
 * Get Yearly Transaction Data
 */
public function getYearlyTransactionData($viewType = 'cashin', $years = 3)
{
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
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    return $data;
}
}