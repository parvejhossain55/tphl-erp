<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require dirname(__DIR__) . "/app/config/config.php";
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to {$config['db_name']}.\n";

// 1. Add payment_attachment_path to fund_requisitions table if it doesn't exist
$res = $conn->query("SHOW COLUMNS FROM fund_requisitions LIKE 'payment_attachment_path'");
if ($res->num_rows == 0) {
    $alterSql = "ALTER TABLE fund_requisitions ADD COLUMN payment_attachment_path VARCHAR(500) NULL AFTER receipt_path";
    if ($conn->query($alterSql)) {
        echo "Added 'payment_attachment_path' column to 'fund_requisitions' table.\n";
    } else {
        echo "Error altering table: " . $conn->error . "\n";
    }
} else {
    echo "'payment_attachment_path' column already exists in 'fund_requisitions'.\n";
}

// 2. Create fund_requisition_expenses table
$createTableSql = "
CREATE TABLE IF NOT EXISTS `fund_requisition_expenses` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `fund_requisition_id` INT UNSIGNED NOT NULL,
  `project_id` INT DEFAULT NULL,
  `other_project_name` VARCHAR(255) DEFAULT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `purpose` TEXT NOT NULL,
  `attachment_path` VARCHAR(500) DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_fre_requisition` FOREIGN KEY (`fund_requisition_id`) REFERENCES `fund_requisitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
";

if ($conn->query($createTableSql) === TRUE) {
    echo "Table 'fund_requisition_expenses' created successfully.\n";
} else {
    echo "Error creating table 'fund_requisition_expenses': " . $conn->error . "\n";
}

$conn->close();
echo "Migration complete.\n";
?>
