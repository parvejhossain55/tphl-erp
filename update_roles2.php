<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('app/controllers/Phase2'));
foreach ($files as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        $newContent = str_replace(['[1, 2, 3,', '[1, 2, 3]'], ['[1, 2, 3, 33,', '[1, 2, 3, 33]'], $content);
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
echo "Done 123.\n";
