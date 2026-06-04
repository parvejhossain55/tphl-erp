<?php
$config = require 'config/config.php';
require 'app/core/Database.php';
$db = (new Database($config['db']))->conn;
$db->query('ALTER TABLE con_work_order_items ADD COLUMN phase_item_id INT NULL DEFAULT NULL AFTER work_order_id');
echo $db->error;
