<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }

$rows = $db->query("SELECT id, project_id, contractor_id, title, status, approved_by FROM con_work_orders")->fetch_all(MYSQLI_ASSOC);
echo "=== All Work Orders ===" . PHP_EOL;
foreach ($rows as $r) {
    echo "ID={$r['id']}, project_id={$r['project_id']}, contractor_id={$r['contractor_id']}, title={$r['title']}, status=" . ($r['status'] ?? 'NULL') . ", approved_by=" . ($r['approved_by'] ?? 'NULL') . PHP_EOL;
}
