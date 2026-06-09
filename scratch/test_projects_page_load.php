<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// simple mockup of view loader and session
session_start();
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1 // Admin
];

$GLOBALS['base_url'] = '/tphl-erp';

class MockController {
    protected $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function view($name, $data) {
        // extract variables and include view file
        extract($data);
        $base_url = $GLOBALS['base_url'];
        $base = $GLOBALS['base_url'];
        
        // Include the view file to see if it parses
        ob_start();
        include "app/views/{$name}.php";
        $html = ob_get_clean();
        echo "View rendered successfully. Length: " . strlen($html) . " characters.\n";
    }
}

// Let's run a modified version of projectsList
require_once 'app/core/Controller.php';
require_once 'app/controllers/Phase2/Con_DashboardController.php';

$controller = new Phase2\Con_DashboardController($db);

// We will use Reflection to call projectsList but we'll mock the view rendering method
// In PHP, we can override methods or inspect projectsList returns.
// Since projectsList returns $this->view(...), let's call it and capture the output.
try {
    ob_start();
    $controller->projectsList();
    $output = ob_get_clean();
    echo "Controller action completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
