<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) die("Connection failed: " . $db->connect_error);

$res = $db->query("SELECT * FROM purchase_requests WHERE id = 3");
echo "Purchase Requests with id 3: " . ($res ? $res->num_rows : $db->error) . "\n";
