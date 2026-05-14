import re

path = r'c:\xampp\htdocs\TPHL-ERP\app\models\AccountShopClient.php'

with open(path, 'r', encoding='utf-8') as f:
    content = f.read()

# 1. Rename class
content = content.replace('class AccountClient extends Model', 'class AccountShopClient extends Model')

# 2. Update getClientByBookingId SQL
# Replace service_charge_per_emi and development_charges in SQL
content = content.replace(
    'COALESCE(p.service_charge_per_emi, 0) as service_charge_per_emi,',
    'COALESCE(f.shop_service_charge, 0) as shop_service_charge,'
)
content = content.replace(
    'COALESCE(f.development_charges, 0) as flat_development_charges,',
    ''
)

# Update development_charge_paid to shop_service_charge_paid
content = content.replace(
"""                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid,""",
"""                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status = 'verified'
                     AND lp.payment_type = 'shop_service_charge'), 
                    0
                ) as shop_service_charge_paid,"""
)
content = content.replace(
"""                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'development_charge'), 
                    0
                ) as development_charge_paid_total,""",
"""                COALESCE(
                    (SELECT SUM(lp.amount) 
                     FROM lead_payments lp 
                     WHERE lp.booking_id = lb.id 
                     AND lp.payment_status IN ('paid','partially_paid')
                     AND lp.verification_status != 'rejected'
                     AND lp.payment_type = 'shop_service_charge'), 
                    0
                ) as shop_service_charge_paid_total,"""
)

# 3. Update getClientByBookingId logic block
# Find the start of the logic block
logic_start = """            // Cast all amounts to float for calculations
            $totalPrice            = (float) $clientDetails['total_price'];
            $totalLandShare        = (float) $clientDetails['total_land_share'];   // flat.land_share_value (fixed target)
            $flatDevCharges        = (float) ($clientDetails['flat_development_charges'] ?? 0); // flat.development_charges (fixed)
            $serviceChargePerEmi   = (float) ($clientDetails['service_charge_per_emi'] ?? 0); // project %
            $bookingMoney          = (float) $clientDetails['booking_money'];      // verified booking money
            $discountAmount        = (float) $clientDetails['discount_amount'];"""

logic_replacement = """            // Cast all amounts to float for calculations
            $totalPrice            = (float) $clientDetails['total_price'];
            $discountAmount        = (float) $clientDetails['discount_amount'];
            
            // Percentage Rules for Shops
            $totalLandShare        = $totalPrice * 0.30;
            $shopServiceChargePct  = (float) ($clientDetails['shop_service_charge'] ?? 0);
            $shopServiceChargeTarget = ($totalPrice * $shopServiceChargePct) / 100;
            
            $bookingMoney          = (float) $clientDetails['booking_money'];      // verified booking money"""
content = content.replace(logic_start, logic_replacement)

paid_start = """            // Paid amounts
            $totalPaid             = (float) $clientDetails['total_paid'];
            $bookingMoneyPaid      = (float) $clientDetails['booking_money_paid'];
            $additionalLandSharePaid = (float) $clientDetails['additional_land_share_paid'];
            $registrationPaid      = (float) $clientDetails['registration_paid'];
            $developmentChargePaid = (float) $clientDetails['development_charge_paid'];
            $emiPaid               = (float) $clientDetails['emi_paid'];
            $totalServiceFee       = (float) $clientDetails['total_service_fee'];"""

paid_replacement = """            // Paid amounts
            $totalPaid             = (float) $clientDetails['total_paid'];
            $bookingMoneyPaid      = (float) $clientDetails['booking_money_paid'];
            $additionalLandSharePaid = (float) $clientDetails['additional_land_share_paid'];
            $registrationPaid      = (float) $clientDetails['registration_paid'];
            $shopServiceChargePaid = (float) $clientDetails['shop_service_charge_paid'];
            $emiPaid               = (float) $clientDetails['emi_paid'];
            $totalServiceFee       = (float) $clientDetails['total_service_fee']; // Should be 0 for shops via EMIs"""
content = content.replace(paid_start, paid_replacement)

calc_start = """            // Total Construction Fee = Total Price - (Land Share + Dev Charges)
            // Discount only affects land share remaining, NOT the construction fee
            $constructionFee = max(0, $totalPrice - ($totalLandShare + $flatDevCharges));

            // Fixed Per-EMI Amount = Construction Fee / Total EMI count
            $emiStats = $this->getEMIStatistics($bookingId);
            $totalEmiCount = $emiStats['total_emi'];
            $perEmiAmount  = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;

            // Dev charge: completed when paid amount >= flat fixed development_charges
            $isDevChargeCompleted = ($flatDevCharges > 0 && $developmentChargePaid >= $flatDevCharges);
            // If there's no dev charge defined on the flat, treat it as complete (not blocking EMI)
            if ($flatDevCharges <= 0) {
                $isDevChargeCompleted = true;
            }"""

