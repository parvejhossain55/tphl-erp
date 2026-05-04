<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/app/config/config.php';
$config = require __DIR__ . '/app/config/config.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$queries = [
    "CREATE TABLE IF NOT EXISTS `qc_checklists` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `work_type_id` INT NOT NULL,
        `material_item_id` INT NULL,
        `checklist_item_name` VARCHAR(255) NOT NULL, 
        `created_by` BIGINT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS `qc_inspections` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `work_type_id` INT NOT NULL,
        `inspector_id` BIGINT UNSIGNED NOT NULL,
        `inspection_date` DATE NOT NULL,
        `overall_status` ENUM('passed', 'failed', 'rework_needed') NOT NULL,
        `comments` TEXT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS `qc_inspection_items` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `qc_inspection_id` BIGINT UNSIGNED NOT NULL,
        `qc_checklist_id` BIGINT UNSIGNED NOT NULL,
        `is_passed` BOOLEAN DEFAULT TRUE,
        `observation_note` TEXT NULL,
        CONSTRAINT `fk_ins_master` FOREIGN KEY (`qc_inspection_id`) REFERENCES `qc_inspections`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_ins_checklist` FOREIGN KEY (`qc_checklist_id`) REFERENCES `qc_checklists`(`id`)
    )",
    "CREATE TABLE IF NOT EXISTS `qc_material_tests` (
        `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `material_receiving_id` BIGINT UNSIGNED NOT NULL,
        `test_name` VARCHAR(255),
        `result_value` VARCHAR(100),
        `test_status` ENUM('pass', 'fail') NOT NULL,
        `lab_report_link` TEXT NULL,
        `created_by` BIGINT UNSIGNED NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
];

foreach ($queries as $query) {
    if ($db->query($query) === TRUE) {
        echo "Table created successfully\n";
    } else {
        echo "Error creating table: " . $db->error . "\n";
    }
}
$db->close();
echo "Done.\n";
