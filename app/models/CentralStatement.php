<?php
class CentralStatement extends Model
{
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }
    /**
     * Get total management cashin
     */
    private function getTotalManagementCashin($startDate, $endDate)
    {
        $query = "
        SELECT COALESCE(SUM(amount), 0) as total
        FROM management_cashin
        WHERE payment_status = 'paid'
        AND transaction_date BETWEEN ? AND ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get total expense adjustment cashin (refunds received)
     */
    private function getTotalExpenseAdjustmentCashin($startDate, $endDate)
    {
        $query = "
        SELECT COALESCE(SUM(at.amount), 0) as total
        FROM adjustment_transactions at
        JOIN expense_adjustments ea ON at.adjustment_id = ea.id
        WHERE at.transaction_type = 'cashin'
        AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
        AND at.payment_date BETWEEN ? AND ?
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get total procurement returns cashin (Office Procurement Assets returned money)
     */
    private function getTotalProcurementReturnsCashin($startDate, $endDate)
    {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(paid_amount), 0) as total
            FROM project_extra_assets
            WHERE overall_status = 'purchased'
            AND purchase_confirmed_at BETWEEN ? AND ?
        ");
        if (!$stmt) {
            return 0.00;
        }

        $endDateTime = $endDate . ' 23:59:59';
        $startDateTime = $startDate . ' 00:00:00';
        $stmt->bind_param("ss", $startDateTime, $endDateTime);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Get management cashin breakdown
     */
    private function getManagementCashinBreakdown($startDate, $endDate)
    {
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
        AND transaction_date BETWEEN ? AND ?
        ORDER BY transaction_date DESC
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ss", $startDate, $endDate);
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
        $totalManagementCashin = $this->getTotalManagementCashin($startDate, $endDate); // ADDED

        // Get individual payment type totals for cashin calculation
        $landsharePayments = $this->getTotalPaymentByType('land_share', $startDate, $endDate, $projectId);
        $registrationPayments = $this->getTotalPaymentByType('registration', $startDate, $endDate, $projectId);
        $emiPayments = $this->getTotalPaymentByType('emi', $startDate, $endDate, $projectId);
        $bookingMoneyPayments = $this->getTotalPaymentByType('booking_money', $startDate, $endDate, $projectId);
        $developmentChargePayments = $this->getTotalPaymentByType('development_charge', $startDate, $endDate, $projectId); // ADDED
        $shopServiceChargePayments = $this->getTotalPaymentByType('shop_service_charge', $startDate, $endDate, $projectId);

        // Get expense adjustment cashin total
        $totalExpenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($startDate, $endDate);

        // Get procurement returns cashin total
        $totalProcurementReturnsCashin = $this->getTotalProcurementReturnsCashin($startDate, $endDate);

        // Calculate cashin according to formula (INCLUDES management_cashin)
        $totalCashin = $landsharePayments + $registrationPayments +
            $totalBookingMoney + $emiPayments + $totalManagementCashin + $developmentChargePayments + $shopServiceChargePayments;

        // Calculate revenue â€” includes land share, booking money, service fee, development charges, AND registration
        $totalRevenue = $totalLandShare + $totalBookingMoney + $totalServiceFee + $developmentChargePayments + $registrationPayments; // registration is now revenue

        // Additional breakdowns
        $paymentBreakdown = $this->getPaymentBreakdown($startDate, $endDate, $projectId);
        $emiBreakdown = $this->getEMIBreakdown($startDate, $endDate, $projectId);
        $landShareBreakdown = $this->getLandShareBreakdown($startDate, $endDate, $projectId);
        $managementCashinBreakdown = $this->getManagementCashinBreakdown($startDate, $endDate); // ADDED

        return [
            'summary' => [
                'total_landshare_from_payments' => $totalLandShare,
                'total_landshare_payments' => $landsharePayments,
                'total_registration_payments' => $registrationPayments,
                'total_emi_payments' => $emiPayments,
                'total_booking_money_payments' => $bookingMoneyPayments,
                'total_discount_from_bookings' => $totalDiscount,
                'total_service_fee' => $totalServiceFee,
                'total_management_cashin' => $totalManagementCashin, // ADDED
                'total_development_charge' => $developmentChargePayments, // ADDED
                'total_expense_adjustment_cashin' => $totalExpenseAdjCashin, // NEW
                'total_procurement_returns_cashin' => $totalProcurementReturnsCashin, // NEW
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue, // Revenue stays the same
                'revenue_percentage' => $totalCashin > 0 ? ($totalRevenue / $totalCashin) * 100 : 0
            ],
            'payment_breakdown' => $paymentBreakdown,
            'emi_breakdown' => $emiBreakdown,
            'landshare_breakdown' => $landShareBreakdown,
            'management_cashin_breakdown' => $managementCashinBreakdown, // ADDED
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ];
    }

    private function getTotalPaymentByType($paymentType, $startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(lp.amount), 0) as total_amount
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_date BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            AND lp.payment_type = ?
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("sssi", $startDate, $endDate, $paymentType, $projectId);
        } else {
            $stmt->bind_param("sss", $startDate, $endDate, $paymentType);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_amount'] ?? 0);
    }

    private function getTotalLandShare($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(lp.land_share_amount), 0) as total_landshare
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_date BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
            AND lp.payment_type = 'land_share'
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
        return (float)($row['total_landshare'] ?? 0);
    }


    private function getTotalBookingMoney($startDate, $endDate, $projectId = null)
    {
        $query = "
        SELECT COALESCE(SUM(lp.amount), 0) as total_booking_money
        FROM lead_payments lp
        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE lp.payment_date BETWEEN ? AND ?
        AND lp.payment_status IN ('paid','partially_paid')
        AND lp.verification_status = 'verified'
        AND lp.payment_type = 'booking_money'
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
        return (float)($row['total_booking_money'] ?? 0);
    }

    private function getTotalServiceFee($startDate, $endDate, $projectId = null)
    {
        if ($projectId) {
            $query = "
                SELECT COALESCE(SUM(es.service_fee), 0) as total_service_fee
                FROM emi_schedules es
                INNER JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE es.due_date BETWEEN ? AND ?
                AND es.status = 'paid'
                AND lb.project_id = ?
            ";
        } else {
            $query = "
                SELECT COALESCE(SUM(service_fee), 0) as total_service_fee
                FROM emi_schedules
                WHERE due_date BETWEEN ? AND ?
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
        $emiServiceFee = (float)($row['total_service_fee'] ?? 0);
        
        $shopServiceCharge = $this->getTotalPaymentByType('shop_service_charge', $startDate, $endDate, $projectId);
        
        return $emiServiceFee + $shopServiceCharge;
    }

    private function getPaymentBreakdown($startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT 
                lp.payment_type,
                COALESCE(SUM(lp.amount), 0) as total_amount,
                COUNT(*) as payment_count
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_date BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $query .= " GROUP BY lp.payment_type ORDER BY total_amount DESC";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
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
        if ($projectId) {
            $query = "
                SELECT 
                    es.lead_booking_id,
                    COALESCE(SUM(es.emi_amount), 0) as total_emi,
                    COALESCE(SUM(es.service_fee), 0) as total_service_fee,
                    COUNT(*) as emi_count
                FROM emi_schedules es
                INNER JOIN lead_bookings lb ON es.lead_booking_id = lb.id
                WHERE es.due_date BETWEEN ? AND ?
                AND es.status = 'paid'
                AND lb.project_id = ?
                GROUP BY es.lead_booking_id
                ORDER BY total_emi DESC
            ";
        } else {
            $query = "
                SELECT 
                    lead_booking_id,
                    COALESCE(SUM(emi_amount), 0) as total_emi,
                    COALESCE(SUM(service_fee), 0) as total_service_fee,
                    COUNT(*) as emi_count
                FROM emi_schedules
                WHERE due_date BETWEEN ? AND ?
                AND status = 'paid'
                GROUP BY lead_booking_id
                ORDER BY total_emi DESC
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
            WHERE lp.payment_date BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.payment_type = 'land_share'
            AND lp.land_share_amount > 0
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $query .= " ORDER BY lp.payment_date DESC";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
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
        $query = "
            SELECT COALESCE(SUM(discount_amount), 0) as total_discount
            FROM lead_bookings
            WHERE booking_date BETWEEN ? AND ?
            AND booking_status = 'booked'
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

        // Get opening balance
        $openingBalance = $this->getOpeningBalance($startDate, $projectId, $category);

        // Get payment breakdown by type
        $landsharePaymentsData = $this->getDailyPaymentByType('land_share', $startDate, $endDate, $projectId);
        $registrationPaymentsData = $this->getDailyPaymentByType('registration', $startDate, $endDate, $projectId);
        $emiPaymentsData = $this->getDailyPaymentByType('emi', $startDate, $endDate, $projectId);
        $bookingMoneyData = $this->getDailyBookingMoney($startDate, $endDate, $projectId);
        $serviceFeeData = $this->getDailyServiceFees($startDate, $endDate, $projectId);
        $shopServiceChargeData = $this->getDailyPaymentByType('shop_service_charge', $startDate, $endDate, $projectId); // ADDED
        $managementCashinData = $this->getDailyManagementCashins($startDate, $endDate); // Already exists
        $developmentChargeData = $this->getDailyPaymentByType('development_charge', $startDate, $endDate, $projectId);
        $expenseAdjCashinData = $this->getDailyExpenseAdjustmentsCashin($startDate, $endDate); // NEW
        $procurementReturnsCashinData = $this->getDailyProcurementReturnsCashin($startDate, $endDate); // NEW
        $vendorPaymentData = $this->getDailyVendorPayments($startDate, $endDate, $projectId);
        $contractorBillData = $this->getDailyContractorBills($startDate, $endDate, $projectId);
        $discountData = $this->getDailyDiscounts($startDate, $endDate, $projectId);
        $cashOutData = $this->getCashOutData($startDate, $endDate, $category, $projectId);

        // Build daily statement
        $dailyStatement = [];
        $runningBalance = $openingBalance;

        // Initialize totals
        $totals = [
            'landshare_payments' => 0,
            'registration_payments' => 0,
            'emi_payments' => 0,
            'booking_money' => 0,
            'service_fee' => 0,
            'management_cashin' => 0,
            'development_charge' => 0,
            'shop_service_charge' => 0, // ADDED
            'expense_adjustment_cashin' => 0, // NEW
            'procurement_returns_cashin' => 0, // NEW
            'vendor_payment' => 0,
            'contractor_bill' => 0,
            'discount' => 0,
            'cash_out' => 0
        ];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dayPadded = sprintf("%02d", $day);
            $date = "$year-$monthPadded-$dayPadded";

            // Get daily amounts
            $landsharePayments = $landsharePaymentsData[$date] ?? 0;
            $registrationPayments = $registrationPaymentsData[$date] ?? 0;
            $emiPayments = $emiPaymentsData[$date] ?? 0;
            $bookingMoney = $bookingMoneyData[$date] ?? 0;
            $shopServiceCharge = $shopServiceChargeData[$date] ?? 0; // ADDED
            $serviceFee = ($serviceFeeData[$date] ?? 0) + $shopServiceCharge; // COMBINED
            $managementCashin = $managementCashinData[$date] ?? 0;
            $developmentCharge = $developmentChargeData[$date] ?? 0;
            $expenseAdjCashin = $expenseAdjCashinData[$date] ?? 0; // NEW
            $procurementReturnsCashin = $procurementReturnsCashinData[$date] ?? 0; // NEW
            $vendorPayment = $vendorPaymentData[$date] ?? 0;
            $contractorBill = $contractorBillData[$date] ?? 0;
            $discount = $discountData[$date] ?? 0;

            // Calculate cashin based on selected category
            $cashin = 0;
            if ($category === 'all' || $category === 'total_cashin') {
                // Total cashin includes all revenue sources
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
// updated 
            // For vendor_payment and contractor_bill categories, use their specific data as cash_out
            $cashOut = 0;
            if ($category === 'vendor_payment') {
                $cashOut = $vendorPayment;
            } elseif ($category === 'contractor_bill') {
                $cashOut = $contractorBill;
            } else {
                $cashOut = $cashOutData[$date] ?? 0;
            }

            if ($category === 'all' || $category === 'total_cashin' || $category === 'cashout') {
                $cashOut -= ($expenseAdjCashin + $procurementReturnsCashin);
            }

            $netAmount = $cashin - $cashOut;

            // Only update running balance for 'all' category
            if ($category === 'all') {
                $runningBalance += $netAmount;
            }

            // Accumulate totals
            $totals['landshare_payments'] += $landsharePayments;
            $totals['registration_payments'] += $registrationPayments;
            $totals['emi_payments'] += $emiPayments;
            $totals['booking_money'] += $bookingMoney;
            $totals['service_fee'] += $serviceFee;
            $totals['management_cashin'] += $managementCashin; // Accumulate
            $totals['development_charge'] += $developmentCharge;
            $totals['shop_service_charge'] += $shopServiceCharge; // ADDED
            $totals['expense_adjustment_cashin'] += $expenseAdjCashin; // NEW
            $totals['procurement_returns_cashin'] += $procurementReturnsCashin; // NEW
            $totals['vendor_payment'] += $vendorPayment;
            $totals['contractor_bill'] += $contractorBill;
            $totals['discount'] += $discount;
            $totals['cash_out'] += $cashOut;

            $dailyStatement[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', strtotime($date)),
                'day_name' => date('l', strtotime($date)),
                'booking_money' => $bookingMoney,
                'registration' => $registrationPayments,
                'emi_payments' => $emiPayments,
                'landshare_payments' => $landsharePayments,
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin, // Show in breakdown
                'development_charge' => $developmentCharge,
                'shop_service_charge' => $shopServiceCharge, // ADDED
                'expense_adjustment_cashin' => $expenseAdjCashin, // NEW
                'procurement_returns_cashin' => $procurementReturnsCashin, // NEW
                'discount' => $discount,
                'total_cashin' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $category === 'all' ? $runningBalance : $cashin,
            ];
        }

        // Calculate final totals based on category
        $totalCashin = 0;
        $totalRevenue = 0;

        if ($category === 'all' || $category === 'total_cashin') {
            // Include management_cashin and expense adjustment cashin in total cashin
            $totalCashin = $totals['landshare_payments'] + $totals['registration_payments'] +
                $totals['booking_money'] + $totals['emi_payments'] + $totals['management_cashin'] + $totals['development_charge'] + $totals['shop_service_charge'] + $totals['expense_adjustment_cashin']; // ADDED expense_adjustment_cashin

            // Include registration payments in revenue
            $totalRevenue = $totals['landshare_payments'] + $totals['booking_money'] + $totals['service_fee'] + $totals['development_charge'] + $totals['registration_payments']; // service_fee already includes shop_service_charge
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
        } elseif ($category === 'vendor_payment') {
            $totalCashin = 0;
            $totalRevenue = 0;
        } elseif ($category === 'contractor_bill') {
            $totalCashin = 0;
            $totalRevenue = 0;
        }

        return [
            'period' => date('F Y', strtotime($startDate)),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'daily_data' => $dailyStatement,
            'summary' => [
                'total_booking_money' => $totals['booking_money'],
                'total_registration_payments' => $totals['registration_payments'],
                'total_emi_payments' => $totals['emi_payments'],
                'total_landshare_payments' => $totals['landshare_payments'],
                'total_service_fee' => $totals['service_fee'],
                'total_management_cashin' => $totals['management_cashin'], // Added to summary
                'total_development_charge' => $totals['development_charge'],
                'total_expense_adjustment_cashin' => $totals['expense_adjustment_cashin'], // NEW
                'total_discount' => $totals['discount'],
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue, // Revenue stays the same - excludes adjustments
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
        // For total_cashin category, we need to return 0 for individual types
        // since total_cashin is calculated separately in the main method
        if ($selectedCategory === 'total_cashin') {
            return 0; // total_cashin is calculated in the main method, not from dataArray
        }

        if ($selectedCategory === 'all' || $selectedCategory === $categoryType) {
            return $dataArray[$date] ?? 0;
        }
        return 0;
    }
    private function getDailyPaymentByType($paymentType, $startDate, $endDate, $projectId = null)
    {
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

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $query .= " GROUP BY DATE(lp.payment_date)";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("sssi", $startDate, $endDate, $paymentType, $projectId);
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
        $query = "
            SELECT 
                DATE(booking_date) as transaction_date,
                SUM(discount_amount) as total_amount
            FROM lead_bookings
            WHERE DATE(booking_date) BETWEEN ? AND ?
            AND booking_status = 'booked'
            AND landshare_status = 'active'
        ";

        if ($projectId) {
            $query .= " AND project_id = ?";
        }

        $query .= " GROUP BY DATE(booking_date)";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
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
    $query = "
        SELECT 
            DATE(lp.payment_date) as transaction_date,
            SUM(lp.amount) as total_amount
        FROM lead_payments lp
        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE DATE(lp.payment_date) BETWEEN ? AND ?
        AND lp.payment_status IN ('paid','partially_paid')
        AND lp.verification_status = 'verified'
        AND lp.payment_type = 'booking_money'
    ";

    if ($projectId) {
        $query .= " AND lb.project_id = ?";
    }

    $query .= " GROUP BY DATE(lp.payment_date)";

    $stmt = $this->db->prepare($query);
    if ($projectId) {
        $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
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

    private function getDailyServiceFees($startDate, $endDate, $projectId = null)
    {
        if ($projectId) {
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
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
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

    private function getDailyManagementCashins($startDate, $endDate)
    {
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
    private function getDailyExpenseAdjustmentsCashin($startDate, $endDate)
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
            GROUP BY DATE(at.payment_date)
        ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getDailyExpenseAdjustmentsCashin: " . $this->db->error);
            return [];
        }
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
     * Get daily procurement returns cashin
     */
    private function getDailyProcurementReturnsCashin($startDate, $endDate)
    {
        $query = "
            SELECT 
                DATE(purchase_confirmed_at) as transaction_date,
                SUM(paid_amount) as total_amount
            FROM project_extra_assets
            WHERE overall_status = 'purchased'
            AND paid_amount > 0
            AND DATE(purchase_confirmed_at) BETWEEN ? AND ?
            GROUP BY DATE(purchase_confirmed_at)
        ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getDailyProcurementReturnsCashin: " . $this->db->error);
            return [];
        }
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
     * Get daily expense adjustment cashout (additional payments)
     */
    private function getDailyExpenseAdjustmentsCashout($startDate, $endDate)
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
            GROUP BY DATE(at.payment_date)
        ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            error_log("Prepare failed in getDailyExpenseAdjustmentsCashout: " . $this->db->error);
            return [];
        }
        $stmt->bind_param("ss", $startDate, $endDate);
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
            cp.office,
            cr.purpose_paid_to,
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

    if ($projectId) {
        $query .= " AND (cr.project_id = ? OR cp.project_id = ?)";
    }

    $query .= " ORDER BY cp.payment_date ASC";

    $stmt = $this->db->prepare($query);
    if ($projectId) {
        $stmt->bind_param("ssii", $startDate, $endDate, $projectId, $projectId);
    } else {
        $stmt->bind_param("ss", $startDate, $endDate);
    }
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
     * Helper: Get cashout data (management_cashout + cashout_request payments) with category filtering
     */
    private function getCashOutData($startDate, $endDate, $category = 'all', $projectId = null)
    {
        if ($category === 'utilities') {
            return $this->getUtilitiesCashOutData($startDate, $endDate, $projectId);
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
        ";

            // Add project filter if specified
            if ($projectId) {
                $cashoutRequestQuery .= " AND (cr.project_id = ? OR cp.project_id = ?)";
                $cashoutRequestParams = [$startDate, $endDate, $projectId, $projectId];
                $cashoutRequestTypes = "ssii";
            } else {
                $cashoutRequestParams = [$startDate, $endDate];
                $cashoutRequestTypes = "ss";
            }

            $cashoutRequestQuery .= " GROUP BY DATE(cp.payment_date)";

            $stmt = $this->db->prepare($cashoutRequestQuery);
            if ($projectId) {
                $stmt->bind_param($cashoutRequestTypes, ...$cashoutRequestParams);
            } else {
                $stmt->bind_param($cashoutRequestTypes, ...$cashoutRequestParams);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $cashoutRequestData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Get salary payments (payslips) - only for 'all' category
        $payslipsData = [];
        if ($category === 'all' || $category === 'cashout') {
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
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $payslipsData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Get vendor payments, contractor bills, and operational fund from con_financial_transactions - only for 'all' category
        $conFinancialData = [];
        if ($category === 'all') {
            $conFinQuery = "
            SELECT 
                DATE(transaction_date) as transaction_date,
                SUM(amount_paid) as total_amount
            FROM con_financial_transactions
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND ref_type IN ('vendor_payment', 'contractor_bill', 'operational_fund')
        ";

            if ($projectId) {
                $conFinQuery .= " AND project_id = ?";
            }

            $conFinQuery .= " GROUP BY DATE(transaction_date)";

            $stmt = $this->db->prepare($conFinQuery);
            if ($projectId) {
                $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $conFinancialData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Get expense adjustment cashout transactions - only for 'all' category
        $adjCashoutData = [];
        if ($category === 'all' || $category === 'cashout') {
            $adjCashoutQuery = "
            SELECT 
                DATE(at.payment_date) as transaction_date,
                SUM(at.amount) as total_amount
            FROM adjustment_transactions at
            JOIN expense_adjustments ea ON at.adjustment_id = ea.id
            WHERE at.transaction_type = 'cashout'
            AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
            AND DATE(at.payment_date) BETWEEN ? AND ?
        ";

            if ($projectId) {
                $adjCashoutQuery .= " AND ea.project_id = ?";
            }

            $adjCashoutQuery .= " GROUP BY DATE(at.payment_date)";

            $stmt = $this->db->prepare($adjCashoutQuery);
            if ($projectId) {
                $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $adjCashoutData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Get fund requisitions - only for 'all' category or 'assets_repairing'
        $fundReqData = [];
        if ($category === 'all' || $category === 'cashout' || $category === 'assets_repairing' || $category === 'fund_requisitions') {
            $fundReqQuery = "
            SELECT 
                DATE(paid_at) as transaction_date,
                SUM(amount) as total_amount
            FROM fund_requisitions
            WHERE DATE(paid_at) BETWEEN ? AND ?
            AND status = 'paid'
        ";

            if ($category === 'assets_repairing') {
                $fundReqQuery .= " AND category = 'Asset Repair'";
            }

            if ($projectId) {
                $fundReqQuery .= " AND project_id = ?";
            }

            $fundReqQuery .= " GROUP BY DATE(paid_at)";

            $stmt = $this->db->prepare($fundReqQuery);
            if ($projectId) {
                $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $fundReqData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Get land fund requisitions - only for 'all' category
        $landFundReqData = [];
        if ($category === 'all' || $category === 'cashout') {
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

            if ($projectId) {
                $landFundReqQuery .= " AND la.project_id = ?";
            }

            $landFundReqQuery .= " GROUP BY DATE(lfr.accounts_paid_at)";

            $stmt = $this->db->prepare($landFundReqQuery);
            if ($projectId) {
                $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $landFundReqData[$row['transaction_date']] = (float)$row['total_amount'];
            }
        }

        // Get office procurement assets - only for 'all' or 'office_procurement_assets'
        $officeProcAssetsData = [];
        if ($category === 'all' || $category === 'office_procurement_assets') {
            $officeProcAssetsQuery = "
            SELECT 
                DATE(paid_at) as transaction_date,
                SUM(paid_amount) as total_amount
            FROM project_extra_assets
            WHERE DATE(paid_at) BETWEEN ? AND ?
            AND payment_status = 'paid'
        ";

            if ($projectId) {
                $officeProcAssetsQuery .= " AND project_id = ?";
            }

            $officeProcAssetsQuery .= " GROUP BY DATE(paid_at)";

            $stmt = $this->db->prepare($officeProcAssetsQuery);
            if ($projectId) {
                $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
            } else {
                $stmt->bind_param("ss", $startDate, $endDate);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $officeProcAssetsData[$row['transaction_date']] = (float)$row['total_amount'];
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
    ";

        // Add project filter if specified
        if ($projectId) {
            $query .= " AND (cr.project_id = ? OR cp.project_id = ?)";
            $params = [$startDate, $endDate, $projectId, $projectId];
            $types = "ssii";
        } else {
            $params = [$startDate, $endDate];
            $types = "ss";
        }

        $query .= " GROUP BY DATE(cp.payment_date)";

        $stmt = $this->db->prepare($query);
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

        $monthlyData = [];

        // Calculate 6 months back
        $startDate = date('Y-m-01', strtotime("-5 months", strtotime("$endYear-$endMonth-01")));
        $endDate = date('Y-m-t', strtotime("$endYear-$endMonth-01"));

        // Get opening balance before the 6-month period
        $runningBalance = $this->getOpeningBalance($startDate, $projectId, $category);

        // Initialize totals
        $totals = [
            'landshare_payments' => 0,
            'registration_payments' => 0,
            'emi_payments' => 0,
            'booking_money' => 0,
            'service_fee' => 0,
            'management_cashin' => 0,
            'discount' => 0,
            'cash_out' => 0
        ];

        for ($i = 5; $i >= 0; $i--) {
            $date = date('Y-m-01', strtotime("-$i months", strtotime("$endYear-$endMonth-01")));
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            $monthStart = "$year-$month-01";
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            // Get monthly totals
            $landsharePayments = $this->getMonthlyPaymentByType('land_share', $monthStart, $monthEnd, $projectId);
            $registrationPayments = $this->getMonthlyPaymentByType('registration', $monthStart, $monthEnd, $projectId);
            $emiPayments = $this->getMonthlyPaymentByType('emi', $monthStart, $monthEnd, $projectId);
            $bookingMoney = $this->getMonthlyBookingMoney($monthStart, $monthEnd, $projectId);
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
            $discount = $this->getMonthlyDiscount($monthStart, $monthEnd, $projectId);

            // Only get cashout for expense categories, not for revenue categories
            $cashOut = 0;
            if ($category === 'vendor_payment') {
                $cashOut = $this->getMonthlyVendorPayments($monthStart, $monthEnd, $projectId);
            } elseif ($category === 'contractor_bill') {
                $cashOut = $this->getMonthlyContractorBills($monthStart, $monthEnd, $projectId);
            } elseif (in_array($category, ['all', 'cashout', 'utilities', 'other'])) {
                $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category, $projectId);
            }

            // Calculate cashin according to formula
            // CORRECT - adding management_cashin
            $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin;

            $netAmount = $cashin - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totals['landshare_payments'] += $landsharePayments;
            $totals['registration_payments'] += $registrationPayments;
            $totals['emi_payments'] += $emiPayments;
            $totals['booking_money'] += $bookingMoney;
            $totals['service_fee'] += $serviceFee;
            $totals['management_cashin'] += $managementCashin;
            $totals['discount'] += $discount;
            $totals['cash_out'] += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', strtotime($date)),
                'year' => $year,
                'month_num' => $month,
                // Detailed breakdown
                'booking_money' => $bookingMoney,
                'registration_payments' => $registrationPayments,
                'emi_payments' => $emiPayments,
                'landshare_payments' => $landsharePayments, // Correct: actual landshare payments
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin,
                'discount' => $discount,
                'total_cashin' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance,
            ];
        }

        // Calculate final totals
        $totalCashin = $totals['landshare_payments'] + $totals['registration_payments'] +
            $totals['booking_money'] + $totals['emi_payments'] + $totals['management_cashin']; // Include management_cashin in total cashin;

        $totalRevenue = $totals['landshare_payments']  + $totals['booking_money'] + $totals['service_fee'];

        return [
            'period' => 'Last 6 Months',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_landshare_payments' => $totals['landshare_payments'],
                'total_registration_payments' => $totals['registration_payments'],
                'total_emi_payments' => $totals['emi_payments'],
                'total_booking_money' => $totals['booking_money'], // Fixed: Use actual booking money
                'total_service_fee' => $totals['service_fee'],
                'total_management_cashin' => $totals['management_cashin'],
                'total_discount' => $totals['discount'],
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totals['cash_out'],
                'net_balance' => $totalCashin - $totals['cash_out'],
                'final_balance' => $runningBalance,
            ]
        ];
    }

    private function getMonthlyPaymentByType($paymentType, $startDate, $endDate, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(lp.amount), 0) as total_amount
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE lp.payment_date BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.payment_type = ?
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("sssi", $startDate, $endDate, $paymentType, $projectId);
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
        $query = "
            SELECT COALESCE(SUM(discount_amount), 0) as total_discount
            FROM lead_bookings
            WHERE booking_date BETWEEN ? AND ?
            AND booking_status = 'booked'
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
        return (float)($row['total_discount'] ?? 0);
    }

private function getMonthlyBookingMoney($startDate, $endDate, $projectId = null)
{
    $query = "
        SELECT COALESCE(SUM(lp.amount), 0) as total
        FROM lead_payments lp
        INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
        WHERE DATE(lp.payment_date) BETWEEN ? AND ?
        AND lp.payment_status IN ('paid','partially_paid')
        AND lp.payment_type = 'booking_money'
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

        // Initialize totals
        $totals = [
            'landshare_payments' => 0,
            'registration_payments' => 0,
            'emi_payments' => 0,
            'booking_money' => 0,
            'service_fee' => 0,
            'management_cashin' => 0,
            'discount' => 0,
            'cash_out' => 0
        ];

        for ($month = 1; $month <= 12; $month++) {
            $monthStart = sprintf("%s-%02d-01", $year, $month);
            $monthEnd = date('Y-m-t', strtotime($monthStart));

            // Get monthly totals
            $landsharePayments = $this->getMonthlyPaymentByType('land_share', $monthStart, $monthEnd, $projectId);
            $registrationPayments = $this->getMonthlyPaymentByType('registration', $monthStart, $monthEnd, $projectId);
            $emiPayments = $this->getMonthlyPaymentByType('emi', $monthStart, $monthEnd, $projectId);
            $bookingMoney = $this->getMonthlyBookingMoney($monthStart, $monthEnd, $projectId);
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
            $discount = $this->getMonthlyDiscount($monthStart, $monthEnd, $projectId);

            // Only get cashout for expense categories, not for revenue categories
            $cashOut = 0;
            if ($category === 'vendor_payment') {
                $cashOut = $this->getMonthlyVendorPayments($monthStart, $monthEnd, $projectId);
            } elseif ($category === 'contractor_bill') {
                $cashOut = $this->getMonthlyContractorBills($monthStart, $monthEnd, $projectId);
            } elseif (in_array($category, ['all', 'cashout', 'utilities', 'other'])) {
                $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category, $projectId);
            }

            // Calculate cashin according to formula
            $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin;

            $netAmount = $cashin - $cashOut;
            $runningBalance += $netAmount;

            // Accumulate totals
            $totals['landshare_payments'] += $landsharePayments;
            $totals['registration_payments'] += $registrationPayments;
            $totals['emi_payments'] += $emiPayments;
            $totals['booking_money'] += $bookingMoney;
            $totals['service_fee'] += $serviceFee;
            $totals['management_cashin'] += $managementCashin;
            $totals['discount'] += $discount;
            $totals['cash_out'] += $cashOut;

            $monthlyData[] = [
                'month' => date('F Y', strtotime($monthStart)),
                'year' => $year,
                'month_num' => $month,
                // Detailed breakdown
                'booking_money' => $bookingMoney,
                'registration_payments' => $registrationPayments,
                'emi_payments' => $emiPayments,
                'landshare_payments' => $landsharePayments,
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin,
                'discount' => $discount,
                'total_cashin' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $runningBalance,
                'total_revenue' => $cashin
            ];
        }

        // Calculate final totals
        $totalCashin = $totals['landshare_payments'] + $totals['registration_payments'] +
            $totals['booking_money'] + $totals['emi_payments'] + $totals['management_cashin'];

        $totalRevenue = $totals['landshare_payments']  +
            $totals['booking_money'] + $totals['service_fee'];

        return [
            'period' => "Year $year",
            'start_date' => "$year-01-01",
            'end_date' => "$year-12-31",
            'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_booking_money' => $totals['booking_money'],
                'total_registration_payments' => $totals['registration_payments'],
                'total_emi_payments' => $totals['emi_payments'],
                'total_landshare_payments' => $totals['landshare_payments'],
                'total_service_fee' => $totals['service_fee'],
                'total_management_cashin' => $totals['management_cashin'],
                'total_discount' => $totals['discount'],
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totals['cash_out'],
                'net_balance' => $totalCashin - $totals['cash_out'],
                'final_balance' => $runningBalance,
                // Backward compatibility
                'total_landshare' => $totals['landshare_payments']
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

    private function getCustomDailyStatement($startDate, $endDate, $projectId = null, $category = 'all')
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        $dailyData = [];

        // Get opening balance before the period
        $runningBalance = $this->getOpeningBalance($startDate, $projectId, $category);

        // Initialize totals
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

        // Get data for each day
        for ($current = $start; $current <= $end; $current += 86400) {
            $date = date('Y-m-d', $current);

            // Get daily amounts
            $landsharePayments = $this->getDailyPaymentByTypeSingle('land_share', $date, $projectId);
            $registrationPayments = $this->getDailyPaymentByTypeSingle('registration', $date, $projectId);
            $emiPayments = $this->getDailyPaymentByTypeSingle('emi', $date, $projectId);
            $bookingMoney = $this->getDailyPaymentByTypeSingle('booking_money', $date, $projectId);

            $serviceFee = $this->getDailyServiceFeeSingle($date, $projectId);
            $managementCashin = $this->getDailyManagementCashinSingle($date);
            $developmentCharge = $this->getDailyPaymentByTypeSingle('development_charge', $date, $projectId);
            $shopServiceCharge = $this->getDailyPaymentByTypeSingle('shop_service_charge', $date, $projectId);
            $expenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($date, $date);
            $procurementReturns = $this->getTotalProcurementReturnsCashin($date, $date);
            $discount = $this->getDailyDiscountSingle($date, $projectId);
            // Handle vendor_payment and contractor_bill as cashout categories
            $cashOut = 0;
            if ($category === 'vendor_payment') {
                $cashOut = $this->getDailyVendorPaymentSingle($date, $projectId);
            } elseif ($category === 'contractor_bill') {
                $cashOut = $this->getDailyContractorBillSingle($date, $projectId);
            } else {
                $cashOut = $this->getDailyCashOut($date, $category);
            }

            // Deduct returns from cashout
            if ($category === 'all' || $category === 'cashout') {
                $cashOut -= ($expenseAdjCashin + $procurementReturns);
            }

            // Calculate cashin based on selected category
            $cashin = 0;
            if ($category === 'all' || $category === 'total_cashin') {
                // Total cashin includes all revenue sources minus discount
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin + $developmentCharge + $shopServiceCharge;

            } elseif ($category === 'emi') {
                $cashin = $emiPayments;
            } elseif ($category === 'landshare') {
                $cashin = $landsharePayments;
            } elseif ($category === 'registration') {
                $cashin = $registrationPayments;
            } elseif ($category === 'service_fee') {
                $cashin = $serviceFee;
            }  elseif ($category === 'booking_money') {
                $cashin = $bookingMoney;
            } elseif ($category === 'management_cashin') {
                $cashin = $managementCashin;
            }

            $netAmount = $cashin - $cashOut;

            // Only update running balance for 'all' category
            if ($category === 'all') {
                $runningBalance += $netAmount;
            }

            // Accumulate totals
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

            $dailyData[] = [
                'date' => $date,
                'formatted_date' => date('d M, Y', strtotime($date)),
                'day_name' => date('l', strtotime($date)),
                // Detailed breakdown
                'booking_money' => $bookingMoney,
                'registration_payments' => $registrationPayments,
                'emi_payments' => $emiPayments,
                'landshare_payments' => $landsharePayments,
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin,
                'discount' => $discount,
                'total_cashin' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $category === 'all' ? $runningBalance : $cashin,
                // Backward compatibility
                'landshare' => $landsharePayments,
                'total_revenue' => $cashin
            ];
        }

        // Calculate final totals based on category
        $totalCashin = 0;
        $totalRevenue = 0;

        if ($category === 'all' || $category === 'total_cashin') {
            $totalCashin = $totals['landshare_payments'] + $totals['registration_payments'] +
                $totals['booking_money'] + $totals['emi_payments'] + $totals['management_cashin'] +
                $totals['development_charge'] + $totals['shop_service_charge'];
            $totalRevenue = $totals['landshare_payments'] + $totals['booking_money'] + $totals['service_fee'];
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
        }  elseif ($category === 'booking_money') {
            $totalCashin = $totals['booking_money'];
            $totalRevenue = $totals['booking_money'];
        } elseif ($category === 'management_cashin') {
            $totalCashin = $totals['management_cashin'];
            $totalRevenue = $totals['management_cashin'];
        } elseif ($category === 'vendor_payment') {
            $totalCashin = 0;
            $totalRevenue = 0;
        } elseif ($category === 'contractor_bill') {
            $totalCashin = 0;
            $totalRevenue = 0;
        }

        return [
            'period' => 'Custom Range (Daily)',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance($startDate, $projectId, $category),
            'daily_data' => $dailyData,
            'summary' => [
                'total_booking_money' => $totals['booking_money'],
                'total_registration_payments' => $totals['registration_payments'],
                'total_emi_payments' => $totals['emi_payments'],
                'total_landshare_payments' => $totals['landshare_payments'],
                'total_service_fee' => $totals['service_fee'],
                'total_management_cashin' => $totals['management_cashin'],
                'total_discount' => $totals['discount'],
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totals['cash_out'],
                'net_balance' => $totalCashin - $totals['cash_out'],
                'final_balance' => $category === 'all' ? $runningBalance : $totalCashin
            ]
        ];
    }

    private function getDailyPaymentByTypeSingle($paymentType, $date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(lp.amount), 0) as total_amount
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(lp.payment_date) = ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.payment_type = ?
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $date, $paymentType, $projectId);
        } else {
            $stmt->bind_param("ss", $date, $paymentType);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_amount'] ?? 0);
    }


   
    private function getDailyServiceFeeSingle($date, $projectId = null)
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

    private function getDailyManagementCashinSingle($date)
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

    private function getDailyDiscountSingle($date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(discount_amount), 0) as total_amount
            FROM lead_bookings
            WHERE DATE(booking_date) = ?
            AND booking_status = 'booked'
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
        return (float)($row['total_amount'] ?? 0);
    }

    /**
     * Get custom monthly statement
     */
    /**
     * Get custom monthly statement with total_cashin support
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

        // Initialize totals
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

        $current = $start;
        while ($current <= $end) {
            $monthStart = date('Y-m-01', $current);
            $monthEnd = date('Y-m-t', $current);

            // Get monthly totals
            $landsharePayments = $this->getMonthlyPaymentByType('land_share', $monthStart, $monthEnd, $projectId);
            $registrationPayments = $this->getMonthlyPaymentByType('registration', $monthStart, $monthEnd, $projectId);
            $emiPayments = $this->getMonthlyPaymentByType('emi', $monthStart, $monthEnd, $projectId);
            $bookingMoney = $this->getMonthlyPaymentByType('booking_money', $monthStart, $monthEnd, $projectId);
           
            $serviceFee = $this->getMonthlyServiceFee($monthStart, $monthEnd, $projectId);
            $managementCashin = $this->getMonthlyManagementCashin($monthStart, $monthEnd);
            $developmentCharge = $this->getMonthlyPaymentByType('development_charge', $monthStart, $monthEnd, $projectId);
            $shopServiceCharge = $this->getMonthlyPaymentByType('shop_service_charge', $monthStart, $monthEnd, $projectId);
            $expenseAdjCashin = $this->getTotalExpenseAdjustmentCashin($monthStart, $monthEnd);
            $procurementReturns = $this->getTotalProcurementReturnsCashin($monthStart, $monthEnd);
            $discount = $this->getMonthlyDiscount($monthStart, $monthEnd, $projectId);

            // Only get cashout for expense categories, not for revenue categories
            $cashOut = 0;
            if ($category === 'vendor_payment') {
                $cashOut = $this->getMonthlyVendorPayments($monthStart, $monthEnd, $projectId);
            } elseif ($category === 'contractor_bill') {
                $cashOut = $this->getMonthlyContractorBills($monthStart, $monthEnd, $projectId);
            } elseif (in_array($category, ['all', 'cashout', 'utilities', 'other'])) {
                $cashOut = $this->getMonthlyCashOut($monthStart, $monthEnd, $category, $projectId);
            }

            // Deduct returns from cashout
            if ($category === 'all' || $category === 'cashout') {
                $cashOut -= ($expenseAdjCashin + $procurementReturns);
            }

            // Calculate cashin based on selected category
            $cashin = 0;
            if ($category === 'all' || $category === 'total_cashin') {
                // Total cashin includes all revenue sources minus discount
                $cashin = $landsharePayments + $registrationPayments + $bookingMoney + $emiPayments + $managementCashin + $developmentCharge + $shopServiceCharge;
            } elseif ($category === 'emi') {
                $cashin = $emiPayments;
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
            }

            $netAmount = $cashin - $cashOut;

            // Only update running balance for 'all' category
            if ($category === 'all') {
                $runningBalance += $netAmount;
            }

            // Accumulate totals
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
                // Detailed breakdown
                'booking_money' => $bookingMoney,
                'registration_payments' => $registrationPayments,
                'emi_payments' => $emiPayments,
                'landshare_payments' => $landsharePayments,
                'service_fee' => $serviceFee,
                'management_cashin' => $managementCashin,
                'discount' => $discount,
                'total_cashin' => $cashin,
                'cash_out' => $cashOut,
                'net_amount' => $netAmount,
                'running_balance' => $category === 'all' ? $runningBalance : $cashin,

                'total_revenue' => $cashin
            ];

            $current = strtotime('+1 month', $current);
        }

        // Calculate final totals based on category
        $totalCashin = 0;
        $totalRevenue = 0;

        if ($category === 'all' || $category === 'total_cashin') {
            $totalCashin = $totals['landshare_payments'] + $totals['registration_payments'] +
                $totals['booking_money'] + $totals['emi_payments'] + $totals['management_cashin'] +
                $totals['development_charge'] + $totals['shop_service_charge'];
            $totalRevenue = $totals['landshare_payments'] + $totals['booking_money'] + $totals['service_fee'];
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
        }  elseif ($category === 'booking_money') {
            $totalCashin = $totals['booking_money'];
            $totalRevenue = $totals['booking_money'];
        } elseif ($category === 'management_cashin') {
            $totalCashin = $totals['management_cashin'];
            $totalRevenue = $totals['management_cashin'];
        } elseif ($category === 'vendor_payment') {
            $totalCashin = 0;
            $totalRevenue = 0;
        } elseif ($category === 'contractor_bill') {
            $totalCashin = 0;
            $totalRevenue = 0;
        }

        return [
            'period' => 'Custom Range (Monthly)',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $this->getOpeningBalance(date('Y-m-01', strtotime($startDate)), $projectId, $category),
            'monthly_data' => $monthlyData,
            'summary' => [
                'total_booking_money' => $totals['booking_money'],
                'total_registration_payments' => $totals['registration_payments'],
                'total_emi_payments' => $totals['emi_payments'],
                'total_landshare_payments' => $totals['landshare_payments'],
                'total_service_fee' => $totals['service_fee'],
                'total_management_cashin' => $totals['management_cashin'],
                'total_discount' => $totals['discount'],
                'total_cashin' => $totalCashin,
                'total_revenue' => $totalRevenue,
                'total_cash_out' => $totals['cash_out'],
                'net_balance' => $totalCashin - $totals['cash_out'],
                'final_balance' => $category === 'all' ? $runningBalance : $totalCashin,
                // Backward compatibility
                'total_landshare' => $totals['landshare_payments']
            ]
        ];
    }

    /**
     * Helper: Get opening balance before a specific date
     */
    private function getOpeningBalance($startDate, $projectId = null, $category = 'all')
    {
        // Get all payments before start date
        $paymentsQuery = "
            SELECT COALESCE(SUM(lp.amount), 0) as total
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(lp.payment_date) < ? 
            AND lp.payment_status IN ('paid','partially_paid')
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
        $totalCashOut = $this->getOpeningCashOutTotal($startDate, $projectId, $category);

        // Add project filter if specified
        if ($projectId) {
            $paymentsQuery .= " AND lb.project_id = ?";

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

        // Get total payments
        $totalPayments = 0;
        $stmt = $this->db->prepare($paymentsQuery);
        if ($projectId) {
            $stmt->bind_param("si", $startDate, $projectId);
        } else {
            $stmt->bind_param("s", $startDate);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalPayments = (float)$row['total'];
        }



        // Get service fees
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

        // Get management cashin
        $totalManagementCashin = 0;
        $stmt = $this->db->prepare($cashinQuery);
        $stmt->bind_param("s", $startDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $totalManagementCashin = (float)$row['total'];
        }

        // Get cash-out
        $totalCashOut = $this->getOpeningCashOutTotal($startDate, $projectId, $category);

        // Calculate opening balance based on the cashin formula
        $landsharePayments = $this->getTotalPaymentByTypeBeforeDate('land_share', $startDate, $projectId);
        $registrationPayments = $this->getTotalPaymentByTypeBeforeDate('registration', $startDate, $projectId);
        $emiPayments = $this->getTotalPaymentByTypeBeforeDate('emi', $startDate, $projectId);
        $totalBookingMoney = $this->getTotalPaymentByTypeBeforeDate('booking_money', $startDate, $projectId);
        $totalDiscount = $this->getTotalDiscountBeforeDate($startDate, $projectId);

        $openingCashin = $landsharePayments + $registrationPayments + $totalBookingMoney + $emiPayments - $totalDiscount;

        // Opening balance = Cashin - Cash out
        $openingBalance = $openingCashin - $totalCashOut;

        return $openingBalance;
    }

    private function getTotalPaymentByTypeBeforeDate($paymentType, $date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(lp.amount), 0) as total_amount
            FROM lead_payments lp
            INNER JOIN lead_bookings lb ON lp.booking_id = lb.id
            WHERE DATE(lp.payment_date) < ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.payment_type = ?
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $date, $paymentType, $projectId);
        } else {
            $stmt->bind_param("ss", $date, $paymentType);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (float)($row['total_amount'] ?? 0);
    }

    private function getTotalDiscountBeforeDate($date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(discount_amount), 0) as total_discount
            FROM lead_bookings
            WHERE DATE(booking_date) < ?
            AND booking_status = 'booked'
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
        return (float)($row['total_discount'] ?? 0);
    }

    /**
     * Helper: Get opening cashout query based on category
     */
    private function getOpeningCashOutTotal($startDate, $projectId = null, $category = 'all')
    {
        $query = "
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM (
            -- Management cashout (only if no project filter)
            " . (!$projectId ? "
            SELECT amount as total_amount
            FROM management_cashout
            WHERE DATE(transaction_date) < ? 
            AND payment_status = 'paid'
            " : "SELECT 0 as total_amount WHERE 1=0") . "
            
            UNION ALL
            
            -- Cashout request payments
            SELECT cp.amount as total_amount
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE DATE(cp.payment_date) < ?
            AND cr.status IN ('approved', 'partially_paid', 'fully_paid')
            " . ($projectId ? " AND (cr.project_id = ? OR cp.project_id = ?)" : "") . "
            
            UNION ALL
            
            -- Vendor payments & contractor bills
            SELECT amount_paid as total_amount
            FROM con_financial_transactions
            WHERE DATE(transaction_date) < ?
            AND ref_type IN ('vendor_payment', 'contractor_bill')
            " . ($projectId ? " AND project_id = ?" : "") . "
            
            UNION ALL
            
            -- Salary payments (only if no project filter)
            " . (!$projectId ? "
            SELECT net_salary as total_amount
            FROM payslips
            WHERE DATE(COALESCE(disbursed_at, payslip_month, updated_at)) < ?
            AND status = 'paid'
            " : "SELECT 0 as total_amount WHERE 1=0") . "
            
            UNION ALL
            
            -- Fund Requisitions
            SELECT amount as total_amount
            FROM fund_requisitions
            WHERE status = 'paid'
            AND DATE(paid_at) < ?
            " . ($projectId ? " AND project_id = ?" : "") . "
            
            UNION ALL
            
            -- Land Fund Requisitions
            SELECT lfr.requested_amount as total_amount
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            WHERE lfr.mgmt_status = 'Approved'
            AND lfr.accounts_status = 'Paid'
            AND DATE(lfr.accounts_paid_at) < ?
            " . ($projectId ? " AND la.project_id = ?" : "") . "
            
            UNION ALL
            
            -- Office Procurement Assets
            SELECT paid_amount as total_amount
            FROM project_extra_assets
            WHERE payment_status = 'paid'
            AND DATE(paid_at) < ?
            " . ($projectId ? " AND project_id = ?" : "") . "
        ) combined
        ";

        $params = [];
        $types = "";

        if (!$projectId) {
            $params[] = $startDate;
            $types .= "s";
        }

        $params[] = $startDate;
        $types .= "s";
        if ($projectId) {
            $params[] = $projectId;
            $params[] = $projectId;
            $types .= "ii";
        }

        $params[] = $startDate;
        $types .= "s";
        if ($projectId) {
            $params[] = $projectId;
            $types .= "i";
        }

        if (!$projectId) {
            $params[] = $startDate;
            $types .= "s";
        }

        $params[] = $startDate;
        $types .= "s";
        if ($projectId) {
            $params[] = $projectId;
            $types .= "i";
        }

        $params[] = $startDate;
        $types .= "s";
        if ($projectId) {
            $params[] = $projectId;
            $types .= "i";
        }

        $params[] = $startDate;
        $types .= "s";
        if ($projectId) {
            $params[] = $projectId;
            $types .= "i";
        }

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return 0.00;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get daily cash-out total
     */
    /**
     * Helper: Get daily cash-out total
     */
    private function getDailyCashOut($date, $category = 'all', $projectId = null)
    {
        if ($category === 'utilities') {
            return $this->getDailyUtilitiesCashOut($date, $projectId);
        }

        // Get category ID if a specific category is selected (for management_cashout)
        $categoryId = null;
        if ($category !== 'all' && $category !== 'cashout' && $category !== 'emi') {
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
            " . ($projectId ? " AND (cr.project_id = ? OR cp.project_id = ?)" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Salary payments (payslips) - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT net_salary as total_amount
            FROM payslips
            WHERE DATE(COALESCE(disbursed_at, payslip_month, updated_at)) = ?
            AND status = 'paid'
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Vendor payments & contractor bills - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT amount_paid as total_amount
            FROM con_financial_transactions
            WHERE DATE(transaction_date) = ?
            AND ref_type IN ('vendor_payment', 'contractor_bill')
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Fund Requisitions - only for 'all' or 'cashout' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT amount as total_amount
            FROM fund_requisitions
            WHERE DATE(paid_at) = ?
            AND status = 'paid'
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Land Fund Requisitions - only for 'all' or 'cashout' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT lfr.requested_amount as total_amount
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            WHERE DATE(lfr.accounts_paid_at) = ?
            AND lfr.mgmt_status = 'Approved'
            AND lfr.accounts_status = 'Paid'
            " . ($projectId ? " AND la.project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Office Procurement Assets - only for 'all' or 'cashout' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT paid_amount as total_amount
            FROM project_extra_assets
            WHERE DATE(paid_at) = ?
            AND payment_status = 'paid'
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Expense adjustments (cashout)
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT at.amount as total_amount
            FROM adjustment_transactions at
            JOIN expense_adjustments ea ON at.adjustment_id = ea.id
            WHERE at.transaction_type = 'cashout'
            AND DATE(at.payment_date) = ?
            AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
            " . ($projectId ? " AND ea.project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
        ) combined
    ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
        }

        // Bind parameters dynamically
        $params = [];
        $types = "";

        if ($categoryId) {
            $params[] = $date;
            $params[] = $categoryId;
            $types .= "si";
        } else {
            // 1. management_cashout
            $params[] = $date;
            $types .= "s";

            if ($category === 'all' || $category === 'cashout') {
                // 2. cashout_payments
                $params[] = $date;
                $types .= "s";
                if ($projectId) {
                    $params[] = $projectId;
                    $params[] = $projectId;
                    $types .= "ii";
                }

                // 3. payslips
                $params[] = $date;
                $types .= "s";

                // 4. vendor & contractor
                $params[] = $date;
                $types .= "s";
                if ($projectId) {
                    $params[] = $projectId;
                    $types .= "i";
                }

                // 5. fund requisitions
                $params[] = $date;
                $types .= "s";
                if ($projectId) {
                    $params[] = $projectId;
                    $types .= "i";
                }

                // 6. land fund requisitions
                $params[] = $date;
                $types .= "s";
                if ($projectId) {
                    $params[] = $projectId;
                    $types .= "i";
                }

                // 7. project extra assets
                $params[] = $date;
                $types .= "s";
                if ($projectId) {
                    $params[] = $projectId;
                    $types .= "i";
                }

                // 8. expense adjustments
                $params[] = $date;
                $types .= "s";
                if ($projectId) {
                    $params[] = $projectId;
                    $types .= "i";
                }
            }
        }

        if (strlen($types) > 0) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get daily utilities cashout from cashout_requests
     */
    private function getDailyUtilitiesCashOut($date, $projectId = null)
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

        // Add project filter if specified
        if ($projectId) {
            $query .= " AND (cr.project_id = ? OR cp.project_id = ?)";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("sii", $date, $projectId, $projectId);
        } else {
            $stmt->bind_param("s", $date);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return (float)($row['total'] ?? 0);
    }

    /**
     * Helper: Get monthly cash-out total
     */
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
            return $this->getMonthlyUtilitiesCashOut($startDate, $endDate, $projectId);
        }

        // Handle office procurement assets separately
        if ($category === 'office_procurement_assets') {
            $query = "
                SELECT COALESCE(SUM(paid_amount), 0) as total
                FROM project_extra_assets
                WHERE payment_status = 'paid'
            ";
            $params = [];
            $types = "";

            if ($startDate && $endDate) {
                $query .= " AND DATE(paid_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
            }

            if ($projectId) {
                $query .= " AND project_id = ?";
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
            return (float)($row['total'] ?? 0);
        }

        // Handle assets repairing separately
        if ($category === 'assets_repairing') {
            $query = "
                SELECT COALESCE(SUM(amount), 0) as total
                FROM fund_requisitions
                WHERE status = 'paid' AND category = 'Asset Repair'
            ";
            $params = [];
            $types = "";

            if ($startDate && $endDate) {
                $query .= " AND DATE(paid_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
            }

            if ($projectId) {
                $query .= " AND project_id = ?";
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
            return (float)($row['total'] ?? 0);
        }

        // Handle general fund requisitions (excluding Asset Repair)
        if ($category === 'fund_requisitions') {
            $query = "
                SELECT COALESCE(SUM(amount), 0) as total
                FROM fund_requisitions
                WHERE status = 'paid'
            ";
            $params = [];
            $types = "";

            if ($startDate && $endDate) {
                $query .= " AND DATE(paid_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
            }

            if ($projectId) {
                $query .= " AND project_id = ?";
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
            return (float)($row['total'] ?? 0);
        }

        // Get category ID if a specific category is selected (for management_cashout)
        $categoryId = null;
        if ($category !== 'all' && $category !== 'cashout' && $category !== 'emi') {
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
            " . ($projectId ? " AND (cr.project_id = ? OR cp.project_id = ?)" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Salary payments (payslips) - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT net_salary as total_amount
            FROM payslips
            WHERE DATE(COALESCE(disbursed_at, payslip_month, updated_at)) BETWEEN ? AND ?
            AND status = 'paid'
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Vendor payments & contractor bills - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT amount_paid as total_amount
            FROM con_financial_transactions
            WHERE DATE(transaction_date) BETWEEN ? AND ?
            AND ref_type IN ('vendor_payment', 'contractor_bill')
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Fund Requisitions (petty cash + general) - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT amount as total_amount
            FROM fund_requisitions
            WHERE status = 'paid'
            " . ($startDate && $endDate ? " AND DATE(paid_at) BETWEEN ? AND ?" : "") . "
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Land Fund Requisitions - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT lfr.requested_amount as total_amount
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            WHERE lfr.mgmt_status = 'Approved'
            AND lfr.accounts_status = 'Paid'
            " . ($startDate && $endDate ? " AND DATE(lfr.accounts_paid_at) BETWEEN ? AND ?" : "") . "
            " . ($projectId ? " AND la.project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL

            -- Office Procurement Assets - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT paid_amount as total_amount
            FROM project_extra_assets
            WHERE payment_status = 'paid'
            " . ($startDate && $endDate ? " AND DATE(paid_at) BETWEEN ? AND ?" : "") . "
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Expense adjustments (cashout)
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT at.amount as total_amount
            FROM adjustment_transactions at
            JOIN expense_adjustments ea ON at.adjustment_id = ea.id
            WHERE at.transaction_type = 'cashout'
            " . ($startDate && $endDate ? " AND DATE(at.payment_date) BETWEEN ? AND ?" : "") . "
            AND ea.status IN ('approved', 'partially_paid', 'fully_paid')
            " . ($projectId ? " AND ea.project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
        ) combined
    ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
        }

        // Bind parameters based on category
        $params = [];
        $types = "";

        if ($categoryId) {
            $params = [$startDate, $endDate, $categoryId];
            $types = "ssi";
        } else {
            // 1. management_cashout
            $params = array_merge($params, [$startDate, $endDate]);
            $types .= "ss";

            if ($category === 'all' || $category === 'cashout') {
                // 2. cashout_payments
                $params = array_merge($params, [$startDate, $endDate]);
                $types .= "ss";
                if ($projectId) {
                    $params = array_merge($params, [$projectId, $projectId]);
                    $types .= "ii";
                }

                // 3. payslips
                $params = array_merge($params, [$startDate, $endDate]);
                $types .= "ss";

                // 4. con_financial_transactions
                $params = array_merge($params, [$startDate, $endDate]);
                $types .= "ss";
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }

                // 5. fund_requisitions
                if ($startDate && $endDate) {
                    $params = array_merge($params, [$startDate, $endDate]);
                    $types .= "ss";
                }
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }

                // 6. land_fund_requisitions
                if ($startDate && $endDate) {
                    $params = array_merge($params, [$startDate, $endDate]);
                    $types .= "ss";
                }
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }

                // 7. project_extra_assets
                if ($startDate && $endDate) {
                    $params = array_merge($params, [$startDate, $endDate]);
                    $types .= "ss";
                }
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }

                // 8. expense adjustments
                if ($startDate && $endDate) {
                    $params = array_merge($params, [$startDate, $endDate]);
                    $types .= "ss";
                }
                if ($projectId) {
                    $params = array_merge($params, [$projectId]);
                    $types .= "i";
                }
            }
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
     * Helper: Get monthly utilities cashout from cashout_requests
     */
    private function getMonthlyUtilitiesCashOut($startDate, $endDate, $projectId = null)
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

        // Add project filter if specified
        if ($projectId) {
            $query .= " AND (cr.project_id = ? OR cp.project_id = ?)";
        }

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssii", $startDate, $endDate, $projectId, $projectId);
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
    public function getOverallStatistics($periodType = 'all_time', $year = null, $month = null, $startDate = null, $endDate = null, $projectId = null, $category = 'all')
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
            // Default to all time
            $startDate = null;
            $endDate = null;
        }

        // Calculate revenue for the period
        $revenueData = $this->calculateRevenue($startDate, $endDate, $projectId);

        // Get cash-out for the period
        $cashOutData = $this->getCashOutForPeriod($startDate, $endDate, $category, $projectId);
        $totalCashOut = $cashOutData['total'] ?? 0;

        return [
            'total_booking_money' => $revenueData['summary']['total_booking_money'],
            'total_landshare_payments' => $revenueData['summary']['total_landshare_payments'],
            'total_registration_payments' => $revenueData['summary']['total_registration_payments'],
            'total_emi_payments' => $revenueData['summary']['total_emi_payments'],
            'total_service_fee' => $revenueData['summary']['total_service_fee'],
            'total_discount' => $revenueData['summary']['total_discount_from_bookings'],
            'total_cashin' => $revenueData['summary']['total_cashin'],
            'total_revenue' => $revenueData['summary']['total_revenue'],
            'total_cash_out' => $totalCashOut,
            'net_balance' => $revenueData['summary']['total_cashin'] - $totalCashOut
        ];
    }

    private function getCashOutForPeriod($startDate, $endDate, $category = 'all', $projectId = null)
    {
        if ($category === 'utilities') {
            return $this->getUtilitiesCashOutForPeriod($startDate, $endDate, $projectId);
        }

        // Handle office procurement assets separately
        if ($category === 'office_procurement_assets') {
            $query = "
                SELECT COALESCE(SUM(paid_amount), 0) as total
                FROM project_extra_assets
                WHERE payment_status = 'paid'
            ";
            $params = [];
            $types = "";

            if ($startDate && $endDate) {
                $query .= " AND DATE(paid_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
            }

            if ($projectId) {
                $query .= " AND project_id = ?";
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
            return ['total' => (float)($row['total'] ?? 0)];
        }

        // Handle assets repairing separately
        if ($category === 'assets_repairing') {
            $query = "
                SELECT COALESCE(SUM(amount), 0) as total
                FROM fund_requisitions
                WHERE status = 'paid' AND category = 'Asset Repair'
            ";
            $params = [];
            $types = "";

            if ($startDate && $endDate) {
                $query .= " AND DATE(paid_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
            }

            if ($projectId) {
                $query .= " AND project_id = ?";
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
            return ['total' => (float)($row['total'] ?? 0)];
        }

        // Handle general fund requisitions (excluding Asset Repair)
        if ($category === 'fund_requisitions') {
            $query = "
                SELECT COALESCE(SUM(amount), 0) as total
                FROM fund_requisitions
                WHERE status = 'paid'
            ";
            $params = [];
            $types = "";

            if ($startDate && $endDate) {
                $query .= " AND DATE(paid_at) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
            }

            if ($projectId) {
                $query .= " AND project_id = ?";
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
            return ['total' => (float)($row['total'] ?? 0)];
        }

        // Handle vendor_payment and contractor_bill separately
        if ($category === 'vendor_payment' || $category === 'contractor_bill') {
            $query = "
                SELECT COALESCE(SUM(amount_paid), 0) as total
                FROM con_financial_transactions
                WHERE ref_type = ?
            ";
            $params = [$category];
            $types = "s";

            if ($startDate && $endDate) {
                $query .= " AND DATE(transaction_date) BETWEEN ? AND ?";
                $params[] = $startDate;
                $params[] = $endDate;
                $types .= "ss";
            }

            if ($projectId) {
                $query .= " AND project_id = ?";
                $params[] = $projectId;
                $types .= "i";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return ['total' => (float)($row['total'] ?? 0)];
        }

        // Get category ID if a specific category is selected (for management_cashout)
        $categoryId = null;
        if ($category !== 'all' && $category !== 'cashout') {
            $categoryId = $this->getCategoryIdByName($category);
        }

        // Build date conditions
        $dateCondition = "";
        $params = [];
        $types = "";

        if ($startDate && $endDate) {
            $dateCondition = " AND DATE(transaction_date) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            $types = "ss";
        }

        // Project condition for cashout requests
        $projectCondition = "";
        if ($projectId && ($category === 'all' || $category === 'cashout')) {
            $projectCondition = " AND (cr.project_id = ? OR cp.project_id = ?)";
        }

        // Query for other categories
        $query = "
        SELECT COALESCE(SUM(total_amount), 0) as total
        FROM (
            -- Management cashout
            SELECT amount as total_amount
            FROM management_cashout
            WHERE payment_status = 'paid'
            {$dateCondition}
            " . ($categoryId ? " AND category_id = ?" : "") . "
            
            UNION ALL
            
            -- Cashout request payments (only for 'cashout' or 'all' categories)
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT cp.amount as total_amount
            FROM cashout_payments cp
            LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
            WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
            " . ($startDate && $endDate ? " AND DATE(cp.payment_date) BETWEEN ? AND ?" : "") . "
            {$projectCondition}
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Salary payments (payslips) - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT net_salary as total_amount
            FROM payslips
            WHERE status = 'paid'
            " . ($startDate && $endDate ? " AND DATE(COALESCE(disbursed_at, payslip_month, updated_at)) BETWEEN ? AND ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Vendor payments & contractor bills - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT amount_paid as total_amount
            FROM con_financial_transactions
            WHERE ref_type IN ('vendor_payment', 'contractor_bill')
            " . ($startDate && $endDate ? " AND DATE(transaction_date) BETWEEN ? AND ?" : "") . "
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Fund Requisitions (petty cash + general) - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT amount as total_amount
            FROM fund_requisitions
            WHERE status = 'paid'
            " . ($startDate && $endDate ? " AND DATE(paid_at) BETWEEN ? AND ?" : "") . "
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL
            
            -- Land Fund Requisitions - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT lfr.requested_amount as total_amount
            FROM land_fund_requisitions lfr
            LEFT JOIN land_acquisitions la ON lfr.land_acquisition_id = la.id
            WHERE lfr.mgmt_status = 'Approved'
            AND lfr.accounts_status = 'Paid'
            " . ($startDate && $endDate ? " AND DATE(lfr.accounts_paid_at) BETWEEN ? AND ?" : "") . "
            " . ($projectId ? " AND la.project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
            
            UNION ALL

            -- Office Procurement Assets - only for 'all' category
            " . (($category === 'all' || $category === 'cashout') ? "
            SELECT paid_amount as total_amount
            FROM project_extra_assets
            WHERE payment_status = 'paid'
            " . ($startDate && $endDate ? " AND DATE(paid_at) BETWEEN ? AND ?" : "") . "
            " . ($projectId ? " AND project_id = ?" : "") . "
            " : "SELECT 0 as total_amount") . "
        ) combined
    ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
        }

        // Build parameters array
        $bindParams = [];
        $bindTypes = "";

        if ($categoryId) {
            if ($startDate && $endDate) {
                $bindParams = [$startDate, $endDate, $categoryId];
                $bindTypes = "ssi";
            } else {
                $bindParams = [$categoryId];
                $bindTypes = "i";
            }
        } else {
            // 1. management_cashout
            if ($startDate && $endDate) {
                $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                $bindTypes .= "ss";
            }

            if ($category === 'all' || $category === 'cashout') {
                // 2. cashout_payments
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId, $projectId]);
                    $bindTypes .= "ii";
                }

                // 3. payslips
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }

                // 4. con_financial_transactions
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }

                // 5. fund_requisitions
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }

                // 6. land_fund_requisitions
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }

                // 7. project_extra_assets
                if ($startDate && $endDate) {
                    $bindParams = array_merge($bindParams, [$startDate, $endDate]);
                    $bindTypes .= "ss";
                }
                if ($projectId) {
                    $bindParams = array_merge($bindParams, [$projectId]);
                    $bindTypes .= "i";
                }
            }
        }

        if (!empty($bindParams)) {
            $stmt->bind_param($bindTypes, ...$bindParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return [
            'total' => (float)($row['total'] ?? 0)
        ];
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
        $utilityKeywords = ['bill', 'bills', 'electricity', 'wifi', 'internet', 'water', 'gas', 'utility', 'utilities', 'power', 'light', 'electric'];

        $keywordConditions = [];
        foreach ($utilityKeywords as $keyword) {
            $keywordConditions[] = "LOWER(cr.purpose_paid_to) LIKE '%" . $this->db->real_escape_string(strtolower($keyword)) . "%'";
        }
        $keywordCondition = implode(' OR ', $keywordConditions);

        $dateCondition = "";
        $params = [];

        if ($startDate && $endDate) {
            $dateCondition = " AND DATE(cp.payment_date) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }

        $projectCondition = "";
        if ($projectId) {
            $projectCondition = " AND (cr.project_id = ? OR cp.project_id = ?)";
            $params = array_merge($params, [$projectId, $projectId]);
        }

        $query = "
        SELECT COALESCE(SUM(cp.amount), 0) as total
        FROM cashout_payments cp
        LEFT JOIN cashout_requests cr ON cp.cashout_request_id = cr.id
        WHERE cr.status IN ('approved', 'partially_paid', 'fully_paid')
        AND ($keywordCondition)
        {$dateCondition}
        {$projectCondition}
    ";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new RuntimeException("SQL Prepare Error: " . $this->db->error);
        }

        if (!empty($params)) {
            $types = str_repeat('s', count($params));
            if ($projectId) {
                $types = substr_replace($types, 'i', -2, 2);
            }
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
            'cashout' => null,
            'landshare' => null,
            'service_fee' => null,
            'emi' => null,  // Add EMI category
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

        if ($projectId) {
            $query .= " AND cft.project_id = ?";
        }

        $query .= " GROUP BY DATE(cft.transaction_date)";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
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

        if ($projectId) {
            $query .= " AND cft.project_id = ?";
        }

        $query .= " GROUP BY DATE(cft.transaction_date)";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
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

    /**
     * Get single-day vendor payment total
     */
    private function getDailyVendorPaymentSingle($date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(amount_paid), 0) as total
            FROM con_financial_transactions
            WHERE ref_type = 'vendor_payment'
            AND DATE(transaction_date) = ?
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
     * Get single-day contractor bill total
     */
    private function getDailyContractorBillSingle($date, $projectId = null)
    {
        $query = "
            SELECT COALESCE(SUM(amount_paid), 0) as total
            FROM con_financial_transactions
            WHERE ref_type = 'contractor_bill'
            AND DATE(transaction_date) = ?
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
                v.name as vendor_name,
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

        if ($projectId) {
            $query .= " AND cft.project_id = ?";
        }

        $query .= " ORDER BY cft.transaction_date ASC";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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

        if ($projectId) {
            $query .= " AND cft.project_id = ?";
        }

        $query .= " ORDER BY cft.transaction_date ASC";

        $stmt = $this->db->prepare($query);
        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
        } else {
            $stmt->bind_param("ss", $startDate, $endDate);
        }
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

    /**
     * Get detailed Client Receipts grouped by payment_date (includes client_name, project_name, flat_name)
     */
    public function getClientPaymentDetails($startDate, $endDate, $projectId = null)
    {
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
            WHERE lp.payment_date BETWEEN ? AND ?
            AND lp.payment_status IN ('paid','partially_paid')
            AND lp.verification_status = 'verified'
        ";

        if ($projectId) {
            $query .= " AND lb.project_id = ?";
        }

        $query .= " ORDER BY lp.payment_date DESC, lp.id DESC";

        $stmt = $this->db->prepare($query);
        if (!$stmt) return [];

        if ($projectId) {
            $stmt->bind_param("ssi", $startDate, $endDate, $projectId);
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



