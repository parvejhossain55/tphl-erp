<?php
$config = require __DIR__ . '/../app/config/config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$tables = [
    'boq_master',
    'boq_items',
    'project_working_plan',
    'budget_extend_requests',
    'con_materials',
    'projects'
];

foreach ($tables as $t) {
    echo "\n--- $t ---\n";
    $res = $conn->query("DESCRIBE $t");
    while ($r = $res->fetch_assoc()) {
        printf("%-20s %s\n", $r['Field'], $r['Type']);
    }
}

$conn->close();
