<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

// Replace the jQuery selector that targets both inner and outer footers
$search1 = "$('.dataTables_scrollFoot tfoot tr, #workspaceTable tfoot tr').each(function() {";
$replace1 = "$('#workspaceTable tfoot').hide();\r\n        $('.dataTables_scrollFoot tfoot tr, #workspaceTable tfoot tr').each(function() {";
$content = str_replace($search1, $replace1, $content);

file_put_contents($file, $content);
echo "Successfully appended jQuery .hide() for the inner scrollbody tfoot duplication.";
