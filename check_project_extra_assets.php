<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SHOW TABLES LIKE 'project_extra_assets'");
if (!$res || $res->num_rows === 0) {
    echo "Table 'project_extra_assets' does NOT exist. Creating it now...\n";
    $createSql = "
    CREATE TABLE IF NOT EXISTS `project_extra_assets` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `project_id` int(11) DEFAULT NULL,
      `asset_location` varchar(150) DEFAULT NULL,
      `assigned_to` int(11) DEFAULT NULL,
      `item_category_id` int(11) NOT NULL,
      `item_name` varchar(255) NOT NULL,
      `quantity` int(11) NOT NULL DEFAULT 1,
      `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
      `total_price` decimal(12,2) NOT NULL DEFAULT 0.00,
      `justification` text NOT NULL,
      `attachment` varchar(500) DEFAULT NULL,
      `requested_by` int(11) NOT NULL,
      `overall_status` enum('pending','in_review','approved','rejected','purchased') DEFAULT 'pending',
      `operations_status` enum('pending','approved','rejected') DEFAULT 'pending',
      `operations_by` int(11) DEFAULT NULL,
      `operations_note` text DEFAULT NULL,
      `operations_at` datetime DEFAULT NULL,
      `purchase_head_status` enum('pending','approved','rejected') DEFAULT 'pending',
      `purchase_head_by` int(11) DEFAULT NULL,
      `purchase_head_note` text DEFAULT NULL,
      `purchase_head_document` varchar(500) DEFAULT NULL,
      `purchase_head_at` datetime DEFAULT NULL,
      `qc_audit_status` enum('pending','approved','rejected') DEFAULT 'pending',
      `qc_audit_by` int(11) DEFAULT NULL,
      `qc_audit_note` text DEFAULT NULL,
      `qc_audit_at` datetime DEFAULT NULL,
      `management_status` enum('pending','approved','rejected') DEFAULT 'pending',
      `management_by` int(11) DEFAULT NULL,
      `management_note` text DEFAULT NULL,
      `management_at` datetime DEFAULT NULL,
      `paid_amount` decimal(12,2) DEFAULT NULL,
      `payment_method` varchar(50) DEFAULT NULL,
      `payment_by` int(11) DEFAULT NULL,
      `payment_at` datetime DEFAULT NULL,
      `purchase_confirmed` tinyint(1) DEFAULT 0,
      `purchase_confirmed_by` int(11) DEFAULT NULL,
      `purchase_confirmed_at` datetime DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    if ($db->query($createSql)) {
        echo "✓ Table 'project_extra_assets' created successfully!\n";
    } else {
        echo "Error creating table: " . $db->error . "\n";
    }
} else {
    echo "Table 'project_extra_assets' exists. Describing schema:\n";
    $desc = $db->query("DESCRIBE project_extra_assets");
    while ($row = $desc->fetch_assoc()) {
        echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
