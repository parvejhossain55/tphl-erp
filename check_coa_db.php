<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$res = $db->query("SELECT id, account_code, account_name, parent_id FROM chart_of_accounts WHERE account_code LIKE '10202%'");
while($row = $res->fetch_assoc()){
    print_r($row);
}
