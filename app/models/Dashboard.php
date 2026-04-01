<?php
class Dashboard extends Model
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
    public function getCashInByPaymentMethod($period = 'all_time', $projectId = null)
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $sql = "
            SELECT 
                lp.payment_method,
                SUM(lp.amount) as total
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_status = 'paid'
            AND lp.payment_date BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($projectId) {
            $sql .= " AND lb.project_id = ?";
            $params[] = $projectId;
            $types .= "i";
        }

        $sql .= " GROUP BY lp.payment_method";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
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
    public function getOverviewStatistics($period = 'all_time', $projectId = null)
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
            'total_booking_money' => 0,
            'total_emi_amount' => 0,
            'total_service_fees' => 0,
            'management_cashin' => 0,
            'projects_summary' => []
        ];

        // Get land share
        $sqlLandShare = "
        SELECT COALESCE(SUM(lp.land_share_amount), 0) as total
        FROM lead_payments lp
        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE lp.payment_status = 'paid'
        AND lp.payment_type = 'land_share'
        AND lp.payment_date BETWEEN ? AND ?
    ";

        if ($projectId) {
            $sqlLandShare .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlLandShare);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_landshare'] = floatval($row['total']);
        }
        $stmt->close();

        // Get registration
        $sqlRegistration = "
        SELECT COALESCE(SUM(lp.amount), 0) as total
        FROM lead_payments lp
        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE lp.payment_status = 'paid'
        AND lp.payment_type = 'registration'
        AND lp.payment_date BETWEEN ? AND ?
    ";

        if ($projectId) {
            $sqlRegistration .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlRegistration);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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

        if ($projectId) {
            $sqlDiscount .= " AND project_id = ?";
        }

        $stmt = $this->db->prepare($sqlDiscount);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_discount'] = floatval($row['total']);
        }
        $stmt->close();

        // Get booking money
        $sqlBookingMoney = "
        SELECT COALESCE(SUM(lp.amount), 0) as total
        FROM lead_payments lp
        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE lp.payment_status = 'paid'
        AND lp.payment_type = 'booking_money'
        AND lp.payment_date BETWEEN ? AND ?
    ";

        if ($projectId) {
            $sqlBookingMoney .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlBookingMoney);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_booking_money'] = floatval($row['total']);
        }
        $stmt->close();

        // Get EMI amount
        $sqlEmi = "
        SELECT COALESCE(SUM(es.emi_amount), 0) as total
        FROM emi_schedules es
        LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
        WHERE es.due_date BETWEEN ? AND ?
        AND es.status = 'paid'
    ";

        if ($projectId) {
            $sqlEmi .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlEmi);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_emi_amount'] = floatval($row['total']);
        }
        $stmt->close();

        // Get service fees
        $sqlServiceFees = "
        SELECT COALESCE(SUM(es.service_fee), 0) as total
        FROM emi_schedules es
        LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
        WHERE es.due_date BETWEEN ? AND ?
        AND es.status = 'paid'
    ";

        if ($projectId) {
            $sqlServiceFees .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlServiceFees);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_service_fees'] = floatval($row['total']);
        }
        $stmt->close();

        // Get management cashin
        $sqlManagementCashIn = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM management_cashin
        WHERE payment_status = 'paid'
        AND transaction_date BETWEEN ? AND ?
    ";

        $stmt = $this->db->prepare($sqlManagementCashIn);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['management_cashin'] = floatval($row['total']);
        }
        $stmt->close();

        // Calculate Total Cash In
        $stats['total_cashin'] = $stats['total_landshare'] + $stats['total_registration'] +
            $stats['total_booking_money'] + $stats['total_emi_amount'] + $stats['management_cashin'] -
            $stats['total_discount'];

        // Calculate Total Revenue
        $stats['total_revenue'] = $stats['total_landshare'] + $stats['total_booking_money'] + $stats['total_service_fees'];

        // Get Cash Out with project filtering
        $sqlCashOut = "
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM (
            SELECT amount as total_amount, transaction_date as date, NULL as project_id
            FROM management_cashout
            WHERE payment_status = 'paid'
            
            UNION ALL
            
            SELECT cp.amount as total_amount, cp.payment_date as date, cp.project_id
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
            
            UNION ALL
            
            SELECT net_salary as total_amount, 
                   STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date,
                   NULL as project_id
            FROM payslips
            WHERE status = 'paid'
        ) combined
        WHERE date BETWEEN ? AND ?
    ";

        if ($projectId) {
            $sqlCashOut .= " AND (project_id = ? OR project_id IS NULL)";
        }

        $stmt = $this->db->prepare($sqlCashOut);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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
        SELECT COUNT(DISTINCT lp.booking_id) as total 
        FROM lead_payments lp
        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE lp.payment_date BETWEEN ? AND ?
        AND lp.payment_status = 'paid'
    ";

        if ($projectId) {
            $sqlClients .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlClients);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_clients'] = intval($row['total']);
        }
        $stmt->close();

        // Get projects summary (only if no specific project is selected)
        if (!$projectId) {
            $stats['projects_summary'] = $this->getProjectsSummary($startDate, $endDate);
        }

        return $stats;
    }

    /**
     * Get projects summary for dashboard
     */
    private function getProjectsSummary($startDate, $endDate)
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                COALESCE(SUM(lp.amount), 0) as total_revenue,
                COUNT(DISTINCT lp.booking_id) as total_clients
            FROM projects p
            LEFT JOIN lead_bookings lb ON p.id = lb.project_id
            LEFT JOIN lead_payments lp ON lb.id = lp.booking_id AND lp.payment_status = 'paid' 
                AND lp.payment_date BETWEEN ? AND ?
            WHERE p.deleted_at IS NULL
            GROUP BY p.id, p.name
            ORDER BY total_revenue DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'total_revenue' => floatval($row['total_revenue']),
                'total_clients' => intval($row['total_clients'])
            ];
        }

        $stmt->close();
        return $projects;
    }

    /**
     * Get Cash In Statistics
     */
    public function getCashInStatistics($period = 'all_time', $projectId = null)
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
            'total_booking_money' => 0,
            'total_landshare' => 0,
            'total_registration' => 0,
            'total_discount' => 0,
            'total_emi_amount' => 0,
            'by_project' => []
        ];

        // Get all payment types from lead_payments with project filter
        $sqlClient = "
            SELECT 
                COALESCE(SUM(CASE WHEN lp.payment_status = 'paid' THEN lp.amount ELSE 0 END), 0) as all_paid,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'land_share' AND lp.payment_status = 'paid' THEN lp.land_share_amount ELSE 0 END), 0) as land_share_paid,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'booking_money' AND lp.payment_status = 'paid' THEN lp.amount ELSE 0 END), 0) as booking_money,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'registration' AND lp.payment_status = 'paid' THEN lp.amount ELSE 0 END), 0) as registration_money,
                COALESCE(SUM(CASE WHEN lp.payment_type = 'emi' AND lp.payment_status = 'paid' THEN lp.amount ELSE 0 END), 0) as emi_payments,
                COALESCE(SUM(CASE WHEN lp.payment_status = 'pending' THEN lp.amount ELSE 0 END), 0) as pending,
                COUNT(*) as total_count
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_date BETWEEN ? AND ?
        ";

        if ($projectId) {
            $sqlClient .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlClient);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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

        // Get component values for revenue calculation with project filter
        $sqlLandShare = "
            SELECT COALESCE(SUM(lp.land_share_amount), 0) as total_landshare
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_status = 'paid' AND lp.payment_type = 'land_share'
            AND lp.payment_date BETWEEN ? AND ?
        ";

        if ($projectId) {
            $sqlLandShare .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlLandShare);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_landshare'] = floatval($row['total_landshare']);
        }
        $stmt->close();

        $sqlReg = "
            SELECT COALESCE(SUM(lp.amount), 0) as total_registration
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_status = 'paid' AND lp.payment_type = 'registration'
            AND lp.payment_date BETWEEN ? AND ?
        ";

        if ($projectId) {
            $sqlReg .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlReg);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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

        if ($projectId) {
            $sqlDiscount .= " AND project_id = ?";
        }

        $stmt = $this->db->prepare($sqlDiscount);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['total_discount'] = floatval($row['total_discount']);
        }
        $stmt->close();

        $sqlEmiAmount = "
            SELECT COALESCE(SUM(es.emi_amount), 0) as total_emi
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE es.due_date BETWEEN ? AND ?
            AND es.status = 'paid'
        ";

        if ($projectId) {
            $sqlEmiAmount .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlEmiAmount);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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

        // Service fees with project filter
        $sqlServiceFees = "
            SELECT COALESCE(SUM(es.service_fee), 0) as total_fees
            FROM emi_schedules es
            LEFT JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE es.due_date BETWEEN ? AND ?
            AND es.status = 'paid'
        ";

        if ($projectId) {
            $sqlServiceFees .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($sqlServiceFees);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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
                SELECT lp.amount, lp.payment_date as date 
                FROM lead_payments lp
                INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                WHERE lp.payment_status = 'paid'
                " . ($projectId ? " AND lb.project_id = ?" : "") . "
                UNION ALL
                SELECT mci.amount, mci.transaction_date as date 
                FROM management_cashin mci 
                WHERE mci.payment_status = 'paid'
            ) as combined
            WHERE date BETWEEN ? AND ?
        ";

        $stmt = $this->db->prepare($sqlMonth);
        if ($projectId) {
            $stmt->bind_param("isss", $projectId, $currentMonthStart, $currentMonthEnd);
        } else {
            $stmt->bind_param("ss", $currentMonthStart, $currentMonthEnd);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = floatval($row['total']);
        }
        $stmt->close();

        $stats['total_amount'] = $stats['lead_payments'] + $stats['management_cashin'];

        // Calculate total revenue
        $stats['total_revenue'] = $stats['total_landshare'] + $stats['total_booking_money'] + $stats['total_service_fees'];

        // Get cashin by project (only if no specific project is selected)
        if (!$projectId) {
            $stats['by_project'] = $this->getCashInByProject($startDate, $endDate);
        }

        return $stats;
    }

    /**
     * Get cashin by project
     */
    private function getCashInByProject($startDate, $endDate)
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                COALESCE(SUM(lp.amount), 0) as total_cashin,
                COUNT(DISTINCT lp.id) as transaction_count
            FROM projects p
            LEFT JOIN lead_bookings lb ON p.id = lb.project_id
            LEFT JOIN lead_payments lp ON lb.id = lp.booking_id 
                AND lp.payment_status = 'paid'
                AND lp.payment_date BETWEEN ? AND ?
            WHERE p.deleted_at IS NULL
            GROUP BY p.id, p.name
            ORDER BY total_cashin DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'total_cashin' => floatval($row['total_cashin']),
                'transaction_count' => intval($row['transaction_count'])
            ];
        }

        $stmt->close();
        return $projects;
    }

    /**
     * Get Cash Out Statistics with complete payment method breakdown
     */
    public function getCashOutStatistics($period = 'all_time', $projectId = null)
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
            'paid_amount' => 0,
            'by_project' => []
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

        // Cashout Request Payments with project filter
        $sqlCashoutPayments = "
            SELECT 
                COALESCE(SUM(cp.amount), 0) as total,
                COUNT(*) as count
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
            AND cp.payment_date BETWEEN ? AND ?
        ";

        if ($projectId) {
            $sqlCashoutPayments .= " AND (cr.project_id = ? OR cp.project_id = ?)";
        }

        $stmt = $this->db->prepare($sqlCashoutPayments);
        if ($projectId) {
            $stmt->bind_param("ssii", $startDate, $endDate, $projectId, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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

        // This Month with project filter
        $currentMonthStart = date('Y-m-01');
        $currentMonthEnd = date('Y-m-t');
        $sqlMonth = "
            SELECT COALESCE(SUM(total_amount), 0) as total
            FROM (
                SELECT amount as total_amount, transaction_date as date, NULL as project_id
                FROM management_cashout
                WHERE payment_status = 'paid'
                
                UNION ALL
                
                SELECT cp.amount as total_amount, cp.payment_date as date, cp.project_id
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                
                UNION ALL
                
                SELECT net_salary as total_amount, 
                       STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date,
                       NULL as project_id
                FROM payslips
                WHERE status = 'paid'
            ) combined
            WHERE date BETWEEN ? AND ?
        ";

        if ($projectId) {
            $sqlMonth .= " AND (project_id = ? OR project_id IS NULL)";
        }

        $stmt = $this->db->prepare($sqlMonth);
        if ($projectId) {
            $stmt->bind_param("ssi", $currentMonthStart, $currentMonthEnd, $projectId);
        } else {
            $stmt->bind_param("ss", $currentMonthStart, $currentMonthEnd);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stats['this_month'] = floatval($row['total']);
        }
        $stmt->close();

        // Payment Method Breakdown with project filter
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
                SELECT payment_method, amount, transaction_date as date, NULL as project_id
                FROM management_cashout
                WHERE payment_status = 'paid'
                AND transaction_date BETWEEN ? AND ?
                
                UNION ALL
                
                -- Cashout Request Payments
                SELECT cp.payment_method, cp.amount, cp.payment_date as date, cp.project_id
                FROM cashout_payments cp
                LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                AND cp.payment_date BETWEEN ? AND ?
                
                UNION ALL
                
                -- Salary Payments (assume bank transfer)
                SELECT 'Bank' as payment_method, net_salary as amount, 
                       STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date,
                       NULL as project_id
                FROM payslips
                WHERE status = 'paid'
                AND STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') BETWEEN ? AND ?
            ) combined
            WHERE 1=1
        ";

        if ($projectId) {
            $sqlMethod .= " AND (project_id = ? OR project_id IS NULL)";
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $projectId];
            $types = "ssssssi";
        } else {
            $params = [$startDate, $endDate, $startDate, $endDate, $startDate, $endDate];
            $types = "ssssss";
        }

        $sqlMethod .= " GROUP BY payment_method";

        $stmt = $this->db->prepare($sqlMethod);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $method = trim($row['payment_method']);
                $methodLower = strtolower($method);
                $amount = floatval($row['total']);

                if (in_array($methodLower, ['cash', 'cash payment']) || $method === 'Cash') {
                    $stats['by_payment_method']['cash'] += $amount;
                } elseif (in_array($methodLower, ['bank', 'bank transfer', 'bank_transfer']) || $method === 'Bank') {
                    $stats['by_payment_method']['bank'] += $amount;
                } elseif (in_array($methodLower, ['cheque', 'check']) || $method === 'Cheque') {
                    $stats['by_payment_method']['cheque'] += $amount;
                } elseif (in_array($methodLower, ['online', 'online payment', 'mobile banking', 'bkash', 'nagad', 'rocket']) || $method === 'Online') {
                    $stats['by_payment_method']['online'] += $amount;
                } else {
                    $stats['by_payment_method']['cash'] += $amount;
                }
            }
        }
        $stmt->close();

        // Get cashout by project (only if no specific project is selected)
        if (!$projectId) {
            $stats['by_project'] = $this->getCashOutByProject($startDate, $endDate);
        }

        return $stats;
    }

    /**
     * Get cashout by project
     */
    private function getCashOutByProject($startDate, $endDate)
    {
        $sql = "
            SELECT 
                p.id,
                p.name,
                COALESCE(SUM(cp.amount), 0) as total_cashout,
                COUNT(DISTINCT cp.id) as transaction_count
            FROM projects p
            LEFT JOIN cashout_requests cr ON p.id = cr.project_id
            LEFT JOIN cashout_payments cp ON cr.id = cp.cashout_request_id
                AND cp.payment_date BETWEEN ? AND ?
                AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            WHERE p.deleted_at IS NULL
            GROUP BY p.id, p.name
            HAVING total_cashout > 0
            ORDER BY total_cashout DESC
            LIMIT 5
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'total_cashout' => floatval($row['total_cashout']),
                'transaction_count' => intval($row['transaction_count'])
            ];
        }

        $stmt->close();
        return $projects;
    }

    /**
     * Get Recent Cash In Transactions with project filter
     */
    public function getRecentCashInTransactions($limit = 10, $period = 'all_time', $projectId = null)
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $sql = "
SELECT 
    'client' COLLATE utf8mb4_general_ci as source,
    lp.id,
    lp.payment_date as date,
    lp.payment_type COLLATE utf8mb4_general_ci as type,
    lp.amount,
    lp.payment_method COLLATE utf8mb4_general_ci,
    lp.payment_status COLLATE utf8mb4_general_ci,
    CONCAT(l.full_name, ' - ', p.name) COLLATE utf8mb4_general_ci as description,
    p.id as project_id,
    p.name COLLATE utf8mb4_general_ci as project_name
