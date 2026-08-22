<?php
require 'app/config/config.php';
$db = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($db->connect_error) die('Connect Error: ' . $db->connect_error);

$sql = "SELECT f.*, p.name as project_name, u.full_name 
        FROM lead_follow_ups f
        LEFT JOIN projects p ON f.project_id = p.id
        LEFT JOIN users u ON f.created_by = u.id
        WHERE f.deleted_at IS NULL
        ORDER BY f.id DESC LIMIT 1";
$result = $db->query($sql);
if (!$result) {
    echo 'Error: ' . $db->error;
} else {
    echo "Success! Rows: " . $result->num_rows;
    print_r($result->fetch_assoc());
}
?>
