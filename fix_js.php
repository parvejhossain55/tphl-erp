<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// 3. Edit exportOptions for CSV
$content = preg_replace(
    '/(exportOptions:\s*\{\s*format:\s*\{\s*body:\s*function\s*\([^\)]*\)\s*\{)/',
    "exportOptions: {\r\n                            rows: function ( idx, data, node ) {\r\n                                return $(node).find('.emp-checkbox').is(':checked');\r\n                            },\r\n                            columns: ':not(:first-child)',\r\n                            format: {\r\n                                body: function (inner, rowidx, colidx, node) {",
    $content
);

// 4. Edit exportDynamicPDF - Header
$content = preg_replace(
    "/(\\\$(dt\.table\(\)\.header\(\))\\.find\('th'\)\\.each\(function\s*\(\)\s*\{)/",
    "$(dt.table().header()).find('th').each(function (index) {\r\n                    if (index === 0) return;",
    $content
);

// 5. Edit exportDynamicPDF - Body
$content = preg_replace(
    "/(var\s+rowArr\s*=\s*\[\];\s*tr\.find\('td'\)\.each\(function\s*\(\)\s*\{)/",
    "if (!tr.find('.emp-checkbox').is(':checked')) return;\r\n                    var rowArr = [];\r\n                    tr.find('td').each(function (index) {\r\n                        if (index === 0) return;",
    $content
);

// 6. Edit Save function
$content = preg_replace(
    "/(var\s+payslipIdStr\s*=\s*tr\.attr\('data-payslip-id'\);)/",
    "if (!tr.find('.emp-checkbox').is(':checked')) return;\r\n                    var payslipIdStr = tr.attr('data-payslip-id');",
    $content
);

file_put_contents($file, $content);
echo "JS logic injected.";
