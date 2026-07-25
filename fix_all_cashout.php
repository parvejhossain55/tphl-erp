<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// Find and replace all ($category === 'all' ? " ... ") with (($category === 'all' || $category === 'cashout') ? " ... ") 
// where it relates to cashout sources.
// The sources are: payslips, con_financial_transactions, fund_requisitions, land_fund_requisitions, project_extra_assets
// But actually, it's easier to just replace all occurrences of `($category === 'all' ? "` that are followed by `SELECT` in a UNION ALL.
$content = preg_replace("/\\(\\\$category === 'all' \? \"/", "((\$category === 'all' || \$category === 'cashout') ? \"", $content);

// Now for the parameter bindings block which looks like:
// if ($category === 'all') {
//     if ($startDate && $endDate) {
//         $bindParams = array_merge($bindParams, [$startDate, $endDate]); ...
// Change them all to check for 'cashout' as well for the query portions.
// Except line 3169 which is already `if ($projectId && ($category === 'all' || $category === 'cashout')) {`

$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*if \(\\\$startDate)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);

// For `getStatementData`, add 'cashout' to the checks.
// if ($category === 'all' || $category === 'assets_repairing' || $category === 'fund_requisitions') {
// -> if ($category === 'all' || $category === 'cashout' || $category === 'assets_repairing' || $category === 'fund_requisitions') {
$content = str_replace(
    "if (\$category === 'all' || \$category === 'assets_repairing' || \$category === 'fund_requisitions')",
    "if (\$category === 'all' || \$category === 'cashout' || \$category === 'assets_repairing' || \$category === 'fund_requisitions')",
    $content
);

// payslips, conFinancial, adjCashoutData, landFundReqData, officeProcAssetsData:
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$payslipsQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$conFinancialQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$adjCashoutQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$landFundReqQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);
$content = preg_replace("/if \(\\\$category === 'all'\) \{(\s*\\\$officeProcAssetsQuery)/", "if (\$category === 'all' || \$category === 'cashout') {\$1", $content);


// There's a big else if chain in getAccountsData for bindings:
/*
        } else if ($category === 'all' && $projectId) {
            // All categories with project filter
            $params = [$date, $date, $projectId, $projectId, $date, $date, $projectId];
            $types = "ssiissi";
        } else if ($category === 'all') {
            // All categories: date, date, date, date
            $params = [$date, $date, $date, $date];
            $types = "ssss";
        } else if ($category === 'cashout' && $projectId) {
            // Cashout only with project filter: date, date, projectId, projectId
            $params = [$date, $date, $projectId, $projectId];
            $types = "ssii";
        } else if ($category === 'cashout') {
            // Cashout only: date, date
            $params = [$date, $date];
            $types = "ss";
        }
*/
// Since cashout now has the exact SAME queries as all (for cashout unions), the number of parameters must match the number of unions!
// If we change ($category === 'all' ? ") to include cashout, then cashout unions will be active. Thus, the bind parameters for cashout MUST be identical to the cashout bindings inside 'all'!
// Wait, 'all' binds parameters for BOTH cash in and cash out!
// The queries are:
// 1. Management Cashout (always active)
// 2. Cashout Payments (active for all, cashout)
// 3. Payslips (active for all) -> NOW active for cashout
// 4. con_financial (active for all) -> NOW active for cashout
// 5. fund_req (active for all) -> NOW active for cashout
// 6. land_fund (active for all) -> NOW active for cashout
// 7. office_procurement (active for all) -> NOW active for cashout
// So the parameters for `cashout` must exactly match the parameters for the cashout section of `all`.
// BUT 'all' doesn't have any cashin queries in getAccountsData (Total Cash Out)?
// Wait! getAccountsData has $query which is just for CashOut!
// Let's look at getAccountsData parameter bindings.
