<?php

$file = __DIR__ . '/../schema/custom-migration/tphl_db.sql';
if (!file_exists($file)) {
    die("File not found\n");
}

$content = file_get_contents($file);

// Find unique COLLATE occurrences
preg_match_all('/COLLATE\s*=\s*([a-zA-Z0-9_]+)/i', $content, $matches1);
preg_match_all('/COLLATE\s+([a-zA-Z0-9_]+)/i', $content, $matches2);

$collations = array_unique(array_merge($matches1[1], $matches2[1]));

// Find unique CHARSET occurrences
preg_match_all('/CHARSET\s*=\s*([a-zA-Z0-9_]+)/i', $content, $matches3);
preg_match_all('/CHARACTER SET\s+([a-zA-Z0-9_]+)/i', $content, $matches4);

$charsets = array_unique(array_merge($matches3[1], $matches4[1]));

echo "Collations found:\n";
print_r($collations);

echo "\nCharacter Sets found:\n";
print_r($charsets);
