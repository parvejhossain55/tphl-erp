<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$query = "
CREATE TABLE IF NOT EXISTS `emi_followups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `emi_no` int(11) NOT NULL,
  `note` text NOT NULL,
  `added_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_emi_idx` (`booking_id`, `emi_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if ($db->query($query) === TRUE) {
    echo "Table 'emi_followups' created successfully.";
} else {
    echo "Error creating table: " . $db->error;
}