FROM lead_payments lp
LEFT JOIN lead_bookings lb ON lp.booking_id = lb.id
LEFT JOIN leads l ON lb.lead_id = l.id
LEFT JOIN projects p ON lb.project_id = p.id
WHERE lp.payment_date BETWEEN ? AND ?
AND lp.payment_status = 'paid'
";

        if ($projectId) {
            $sql .= " AND lb.project_id = ?";
        }

        $sql .= "
UNION ALL

SELECT 
    'management' COLLATE utf8mb4_general_ci as source,
    mci.id,
    mci.transaction_date as date,
    'management' COLLATE utf8mb4_general_ci as type,
    mci.amount,
    mci.payment_method COLLATE utf8mb4_general_ci,
    mci.payment_status COLLATE utf8mb4_general_ci,
    CONCAT(mci.role_name, ' - ', mci.source) COLLATE utf8mb4_general_ci as description,
    NULL as project_id,
    NULL as project_name
FROM management_cashin mci
WHERE mci.transaction_date BETWEEN ? AND ?

ORDER BY date DESC
LIMIT ?
";

        $stmt = $this->db->prepare($sql);

        if ($projectId) {
            $stmt->bind_param("ssissi", $startDate, $endDate, $projectId, $startDate, $endDate, $limit);
        } else {
            $stmt->bind_param("ssssi", $startDate, $endDate, $startDate, $endDate, $limit);
        }

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
     * Get Recent Cash Out Transactions with project filter
     */
    public function getRecentCashOutTransactions($limit = 10, $period = 'all_time', $projectId = null)
    {
        $dateRange = $this->getDateRangeByPeriod($period);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $sql = "
        SELECT 
            'management' as source,
            mco.id,
            mco.transaction_date as date,
            mco.role_name,
            mco.person_name,
            mco.paid_to,
            mco.amount,
            mco.payment_method,
            mco.payment_status,
            mco.description,
            NULL as project_id,
            NULL as project_name
        FROM management_cashout mco
        WHERE mco.transaction_date BETWEEN ? AND ?
        
        UNION ALL
        
        SELECT 
            'cashout_request' as source,
            cp.id,
            cp.payment_date as date,
            'Cashout Request' as role_name,
            u.full_name as person_name,
            cr.purpose_paid_to as paid_to,
            cp.amount,
            cp.payment_method,
            'paid' as payment_status,
            cr.description,
            cr.project_id,
            p.name as project_name
        FROM cashout_payments cp
        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
        LEFT JOIN users u ON cr.requested_by = u.id
        LEFT JOIN projects p ON cr.project_id = p.id
        WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
        AND cp.payment_date BETWEEN ? AND ?
    ";

        if ($projectId) {
            $sql .= " AND (cr.project_id = ? OR cp.project_id = ?)";
        }

        $sql .= " ORDER BY date DESC LIMIT ?";

        $stmt = $this->db->prepare($sql);

        if ($projectId) {
            $stmt->bind_param("ssssiii", $startDate, $endDate, $startDate, $endDate, $projectId, $projectId, $limit);
        } else {
            $stmt->bind_param("ssssi", $startDate, $endDate, $startDate, $endDate, $limit);
        }

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
     * Get Monthly Cash In Data with project filter
     */
    public function getMonthlyCashInData($months = 6, $period = 'all_time', $projectId = null)
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
                SELECT lp.payment_date as date, lp.amount 
                FROM lead_payments lp
                INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                WHERE lp.payment_status = 'paid'
                " . ($projectId ? " AND lb.project_id = ?" : "") . "
                UNION ALL
                SELECT mci.transaction_date as date, mci.amount 
                FROM management_cashin mci 
                WHERE mci.payment_status = 'paid'
            ) as combined
            WHERE date BETWEEN ? AND ?
            GROUP BY month
            ORDER BY month ASC
        ";

            $stmt = $this->db->prepare($sql);
            if ($projectId) {
                $stmt->bind_param("iss", $projectId, $startDate, $endDate);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
        } else {
            $sql = "
            SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                COALESCE(SUM(amount), 0) as total
            FROM (
                SELECT lp.payment_date as date, lp.amount 
                FROM lead_payments lp
                INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                WHERE lp.payment_status = 'paid'
                " . ($projectId ? " AND lb.project_id = ?" : "") . "
                UNION ALL
                SELECT mci.transaction_date as date, mci.amount 
                FROM management_cashin mci 
                WHERE mci.payment_status = 'paid'
            ) as combined
            WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
            GROUP BY month
            ORDER BY month ASC
        ";

            $stmt = $this->db->prepare($sql);
            if ($projectId) {
                $stmt->bind_param("ii", $projectId, $months);
            } else {
                $stmt->bind_param("i", $months);
            }
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
     * Get Monthly Cash Out Data with project filter
     */
    public function getMonthlyCashOutData($months = 6, $period = 'all_time', $projectId = null)
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
                    SELECT transaction_date as date, amount, NULL as project_id
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    
                    UNION ALL
                    
                    SELECT cp.payment_date as date, cp.amount, cp.project_id
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    
                    UNION ALL
                    
                    SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, 
                           net_salary as amount, NULL as project_id
                    FROM payslips
                    WHERE status = 'paid'
                ) combined
                WHERE date BETWEEN ? AND ?
            ";

            if ($projectId) {
                $sql .= " AND (project_id = ? OR project_id IS NULL)";
            }

            $sql .= " GROUP BY month ORDER BY month ASC";

            $stmt = $this->db->prepare($sql);
            if ($projectId) {
                $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
        } else {
            $sql = "
                SELECT 
                    DATE_FORMAT(date, '%Y-%m') as month,
                    COALESCE(SUM(amount), 0) as total
                FROM (
                    SELECT transaction_date as date, amount, NULL as project_id
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    
                    UNION ALL
                    
                    SELECT cp.payment_date as date, cp.amount, cp.project_id
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    
                    UNION ALL
                    
                    SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, 
                           net_salary as amount, NULL as project_id
                    FROM payslips
                    WHERE status = 'paid'
                ) combined
                WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
            ";

            if ($projectId) {
                $sql .= " AND (project_id = ? OR project_id IS NULL)";
            }

            $sql .= " GROUP BY month ORDER BY month ASC";

            $stmt = $this->db->prepare($sql);
            if ($projectId) {
                $stmt->bind_param("ii", $months, $projectId);
            } else {
                $stmt->bind_param("i", $months);
            }
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
     * Get Weekly Transaction Data with project filter
     */
    public function getWeeklyTransactionData($viewType = 'cashin', $period = 'all_time', $projectId = null)
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
                    SELECT lp.payment_date as date, lp.amount 
                    FROM lead_payments lp
                    INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                    WHERE lp.payment_status = 'paid'
                    " . ($projectId ? " AND lb.project_id = ?" : "") . "
                    UNION ALL
                    SELECT mci.transaction_date as date, mci.amount 
                    FROM management_cashin mci 
                    WHERE mci.payment_status = 'paid'
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
                    SELECT transaction_date as date, amount, NULL as project_id
                    FROM management_cashout
                    WHERE payment_status = 'paid'
                    
                    UNION ALL
                    
                    SELECT cp.payment_date as date, cp.amount, cp.project_id
                    FROM cashout_payments cp
                    LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                    WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                    
                    UNION ALL
                    
                    SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, 
                           net_salary as amount, NULL as project_id
                    FROM payslips
                    WHERE status = 'paid'
                ) combined
                WHERE date BETWEEN ? AND ?
            ";

            if ($projectId) {
                $sql .= " AND (project_id = ? OR project_id IS NULL)";
            }

            $sql .= " GROUP BY DAYOFWEEK(date), DAYNAME(date) ORDER BY DAYOFWEEK(date)";
        }

        $stmt = $this->db->prepare($sql);
        if ($viewType === 'cashin' && $projectId) {
            $stmt->bind_param("iss", $projectId, $startOfWeek, $endOfWeek);
        } elseif ($viewType === 'cashout' && $projectId) {
            $stmt->bind_param("ssi", $startOfWeek, $endOfWeek, $projectId);
        } else {
            $stmt->bind_param("ss", $startOfWeek, $endOfWeek);
        }

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
     * Get Yearly Transaction Data with project filter
     */
    public function getYearlyTransactionData($viewType = 'cashin', $years = 3, $period = 'all_time', $projectId = null)
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
                        SELECT lp.payment_date as date, lp.amount 
                        FROM lead_payments lp
                        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                        WHERE lp.payment_status = 'paid'
                        " . ($projectId ? " AND lb.project_id = ?" : "") . "
                        UNION ALL
                        SELECT mci.transaction_date as date, mci.amount 
                        FROM management_cashin mci 
                        WHERE mci.payment_status = 'paid'
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
                        SELECT transaction_date as date, amount, NULL as project_id
                        FROM management_cashout
                        WHERE payment_status = 'paid'
                        
                        UNION ALL
                        
                        SELECT cp.payment_date as date, cp.amount, cp.project_id
                        FROM cashout_payments cp
                        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                        WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                        
                        UNION ALL
                        
                        SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, 
                               net_salary as amount, NULL as project_id
                        FROM payslips
                        WHERE status = 'paid'
                    ) combined
                    WHERE date BETWEEN ? AND ?
                ";

                if ($projectId) {
                    $sql .= " AND (project_id = ? OR project_id IS NULL)";
                }

                $sql .= " GROUP BY YEAR(date) ORDER BY YEAR(date) ASC";
            }

            $stmt = $this->db->prepare($sql);
            if ($viewType === 'cashin' && $projectId) {
                $stmt->bind_param("iss", $projectId, $startDate, $endDate);
            } elseif ($viewType === 'cashout' && $projectId) {
                $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
        } else {
            if ($viewType === 'cashin') {
                $sql = "
                    SELECT 
                        YEAR(date) as year,
                        COALESCE(SUM(amount), 0) as total
                    FROM (
                        SELECT lp.payment_date as date, lp.amount 
                        FROM lead_payments lp
                        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
                        WHERE lp.payment_status = 'paid'
                        " . ($projectId ? " AND lb.project_id = ?" : "") . "
                        UNION ALL
                        SELECT mci.transaction_date as date, mci.amount 
                        FROM management_cashin mci 
                        WHERE mci.payment_status = 'paid'
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
                        SELECT transaction_date as date, amount, NULL as project_id
                        FROM management_cashout
                        WHERE payment_status = 'paid'
                        
                        UNION ALL
                        
                        SELECT cp.payment_date as date, cp.amount, cp.project_id
                        FROM cashout_payments cp
                        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
                        WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
                        
                        UNION ALL
                        
                        SELECT STR_TO_DATE(CONCAT(payslip_month, '-01'), '%Y-%m-%d') as date, 
                               net_salary as amount, NULL as project_id
                        FROM payslips
                        WHERE status = 'paid'
                    ) combined
                    WHERE date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? YEAR)
                ";

                if ($projectId) {
                    $sql .= " AND (project_id = ? OR project_id IS NULL)";
                }

                $sql .= " GROUP BY YEAR(date) ORDER BY YEAR(date) ASC";
            }

            $stmt = $this->db->prepare($sql);
            if ($viewType === 'cashin' && $projectId) {
                $stmt->bind_param("ii", $projectId, $years);
            } elseif ($viewType === 'cashout' && $projectId) {
                $stmt->bind_param("ii", $years, $projectId);
            } else {
                $stmt->bind_param("i", $years);
            }
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
     * Get recent cashout requests with project filter
     */
    public function getRecentCashOutRequests($limit = 5, $period = 'all_time', $projectId = null)
    {
        $dateCondition = $this->getPeriodCondition($period, 'cr.created_at');

        $sql = "SELECT cr.*, 
                u.full_name as requester_name,
                p.name as project_name,
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
                LEFT JOIN projects p ON cr.project_id = p.id
                LEFT JOIN approval_requests ar ON ar.source_table = 'cashout_requests' AND ar.source_id = cr.id
                LEFT JOIN users approver ON ar.approved_by = approver.id
                LEFT JOIN users rejector ON cr.rejected_by = rejector.id
                WHERE 1=1 ";

        if ($dateCondition) {
            $sql .= " AND " . substr($dateCondition, 6);
        }

        if ($projectId) {
            $sql .= " AND cr.project_id = ?";
        }

        $sql .= " ORDER BY cr.created_at DESC LIMIT ?";

        $stmt = $this->db->prepare($sql);
        if ($projectId) {
            $stmt->bind_param("ii", $projectId, $limit);
        } else {
            $stmt->bind_param("i", $limit);
        }
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
     * Get all projects for dropdown
     */
    public function getAllProjects()
    {
        $sql = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
        $result = $this->db->query($sql);

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }

        return $projects;
    }

    /**
     * Get projects with cashout data
     */
    public function getProjectsWithCashout()
    {
        $sql = "
            SELECT DISTINCT 
                p.id,
                p.name
            FROM projects p
            LEFT JOIN cashout_requests cr ON p.id = cr.project_id
            LEFT JOIN cashout_payments cp ON p.id = cp.project_id
            WHERE p.deleted_at IS NULL
            AND (cr.id IS NOT NULL OR cp.id IS NOT NULL)
            ORDER BY p.name
        ";

        $result = $this->db->query($sql);

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }

        return $projects;
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
