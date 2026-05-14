<?php
$path = 'c:\xampp\htdocs\TPHL-ERP\app\models\AccountShopClient.php';
$content = file_get_contents($path);

// Normalize line endings to \n
$content = str_replace("\r\n", "\n", $content);

// 1. Rename class
$content = str_replace('class AccountClient extends Model', 'class AccountShopClient extends Model', $content);

// 2. Update getClientByBookingId SQL
$content = str_replace(
    'COALESCE(p.service_charge_per_emi, 0) as service_charge_per_emi,',
    'COALESCE(f.shop_service_charge, 0) as shop_service_charge,',
    $content
);
$content = str_replace(
    'COALESCE(f.development_charges, 0) as flat_development_charges,',
    '',
    $content
);

$content = str_replace(
    "                COALESCE(\n                    (SELECT SUM(lp.amount) \n                     FROM lead_payments lp \n                     WHERE lp.booking_id = lb.id \n                     AND lp.payment_status IN ('paid','partially_paid')\n                     AND lp.verification_status = 'verified'\n                     AND lp.payment_type = 'development_charge'), \n                    0\n                ) as development_charge_paid,",
    "                COALESCE(\n                    (SELECT SUM(lp.amount) \n                     FROM lead_payments lp \n                     WHERE lp.booking_id = lb.id \n                     AND lp.payment_status IN ('paid','partially_paid')\n                     AND lp.verification_status = 'verified'\n                     AND lp.payment_type = 'shop_service_charge'), \n                    0\n                ) as shop_service_charge_paid,",
    $content
);

$content = str_replace(
    "                COALESCE(\n                    (SELECT SUM(lp.amount) \n                     FROM lead_payments lp \n                     WHERE lp.booking_id = lb.id \n                     AND lp.payment_status IN ('paid','partially_paid')\n                     AND lp.verification_status != 'rejected'\n                     AND lp.payment_type = 'development_charge'), \n                    0\n                ) as development_charge_paid_total,",
    "                COALESCE(\n                    (SELECT SUM(lp.amount) \n                     FROM lead_payments lp \n                     WHERE lp.booking_id = lb.id \n                     AND lp.payment_status IN ('paid','partially_paid')\n                     AND lp.verification_status != 'rejected'\n                     AND lp.payment_type = 'shop_service_charge'), \n                    0\n                ) as shop_service_charge_paid_total,",
    $content
);

// 3. Update getClientByBookingId logic block
$logic_start = "            // Cast all amounts to float for calculations\n            \$totalPrice            = (float) \$clientDetails['total_price'];\n            \$totalLandShare        = (float) \$clientDetails['total_land_share'];   // flat.land_share_value (fixed target)\n            \$flatDevCharges        = (float) (\$clientDetails['flat_development_charges'] ?? 0); // flat.development_charges (fixed)\n            \$serviceChargePerEmi   = (float) (\$clientDetails['service_charge_per_emi'] ?? 0); // project %\n            \$bookingMoney          = (float) \$clientDetails['booking_money'];      // verified booking money\n            \$discountAmount        = (float) \$clientDetails['discount_amount'];";

$logic_replacement = "            // Cast all amounts to float for calculations\n            \$totalPrice            = (float) \$clientDetails['total_price'];\n            \$discountAmount        = (float) \$clientDetails['discount_amount'];\n            \n            // Percentage Rules for Shops\n            \$totalLandShare        = \$totalPrice * 0.30;\n            \$shopServiceChargePct  = (float) (\$clientDetails['shop_service_charge'] ?? 0);\n            \$shopServiceChargeTarget = (\$totalPrice * \$shopServiceChargePct) / 100;\n            \n            \$bookingMoney          = (float) \$clientDetails['booking_money'];      // verified booking money";
$content = str_replace($logic_start, $logic_replacement, $content);

