<?php
require 'app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$q = "CREATE TABLE IF NOT EXISTS tipsoi_email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    approver_name VARCHAR(255),
    approver_email VARCHAR(255),
    pending_level VARCHAR(50),
    request_details TEXT,
    status VARCHAR(50),
    error_message TEXT,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($db->query($q) === TRUE) {
    echo "Table tipsoi_email_logs created successfully\n";
} else {
    echo "Error creating table: " . $db->error . "\n";
}
