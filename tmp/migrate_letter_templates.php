<?php
require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';

$config = require __DIR__ . '/../app/config/config.php';
$database = new Database($config);
$db = $database->conn;

// Check if designation_id exists in letter_templates
$result = $db->query("SHOW COLUMNS FROM letter_templates LIKE 'designation_id'");
if ($result->num_rows == 0) {
    if ($db->query("ALTER TABLE letter_templates ADD COLUMN designation_id INT NULL AFTER type")) {
        echo "Successfully added designation_id to letter_templates table.\n";
    } else {
        echo "Error adding column: " . $db->error . "\n";
    }
} else {
    echo "designation_id column already exists.\n";
}
?>
