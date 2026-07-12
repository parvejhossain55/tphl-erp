<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/helpers/helper.php';
require_once __DIR__ . '/../app/services/ExternalApiService.php';

$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$api = new ExternalApiService($db);
$result = $api->getExternalEmployees(1, 2000);

$targetIds = ['1268', '1182', '1117', '1194', '1240', '1191', '1255', '1170'];

header('Content-Type: text/plain');
if ($result && $result['success']) {
    foreach ($result['data'] as $emp) {
        $officeId = strval($emp['employeeOfficeId'] ?? $emp['employee_id'] ?? '');
        if (in_array($officeId, $targetIds)) {
            echo "ID: $officeId\n";
            echo "Name: " . ($emp['name'] ?? $emp['full_name'] ?? 'N/A') . "\n";
            echo "Shift Group Name: " . ($emp['shiftGroupName'] ?? 'N/A') . "\n";
            echo "-----------------------------\n";
        }
    }
} else {
    echo "API fetch failed: " . ($result['message'] ?? 'Unknown error') . "\n";
}
