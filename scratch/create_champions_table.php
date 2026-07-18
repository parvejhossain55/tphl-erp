<?php
$config = require dirname(__DIR__) . "/app/config/config.php";
require_once dirname(__DIR__) . '/app/core/Database.php';

$database = new Database($config);
$db = $database->conn;

$sql = "
CREATE TABLE IF NOT EXISTS erp_tracker_champions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_champ (department_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($db->query($sql)) {
    echo "Table created successfully.";
} else {
    echo "Error: " . $db->error;
}
