<?php
$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$query = "SELECT id, account_code, account_name, account_type, parent_id FROM chart_of_accounts WHERE status = 'active' ORDER BY account_code ASC";
$result = $db->query($query);
$accounts = [];
while ($row = $result->fetch_assoc()) {
    $accounts[] = $row;
}
echo "accounts array length: " . count($accounts) . "\n";
echo "json output: " . substr(json_encode($accounts), 0, 500) . "...\n";
