<?php
$config = require 'app/config/config.php';
require_once 'app/services/AutoPostingService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$autoPoster = new AutoPostingService($db);

echo "EBL match ID: " . $autoPoster->getBankOrCashAccountId('Bank', 'EBL Bank') . "\n";
echo "City match ID: " . $autoPoster->getBankOrCashAccountId('Bank', 'City Bank') . "\n";
echo "DBBL match ID: " . $autoPoster->getBankOrCashAccountId('Bank', 'Dutch Bangla Bank') . "\n";
