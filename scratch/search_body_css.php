<?php
$file = 'public/assets/css/main.css';
$path = __DIR__ . '/../' . $file;
$content = file_get_contents($path);
$lines = explode("\n", $content);

foreach ($lines as $index => $line) {
    if (preg_match('/\bbody\b/i', $line) && strpos($line, '{') !== false) {
        echo "Line " . ($index + 1) . ": " . trim($line) . "\n";
        for ($j = 1; $j <= 6; $j++) {
            if (isset($lines[$index + $j])) {
                echo "  " . trim($lines[$index + $j]) . "\n";
            }
        }
        echo "\n";
    }
}
