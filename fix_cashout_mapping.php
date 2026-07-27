<?php
$file = 'c:/xampp/htdocs/TPHL-ERP/app/models/CentralStatement.php';
$content = file_get_contents($file);

// 1. Change getCategoryIdByName 'cashout' => 2 to 'cashout' => null
$content = str_replace("'cashout' => 2,", "'cashout' => null,", $content);

// 2. In getStatementData arrays, add $category === 'cashout'
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

// 3. Replace " . ($category === 'all' ? " with " . (($category === 'all' || $category === 'cashout') ? "
$content = preg_replace("/\"\s*\.\s*\(\\\$category === 'all' \? \"/", "\" . ((\$category === 'all' || \$category === 'cashout') ? \"", $content);

// 4. Update parameter bindings logic
$content = str_replace("else if (\$category === 'all' && \$projectId)", "else if ((\$category === 'all' || \$category === 'cashout') && \$projectId)", $content);
$content = preg_replace("/\} else if \(\\\$category === 'all'\) \{(.*?)\s*\$params =/s", "} else if (\$category === 'all' || \$category === 'cashout') {\$1\n            \$params =", $content);

// 5. Remove the old cashout bindings since they are now merged into 'all'
// This matches:
// } else if ($category === 'cashout' && $projectId) { ... } else if ($category === 'cashout') { ... }
// We can use a regex that deletes from `} else if ($category === 'cashout'` up to the next `} else {`
$content = preg_replace("/\} else if \(\\\$category === 'cashout'.*?\} else \{/s", "} else {", $content);

file_put_contents('temp_fix.php', $content);
