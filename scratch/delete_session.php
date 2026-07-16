<?php
$config = require dirname(__DIR__) . "/app/config/config.php";
require_once dirname(__DIR__) . '/app/core/Database.php';

$database = new Database($config);
$db = $database->conn;

$db->query("DELETE FROM erp_tracker_sessions");
$db->query("DELETE FROM erp_tracker_submissions");
$db->query("DELETE FROM erp_tracker_actions");

echo "All test sessions deleted.";
