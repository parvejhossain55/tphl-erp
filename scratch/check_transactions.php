<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SELECT * FROM con_financial_transactions WHERE project_id = 13");
print_r($res->fetch_all(MYSQLI_ASSOC));

$db->close();
