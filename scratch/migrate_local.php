<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$db->query("ALTER TABLE phase_monthly_target_items ADD COLUMN IF NOT EXISTS planned_progress_pct decimal(5, 2) NOT NULL DEFAULT 0.00 AFTER actual_qty");
$db->query("ALTER TABLE phase_monthly_target_items ADD COLUMN IF NOT EXISTS actual_progress_pct decimal(5, 2) NOT NULL DEFAULT 0.00 AFTER planned_progress_pct");

echo "Columns added to local database.\n";
