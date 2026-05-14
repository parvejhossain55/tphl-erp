<?php
$source = file_get_contents('c:\xampp\htdocs\TPHL-ERP\app\models\AccountClient.php');

// Extract addTransaction
$start = strpos($source, 'public function addTransaction(');
if ($start === false) die("addTransaction not found");

// Extract the whole function (simplistic brace counting)
$braces = 0;
$in_func = false;
$end = $start;
for ($i = $start; $i < strlen($source); $i++) {
    if ($source[$i] == '{') {
        $braces++;
        $in_func = true;
    }
    if ($source[$i] == '}') {
        $braces--;
        if ($in_func && $braces == 0) {
            $end = $i;
            break;
        }
    }
}

$addTransaction = substr($source, $start, $end - $start + 1);

// Modifications for addTransaction
$addTransaction = str_replace(
    "\$remainingBooking = \$summary['agreement_value'] - \$summary['booking_money_paid'];",
    "\$remainingBooking = (\$summary['total_price'] * 0.15) - \$summary['booking_money_paid'];",
    $addTransaction
);

// Replace registration validation
$regOld = "                case 'registration':\n                    // Registration unlocks AND locks at the same time as Land Share\n                    if (empty(\$summary['is_booking_fully_paid'])) {\n                        throw new Exception(\"Booking money must be fully settled before registration payment can be recorded.\");\n                    }\n                    if (!empty(\$summary['is_land_share_completed'])) {\n                        throw new Exception(\"Registration is already completed (land share completion marks registration as complete).\");\n                    }\n                    break;";
$regNew = "                case 'registration':\n                    if (empty(\$summary['is_booking_fully_paid'])) {\n                        throw new Exception(\"Booking money must be fully settled before registration payment can be recorded.\");\n                    }\n                    \$regTarget = \$summary['total_price'] * 0.15;\n                    \$regPaid = \$summary['registration_paid'] ?? 0;\n                    \$regRemaining = max(0, \$regTarget - \$regPaid);\n                    if (\$regRemaining <= 0) {\n                        throw new Exception(\"Registration is already completed.\");\n                    }\n                    if (\$amount > \$regRemaining) {\n                        throw new Exception(\"Registration amount cannot exceed remaining registration target: ৳\" . number_format(\$regRemaining, 2));\n                    }\n                    break;";
$addTransaction = str_replace($regOld, $regNew, $addTransaction);

// Replace dev charge with shop service charge
$devOld = "                case 'development_charge':\n                    // Dev charge requires land share to be fully completed (which also means registration is done)\n                    if (empty(\$summary['is_land_share_completed'])) {\n                        \$remaining = number_format(\$summary['remaining_land_share'] ?? 0, 2);\n                        throw new Exception(\"Land share (and registration) must be fully completed before development charge can be recorded. Remaining land share: ৳{\$remaining}\");\n                    }\n                    \$devTarget = (float) (\$summary['flat_development_charges'] ?? 0);\n                    \$devPaid   = (float) (\$summary['development_charge_paid_total'] ?? 0);\n                    \$devRemaining = max(0, \$devTarget - \$devPaid);\n                    if (\$devRemaining <= 0) {\n                        throw new Exception(\"Development charge is already fully paid!\");\n                    }\n                    if (\$amount > \$devRemaining) {\n                        throw new Exception(\"Amount exceeds remaining development charge: ৳\" . number_format(\$devRemaining, 2));\n                    }\n                    break;";
$devNew = "                case 'shop_service_charge':\n                    if (empty(\$summary['is_land_share_completed'])) {\n                        \$remaining = number_format(\$summary['remaining_land_share'] ?? 0, 2);\n                        throw new Exception(\"Land share (Booking 15% + Reg 15%) must be fully completed before Shop Service Charge can be recorded. Remaining land share: ৳{\$remaining}\");\n                    }\n                    \$sscTarget = (float) (\$summary['shop_service_charge_target'] ?? 0);\n                    \$sscPaid   = (float) (\$summary['shop_service_charge_paid_total'] ?? 0);\n                    \$sscRemaining = max(0, \$sscTarget - \$sscPaid);\n                    if (\$sscRemaining <= 0) {\n                        throw new Exception(\"Shop Service Charge is already fully paid!\");\n                    }\n                    if (\$amount > \$sscRemaining) {\n                        throw new Exception(\"Amount exceeds remaining Shop Service Charge: ৳\" . number_format(\$sscRemaining, 2));\n                    }\n                    break;";
$addTransaction = str_replace($devOld, $devNew, $addTransaction);

// Replace EMI validation
$emiOld = "                case 'emi':\n                    // EMI requires development charge to be fully paid (or no dev charge defined on flat)\n                    if (empty(\$summary['is_dev_charge_completed'])) {\n                        \$devTarget = (float) (\$summary['flat_development_charges'] ?? 0);\n                        \$devPaid   = (float) (\$summary['development_charge_paid_total'] ?? 0);\n                        throw new Exception(\"Development charge must be fully paid before EMI can be recorded. Paid: ৳\" . number_format(\$devPaid, 2) . \" / Required: ৳\" . number_format(\$devTarget, 2));\n                    }\n                    // Service fee = amount_paid × (service_charge_per_emi% / 100)\n                    \$serviceChargeRate = (float) (\$summary['service_charge_per_emi'] ?? 0);\n                    \$serviceFee        = (\$serviceChargeRate > 0) ? round(\$amount * (\$serviceChargeRate / 100)) : 0;";
$emiNew = "                case 'emi':\n                    if (empty(\$summary['is_shop_service_charge_completed'])) {\n                        \$sscTarget = (float) (\$summary['shop_service_charge_target'] ?? 0);\n                        \$sscPaid   = (float) (\$summary['shop_service_charge_paid_total'] ?? 0);\n                        throw new Exception(\"Shop Service Charge must be fully paid before EMI can be recorded. Paid: ৳\" . number_format(\$sscPaid, 2) . \" / Required: ৳\" . number_format(\$sscTarget, 2));\n                    }\n                    \$serviceFee = 0;";
$addTransaction = str_replace($emiOld, $emiNew, $addTransaction);

// Replace emi_due_settlement service fee
$emiSetOld = "                    // Service fee applies on the settled amount at same rate\n                    \$serviceChargeRate = (float) (\$summary['service_charge_per_emi'] ?? 0);\n                    \$serviceFee        = (\$serviceChargeRate > 0) ? round(\$amount * (\$serviceChargeRate / 100)) : 0;";
$emiSetNew = "                    \$serviceFee        = 0;";
$addTransaction = str_replace($emiSetOld, $emiSetNew, $addTransaction);

// Now, insert $addTransaction into AccountShopClient.php just before the closing brace
$target = file_get_contents('c:\xampp\htdocs\TPHL-ERP\app\models\AccountShopClient.php');

// remove the last closing brace
$target = rtrim($target);
if (substr($target, -1) == '}') {
    $target = substr($target, 0, -1);
}

$target .= "\n\n    " . $addTransaction . "\n}\n";

file_put_contents('c:\xampp\htdocs\TPHL-ERP\app\models\AccountShopClient.php', $target);
echo "Added addTransaction";
