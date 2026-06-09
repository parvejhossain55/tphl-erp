<?php
$file = 'public/assets/js/main.js';
$path = __DIR__ . '/../' . $file;
$content = file_get_contents($path);
$lines = explode("\n", $content);

foreach ($lines as $index => $line) {
    if (strpos($line, 'appendTo') !== false) {
        echo "Line " . ($index + 1) . ": " . trim($line) . "\n";
    }
}
