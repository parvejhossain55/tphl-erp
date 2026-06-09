<?php
$files = [
    'app/views/layouts/header.php',
    'app/views/layouts/sidebar.php',
    'app/views/layouts/footer.php',
    'app/views/layouts/master.php',
    'app/views/phase2/land_acquisition/index.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (!file_exists($path)) {
        echo "File does not exist: $path\n";
        continue;
    }
    $content = file_get_contents($path);
    echo "--- Search in $file ---\n";
    // Search for script tags
    preg_match_all('/<script.*?>/is', $content, $matches, PREG_OFFSET_CAPTURE);
    foreach ($matches[0] as $match) {
        $offset = $match[1];
        $line = substr_count(substr($content, 0, $offset), "\n") + 1;
        echo "Line $line: " . trim($match[0]) . "\n";
    }
}
