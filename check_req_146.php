<?php
require 'app/config/config.php';
require 'app/core/Database.php';
$database = new Database($config);
$db = $database->conn;
$res = $db->query("SELECT id, source_id, source_table, pending_stage_label, pending_for_text, approve_role, status, request_amount FROM approval_requests WHERE source_id=146");
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
