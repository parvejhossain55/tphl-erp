<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$db->query("DELETE FROM chart_of_accounts WHERE account_code IN ('10202', '102023')");
echo "Cleaned up";
