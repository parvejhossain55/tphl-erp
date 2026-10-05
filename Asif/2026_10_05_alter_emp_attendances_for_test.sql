-- ============================================================================
-- Migration: Add shift and calculated columns to emp_attendances
-- Date: 2026-10-05
-- Purpose: Support Test Attendance Module for HR and Employees
-- ============================================================================

-- 1. Add shift, late minutes, working hours, and overtime columns if not present
ALTER TABLE `emp_attendances`
ADD COLUMN IF NOT EXISTS `shift_name` VARCHAR(100) NULL AFTER `today_status`,
ADD COLUMN IF NOT EXISTS `shift_start_time` TIME NULL AFTER `shift_name`,
ADD COLUMN IF NOT EXISTS `late_minutes` INT DEFAULT 0 AFTER `shift_start_time`,
ADD COLUMN IF NOT EXISTS `working_hours` VARCHAR(20) NULL AFTER `late_minutes`,
ADD COLUMN IF NOT EXISTS `overtime_minutes` INT DEFAULT 0 AFTER `working_hours`;

-- 2. Ensure today_status enum includes 'Holiday' and 'Day Off'
ALTER TABLE `emp_attendances` 
MODIFY COLUMN `today_status` ENUM('Present','Late','Absent','On Leave','Half-Day','Holiday','Day Off') DEFAULT 'Absent';
