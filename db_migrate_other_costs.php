<?php
$config = require __DIR__ . '/app/config/config.php';
$conn = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE TABLE IF NOT EXISTS `project_other_costs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `rcost_type` varchar(255) NOT NULL COMMENT 'electricity bill, entertainment bill, internate bill, convaynce bill, others, labour bill',
  `purpose` text NOT NULL,
  `request_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `approved_amount` decimal(10,2) DEFAULT NULL,
  `qc_audit_approved_id` int(11) DEFAULT NULL,
  `qc_audit_note` text DEFAULT NULL,
  `management_apporved_by_id` int(11) DEFAULT NULL,
  `management_apporved_note` text DEFAULT NULL,
  `pc_approved_id` int(11) DEFAULT NULL,
  `payment_by_id` int(11) DEFAULT NULL,
  `payment_at` datetime DEFAULT NULL,
  `created_by_id` int(11) NOT NULL,
  `status` enum('pending', 'qc_approved', 'management_approved', 'paid', 'rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql) === TRUE) {
    echo "Table project_other_costs created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}
$conn->close();
?>
