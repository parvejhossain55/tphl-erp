<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$res = $db->query("SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE account_code LIKE '22%' OR account_code LIKE '41%' OR account_code LIKE '42%' OR account_name LIKE '%development%' ORDER BY account_code ASC");
echo "Relevant Revenue & Liability COA Accounts:\n";
while ($r = $res->fetch_assoc()) {
    echo "- ID: {$r['id']} | Code: {$r['account_code']} | Name: {$r['account_name']} | Type: {$r['account_type']}\n";
}
