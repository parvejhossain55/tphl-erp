<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT * FROM daily_work_item_logs");
$all = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
echo "Total rows in daily_work_item_logs: " . count($all) . "\n";
print_r($all);
