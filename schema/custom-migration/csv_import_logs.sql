-- CSV Import Logs Table - Audit trail for bulk payment imports
-- Run this migration on the TPHL database

CREATE TABLE IF NOT EXISTS `csv_import_logs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `error_details` longtext DEFAULT NULL COMMENT 'JSON array of row-level errors',
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
