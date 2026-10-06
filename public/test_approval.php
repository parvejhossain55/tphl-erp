<?php
require '../app/config/config.php';
require '../app/helpers/helper.php';
session_start();
$_SESSION['user'] = ['id' => 170, 'role_id' => 30, 'role_name' => 'Management'];
require '../app/controllers/Phase2/FundRequisitionController.php';
require '../app/config/Database.php';
$config = require '../app/config/config.php';
$database = new Database($config);
$db = $database->conn;
$controller = new FundRequisitionController($db);
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['status' => 'Approved', 'approved_amount' => 100];
try {
    $controller->approve_management(22);
    echo "Success!";
} catch (\Exception $e) {
    echo 'Exception: ' . $e->getMessage();
} catch (\Error $e) {
    echo 'Error: ' . $e->getMessage();
}
