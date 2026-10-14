-- Migration: Create project_progress table
-- Date: 2026-10-14
-- Author: Asif / Assistant

CREATE TABLE IF NOT EXISTS `project_progress` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT NOT NULL,
  `last_updated_date` DATE DEFAULT NULL,
  `last_month_target` DECIMAL(5,2) DEFAULT 0.00,
  `last_month_achieved` DECIMAL(5,2) DEFAULT 0.00,
  `structural_work` DECIMAL(5,2) DEFAULT 0.00,
  `plaster_work` DECIMAL(5,2) DEFAULT 0.00,
  `brick_work` DECIMAL(5,2) DEFAULT 0.00,
  `electrical_work` DECIMAL(5,2) DEFAULT 0.00,
  `plumbing_work` DECIMAL(5,2) DEFAULT 0.00,
  `tiles_work` DECIMAL(5,2) DEFAULT 0.00,
  `paint_work` DECIMAL(5,2) DEFAULT 0.00,
  `total_progress` DECIMAL(5,2) DEFAULT 0.00,
  `created_by` INT DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
