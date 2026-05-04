<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query("ALTER TABLE material_receiving_items MODIFY COLUMN status ENUM('Pending QC', 'Accepted', 'Rejected', 'Partially Accepted') DEFAULT 'Pending QC'");
echo $db->error;
