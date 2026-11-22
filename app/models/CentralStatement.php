<?php
class CentralStatement extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Helper: Check if filter is specifically Head Office / General only
     */
    private function isHeadOfficeOnly($projectId): bool
    {
        return $projectId === 'head_office';
    }

    /**
     * Helper: Check if filter is a specific numeric project ID
     */
    private function isSpecificProject($projectId): bool
    {
        return $projectId !== null && $projectId !== '' && $projectId !== 'head_office' && is_numeric($projectId) && (int)$projectId > 0;
    }

    /**
     * Get total management cashin (Head Office level)
     */
    private function getTotalManagementCashin($startDate, $endDate, $projectId = null)
    {
        if ($this->isSpecificProject($projectId)) {
            return 0.00;
        }

        $query = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM management_cashin
        WHERE payment_status = 'paid'
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(transaction_date) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            $types = "ss";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get total expense adjustment cashin (refunds received)
     */
    private function getTotalExpenseAdjustmentCashin($startDate, $endDate, $projectId = null)
    {
        $query = "
        SELECT COALESCE(SUM(at.amount), 0) as total
        FROM adjustment_transactions at
        JOIN expense_adjustments ea ON at.adjustment_id = ea.id
        WHERE at.transaction_type = 'cashin'
        AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(at.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (ea.project_id IS NULL OR ea.project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND ea.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get total procurement returns cashin (Office Procurement Assets returned money)
     */
    private function getTotalProcurementReturnsCashin($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(return_amount), 0) as total
            FROM project_extra_assets
            WHERE overall_status = 'purchased'
            AND return_amount > 0
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(purchase_confirmed_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (project_id IS NULL OR project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return 0.00;
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get management cashin breakdown
     */
    private function getManagementCashinBreakdown($startDate, $endDate, $projectId = null)
    {
        if ($this->isSpecificProject($projectId)) {
            return [
                'breakdown' => [],
                'total_amount' => 0,
                'transaction_count' => 0
            ];
        }

        $query = "
        SELECT 
            id,
            transaction_date,
            role_name,
            person_name,
            amount,
            payment_method,
            source,
            description
        FROM management_cashin
        WHERE payment_status = 'paid'
    ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(transaction_date) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            $types = "ss";
        }

        $query .= " ORDER BY transaction_date DESC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['breakdown' => [], 'total_amount' => 0, 'transaction_count' => 0];
        }
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $breakdown = [];
        $totalAmount = 0;

        while ($row = $result->fetch_assoc()) {
            $amount = (float)$row['amount'];
            $breakdown[] = [
                'id' => $row['id'],
                'transaction_date' => $row['transaction_date'],
                'role_name' => $row['role_name'],
                'person_name' => $row['person_name'],
                'amount' => $amount,
                'payment_method' => $row['payment_method'],
                'source' => $row['source'],
                'description' => $row['description']
            ];
            $totalAmount += $amount;
        }

        return [
            'breakdown' => $breakdown,
            'total_amount' => $totalAmount,
            'transaction_count' => count($breakdown)
        ];
    }

    public function calculateRevenue($startDate, $endDate, $projectId = null)
    {
        // Get all required totals
        $totalLandShare = $this->getTotalLandShare($startDate, $endDate, $projectId);
        $totalDiscount = $this->getTotalDiscount($startDate, $endDate, $projectId);
        $totalBookingMoney = $this->getTotalBookingMoney($startDate, $endDate, $projectId);
        $totalServiceFee = $this->getTotalServiceFee($startDate, $endDate, $projectId);
        $totalManagementCashin = $this->getTotalManagementCashin($startDate, $endDate, $projectId);

        // Get individual payment type totals for cashin calculation
        $landsharePayments = $this->getTotalPaymentByType('land_share', $startDate, $endDate, $projectId);
        $registrationPayments = $this->getTotalPaymentByType('registration', $startDate, $endDate, $projectId);
        $emiPayments = $this->getTotalPaymentByType('emi', $startDate, $endDate, $projectId);
        $bookingMoneyPayments = $this->getTotalPaymentByType('booking_money', $startDate, $endDate, $projectId);
        $developmentChargePayments = $this->getTotalPaymentByType('development_charge', $startDate, $endDate, $projectId);
        $shopServiceChargePayments = $this->getTotalPaymentByType('shop_service_charge', $startDate, $endDate, $projectId);

        // Get expense adjustment cashin total
        $totalExpenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($startDate, $endDate, $projectId);

        // Get procurement returns cashin total
        $totalProcurementReturnsCashin = $this->getTotalProcurementReturnsCashin($startDate, $endDate, $projectId);

        // Calculate cashin according to standard formula (INCLUDES management_cashin)
        $totalCashin = $landsharePayments + $registrationPayments +
            $totalBookingMoney + $emiPayments + $totalManagementCashin + $developmentChargePayments + $shopServiceChargePayments;

        // Calculate revenue — includes land share, booking money, service fee, development charges, AND registration
        $totalRevenue = $totalLandShare + $totalBookingMoney + $totalServiceFee + $developmentChargePayments + $registrationPayments;

        // Additional breakdowns
        $paymentBreakdown = $this->getPaymentBreakdown($startDate, $endDate, $projectId);
        $emiBreakdown = $this->getEMIBreakdown($startDate, $endDate, $projectId);
        $landShareBreakdown = $this->getLandShareBreakdown($startDate, $endDate, $projectId);
        $managementCashinBreakdown = $this->getManagementCashinBreakdown($startDate, $endDate, $projectId);

        return [
            'summary' => [
                'total_landshare_from_payments' => $totalLandShare,
                'total_landshare_payments' => $landsharePayments,
                'total_registration_payments' => $registrationPayments,
                'total_emi_payments' => $emiPayments,
                'total_booking_money' => $bookingMoneyPayments,
                'total_booking_money_payments' => $bookingMoneyPayments,
                'total_discount_from_bookings' => $totalDiscount,
                'total_service_fee' => $totalServiceFee,
                'total_shop_service_charge' => $shopServiceChargePayments,
                'total_management_cashin' => $totalManagementCashin,
                'total_development_charge' => $developmentChargePayments,
                'total_expense_adjustment_cashin' => $totalExpenseAdjCashin,
                'total_procurement_returns_cashin' => $totalProcurementReturnsCashin,
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'revenue_percentage' => $totalCashin > 0 ? ($totalRevenue / $totalCashin) * 100 : 0
            ],
            'payment_breakdown' => $paymentBreakdown,
            'emi_breakdown' => $emiBreakdown,
            'landshare_breakdown' => $landShareBreakdown,
            'management_cashin_breakdown' => $managementCashinBreakdown,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    private function getTotalPaymentByType($paymentType, $startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return 0.00;
        }

        $query = "
            SELECT COALESCE(SUM(lp.amount), 0) as total_amount
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            AND lp.payment_type = ?
        ";

        $params = [$paymentType];
        $types = "s";

        if ($startDate && $endDate) {
            $query .= " AND DATE(lp.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_amount'] ?? 0);
    }

    private function getTotalLandShare($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return 0.00;
        }

        $query = "
            SELECT COALESCE(SUM(lp.land_share_amount), 0) as total_landshare
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            AND lp.payment_type = 'land_share'
        ";

        $params = [];
        $types = "";

        if ($startDate && $endDate) {
            $query .= " AND DATE(lp.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_landshare'] ?? 0);
    }

    private function getTotalBookingMoney($startDate, $endDate, $projectId = null)
    {
        return $this->getTotalPaymentByType('booking_money', $startDate, $endDate, $projectId);
    }

    private function getTotalServiceFee($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return 0.00;
        }

        $query = "
            SELECT COALESCE(SUM(es.service_fee), 0) as total_service_fee
            FROM emi_schedules es
            INNER JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE es.status = 'paid'
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(es.due_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $emiServiceFee = (float)($row['total_service_fee'] ?? 0);

        $shopServiceCharge = $this->getTotalPaymentByType('shop_service_charge', $startDate, $endDate, $projectId);

        return $emiServiceFee + $shopServiceCharge;
    }

    private function getPaymentBreakdown($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return ['breakdown' => [], 'grand_total' => 0];
        }

        $query = "
            SELECT 
                lp.payment_type,
                COALESCE(SUM(lp.amount), 0) as total_amount,
                COUNT(*) as payment_count
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(lp.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " GROUP BY lp.payment_type ORDER BY total_amount DESC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['breakdown' => [], 'grand_total' => 0];
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $breakdown = [];
        $grandTotal = 0;
        while ($row = $result->fetch_assoc()) {
            $amount = (float)$row['total_amount'];
            $breakdown[] = [
                'payment_type' => $row['payment_type'],
                'total_amount' => $amount,
                'payment_count' => (int)$row['payment_count']
            ];
            $grandTotal += $amount;
        }

        return [
            'breakdown' => $breakdown,
            'grand_total' => $grandTotal
        ];
    }

    private function getEMIBreakdown($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return ['breakdown' => [], 'totals' => ['total_emi' => 0, 'total_service_fee' => 0, 'total_count' => 0]];
        }

        $query = "
            SELECT 
                es.lead_booking_id,
                COALESCE(SUM(es.emi_amount), 0) as total_emi,
                COALESCE(SUM(es.service_fee), 0) as total_service_fee,
                COUNT(*) as emi_count
            FROM emi_schedules es
            INNER JOIN lead_bookings lb ON es.lead_booking_id = lb.id
            WHERE es.status = 'paid'
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(es.due_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " GROUP BY es.lead_booking_id ORDER BY total_emi DESC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['breakdown' => [], 'totals' => ['total_emi' => 0, 'total_service_fee' => 0, 'total_count' => 0]];
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $breakdown = [];
        $totalEmi = 0;
        $totalServiceFee = 0;
        $totalCount = 0;

        while ($row = $result->fetch_assoc()) {
            $emi = (float)$row['total_emi'];
            $serviceFee = (float)$row['total_service_fee'];
            $breakdown[] = [
                'booking_id' => $row['lead_booking_id'],
                'total_emi' => $emi,
                'total_service_fee' => $serviceFee,
                'emi_count' => (int)$row['emi_count']
            ];
            $totalEmi += $emi;
            $totalServiceFee += $serviceFee;
            $totalCount += (int)$row['emi_count'];
        }

        return [
            'breakdown' => $breakdown,
            'totals' => [
                'total_emi' => $totalEmi,
                'total_service_fee' => $totalServiceFee,
                'total_count' => $totalCount
            ]
        ];
    }

    private function getLandShareBreakdown($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return ['breakdown' => [], 'total_landshare' => 0, 'transaction_count' => 0];
        }

        $query = "
            SELECT 
                lp.booking_id,
                lp.payment_date,
                lp.land_share_amount,
                lp.amount as total_payment,
                lb.lead_id,
                lb.project_id
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            AND lp.payment_type = 'land_share'
            AND lp.land_share_amount > 0
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(lp.payment_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " ORDER BY lp.payment_date DESC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['breakdown' => [], 'total_landshare' => 0, 'transaction_count' => 0];
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $breakdown = [];
        $totalLandShare = 0;

        while ($row = $result->fetch_assoc()) {
            $landShare = (float)$row['land_share_amount'];
            $breakdown[] = [
                'booking_id' => $row['booking_id'],
                'payment_date' => $row['payment_date'],
                'land_share_amount' => $landShare,
                'total_payment' => (float)$row['total_payment'],
                'lead_id' => $row['lead_id'],
                'project_id' => $row['project_id']
            ];
            $totalLandShare += $landShare;
        }

        return [
            'breakdown' => $breakdown,
            'total_landshare' => $totalLandShare,
            'transaction_count' => count($breakdown)
        ];
    }

    private function getTotalDiscount($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return 0.00;
        }

        $query = "
            SELECT COALESCE(SUM(discount_amount), 0) as total_discount
            FROM lead_bookings
            WHERE booking_status = 'booked'
            AND landshare_status = 'active'
        ";

        $params = [];
        $types = "";
        if ($startDate && $endDate) {
            $query .= " AND DATE(booking_date) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_discount'] ?? 0);
    }

    /**
     * Get monthly statement (daily breakdown)
     */
    public function getEnhancedMonthlyStatement($year, $month, $projectId = null)
    {
        // Get the regular monthly statement
        $monthlyStatement = $this->getMonthlyStatement($year, $month, $projectId, 'all');

        // Calculate revenue for the month
        $monthPadded = sprintf("%02d", $month);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $daysPadded = sprintf("%02d", $daysInMonth);

        $startDate = "$year-$monthPadded-01";
        $endDate = "$year-$monthPadded-$daysPadded";

        $revenueData = $this->calculateRevenue($startDate, $endDate, $projectId);

        // Combine both results
        return [
            'period' => $monthlyStatement['period'],
            'start_date' => $monthlyStatement['start_date'],
            'end_date' => $monthlyStatement['end_date'],
            'daily_data' => $monthlyStatement['daily_data'],
            'financial_summary' => [
                'cash_flow' => $monthlyStatement['summary'],
                'revenue_analysis' => $revenueData['summary'],
                'payment_breakdown' => $revenueData['payment_breakdown'],
                'emi_breakdown' => $revenueData['emi_breakdown'],
                'landshare_breakdown' => $revenueData['landshare_breakdown']
            ]
        ];
    }

    public function getRevenueStatistics($periodType = 'monthly', $year = null, $month = null, $startDate = null, $endDate = null, $projectId = null)
    {
        // Build date range based on period type
        if ($periodType === 'monthly' && $year && $month) {
            $monthPadded = sprintf("%02d", $month);
            $startDate = "$year-$monthPadded-01";
            $endDate = date('Y-m-t', strtotime($startDate));
        } elseif ($periodType === 'yearly' && $year) {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
        } elseif ($periodType === 'custom' && $startDate && $endDate) {
            // Use provided dates
        } else {
            // Default to current month
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
        }

        // Get revenue calculation
        $revenueData = $this->calculateRevenue($startDate, $endDate, $projectId);

        // Get cash flow data
        $cashFlowData = $this->getOverallStatistics($periodType, $year, $month, $startDate, $endDate, $projectId, 'all');

        return [
            'period' => [
                'type' => $periodType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'year' => $year,
                'month' => $month
            ],
            'revenue_summary' => $revenueData['summary'],
            'cash_flow_summary' => $cashFlowData,
            'key_metrics' => [
                'profitability_ratio' => $revenueData['summary']['revenue_percentage'],
                'cash_to_revenue_ratio' => $cashFlowData['total_cashin'] > 0 ?
                    ($revenueData['summary']['total_revenue'] / $cashFlowData['total_cashin']) * 100 : 0,
                'average_transaction_value' => $revenueData['payment_breakdown']['grand_total'] > 0 &&
                    $revenueData['payment_breakdown']['breakdown'] ?
                    $revenueData['payment_breakdown']['grand_total'] / count($revenueData['payment_breakdown']['breakdown']) : 0
            ]
        ];
    }

    public function getMonthlyStatement($year, $month, $projectId = null, $category = 'all')
    {
        $year = (int)$year;
        $month = (int)$month;
        if ($month < 1 || $month > 12) {
            $month = (int)date('m');
        }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $monthPadded = sprintf("%02d", $month);
        $daysPadded = sprintf("%02d", $daysInMonth);

        $startDate = "$year-$monthPadded-01";
        $endDate = "$year-$monthPadded-$daysPadded";

        return $this->buildDailyRangeStatement($startDate, $endDate, $projectId, $category, date('F Y', strtotime($startDate)));
    }

    /**
     * Unified daily range statement builder used by getMonthlyStatement & getCustomDailyStatement
     */
    private function buildDailyRangeStatement($startDate, $endDate, $projectId = null, $category = 'all', $periodLabel = '')
    {
        if ($projectId === '') {
            $projectId = null;
        }

        // Get opening balance
        $openingBalance = $this->getOpeningBalance($startDate, $projectId, $category);

        // Get daily payment breakdown by type
        $landsharePaymentsData = $this->getDailyPaymentByType('land_share', $startDate, $endDate, $projectId);
        $registrationPaymentsData = $this->getDailyPaymentByType('registration', $startDate, $endDate, $projectId);
        $emiPaymentsData = $this->getDailyPaymentByType('emi', $startDate, $endDate, $projectId);
        $bookingMoneyData = $this->getDailyBookingMoney($startDate, $endDate, $projectId);
        $serviceFeeData = $this->getDailyServiceFees($startDate, $endDate, $projectId);
        $shopServiceChargeData = $this->getDailyPaymentByType('shop_service_charge', $startDate, $endDate, $projectId);
        $managementCashinData = $this->getDailyManagementCashins($startDate, $endDate, $projectId);
        $developmentChargeData = $this->getDailyPaymentByType('development_charge', $startDate, $endDate, $projectId);
        $expenseAdjCashinData = $this->getDailyExpenseAdjustmentsCashin($startDate, $endDate, $projectId);
        $procurementReturnsCashinData = $this->getDailyProcurementReturnsCashin($startDate, $endDate, $projectId);
        $vendorPaymentData = $this->getDailyVendorPayments($startDate, $endDate, $projectId);
        $contractorBillData = $this->getDailyContractorBills($startDate, $endDate, $projectId);
        $discountData = $this->getDailyDiscounts($startDate, $endDate, $projectId);
        $cashOutData = $this->getCashOutData($startDate, $endDate, $category, $projectId);

        $dailyStatement = [];
        $runningBalance = $openingBalance;

        $totals = [
            'landshare_payments' => 0,
            'registration_payments' => 0,
            'emi_payments' => 0,
            'booking_money' => 0,
            'service_fee' => 0,
            'management_cashin' => 0,
            'development_charge' => 0,
            'shop_service_charge' => 0,
            'expense_adjustment_cashin' => 0,
            'procurement_returns_cashin' => 0,
            'vendor_payment' => 0,
            'contractor_bill' => 0,
            'discount' => 0,
            'cash_out' => 0
        ];

        $expenseCategories = ['cashout', 'utilities', 'other', 'vendor_payment', 'contractor_bill', 'office_procurement_assets', 'assets_repairing', 'fund_requisitions'];

        $startTs = strtotime($startDate);
        $endTs = strtotime($endDate);

        for ($curTs = $startTs; $curTs <= $endTs; $curTs = strtotime('+1 day', $curTs)) {
            $date = date('Y-m-d', $curTs);

            $landsharePayments = $landsharePaymentsData[$date] ?? 0;
            $registrationPayments = $registrationPaymentsData[$date] ?? 0;
            $emiPayments = $emiPaymentsData[$date] ?? 0;
            $bookingMoney = $bookingMoneyData[$date] ?? 0;
            $shopServiceCharge = $shopServiceChargeData[$date] ?? 0;
            $serviceFee = ($serviceFeeData[$date] ?? 0) + $shopServiceCharge;
            $managementCashin = $managementCashinData[$date] ?? 0;
            $developmentCharge = $developmentChargeData[$date] ?? 0;
            $expenseAdjCashin = $expenseAdjCashinData[$date] ?? 0;
            $procurementReturnsCashin = $procurementReturnsCashinData[$date] ?? 0;
            $vendorPayment = $vendorPaymentData[$date] ?? 0;
            $contractorBill = $contractorBillData[$date] ?? 0;
            $discount = $discountData[$date] ?? 0;

            $cashin = 0;
            if ($category === 'all' || $category === 'total_cashin') {
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin + $developmentCharge + $shopServiceCharge;
            } elseif ($category === 'emi') {
                $cashin = $emiPayments;
            } elseif ($category === 'development_charge') {
                $cashin = $developmentCharge;
            } elseif ($category === 'landshare') {
                $cashin = $landsharePayments;
            } elseif ($category === 'registration') {
                $cashin = $registrationPayments;
            } elseif ($category === 'service_fee') {
                $cashin = $serviceFee;
            } elseif ($category === 'booking_money') {
                $cashin = $bookingMoney;
            } elseif ($category === 'management_cashin') {
                $cashin = $managementCashin;
            } elseif ($category === 'expense_adjustment_cashin') {
                $cashin = $expenseAdjCashin;
            } elseif ($category === 'procurement_returns_cashin') {
                $cashin = $procurementReturnsCashin;
            }

            $cashOut = 0;
            if ($category === 'vendor_payment') {
                $cashOut = $vendorPayment;
            } elseif ($category === 'contractor_bill') {
                $cashOut = $contractorBill;
            } elseif ($category === 'all' || in_array($category, $expenseCategories, true)) {
                $cashOut = $cashOutData[$date] ?? 0;
            }

            if ($category === 'all' || $category === 'cashout') {
                $cashOut -= ($expenseAdjCashin + $procurementReturnsCashin);
            }

            $netAmount = $cashin - $cashOut;

            if ($category === 'all') {
                $runningBalance += $netAmount;
            }

            $totals['landshare_payments'] += $landsharePayments;
            $totals['registration_payments'] += $registrationPayments;
            $totals['emi_payments'] += $emiPayments;
            $totals['booking_money'] += $bookingMoney;
            $totals['service_fee'] += $serviceFee;
            $totals['management_cashin'] += $managementCashin;
            $totals['development_charge'] += $developmentCharge;
            $totals['shop_service_charge'] += $shopServiceCharge;
            $totals['expense_adjustment_cashin'] += $expenseAdjCashin;
            $totals['procurement_returns_cashin'] += $procurementReturnsCashin;
            $totals['vendor_payment'] += $vendorPayment;
            $totals['contractor_bill'] += $contractorBill;
            $totals['discount'] += $discount;
            $totals['cash_out'] += $cashOut;

            $dailyStatement[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', $curTs),
                'day_name' => date('l', $curTs),
                'booking_money' => $bookingMoney,
                'registration' => $registrationPayments,
                'registration_payments' => $registrationPayments,
                'emi_payments' => $emiPayments,
                'landshare_payments' => $landsharePayments,
                'landshare' => $landsharePayments,
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin,
                'development_charge' => $developmentCharge,
                'shop_service_charge' => $shopServiceCharge,
                'expense_adjustment_cashin' => $expenseAdjCashin,
                'procurement_returns_cashin' => $procurementReturnsCashin,
                'discount' => $discount,
                'total_cashin' => $cashin,
                'total_revenue' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $category === 'all' ? $runningBalance : $cashin,
            ];
        }

        $totalCashin = 0;
        $totalRevenue = 0;

        if ($category === 'all' || $category === 'total_cashin') {
            $totalCashin = $totals['landshare_payments'] + $totals['registration_payments'] +
                $totals['booking_money'] + $totals['emi_payments'] + $totals['management_cashin'] +
                $totals['development_charge'] + $totals['shop_service_charge'];

            $totalRevenue = $totals['landshare_payments'] + $totals['booking_money'] +
                $totals['service_fee'] + $totals['development_charge'] + $totals['registration_payments'];
        } elseif ($category === 'development_charge') {
            $totalCashin = $totals['development_charge'];
            $totalRevenue = $totals['development_charge'];
        } elseif ($category === 'emi') {
            $totalCashin = $totals['emi_payments'];
            $totalRevenue = $totals['emi_payments'];
        } elseif ($category === 'landshare') {
            $totalCashin = $totals['landshare_payments'];
            $totalRevenue = $totals['landshare_payments'];
        } elseif ($category === 'registration') {
            $totalCashin = $totals['registration_payments'];
            $totalRevenue = $totals['registration_payments'];
        } elseif ($category === 'service_fee') {
            $totalCashin = $totals['service_fee'];
            $totalRevenue = $totals['service_fee'];
        } elseif ($category === 'booking_money') {
            $totalCashin = $totals['booking_money'];
            $totalRevenue = $totals['booking_money'];
        } elseif ($category === 'management_cashin') {
            $totalCashin = $totals['management_cashin'];
            $totalRevenue = $totals['management_cashin'];
        } elseif ($category === 'expense_adjustment_cashin') {
            $totalCashin = $totals['expense_adjustment_cashin'];
            $totalRevenue = $totals['expense_adjustment_cashin'];
        } elseif ($category === 'procurement_returns_cashin') {
            $totalCashin = $totals['procurement_returns_cashin'];
            $totalRevenue = $totals['procurement_returns_cashin'];
        }

        return [
            'period' => $periodLabel ?: (date('d M, Y', strtotime($startDate)) . ' to ' . date('d M, Y', strtotime($endDate))),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'daily_data' => $dailyStatement,
            'summary' => [
                'total_booking_money' => $totals['booking_money'],
                'total_registration_payments' => $totals['registration_payments'],
                'total_emi_payments' => $totals['emi_payments'],
                'total_landshare_payments' => $totals['landshare_payments'],
                'total_landshare' => $totals['landshare_payments'],
                'total_service_fee' => $totals['service_fee'],
                'total_shop_service_charge' => $totals['shop_service_charge'],
                'total_management_cashin' => $totals['management_cashin'],
                'total_development_charge' => $totals['development_charge'],
                'total_expense_adjustment_cashin' => $totals['expense_adjustment_cashin'],
                'total_procurement_returns_cashin' => $totals['procurement_returns_cashin'],
                'total_discount' => $totals['discount'],
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totals['cash_out'],
                'net_balance' => $totalCashin - $totals['cash_out'],
                'final_balance' => $category === 'all' ? $runningBalance : $totalCashin
            ]
        ];
    }

    /**
     * Helper method to get amount based on category
     */
    private function getCategoryAmount($selectedCategory, $categoryType, $dataArray, $date)
    {
        if ($selectedCategory === 'total_cashin') {
            return 0;
        }
        if ($selectedCategory === 'all' || $selectedCategory === $categoryType) {
            return $dataArray[$date] ?? 0;
        }
        return 0;
    }

    private function getDailyPaymentByType($paymentType, $startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return [];
        }

        $query = " 
            SELECT 
                DATE(lp.payment_date) as transaction_date,
                SUM(lp.amount) as total_amount
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(lp.payment_date) BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            AND lp.payment_type = ?
        ";

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
        }

        $query .= " GROUP BY DATE(lp.payment_date)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        if ($this->isSpecificProject($projectId)) {
            $pid = (int)$projectId;
            $stmt->bind_param("sssi", $startDate, $endDate, $paymentType, $pid);
        } else {
            $stmt->bind_param("sss", $startDate, $endDate, $paymentType);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    private function getDailyDiscounts($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return [];
        }

        $query = "
            SELECT 
                DATE(booking_date) as transaction_date,
                SUM(discount_amount) as total_amount
            FROM lead_bookings
            WHERE DATE(booking_date) BETWEEN ? AND ?
            AND booking_status = 'booked'
            AND landshare_status = 'active'
        ";

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND project_id = ?";
        }

        $query .= " GROUP BY DATE(booking_date)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        if ($this->isSpecificProject($projectId)) {
            $pid = (int)$projectId;
            $stmt->bind_param("ssi", $startDate, $endDate, $pid);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    private function getDailyBookingMoney($startDate, $endDate, $projectId = null)
    {
        return $this->getDailyPaymentByType('booking_money', $startDate, $endDate, $projectId);
    }

    private function getDailyServiceFees($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return [];
        }

        if ($this->isSpecificProject($projectId)) {
            $query = "
                SELECT 
                    DATE(es.due_date) as transaction_date,
                    SUM(es.service_fee) as total_amount
                FROM emi_schedules es
                INNER JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE DATE(es.due_date) BETWEEN ? AND ?
                AND es.status = 'paid'
                AND lb.project_id = ?
                GROUP BY DATE(es.due_date)
            ";
        } else {
            $query = "
                SELECT 
                    DATE(es.due_date) as transaction_date,
                    SUM(es.service_fee) as total_amount
                FROM emi_schedules es
                WHERE DATE(es.due_date) BETWEEN ? AND ?
                AND es.status = 'paid'
                GROUP BY DATE(es.due_date)
            ";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        if ($this->isSpecificProject($projectId)) {
            $pid = (int)$projectId;
            $stmt->bind_param("ssi", $startDate, $endDate, $pid);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    private function getDailyManagementCashins($startDate, $endDate, $projectId = null)
    {
        if ($this->isSpecificProject($projectId)) {
            return [];
        }

        $query = "
            SELECT 
                DATE(transaction_date) as transaction_date,
                SUM(amount) as total_amount
            FROM management_cashin
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
            GROUP BY DATE(transaction_date)
        ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    /**
     * Get daily expense adjustment cashin (refunds received)
     */
    private function getDailyExpenseAdjustmentsCashin($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(at.payment_date) as transaction_date,
                SUM(at.amount) as total_amount
            FROM adjustment_transactions at
            JOIN expense_adjustments ea ON at.adjustment_id = ea.id
            WHERE at.transaction_type = 'cashin'
            AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
            AND DATE(at.payment_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (ea.project_id IS NULL OR ea.project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND ea.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " GROUP BY DATE(at.payment_date)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getDailyExpenseAdjustmentsCashin: " . $this->db->error);
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    /**
     * Get daily procurement returns cashin (actual return_amount, NOT paid_amount)
     */
    private function getDailyProcurementReturnsCashin($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(purchase_confirmed_at) as transaction_date,
                SUM(return_amount) as total_amount
            FROM project_extra_assets
            WHERE overall_status = 'purchased'
            AND return_amount > 0
            AND DATE(purchase_confirmed_at) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (project_id IS NULL OR project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " GROUP BY DATE(purchase_confirmed_at)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getDailyProcurementReturnsCashin: " . $this->db->error);
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    /**
     * Get daily expense adjustment cashout (additional payments)
     */
    private function getDailyExpenseAdjustmentsCashout($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(at.payment_date) as transaction_date,
                SUM(at.amount) as total_amount
            FROM adjustment_transactions at
            JOIN expense_adjustments ea ON at.adjustment_id = ea.id
            WHERE at.transaction_type = 'cashout'
            AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
            AND DATE(at.payment_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (ea.project_id IS NULL OR ea.project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND ea.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " GROUP BY DATE(at.payment_date)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getDailyExpenseAdjustmentsCashout: " . $this->db->error);
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    public function getCashoutPaymentDetails($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(cp.payment_date) as payment_date,
                cp.amount,
                cp.payment_method,
                cp.transaction_ref,
                COALESCE(cp.office, cr.office) as office,
                COALESCE(cr.purpose_paid_to, cp.purpose_paid_to) as purpose_paid_to,
                cr.description,
                p.name as project_name,
                u.full_name as created_by_name
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            LEFT JOIN projects p ON COALESCE(cr.project_id, cp.project_id) = p.id
            LEFT JOIN users u ON cp.created_by = u.id
            WHERE DATE(cp.payment_date) BETWEEN ? AND ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND COALESCE(cr.project_id, cp.project_id, 0) = 0";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND (cr.project_id = ? OR cp.project_id = ?)";
            $pid = (int)$projectId;
            $params[] = $pid;
            $params[] = $pid;
            $types .= "ii";
        }

        $query .= " ORDER BY cp.payment_date ASC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $date = $row['payment_date'];
            if (!isset($payments[$date])) {
                $payments[$date] = [];
            }
            $payments[$date][] = $row;
        }

        return $payments;
    }

    /**
     * Helper: Get cashout data with category and project filtering
     */
    private function getCashOutData($startDate, $endDate, $category = 'all', $projectId = null)
    {
        if ($category === 'utilities') {
            return $this->getUtilitiesCashOutData($startDate, $endDate, $projectId);
        }

        // 1. Management cashout (Head Office only; only for 'all', 'cashout', 'other', or mapped category_id)
        $managementData = [];
        $categoryId = ($category !== 'all' && $category !== 'cashout') ? $this->getCategoryIdByName($category) : null;
        $includeMgmtCashout = !$this->isSpecificProject($projectId) && (
            in_array($category, ['all', 'cashout', 'other'], true) || $categoryId !== null
        );

        if ($includeMgmtCashout) {
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

            if ($categoryId) {
                $managementQuery .= " AND category_id = ?";
                $managementParams[] = $categoryId;
                $managementTypes .= "i";
            }

            $managementQuery .= " GROUP BY DATE(transaction_date)";
            $stmt = $this->db->prepare($managementQuery);
            if ($stmt) {
                $stmt->bind_param($managementTypes, ...$managementParams);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $managementData[$row['transaction_date']] = (float)$row['total_amount'];
                }
            }
        }

        // 2. Cashout request payments (for 'all' or 'cashout')
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
            ";
            $cashoutRequestParams = [$startDate, $endDate];
            $cashoutRequestTypes = "ss";

            if ($this->isHeadOfficeOnly($projectId)) {
                $cashoutRequestQuery .= " AND COALESCE(cr.project_id, cp.project_id, 0) = 0";
            } elseif ($this->isSpecificProject($projectId)) {
                $cashoutRequestQuery .= " AND (cr.project_id = ? OR cp.project_id = ?)";
                $pid = (int)$projectId;
                $cashoutRequestParams[] = $pid;
                $cashoutRequestParams[] = $pid;
                $cashoutRequestTypes .= "ii";
            }

            $cashoutRequestQuery .= " GROUP BY DATE(cp.payment_date)";
            $stmt = $this->db->prepare($cashoutRequestQuery);
            if ($stmt) {
                $stmt->bind_param($cashoutRequestTypes, ...$cashoutRequestParams);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $cashoutRequestData[$row['transaction_date']] = (float)$row['total_amount'];
                }
            }
        }

        // 3. Salary payments (payslips) - Head Office / General (excluded when filtering a specific project)
        $payslipsData = [];
        if (($category === 'all' || $category === 'cashout') && !$this->isSpecificProject($projectId)) {
            $payslipsQuery = "
                SELECT 
                    DATE(COALESCE(disbursed_at, payslip_month, updated_at)) as transaction_date,
                    SUM(net_salary) as total_amount
                FROM payslips
                WHERE DATE(COALESCE(disbursed_at, payslip_month, updated_at)) BETWEEN ? AND ?
                AND status = 'paid'
                GROUP BY DATE(COALESCE(disbursed_at, payslip_month, updated_at))
            ";
            $stmt = $this->db->prepare($payslipsQuery);
            if ($stmt) {
                $stmt->bind_param("ss", $startDate, $endDate);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $payslipsData[$row['transaction_date']] = (float)$row['total_amount'];
                }
            }
        }

        // 4. Construction financial transactions (vendor_payment, contractor_bill, operational_fund)
        $conFinancialData = [];
        if ($category === 'all' || $category === 'cashout') {
            $conFinQuery = "
                SELECT 
                    DATE(transaction_date) as transaction_date,
                    SUM(amount_paid) as total_amount
                FROM con_financial_transactions
                WHERE DATE(transaction_date) BETWEEN ? AND ?
                AND ref_type IN ('vendor_payment', 'contractor_bill', 'operational_fund')
            ";
            $conParams = [$startDate, $endDate];
            $conTypes = "ss";

            if ($this->isHeadOfficeOnly($projectId)) {
                $conFinQuery .= " AND (project_id IS NULL OR project_id = 0)";
            } elseif ($this->isSpecificProject($projectId)) {
                $conFinQuery .= " AND project_id = ?";
                $conParams[] = (int)$projectId;
                $conTypes .= "i";
            }

            $conFinQuery .= " GROUP BY DATE(transaction_date)";
            $stmt = $this->db->prepare($conFinQuery);
            if ($stmt) {
                $stmt->bind_param($conTypes, ...$conParams);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $conFinancialData[$row['transaction_date']] = (float)$row['total_amount'];
                }
            }
        }

        // 5. Expense adjustment cashout transactions
        $adjCashoutData = [];
        if ($category === 'all' || $category === 'cashout') {
            $adjCashoutData = $this->getDailyExpenseAdjustmentsCashout($startDate, $endDate, $projectId);
        }

        // 6. Fund requisitions ('all', 'cashout', 'assets_repairing', 'fund_requisitions')
        $fundReqData = [];
        if (in_array($category, ['all', 'cashout', 'assets_repairing', 'fund_requisitions'], true)) {
            $fundReqQuery = "
                SELECT 
                    DATE(paid_at) as transaction_date,
                    SUM(amount) as total_amount
                FROM fund_requisitions
                WHERE DATE(paid_at) BETWEEN ? AND ?
                AND status = 'paid'
            ";
            $fundParams = [$startDate, $endDate];
            $fundTypes = "ss";

            if ($category === 'assets_repairing') {
                $fundReqQuery .= " AND category = 'Asset Repair'";
            }

            if ($this->isHeadOfficeOnly($projectId)) {
                $fundReqQuery .= " AND (project_id IS NULL OR project_id = 0)";
            } elseif ($this->isSpecificProject($projectId)) {
                $fundReqQuery .= " AND project_id = ?";
                $fundParams[] = (int)$projectId;
                $fundTypes .= "i";
            }

            $fundReqQuery .= " GROUP BY DATE(paid_at)";
            $stmt = $this->db->prepare($fundReqQuery);
            if ($stmt) {
                $stmt->bind_param($fundTypes, ...$fundParams);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $fundReqData[$row['transaction_date']] = (float)$row['total_amount'];
                }
            }
        }

        // 7. Land fund requisitions
        $landFundReqData = [];
        if (($category === 'all' || $category === 'cashout') && !$this->isHeadOfficeOnly($projectId)) {
            $landFundReqQuery = "
                SELECT 
                    DATE(lfr.accounts_paid_at) as transaction_date,
                    SUM(lfr.requested_amount) as total_amount
                FROM land_fund_requisitions lfr
                LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
                WHERE DATE(lfr.accounts_paid_at) BETWEEN ? AND ?
                AND lfr.mgmt_status = 'Approved'
                AND lfr.accounts_status = 'Paid'
            ";
            $lfrParams = [$startDate, $endDate];
            $lfrTypes = "ss";

            if ($this->isSpecificProject($projectId)) {
                $landFundReqQuery .= " AND la.project_id = ?";
                $lfrParams[] = (int)$projectId;
                $lfrTypes .= "i";
            }

            $landFundReqQuery .= " GROUP BY DATE(lfr.accounts_paid_at)";
            $stmt = $this->db->prepare($landFundReqQuery);
            if ($stmt) {
                $stmt->bind_param($lfrTypes, ...$lfrParams);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $landFundReqData[$row['transaction_date']] = (float)$row['total_amount'];
                }
            }
        }

        // 8. Office procurement assets ('all', 'cashout', 'office_procurement_assets')
        $officeProcAssetsData = [];
        if (in_array($category, ['all', 'cashout', 'office_procurement_assets'], true)) {
            $officeProcAssetsQuery = "
                SELECT 
                    DATE(paid_at) as transaction_date,
                    SUM(paid_amount) as total_amount
                FROM project_extra_assets
                WHERE DATE(paid_at) BETWEEN ? AND ?
                AND payment_status = 'paid'
            ";
            $opaParams = [$startDate, $endDate];
            $opaTypes = "ss";

            if ($this->isHeadOfficeOnly($projectId)) {
                $officeProcAssetsQuery .= " AND (project_id IS NULL OR project_id = 0)";
            } elseif ($this->isSpecificProject($projectId)) {
                $officeProcAssetsQuery .= " AND project_id = ?";
                $opaParams[] = (int)$projectId;
                $opaTypes .= "i";
            }

            $officeProcAssetsQuery .= " GROUP BY DATE(paid_at)";
            $stmt = $this->db->prepare($officeProcAssetsQuery);
            if ($stmt) {
                $stmt->bind_param($opaTypes, ...$opaParams);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $officeProcAssetsData[$row['transaction_date']] = (float)$row['total_amount'];
                }
            }
        }

        // Combine all sources based on category
        $allDates = array_unique(array_merge(
            array_keys($managementData),
            array_keys($cashoutRequestData),
            array_keys($payslipsData),
            array_keys($conFinancialData),
            array_keys($adjCashoutData),
            array_keys($fundReqData),
            array_keys($landFundReqData),
            array_keys($officeProcAssetsData)
        ));

        $combinedData = [];
        foreach ($allDates as $date) {
            $combinedData[$date] =
                ($managementData[$date] ?? 0) +
                ($cashoutRequestData[$date] ?? 0) +
                ($payslipsData[$date] ?? 0) +
                ($conFinancialData[$date] ?? 0) +
                ($adjCashoutData[$date] ?? 0) +
                ($fundReqData[$date] ?? 0) +
                ($landFundReqData[$date] ?? 0) +
                ($officeProcAssetsData[$date] ?? 0);
        }

        return $combinedData;
    }

    /**
     * Helper: Get Utilities cashout data from cashout_requests table based on keywords
     */
    private function getUtilitiesCashOutData($startDate, $endDate, $projectId = null)
    {
        $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

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
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND COALESCE(cr.project_id, cp.project_id, 0) = 0";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND (cr.project_id = ? OR cp.project_id = ?)";
            $pid = (int)$projectId;
            $params[] = $pid;
            $params[] = $pid;
            $types .= "ii";
        }

        $query .= " GROUP BY DATE(cp.payment_date)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $utilitiesData = [];
        while ($row = $result->fetch_assoc()) {
            $utilitiesData[$row['transaction_date']] = (float)$row['total_amount'];
        }

        return $utilitiesData;
    }

    /**
     * Get 6-month statement (monthly breakdown)
     */
    public function getSixMonthStatement($endYear, $endMonth, $projectId = null, $category = 'all')
    {
        $endYear = (int)$endYear;
        if (empty($endYear)) {
            $endYear = (int)date('Y');
        }
        $endMonthInt = (int)$endMonth;
        if ($endMonthInt < 1 || $endMonthInt > 12) {
            $endMonthInt = (int)date('m');
        }
        $endMonth = sprintf('%02d', $endMonthInt);

        $startDate = date('Y-m-01', strtotime("-5 months", strtotime("$endYear-$endMonth-01")));
        $endDate = date('Y-m-t', strtotime("$endYear-$endMonth-01"));

        $statement = $this->getCustomMonthlyStatement($startDate, $endDate, $projectId, $category);
        $statement['period'] = 'Last 6 Months';
        return $statement;
    }

    private function getMonthlyPaymentByType($paymentType, $startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return 0.00;
        }

        $query = "
            SELECT COALESCE(SUM(lp.amount), 0) as total_amount
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(lp.payment_date) BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            AND lp.payment_type = ?
        ";

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        if ($this->isSpecificProject($projectId)) {
            $pid = (int)$projectId;
            $stmt->bind_param("sssi", $startDate, $endDate, $paymentType, $pid);
        } else {
            $stmt->bind_param("sss", $startDate, $endDate, $paymentType);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_amount'] ?? 0);
    }

    private function getMonthlyDiscount($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return 0.00;
        }

        $query = "
            SELECT COALESCE(SUM(discount_amount), 0) as total_discount
            FROM lead_bookings
            WHERE DATE(booking_date) BETWEEN ? AND ?
            AND booking_status = 'booked'
            AND landshare_status = 'active'
        ";

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        if ($this->isSpecificProject($projectId)) {
            $pid = (int)$projectId;
            $stmt->bind_param("ssi", $startDate, $endDate, $pid);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_discount'] ?? 0);
    }

    private function getMonthlyBookingMoney($startDate, $endDate, $projectId = null)
    {
        return $this->getMonthlyPaymentByType('booking_money', $startDate, $endDate, $projectId);
    }

    private function getMonthlyServiceFee($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return 0.00;
        }

        if ($this->isSpecificProject($projectId)) {
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
        if (!$stmt) return 0.00;
        if ($this->isSpecificProject($projectId)) {
            $pid = (int)$projectId;
            $stmt->bind_param("ssi", $startDate, $endDate, $pid);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $emiServiceFee = (float)($row['total'] ?? 0);

        $shopServiceCharge = $this->getMonthlyPaymentByType('shop_service_charge', $startDate, $endDate, $projectId);

        return $emiServiceFee + $shopServiceCharge;
    }

    private function getMonthlyManagementCashin($startDate, $endDate, $projectId = null)
    {
        if ($this->isSpecificProject($projectId)) {
            return 0.00;
        }

        $query = "
            SELECT COALESCE(SUM(amount), 0) as total
            FROM management_cashin
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND payment_status = 'paid'
        ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get yearly statement (monthly breakdown for a year)
     */
    public function getYearlyStatement($year, $projectId = null, $category = 'all')
    {
        $year = (int)$year;
        $startDate = "$year-01-01";
        $endDate = "$year-12-31";

        $statement = $this->getCustomMonthlyStatement($startDate, $endDate, $projectId, $category);
        $statement['period'] = "Year $year";
        return $statement;
    }

    /**
     * Get all time statement across all recorded dates
     */
    public function getAllTimeStatement($projectId = null, $category = 'all')
    {
        if ($projectId === '') {
            $projectId = null;
        }

        $startDate = $this->getEarliestTransactionDate($projectId);
        $endDate = $this->getLatestTransactionDate($projectId);

        if (!$startDate) {
            $startDate = date('Y-01-01');
        }
        if (!$endDate) {
            $endDate = date('Y-12-31');
        }

        $currentYearEnd = date('Y-12-31');
        if (strtotime($endDate) < strtotime($currentYearEnd)) {
            $endDate = $currentYearEnd;
        }

        $statement = $this->getCustomMonthlyStatement($startDate, $endDate, $projectId, $category);
        $statement['period'] = 'All Time';
        $statement['start_date'] = $startDate;
        $statement['end_date'] = $endDate;
        return $statement;
    }

    /**
     * Get earliest transaction date across all cashin/cashout tables
     */
    public function getEarliestTransactionDate($projectId = null)
    {
        $dates = [];

        // 1. Lead payments (verified only)
        if (!$this->isHeadOfficeOnly($projectId)) {
            $sql1 = "SELECT MIN(lp.payment_date) as min_date FROM lead_payments lp";
            if ($this->isSpecificProject($projectId)) {
                $sql1 .= " INNER JOIN lead_bookings lb ON lp.booking_id = lb.id WHERE lb.project_id = " . (int)$projectId . " AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified'";
            } else {
                $sql1 .= " WHERE lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified'";
            }
            $res1 = $this->db->query($sql1);
            if ($res1 && $row = $res1->fetch_assoc()) {
                if (!empty($row['min_date'])) $dates[] = $row['min_date'];
            }
        }

        // 2. Cashout payments
        $sql3 = "SELECT MIN(cp.payment_date) as min_date FROM cashout_payments cp LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')";
        if ($this->isHeadOfficeOnly($projectId)) {
            $sql3 .= " AND COALESCE(cr.project_id, cp.project_id, 0) = 0";
        } elseif ($this->isSpecificProject($projectId)) {
            $sql3 .= " AND (cr.project_id = " . (int)$projectId . " OR cp.project_id = " . (int)$projectId . ")";
        }
        $res3 = $this->db->query($sql3);
        if ($res3 && $row = $res3->fetch_assoc()) {
            if (!empty($row['min_date'])) $dates[] = $row['min_date'];
        }

        // 3. Management cashin
        if (!$this->isSpecificProject($projectId)) {
            $sql4 = "SELECT MIN(transaction_date) as min_date FROM management_cashin WHERE payment_status = 'paid'";
            $res4 = $this->db->query($sql4);
            if ($res4 && $row = $res4->fetch_assoc()) {
                if (!empty($row['min_date'])) $dates[] = $row['min_date'];
            }
        }

        if (empty($dates)) {
            return date('Y-01-01');
        }

        sort($dates);
        return date('Y-m-01', strtotime($dates[0]));
    }

    /**
     * Get latest transaction date across all cashin/cashout tables
     */
    public function getLatestTransactionDate($projectId = null)
    {
        $dates = [];

        // 1. Lead payments (verified only)
        if (!$this->isHeadOfficeOnly($projectId)) {
            $sql1 = "SELECT MAX(lp.payment_date) as max_date FROM lead_payments lp";
            if ($this->isSpecificProject($projectId)) {
                $sql1 .= " INNER JOIN lead_bookings lb ON lp.booking_id = lb.id WHERE lb.project_id = " . (int)$projectId . " AND lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified'";
            } else {
                $sql1 .= " WHERE lp.payment_status IN ('paid', 'partially_paid') AND lp.verification_status = 'verified'";
            }
            $res1 = $this->db->query($sql1);
            if ($res1 && $row = $res1->fetch_assoc()) {
                if (!empty($row['max_date'])) $dates[] = $row['max_date'];
            }
        }

        // 2. Cashout payments
        $sql3 = "SELECT MAX(cp.payment_date) as max_date FROM cashout_payments cp LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')";
        if ($this->isHeadOfficeOnly($projectId)) {
            $sql3 .= " AND COALESCE(cr.project_id, cp.project_id, 0) = 0";
        } elseif ($this->isSpecificProject($projectId)) {
            $sql3 .= " AND (cr.project_id = " . (int)$projectId . " OR cp.project_id = " . (int)$projectId . ")";
        }
        $res3 = $this->db->query($sql3);
        if ($res3 && $row = $res3->fetch_assoc()) {
            if (!empty($row['max_date'])) $dates[] = $row['max_date'];
        }

        // 3. Management cashin
        if (!$this->isSpecificProject($projectId)) {
            $sql4 = "SELECT MAX(transaction_date) as max_date FROM management_cashin WHERE payment_status = 'paid'";
            $res4 = $this->db->query($sql4);
            if ($res4 && $row = $res4->fetch_assoc()) {
                if (!empty($row['max_date'])) $dates[] = $row['max_date'];
            }
        }

        $dates[] = date('Y-m-d');

        rsort($dates);
        return date('Y-m-t', strtotime($dates[0]));
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

    private function getCustomDailyStatement($startDate, $endDate, $projectId = null, $category = 'all')
    {
        return $this->buildDailyRangeStatement($startDate, $endDate, $projectId, $category, 'Custom Range (Daily)');
    }

    /**
     * Unified monthly breakdown statement builder (used by Yearly, 6 Months, All Time, and Custom > 31 days)
     */
    private function getCustomMonthlyStatement($startDate, $endDate, $projectId = null, $category = 'all')
    {
        if ($projectId === '') {
            $projectId = null;
        }

        $monthlyData = [];

        $start = strtotime(date('Y-m-01', strtotime($startDate)));
        $end = strtotime(date('Y-m-t', strtotime($endDate)));

        // Get opening balance before the period
        $openingDate = date('Y-m-01', strtotime($startDate));
        $openingBalance = $this->getOpeningBalance($openingDate, $projectId, $category);
        $runningBalance = $openingBalance;

        $totals = [
            'landshare_payments' => 0,
            'registration_payments' => 0,
            'emi_payments' => 0,
            'booking_money' => 0,
            'service_fee' => 0,
            'management_cashin' => 0,
            'development_charge' => 0,
            'shop_service_charge' => 0,
            'expense_adjustment_cashin' => 0,
            'procurement_returns_cashin' => 0,
            'discount' => 0,
            'cash_out' => 0
        ];

        $expenseCategories = ['cashout', 'utilities', 'other', 'office_procurement_assets', 'assets_repairing', 'fund_requisitions'];

        $current = $start;
        while ($current <= $end) {
            $monthStart = date('Y-m-01', $current);
            $monthEnd = date('Y-m-t', $current);

            $landsharePayments = $this->getMonthlyPaymentByType('land_share', $monthStart, $monthEnd, $projectId);
            $registrationPayments = $this->getMonthlyPaymentByType('registration', $monthStart, $monthEnd, $projectId);
            $emiPayments = $this->getMonthlyPaymentByType('emi', $monthStart, $monthEnd, $projectId);
            $bookingMoney = $this->getMonthlyPaymentByType('booking_money', $monthStart, $monthEnd, $projectId);
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd, $projectId);
            $developmentCharge = $this->getMonthlyPaymentByType('development_charge', $monthStart, $monthEnd, $projectId);
            $shopServiceCharge = $this->getMonthlyPaymentByType('shop_service_charge', $monthStart, $monthEnd, $projectId);
            $expenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($monthStart, $monthEnd, $projectId);
            $procurementReturns = $this->getTotalProcurementReturnsCashin($monthStart, $monthEnd, $projectId);
            $discount = $this->getMonthlyDiscount($monthStart, $monthEnd, $projectId);

            $cashOut = 0;
            if ($category === 'vendor_payment') {
                $cashOut = $this->getMonthlyVendorPayments($monthStart, $monthEnd, $projectId);
            } elseif ($category === 'contractor_bill') {
                $cashOut = $this->getMonthlyContractorBills($monthStart, $monthEnd, $projectId);
            } elseif ($category === 'all' || in_array($category, $expenseCategories, true)) {
                $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category, $projectId);
            }

            if ($category === 'all' || $category === 'cashout') {
                $cashOut -= ($expenseAdjCashin + $procurementReturns);
            }

            $cashin = 0;
            if ($category === 'all' || $category === 'total_cashin') {
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin + $developmentCharge + $shopServiceCharge;
            } elseif ($category === 'emi') {
                $cashin = $emiPayments;
            } elseif ($category === 'development_charge') {
                $cashin = $developmentCharge;
            } elseif ($category === 'landshare') {
                $cashin = $landsharePayments;
            } elseif ($category === 'registration') {
                $cashin = $registrationPayments;
            } elseif ($category === 'service_fee') {
                $cashin = $serviceFee;
            } elseif ($category === 'booking_money') {
                $cashin = $bookingMoney;
            } elseif ($category === 'management_cashin') {
                $cashin = $managementCashin;
            } elseif ($category === 'expense_adjustment_cashin') {
                $cashin = $expenseAdjCashin;
            } elseif ($category === 'procurement_returns_cashin') {
                $cashin = $procurementReturns;
            }

            $netAmount = $cashin - $cashOut;

            if ($category === 'all') {
                $runningBalance += $netAmount;
            }

            $totals['landshare_payments'] += $landsharePayments;
            $totals['registration_payments'] += $registrationPayments;
            $totals['emi_payments'] += $emiPayments;
            $totals['booking_money'] += $bookingMoney;
            $totals['service_fee'] += $serviceFee;
            $totals['management_cashin'] += $managementCashin;
            $totals['development_charge'] += $developmentCharge;
            $totals['shop_service_charge'] += $shopServiceCharge;
            $totals['expense_adjustment_cashin'] += $expenseAdjCashin;
            $totals['procurement_returns_cashin'] += $procurementReturns;
            $totals['discount'] += $discount;
            $totals['cash_out'] += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', $current),
                'year' => date('Y', $current),
                'month_num' => date('m', $current),
                'booking_money' => $bookingMoney,
                'registration' => $registrationPayments,
                'registration_payments' => $registrationPayments,
                'emi_payments' => $emiPayments,
                'landshare_payments' => $landsharePayments,
                'landshare' => $landsharePayments,
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin,
                'development_charge' => $developmentCharge,
                'shop_service_charge' => $shopServiceCharge,
                'expense_adjustment_cashin' => $expenseAdjCashin,
                'procurement_returns_cashin' => $procurementReturns,
                'discount' => $discount,
                'total_cashin' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $category === 'all' ? $runningBalance : $cashin,
                'total_revenue' => $cashin
            ];

            $current = strtotime('+1 month', $current);
        }

        $totalCashin = 0;
        $totalRevenue = 0;

        if ($category === 'all' || $category === 'total_cashin') {
            $totalCashin = $totals['landshare_payments'] + $totals['registration_payments'] +
                $totals['booking_money'] + $totals['emi_payments'] + $totals['management_cashin'] +
                $totals['development_charge'] + $totals['shop_service_charge'];
            $totalRevenue = $totals['landshare_payments'] + $totals['booking_money'] +
                $totals['service_fee'] + $totals['development_charge'] + $totals['registration_payments'];
        } elseif ($category === 'development_charge') {
            $totalCashin = $totals['development_charge'];
            $totalRevenue = $totals['development_charge'];
        } elseif ($category === 'emi') {
            $totalCashin = $totals['emi_payments'];
            $totalRevenue = $totals['emi_payments'];
        } elseif ($category === 'landshare') {
            $totalCashin = $totals['landshare_payments'];
            $totalRevenue = $totals['landshare_payments'];
        } elseif ($category === 'registration') {
            $totalCashin = $totals['registration_payments'];
            $totalRevenue = $totals['registration_payments'];
        } elseif ($category === 'service_fee') {
            $totalCashin = $totals['service_fee'];
            $totalRevenue = $totals['service_fee'];
        } elseif ($category === 'booking_money') {
            $totalCashin = $totals['booking_money'];
            $totalRevenue = $totals['booking_money'];
        } elseif ($category === 'management_cashin') {
            $totalCashin = $totals['management_cashin'];
            $totalRevenue = $totals['management_cashin'];
        } elseif ($category === 'expense_adjustment_cashin') {
            $totalCashin = $totals['expense_adjustment_cashin'];
            $totalRevenue = $totals['expense_adjustment_cashin'];
        } elseif ($category === 'procurement_returns_cashin') {
            $totalCashin = $totals['procurement_returns_cashin'];
            $totalRevenue = $totals['procurement_returns_cashin'];
        }

        return [
            'period' => 'Custom Range (Monthly)',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_booking_money' => $totals['booking_money'],
                'total_registration_payments' => $totals['registration_payments'],
                'total_emi_payments' => $totals['emi_payments'],
                'total_landshare_payments' => $totals['landshare_payments'],
                'total_landshare' => $totals['landshare_payments'],
                'total_service_fee' => $totals['service_fee'],
                'total_shop_service_charge' => $totals['shop_service_charge'],
                'total_management_cashin' => $totals['management_cashin'],
                'total_development_charge' => $totals['development_charge'],
                'total_expense_adjustment_cashin' => $totals['expense_adjustment_cashin'],
                'total_procurement_returns_cashin' => $totals['procurement_returns_cashin'],
                'total_discount' => $totals['discount'],
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totals['cash_out'],
                'net_balance' => $totalCashin - $totals['cash_out'],
                'final_balance' => $category === 'all' ? $runningBalance : $totalCashin
            ]
        ];
    }

    /**
     * Helper: Get opening balance before a specific date (100% consistent with daily/monthly running balance)
     */
    private function getOpeningBalance($startDate, $projectId = null, $category = 'all')
    {
        if (!$startDate) {
            return 0.00;
        }

        $prevDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $epoch = '1970-01-01';

        $landsharePayments = $this->getMonthlyPaymentByType('land_share', $epoch, $prevDate, $projectId);
        $registrationPayments = $this->getMonthlyPaymentByType('registration', $epoch, $prevDate, $projectId);
        $emiPayments = $this->getMonthlyPaymentByType('emi', $epoch, $prevDate, $projectId);
        $totalBookingMoney = $this->getMonthlyPaymentByType('booking_money', $epoch, $prevDate, $projectId);
        $developmentCharge = $this->getMonthlyPaymentByType('development_charge', $epoch, $prevDate, $projectId);
        $shopServiceCharge = $this->getMonthlyPaymentByType('shop_service_charge', $epoch, $prevDate, $projectId);
        $totalManagementCashin = $this->getMonthlyManagementCashin($epoch, $prevDate, $projectId);

        $openingCashin = $landsharePayments + $registrationPayments + $totalBookingMoney +
            $emiPayments + $developmentCharge + $shopServiceCharge + $totalManagementCashin;

        $totalCashOut = $this->getOpeningCashOutTotal($startDate, $projectId, $category);

        return $openingCashin - $totalCashOut;
    }

    private function getTotalPaymentByTypeBeforeDate($paymentType, $date, $projectId = null)
    {
        $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
        return $this->getMonthlyPaymentByType($paymentType, '1970-01-01', $prevDate, $projectId);
    }

    private function getTotalDiscountBeforeDate($date, $projectId = null)
    {
        $prevDate = date('Y-m-d', strtotime($date . ' -1 day'));
        return $this->getMonthlyDiscount('1970-01-01', $prevDate, $projectId);
    }

    /**
     * Helper: Get opening cashout total before $startDate
     */
    private function getOpeningCashOutTotal($startDate, $projectId = null, $category = 'all')
    {
        if (!$startDate) {
            return 0.00;
        }
        $prevDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
        $epoch = '1970-01-01';

        $grossCashOut = $this->getMonthlyCashOut($epoch, $prevDate, $category, $projectId);
        if ($category === 'all' || $category === 'cashout') {
            $expenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($epoch, $prevDate, $projectId);
            $procurementReturns = $this->getTotalProcurementReturnsCashin($epoch, $prevDate, $projectId);
            $grossCashOut -= ($expenseAdjCashin + $procurementReturns);
        }
        return $grossCashOut;
    }

    /**
     * Helper: Get single-day cash-out total
     */
    private function getDailyCashOut($date, $category = 'all', $projectId = null)
    {
        $data = $this->getCashOutData($date, $date, $category, $projectId);
        return (float)($data[$date] ?? 0);
    }

    /**
     * Helper: Get monthly cash-out total (uses unified getCashOutData so daily and monthly sums match 100%)
     */
    private function getMonthlyCashOut($startDate, $endDate, $category = 'all', $projectId = null)
    {
        if ($projectId === '') {
            $projectId = null;
        }

        if ($category === 'vendor_payment') {
            return $this->getMonthlyVendorPayments($startDate, $endDate, $projectId);
        }
        if ($category === 'contractor_bill') {
            return $this->getMonthlyContractorBills($startDate, $endDate, $projectId);
        }

        $dailyMap = $this->getCashOutData($startDate, $endDate, $category, $projectId);
        return (float)array_sum($dailyMap);
    }

    /**
     * Helper: Get monthly utilities cashout from cashout_requests
     */
    private function getMonthlyUtilitiesCashOut($startDate, $endDate, $projectId = null)
    {
        $dailyMap = $this->getUtilitiesCashOutData($startDate, $endDate, $projectId);
        return (float)array_sum($dailyMap);
    }

    /**
     * Get overall statistics with period filter
     */
    public function getOverallStatistics($periodType = 'all_time', $year = null, $month = null, $startDate = null, $endDate = null, $projectId = null, $category = 'all')
    {
        if ($periodType === 'monthly' && $year && $month) {
            $monthPadded = sprintf("%02d", $month);
            $startDate = "$year-$monthPadded-01";
            $endDate = date('Y-m-t', strtotime($startDate));
        } elseif ($periodType === 'yearly' && $year) {
            $startDate = "$year-01-01";
            $endDate = "$year-12-31";
        } elseif ($periodType === 'custom' && $startDate && $endDate) {
            // Use provided dates
        } else {
            $startDate = $this->getEarliestTransactionDate($projectId);
            $endDate = $this->getLatestTransactionDate($projectId);
        }

        $revenueData = $this->calculateRevenue($startDate, $endDate, $projectId);
        $cashOutData = $this->getCashOutForPeriod($startDate, $endDate, $category, $projectId);
        $totalCashOut = $cashOutData['total'] ?? 0;

        return [
            'total_booking_money' => $revenueData['summary']['total_booking_money'],
            'total_landshare_payments' => $revenueData['summary']['total_landshare_payments'],
            'total_registration_payments' => $revenueData['summary']['total_registration_payments'],
            'total_emi_payments' => $revenueData['summary']['total_emi_payments'],
            'total_service_fee' => $revenueData['summary']['total_service_fee'],
            'total_development_charge' => $revenueData['summary']['total_development_charge'],
            'total_management_cashin' => $revenueData['summary']['total_management_cashin'],
            'total_discount' => $revenueData['summary']['total_discount_from_bookings'],
            'total_cashin' => $revenueData['summary']['total_cashin'],
            'total_revenue' => $revenueData['summary']['total_revenue'],
            'total_cash_out' => $totalCashOut,
            'net_balance' => $revenueData['summary']['total_cashin'] - $totalCashOut
        ];
    }

    private function getCashOutForPeriod($startDate, $endDate, $category = 'all', $projectId = null)
    {
        if (!$startDate) {
            $startDate = $this->getEarliestTransactionDate($projectId);
        }
        if (!$endDate) {
            $endDate = $this->getLatestTransactionDate($projectId);
        }

        $total = $this->getMonthlyCashOut($startDate, $endDate, $category, $projectId);
        if ($category === 'all' || $category === 'cashout') {
            $expenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($startDate, $endDate, $projectId);
            $procurementReturns = $this->getTotalProcurementReturnsCashin($startDate, $endDate, $projectId);
            $total -= ($expenseAdjCashin + $procurementReturns);
        }

        return ['total' => (float)$total];
    }
    public function getProjects()
    {
        $query = "
            SELECT DISTINCT p.id, p.name 
            FROM projects p 
            INNER JOIN journals j ON j.project_id = p.id 
            WHERE p.deleted_at IS NULL 
            ORDER BY p.name ASC
        ";
        $res = $this->db->query($query);
        $projects = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $projects[] = $row;
            }
        }
        if (empty($projects)) {
            $res2 = $this->db->query("SELECT DISTINCT p.id, p.name FROM projects p WHERE p.deleted_at IS NULL ORDER BY p.name ASC");
            if ($res2) {
                while ($row = $res2->fetch_assoc()) {
                    $projects[] = $row;
                }
            }
        }
        return $projects;
    }

    public function getProjectsWithCashout($category = 'cashout')
    {
        $query = "
        SELECT DISTINCT 
            p.id,
            p.name,
            p.location_id,
            p.community_id
        FROM projects p
        LEFT JOIN cashout_requests cr ON p.id = cr.project_id
        LEFT JOIN cashout_payments cp ON p.id = cp.project_id
        WHERE p.deleted_at IS NULL
        AND (cr.id IS NOT NULL OR cp.id IS NOT NULL)
        ORDER BY p.name
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }

        return $projects;
    }

    // Also add this method to get all projects (for dropdown):
    public function getAllProjects()
    {
        $query = "
        SELECT id, name 
        FROM projects 
        WHERE deleted_at IS NULL 
        ORDER BY name
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }

        return $projects;
    }
    private function getUtilitiesCashOutForPeriod($startDate, $endDate, $projectId = null)
    {
        return ['total' => $this->getMonthlyUtilitiesCashOut($startDate, $endDate, $projectId)];
    }

    /**
     * Helper: Get category ID by name
     */
    private function getCategoryIdByName($categoryName)
    {
        $categoryMap = [
            'cashout' => null,
            'landshare' => null,
            'service_fee' => null,
            'emi' => null,
            'registration' => null,
            'booking_money' => null,
            'management_cashin' => null,
            'utilities' => 1,
            'other' => null
        ];

        return $categoryMap[$categoryName] ?? null;
    }

    // =========================================================================
    // VENDOR PAYMENT & CONTRACTOR BILL METHODS
    // =========================================================================

    /**
     * Get daily vendor payments from con_financial_transactions
     */
    private function getDailyVendorPayments($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(cft.transaction_date) as transaction_date,
                SUM(cft.amount_paid) as total_amount
            FROM con_financial_transactions cft
            WHERE cft.ref_type = 'vendor_payment'
            AND DATE(cft.transaction_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (cft.project_id IS NULL OR cft.project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND cft.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " GROUP BY DATE(cft.transaction_date)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    /**
     * Get daily contractor bills from con_financial_transactions
     */
    private function getDailyContractorBills($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(cft.transaction_date) as transaction_date,
                SUM(cft.amount_paid) as total_amount
            FROM con_financial_transactions cft
            WHERE cft.ref_type = 'contractor_bill'
            AND DATE(cft.transaction_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (cft.project_id IS NULL OR cft.project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND cft.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " GROUP BY DATE(cft.transaction_date)";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[$row['transaction_date']] = (float)$row['total_amount'];
        }
        return $data;
    }

    /**
     * Get single-day vendor payment total
     */
    private function getDailyVendorPaymentSingle($date, $projectId = null)
    {
        return $this->getMonthlyVendorPayments($date, $date, $projectId);
    }

    /**
     * Get single-day contractor bill total
     */
    private function getDailyContractorBillSingle($date, $projectId = null)
    {
        return $this->getMonthlyContractorBills($date, $date, $projectId);
    }

    /**
     * Get monthly vendor payment total
     */
    private function getMonthlyVendorPayments($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(amount_paid), 0) as total
            FROM con_financial_transactions
            WHERE ref_type = 'vendor_payment'
            AND DATE(transaction_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (project_id IS NULL OR project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get monthly contractor bill total
     */
    private function getMonthlyContractorBills($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(amount_paid), 0) as total
            FROM con_financial_transactions
            WHERE ref_type = 'contractor_bill'
            AND DATE(transaction_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (project_id IS NULL OR project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) return 0.00;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get vendor payment details grouped by date for breakdown view
     */
    public function getVendorPaymentDetails($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(cft.transaction_date) as payment_date,
                cft.amount_paid as amount,
                cft.payment_method,
                cft.reference_number as transaction_ref,
                cft.payment_type,
                cft.notes,
                p.name as project_name,
                COALESCE(NULLIF(TRIM(v.company_name), ''), v.name) as vendor_name,
                vpr.payment_type as vpr_payment_type,
                u.full_name as processed_by_name
            FROM con_financial_transactions cft
            LEFT JOIN projects p ON cft.project_id = p.id
            LEFT JOIN vendor_payment_requests vpr ON cft.ref_id = vpr.id
            LEFT JOIN vendors v ON vpr.vendor_id = v.id
            LEFT JOIN users u ON cft.processed_by = u.id
            WHERE cft.ref_type = 'vendor_payment'
            AND DATE(cft.transaction_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (cft.project_id IS NULL OR cft.project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND cft.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " ORDER BY cft.transaction_date ASC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $date = $row['payment_date'];
            if (!isset($payments[$date])) {
                $payments[$date] = [];
            }
            $payments[$date][] = $row;
        }

        return $payments;
    }

    /**
     * Get contractor bill details grouped by date for breakdown view
     */
    public function getContractorBillDetails($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                DATE(cft.transaction_date) as payment_date,
                cft.amount_paid as amount,
                cft.payment_method,
                cft.reference_number as transaction_ref,
                cft.payment_type,
                cft.notes,
                p.name as project_name,
                oo.name as contractor_name,
                cbr.work_description as bill_type,
                u.full_name as processed_by_name
            FROM con_financial_transactions cft
            LEFT JOIN projects p ON cft.project_id = p.id
            LEFT JOIN contractor_bill_requests cbr ON cft.ref_id = cbr.id
            LEFT JOIN contractors oo ON cbr.operator_id = oo.id
            LEFT JOIN users u ON cft.processed_by = u.id
            WHERE cft.ref_type = 'contractor_bill'
            AND DATE(cft.transaction_date) BETWEEN ? AND ?
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND (cft.project_id IS NULL OR cft.project_id = 0)";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND cft.project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        }

        $query .= " ORDER BY cft.transaction_date ASC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $payments = [];
        while ($row = $result->fetch_assoc()) {
            $date = $row['payment_date'];
            if (!isset($payments[$date])) {
                $payments[$date] = [];
            }
            $payments[$date][] = $row;
        }

        return $payments;
    }

    /**
     * Get detailed utilities transactions for view
     */
    public function getUtilitiesTransactions($startDate, $endDate, $projectId = null)
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
        ";

        $params = [$startDate, $endDate];
        $types = "ss";

        if ($this->isHeadOfficeOnly($projectId)) {
            $query .= " AND COALESCE(cr.project_id, cp.project_id, 0) = 0";
        } elseif ($this->isSpecificProject($projectId)) {
            $query .= " AND (cr.project_id = ? OR cp.project_id = ?)";
            $pid = (int)$projectId;
            $params[] = $pid;
            $params[] = $pid;
            $types .= "ii";
        }

        $query .= " ORDER BY cp.payment_date DESC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }

        return $transactions;
    }

    /**
     * Get detailed Client Receipts grouped by payment_date (includes client_name, project_name, flat_name)
     */
    public function getClientPaymentDetails($startDate, $endDate, $projectId = null)
    {
        if ($this->isHeadOfficeOnly($projectId)) {
            return ['by_date' => [], 'by_month' => []];
        }

        $query = "
            SELECT 
                lp.id as payment_id,
                lp.payment_date,
                lp.amount,
                lp.payment_type,
                lp.payment_method,
                lp.transaction_ref,
                l.full_name as client_name,
                p.name as project_name,
                f.flat_name
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            INNER JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            WHERE DATE(lp.payment_date) BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
        ";

        if ($this->isSpecificProject($projectId)) {
            $query .= " AND lb.project_id = ?";
        }

        $query .= " ORDER BY lp.payment_date DESC, lp.id DESC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return ['by_date' => [], 'by_month' => []];

        if ($this->isSpecificProject($projectId)) {
            $pid = (int)$projectId;
            $stmt->bind_param("ssi", $startDate, $endDate, $pid);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $grouped = [
            'by_date' => [],
            'by_month' => []
        ];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $date = $row['payment_date'];
                $month = substr($date, 0, 7);

                if (!isset($grouped['by_date'][$date])) {
                    $grouped['by_date'][$date] = [];
                }
                $grouped['by_date'][$date][] = $row;

                if (!isset($grouped['by_month'][$month])) {
                    $grouped['by_month'][$month] = [];
                }
                $grouped['by_month'][$month][] = $row;
            }
        }

        return $grouped;
    }
}



