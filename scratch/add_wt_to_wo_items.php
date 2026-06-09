<?php
$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';
$db = (new Database($config))->conn;

$sql = "ALTER TABLE con_work_order_items ADD COLUMN work_type_id INT NULL DEFAULT NULL AFTER phase_item_id";
if ($db->query($sql)) {
    echo "Successfully added work_type_id column to con_work_order_items.\n";
} else {
    echo "Failed or column already exists: " . $db->error . "\n";
}
