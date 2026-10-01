<?php
session_start();
define('APP_PATH', 'c:/xampp/htdocs/TPHL-ERP');
require_once APP_PATH . '/app/core/Database.php';
// Database connection
$config = require APP_PATH . '/app/config/config.php';
global $db;
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$GLOBALS['db'] = $db;
$GLOBALS['base_url'] = 'http://localhost:8080/TPHL-ERP';
$base_url = 'http://localhost:8080/TPHL-ERP';
$page_title = 'Test';
require_once APP_PATH . '/app/helpers/helper.php';
require_once APP_PATH . '/app/views/layouts/master.php';
// Look at media_1790835991254.png: "Hello Management", role is Management (Role 3 or 1)
$_SESSION['user'] = [
    'id' => 1,
    'full_name' => 'Management',
    'role_id' => 1,
    'role_name' => 'Admin'
];

$current_route = 'employees';
$base_url = '/TPHL-ERP';
$GLOBALS['base_url'] = '/TPHL-ERP';



ob_start();
require APP_PATH . '/app/views/layouts/header.php';
// header.php already requires sidebar.php!
require APP_PATH . '/app/views/layouts/footer.php';
$html = ob_get_clean();

file_put_contents(__DIR__ . '/rendered_sidebar.html', $html);
echo "Rendered HTML length: " . strlen($html) . "\n";
