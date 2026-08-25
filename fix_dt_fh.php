<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// Add FixedHeader CDNs at the end of the file or before script
$cdn = "
<link rel=\"stylesheet\" href=\"https://cdn.datatables.net/fixedheader/3.3.2/css/fixedHeader.bootstrap5.min.css\">
<script src=\"https://cdn.datatables.net/fixedheader/3.3.2/js/dataTables.fixedHeader.min.js\"></script>
";

$content = str_replace('<script>', $cdn . "\n<script>", $content);

// Remove the inline CSS I added previously
$content = preg_replace('/<style>\s*\.dataTables_scrollHead.*?<\/style>/is', '', $content);

// Add fixedHeader to DataTable init
$searchDT = "                scrollX: true,";
$replaceDT = "                scrollX: true,
                fixedHeader: { header: true, headerOffset: 70 },"; // offset 70px for navbar
$content = str_replace($searchDT, $replaceDT, $content);

file_put_contents($file, $content);
echo "FixedHeader patched.";