calc_replacement = """            // Total Construction Fee = Total Price - (Land Share + Shop Service Charge)
            // Discount affects land share remaining.
            $constructionFee = max(0, $totalPrice - ($totalLandShare + $shopServiceChargeTarget));

            // Fixed Per-EMI Amount = Construction Fee / Total EMI count
            $emiStats = $this->getEMIStatistics($bookingId);
            $totalEmiCount = $emiStats['total_emi'];
            $perEmiAmount  = ($totalEmiCount > 0) ? round($constructionFee / $totalEmiCount) : 0;

            // Shop Service Charge completion
            $isShopServiceChargeCompleted = ($shopServiceChargeTarget > 0 && $shopServiceChargePaid >= $shopServiceChargeTarget);
            if ($shopServiceChargeTarget <= 0) {
                $isShopServiceChargeCompleted = true;
            }"""
content = content.replace(calc_start, calc_replacement)

# Update return array of getClientByBookingId
content = content.replace(
    "$clientDetails['flat_development_charges']             = $flatDevCharges;",
    "$clientDetails['shop_service_charge_target']           = $shopServiceChargeTarget;"
)
content = content.replace(
    "$clientDetails['development_charge_paid']              = $developmentChargePaid;",
    "$clientDetails['shop_service_charge_paid']             = $shopServiceChargePaid;"
)
content = content.replace(
    "$clientDetails['is_dev_charge_completed']              = $isDevChargeCompleted;",
    "$clientDetails['is_shop_service_charge_completed']     = $isShopServiceChargeCompleted;"
)
content = content.replace(
    "$clientDetails['service_charge_per_emi']               = $serviceChargePerEmi;",
    "$clientDetails['shop_service_charge_pct']              = $shopServiceChargePct;"
)

# 4. Update getPaymentSummaryByBookingId
content = content.replace(
    "'flat_development_charges'             => $clientDetails['flat_development_charges'] ?? 0,",
    "'shop_service_charge_target'           => $clientDetails['shop_service_charge_target'] ?? 0,"
)
content = content.replace(
    "'service_charge_per_emi'               => $clientDetails['service_charge_per_emi'] ?? 0,",
    "'shop_service_charge_pct'              => $clientDetails['shop_service_charge_pct'] ?? 0,"
)
content = content.replace(
    "'is_dev_charge_completed'              => $clientDetails['is_dev_charge_completed'] ?? false,",
    "'is_shop_service_charge_completed'     => $clientDetails['is_shop_service_charge_completed'] ?? false,"
)
content = content.replace(
    "'development_charge_paid'              => $clientDetails['development_charge_paid'] ?? 0,",
    "'shop_service_charge_paid'             => $clientDetails['shop_service_charge_paid'] ?? 0,"
)
content = content.replace(
    "'development_charge_paid_total'        => $clientDetails['development_charge_paid_total'] ?? 0,",
    "'shop_service_charge_paid_total'       => $clientDetails['shop_service_charge_paid_total'] ?? 0,"
)
content = content.replace(
    "'flat_development_charges'             => 0,",
    "'shop_service_charge_target'           => 0,"
)
content = content.replace(
    "'service_charge_per_emi'               => 0,",
    "'shop_service_charge_pct'              => 0,"
)
content = content.replace(
    "'is_dev_charge_completed'              => false,",
    "'is_shop_service_charge_completed'     => false,"
)
content = content.replace(
    "'development_charge_paid'              => 0,",
    "'shop_service_charge_paid'             => 0,"
)
content = content.replace(
    "'development_charge_paid_total'        => 0,",
    "'shop_service_charge_paid_total'       => 0,"
)

# 5. Update addTransaction Logic
# Booking money limit
content = content.replace(
    "$remainingBooking = $summary['agreement_value'] - $summary['booking_money_paid'];",
    "$remainingBooking = ($summary['total_price'] * 0.15) - $summary['booking_money_paid'];"
)

# Registration logic
reg_logic_start = """                case 'registration':
                    // Registration unlocks AND locks at the same time as Land Share
                    if (empty($summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before registration payment can be recorded.");
                    }
                    if (!empty($summary['is_land_share_completed'])) {
                        throw new Exception("Registration is already completed (land share completion marks registration as complete).");
                    }
                    break;"""

