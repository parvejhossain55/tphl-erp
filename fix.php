<?php
$f = 'c:/xampp/htdocs/TPHL-ERP/app/views/payrolls/payslips/workspace.php';
$c = file_get_contents($f);
$r = 'top: ' . '$' . '{navHeight}px !important;';
$c = str_replace('top: px !important;', $r, $c);
file_put_contents($f, $c);
?>
