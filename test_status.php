<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT DISTINCT verification_status FROM lead_payments");
while($r = $res->fetch_assoc()) {
    print_r($r);
}
