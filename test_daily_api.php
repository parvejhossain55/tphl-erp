<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Set fake user session to pass permission checks
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1,
    'role_name' => 'HRM'
];

require_once 'app/helpers/helper.php';
require_once 'vendor/autoload.php';

$config = require 'app/config/config.php';
$database = new Database($config);
$db = $database->conn;

// Instantiate controller
require_once 'app/controllers/EmployeeDashboardController.php';
$controller = new EmployeeDashboardController($db);

$_GET['date'] = '2026-07-12';
ob_start();
$controller->getTipsoiDailyData();
$output = ob_get_clean();

echo "OUTPUT:\n";
echo $output;
