<?php
$file = 'c:\\xampp\\htdocs\\TPHL-ERP\\app\\views\\layouts\\sidebar.php';
$content = file_get_contents($file);

$incentivesStart = strpos($content, '<!-- Incentives Module -->');
$flatsStart = strpos($content, '<!-- Flats Manage -->');

$incentivesBlock = substr($content, $incentivesStart, $flatsStart - $incentivesStart);

// find the end of the stock part
$flatsStockEnd = strpos($content, '<?php if (!$isManagement): ?>', $flatsStart);
if ($flatsStockEnd === false) {
    $flatsStockEnd = strpos($content, '<!-- Executive Team  -->', $flatsStart);
}

if ($incentivesStart !== false && $flatsStart !== false && $flatsStockEnd !== false) {
    $flatsBlock = substr($content, $flatsStart, $flatsStockEnd - $flatsStart);
    $newContent = substr($content, 0, $incentivesStart) . $flatsBlock . $incentivesBlock . substr($content, $flatsStockEnd);
    file_put_contents($file, $newContent);
    echo "Swapped Incentives and Flats/Stock.";
} else {
    echo "Could not find boundaries.";
}
?>
