-- =============================================================================
-- PHASE-WISE CONSTRUCTION MANAGEMENT — ROLLBACK SQL
-- Version   : 1.0.0
-- Date      : 2026-05-24
-- WARNING   : Only run this if you want to UNDO the migration
-- SAFE      : Does NOT touch any original tables — only drops the 15 new tables
--             and removes the 3 added columns
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Step 1: Drop FK on project_drawings first
ALTER TABLE `project_drawings`
    DROP FOREIGN KEY IF EXISTS `fk_pd_phase`;

-- Step 2: Drop all 15 new tables (in reverse dependency order)
DROP TABLE IF EXISTS `recovery_action_items`;
DROP TABLE IF EXISTS `recovery_plans`;
DROP TABLE IF EXISTS `project_delay_snapshots`;
DROP TABLE IF EXISTS `project_risks`;
DROP TABLE IF EXISTS `monthly_phase_snapshots`;
DROP TABLE IF EXISTS `monthly_progress_reviews`;
DROP TABLE IF EXISTS `daily_work_item_logs`;
DROP TABLE IF EXISTS `daily_progress_logs`;
DROP TABLE IF EXISTS `phase_weekly_progress_items`;
DROP TABLE IF EXISTS `phase_weekly_progress`;
DROP TABLE IF EXISTS `phase_monthly_target_items`;
DROP TABLE IF EXISTS `phase_monthly_targets`;
DROP TABLE IF EXISTS `project_phases`;
DROP TABLE IF EXISTS `phase_template_defaults`;
DROP TABLE IF EXISTS `project_timelines`;

-- Step 3: Remove added columns from existing tables
ALTER TABLE `projects`         DROP COLUMN IF EXISTS `workflow_version`;
ALTER TABLE `work_types`       DROP COLUMN IF EXISTS `default_phase_name`;
ALTER TABLE `project_drawings` DROP COLUMN IF EXISTS `phase_id`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Rollback complete. All 15 new tables dropped. Original tables unchanged.' AS message;
