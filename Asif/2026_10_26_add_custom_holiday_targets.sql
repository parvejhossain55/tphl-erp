-- ============================================================================
-- Migration: Add Custom Holiday Targets (Department & Employee) to Holidays Table
-- Date: 2026-10-26
-- ============================================================================

ALTER TABLE `holidays`
ADD COLUMN `applies_to` ENUM('all', 'department', 'employee') NOT NULL DEFAULT 'all' AFTER `end_date`,
ADD COLUMN `department_id` INT NULL DEFAULT NULL AFTER `applies_to`,
ADD COLUMN `employee_id` INT NULL DEFAULT NULL AFTER `department_id`,
ADD INDEX `idx_holidays_applies_to` (`applies_to`),
ADD INDEX `idx_holidays_department` (`department_id`),
ADD INDEX `idx_holidays_employee` (`employee_id`);
