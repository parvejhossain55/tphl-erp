<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query('ALTER TABLE material_receiving_items ADD COLUMN accepted_qty DECIMAL(10,3) NOT NULL DEFAULT 0 AFTER received_quantity');
$db->query('ALTER TABLE material_receiving_items ADD COLUMN rejected_qty DECIMAL(10,3) NOT NULL DEFAULT 0 AFTER accepted_qty');
echo $db->error;
