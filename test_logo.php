<?php
require 'app/config/config.php';
require 'app/core/Database.php';
$db = (new Database(require 'app/config/config.php'))->conn;
$res = $db->query('SELECT dark_logo, website_title FROM site_settings LIMIT 1')->fetch_assoc();
print_r($res);
