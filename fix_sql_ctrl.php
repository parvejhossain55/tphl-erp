<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\PayrollController.php';
$content = file_get_contents($file);

// Fix the SELECT query
$search1 = "WHERE p.id IN (\$placeholders) AND (p.status != 'paid' OR p.payment_status != 'paid')";
$replace1 = "WHERE p.id IN (\$placeholders) AND p.status != 'paid'";
$content = str_replace($search1, $replace1, $content);

// Fix the UPDATE query
$search2 = "SET status = 'paid', payment_status = 'paid', hr_status = 'approved', mgmt_status = 'approved',\r\n                      bank_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_voucher_id = ?";
$replace2 = "SET status = 'paid', paid_via_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_id = ?";
$content = str_replace($search2, $replace2, $content);

// Let's also check if there is an alternative formatting of the UPDATE query (since CRLF might differ)
$content = preg_replace(
    "/SET status = 'paid', payment_status = 'paid', hr_status = 'approved', mgmt_status = 'approved',\s*bank_account_id = \?, disbursed_by = \?, disbursed_at = NOW\(\), journal_voucher_id = \?/i",
    "SET status = 'paid', paid_via_account_id = ?, disbursed_by = ?, disbursed_at = NOW(), journal_id = ?",
    $content
);

file_put_contents($file, $content);
echo "Cleaned up invalid SQL columns from PayrollController.";
