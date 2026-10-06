<?php
require 'app/config/config.php';
require 'app/helpers/helper.php';
session_start();
$_SESSION['user'] = ['id' => 170, 'role_id' => 30, 'role_name' => 'Management'];
require 'vendor/autoload.php';

spl_autoload_register(function ($class) {
    $classFile = str_replace('\\', '/', $class);
    $paths = [
        __DIR__ . "/app/core/{$classFile}.php",
        __DIR__ . "/app/controllers/{$classFile}.php",
        __DIR__ . "/app/controllers/Phase2/{$classFile}.php",
        __DIR__ . "/app/models/{$classFile}.php",
        __DIR__ . "/app/services/{$classFile}.php",
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require 'app/controllers/Phase2/Con_VendorPaymentRequestController.php';
$config = require 'app/config/config.php';
$database = new Database($config);
$db = $database->conn;

$controller = new Con_VendorPaymentRequestController($db);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['status' => 'Approved', 'approved_amount' => 100];
try {
    $controller->approve(524);
    echo "Success!";
} catch (\Exception $e) {
    echo 'Exception: ' . $e->getMessage();
} catch (\Error $e) {
    echo 'Error: ' . $e->getMessage();
}
