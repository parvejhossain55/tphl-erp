<?php
$file = 'c:\\xampp\\htdocs\\TPHL-ERP\\app\\views\\layouts\\sidebar.php';
$content = file_get_contents($file);

// Block 1: Sales Activity & Team Performance (starts at '<!-- Sales Activity Report -->' and ends just before '<!-- Incentives Module -->')
$saStart = strpos($content, '<!-- Sales Activity Report -->');
$saEnd = strpos($content, '<!-- Incentives Module -->');
$saBlock = substr($content, $saStart, $saEnd - $saStart);

// Remove Block 1 from original position
$content = substr($content, 0, $saStart) . substr($content, $saEnd);

// Block 2: Sales Team Performance (starts at '<?php if (hasPermission("Sales team Performance")): ?>' and ends at its 'endif;')
$stpStart = strpos($content, '<?php if (hasPermission("Sales team Performance")): ?>');
$stpEnd = strpos($content, '<?php endif; ?>', $stpStart) + strlen('<?php endif; ?>') + 1; // +1 to include trailing newline maybe
$stpBlock = substr($content, $stpStart, $stpEnd - $stpStart);

// Let's make stpBlock explicitly visible
$stpBlock = trim($stpBlock) . "\n";

// Remove Block 2 from original position
$content = substr($content, 0, $stpStart) . substr($content, $stpEnd);

// Find insertion point for Sales Activity + Team Perf + Sales Team Perf (after Overall Sales Dashboard endif)
$insertPoint1 = strpos($content, '<?php endif; ?>', strpos($content, '<!-- sales Team Head Dashboard -->')) + strlen('<?php endif; ?>') + 1;

// Prepare block to insert at insertionPoint1
$salesBlock = "\n                        <?php if ((hasPermission(\"sales & business\") || in_array(\$_SESSION['user']['role_id'] ?? 0, [1, 2, 3, 5, 9, 15, 33, 34, 35]))): ?>\n" . 
              $saBlock . 
              "                        <?php endif; ?>\n" . 
              "                        " . $stpBlock . "\n";

// Insert Block
$content = substr($content, 0, $insertPoint1) . $salesBlock . substr($content, $insertPoint1);

// Block 3: Incentives Module (starts at '<!-- Incentives Module -->' and ends before '<!-- Flats Manage -->')
$incStart = strpos($content, '<!-- Incentives Module -->');
$incEnd = strpos($content, '<!-- Flats Manage -->');
$incBlock = substr($content, $incStart, $incEnd - $incStart);

// Remove Block 3 from original position
$content = substr($content, 0, $incStart) . substr($content, $incEnd);

// Find insertion point for Incentives Module (after Project Wise Flat Stock)
$stockStart = strpos($content, '<!-- Inventroy -->'); // Typo in code is 'Inventroy'
// The stock block ends before '<?php if (!$isManagement): ?>' or '<!-- Executive Team -->'
$insertPoint2 = strpos($content, '<?php if (!$isManagement): ?>', $stockStart);

// Insert Block 3
$content = substr($content, 0, $insertPoint2) . $incBlock . substr($content, $insertPoint2);

file_put_contents($file, $content);
echo "All Sales blocks reorganized successfully.";
?>
