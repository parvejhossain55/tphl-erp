<?php
$f1 = file_get_contents('c:/xampp/htdocs/TPHL-ERP/app/views/ced/clients-cash-in-history-view.php');
echo 'CED: div: ' . substr_count($f1, '<div') . ' | /div: ' . substr_count($f1, '</div') . "\n";

$f2 = file_get_contents('c:/xampp/htdocs/TPHL-ERP/app/views/accounts/cashIn/clients-cash-in-history.php');
echo 'Accounts: div: ' . substr_count($f2, '<div') . ' | /div: ' . substr_count($f2, '</div') . "\n";
