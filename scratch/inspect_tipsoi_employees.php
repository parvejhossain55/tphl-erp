<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';
require_once __DIR__ . '/../app/services/ExternalApiService.php';

// Instantiate mock or real DB connection to pass to service
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$api = new ExternalApiService($db);
$result = $api->getExternalEmployees(1, 20);

header('Content-Type: text/plain');
if ($result && $result['success']) {
    echo "Successfully fetched " . count($result['data']) . " employees from Tipsoi.\n\n";
    foreach (array_slice($result['data'], 0, 5) as $emp) {
        echo "Name: " . ($emp['name'] ?? $emp['full_name'] ?? 'N/A') . "\n";
        echo "Office ID: " . ($emp['employeeOfficeId'] ?? $emp['employee_id'] ?? 'N/A') . "\n";
        echo "Shift Group ID: " . ($emp['shiftGroupId'] ?? 'N/A') . "\n";
        echo "Shift Group Name: " . ($emp['shiftGroupName'] ?? 'N/A') . "\n";
        echo "Employment Type: " . ($emp['employmentTypeName'] ?? 'N/A') . "\n";
        echo "JSON: " . json_encode($emp, JSON_PRETTY_PRINT) . "\n";
        echo "--------------------------------------------------------\n\n";
    }
} else {
    echo "API fetch failed: " . ($result['message'] ?? 'Unknown error') . "\n";
}
