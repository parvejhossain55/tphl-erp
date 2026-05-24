<?php

$file = __DIR__ . '/../schema/custom-migration/tphl_db.sql';
if (!file_exists($file)) {
    die("File not found\n");
}

echo "Reading schema file...\n";
$content = file_get_contents($file);

echo "Replacing collations...\n";
$replacements = [
    'utf8mb4_unicode_ci' => 'utf8mb4_general_ci',
    'utf8mb4_0900_ai_ci' => 'utf8mb4_general_ci',
    'CHARSET=latin1'     => 'CHARSET=utf8mb4',
    'CHARACTER SET latin1' => 'CHARACTER SET utf8mb4',
];

$replacedCount = 0;
foreach ($replacements as $search => $replace) {
    $count = 0;
    $content = str_ireplace($search, $replace, $content, $count);
    echo "Replaced '$search' with '$replace' ($count times)\n";
    $replacedCount += $count;
}

echo "Saving updated schema...\n";
file_put_contents($file, $content);

echo "Completed! Updated $replacedCount references in $file.\n";
