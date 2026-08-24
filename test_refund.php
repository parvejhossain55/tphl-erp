<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT * FROM client_refund_requests WHERE cashout_request_id IS NULL AND status = 'approved'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
