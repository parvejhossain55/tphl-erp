<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT * FROM lead_payments WHERE payment_status = 'partially_paid' LIMIT 5");
while($r = $res->fetch_assoc()) {
    print_r($r);
}
