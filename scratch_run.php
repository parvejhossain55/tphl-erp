<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$queries = [
    "CREATE TABLE IF NOT EXISTS `material_returns` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `receiving_id` INT NOT NULL,
        `vendor_id` INT NOT NULL,
        `project_id` INT NOT NULL,
        `return_date` DATE NOT NULL,
        `return_reason` TEXT NULL,
        `attachment` VARCHAR(255) NULL,
        `status` ENUM('pending', 'sent', 'acknowledged') DEFAULT 'pending',
        `created_by` BIGINT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS `material_return_items` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `return_id` BIGINT UNSIGNED NOT NULL,
        `material_item_id` BIGINT UNSIGNED NOT NULL,
        `return_qty` DECIMAL(15,2) NOT NULL,
        `unit_price` DECIMAL(15,2) NOT NULL
    )"
];

foreach ($queries as $query) {
    if ($db->query($query) === TRUE) {
        echo "Query executed successfully.\n";
    } else {
        echo "Error: " . $db->error . "\n";
    }
}
$db->close();
