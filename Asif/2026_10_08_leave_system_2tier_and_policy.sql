-- ============================================================================
-- SQL Migration Script: 2-Tier Leave Approval, 4 Leave Types, Duty Leave & Policy
-- Target Database: TPHL-ERP
-- Created Date: 2026-10-08
-- Location: Asif/2026_10_08_leave_system_2tier_and_policy.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Ensure the 4 Defined Leave Categories exist in `leave_types`
--    - Sick Leave (Paid)
--    - Casual Leave (Paid)
--    - Duty Leave (Comp-off, initial quota 0, earned on holidays)
--    - Leave Without Pay / LWP (Unpaid)
-- ----------------------------------------------------------------------------
INSERT INTO `leave_types` (`name`, `total_days`, `is_paid`, `is_active`, `created_at`, `updated_at`)
SELECT 'Casual Leave', 10, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leave_types` WHERE LOWER(`name`) = 'casual leave');

INSERT INTO `leave_types` (`name`, `total_days`, `is_paid`, `is_active`, `created_at`, `updated_at`)
SELECT 'Duty Leave', 0, 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leave_types` WHERE LOWER(`name`) = 'duty leave');

INSERT INTO `leave_types` (`name`, `total_days`, `is_paid`, `is_active`, `created_at`, `updated_at`)
SELECT 'Leave Without Pay (LWP)', 0, 0, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `leave_types` WHERE LOWER(`name`) LIKE '%lwp%' OR LOWER(`name`) LIKE '%leave without pay%');


ALTER TABLE `employee_leaves`
    ADD COLUMN `reporting_user_id` INT NULL DEFAULT NULL AFTER `employee_id`,
    ADD COLUMN `supervisor_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER `approval_status`,
    ADD COLUMN `supervisor_approved_at` DATETIME NULL DEFAULT NULL AFTER `supervisor_status`,
    ADD COLUMN `supervisor_remarks` TEXT NULL DEFAULT NULL AFTER `supervisor_approved_at`,
    ADD COLUMN `hr_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending' AFTER `supervisor_remarks`,
    ADD COLUMN `hr_approved_by` INT NULL DEFAULT NULL AFTER `hr_status`,
    ADD COLUMN `hr_approved_at` DATETIME NULL DEFAULT NULL AFTER `hr_approved_by`,
    ADD COLUMN `hr_remarks` TEXT NULL DEFAULT NULL AFTER `hr_approved_at`,
    ADD COLUMN `cancelled_at` DATETIME NULL DEFAULT NULL AFTER `hr_remarks`;


ALTER TABLE `employee_leaves`
    MODIFY COLUMN `approval_status` ENUM('pending_supervisor', 'pending_hr', 'approved', 'rejected', 'cancelled', 'pending', 'partial') 
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'pending_supervisor';


CREATE TABLE IF NOT EXISTS `emp_duty_leave_earnings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `attendance_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `work_date` DATE NOT NULL,
    `day_type` ENUM('weekly_holiday', 'public_holiday') NOT NULL DEFAULT 'weekly_holiday',
    `earned_days` DECIMAL(3, 1) NOT NULL DEFAULT '1.0',
    `is_credited` TINYINT(1) NOT NULL DEFAULT '1',
    `credited_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_emp_work_date` (`employee_id`, `work_date`),
    KEY `idx_emp_duty_date` (`employee_id`, `work_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE IF NOT EXISTS `leave_policies` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `policy_name` VARCHAR(150) NOT NULL,
    `applicable_year` INT NOT NULL,
    `sick_leave_days` INT NOT NULL DEFAULT '14',
    `casual_leave_days` INT NOT NULL DEFAULT '10',
    `description` TEXT NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT '1',
    `created_by` INT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_policy_year` (`applicable_year`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
