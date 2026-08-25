<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\views\payrolls\payslips\workspace.php';
$content = file_get_contents($file);

$tds = "";
for ($i = 0; $i < 36; $i++) {
    if ($i === 6) {
        $tds .= '        <td class="text-end ps-3">Totals</td>' . "\r\n";
    } else {
        $tds .= '        <td></td>' . "\r\n";
    }
}

$newTfoot = "<tfoot>\r\n    <tr class=\"fw-bold\" style=\"background-color: #e6f2f1; color: #0f766e;\">\r\n$tds    </tr>\r\n</tfoot>";

$pattern = '/<tfoot>\s*<tr class="fw-bold" style="background-color: #e6f2f1; color: #0f766e;"><\/tr>\s*<\/tfoot>/s';
$content = preg_replace($pattern, $newTfoot, $content);

file_put_contents($file, $content);
echo "Injected 36 TD cells into footer row.";
