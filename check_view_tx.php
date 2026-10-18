<?php
$lines = file(__DIR__ . '/app/views/ced/clients-cash-in-history-view.php');
foreach ($lines as $i => $line) {
    if (preg_match('/\$transactions\s*(=|as|\+=|\[)/', $line)) {
        echo "Line " . ($i + 1) . ": " . trim($line) . "\n";
    }
}
