<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require __DIR__ . "/app/config/config.php";

$db_host = $config['db_host'];
$db_user = $config['db_user'];
$db_pass = $config['db_pass'];
$db_name = $config['db_name'];

echo "Connecting to database: {$db_name} on {$db_host}...\n";
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully.\n";

$queries = [
    // 1. land_acquisitions
    "DROP TABLE IF EXISTS `land_acquisitions`",
    "CREATE TABLE `land_acquisitions` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `project_id` int NOT NULL,
      `land_owner_name` varchar(255) NOT NULL,
      `land_owner_phone` varchar(50) DEFAULT NULL,
      `total_area` decimal(12,4) NOT NULL COMMENT 'in decimals/kathas',
      `area_unit` varchar(20) DEFAULT 'decimal',
      `price_per_unit` decimal(15,2) NOT NULL,
      `total_amount` decimal(15,2) NOT NULL,
      `location` varchar(500) DEFAULT NULL,
      `status` enum('selected','bayana','registered','cancelled') DEFAULT 'selected',
      `remarks` text DEFAULT NULL,
      `created_by` int NOT NULL COMMENT 'user_id',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 2. land_bayana_payments
    "DROP TABLE IF EXISTS `land_bayana_payments`",
    "CREATE TABLE `land_bayana_payments` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `land_acquisition_id` int unsigned NOT NULL,
      `payment_amount` decimal(15,2) NOT NULL,
      `payment_date` date NOT NULL,
      `payment_method` varchar(100) DEFAULT 'Bank',
      `transaction_no` varchar(100) DEFAULT NULL,
      `bayana_document_link` varchar(500) DEFAULT NULL,
      `remarks` text DEFAULT NULL,
      `created_by` int NOT NULL COMMENT 'user_id',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 3. land_registrations
    "DROP TABLE IF EXISTS `land_registrations`",
    "CREATE TABLE `land_registrations` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `land_acquisition_id` int unsigned NOT NULL,
      `registration_no` varchar(100) NOT NULL,
      `registration_date` date NOT NULL,
      `sub_registry_office` varchar(255) DEFAULT NULL,
      `registration_cost` decimal(15,2) NOT NULL,
      `mutation_status` enum('Pending','Completed') DEFAULT 'Pending',
      `mutation_date` date DEFAULT NULL,
      `deed_document_link` varchar(500) DEFAULT NULL,
      `remarks` text DEFAULT NULL,
      `created_by` int NOT NULL COMMENT 'user_id',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 4. land_fund_requisitions
    "DROP TABLE IF EXISTS `land_fund_requisitions`",
    "CREATE TABLE `land_fund_requisitions` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `land_acquisition_id` int unsigned NOT NULL,
      `requisition_type` enum('Bayana','Registration','Extra Cost','Other') NOT NULL,
      `requested_amount` decimal(15,2) NOT NULL,
      `purpose` text NOT NULL,
      `requested_by` int NOT NULL COMMENT 'users.id',
      `mgmt_status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
      `mgmt_by` int DEFAULT NULL COMMENT 'users.id',
      `mgmt_note` text DEFAULT NULL,
      `mgmt_approved_at` timestamp NULL DEFAULT NULL,
      `accounts_status` enum('Pending','Paid','Rejected') DEFAULT 'Pending',
      `accounts_by` int DEFAULT NULL COMMENT 'users.id',
      `accounts_note` text DEFAULT NULL,
      `accounts_paid_at` timestamp NULL DEFAULT NULL,
      `payment_method` varchar(50) DEFAULT NULL,
      `transaction_no` varchar(100) DEFAULT NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 5. land_monthly_reports
    "DROP TABLE IF EXISTS `land_monthly_reports`",
    "CREATE TABLE `land_monthly_reports` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `project_id` int NOT NULL,
      `report_month` varchar(7) NOT NULL COMMENT 'YYYY-MM',
      `total_lands` int DEFAULT 0,
      `total_area` decimal(12,4) DEFAULT 0,
      `total_land_value` decimal(15,2) DEFAULT 0,
      `total_bayana_paid` decimal(15,2) DEFAULT 0,
      `total_reg_cost` decimal(15,2) DEFAULT 0,
      `lands_selected` int DEFAULT 0,
      `lands_bayana` int DEFAULT 0,
      `lands_registered` int DEFAULT 0,
      `lands_cancelled` int DEFAULT 0,
      `summary_notes` text DEFAULT NULL,
      `generated_by` int NOT NULL COMMENT 'users.id',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

    // 6. petty_cash_requests
    "DROP TABLE IF EXISTS `petty_cash_requests`",
    "CREATE TABLE `petty_cash_requests` (
      `id` int unsigned NOT NULL AUTO_INCREMENT,
      `department_id` int NOT NULL,
      `project_id` int DEFAULT NULL,
      `requested_by` int NOT NULL COMMENT 'users.id',
      `expense_date` date NOT NULL,
      `expense_category` enum('Transport','Food','Office Supplies','Communication','Site Expense','Repair','Miscellaneous') NOT NULL,
      `amount` decimal(12,2) NOT NULL,
      `purpose` text NOT NULL,
      `receipt_path` varchar(500) DEFAULT NULL,
      `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
      `approved_by` int DEFAULT NULL COMMENT 'users.id',
      `approved_at` timestamp NULL DEFAULT NULL,
      `approval_note` text DEFAULT NULL,
      `paid_by` int DEFAULT NULL COMMENT 'users.id',
      `paid_at` timestamp NULL DEFAULT NULL,
      `payment_method` varchar(50) DEFAULT 'Cash',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
];

foreach ($queries as $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "Success: " . substr($sql, 0, 50) . "...\n";
    } else {
        echo "Error: " . $conn->error . "\nFor Query: " . $sql . "\n";
    }
}

$conn->close();
echo "Migration complete.\n";
