-- =============================================================================
-- PHASE 2: RISK DASHBOARD & RECOVERY PLAN DB SYNC UPDATE
-- Date      : 2026-06-02
-- Author    : Antigravity Dev Agent
-- Safe      : YES
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. Update project_risks table: Rename/add auto-risk and tracking columns.
-- ----------------------------------------------------------------------------
ALTER TABLE `project_risks` 
    CHANGE COLUMN IF EXISTS `issue_description` `description` TEXT NOT NULL,
    ADD COLUMN IF NOT EXISTS `is_auto` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`,
    ADD COLUMN IF NOT EXISTS `missed_week` INT NULL DEFAULT NULL AFTER `is_auto`,
    ADD COLUMN IF NOT EXISTS `target_item_id` INT UNSIGNED NULL DEFAULT NULL AFTER `missed_week`,
    ADD COLUMN IF NOT EXISTS `missed_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `target_item_id`,
    ADD COLUMN IF NOT EXISTS `planned_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `missed_qty`,
    ADD COLUMN IF NOT EXISTS `achieved_qty` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `planned_qty`;

-- Convert enum columns to VARCHAR to prevent enum constraint failures
ALTER TABLE `project_risks` 
    MODIFY COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'Other',
    MODIFY COLUMN `impact` VARCHAR(50) NOT NULL DEFAULT 'Medium',
    MODIFY COLUMN `likelihood` VARCHAR(50) NOT NULL DEFAULT 'Medium',
    MODIFY COLUMN `severity` VARCHAR(50) NOT NULL DEFAULT 'Medium';


-- ----------------------------------------------------------------------------
-- 2. Update recovery_plans table: Sync with PHP controllers & views.
-- ----------------------------------------------------------------------------
ALTER TABLE `recovery_plans` 
    CHANGE COLUMN IF EXISTS `recovery_month` `plan_month` DATE NOT NULL,
    ADD COLUMN IF NOT EXISTS `phase_id` INT UNSIGNED NULL DEFAULT NULL AFTER `project_id`,
    ADD COLUMN IF NOT EXISTS `delay_carryforward_pct` DECIMAL(5,2) NULL DEFAULT NULL AFTER `plan_month`,
    ADD COLUMN IF NOT EXISTS `pm_notes` TEXT NULL DEFAULT NULL AFTER `extra_working_hours`,
    ADD COLUMN IF NOT EXISTS `status` VARCHAR(50) NOT NULL DEFAULT 'Draft' AFTER `pm_notes`;


-- ----------------------------------------------------------------------------
-- 3. Update recovery_action_items table: Sync with PHP controllers & views.
-- ----------------------------------------------------------------------------
ALTER TABLE `recovery_action_items` 
    CHANGE COLUMN IF EXISTS `recovery_plan_id` `plan_id` INT UNSIGNED NOT NULL,
    ADD COLUMN IF NOT EXISTS `project_id` INT NULL DEFAULT NULL AFTER `plan_id`,
    ADD COLUMN IF NOT EXISTS `action_description` TEXT NOT NULL AFTER `project_id`;


SET FOREIGN_KEY_CHECKS = 1;
