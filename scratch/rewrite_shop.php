<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\models\AccountShopClient.php';
$content = file_get_contents($file);

// Replace case 'development_charge':
$devChargeOld = <<<EOD
                case 'development_charge':
                    // Dev charge requires land share to be fully completed (which also means registration is done)
                    if (empty(\$summary['is_land_share_completed'])) {
                        \$remaining = number_format(\$summary['remaining_land_share'] ?? 0, 2);
                        throw new Exception("Land share (and registration) must be fully completed before development charge can be recorded. Remaining land share: ৳{\$remaining}");
                    }
                    \$devTarget = (float) (\$summary['flat_development_charges'] ?? 0);
                    \$devPaid   = (float) (\$summary['development_charge_paid_total'] ?? 0);
                    \$devRemaining = max(0, \$devTarget - \$devPaid);
                    if (\$devRemaining <= 0) {
                        throw new Exception("Development charge is already fully paid!");
                    }
                    if (\$amount > \$devRemaining) {
                        throw new Exception("Amount exceeds remaining development charge: ৳" . number_format(\$devRemaining, 2));
                    }
                    break;
EOD;

$devChargeNew = <<<EOD
                case 'shop_service_charge':
                    if (empty(\$summary['is_land_share_completed'])) {
                        \$remaining = number_format(\$summary['remaining_land_share'] ?? 0, 2);
                        throw new Exception("Registration must be fully completed before shop service charge can be recorded. Remaining: ৳{\$remaining}");
                    }
                    \$devTarget = (float) (\$summary['shop_service_charge_target'] ?? 0);
                    \$devPaid   = (float) (\$summary['shop_service_charge_paid_total'] ?? 0);
                    \$devRemaining = max(0, \$devTarget - \$devPaid);
                    if (\$devRemaining <= 0) {
                        throw new Exception("Shop service charge is already fully paid!");
                    }
                    if (\$amount > \$devRemaining) {
                        throw new Exception("Amount exceeds remaining shop service charge: ৳" . number_format(\$devRemaining, 2));
                    }
                    break;
EOD;

$content = str_replace($devChargeOld, $devChargeNew, $content);

// Replace case 'emi': dependency
$emiOld = <<<EOD
                case 'emi':
                    // EMI requires development charge to be fully paid (or no dev charge defined on flat)
                    if (empty(\$summary['is_dev_charge_completed'])) {
                        \$devTarget = (float) (\$summary['flat_development_charges'] ?? 0);
                        \$devPaid   = (float) (\$summary['development_charge_paid_total'] ?? 0);
                        throw new Exception("Development charge must be fully paid before EMI can be recorded. Paid: ৳" . number_format(\$devPaid, 2) . " / Required: ৳" . number_format(\$devTarget, 2));
                    }
EOD;

$emiNew = <<<EOD
                case 'emi':
                    // EMI requires shop service charge to be fully paid
                    if (empty(\$summary['is_shop_service_charge_completed'])) {
                        \$devTarget = (float) (\$summary['shop_service_charge_target'] ?? 0);
                        \$devPaid   = (float) (\$summary['shop_service_charge_paid_total'] ?? 0);
                        throw new Exception("Shop service charge must be fully paid before EMI can be recorded. Paid: ৳" . number_format(\$devPaid, 2) . " / Required: ৳" . number_format(\$devTarget, 2));
                    }
EOD;

$content = str_replace($emiOld, $emiNew, $content);

// Remove case 'land_share':
$landShareOld = <<<EOD
                case 'land_share':
                    // Block land_share until the full agreed booking money has been received
                    if (empty(\$summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before land share payments can be recorded.");
                    }
                    if (\$summary['remaining_land_share'] <= 0) {
                        throw new Exception("Land share payment is already completed!");
                    }
                    if (\$amount > \$summary['remaining_land_share']) {
                        throw new Exception("Land share amount cannot exceed remaining land share: ৳" . number_format(\$summary['remaining_land_share'], 2));
                    }
                    \$landShareAmount = \$amount;
                    break;
EOD;

$content = str_replace($landShareOld, "", $content);

// Modify case 'registration':
$regOld = <<<EOD
                case 'registration':
                    // Registration unlocks AND locks at the same time as Land Share
                    if (empty(\$summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before registration payment can be recorded.");
                    }
                    if (!empty(\$summary['is_land_share_completed'])) {
                        throw new Exception("Registration is already completed (land share completion marks registration as complete).");
                    }
                    break;
EOD;

$regNew = <<<EOD
                case 'registration':
                    if (empty(\$summary['is_booking_fully_paid'])) {
                        throw new Exception("Booking money must be fully settled before registration payment can be recorded.");
                    }
                    if (!empty(\$summary['is_land_share_completed'])) {
                        throw new Exception("Registration is already completed.");
                    }
                    \$landShareAmount = \$amount;
                    break;
EOD;

$content = str_replace($regOld, $regNew, $content);

file_put_contents($file, $content);
echo "Done";
