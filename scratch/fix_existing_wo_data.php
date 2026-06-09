<?php
$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';
$db = (new Database($config))->conn;

// Set header work_type_id
$db->query("UPDATE con_work_orders SET work_type_id = 10 WHERE id = 1");

// Set item 1 work_type_id to 10 (Earth Excavation & Backfilling)
$db->query("UPDATE con_work_order_items SET work_type_id = 10 WHERE id = 1");

// Set item 2 work_type_id to 11 (Soling & PCC Work)
$db->query("UPDATE con_work_order_items SET work_type_id = 11 WHERE id = 2");

echo "Existing WO 1 data fixed.\n";
