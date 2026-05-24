<?php
require_once 'app/core/Database.php';
$db = (new Database())->connect();
$res = $db->query("SELECT * FROM material_receiving");
$data = [];
while ($row = $res->fetch_assoc()) $data[] = $row;
file_put_contents('dump1.txt', print_r($data, true));

$res2 = $db->query("SELECT * FROM material_receiving_items");
$data2 = [];
while ($row = $res2->fetch_assoc()) $data2[] = $row;
file_put_contents('dump2.txt', print_r($data2, true));
