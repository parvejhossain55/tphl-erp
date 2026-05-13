<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'app/config/config.php';
require 'app/core/Database.php';
require 'app/models/FlatModel.php';
$config = require 'app/config/config.php';
$db = (new Database($config))->conn;
$model = new FlatModel($db);
$res = $model->getFlatsAjax(0, 10, '', '', '', 0, 'asc');
echo count($res);