reg_logic_replacement = """                case 'registration':
                    if (empty($summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before registration payment can be recorded.");
                    }
                    $regTarget = $summary['total_price'] * 0.15;
                    $regPaid = $summary['registration_paid'] ?? 0;
                    $regRemaining = max(0, $regTarget - $regPaid);
                    if ($regRemaining <= 0) {
                        throw new Exception("Registration is already completed.");
                    }
                    if ($amount > $regRemaining) {
                        throw new Exception("Registration amount cannot exceed remaining registration target: ৳" . number_format($regRemaining, 2));
                    }
                    break;"""
content = content.replace(reg_logic_start, reg_logic_replacement)

# Replace development_charge with shop_service_charge in addTransaction
dev_logic_start = """                case 'development_charge':
                    // Dev charge requires land share to be fully completed (which also means registration is done)
                    if (empty($summary['is_land_share_completed'])) {
                        $remaining = number_format($summary['remaining_land_share'] ?? 0, 2);
                        throw new Exception("Land share (and registration) must be fully completed before development charge can be recorded. Remaining land share: ৳{$remaining}");
                    }
                    $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                    $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                    $devRemaining = max(0, $devTarget - $devPaid);
                    if ($devRemaining <= 0) {
                        throw new Exception("Development charge is already fully paid!");
                    }
                    if ($amount > $devRemaining) {
                        throw new Exception("Amount exceeds remaining development charge: ৳" . number_format($devRemaining, 2));
                    }
                    break;"""

dev_logic_replacement = """                case 'shop_service_charge':
                    // Shop service charge requires land share to be fully completed
                    if (empty($summary['is_land_share_completed'])) {
                        $remaining = number_format($summary['remaining_land_share'] ?? 0, 2);
                        throw new Exception("Land share (Booking 15% + Reg 15%) must be fully completed before Shop Service Charge can be recorded. Remaining land share: ৳{$remaining}");
                    }
                    $sscTarget = (float) ($summary['shop_service_charge_target'] ?? 0);
                    $sscPaid   = (float) ($summary['shop_service_charge_paid_total'] ?? 0);
                    $sscRemaining = max(0, $sscTarget - $sscPaid);
                    if ($sscRemaining <= 0) {
                        throw new Exception("Shop Service Charge is already fully paid!");
                    }
                    if ($amount > $sscRemaining) {
                        throw new Exception("Amount exceeds remaining Shop Service Charge: ৳" . number_format($sscRemaining, 2));
                    }
                    break;"""
content = content.replace(dev_logic_start, dev_logic_replacement)

emi_logic_start = """                case 'emi':
                    // EMI requires development charge to be fully paid (or no dev charge defined on flat)
                    if (empty($summary['is_dev_charge_completed'])) {
                        $devTarget = (float) ($summary['flat_development_charges'] ?? 0);
                        $devPaid   = (float) ($summary['development_charge_paid_total'] ?? 0);
                        throw new Exception("Development charge must be fully paid before EMI can be recorded. Paid: ৳" . number_format($devPaid, 2) . " / Required: ৳" . number_format($devTarget, 2));
                    }
                    // Service fee = amount_paid × (service_charge_per_emi% / 100)
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;"""

emi_logic_replacement = """                case 'emi':
                    // EMI requires Shop Service Charge to be fully paid
                    if (empty($summary['is_shop_service_charge_completed'])) {
                        $sscTarget = (float) ($summary['shop_service_charge_target'] ?? 0);
                        $sscPaid   = (float) ($summary['shop_service_charge_paid_total'] ?? 0);
                        throw new Exception("Shop Service Charge must be fully paid before EMI can be recorded. Paid: ৳" . number_format($sscPaid, 2) . " / Required: ৳" . number_format($sscTarget, 2));
                    }
                    // NO SERVICE FEE ON SHOP EMIs
                    $serviceFee = 0;"""
content = content.replace(emi_logic_start, emi_logic_replacement)

emi_settlement_start = """                    // Service fee applies on the settled amount at same rate
                    $serviceChargeRate = (float) ($summary['service_charge_per_emi'] ?? 0);
                    $serviceFee        = ($serviceChargeRate > 0) ? round($amount * ($serviceChargeRate / 100)) : 0;"""
emi_settlement_replacement = """                    // NO SERVICE FEE ON SHOP EMIs
                    $serviceFee        = 0;"""
content = content.replace(emi_settlement_start, emi_settlement_replacement)

# 6. View formatting for transaction types
content = content.replace("WHEN lp.payment_type = 'development_charge' THEN 'DEV CHARGE'", "WHEN lp.payment_type = 'shop_service_charge' THEN 'SHOP SVC CHARGE'")
content = content.replace("WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'", "WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'")

with open(path, 'w', encoding='utf-8') as f:
    f.write(content)
print("Rewrite complete")
