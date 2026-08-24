<?php
require 'app/config/config.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$res = $db->query('SELECT id, bank_name, branch_name FROM banks ORDER BY bank_name;');
$rows = [];
while ($row = $res->fetch_assoc()) {
    $rows[] = $row;
}
print_r($rows);
