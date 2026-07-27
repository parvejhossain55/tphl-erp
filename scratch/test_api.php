<?php
$_SESSION['user'] = [
    'id' => 1,
    'role_id' => 1,
    'email' => 'info@dpremiumhomes.com',
    'tipsoi_id' => 357759
];
$config = require __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/services/ExternalApiService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$apiService = new ExternalApiService($db);
$token = $apiService->getBearerToken();

echo "Bearer token: " . ($token ? "OK" : "Failed") . "\n";
