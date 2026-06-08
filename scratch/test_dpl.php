<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}
$res = $db->query("SELECT qc_status, pm_status FROM daily_progress_logs LIMIT 1");
if ($res) {
    echo "Columns exist!\n";
} else {
    echo "Error: " . $db->error . "\n";
}
