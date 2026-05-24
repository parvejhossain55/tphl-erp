<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$config = require dirname(__DIR__) . "/app/config/config.php";

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

$sql = "CREATE TABLE IF NOT EXISTS `petty_cash_expenses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `petty_cash_request_id` int unsigned NOT NULL,
  `expense_amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `purpose` text NOT NULL,
  `attachment_path` varchar(500) DEFAULT NULL,
  `created_by` int NOT NULL COMMENT 'users.id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pcr_expenses` (`petty_cash_request_id`),
  CONSTRAINT `fk_pcr_expenses` FOREIGN KEY (`petty_cash_request_id`) REFERENCES `petty_cash_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table petty_cash_expenses created successfully.\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->close();
echo "Done.\n";
?>
