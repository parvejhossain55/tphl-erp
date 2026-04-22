<?php
require_once 'app/config/db.php';
$sql = "ALTER TABLE daily_progress_reports ADD COLUMN IF NOT EXISTS progress_image VARCHAR(500) NULL DEFAULT NULL AFTER manpower_count";
if ($db->query($sql)) {
    echo "OK: Column 'progress_image' added (or already exists).\n";
} else {
    echo "ERROR: " . $db->error . "\n";
}
