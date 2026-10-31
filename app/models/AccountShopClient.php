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
                COALESCE(f.development_charges, 0) as flat_development_charges,
                COALESCE(f.land_share_value, 0) as flat_land_share_value,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'land_share'), 
                    0
                ) as land_share_type_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'land_share'), 
                    0
                ) as land_share_type_paid_total,
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
                     AND lp.payment_type = 'others'
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'), 
                    0
                ) as others_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_type = 'others'
                     AND lp.verification_status != 'rejected'), 
                    0
                ) as others_paid_total,
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
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid,
                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE {$lpTrash}lp.booking_id = lb.id 
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid_total,
                COALESCE(
                    (SELECT SUM(lp.due_emi_amount)
                     FROM lead_payments lp
                     WHERE {$lpTrash}lp.booking_id = lb.id
                     AND lp.payment_type = 'emi'
                     AND lp.due_emi_amount > 0
                     AND lp.verification_status != 'rejected'
                     AND (lp.settlement_type IS NULL OR lp.settlement_type != 'emi_due_settlement')
                     AND NOT EXISTS (
                         SELECT 1 FROM lead_payments child
                         WHERE child.is_trashed = 0 AND child.parent_payment_id = lp.id
                         AND (child.payment_type = 'emi_due_settlement' OR child.settlement_type = 'emi_due_settlement')
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

            // Shop logic — Land Share = flats.land_share_value (fallback 30% of price if not set)
            $flatLandShareValue    = (float) ($clientDetails['flat_land_share_value'] ?? 0);
            $totalLandShare        = $flatLandShareValue > 0 ? $flatLandShareValue : $totalPrice * 0.30;
            // Shop logic — Shop Service Charge is now stored directly as a fixed BDT amount in flats.shop_service_charge
            $shopServiceChargeTarget = (float) ($clientDetails['shop_service_charge'] ?? 0);
            $shopServiceChargePct    = ($totalPrice > 0 && $shopServiceChargeTarget > 0) ? round(($shopServiceChargeTarget / $totalPrice) * 100, 2) : 0;

            $bookingMoney          = (float) $clientDetails['booking_money'];
            $bookingMoneyTotal     = (float) ($clientDetails['booking_money_total'] ?? 0);

            // Paid amounts
            $totalPaid             = (float) $clientDetails['total_paid'];
            $bookingMoneyPaid      = (float) $clientDetails['booking_money_paid'];
            $registrationPaid      = (float) $clientDetails['registration_paid'];
            $registrationPaidTotal = (float) ($clientDetails['registration_paid_total'] ?? $registrationPaid);
            // Development Charge (flats.development_charges) is tracked separately from Shop Service Charge
            $flatDevCharges        = (float) ($clientDetails['flat_development_charges'] ?? 0);
            $devChargePaid         = (float) ($clientDetails['development_charge_paid'] ?? 0);
            $devChargePaidTotal    = (float) ($clientDetails['development_charge_paid_total'] ?? $devChargePaid);
            $devChargeRemaining    = max(0, round($flatDevCharges - $devChargePaidTotal));
            $isDevChargeCompleted  = ($flatDevCharges <= 0) || ($devChargePaidTotal >= $flatDevCharges || $devChargePaid >= $flatDevCharges);
            $shopServiceChargePaid      = (float) $clientDetails['shop_service_charge_paid'];
            $shopServiceChargePaidTotal = (float) ($clientDetails['shop_service_charge_paid_total'] ?? $shopServiceChargePaid);
            $emiPaid               = (float) $clientDetails['emi_paid'];
            $emiPaidTotal          = (float) ($clientDetails['emi_paid_total'] ?? $emiPaid);
            
            // Shops have no per-EMI service fee
            $totalServiceFee       = 0;

            // Land share group = booking money + land_share payments + registration
            $landShareTypePaid       = (float) ($clientDetails['land_share_type_paid'] ?? 0);
            $landShareTypePaidTotal  = (float) ($clientDetails['land_share_type_paid_total'] ?? $landShareTypePaid);
            $totalLandSharePaid      = $bookingMoney + $landShareTypePaid + $registrationPaid;
            $totalLandSharePaidTotal = $bookingMoneyTotal + $landShareTypePaidTotal + $registrationPaidTotal;

            $landSharePaymentsOnly    = round($bookingMoney + $landShareTypePaid + $registrationPaid);
            $effectiveLandShareTarget = max(0, round($totalLandShare - $discountAmount));
            $remainingLandShare       = max(0, round($effectiveLandShareTarget - $landSharePaymentsOnly));
            $remainingLandShareTotal  = max(0, round($effectiveLandShareTarget - $totalLandSharePaidTotal));
            $isLandShareCompleted     = ($remainingLandShareTotal <= 0 || $remainingLandShare <= 0);

            $constructionFee = max(0, round($totalPrice - ($totalLandShare + $shopServiceChargeTarget + $flatDevCharges)));

            $isShopServiceChargeCompleted = ($shopServiceChargeTarget > 0 && ($shopServiceChargePaidTotal >= $shopServiceChargeTarget || $shopServiceChargePaid >= $shopServiceChargeTarget));
            if ($shopServiceChargeTarget <= 0) {
                $isShopServiceChargeCompleted = true;
            }

            $constructionFeePaid         = $emiPaid;
            $totalBulkEmiDiscount        = (float) ($clientDetails['total_bulk_emi_discount'] ?? 0);
            $remainingConstructionFee    = max(0, round($constructionFee - $constructionFeePaid - $totalBulkEmiDiscount));

            $isRegistrationPaid = ($registrationPaid > 0);

            $othersPaid                 = (float) ($clientDetails['others_paid'] ?? 0);
            $othersPaidTotal            = (float) ($clientDetails['others_paid_total'] ?? $othersPaid);

            // Note: Payments under 'others' are outside shop unit price and DO NOT reduce flat/shop due
            $unitPaid     = max(0, $totalPaid - $othersPaid);
            $totalDue     = max(0, round($totalPrice - $discountAmount - $unitPaid - $totalBulkEmiDiscount));
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

            // Fixed Per-EMI Calculation: Total Construction Fee ÷ Total EMI Count
            $perEmiAmount = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;

            $pendingEmi = $remainingEmiCount;
            $pendingEmiNumbers = [];
            if ($pendingEmi > 0) {
                for ($i = $completedEmi + 1; $i <= $totalEmiCount; $i++) {
                    $pendingEmiNumbers[] = $i;
                }
            }

            // Assign fields
            $clientDetails['total_land_share']                     = $totalLandShare;
            $clientDetails['total_land_share_paid']                = $totalLandSharePaid;
            $clientDetails['total_land_share_paid_total']          = $totalLandSharePaidTotal;
            $clientDetails['landshare_paid']                       = $totalLandSharePaid;
            $clientDetails['shop_service_charge_target']           = $shopServiceChargeTarget;
            $clientDetails['shop_service_charge_paid']             = $shopServiceChargePaid;
            $clientDetails['shop_service_charge_paid_total']       = $shopServiceChargePaidTotal;
            $clientDetails['flat_development_charges']             = $flatDevCharges;
            $clientDetails['development_charge_paid']              = $devChargePaid;
            $clientDetails['development_charge_paid_total']        = $devChargePaidTotal;
            $clientDetails['development_charge_remaining']         = $devChargeRemaining;
            $clientDetails['is_dev_charge_completed']              = $isDevChargeCompleted;
            $clientDetails['shop_service_charge_remaining']        = max(0, round($shopServiceChargeTarget - $shopServiceChargePaidTotal));
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
            $clientDetails['emi_paid_total']                       = $emiPaidTotal;
            $clientDetails['construction_fee_paid_after_service']  = $constructionFeePaid;
            $clientDetails['remaining_construction_fee']           = $remainingConstructionFee;
            $clientDetails['total_due']                            = $totalDue;
            $clientDetails['others_paid']                          = $othersPaid;
            $clientDetails['others_paid_total']                    = $othersPaidTotal;
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

        // Booking target = lead_bookings.agreement_value (same as flats); fallback 15% of price if not set
        $rawAgreementValue    = (float) ($clientDetails['agreement_value'] ?? 0);
        $agreementValue       = $rawAgreementValue > 0 ? $rawAgreementValue : (float) ($clientDetails['total_price'] * 0.15);
        $discountForBooking   = (float) ($clientDetails['discount_amount'] ?? 0);
        $bookingMoneyTotal    = (float) ($clientDetails['booking_money_total'] ?? 0);
        $bookingMoneyPaid     = (float) ($clientDetails['booking_money_paid'] ?? 0);
        
        $effectiveBookingTarget = max(0, $agreementValue - $discountForBooking);
        $isBookingFullyPaid  = ($effectiveBookingTarget > 0 && $bookingMoneyPaid >= ($effectiveBookingTarget - 1.0));
        if ($effectiveBookingTarget <= 0 && $clientDetails['total_price'] <= 0) {
            $isBookingFullyPaid = false; 
        }

        return [
            'total_price'                          => $clientDetails['total_price'],
            'agreement_value'                      => $agreementValue,
            'total_land_share'                     => $clientDetails['total_land_share'] ?? ($clientDetails['total_price'] * 0.30),
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
            'shop_service_charge_remaining'        => $clientDetails['shop_service_charge_remaining'] ?? 0,
            'development_charge_paid'              => $clientDetails['development_charge_paid'] ?? 0,
            'development_charge_paid_total'        => $clientDetails['development_charge_paid_total'] ?? 0,
            'development_charge_remaining'         => $clientDetails['development_charge_remaining'] ?? 0,
            'is_dev_charge_completed'              => $clientDetails['is_dev_charge_completed'] ?? true,
            'flat_development_charges'             => $clientDetails['flat_development_charges'] ?? 0,
            'construction_fee'                     => $clientDetails['construction_fee'],
            'per_emi_amount'                       => $clientDetails['per_emi_amount'],
            'construction_fee_paid'                => $clientDetails['construction_fee_paid'],
            'emi_paid_total'                       => $clientDetails['emi_paid_total'] ?? $clientDetails['construction_fee_paid'],
            'construction_fee_paid_after_service'  => $clientDetails['construction_fee_paid_after_service'],
            'remaining_construction_fee'           => $clientDetails['remaining_construction_fee'],
            'total_paid'                           => $clientDetails['total_paid'],
            'others_paid'                          => $clientDetails['others_paid'] ?? 0,
            'others_paid_total'                    => $clientDetails['others_paid_total'] ?? 0,
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



}
