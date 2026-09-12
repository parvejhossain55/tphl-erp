-- =========================================================================
-- Meal Tracker Module Database Setup
-- Description: Migration for Meal Projects, Daily Records, Guest Meals & Employee settings
-- Collation: utf8mb4_general_ci (MariaDB & MySQL compatible)
-- =========================================================================

-- 1. Alter employees table to add is_meal_deduction and per_meal_percentage
SET @dbname = DATABASE();
SET @tablename = "employees";

-- Check and add is_meal_deduction
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = "is_meal_deduction")
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `employees` ADD COLUMN `is_meal_deduction` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Subject to meal deduction, 0 = No deduction' AFTER `per_meal_amount`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Check and add per_meal_percentage
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = "per_meal_percentage")
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `employees` ADD COLUMN `per_meal_percentage` DECIMAL(5,2) NOT NULL DEFAULT 100.00 COMMENT 'Percentage of standard meal amount employee pays (e.g. 100.00, 50.00)' AFTER `is_meal_deduction`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Create meal_projects table
CREATE TABLE IF NOT EXISTS `meal_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT 'e.g. Aug-Sep 2026 Meal Project',
  `standard_meal_amount` decimal(10,2) NOT NULL DEFAULT 120.00,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `payroll_project_id` int(11) DEFAULT NULL COMMENT 'Link to payroll_projects.id',
  `status` enum('active','locked','closed') NOT NULL DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payroll_project` (`payroll_project_id`),
  KEY `idx_dates` (`from_date`, `to_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. Create meal_records table (stores daily meal checks per employee)
CREATE TABLE IF NOT EXISTS `meal_records` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `meal_project_id` int(11) NOT NULL,
  `office_branch_id` int(11) NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `meal_date` date NOT NULL,
  `is_consumed` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Meal consumed, 0 = No meal',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_emp_date` (`meal_project_id`, `employee_id`, `meal_date`),
  KEY `idx_project_branch` (`meal_project_id`, `office_branch_id`),
  KEY `idx_emp_date` (`employee_id`, `meal_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Create meal_guest_records table (for office visitor/guest meals per branch)
CREATE TABLE IF NOT EXISTS `meal_guest_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meal_project_id` int(11) NOT NULL,
  `office_branch_id` int(11) NOT NULL,
  `meal_date` date NOT NULL,
  `guest_count` int(11) NOT NULL DEFAULT 0,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_branch_date` (`meal_project_id`, `office_branch_id`, `meal_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
