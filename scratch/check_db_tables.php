<?php
$root = dirname(__DIR__);
$config = require $root . '/app/config/config.php';
require $root . '/app/core/Database.php';
$db = (new Database($config))->conn;

echo "--- contractor_bill_requests ---\n";
$res = $db->query('DESCRIBE contractor_bill_requests');
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' (Null: ' . $row['Null'] . ")\n";
}
