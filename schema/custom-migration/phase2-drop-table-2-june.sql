-- =============================================================================
-- PHASE 2 LEGACY & UNUSED TABLES CLEANUP
-- Date      : 2026-06-04
-- Description: Drops 8 redundant and legacy construction tables.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Redundant / Unused tables (Zero Code References)
DROP TABLE IF EXISTS `monthly_phase_snapshots`;
DROP TABLE IF EXISTS `monthly_progress_reviews`;
DROP TABLE IF EXISTS `project_delay_snapshots`;
DROP TABLE IF EXISTS `con_material_issue_items`;

-- 2. Legacy tables (Replaced by new Phase & Weekly Progress system)
DROP TABLE IF EXISTS `weekly_progress_report`;
DROP TABLE IF EXISTS `target_fixed`;
DROP TABLE IF EXISTS `project_working_plan_items`;
DROP TABLE IF EXISTS `project_working_plans`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Cleanup completed successfully. 8 tables dropped.' AS message;
