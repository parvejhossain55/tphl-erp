<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
require __DIR__ . '/../app/models/ChartOfAccount.php';
$coa = new ChartOfAccount($db);

$data = [
    'parent_id' => '1', // Test string ID (Cash)
    'account_code' => '999999',
    'account_name' => 'Test Subcategory',
    'account_type' => 'Asset',
    'status' => 'active'
];
if ($coa->createAccount($data)) {
    echo "Inserted sub category!\n";
} else {
    echo "Failed: " . $db->error . "\n";
}

$res = $db->query("SELECT * FROM chart_of_accounts WHERE account_code = '999999'");
print_r($res->fetch_assoc());

// Cleanup
$db->query("DELETE FROM chart_of_accounts WHERE account_code = '999999'");
