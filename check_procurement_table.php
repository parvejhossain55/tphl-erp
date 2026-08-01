<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$res = $db->query("SHOW TABLES LIKE 'office_procurement_categories'");
if (!$res || $res->num_rows === 0) {
    echo "Table 'office_procurement_categories' does NOT exist. Creating it now...\n";
    $createSql = "
    CREATE TABLE IF NOT EXISTS `office_procurement_categories` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(255) NOT NULL,
      `status` enum('active','inactive') DEFAULT 'active',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    if ($db->query($createSql)) {
        echo "✓ Table 'office_procurement_categories' created successfully!\n";
    } else {
        echo "Error creating table: " . $db->error . "\n";
    }
} else {
    echo "Table 'office_procurement_categories' exists. Describing schema:\n";
    $desc = $db->query("DESCRIBE office_procurement_categories");
    while ($row = $desc->fetch_assoc()) {
        echo " - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
