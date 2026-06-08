<?php
// Bootstrap the application or mock the db connection
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Simple test to call the method directly by creating the class
require_once 'app/core/Controller.php';
require_once 'app/controllers/Phase2/Con_DashboardController.php';

// Mock the controller constructor session requirements
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1 // Admin
];

$controller = new Phase2\Con_DashboardController($db);

// We want to test calculatePhaseBasedProgress method (it is private, so we use reflection)
$ref = new ReflectionClass('Phase2\Con_DashboardController');
$method = $ref->getMethod('calculatePhaseBasedProgress');
$method->setAccessible(true);

$res = $method->invoke($controller, [13]);
echo "Dynamic progress for project 13:\n";
print_r($res);
