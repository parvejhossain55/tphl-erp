-- ============================================================================
-- SQL Migration Script: Late Attendance Approval System (2-Tier & Waiver)
-- Target Database: TPHL-ERP
-- Created Date: 2026-10-08
-- Location: Asif/2026_10_08_emp_late_approvals.sql
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Extend `emp_attendances` Table with Late Approval Flags
-- ----------------------------------------------------------------------------
ALTER TABLE `emp_attendances`
    ADD COLUMN IF NOT EXISTS `is_late_approved` TINYINT(1) NOT NULL DEFAULT 0 AFTER `late_minutes`,
    ADD COLUMN IF NOT EXISTS `late_approval_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `is_late_approved`;

-- ----------------------------------------------------------------------------
-- 2. Create `emp_late_approvals` Table
--    Tracks employee late waiver applications with 2-tier approval workflow
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emp_late_approvals` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `employee_id` INT UNSIGNED NOT NULL,
    `attendance_id` BIGINT UNSIGNED NULL DEFAULT NULL,
    `attendance_date` DATE NOT NULL,
    `shift_start_time` TIME NULL DEFAULT NULL,
    `punch_in_time` DATETIME NULL DEFAULT NULL,
    `late_minutes` INT NOT NULL DEFAULT 0,
    `reason_type` VARCHAR(100) NOT NULL DEFAULT 'Official Duty',
    `reason_description` TEXT NULL DEFAULT NULL,
    `reporting_user_id` INT NULL DEFAULT NULL,
    `supervisor_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `supervisor_remarks` TEXT NULL DEFAULT NULL,
    `supervisor_approved_at` DATETIME NULL DEFAULT NULL,
    `hr_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `hr_approved_by` INT NULL DEFAULT NULL,
    `hr_approved_at` DATETIME NULL DEFAULT NULL,
    `hr_remarks` TEXT NULL DEFAULT NULL,
    `approval_status` ENUM('pending_supervisor', 'pending_hr', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending_supervisor',
    `cancelled_at` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_emp_late_date` (`employee_id`, `attendance_date`),
    KEY `idx_late_employee` (`employee_id`),
    KEY `idx_late_attendance` (`attendance_id`),
    KEY `idx_late_supervisor` (`reporting_user_id`),
    KEY `idx_late_status` (`approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
