<?php
$config = require 'app/config/config.php';
// Database connection
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get history for all leads or a specific one
$query = "SELECT fu.*, l.full_name, p.name AS project_name, f.flat_name 
          FROM lead_follow_ups fu 
          LEFT JOIN leads l ON fu.lead_id = l.id 
          LEFT JOIN projects p ON fu.project_id = p.id 
          LEFT JOIN flats f ON fu.flat_id = f.id 
          ORDER BY fu.follow_up_date DESC, fu.id DESC LIMIT 100";

$result = $conn->query($query);
$histories = [];
while ($row = $result->fetch_assoc()) {
    $histories[$row['lead_id']][] = $row;
}

file_put_contents('history_dump.json', json_encode($histories, JSON_PRETTY_PRINT));
echo "Dumped history to history_dump.json";
