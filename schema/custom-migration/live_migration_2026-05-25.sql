-- ================================================================
-- LIVE DATABASE MIGRATION SCRIPT
-- Generated : 2026-05-25
-- Source    : tphl_db2.sql (local)  vs  tphl_db.sql (live)
-- Apply to  : tphl_db  (LIVE server)
-- ----------------------------------------------------------------
-- WHAT THIS SCRIPT DOES:
--   1. Adds missing columns to existing live tables
--   2. Creates 2 new tables that exist locally but not on live
-- ================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================
-- SECTION 1: ALTER existing tables — add missing columns
-- ================================================================

-- ----------------------------------------------------------------
-- 1a. daily_work_item_logs
--     Missing: work_type_id, item_status, responsible_team
-- ----------------------------------------------------------------
ALTER TABLE `daily_work_item_logs`
  ADD COLUMN IF NOT EXISTS `work_type_id`      bigint(20) UNSIGNED DEFAULT NULL            AFTER `target_item_id`,
  ADD COLUMN IF NOT EXISTS `item_status`        varchar(30) NOT NULL DEFAULT 'In Progress'  AFTER `remarks`,
  ADD COLUMN IF NOT EXISTS `responsible_team`   varchar(100) DEFAULT NULL                   AFTER `item_status`;

-- ----------------------------------------------------------------
-- 1b. phase_monthly_target_items
--     Missing: actual_qty, actual_progress_pct
-- ----------------------------------------------------------------
ALTER TABLE `phase_monthly_target_items`
  ADD COLUMN IF NOT EXISTS `actual_qty`           decimal(12,2) NOT NULL DEFAULT 0.00
    COMMENT 'Actual quantity achieved'             AFTER `monthly_target_qty`,
  ADD COLUMN IF NOT EXISTS `actual_progress_pct`  decimal(5,2)  NOT NULL DEFAULT 0.00
    COMMENT 'Actual % achieved this month'         AFTER `planned_progress_pct`;

-- ----------------------------------------------------------------
-- 1c. project_drawings
--     Missing: phase_id
-- ----------------------------------------------------------------
ALTER TABLE `project_drawings`
  ADD COLUMN IF NOT EXISTS `phase_id` int(10) UNSIGNED DEFAULT NULL
    COMMENT 'Optional: which construction phase this drawing belongs to'
    AFTER `project_id`;

-- ----------------------------------------------------------------
-- 1d. projects
--     Missing: workflow_version
-- ----------------------------------------------------------------
ALTER TABLE `projects`
  ADD COLUMN IF NOT EXISTS `workflow_version`
    enum('legacy','phase_based') NOT NULL DEFAULT 'legacy'
    COMMENT 'legacy=old target_fixed system | phase_based=new 7-module lifecycle'
    AFTER `name`;

-- ----------------------------------------------------------------
-- 1e. work_types
--     Missing: default_phase_name
-- ----------------------------------------------------------------
ALTER TABLE `work_types`
  ADD COLUMN IF NOT EXISTS `default_phase_name` varchar(150) DEFAULT NULL
    COMMENT 'Suggested construction phase for this work type'
    AFTER `design_type_id`;

-- ================================================================
-- SECTION 2: CREATE new tables (exist locally, missing on live)
-- ================================================================

-- ----------------------------------------------------------------
-- 2a. project_phase_items
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_phase_items` (
  `id`               int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phase_id`         int(10) UNSIGNED NOT NULL,
  `item_name`        varchar(255) NOT NULL,
  `item_order`       tinyint(4) NOT NULL DEFAULT 1,
  `weight_pct`       decimal(5,2) NOT NULL DEFAULT 0.00,
  `planned_progress` decimal(5,2) NOT NULL DEFAULT 0.00,
  `actual_progress`  decimal(5,2) NOT NULL DEFAULT 0.00,
  `design_type_id`   int(10) UNSIGNED DEFAULT NULL,
  `work_type_id`     bigint(20) UNSIGNED DEFAULT NULL,
  `status`           enum('Not Started','In Progress','Completed','At Risk') NOT NULL DEFAULT 'Not Started',
  `notes`            text DEFAULT NULL,
  `created_at`       timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at`       timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ppi_phase`   (`phase_id`),
  KEY `fk_ppi_wtype`   (`work_type_id`),
  KEY `fk_ppi_dtype`   (`design_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------
-- 2b. phase_item_template_defaults
-- ----------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `phase_item_template_defaults` (
  `id`              int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `phase_name`      varchar(150) NOT NULL,
  `item_name`       varchar(255) NOT NULL,
  `item_order`      tinyint(4) NOT NULL DEFAULT 1,
  `weight_pct`      decimal(5,2) NOT NULL DEFAULT 0.00,
  `design_type_id`  int(10) UNSIGNED DEFAULT NULL,
  `work_type_id`    bigint(20) UNSIGNED DEFAULT NULL,
  `created_at`      timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pitd_phase_name`   (`phase_name`),
  KEY `fk_pitd_wtype`         (`work_type_id`),
  KEY `fk_pitd_dtype`         (`design_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- SECTION 3: NOTE — Tables only on LIVE (do NOT delete)
-- ================================================================
-- These tables exist on LIVE but not locally — leave them as-is:
--   asset_movement_logs
--   office_asset_products
--   office_assets
--   project_extra_assets

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- END OF MIGRATION
-- ================================================================
