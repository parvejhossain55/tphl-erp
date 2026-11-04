-- ============================================================================
-- Migration: Add Salary & Notice Period fields to ats_candidates
-- Date: 2026-11-04
-- ============================================================================

ALTER TABLE `ats_candidates`
ADD COLUMN `current_salary` DECIMAL(12,2) DEFAULT NULL AFTER `cv_drive_link`,
ADD COLUMN `expected_salary` DECIMAL(12,2) DEFAULT NULL AFTER `current_salary`,
ADD COLUMN `bottom_line_salary` DECIMAL(12,2) DEFAULT NULL AFTER `expected_salary`,
ADD COLUMN `notice_period` VARCHAR(30) DEFAULT NULL COMMENT 'Yes, Immediate Joiner' AFTER `bottom_line_salary`,
ADD COLUMN `notice_period_days` INT DEFAULT NULL COMMENT 'Notice period time in days' AFTER `notice_period`;
