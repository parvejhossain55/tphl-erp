<?php
require_once __DIR__ . '/../app/core/Database.php';
$db = new Database();
$db = $db->connect();
$result = $db->query("DESCRIBE con_work_order_items");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
