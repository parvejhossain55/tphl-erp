<?php
$lines = file('app/controllers/AccountClientController.php');
foreach ($lines as $i => $l) {
    if (stripos($l, 'public function addCedTransaction') !== false) {
        echo ($i + 1) . ': ' . trim($l) . PHP_EOL;
    }
}
