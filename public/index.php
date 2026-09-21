<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);
// Disable all error reporting for production/live
// error_reporting(0);
// ini_set('display_errors', 0);
// Load helper functions and composer autoloader
require_once("../app/helpers/helper.php");
require_once __DIR__ . '/../vendor/autoload.php';

// Custom Autoloader Fallback for Controllers & Models
spl_autoload_register(function ($class) {
    $classFile = str_replace('\\', '/', $class);
    $paths = [
        __DIR__ . "/../app/core/{$classFile}.php",
        __DIR__ . "/../app/controllers/{$classFile}.php",
        __DIR__ . "/../app/models/{$classFile}.php",
        __DIR__ . "/../app/services/{$classFile}.php",
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Enable debug (local dev)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

$root = dirname(__DIR__);
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $root);
}
$GLOBALS['base_url'] = '/TPHL-ERP';

// Load config
$config = require $root . "/app/config/config.php";

// Create DB
$database = new Database($config);
$db = $database->conn;

// Routes (regex for ID)
$routes = array_merge(
    require $root . '/app/routes/web.php',
    require $root . '/app/routes/crm.php',
    require $root . '/app/routes/hrm.php',
    require $root . '/app/routes/construction.php',
    require $root . '/app/routes/accounts.php'
);

$request_uri = $_SERVER['REQUEST_URI'];
$route = trim(strtok($request_uri, '?'), '/');

$base_folder = 'TPHL-ERP';
if (strpos($route, $base_folder) === 0) {
    $route = substr($route, strlen($base_folder));
}
$route = trim($route, '/');

if ($route === 'log-client-debug') {
    header('Content-Type: application/json');
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if ($data) {
        $logFile = dirname(__DIR__) . '/logs/client_debug.log';
        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] URL: %s | Type: %s | Message: %s | Details: %s\n",
            $timestamp,
            $data['url'] ?? 'N/A',
            $data['type'] ?? 'info',
            $data['message'] ?? '',
            json_encode($data['details'] ?? [], JSON_UNESCAPED_SLASHES)
        );
        file_put_contents($logFile, $message, FILE_APPEND);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    }
    exit;
}

$public_routes = [
    '',
    'admin-login',
    'logout',
    'admin-registration',
    'api/client-payment-info',
    'api/project-expenses',
    'api/client-authenticate',
    'api/client-details',
    'run-restore-bookings-payments',
    'restore-bookings-data'
];

if (!in_array($route, $public_routes) && !str_starts_with($route, 'api/')) {
    checkLogin();
} else {
    // logged in user cannot see login page
    if (!empty($_SESSION['user']['id']) && ($route === '' || $route === 'admin-login')) {
        redirectByRole();
    }
}

$GLOBALS['route'] = $route;
$matched = false;
$params = [];

foreach ($routes as $pattern => $routeInfo) {
    if (preg_match("#^$pattern$#", $route, $matches)) {
        $controllerName = $routeInfo['controller'];
        $methodName = $routeInfo['method'];
        $params = array_slice($matches, 1); // ID captured here
        $matched = true;
        break;
    }
}

if (!$matched) {
    if (strpos($route, 'public/uploads/') === 0 || strpos($route, 'uploads/') === 0) {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html><head><title>File Not Found</title><link rel='stylesheet' href='{$GLOBALS['base_url']}/assets/css/vendor/bootstrap.min.css'></head><body class='bg-light d-flex align-items-center justify-content-center' style='min-height:80vh;'>";
        echo "<div class='text-center p-4 bg-white rounded shadow-sm border' style='max-width:520px;'>";
        echo "<h4 class='text-warning fw-bold mb-2'>Attached File Not Found</h4>";
        echo "<p class='text-muted mb-3'>The requested file was not found on the server or has been removed.</p>";
        echo "<div class='p-2 bg-light text-break rounded small text-secondary font-monospace mb-4'>" . htmlspecialchars(basename(urldecode($route))) . "</div>";
        echo "<a href='javascript:history.back()' class='btn btn-outline-primary btn-sm px-4'>&larr; Go Back</a>";
        echo "</div></body></html>";
        exit;
    }
    die("404 - Route not found: $route");
}

// Instantiate controller
$controller = new $controllerName($db);

// Call method with ID param if exists
call_user_func_array([$controller, $methodName], $params);
