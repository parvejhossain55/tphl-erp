<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// 1. Change getCategoryIdByName 'cashout' => 2 to 'cashout' => null
$content = str_replace("'cashout' => 2,", "'cashout' => null,", $content);

// 2. In getStatementData arrays, add $category === 'cashout'
$arrays_to_update = [
    '$payslipsQuery', '$conFinancialQuery', '$adjCashoutQuery', 
    '$fundReqQuery', '$landFundReqQuery', '$officeProcAssetsQuery'
];

// In getStatementData: 
// if ($category === 'all') {
//     $payslipsQuery = ...
// We need to replace `if ($category === 'all')` with `if ($category === 'all' || $category === 'cashout')` for these.
// And `if ($category === 'all' || $category === 'assets_repairing' || $category === 'fund_requisitions')` -> add cashout

$content = str_replace(
    "if (\$category === 'all' || \$category === 'assets_repairing' || \$category === 'fund_requisitions')",
    "if (\$category === 'all' || \$category === 'cashout' || \$category === 'assets_repairing' || \$category === 'fund_requisitions')",
    $content
);

$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$payslipsQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$conFinancialQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$adjCashoutQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$landFundReqQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$officeProcAssetsQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);

// 3. For the 4 big queries, replace " . ($category === 'all' ? " with " . (($category === 'all' || $category === 'cashout') ? "
// Note: Some might already have it if they were modified for cashout before, like cashout_payments.
// If it is `(($category === 'all' || $category === 'cashout') ? "` we leave it. If it's `($category === 'all' ? "` we change it.
$content = preg_replace("/\"\s*\.\s*\(\\\$category === 'all' \? \"/", "\" . ((\$category === 'all' || \$category === 'cashout') ? \"", $content);

// 4. Update the bind_params!
// We need to change `else if ($category === 'all' && $projectId)` to `else if (($category === 'all' || $category === 'cashout') && $projectId)`
$content = str_replace("else if (\$category === 'all' && \$projectId)", "else if ((\$category === 'all' || \$category === 'cashout') && \$projectId)", $content);
$content = str_replace("else if (\$category === 'all')", "else if (\$category === 'all' || \$category === 'cashout')", $content);

// Now REMOVE the old cashout bindings!
// e.g. 
// } else if ($category === 'cashout' && $projectId) {
//     $params = [...];
//     $types = "...";
// } else if ($category === 'cashout') {
//     $params = [...];
//     $types = "...";
// }
// We can just regex remove them.
$content = preg_replace("/\} else if \(\\\$category === 'cashout'.*?\} else/s", "} else", $content);

// 5. In getOpeningBalance
// $stmt->bind_param("sss", $startDate, $startDate, $startDate); -> needs to match the number of cashout sources in getOpeningCashOutQuery!
// Wait! getOpeningCashOutQuery currently has 3 sources: management_cashout, cashout_payments, con_financial_transactions (Wait, no, getOpeningCashOutQuery didn't use the 'all' conditional before!)
// Ah! getOpeningCashOutQuery doesn't use `($category === 'all' ? "`. It hardcodes all 3 sources!
// We must manually fix getOpeningCashOutQuery to include the rest of the sources, and update getOpeningBalance!

file_put_contents('temp_fix.php', $content);
