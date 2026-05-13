<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = __DIR__ . '/../';
require_once $root . "app/core/Model.php";
require_once $root . "app/core/Database.php";
require_once $root . "app/models/FlatModel.php";

$config = require $root . "app/config/config.php";
$database = new Database($config);
$db = $database->conn;

$flatModel = new FlatModel($db);

try {
    $res = $flatModel->getFlatsAjax(0, 10, '', '', '', 0, 'desc');
    print_r($res);
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
