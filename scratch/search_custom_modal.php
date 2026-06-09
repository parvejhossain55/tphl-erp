<?php
// Search all files in public/assets recursively for "custom-modal"
function searchDir($dir, $pattern) {
    $results = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $content = file_get_contents($file->getPathname());
            if (strpos($content, $pattern) !== false) {
                $results[] = $file->getPathname();
            }
        }
    }
    return $results;
}

$results = searchDir(__DIR__ . '/../public/assets', 'custom-modal');
echo "Found " . count($results) . " files:\n";
foreach ($results as $res) {
    echo "- " . realpath($res) . "\n";
}
