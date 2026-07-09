<?php
require_once dirname(__DIR__) . "/app/helpers/helper.php";
require_once __DIR__ . '/../vendor/autoload.php';

// Enable error reporting for debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$config = require $root . "/app/config/config.php";

echo "<h2>TPHL HRM API Verification Test</h2>";

// 1. Database Connection
try {
    $database = new Database($config);
    $db = $database->conn;
    echo "<p style='color:green'>✔ Database Connected Successfully.</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✘ Database Connection Failed: " . $e->getMessage() . "</p>";
    exit;
}

// 2. Tipsoi Authentication
echo "<h3>Testing Tipsoi API Auth...</h3>";
$apiService = new ExternalApiService($db);
$token = $apiService->getBearerToken();

if ($token) {
    echo "<p style='color:green'>✔ Tipsoi Authenticated Successfully!</p>";
    echo "<p><strong>Token:</strong> <code>" . htmlspecialchars(substr($token, 0, 15)) . "... (truncated)</code></p>";
} else {
    echo "<p style='color:red'>✘ Tipsoi Authentication Failed. Please verify your credentials in config.php under current server environment.</p>";
    echo "<p>Configured Base URL: <code>" . htmlspecialchars($config['tipsoi_base_url'] ?? 'none') . "</code></p>";
    echo "<p>Configured Email: <code>" . htmlspecialchars($config['tipsoi_email'] ?? 'none') . "</code></p>";
}

// 3. Tipsoi Employee Fetch
echo "<h3>Testing Tipsoi Employees List Fetch...</h3>";
$externalData = $apiService->getExternalEmployees(1, 5);

echo "<p><strong>Fetch Success Status:</strong> " . ($externalData['success'] ? "<span style='color:green'>True</span>" : "<span style='color:red'>False</span>") . "</p>";
echo "<p><strong>Data Source:</strong> " . ($externalData['from_mock'] ? "<span style='color:orange'>Mock Fallback</span>" : "<span style='color:green'>Live API Sync</span>") . "</p>";
if (!empty($externalData['error_message'])) {
    echo "<p><strong>Details/Errors:</strong> <code style='color:red'>" . htmlspecialchars($externalData['error_message']) . "</code></p>";
}
echo "<p><strong>Total Count:</strong> " . $externalData['total'] . "</p>";
echo "<pre>Data Sample: " . htmlspecialchars(json_encode($externalData['data'], JSON_PRETTY_PRINT)) . "</pre>";


