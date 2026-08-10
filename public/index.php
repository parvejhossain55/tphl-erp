<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_OFF);

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
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
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
    'api/client-payment-info'
];

if (!in_array($route, $public_routes)) {
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
    die("404 - Route not found: $route");
}

// Instantiate controller
$controller = new $controllerName($db);

// Call method with ID param if exists
call_user_func_array([$controller, $methodName], $params);
