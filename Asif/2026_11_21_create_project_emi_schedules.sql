-- Migration: Create project_emi_schedules table for project-wise EMI schedule configuration
-- Date: 2026-11-21

CREATE TABLE IF NOT EXISTS `project_emi_schedules` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `emi_start_date` date DEFAULT NULL COMMENT 'First EMI start date',
  `total_emi_no` int DEFAULT 36 COMMENT 'Total EMI count (months)',
  `emi_due_day` tinyint unsigned DEFAULT 10 COMMENT 'Monthly due day (1-31)',
  `default_emi_amount` decimal(12,2) DEFAULT NULL COMMENT 'Default monthly EMI amount (optional)',
  `remarks` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
