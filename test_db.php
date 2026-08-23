<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query('DESCRIBE tipsoi_email_logs');
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
