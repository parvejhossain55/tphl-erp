<?php
$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';
$db = (new Database($config))->conn;

$res = $db->query("SELECT id, wo_number, title, design_id, work_type_id FROM con_work_orders");
while ($wo = $res->fetch_assoc()) {
    echo "WO ID: {$wo['id']} | Number: {$wo['wo_number']} | Title: {$wo['title']} | Design ID: {$wo['design_id']} | Work Type ID: {$wo['work_type_id']}\n";
    $itemsRes = $db->query("SELECT id, phase_item_id, work_type_id, description FROM con_work_order_items WHERE work_order_id = {$wo['id']}");
    while ($item = $itemsRes->fetch_assoc()) {
        echo "  - Item ID: {$item['id']} | Phase Item ID: {$item['phase_item_id']} | Work Type ID: {$item['work_type_id']} | Desc: " . substr($item['description'], 0, 40) . "\n";
    }
}
