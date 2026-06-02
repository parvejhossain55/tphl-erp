<?php
$dir = new RecursiveDirectoryIterator('C:\xampp\htdocs\easy-nirmanX\app\controllers\Phase2');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$count = 0;
foreach($files as $file) {
    $filePath = $file[0];
    $content = file_get_contents($filePath);
    if (strpos($content, '[1, 3, 23]') !== false) {
        $newContent = str_replace('[1, 3, 23]', '[1, 3, 20, 23]', $content);
        file_put_contents($filePath, $newContent);
        echo "Updated $filePath\n";
        $count++;
    }
}
echo "Total updated: $count\n";
