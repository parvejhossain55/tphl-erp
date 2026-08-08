<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

echo "=== LEGACY COA ENTRIES WITH JOURNAL DETAILS COUNT ===\n";
$res = $db->query("
    SELECT c.id, c.account_code, c.account_name, c.account_type, COUNT(jd.id) as txn_count
    FROM chart_of_accounts c
    LEFT JOIN journal_details jd ON c.id = jd.account_id
    GROUP BY c.id
    HAVING txn_count > 0
    ORDER BY c.account_code ASC
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        echo "ID: {$row['id']} | Code: {$row['account_code']} | Name: {$row['account_name']} | Type: {$row['account_type']} | Transactions: {$row['txn_count']}\n";
    }
}
