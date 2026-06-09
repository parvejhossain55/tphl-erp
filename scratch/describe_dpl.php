<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("DESCRIBE daily_progress_logs");
echo "--- daily_progress_logs ---\n";
print_r($res->fetch_all(MYSQLI_ASSOC));

$res2 = $db->query("DESCRIBE daily_work_item_logs");
echo "--- daily_work_item_logs ---\n";
print_r($res2->fetch_all(MYSQLI_ASSOC));

