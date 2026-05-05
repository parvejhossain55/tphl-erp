<?php
$config = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query("UPDATE material_returns SET project_id = 13 WHERE id = 1");
echo "Updated project ID.";
