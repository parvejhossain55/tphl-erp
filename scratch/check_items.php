<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

foreach ([1, 2, 3] as $bid) {
    echo "=== Budget ID $bid Items ===\n";
    $res = $db->query("SELECT COUNT(*) as cnt FROM initial_estimate_items WHERE budget_id = $bid");
    $row = $res->fetch_assoc();
    echo "Items: " . $row['cnt'] . "\n";

    $res = $db->query("SELECT COUNT(*) as cnt FROM initial_estimate_labors WHERE budget_id = $bid");
    $row = $res->fetch_assoc();
    echo "Labors: " . $row['cnt'] . "\n";
}
