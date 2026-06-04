-- =============================================================================
-- PHASE 2: WEEKLY PROGRESS QC & PM APPROVAL SYSTEM UPDATE
-- Date      : 2026-06-02
-- Author    : Antigravity Dev Agent
-- Safe      : YES
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `phase_weekly_progress`
    ADD COLUMN IF NOT EXISTS `qc_approved_id` INT UNSIGNED NULL DEFAULT NULL AFTER `material_received`,
    ADD COLUMN IF NOT EXISTS `qc_status` VARCHAR(50) NOT NULL DEFAULT 'Pending' AFTER `qc_approved_id`,
    ADD COLUMN IF NOT EXISTS `qc_image` VARCHAR(255) NULL DEFAULT NULL AFTER `qc_status`,
    ADD COLUMN IF NOT EXISTS `qc_comment` TEXT NULL DEFAULT NULL AFTER `qc_image`,
    ADD COLUMN IF NOT EXISTS `pm_approved_id` INT UNSIGNED NULL DEFAULT NULL AFTER `qc_comment`,
    ADD COLUMN IF NOT EXISTS `pm_status` VARCHAR(50) NOT NULL DEFAULT 'Pending' AFTER `pm_approved_id`,
    ADD COLUMN IF NOT EXISTS `pm_comment` TEXT NULL DEFAULT NULL AFTER `pm_status`;

SET FOREIGN_KEY_CHECKS = 1;
