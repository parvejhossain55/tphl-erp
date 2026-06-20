<?php
require_once __DIR__ . '/../app/helpers/helper.php';
require_once __DIR__ . '/../app/core/Database.php';

$config = require __DIR__ . '/../app/config/config.php';
$db = new Database($config);
$conn = $db->conn;

$tables = ['phase_monthly_targets', 'phase_monthly_target_items', 'phase_weekly_progress', 'phase_weekly_progress_items'];
foreach ($tables as $t) {
    echo "=== Table: $t ===\n";
    $res = $conn->query("DESCRIBE $t")->fetch_all(MYSQLI_ASSOC);
    foreach ($res as $row) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    echo "\n";
}
