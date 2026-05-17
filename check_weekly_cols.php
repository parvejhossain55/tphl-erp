<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die('Connection failed: ' . $db->connect_error); }
$r = $db->query('DESCRIBE weekly_progress_report');
while ($row = $r->fetch_assoc()) {
    echo $row['Field'] . ' => ' . $row['Type'] . PHP_EOL;
}
