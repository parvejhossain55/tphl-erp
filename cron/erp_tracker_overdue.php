<?php
// cron/erp_tracker_overdue.php
// This script should be run daily at midnight via a cron job

$root = dirname(__DIR__);
$config = require $root . "/app/config/config.php";
require_once $root . '/app/core/Database.php';

$database = new Database($config);
$db = $database->conn;

echo "[".date('Y-m-d H:i:s')."] Running ERP Tracker Overdue check...\n";

// Find all 'Pending' tasks where the deadline is in the past (deadline < current date)
$sql = "UPDATE erp_tracker_actions 
        SET status = 'Overdue' 
        WHERE status = 'Pending' AND deadline < CURRENT_DATE()";

if ($db->query($sql)) {
    $affected = $db->affected_rows;
    echo "[".date('Y-m-d H:i:s')."] Successfully updated $affected tasks to Overdue.\n";
} else {
    echo "[".date('Y-m-d H:i:s')."] Error updating tasks: " . $db->error . "\n";
}
