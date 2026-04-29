-- ============================================================
--  TPHL-ERP: Local → Live Server Migration Script
--  Generated: 2026-04-29
--  Run this on LIVE server phpMyAdmin / MySQL CLI
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

-- ============================================================
-- SECTION 1: CREATE MISSING TABLES (3 tables)
-- ============================================================

-- 1A. work_types
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `work_types` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `design_type_id` bigint(20) UNSIGNED NOT NULL,
  `work_type_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 1B. project_structural_designs
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_structural_designs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `designer_type` enum('internal','external') NOT NULL DEFAULT 'internal',
  `designer_name` varchar(255) NOT NULL,
  `company_name` varchar(200) DEFAULT NULL,
  `designer_phone` varchar(20) DEFAULT NULL,
  `designer_address` text DEFAULT NULL,
  `design_link` text NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 1C. client_refund_requests
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `client_refund_requests` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) UNSIGNED NOT NULL,
  `lead_id` int(10) UNSIGNED NOT NULL,
  `project_id` int(11) NOT NULL,
  `flat_id` int(11) NOT NULL,
  `total_paid` decimal(15,2) NOT NULL DEFAULT 0.00,
  `retention_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `cashout_request_id` int(11) DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- SECTION 2: ALTER EXISTING TABLES (add missing columns)
-- IF NOT EXISTS used to safely re-run without errors
-- ============================================================

-- 2A. projects (10 role/team assignment columns)
ALTER TABLE `projects`
  ADD COLUMN IF NOT EXISTS `pm_id`              int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `site_engineer_id`   int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `landman_id`         int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `struct_engineer_id` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `operation_head_id`  int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `draftsman_id`       int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `architect_id`       int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `estimator_id`       int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `qc_id`              int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `operator_id`        int(11) DEFAULT NULL;

-- 2B. initial_estimate_budgets (4 columns)
ALTER TABLE `initial_estimate_budgets`
  ADD COLUMN IF NOT EXISTS `work_type_id`      bigint(20) UNSIGNED DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `budget_link`       varchar(255)        DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `work_details`      text                DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `other_cost_detail` text                DEFAULT NULL;

-- 2C. initial_estimate_items (1 column)
ALTER TABLE `initial_estimate_items`
  ADD COLUMN IF NOT EXISTS `work_type_id` bigint(20) UNSIGNED DEFAULT NULL;

-- 2D. initial_estimate_labors (1 column)
ALTER TABLE `initial_estimate_labors`
  ADD COLUMN IF NOT EXISTS `work_type_id` bigint(20) UNSIGNED DEFAULT NULL;

-- 2E. architect_engineering (1 column)
ALTER TABLE `architect_engineering`
  ADD COLUMN IF NOT EXISTS `status` enum('draft','partially','fully') DEFAULT 'draft';

-- 2F. boq_master (2 columns)
ALTER TABLE `boq_master`
  ADD COLUMN IF NOT EXISTS `target_id`      int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `design_type_id` int(11) DEFAULT NULL;

-- 2G. con_financial_transactions (2 columns)
ALTER TABLE `con_financial_transactions`
  ADD COLUMN IF NOT EXISTS `transaction_date_str` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `disbursement_proof`   text DEFAULT NULL;

-- 2H. lead_follow_ups (3 columns)
ALTER TABLE `lead_follow_ups`
  ADD COLUMN IF NOT EXISTS `follow_up_agent_id`        int(11)      DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `lead_status`               varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `next_follow_up_plan_notes` text         DEFAULT NULL;

-- 2I. lead_payments (1 column)
ALTER TABLE `lead_payments`
  ADD COLUMN IF NOT EXISTS `due_emi_amount` decimal(12,2) DEFAULT 0.00;

-- 2J. material_receiving (3 PM approval columns)
ALTER TABLE `material_receiving`
  ADD COLUMN IF NOT EXISTS `pm_checked_by`  int(11)  DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `pm_note`        text     DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `pm_approved_at` datetime DEFAULT NULL;

-- 2K. purchase_requests (1 column)
ALTER TABLE `purchase_requests`
  ADD COLUMN IF NOT EXISTS `bom_id` int(11) DEFAULT NULL;

-- 2L. target_fixed (3 columns)
ALTER TABLE `target_fixed`
  ADD COLUMN IF NOT EXISTS `is_lab_test`    int(11) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `lab_test_date`  date    DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `target_details` text    DEFAULT NULL;

-- 2M. weekly_progress_report (2 columns)
ALTER TABLE `weekly_progress_report`
  ADD COLUMN IF NOT EXISTS `approved_by_pm` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `approved_by_qc` int(11) DEFAULT NULL;

-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;
-- Done! 3 new tables + 13 altered tables (~35 new columns)
-- ============================================================