$paid_start = "            // Paid amounts\n            \$totalPaid             = (float) \$clientDetails['total_paid'];\n            \$bookingMoneyPaid      = (float) \$clientDetails['booking_money_paid'];\n            \$additionalLandSharePaid = (float) \$clientDetails['additional_land_share_paid'];\n            \$registrationPaid      = (float) \$clientDetails['registration_paid'];\n            \$developmentChargePaid = (float) \$clientDetails['development_charge_paid'];\n            \$emiPaid               = (float) \$clientDetails['emi_paid'];\n            \$totalServiceFee       = (float) \$clientDetails['total_service_fee'];";

$paid_replacement = "            // Paid amounts\n            \$totalPaid             = (float) \$clientDetails['total_paid'];\n            \$bookingMoneyPaid      = (float) \$clientDetails['booking_money_paid'];\n            \$additionalLandSharePaid = (float) \$clientDetails['additional_land_share_paid'];\n            \$registrationPaid      = (float) \$clientDetails['registration_paid'];\n            \$shopServiceChargePaid = (float) \$clientDetails['shop_service_charge_paid'];\n            \$emiPaid               = (float) \$clientDetails['emi_paid'];\n            \$totalServiceFee       = (float) \$clientDetails['total_service_fee']; // Should be 0 for shops via EMIs";
$content = str_replace($paid_start, $paid_replacement, $content);

$calc_start = "            // Total Construction Fee = Total Price - (Land Share + Dev Charges)\n            // Discount only affects land share remaining, NOT the construction fee\n            \$constructionFee = max(0, \$totalPrice - (\$totalLandShare + \$flatDevCharges));\n\n            // Fixed Per-EMI Amount = Construction Fee / Total EMI count\n            \$emiStats = \$this->getEMIStatistics(\$bookingId);\n            \$totalEmiCount = \$emiStats['total_emi'];\n            \$perEmiAmount  = (\$totalEmiCount > 0) ? round(\$constructionFee / \$totalEmiCount) : 0;\n\n            // Dev charge: completed when paid amount >= flat fixed development_charges\n            \$isDevChargeCompleted = (\$flatDevCharges > 0 && \$developmentChargePaid >= \$flatDevCharges);\n            // If there's no dev charge defined on the flat, treat it as complete (not blocking EMI)\n            if (\$flatDevCharges <= 0) {\n                \$isDevChargeCompleted = true;\n            }";

$calc_replacement = "            // Total Construction Fee = Total Price - (Land Share + Shop Service Charge)\n            // Discount affects land share remaining.\n            \$constructionFee = max(0, \$totalPrice - (\$totalLandShare + \$shopServiceChargeTarget));\n\n            // Fixed Per-EMI Amount = Construction Fee / Total EMI count\n            \$emiStats = \$this->getEMIStatistics(\$bookingId);\n            \$totalEmiCount = \$emiStats['total_emi'];\n            \$perEmiAmount  = (\$totalEmiCount > 0) ? round(\$constructionFee / \$totalEmiCount) : 0;\n\n            // Shop Service Charge completion\n            \$isShopServiceChargeCompleted = (\$shopServiceChargeTarget > 0 && \$shopServiceChargePaid >= \$shopServiceChargeTarget);\n            if (\$shopServiceChargeTarget <= 0) {\n                \$isShopServiceChargeCompleted = true;\n            }";
$content = str_replace($calc_start, $calc_replacement, $content);

$content = str_replace("\$clientDetails['flat_development_charges']             = \$flatDevCharges;", "\$clientDetails['shop_service_charge_target']           = \$shopServiceChargeTarget;", $content);
$content = str_replace("\$clientDetails['development_charge_paid']              = \$developmentChargePaid;", "\$clientDetails['shop_service_charge_paid']             = \$shopServiceChargePaid;", $content);
$content = str_replace("\$clientDetails['is_dev_charge_completed']              = \$isDevChargeCompleted;", "\$clientDetails['is_shop_service_charge_completed']     = \$isShopServiceChargeCompleted;", $content);
$content = str_replace("\$clientDetails['service_charge_per_emi']               = \$serviceChargePerEmi;", "\$clientDetails['shop_service_charge_pct']              = \$shopServiceChargePct;", $content);

