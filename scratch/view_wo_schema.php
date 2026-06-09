<?php
$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';
$db = (new Database($config))->conn;

echo "--- con_work_orders ---\n";
$res = $db->query('DESCRIBE con_work_orders');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' (Null: ' . $row['Null'] . ")\n";
}

echo "\n--- con_work_order_items ---\n";
$res = $db->query('DESCRIBE con_work_order_items');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' (Null: ' . $row['Null'] . ")\n";
}
