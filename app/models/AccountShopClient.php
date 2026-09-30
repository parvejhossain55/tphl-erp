<?php

class AccountShopClient extends AccountClient
{
    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Get client details by booking ID tailored for Commercial Shops
     */
    public function getClientByBookingId($bookingId)
    {
        $hasTrashed = $this->hasColumn('lead_payments', 'is_trashed');
        $lpTrash = $hasTrashed ? "lp.is_trashed = 0 AND " : "";

        $hasTransfer = $this->hasColumn('lead_bookings', 'transfer_status');
        $hasTotalEmi = $this->hasColumn('lead_bookings', 'total_emi_no');
        $hasEmiAudit = $this->hasColumn('lead_bookings', 'emi_updated_by');
        $hasApproval = $this->hasColumn('lead_bookings', 'approval_status');
        $hasApprovedAmt = $this->hasColumn('lead_bookings', 'approved_amount');
        $hasPastApp  = $this->hasColumn('lead_bookings', 'past_approval_attachment');
        $hasLandshareStatus = $this->hasColumn('lead_bookings', 'landshare_status');
        $hasServiceFeeBal   = $this->hasColumn('lead_bookings', 'service_fee_balance');

        $approvalCol    = $hasApproval ? "lb.approval_status," : "NULL as approval_status,";
        $approvedAmtCol = $hasApprovedAmt ? "lb.approved_amount," : "NULL as approved_amount,";
        $pastAppCol     = $hasPastApp ? "lb.past_approval_attachment," : "NULL as past_approval_attachment,";
        $lsStatusCol    = $hasLandshareStatus ? "lb.landshare_status," : "NULL as landshare_status,";
        $sfBalCol       = $hasServiceFeeBal ? "lb.service_fee_balance," : "NULL as service_fee_balance,";

        $transferCols   = $hasTransfer ? "lb.transfer_date, lb.transfer_note, lb.transfer_status, transfer_u.full_name as transfer_updated_by_name" : "NULL as transfer_date, NULL as transfer_note, 0 as transfer_status, NULL as transfer_updated_by_name";
        $transferJoin   = $hasTransfer ? "LEFT JOIN users transfer_u ON lb.transfer_updated_by = transfer_u.id" : "";

        $stmt = $this->db->prepare("
            SELECT 
                lb.id as booking_id,
                lb.lead_id,
                lb.booking_date,
                lb.sales_by_id,
                l.assigned_id,
                lb.agreement_value,
                -- booking_money_total: all NON-REJECTED entries (pending + verified)
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE {$lpTrash}lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status != 'rejected'), 
                 0) AS booking_money_total,
                -- booking_money: only VERIFIED entries
                COALESCE(
                 (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                WHERE {$lpTrash}lp.booking_id = lb.id 
                 AND lp.payment_type = 'booking_money'
                 AND lp.verification_status = 'verified'), 
                 0) AS booking_money,
                lb.discount_amount,
                {$approvedAmtCol}
                {$approvalCol}
                lb.booking_status,
                {$lsStatusCol}
                {$sfBalCol}
                {$pastAppCol}
                l.id as lead_id,
                l.full_name as client_name,
                COALESCE(NULLIF(TRIM(l.phone), ''), TRIM(l.phone_number)) AS phone,
                l.phone_number,
                l.email,
                l.designation,
                p.id as project_id,
                p.name as project_name,
                p.types as project_type,
                COALESCE(f.shop_service_charge, 0) as shop_service_charge,
                f.id as flat_id,
                f.floor_no as flat_no,
                f.sqft as flat_size,
                f.price_value as total_price,
                f.flat_name as unit_name,
                f.is_shop,
                f.unit_type,
                u.full_name as agent_name,
                COALESCE(NULLIF(e1.employee_id, ''), NULLIF(e2.employee_id, ''), NULLIF(u.employee_id, '')) as agent_employee_id,
                -- Payment calculations
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as total_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'registration'), 
                    0
                ) as registration_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'registration'), 
                    0
                ) as registration_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type = 'booking_money'
                     AND lp.verification_status = 'verified'), 
                    0
                ) as booking_money_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type IN ('emi', 'emi_due_settlement')), 
                    0
                ) as emi_paid_total,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'shop_service_charge'), 
                    0
                ) as shop_service_charge_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'shop_service_charge'), 
                    0
                ) as shop_service_charge_paid_total,
                COALESCE(
                    (SELECT SUM(lp.due_emi_amount)
                     FROM lead_payments lp
                     WHERE {$lpTrash}lp.booking_id = lb.id
                     AND lp.payment_type = 'emi'
                     AND lp.due_emi_amount > 0
                     AND lp.verification_status = 'verified'
                     AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                     AND NOT EXISTS (
                         SELECT 1 FROM lead_payments child
                         WHERE child.parent_payment_id = lp.id
                         AND child.payment_type = 'emi_due_settlement'
                         AND child.verification_status IN ('pending', 'verified')
                     )),
                    0
                ) as total_emi_due,
                COALESCE(
                    (SELECT SUM(lp.bulk_emi_discount)
                     FROM lead_payments lp
                     WHERE {$lpTrash}lp.booking_id = lb.id
                     AND lp.verification_status = 'verified'
                     AND lp.bulk_emi_discount > 0),
                    0
                ) as total_bulk_emi_discount,
                COALESCE(
                    (SELECT MAX(lp.payment_date) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    NULL
                ) as last_payment_date,
                {$transferCols}
            FROM lead_bookings lb
            LEFT JOIN leads l ON lb.lead_id = l.id
            LEFT JOIN projects p ON lb.project_id = p.id
            LEFT JOIN flats f ON lb.flat_id = f.id
            LEFT JOIN users u ON COALESCE(NULLIF(lb.sales_by_id, 0), NULLIF(l.assigned_id, 0)) = u.id
            LEFT JOIN employees e1 ON e1.user_id = u.id
            LEFT JOIN employees e2 ON u.employee_id = e2.id
            {$transferJoin}
            WHERE lb.id = ?
        ");

        if (!$stmt) {
            error_log("Prepare statement failed: " . $this->db->error);
            return null;
        }

        $stmt->bind_param("i", $bookingId);

        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            return null;
        }

        $result = $stmt->get_result();
        $clientDetails = $result ? $result->fetch_assoc() : null;

        if ($clientDetails) {
            // Cast all amounts to float for calculations
            $totalPrice            = (float) $clientDetails['total_price'];
            $discountAmount        = (float) $clientDetails['discount_amount'];

            // Shop logic
            $totalLandShare        = $totalPrice * 0.30;
            $shopServiceChargePct  = (float) ($clientDetails['shop_service_charge'] ?? 0);
            $shopServiceChargeTarget = ($totalPrice * $shopServiceChargePct) / 100;

            $bookingMoney          = (float) $clientDetails['booking_money'];
            $bookingMoneyTotal     = (float) ($clientDetails['booking_money_total'] ?? 0);

            // Paid amounts
            $totalPaid             = (float) $clientDetails['total_paid'];
            $bookingMoneyPaid      = (float) $clientDetails['booking_money_paid'];
            $registrationPaid      = (float) $clientDetails['registration_paid'];
            $registrationPaidTotal = (float) ($clientDetails['registration_paid_total'] ?? $registrationPaid);
            $shopServiceChargePaid = (float) $clientDetails['shop_service_charge_paid'];
            $shopServiceChargePaidTotal = (float) ($clientDetails['shop_service_charge_paid_total'] ?? $shopServiceChargePaid);
            $emiPaid               = (float) $clientDetails['emi_paid'];
            $emiPaidTotal          = (float) ($clientDetails['emi_paid_total'] ?? $emiPaid);
            
            // Shops have no per-EMI service fee
            $totalServiceFee       = 0;

            $totalLandSharePaid      = $bookingMoney + $registrationPaid;
            $totalLandSharePaidTotal = $bookingMoneyTotal + $registrationPaidTotal;

            $landSharePaymentsOnly    = round($bookingMoney + $registrationPaid);
            $effectiveLandShareTarget = max(0, round($totalLandShare - $discountAmount));
            $remainingLandShare       = max(0, round($effectiveLandShareTarget - $landSharePaymentsOnly));
            $remainingLandShareTotal  = max(0, round($effectiveLandShareTarget - $totalLandSharePaidTotal));
            $isLandShareCompleted     = ($remainingLandShareTotal <= 0 || $remainingLandShare <= 0);

            $constructionFee = max(0, round($totalPrice - ($totalLandShare + $shopServiceChargeTarget)));

            $isShopServiceChargeCompleted = ($shopServiceChargeTarget > 0 && ($shopServiceChargePaidTotal >= $shopServiceChargeTarget || $shopServiceChargePaid >= $shopServiceChargeTarget));
            if ($shopServiceChargeTarget <= 0) {
                $isShopServiceChargeCompleted = true;
            }

            $constructionFeePaid         = $emiPaid;
            $totalBulkEmiDiscount        = (float) ($clientDetails['total_bulk_emi_discount'] ?? 0);
            $remainingConstructionFee    = max(0, round($constructionFee - $constructionFeePaid - $totalBulkEmiDiscount));

            $isRegistrationPaid = ($registrationPaid > 0);

            $totalDue     = max(0, round($totalPrice - $discountAmount - $totalPaid - $totalBulkEmiDiscount));
            $netReceived  = round($totalPaid - $totalServiceFee);
            $totalEmiDue  = (float) ($clientDetails['total_emi_due'] ?? 0);

            if ($remainingConstructionFee <= 0) {
                $totalEmiDue = 0;
            }

            // Get total EMI count & audit metadata from lead_bookings / lead_payments
            $hasTotalEmiLb = $this->hasColumn('lead_bookings', 'total_emi_no');
            $hasEmiAuditLb = $this->hasColumn('lead_bookings', 'emi_updated_by');

            if ($hasTotalEmiLb && $hasEmiAuditLb) {
                $emiStatsStmt = $this->db->prepare("
                    SELECT 
                        COALESCE(NULLIF(lb.total_emi_no, 0), lp.total_emi_no, 0) AS total_emi_no,
                        lb.emi_updated_by,
                        lb.emi_updated_at,
                        u.full_name AS emi_updated_by_name
                    FROM lead_bookings lb
                    LEFT JOIN (
                        SELECT booking_id, MAX(total_emi_no) as total_emi_no
                        FROM lead_payments
                        WHERE booking_id = ? AND total_emi_no IS NOT NULL
                        GROUP BY booking_id
                    ) lp ON lp.booking_id = lb.id
                    LEFT JOIN users u ON u.id = lb.emi_updated_by
                    WHERE lb.id = ?
                    LIMIT 1
                ");
                $emiStatsStmt->bind_param("ii", $bookingId, $bookingId);
            } else {
                $emiStatsStmt = $this->db->prepare("
                    SELECT 
                        COALESCE(MAX(total_emi_no), 0) AS total_emi_no,
                        NULL AS emi_updated_by,
                        NULL AS emi_updated_at,
                        NULL AS emi_updated_by_name
                    FROM lead_payments
                    WHERE booking_id = ? AND total_emi_no IS NOT NULL
                    LIMIT 1
                ");
                $emiStatsStmt->bind_param("i", $bookingId);
            }
            $emiStatsStmt->execute();
            $emiRes = $emiStatsStmt->get_result()->fetch_assoc();
            $totalEmiCount = $emiRes && $emiRes['total_emi_no'] ? (int) $emiRes['total_emi_no'] : 0;
            if ($totalEmiCount > 120) {
                $totalEmiCount = 42;
            }
            $emiUpdatedBy = $emiRes['emi_updated_by'] ?? null;
            $emiUpdatedAt = $emiRes['emi_updated_at'] ?? null;
            $emiUpdatedByName = $emiRes['emi_updated_by_name'] ?? null;

            // Get EMI arrays by calling parent private function logic via a duplicate here (since it's private in parent)
            $completedEmi = 0;
            $paidEmiNumbers = [];
            $paidEmiStmt = $this->db->prepare("SELECT es.emi_no FROM emi_schedules es INNER JOIN lead_payments lp ON lp.id = es.lead_payment_id WHERE lp.booking_id = ? AND lp.payment_status IN ('paid','partially_paid') AND lp.verification_status = 'verified' ORDER BY es.emi_no");
            $paidEmiStmt->bind_param("i", $bookingId);
            $paidEmiStmt->execute();
            $paidRes = $paidEmiStmt->get_result();
            while ($r = $paidRes->fetch_assoc()) {
                $paidEmiNumbers[] = (int) $r['emi_no'];
            }
            $completedEmi = count($paidEmiNumbers);
            $remainingEmiCount = max(0, $totalEmiCount - $completedEmi);

            // OPTION 1: Dynamic Remaining Per-EMI Calculation
            if ($completedEmi > 0) {
                $unscheduledCF = max(0, $remainingConstructionFee - $totalEmiDue);
                $perEmiAmount  = ($remainingEmiCount > 0) ? round($unscheduledCF / $remainingEmiCount) : 0;
            } else {
                $perEmiAmount  = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;
            }

            $pendingEmi = $remainingEmiCount;
            $pendingEmiNumbers = [];
            if ($pendingEmi > 0) {
                for ($i = $completedEmi + 1; $i <= $totalEmiCount; $i++) {
                    $pendingEmiNumbers[] = $i;
                }
            }

            // Assign fields
            $clientDetails['total_land_share_paid']                = $totalLandSharePaid;
            $clientDetails['total_land_share_paid_total']          = $totalLandSharePaidTotal;
            $clientDetails['landshare_paid']                       = $totalLandSharePaid;
            $clientDetails['shop_service_charge_target']           = $shopServiceChargeTarget;
            $clientDetails['shop_service_charge_paid']             = $shopServiceChargePaid;
            $clientDetails['shop_service_charge_paid_total']       = $shopServiceChargePaidTotal;
            $clientDetails['remaining_land_share']                 = $remainingLandShare;
            $clientDetails['remaining_land_share_total']           = $remainingLandShareTotal;
            $clientDetails['is_land_share_completed']              = $isLandShareCompleted;
            $clientDetails['is_shop_service_charge_completed']     = $isShopServiceChargeCompleted;
            $clientDetails['is_registration_paid']                 = $isRegistrationPaid;
            $clientDetails['construction_fee']                     = $constructionFee;
            $clientDetails['per_emi_amount']                       = $perEmiAmount;
            $clientDetails['shop_service_charge_pct']              = $shopServiceChargePct;
            $clientDetails['emi_updated_by']                       = $emiUpdatedBy;
            $clientDetails['emi_updated_at']                       = $emiUpdatedAt;
            $clientDetails['emi_updated_by_name']                  = $emiUpdatedByName;
            $clientDetails['construction_fee_paid']                = $constructionFeePaid;
            $clientDetails['construction_fee_paid_after_service']  = $constructionFeePaid;
            $clientDetails['remaining_construction_fee']           = $remainingConstructionFee;
            $clientDetails['total_due']                            = $totalDue;
            $clientDetails['net_received']                         = $netReceived;
            $clientDetails['total_emi_due']                        = $totalEmiDue;
            $clientDetails['total_bulk_emi_discount']              = $totalBulkEmiDiscount;
            
            $clientDetails['total_emi']                            = $totalEmiCount;
            $clientDetails['completed_emi']                        = $completedEmi;
            $clientDetails['pending_emi']                          = $pendingEmi;
            $clientDetails['paid_emi_numbers']                     = $paidEmiNumbers;
            $clientDetails['pending_emi_numbers']                  = $pendingEmiNumbers;
        }

        return $clientDetails;
    }

    public function getPaymentSummaryByBookingId($bookingId)
    {
        $clientDetails = $this->getClientByBookingId($bookingId);

        if (!$clientDetails) {
            return [
                'total_price'                          => 0,
                'agreement_value'                      => 0,
                'total_land_share'                     => 0,
                'shop_service_charge_target'           => 0,
                'shop_service_charge_pct'              => 0,
                'booking_money'                        => 0,
                'booking_money_paid'                   => 0,
                'is_booking_fully_paid'                => false,
                'total_land_share_paid'                => 0,
                'landshare_paid'                       => 0,
                'remaining_land_share'                 => 0,
                'discount_amount'                      => 0,
                'registration_paid'                    => 0,
                'is_registration_paid'                 => false,
                'is_land_share_completed'              => false,
                'is_shop_service_charge_completed'     => false,
                'shop_service_charge_paid'             => 0,
                'shop_service_charge_paid_total'       => 0,
                'construction_fee'                     => 0,
                'per_emi_amount'                       => 0,
                'construction_fee_paid'                => 0,
                'construction_fee_paid_after_service'  => 0,
                'remaining_construction_fee'           => 0,
                'total_paid'                           => 0,
                'total_due'                            => 0,
                'total_emi_due'                        => 0,
                'total_emi'                            => 0,
                'completed_emi'                        => 0,
                'pending_emi'                          => 0,
                'last_payment_date'                    => null,
                'total_service_fee'                    => 0,
                'net_received'                         => 0,
                'paid_emi_numbers'                     => [],
                'pending_emi_numbers'                  => []
            ];
        }

        $agreementValue       = (float) ($clientDetails['total_price'] * 0.30); // Agreement value conceptually is Land Share
        $discountForBooking   = (float) ($clientDetails['discount_amount'] ?? 0);
        $bookingMoneyTotal    = (float) ($clientDetails['booking_money_total'] ?? 0);
        $bookingMoneyPaid     = (float) ($clientDetails['booking_money_paid'] ?? 0);
        
        $effectiveBookingTarget = max(0, ($clientDetails['total_price'] * 0.15) - $discountForBooking);
        $isBookingFullyPaid  = ($effectiveBookingTarget > 0 && $bookingMoneyPaid >= ($effectiveBookingTarget - 1.0));
        if ($effectiveBookingTarget <= 0 && $clientDetails['total_price'] <= 0) {
            $isBookingFullyPaid = false; 
        }

        return [
            'total_price'                          => $clientDetails['total_price'],
            'agreement_value'                      => $agreementValue,
            'total_land_share'                     => $clientDetails['total_price'] * 0.30,
            'shop_service_charge_target'           => $clientDetails['shop_service_charge_target'] ?? 0,
            'shop_service_charge_pct'              => $clientDetails['shop_service_charge_pct'] ?? 0,
            'booking_money'                        => $clientDetails['booking_money'],
            'booking_money_total'                  => $bookingMoneyTotal,
            'booking_money_paid'                   => $bookingMoneyPaid,
            'effective_booking_target'             => $effectiveBookingTarget,
            'is_booking_fully_paid'                => $isBookingFullyPaid,
            'total_land_share_paid'                => $clientDetails['total_land_share_paid'],
            'total_land_share_paid_total'          => $clientDetails['total_land_share_paid_total'] ?? $clientDetails['total_land_share_paid'],
            'landshare_paid'                       => $clientDetails['landshare_paid'],
            'remaining_land_share'                 => $clientDetails['remaining_land_share_total'] ?? $clientDetails['remaining_land_share'],
            'remaining_land_share_total'           => $clientDetails['remaining_land_share_total'] ?? $clientDetails['remaining_land_share'],
            'discount_amount'                      => $clientDetails['discount_amount'],
            'registration_paid'                    => $clientDetails['registration_paid'],
            'registration_paid_total'              => $clientDetails['registration_paid_total'] ?? $clientDetails['registration_paid'],
            'registration_money'                   => $clientDetails['registration_paid'],
            'is_registration_paid'                 => $clientDetails['is_registration_paid'],
            'is_land_share_completed'              => $clientDetails['is_land_share_completed'],
            'is_shop_service_charge_completed'     => $clientDetails['is_shop_service_charge_completed'] ?? false,
            'shop_service_charge_paid'             => $clientDetails['shop_service_charge_paid'] ?? 0,
            'shop_service_charge_paid_total'       => $clientDetails['shop_service_charge_paid_total'] ?? 0,
            'construction_fee'                     => $clientDetails['construction_fee'],
            'per_emi_amount'                       => $clientDetails['per_emi_amount'],
            'construction_fee_paid'                => $clientDetails['construction_fee_paid'],
            'construction_fee_paid_after_service'  => $clientDetails['construction_fee_paid_after_service'],
            'remaining_construction_fee'           => $clientDetails['remaining_construction_fee'],
            'total_paid'                           => $clientDetails['total_paid'],
            'total_due'                            => $clientDetails['total_due'],
            'total_emi_due'                        => $clientDetails['total_emi_due'],
            'total_bulk_emi_discount'              => $clientDetails['total_bulk_emi_discount'],
            'total_emi'                            => $clientDetails['total_emi'],
            'completed_emi'                        => $clientDetails['completed_emi'],
            'pending_emi'                          => $clientDetails['pending_emi'],
            'last_payment_date'                    => $clientDetails['last_payment_date'],
            'total_service_fee'                    => 0, // No service fee on EMIs
            'net_received'                         => $clientDetails['net_received'],
            'paid_emi_numbers'                     => $clientDetails['paid_emi_numbers'],
            'pending_emi_numbers'                  => $clientDetails['pending_emi_numbers']
        ];
    }


    
    /**
     * Add EMI transaction with automated outstanding settlement and splitting
     */
    public function addTransactionWithEMI($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            $bookingId    = (int) $transactionData['booking_id'];
            $totalAmount  = (float) $transactionData['amount'];

            // 0. Get payment summary for validations
            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            if (empty($summary['is_shop_service_charge_completed'])) {
                $svcTarget = (float) ($summary['shop_service_charge_target'] ?? 0);
                $svcPaid   = (float) ($summary['shop_service_charge_paid_total'] ?? 0);
                throw new Exception("Shop service charge must be fully paid before EMI. Paid: ৳" . number_format($svcPaid, 2) . " / Required: ৳" . number_format($svcTarget, 2));
            }

            // Service charge on FULL amount (user's answer: YES)
            $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
            $totalServiceFee   = ($serviceChargeRate > 0) ? round($totalAmount * ($serviceChargeRate / 100)) : 0;

            // Construction fee cap check (accounts for existing bulk discounts)
            $constructionFee      = (float) ($summary['construction_fee'] ?? 0);
            $emiPaidSoFar         = (float) ($summary['emi_paid_total'] ?? 0);
            $perEmiAmount         = (float) ($summary['per_emi_amount'] ?? 0);
            $existingBulkDiscount = (float) ($summary['total_bulk_emi_discount'] ?? 0);
            
            // Bulk discount validation
            $bulkEmiDiscount = (float) ($transactionData['bulk_emi_discount'] ?? 0);
            
            $effectiveCF          = max(0, $constructionFee - $existingBulkDiscount - $bulkEmiDiscount);

            if ($bulkEmiDiscount > 0) {
                // Discount only allowed when paying >= 2 EMIs worth
                if ($perEmiAmount > 0 && $totalAmount < ($perEmiAmount * 2)) {
                    throw new Exception("Bulk EMI discount requires paying at least 2 EMI installments at once (minimum: ৳" . number_format($perEmiAmount * 2, 2) . ").");
                }
                // Discount cannot exceed remaining construction fee after this payment
                $remainingAfterPayment = $effectiveCF - $emiPaidSoFar - $totalAmount;
                if ($bulkEmiDiscount > $remainingAfterPayment) {
                    throw new Exception("Bulk discount (৳" . number_format($bulkEmiDiscount, 2) . ") exceeds remaining construction fee after payment (৳" . number_format(max(0, $remainingAfterPayment), 2) . "). Max discount: ৳" . number_format(max(0, $remainingAfterPayment), 2));
                }
            }

            if (($emiPaidSoFar + $totalAmount) > $effectiveCF) {
                $maxPayable = max(0, $effectiveCF - $emiPaidSoFar);
                throw new Exception("EMI payment exceeds remaining construction fee. Maximum payable: ৳" . number_format($maxPayable, 2));
            }

            // Common payment metadata
            $attachmentPathsJson = null;
            if (!empty($transactionData['attachment_paths'])) {
                $attachmentPathsJson = is_array($transactionData['attachment_paths'])
                    ? json_encode($transactionData['attachment_paths'])
                    : $transactionData['attachment_paths'];
            }
            $paymentMethod  = $transactionData['payment_method']   ?? 'cash';
            $paymentDate    = $transactionData['payment_date']     ?? date('Y-m-d');
            $transactionRef = $transactionData['transaction_ref']  ?? '';
            $bankId         = !empty($transactionData['bank_id']) ? (int) $transactionData['bank_id'] : null;
            $bankName       = $transactionData['bank_name']        ?? '';
            $bankAccNo      = $transactionData['bank_account_no']  ?? '';
            $chequeNo       = $transactionData['cheque_no']        ?? '';
            $onlineType     = $transactionData['online_type']      ?? '';
            $createdBy      = (int) ($transactionData['created_by'] ?? 1);

            $pool = $totalAmount;
            $settledDues = 0;
            $newInstallments = 0;
            $settlementDetails = [];

            // ─── PHASE 1: Auto-settle outstanding dues (FIFO — oldest first) ───
            $outstandingDues = $this->getOutstandingEmiDues($bookingId);

            foreach ($outstandingDues as $due) {
                if ($pool <= 0) break;

                $dueAmount = (float) $due['due_emi_amount'];
                $settle = min($pool, $dueAmount);

                // Service fee for this settlement portion
                $settleSvcFee = ($serviceChargeRate > 0) ? round($settle * ($serviceChargeRate / 100)) : 0;

                // Create settlement audit record
                $settleStmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_id, bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$settleStmt) throw new Exception("Settlement prepare failed: " . $this->db->error);

                $stType = 'emi_due_settlement';
                $stPayType = 'emi'; // Use 'emi' for DB ENUM consistency; settlement tracked via settlement_type
                $stStatus = 'paid';
                $stLandShare = 0.0;
                $stDue = 0.0;
                $parentId = (int) $due['id'];

                $settleStmt->bind_param(
                    "isdddssssisssssdisi",
                    $bookingId, $stPayType, $settle, $stLandShare, $settleSvcFee,
                    $paymentMethod, $stStatus, $paymentDate, $transactionRef,
                    $bankId, $bankName, $bankAccNo, $chequeNo, $onlineType,
                    $attachmentPathsJson, $stDue, $parentId, $stType, $createdBy
                );

                if (!$settleStmt->execute()) throw new Exception("Settlement insert failed: " . $settleStmt->error);

                // Update parent's due_emi_amount in lead_payments
                $newDue = max(0, $dueAmount - $settle);
                $newParentStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                $updStmt = $this->db->prepare("UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?");
                $updStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                $updStmt->execute();

                // Also sync emi_schedules
                $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                $schedSyncStmt->bind_param('dsi', $newDue, $newParentStatus, $parentId);
                $schedSyncStmt->execute();
                $schedSyncStmt->close();

                $pool -= $settle;
                $settledDues++;
                $settlementDetails[] = [
                    'emi_no' => $due['emi_no'],
                    'settled' => $settle,
                    'remaining_due' => $newDue
                ];
            }

            // ─── PHASE 2: Allocate remaining pool to new installment(s) ───
            $firstPaymentId = null;
            $totalAllocated = 0;  // Track total EMI allocated in this transaction

            while ($pool > 0) {
                // How much goes to this installment
                $thisEmi = ($perEmiAmount > 0) ? min($pool, $perEmiAmount) : $pool;

                // Calculate remaining CF after ALL installments so far (including this one)
                // If this payment fully covers the remaining CF, there's no real due
                $cfRemainingBeforeThis = max(0, $effectiveCF - $emiPaidSoFar - $totalAllocated);
                $cfRemainingAfterThis = max(0, $cfRemainingBeforeThis - $thisEmi);
                if ($cfRemainingAfterThis <= 0) {
                    // Last installment — no due even if less than per EMI
                    $thisDue = 0;
                } else {
                    // Expected for this installment is capped at what's actually remaining
                    $expectedForThis = ($perEmiAmount > 0) ? min($perEmiAmount, $cfRemainingBeforeThis) : 0;
                    $thisDue = ($expectedForThis > 0 && $thisEmi < $expectedForThis)
                               ? max(0, round($expectedForThis - $thisEmi))
                               : 0;
                }
                $thisStatus = ($thisDue > 0) ? 'partially_paid' : 'paid';

                // Service fee for this installment
                $thisSvcFee = ($serviceChargeRate > 0) ? round($thisEmi * ($serviceChargeRate / 100)) : 0;

                // Get next EMI number
                $nextEmiNo = $this->getNextAvailableEmiNo($bookingId);

                // Insert lead_payments row for this installment
                $emiStmt = $this->db->prepare("
                    INSERT INTO lead_payments (
                        booking_id, payment_type, amount, land_share_amount, service_fee,
                        payment_method, payment_status, payment_date, transaction_ref,
                        bank_id, bank_name, bank_account_no, cheque_no, online_type,
                        attachment_paths, due_emi_amount, bulk_emi_discount, bulk_pay_amount, created_by
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$emiStmt) throw new Exception("EMI payment prepare failed: " . $this->db->error);

                $emiPayType = 'emi';
                $emiLandShare = 0.0;
                // Attach discount + bulk pay amount only to the FIRST installment
                $thisDiscount = (!$firstPaymentId && $bulkEmiDiscount > 0) ? $bulkEmiDiscount : 0.0;
                $thisBulkPayAmount = (!$firstPaymentId && $newInstallments === 0) ? $totalAmount : null;

                $emiStmt->bind_param(
                    "isdddssssisssssdddi",
                    $bookingId, $emiPayType, $thisEmi, $emiLandShare, $thisSvcFee,
                    $paymentMethod, $thisStatus, $paymentDate, $transactionRef,
                    $bankId, $bankName, $bankAccNo, $chequeNo, $onlineType,
                    $attachmentPathsJson, $thisDue, $thisDiscount, $thisBulkPayAmount, $createdBy
                );

                if (!$emiStmt->execute()) throw new Exception("EMI payment insert failed: " . $emiStmt->error);

                $paymentId = $this->db->insert_id;
                if (!$firstPaymentId) $firstPaymentId = $paymentId;

                // Insert emi_schedule row
                $schedStmt = $this->db->prepare("
                    INSERT INTO emi_schedules (
                        lead_booking_id, emi_no, emi_amount, service_fee,
                        due_emi_amount, due_date, status, lead_payment_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$schedStmt) throw new Exception("EMI schedule prepare failed: " . $this->db->error);

                $schedStatus = ($thisDue > 0) ? 'paid' : 'paid'; // schedule row always 'paid' (payment was recorded)

                $schedStmt->bind_param(
                    "iidddssi",
                    $bookingId, $nextEmiNo, $thisEmi, $thisSvcFee,
                    $thisDue, $paymentDate, $schedStatus, $paymentId
                );

                if (!$schedStmt->execute()) throw new Exception("EMI schedule insert failed: " . $schedStmt->error);

                $pool -= $thisEmi;
                $totalAllocated += $thisEmi;
                $newInstallments++;
            }

            $this->db->commit();

            // Build result message
            $msgParts = [];
            if ($settledDues > 0) {
                $settledTotal = array_sum(array_column($settlementDetails, 'settled'));
                $msgParts[] = $settledDues . ' outstanding due(s) settled (৳' . number_format($settledTotal, 2) . ')';
            }
            if ($newInstallments > 0) {
                $msgParts[] = $newInstallments . ' new EMI installment(s) recorded';
            }
            if ($totalServiceFee > 0) {
                $msgParts[] = 'Service Fee deducted (৳' . number_format($totalServiceFee, 2) . ')';
            }
            
            $msg = implode(' | ', $msgParts);
            if (empty($msg)) $msg = 'Transaction processed successfully.';

            return [
                'success' => true,
                'message' => $msg,
                'payment_id' => $firstPaymentId // return first ID for reference
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }
    public function addTransaction($transactionData, $emiScheduleData = null)
    {
        $this->db->begin_transaction();

        try {
            // Validate required fields
            if (
                empty($transactionData['booking_id']) || empty($transactionData['payment_type']) ||
                empty($transactionData['amount']) || empty($transactionData['payment_status'])
            ) {
                throw new Exception("Missing required transaction data");
            }

            $bookingId = (int) $transactionData['booking_id'];
            $paymentType = $transactionData['payment_type'];
            $amount = (float) $transactionData['amount'];
            $landShareAmount = 0;
            $serviceFee = 0;

            // Get current payment summary for validations
            $summary = $this->getPaymentSummaryByBookingId($bookingId);

            // Validate based on payment type
            switch ($paymentType) {
                case 'booking_money':
                    if ($summary['is_booking_fully_paid']) {
                        throw new Exception("Booking money has already been fully paid for this booking.");
                    }
                    $remainingBooking = ($summary['total_price'] * 0.15) - $summary['booking_money_paid'];
                    if ($amount > $remainingBooking) {
                        throw new Exception("Amount exceeds outstanding booking balance: ৳" . number_format($remainingBooking, 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'land_share':
                    if ($summary['remaining_land_share'] <= 0) {
                        throw new Exception("Land share payment is already completed!");
                    }
                    if ($amount > $summary['remaining_land_share']) {
                        throw new Exception("Land share amount cannot exceed remaining land share: ৳" . number_format($summary['remaining_land_share'], 2));
                    }
                    $landShareAmount = $amount;
                    break;

                case 'registration':
                    break;

                case 'shop_service_charge':
                    $svcTarget = (float) ($summary['shop_service_charge_target'] ?? 0);
                    $svcPaid   = (float) ($summary['shop_service_charge_paid_total'] ?? 0);
                    $svcRemaining = max(0, $svcTarget - $svcPaid);
                    if ($svcRemaining <= 0) {
                        throw new Exception("Shop service charge is already fully paid!");
                    }
                    if ($amount > $svcRemaining) {
                        throw new Exception("Amount exceeds remaining shop service charge: ৳" . number_format($svcRemaining, 2));
                    }
                    break;

                

                case 'emi_due_settlement':
                    // Validate parent EMI payment
                    if (empty($transactionData['parent_payment_id'])) {
                        throw new Exception("Parent EMI payment ID is required for EMI due settlement.");
                    }
                    $parentId  = (int) $transactionData['parent_payment_id'];
                    $parentRow = $this->db->query("
                        SELECT id, booking_id, due_emi_amount FROM lead_payments
                        WHERE id = {$parentId} AND payment_type = 'emi' AND booking_id = {$bookingId}
                    ");
                    if (!$parentRow || !($parentData = $parentRow->fetch_assoc())) {
                        throw new Exception("Invalid parent EMI payment reference.");
                    }
                    $remainingDue = (float) $parentData['due_emi_amount'];
                    if ($remainingDue <= 0) {
                        throw new Exception("This EMI has no outstanding due balance.");
                    }
                    if ($amount > $remainingDue) {
                        throw new Exception("Settle amount (৳" . number_format($amount, 2) . ") cannot exceed outstanding due (৳" . number_format($remainingDue, 2) . ").");
                    }
                    // Service fee applies on the settled amount at same rate
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;
                    $dueEmiAmount      = 0; // settlement itself has no new due
                    // Change $paymentType to 'emi' to satisfy ENUM constraint, settlement_type will track the difference
                    $paymentType = 'emi';
                    break;

                default:
                    break;
            }

            // Insert payment record (verification_status defaults to 'pending' via DB default)
            $bankId            = !empty($transactionData['bank_id']) ? (int)$transactionData['bank_id'] : null;
            $attachmentPaths   = !empty($transactionData['attachment_paths']) ? json_encode($transactionData['attachment_paths']) : null;
            $parentPaymentId   = !empty($transactionData['parent_payment_id']) ? (int)$transactionData['parent_payment_id'] : null;
            $settlementType    = !empty($transactionData['settlement_type']) ? $transactionData['settlement_type'] : null;

            $stmt = $this->db->prepare("
                INSERT INTO lead_payments (
                    booking_id, payment_type, amount, land_share_amount, service_fee,
                    payment_method, payment_status, payment_date, transaction_ref,
                    bank_id, bank_name, bank_account_no, cheque_no, online_type,
                    attachment_paths, due_emi_amount, parent_payment_id, settlement_type, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->db->error);
            }

            $bankNameLookup = '';
            if ($bankId) {
                $bRow = $this->db->query("SELECT CONCAT(bank_name, ' - ', account_name) as label FROM company_bank_accounts WHERE id = {$bankId}");
                if ($bRow) { $bData = $bRow->fetch_assoc(); $bankNameLookup = $bData['label'] ?? ''; }
            }

            // Extract values — bind_param requires pass-by-reference variables
            $p_method    = $transactionData['payment_method'] ?? 'cash';
            $p_status    = $transactionData['payment_status'];
            $p_date      = $transactionData['payment_date'] ?? date('Y-m-d');
            $p_txRef     = $transactionData['transaction_ref'] ?? '';
            $p_bankAccNo = $transactionData['bank_account_no'] ?? '';
            $p_chequeNo  = $transactionData['cheque_no'] ?? '';
            $p_onlineType= $transactionData['online_type'] ?? '';
            $p_createdBy = (int) ($transactionData['created_by'] ?? 0);

            $stmt->bind_param(
                "isdddssssisssssdisi",
                $bookingId,
                $paymentType,
                $amount,
                $landShareAmount,
                $serviceFee,
                $p_method,
                $p_status,
                $p_date,
                $p_txRef,
                $bankId,
                $bankNameLookup,
                $p_bankAccNo,
                $p_chequeNo,
                $p_onlineType,
                $attachmentPaths,
                $dueEmiAmount,
                $parentPaymentId,
                $settlementType,
                $p_createdBy
            );

            if (!$stmt->execute()) {
                throw new Exception("Transaction insert failed: " . $stmt->error);
            }

            $paymentId = $this->db->insert_id;

            // If this is an EMI due settlement, reduce the due on the original payment
            if (!empty($transactionData['settlement_type']) && $transactionData['settlement_type'] === 'emi_due_settlement' && $parentPaymentId) {
                $newDue = max(0, (float)$parentData['due_emi_amount'] - $amount);
                $newStatus = ($newDue <= 0) ? 'paid' : 'partially_paid';
                $updStmt = $this->db->prepare("
                    UPDATE lead_payments SET due_emi_amount = ?, payment_status = ? WHERE id = ?
                ");
                $updStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $updStmt->execute();

                // Also sync emi_schedules
                $schedSyncStmt = $this->db->prepare("UPDATE emi_schedules SET due_emi_amount = ?, status = ? WHERE lead_payment_id = ?");
                $schedSyncStmt->bind_param('dsi', $newDue, $newStatus, $parentPaymentId);
                $schedSyncStmt->execute();
                $schedSyncStmt->close();
            }

            if ($paymentType === 'emi' && $emiScheduleData) {
                // Ensure the backend-calculated due amount is passed to the schedule!
                $emiScheduleData['due_emi_amount'] = $dueEmiAmount;
                $this->createEMISchedule($bookingId, $paymentId, $amount, $serviceFee, $emiScheduleData);
            }

            $this->db->commit();

            return [
                'success'           => true,
                'message'           => 'Transaction added successfully!',
                'payment_id'        => $paymentId,
                'service_fee'       => $serviceFee,
                'land_share_amount' => $landShareAmount,
                'due_emi_amount'    => $dueEmiAmount
            ];
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage()
            ];
        }
    }
}
