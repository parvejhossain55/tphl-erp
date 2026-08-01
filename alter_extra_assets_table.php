<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");

// Add missing columns if they don't exist
$checkCat = $db->query("SHOW COLUMNS FROM project_extra_assets LIKE 'item_category_id'");
if ($checkCat->num_rows === 0) {
    $db->query("ALTER TABLE project_extra_assets ADD COLUMN item_category_id INT(11) NULL AFTER product_id");
    echo "✓ Added column item_category_id to project_extra_assets\n";
}

$checkAssign = $db->query("SHOW COLUMNS FROM project_extra_assets LIKE 'assigned_to'");
if ($checkAssign->num_rows === 0) {
    $db->query("ALTER TABLE project_extra_assets ADD COLUMN assigned_to INT(11) NULL AFTER asset_location");
    echo "✓ Added column assigned_to to project_extra_assets\n";
}
