<?php
$lines = file('app/models/AccountClient.php');
foreach ($lines as $i => $l) {
    if (stripos($l, 'public function getClientByBookingId') !== false) {
        echo ($i + 1) . ': ' . trim($l) . PHP_EOL;
    }
}
