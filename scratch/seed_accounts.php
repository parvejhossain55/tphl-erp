<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$accounts = [
    ['1000', 'Cash', 'Asset'],
    ['1010', 'Bank', 'Asset'],
    ['1200', 'Accounts Receivable', 'Asset'],
    ['2000', 'Accounts Payable', 'Liability'],
    ['3000', 'Owner Equity', 'Equity'],
    ['4000', 'Sales Revenue', 'Revenue'],
    ['5000', 'Office Expense', 'Expense'],
    ['5100', 'Construction Material Expense', 'Expense'],
    ['5200', 'Labor Expense', 'Expense']
];

$stmt = $db->prepare("INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_type) VALUES (?, ?, ?)");
foreach ($accounts as $acc) {
    $stmt->bind_param("sss", $acc[0], $acc[1], $acc[2]);
    $stmt->execute();
}
echo "Accounts seeded successfully.";
$stmt->close();
$db->close();
