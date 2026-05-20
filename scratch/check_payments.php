<?php
$config = require dirname(__DIR__) . "/app/config/config.php";
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

echo "=== LAND BAYANA PAYMENTS ===\n";
$res1 = $db->query("SELECT * FROM land_bayana_payments");
while ($row = $res1->fetch_assoc()) {
    print_r($row);
}

echo "=== LAND FUND REQUISITIONS ===\n";
$res2 = $db->query("SELECT * FROM land_fund_requisitions");
while ($row = $res2->fetch_assoc()) {
    print_r($row);
}
