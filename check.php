<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT image, image2, image3 FROM projects WHERE id=5");
print_r($res->fetch_assoc());
