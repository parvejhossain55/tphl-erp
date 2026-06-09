<?php
$file = 'public/assets/css/main.css';
$path = __DIR__ . '/../' . $file;
$content = file_get_contents($path);
$lines = explode("\n", $content);

for ($i = 5100; $i < 5180; $i++) {
    if (isset($lines[$i])) {
        echo ($i + 1) . ": " . $lines[$i] . "\n";
    }
}