$content = str_replace("'flat_development_charges'             => \$clientDetails['flat_development_charges'] ?? 0,", "'shop_service_charge_target'           => \$clientDetails['shop_service_charge_target'] ?? 0,", $content);
$content = str_replace("'service_charge_per_emi'               => \$clientDetails['service_charge_per_emi'] ?? 0,", "'shop_service_charge_pct'              => \$clientDetails['shop_service_charge_pct'] ?? 0,", $content);
$content = str_replace("'is_dev_charge_completed'              => \$clientDetails['is_dev_charge_completed'] ?? false,", "'is_shop_service_charge_completed'     => \$clientDetails['is_shop_service_charge_completed'] ?? false,", $content);
$content = str_replace("'development_charge_paid'              => \$clientDetails['development_charge_paid'] ?? 0,", "'shop_service_charge_paid'             => \$clientDetails['shop_service_charge_paid'] ?? 0,", $content);
$content = str_replace("'development_charge_paid_total'        => \$clientDetails['development_charge_paid_total'] ?? 0,", "'shop_service_charge_paid_total'       => \$clientDetails['shop_service_charge_paid_total'] ?? 0,", $content);
$content = str_replace("'flat_development_charges'             => 0,", "'shop_service_charge_target'           => 0,", $content);
$content = str_replace("'service_charge_per_emi'               => 0,", "'shop_service_charge_pct'              => 0,", $content);
$content = str_replace("'is_dev_charge_completed'              => false,", "'is_shop_service_charge_completed'     => false,", $content);
$content = str_replace("'development_charge_paid'              => 0,", "'shop_service_charge_paid'             => 0,", $content);
$content = str_replace("'development_charge_paid_total'        => 0,", "'shop_service_charge_paid_total'       => 0,", $content);

$content = str_replace("\$remainingBooking = \$summary['agreement_value'] - \$summary['booking_money_paid'];", "\$remainingBooking = (\$summary['total_price'] * 0.15) - \$summary['booking_money_paid'];", $content);

$reg_logic_start = "                case 'registration':\n                    // Registration unlocks AND locks at the same time as Land Share\n                    if (empty(\$summary['is_booking_fully_paid'])) {\n                        throw new Exception(\"Booking money must be fully settled before registration payment can be recorded.\");\n                    }\n                    if (!empty(\$summary['is_land_share_completed'])) {\n                        throw new Exception(\"Registration is already completed (land share completion marks registration as complete).\");\n                    }\n                    break;";
$reg_logic_replacement = "                case 'registration':\n                    if (empty(\$summary['is_booking_fully_paid'])) {\n                        throw new Exception(\"Booking money must be fully settled before registration payment can be recorded.\");\n                    }\n                    \$regTarget = \$summary['total_price'] * 0.15;\n                    \$regPaid = \$summary['registration_paid'] ?? 0;\n                    \$regRemaining = max(0, \$regTarget - \$regPaid);\n                    if (\$regRemaining <= 0) {\n                        throw new Exception(\"Registration is already completed.\");\n                    }\n                    if (\$amount > \$regRemaining) {\n                        throw new Exception(\"Registration amount cannot exceed remaining registration target: ৳\" . number_format(\$regRemaining, 2));\n                    }\n                    break;";
$content = str_replace($reg_logic_start, $reg_logic_replacement, $content);

