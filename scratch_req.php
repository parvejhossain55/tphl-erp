<?php
$config = require 'app/config/config.php';
require 'app/models/CashoutRequest.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$model = new CashoutRequest($db);
$req = $model->getRequestById(1);
echo "display_status = " . (isset($req['display_status']) ? $req['display_status'] : 'NOT SET') . "\n";
echo "approval_status = " . (isset($req['approval_status']) ? $req['approval_status'] : 'NOT SET') . "\n";
