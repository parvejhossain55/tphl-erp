<?php
$dir = __DIR__ . '/../public/assets/css';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'css') {
        $content = file_get_contents($file->getPathname());
        if (preg_match_all('/\.modal[^{]*\{[^}]*\}/i', $content, $matches)) {
            echo "--- " . $file->getFilename() . " ---\n";
            foreach ($matches[0] as $match) {
                echo trim($match) . "\n";
            }
        }
    }
}
