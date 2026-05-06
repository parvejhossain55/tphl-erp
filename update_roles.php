<?php
/**
 * Role Update Script
 * Rule 1: In read-only guards — [1, 3, 20, 23] becomes [1, 3, 20, 22]
 * Rule 2: In admin/PM-head filter — [1, 3, 22, 23] becomes [1, 3, 15, 22]
 * Rule 3: In project manager access — [1, 2, 3, 13, 15, 23] becomes [1, 2, 3, 13, 15, 22]
 * Rule 4: Target Fixed approval: role 22 => role 15
 * Rule 5: Sidebar [1, 3, 23] => [1, 3, 22]
 */

$rootPath = __DIR__ . '/app';
$publicPath = __DIR__ . '/public/index.php';

$replacements = [
    // Read-only block guards: 23 → 22
    '[1, 3, 20, 23]'           => '[1, 3, 20, 22]',
    // Admin / PM-head all-project access: 23 → 22 (keep 22 as is)
    '[1, 3, 22, 23]'           => '[1, 3, 15, 22]',
    // PM/QC/admin project access: 23 → 22
    '[1, 2, 3, 13, 15, 23]'   => '[1, 2, 3, 13, 15, 22]',
    // Sidebar super-admin: 23 → 22
    '[1, 3, 23]'               => '[1, 3, 22]',
];

$targetFiles = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootPath)
);

$changedFiles = [];

foreach ($targetFiles as $file) {
    if ($file->getExtension() !== 'php') continue;
    $path = $file->getRealPath();
    $content = file_get_contents($path);
    $newContent = $content;

    foreach ($replacements as $from => $to) {
        $newContent = str_replace($from, $to, $newContent);
    }

    if ($newContent !== $content) {
        file_put_contents($path, $newContent);
        $changedFiles[] = str_replace(__DIR__ . '/', '', $path);
        echo "✓ Updated: " . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path) . "\n";
    }
}

// Also update public/index.php
$content = file_get_contents($publicPath);
$newContent = $content;
foreach ($replacements as $from => $to) {
    $newContent = str_replace($from, $to, $newContent);
}
if ($newContent !== $content) {
    file_put_contents($publicPath, $newContent);
    echo "✓ Updated: public/index.php\n";
}

echo "\nDone! " . count($changedFiles) . " files updated.\n";
