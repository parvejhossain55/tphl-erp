<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\accounts\cashIn\shop-client-cash-in-form.php';
$content = file_get_contents($file);

// Form action
$content = str_replace('/add-transaction', '/add-shop-transaction', $content);

// Variables
$content = str_replace("\$isDevChargeCompleted    = \$paymentSummary['is_dev_charge_completed']    ?? false;", "\$isShopServiceChargeCompleted = \$paymentSummary['is_shop_service_charge_completed'] ?? false;", $content);
$content = str_replace("\$flatDevCharges       = \$paymentSummary['flat_development_charges'] ?? 0;", "\$shopServiceChargeTarget = \$paymentSummary['shop_service_charge_target'] ?? 0;", $content);
$content = str_replace("\$devChargePaid        = \$paymentSummary['development_charge_paid'] ?? 0;", "\$shopServiceChargePaid   = \$paymentSummary['shop_service_charge_paid_total'] ?? 0;", $content);

// Alerts logic
$content = str_replace("\$isDevChargeCompleted", "\$isShopServiceChargeCompleted", $content);
$content = str_replace("\$flatDevCharges", "\$shopServiceChargeTarget", $content);
$content = str_replace("\$devChargePaid", "\$shopServiceChargePaid", $content);

$content = str_replace("Development Charge is next", "Shop Service Charge is next", $content);
$content = str_replace("Development Charges", "Shop Service Charge", $content);
$content = str_replace("Development charge payment", "Shop service charge payment", $content);
$content = str_replace("Dev Charge paid", "Shop Svc Charge paid", $content);
$content = str_replace("dev charge first", "shop service charge first", $content);

$content = str_replace('value="development_charge"', 'value="shop_service_charge"', $content);
$content = str_replace("case 'development_charge':", "case 'shop_service_charge':", $content);

// JS constants
$content = str_replace("const isDevChargeCompleted   = <?= json_encode(\$isDevChargeCompleted) ?>;", "const isShopServiceChargeCompleted = <?= json_encode(\$isShopServiceChargeCompleted) ?>;", $content);

// The AccountClient instantiation at the top should be AccountShopClient
$content = str_replace("\$accountModel     = new AccountClient();", "\$accountModel     = new AccountShopClient();", $content);

file_put_contents($file, $content);
echo "View rewrites complete";
