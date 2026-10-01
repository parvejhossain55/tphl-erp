<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =========================================================================
// Maintenance Mode (লাইভে অন করতে true করুন, অফ করতে false করুন)
// =========================================================================
$maintenance_mode = false; 

// আপনি নিজে লাইভে কাজ বা টেস্ট করার জন্য ?bypass=tphl দিলে মেইনটেন্যান্স বাইপাস হবে
if (isset($_GET['bypass']) && $_GET['bypass'] === 'tphl') {
    $_SESSION['maintenance_bypass'] = true;
}

if ($maintenance_mode && empty($_SESSION['maintenance_bypass'])) {
    http_response_code(503);
    $maintenancePage = __DIR__ . '/maintenance.html';
    if (file_exists($maintenancePage)) {
        readfile($maintenancePage);
    } else {
        echo "<h1 style='color:#2d4a54;text-align:center;font-family:sans-serif;margin-top:15%;'>We're Down for Maintenance</h1><p style='color:#788395;text-align:center;'>We'll be back soon!</p>";
    }
    exit;
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

// ── Migration: add account_id column to all expense tables ──────────────────
if ($route === 'run-migration-account-id') {
    $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
    if (!in_array($userRoleId, [1, 3])) { die('Unauthorized.'); }
    $tables = [
        'fund_requisitions', 'con_labor_requisitions', 'vendor_payment_requests',
        'contractor_bill_requests', 'operational_fund_requests', 'project_other_costs',
        'project_extra_assets', 'payslips', 'advance_salaries', 'cashout_requests',
        'client_refund_requests', 'project_incentive_claims', 'land_fund_requisitions'
    ];
    header('Content-Type: text/plain; charset=utf-8');
    foreach ($tables as $t) {
        $db->query("ALTER TABLE `$t` ADD COLUMN account_id INT NULL");
        if ($db->error && strpos($db->error, 'Duplicate') === false) {
            echo "Error on $t: " . $db->error . "\n";
        } else {
            echo "OK: $t - account_id column ready.\n";
        }
    }
    echo "\nMigration complete!";
    exit;
}

// ── Resync journals with new COA mappings ────────────────────────────────────
if ($route === 'run-resync-journals') {
    $userRoleId = (int)($_SESSION['user']['role_id'] ?? 0);
    if (!in_array($userRoleId, [1, 3])) { die('Unauthorized.'); }
    header('Content-Type: text/plain; charset=utf-8');
    require_once $root . '/app/services/AutoPostingService.php';
    $svc = new AutoPostingService($db);
    $db->query("DELETE jd FROM journal_details jd INNER JOIN journals j ON jd.journal_id = j.id WHERE j.is_auto_posted = 1");
    $db->query("DELETE FROM journals WHERE is_auto_posted = 1");
    echo "Deleted auto-synced journals. Resyncing...\n";
    $svc->syncUnpostedExpenses(9999);
    echo "Resync complete!";
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
        // Fallback: Check if file physically exists and serve it via PHP
        // This helps when Live Server (Nginx or Apache) DocumentRoot is misconfigured
        $cleanPath = str_replace('public/uploads/', 'uploads/', $route);
        $absolutePath = __DIR__ . '/' . $cleanPath;
        
        // URL decode the path in case the file on disk has spaces
        $absolutePath = urldecode($absolutePath);

        if (file_exists($absolutePath) && is_file($absolutePath)) {
            $mime = 'application/octet-stream';
            if (function_exists('mime_content_type')) {
                $mime = mime_content_type($absolutePath);
            } elseif (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $absolutePath);
                finfo_close($finfo);
            } else {
                // simple extension fallback
                $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
                $mimes = ['pdf'=>'application/pdf','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif'];
                if (isset($mimes[$ext])) $mime = $mimes[$ext];
            }
            if (!$mime) $mime = 'application/octet-stream';
            
            header("Content-Type: $mime");
            header("Content-Length: " . filesize($absolutePath));
            readfile($absolutePath);
            exit;
        }

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
