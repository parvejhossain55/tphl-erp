<?php
require 'app/config/config.php';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Mock the global db
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Mock input stream
$payload = json_encode([
    'project_id' => 1,
    'year' => '2026',
    'month' => '08',
    'rows' => [
        [
            'employee_id' => 5, // We'll assume a valid employee ID like 5
            'net_salary' => 6000,
            'total_earning' => 6000,
            'total_deduction' => 0,
            'details' => [
                '1' => 1000
            ]
        ]
    ]
]);

// We need a temp php://temp stream since we can't mock php://input easily without it
$stream = fopen('php://temp', 'w+');
fwrite($stream, $payload);
rewind($stream);

// Wait, file_get_contents('php://input') can't be easily mocked in a script unless we override it.
// Let's just create a test route or execute the logic.
file_put_contents('test_payload.json', $payload);
?>
