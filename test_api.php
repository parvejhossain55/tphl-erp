<?php
require 'app/config/config.php';
require 'app/helpers/helper.php';
require 'app/core/Database.php';
require 'app/core/Controller.php';

// Mock Employee class and ExternalApiService so they don't crash
class Employee {
    public function __construct($db) {}
}
class ExternalApiService {
    public function __construct($db) {}
}

require 'app/controllers/HrmApiController.php';

$config = require 'app/config/config.php';
$database = new Database($config);
$db = $database->conn;

$controller = new HrmApiController($db);
$controller->getTipsoiEmailLogs();
