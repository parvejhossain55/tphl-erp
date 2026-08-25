<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// 1. Remove scrollY and scrollCollapse
$searchDT = "                scrollX: true,
                scrollY: '65vh',
                scrollCollapse: true,";
$replaceDT = "                scrollX: true,";
$content = str_replace($searchDT, $replaceDT, $content);

// 2. Inject CSS for .dataTables_scrollHead
$css = "
<style>
    .dataTables_scrollHead {
        position: sticky !important;
        top: 0;
        z-index: 1020;
        background-color: #f8f9fa; /* matches light bg commonly used in table headers */
        box-shadow: 0 2px 4px rgba(0,0,0,0.05); /* gives it a nice sticky shadow */
    }
    .dataTables_scrollHead table {
        margin-top: 0 !important;
        margin-bottom: 0 !important;
    }
</style>
";

// Insert CSS before the `<div class="table__wrapper table-responsive">` or at the top of the file
$searchWrapper = '<div class="table__wrapper table-responsive">';
$content = str_replace($searchWrapper, $css . "\n            " . $searchWrapper, $content);

file_put_contents($file, $content);
echo "Sticky CSS injected.";
