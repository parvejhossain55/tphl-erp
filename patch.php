<?php
$file = 'c:\xampp\htdocs\TPHL-ERP\app\controllers\FlatController.php';
$content = file_get_contents($file);

$content = preg_replace(
    "/('suite_id'\s*=>\s*\\\$_POST\['suite_id'\]\s*\?\?\s*null,)/m",
    "$1\n            'ownership_type'         => \$_POST['ownership_type'] ?? null,",
    $content
);

$content = preg_replace(
    "/('facing_site'\s*=>\s*'facing_site',)/m",
    "$1\n                'ownership_type'     => 'ownership_type',",
    $content
);

$content = preg_replace(
    "/('suite_id'\s*=>\s*trim\(\\\$row\['suite_id'\]\s*\?\?\s*''\),)/m",
    "$1\n                    'ownership_type'         => trim(\$row['ownership_type'] ?? ''),",
    $content
);

file_put_contents($file, $content);
echo 'Regex replacement done';
