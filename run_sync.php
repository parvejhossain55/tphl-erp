<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once 'app/config/config.php';
require_once 'app/core/Controller.php';
require_once 'app/controllers/HrmApiController.php';
require_once 'app/services/ExternalApiService.php';
require_once 'app/models/Employee.php';

function db() {
    global $config;
    return new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
}
$config = require 'app/config/config.php';
$db = db();
$controller = new HrmApiController($db);
$controller->getExternalEmployees();
