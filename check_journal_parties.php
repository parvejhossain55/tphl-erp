<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

// Check journal_details columns and data
$res = $db->query("SELECT id, journal_id, account_id, party_type, party_id, narration FROM journal_details ORDER BY id DESC LIMIT 10");
echo "Journal Details:\n";
while ($r = $res->fetch_assoc()) {
    print_r($r);
}

// Check vendors table
$vRes = $db->query("SELECT id, name, company_name FROM vendors ORDER BY id ASC");
echo "\nVendors:\n";
while ($v = $vRes->fetch_assoc()) {
    echo "- ID: {$v['id']} | Name: {$v['name']} | Company: {$v['company_name']}\n";
}

// Check clients/bookings table
$cRes = $db->query("SELECT DISTINCT client_name FROM client_payments WHERE client_name IS NOT NULL LIMIT 10");
echo "\nClients from client_payments:\n";
if ($cRes) {
    while ($c = $cRes->fetch_assoc()) {
        echo "- Name: {$c['client_name']}\n";
    }
}
