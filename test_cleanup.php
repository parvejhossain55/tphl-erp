<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT id, source_table, source_id, status FROM approval_requests WHERE source_table IN ('lead_bookings', 'leads', 'flats') AND source_id IN (585, 586, 603, 17606, 30664, 30663, 2704, 3074, 4420)");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
