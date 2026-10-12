<?php
$lines = file('app/views/ced/clients-cash-in-history-view.php');
foreach ($lines as $i => $l) {
    if (stripos($l, 'EMI Discount') !== false) {
        echo ($i + 1) . ': ' . trim($l) . PHP_EOL;
    }
}
