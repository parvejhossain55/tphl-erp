<?php
$file = __DIR__ . '/schema/custom-migration/vps_missing_updates.sql';
if (file_exists($file)) {
    $content = file_get_contents($file);
    $content = str_ireplace('ADD COLUMN IF NOT EXISTS', 'ADD COLUMN', $content);
    file_put_contents($file, $content);
    echo "Fixed syntax in vps_missing_updates.sql\n";
} else {
    echo "File not found: $file\n";
}