$dev_logic_start = "                case 'development_charge':\n                    // Dev charge requires land share to be fully completed (which also means registration is done)\n                    if (empty(\$summary['is_land_share_completed'])) {\n                        \$remaining = number_format(\$summary['remaining_land_share'] ?? 0, 2);\n                        throw new Exception(\"Land share (and registration) must be fully completed before development charge can be recorded. Remaining land share: ৳{\$remaining}\");\n                    }\n                    \$devTarget = (float) (\$summary['flat_development_charges'] ?? 0);\n                    \$devPaid   = (float) (\$summary['development_charge_paid_total'] ?? 0);\n                    \$devRemaining = max(0, \$devTarget - \$devPaid);\n                    if (\$devRemaining <= 0) {\n                        throw new Exception(\"Development charge is already fully paid!\");\n                    }\n                    if (\$amount > \$devRemaining) {\n                        throw new Exception(\"Amount exceeds remaining development charge: ৳\" . number_format(\$devRemaining, 2));\n                    }\n                    break;";
$dev_logic_replacement = "                case 'shop_service_charge':\n                    // Shop service charge requires land share to be fully completed\n                    if (empty(\$summary['is_land_share_completed'])) {\n                        \$remaining = number_format(\$summary['remaining_land_share'] ?? 0, 2);\n                        throw new Exception(\"Land share (Booking 15% + Reg 15%) must be fully completed before Shop Service Charge can be recorded. Remaining land share: ৳{\$remaining}\");\n                    }\n                    \$sscTarget = (float) (\$summary['shop_service_charge_target'] ?? 0);\n                    \$sscPaid   = (float) (\$summary['shop_service_charge_paid_total'] ?? 0);\n                    \$sscRemaining = max(0, \$sscTarget - \$sscPaid);\n                    if (\$sscRemaining <= 0) {\n                        throw new Exception(\"Shop Service Charge is already fully paid!\");\n                    }\n                    if (\$amount > \$sscRemaining) {\n                        throw new Exception(\"Amount exceeds remaining Shop Service Charge: ৳\" . number_format(\$sscRemaining, 2));\n                    }\n                    break;";
$content = str_replace($dev_logic_start, $dev_logic_replacement, $content);

$emi_logic_start = "                case 'emi':\n                    // EMI requires development charge to be fully paid (or no dev charge defined on flat)\n                    if (empty(\$summary['is_dev_charge_completed'])) {\n                        \$devTarget = (float) (\$summary['flat_development_charges'] ?? 0);\n                        \$devPaid   = (float) (\$summary['development_charge_paid_total'] ?? 0);\n                        throw new Exception(\"Development charge must be fully paid before EMI can be recorded. Paid: ৳\" . number_format(\$devPaid, 2) . \" / Required: ৳\" . number_format(\$devTarget, 2));\n                    }\n                    // Service fee = amount_paid × (service_charge_per_emi% / 100)\n                    \$serviceChargeRate = (float) (\$summary['service_charge_per_emi'] ?? 0);\n                    \$serviceFee        = (\$serviceChargeRate > 0) ? round(\$amount * (\$serviceChargeRate / 100)) : 0;";
$emi_logic_replacement = "                case 'emi':\n                    // EMI requires Shop Service Charge to be fully paid\n                    if (empty(\$summary['is_shop_service_charge_completed'])) {\n                        \$sscTarget = (float) (\$summary['shop_service_charge_target'] ?? 0);\n                        \$sscPaid   = (float) (\$summary['shop_service_charge_paid_total'] ?? 0);\n                        throw new Exception(\"Shop Service Charge must be fully paid before EMI can be recorded. Paid: ৳\" . number_format(\$sscPaid, 2) . \" / Required: ৳\" . number_format(\$sscTarget, 2));\n                    }\n                    // NO SERVICE FEE ON SHOP EMIs\n                    \$serviceFee = 0;";
$content = str_replace($emi_logic_start, $emi_logic_replacement, $content);

$emi_settlement_start = "                    // Service fee applies on the settled amount at same rate\n                    \$serviceChargeRate = (float) (\$summary['service_charge_per_emi'] ?? 0);\n                    \$serviceFee        = (\$serviceChargeRate > 0) ? round(\$amount * (\$serviceChargeRate / 100)) : 0;";
$emi_settlement_replacement = "                    // NO SERVICE FEE ON SHOP EMIs\n                    \$serviceFee        = 0;";
$content = str_replace($emi_settlement_start, $emi_settlement_replacement, $content);

$content = str_replace("WHEN lp.payment_type = 'development_charge' THEN 'DEV CHARGE'", "WHEN lp.payment_type = 'shop_service_charge' THEN 'SHOP SVC CHARGE'", $content);
$content = str_replace("WHEN lp.payment_type = 'development_charge' THEN 'Development Charge'", "WHEN lp.payment_type = 'shop_service_charge' THEN 'Shop Service Charge'", $content);

// Convert back to \r\n before writing
$content = str_replace("\n", "\r\n", $content);

file_put_contents($path, $content);
echo "Rewrite complete";
