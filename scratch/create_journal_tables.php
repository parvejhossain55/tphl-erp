<?php
$config = require __DIR__ . '/../app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$queries = [
    "CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `account_code` VARCHAR(50) NOT NULL UNIQUE,
        `account_name` VARCHAR(255) NOT NULL,
        `account_type` ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
        `status` ENUM('active', 'inactive') DEFAULT 'active'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `journals` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `journal_number` VARCHAR(50) NOT NULL UNIQUE,
        `date` DATE NOT NULL,
        `voucher_type` VARCHAR(50) DEFAULT 'General',
        `narration` TEXT,
        `total_debit` DECIMAL(15,2) DEFAULT 0.00,
        `total_credit` DECIMAL(15,2) DEFAULT 0.00,
        `status` ENUM('pending', 'approved', 'posted', 'rejected') DEFAULT 'pending',
        `created_by` INT(11) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `journal_details` (
        `id` INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `journal_id` INT(11) UNSIGNED NOT NULL,
        `account_id` INT(11) UNSIGNED NOT NULL,
        `debit` DECIMAL(15,2) DEFAULT 0.00,
        `credit` DECIMAL(15,2) DEFAULT 0.00,
        `project_id` INT(11) DEFAULT NULL,
        `reference_id` INT(11) DEFAULT NULL,
        `reference_type` VARCHAR(50) DEFAULT NULL,
        `narration` TEXT,
        FOREIGN KEY (`journal_id`) REFERENCES `journals`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts`(`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

$success = true;
foreach ($queries as $index => $query) {
    if ($db->query($query) === TRUE) {
        echo "Query " . ($index + 1) . " executed successfully.\n";
    } else {
        echo "Error creating table: " . $db->error . "\n";
        $success = false;
    }
}

if ($success) {
    echo "All tables created successfully!";
}

$db->close();
