<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql1 = "CREATE TABLE IF NOT EXISTS `project_incentive_claims` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id` INT(10) UNSIGNED DEFAULT NULL,
  `project_id` INT(11) NOT NULL,
  `flat_id` INT(11) DEFAULT NULL,
  `user_id` INT(11) NOT NULL COMMENT 'Sales Person or External Employee user_id',
  `is_external` TINYINT(1) DEFAULT 0 COMMENT '0 = Sales Person, 1 = Non-sales / External Employee',
  `assigned_by` INT(11) DEFAULT NULL COMMENT 'User ID who assigned external incentive',
  `sale_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `incentive_percentage` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `incentive_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `booking_completed` TINYINT(1) DEFAULT 0,
  `registration_completed` TINYINT(1) DEFAULT 0,
  `claim_status` ENUM('eligible', 'claimed', 'approved', 'rejected', 'partially_paid', 'paid') NOT NULL DEFAULT 'eligible',
  `claimed_at` DATETIME DEFAULT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`booking_id`),
  INDEX (`project_id`),
  INDEX (`claim_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$sql2 = "CREATE TABLE IF NOT EXISTS `project_incentive_payments` (
  `id` INT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `claim_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(11) NOT NULL,
  `payment_amount` DECIMAL(15,2) NOT NULL,
  `payment_method` ENUM('Cash', 'Bank Transfer', 'Mobile Banking', 'Cheque') NOT NULL DEFAULT 'Bank Transfer',
  `transaction_ref` VARCHAR(100) DEFAULT NULL,
  `payment_date` DATE NOT NULL,
  `bank_name` VARCHAR(100) DEFAULT NULL,
  `disbursed_by` INT(11) NOT NULL COMMENT 'Accounts User ID',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`claim_id`),
  INDEX (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if ($mysqli->query($sql1) && $mysqli->query($sql2)) {
    echo "Incentive database tables created successfully!\n";
} else {
    echo "Error creating tables: " . $mysqli->error . "\n";
}
