-- Migration: Create project_emi_schedule_items table for per-installment due dates
-- Date: 2026-11-21

CREATE TABLE IF NOT EXISTS `project_emi_schedule_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL,
  `emi_no` int NOT NULL,
  `due_date` date NOT NULL,
  `emi_amount` decimal(12,2) DEFAULT NULL,
  `remarks` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Active, 0=Deactive',
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_emi_no` (`project_id`, `emi_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
