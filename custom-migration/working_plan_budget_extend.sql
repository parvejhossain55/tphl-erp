-- =============================================================================
-- Migration: Working Plans & Budget Extension Requests
-- Run this SQL in phpMyAdmin / MySQL CLI
-- =============================================================================

-- Drop old tables
DROP TABLE IF EXISTS `project_working_plans`;
DROP TABLE IF EXISTS `project_working_plan`;
DROP TABLE IF EXISTS `budget_extend_requests`;

-- project_working_plans (new schema from Readme.md)
CREATE TABLE IF NOT EXISTS `project_working_plans` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`        INT(11)          NOT NULL,
  `initial_budget_id` INT(11) UNSIGNED NOT NULL COMMENT 'FK to initial_estimate_budgets',
  `design_type_id`    INT(11) UNSIGNED NOT NULL COMMENT '1=Construction, 2=Electrical, etc.',
  `year`              SMALLINT UNSIGNED NOT NULL,
  `month`             TINYINT UNSIGNED  NOT NULL,
  `work_description`  TEXT             NOT NULL COMMENT 'কী কী কাজ করা হবে তার বিস্তারিত',
  `planned_quantity`  DECIMAL(12,3)    DEFAULT 0.000 COMMENT 'ঐচ্ছিক',
  `estimated_cost`    DECIMAL(16,2)    NOT NULL DEFAULT 0.00,
  `created_by`        INT(11)          NOT NULL,
  `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pwp_project`    (`project_id`),
  KEY `idx_pwp_budget`     (`initial_budget_id`),
  KEY `idx_pwp_design`     (`design_type_id`),
  KEY `idx_pwp_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- budget_extend_requests (with initial_budget_id FK)
CREATE TABLE IF NOT EXISTS `budget_extend_requests` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`        INT(11)          NOT NULL,
  `initial_budget_id` INT(11) UNSIGNED NOT NULL COMMENT 'FK to initial_estimate_budgets',
  `requested_by`      INT(11)          NOT NULL,
  `title`             VARCHAR(255)     NOT NULL,
  `amount`            DECIMAL(16,4)    NOT NULL,
  `reason`            TEXT             NOT NULL,
  `status`            ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `approved_by`       INT(11)          DEFAULT NULL,
  `approved_at`       TIMESTAMP        NULL DEFAULT NULL,
  `remarks`           TEXT             DEFAULT NULL,
  `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ber_project`      (`project_id`),
  KEY `idx_ber_budget`       (`initial_budget_id`),
  KEY `idx_ber_requested_by` (`requested_by`),
  KEY `idx_ber_approved_by`  (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
