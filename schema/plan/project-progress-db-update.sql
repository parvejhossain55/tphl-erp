-- =============================================================================
-- SAFE RUN VERSION — conflict-free for existing DB
-- Changes from original:
--   1. Removed INSERT IGNORE for phase_template_defaults (15 phases already seeded)
--   2. FK fk_pd_phase already exists on project_drawings — skipped
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- =============================================================================
-- SECTION 1: ALTER EXISTING TABLES (all use IF NOT EXISTS — safe)
-- =============================================================================

ALTER TABLE `projects`
    ADD COLUMN IF NOT EXISTS `workflow_version`
        ENUM('legacy','phase_based') NOT NULL DEFAULT 'legacy'
        AFTER `name`;

ALTER TABLE `work_types`
    ADD COLUMN IF NOT EXISTS `default_phase_name`
        VARCHAR(150) NULL DEFAULT NULL
        AFTER `design_type_id`;

ALTER TABLE `project_drawings`
    ADD COLUMN IF NOT EXISTS `phase_id`
        INT UNSIGNED NULL DEFAULT NULL
        AFTER `project_id`;

-- =============================================================================
-- SECTION 2: CREATE NEW TABLES (all IF NOT EXISTS — safe)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `project_timelines` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `project_code`              VARCHAR(50)     NULL DEFAULT NULL,
    `project_type`              VARCHAR(100)    NULL DEFAULT NULL,
    `project_category`          VARCHAR(100)    NULL DEFAULT NULL,
    `handover_months`           INT             NOT NULL,
    `construction_months`       INT             NOT NULL,
    `post_construction_months`  INT             NOT NULL DEFAULT 6,
    `project_start_date`        DATE            NOT NULL,
    `construction_start_date`   DATE            NOT NULL,
    `construction_end_date`     DATE            NOT NULL,
    `expected_handover_date`    DATE            NOT NULL,
    `current_status`            ENUM('Planning','Pre-Construction','Construction','Post-Construction','Handover','Completed') NOT NULL DEFAULT 'Planning',
    `description`               TEXT            NULL DEFAULT NULL,
    `created_by`                INT             NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pt_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `phase_template_defaults` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `phase_name`                VARCHAR(150)    NOT NULL,
    `phase_order`               TINYINT         NOT NULL,
    `default_weight_percentage` DECIMAL(5,2)    NOT NULL,
    `description`               TEXT            NULL DEFAULT NULL,
    `is_active`                 TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_phase_order` (`phase_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- !! SKIPPED: INSERT IGNORE for phase_template_defaults
-- Reason: 15 work-type phases already seeded (Pre-Construction + 14 BOQ categories)
-- Inserting the original 6 legacy phases would create DUPLICATE ROWS

CREATE TABLE IF NOT EXISTS `project_phases` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`            INT             NOT NULL,
    `phase_name`            VARCHAR(150)    NOT NULL,
    `phase_order`           TINYINT         NOT NULL DEFAULT 1,
    `weight_percentage`     DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `planned_start_date`    DATE            NULL DEFAULT NULL,
    `planned_end_date`      DATE            NULL DEFAULT NULL,
    `actual_start_date`     DATE            NULL DEFAULT NULL,
    `actual_end_date`       DATE            NULL DEFAULT NULL,
    `planned_progress`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `actual_progress`       DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `status`                ENUM('Not Started','In Progress','Completed','At Risk','Delayed') NOT NULL DEFAULT 'Not Started',
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pp_project` (`project_id`),
    KEY `idx_pp_order` (`project_id`, `phase_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `phase_monthly_targets` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `phase_id`                  INT UNSIGNED    NOT NULL,
    `target_month`              DATE            NOT NULL,
    `target_month_number`       TINYINT         NOT NULL,
    `target_year`               YEAR            NOT NULL,
    `total_target_weight_pct`   DECIMAL(5,2)    NULL DEFAULT NULL,
    `planning_status`           ENUM('Draft','In Review','Approved') NOT NULL DEFAULT 'Draft',
    `responsible_team`          VARCHAR(150)    NULL DEFAULT NULL,
    `created_by`                INT             NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pmt_month` (`project_id`, `phase_id`, `target_month`),
    KEY `idx_pmt_phase` (`phase_id`),
    CONSTRAINT `fk_pmt_phase` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `phase_monthly_target_items` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `target_id`                 INT UNSIGNED    NOT NULL,
    `work_type_id`              BIGINT UNSIGNED NULL DEFAULT NULL,
    `work_item_name`            VARCHAR(200)    NOT NULL,
    `unit`                      VARCHAR(50)     NULL DEFAULT NULL,
    `monthly_target_qty`        DECIMAL(12,2)   NULL DEFAULT NULL,
    `planned_progress_pct`      DECIMAL(5,2)    NULL DEFAULT NULL,
    `start_date`                DATE            NULL DEFAULT NULL,
    `end_date`                  DATE            NULL DEFAULT NULL,
    `responsible_team`          VARCHAR(150)    NULL DEFAULT NULL,
    `priority`                  ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
    `status`                    ENUM('Draft','Ready','Approved','At Risk','Critical') NOT NULL DEFAULT 'Draft',
    `week_1_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `week_2_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `week_3_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `week_4_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `sort_order`                INT             NOT NULL DEFAULT 0,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pmti_target` (`target_id`),
    CONSTRAINT `fk_pmti_target` FOREIGN KEY (`target_id`) REFERENCES `phase_monthly_targets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `phase_weekly_progress` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `phase_id`                  INT UNSIGNED    NOT NULL,
    `target_id`                 INT UNSIGNED    NOT NULL,
    `week_number`               TINYINT         NOT NULL,
    `week_start_date`           DATE            NOT NULL,
    `week_end_date`             DATE            NOT NULL,
    `overall_progress_pct`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `total_planned_qty`         DECIMAL(12,2)   NULL DEFAULT NULL,
    `total_achieved_qty`        DECIMAL(12,2)   NULL DEFAULT NULL,
    `labour_used`               INT             NOT NULL DEFAULT 0,
    `cement_used_bags`          INT             NOT NULL DEFAULT 0,
    `concrete_poured_mt`        DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    `concrete_poured_cft`       DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    `materials_used`            INT             NOT NULL DEFAULT 0,
    `equipment_active`          INT             NOT NULL DEFAULT 0,
    `weather_condition`         VARCHAR(100)    NULL DEFAULT NULL,
    `temperature_celsius`       DECIMAL(4,1)    NULL DEFAULT NULL,
    `site_engineer_remarks`     TEXT            NULL DEFAULT NULL,
    `safety_ok`                 TINYINT(1)      NOT NULL DEFAULT 0,
    `qc_checked`                TINYINT(1)      NOT NULL DEFAULT 0,
    `material_received`         TINYINT(1)      NOT NULL DEFAULT 0,
    `entry_status`              ENUM('Draft','Submitted','Approved') NOT NULL DEFAULT 'Draft',
    `submitted_by`              INT             NULL DEFAULT NULL,
    `submitted_at`              DATETIME        NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pwp_week` (`project_id`, `phase_id`, `target_id`, `week_number`),
    KEY `idx_pwp_target` (`target_id`),
    CONSTRAINT `fk_pwp_phase`  FOREIGN KEY (`phase_id`)  REFERENCES `project_phases`        (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pwp_target` FOREIGN KEY (`target_id`) REFERENCES `phase_monthly_targets` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `phase_weekly_progress_items` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `weekly_progress_id`    INT UNSIGNED    NOT NULL,
    `target_item_id`        INT UNSIGNED    NOT NULL,
    `planned_qty`           DECIMAL(12,2)   NULL DEFAULT NULL,
    `achieved_qty`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `today_progress_qty`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `percent_complete`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `responsible_team`      VARCHAR(150)    NULL DEFAULT NULL,
    `status`                ENUM('Not Started','In Progress','Completed','Delayed','Pending QA') NOT NULL DEFAULT 'Not Started',
    `has_issue_flag`        TINYINT(1)      NOT NULL DEFAULT 0,
    `photo_path`            VARCHAR(500)    NULL DEFAULT NULL,
    `remarks_action`        TEXT            NULL DEFAULT NULL,
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pwpi_weekly` (`weekly_progress_id`),
    KEY `idx_pwpi_item`   (`target_item_id`),
    CONSTRAINT `fk_pwpi_progress` FOREIGN KEY (`weekly_progress_id`) REFERENCES `phase_weekly_progress`      (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pwpi_item`     FOREIGN KEY (`target_item_id`)     REFERENCES `phase_monthly_target_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `daily_progress_logs` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `weekly_progress_id`    INT UNSIGNED    NOT NULL,
    `project_id`            INT             NOT NULL,
    `log_date`              DATE            NOT NULL,
    `labour_count`          INT             NOT NULL DEFAULT 0,
    `cement_bags`           INT             NOT NULL DEFAULT 0,
    `concrete_mt`           DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    `concrete_cft`          DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
    `materials_used`        INT             NOT NULL DEFAULT 0,
    `equipment_active`      INT             NOT NULL DEFAULT 0,
    `weather`               VARCHAR(100)    NULL DEFAULT NULL,
    `temperature_celsius`   DECIMAL(4,1)    NULL DEFAULT NULL,
    `safety_ok`             TINYINT(1)      NOT NULL DEFAULT 0,
    `qc_checked`            TINYINT(1)      NOT NULL DEFAULT 0,
    `site_remarks`          TEXT            NULL DEFAULT NULL,
    `created_by`            INT             NULL DEFAULT NULL,
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_dpl_day` (`weekly_progress_id`, `log_date`),
    CONSTRAINT `fk_dpl_week` FOREIGN KEY (`weekly_progress_id`) REFERENCES `phase_weekly_progress` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `daily_work_item_logs` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `daily_log_id`      INT UNSIGNED    NOT NULL,
    `target_item_id`    INT UNSIGNED    NOT NULL,
    `qty_done_today`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `unit`              VARCHAR(50)     NULL DEFAULT NULL,
    `remarks`           TEXT            NULL DEFAULT NULL,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dwil_log`  (`daily_log_id`),
    KEY `idx_dwil_item` (`target_item_id`),
    CONSTRAINT `fk_dwil_log`  FOREIGN KEY (`daily_log_id`)   REFERENCES `daily_progress_logs`        (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dwil_item` FOREIGN KEY (`target_item_id`) REFERENCES `phase_monthly_target_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `monthly_progress_reviews` (
    `id`                            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                    INT             NOT NULL,
    `review_month`                  DATE            NOT NULL,
    `review_month_number`           TINYINT         NOT NULL,
    `review_year`                   YEAR            NOT NULL,
    `planned_progress_pct`          DECIMAL(5,2)    NULL DEFAULT NULL,
    `actual_progress_pct`           DECIMAL(5,2)    NULL DEFAULT NULL,
    `delay_gap_pct`                 DECIMAL(5,2)    NULL DEFAULT NULL,
    `performance_score`             DECIMAL(5,2)    NULL DEFAULT NULL,
    `risk_status`                   ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    `material_delay_pct`            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `labour_shortage_pct`           DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `contractor_delay_pct`          DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `drawing_pending_pct`           DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `weather_issue_pct`             DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `payment_delay_pct`             DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `approval_delay_pct`            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `next_month_recovery_target`    DECIMAL(5,2)    NULL DEFAULT NULL,
    `extra_workload_needed`         DECIMAL(5,2)    NULL DEFAULT NULL,
    `critical_focal_area`           VARCHAR(200)    NULL DEFAULT NULL,
    `expected_revised_progress`     DECIMAL(5,2)    NULL DEFAULT NULL,
    `review_status`                 ENUM('Draft','Submitted','Management Approved') NOT NULL DEFAULT 'Draft',
    `submitted_by`                  INT             NULL DEFAULT NULL,
    `approved_by`                   INT             NULL DEFAULT NULL,
    `approved_at`                   DATETIME        NULL DEFAULT NULL,
    `created_at`                    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                    TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_mpr_month` (`project_id`, `review_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `monthly_phase_snapshots` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `review_id`         INT UNSIGNED    NOT NULL,
    `phase_id`          INT UNSIGNED    NOT NULL,
    `planned_pct`       DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `actual_pct`        DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `variance_pct`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `status`            ENUM('Achieved','At Risk','Delayed','Not Started') NOT NULL DEFAULT 'Not Started',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_mps_review` (`review_id`),
    CONSTRAINT `fk_mps_review` FOREIGN KEY (`review_id`) REFERENCES `monthly_progress_reviews` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mps_phase`  FOREIGN KEY (`phase_id`)  REFERENCES `project_phases`           (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `project_risks` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`        INT             NOT NULL,
    `phase_id`          INT UNSIGNED    NULL DEFAULT NULL,
    `risk_code`         VARCHAR(20)     NULL DEFAULT NULL,
    `issue_description` TEXT            NOT NULL,
    `category`          ENUM('Labour','Material','Design Quality','Approval Delay','Contractor Delay','Weather','Budget','Other') NOT NULL DEFAULT 'Other',
    `impact`            ENUM('Very High','High','Medium','Low') NOT NULL DEFAULT 'Medium',
    `likelihood`        ENUM('Very High','High','Medium','Low') NOT NULL DEFAULT 'Medium',
    `severity`          ENUM('Critical','High','Medium','Low')  NOT NULL DEFAULT 'Medium',
    `owner`             VARCHAR(150)    NULL DEFAULT NULL,
    `due_date`          DATE            NULL DEFAULT NULL,
    `status`            ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
    `created_by`        INT             NULL DEFAULT NULL,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pr_project` (`project_id`),
    KEY `idx_pr_phase`   (`phase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `project_delay_snapshots` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `snapshot_date`             DATE            NOT NULL,
    `total_delay_days`          INT             NOT NULL DEFAULT 0,
    `critical_risks_count`      INT             NOT NULL DEFAULT 0,
    `time_risk_level`           ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
    `budget_risk_amount`        DECIMAL(15,2)   NULL DEFAULT NULL,
    `handover_risk_level`       ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
    `original_handover_date`    DATE            NULL DEFAULT NULL,
    `forecast_handover_date`    DATE            NULL DEFAULT NULL,
    `material_delay_count`      INT             NOT NULL DEFAULT 0,
    `labour_shortage_count`     INT             NOT NULL DEFAULT 0,
    `contractor_delay_count`    INT             NOT NULL DEFAULT 0,
    `design_pending_count`      INT             NOT NULL DEFAULT 0,
    `approval_delay_count`      INT             NOT NULL DEFAULT 0,
    `weather_count`             INT             NOT NULL DEFAULT 0,
    `payment_delay_count`       INT             NOT NULL DEFAULT 0,
    `confidence_level_pct`      DECIMAL(5,2)    NULL DEFAULT NULL,
    `created_by`                INT             NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pds_project` (`project_id`),
    KEY `idx_pds_date`    (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `recovery_plans` (
    `id`                            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                    INT             NOT NULL,
    `review_id`                     INT UNSIGNED    NULL DEFAULT NULL,
    `recovery_month`                DATE            NOT NULL,
    `delay_carry_forward_pct`       DECIMAL(5,2)    NULL DEFAULT NULL,
    `recovery_target_pct`           DECIMAL(5,2)    NULL DEFAULT NULL,
    `projected_extra_progress_pct`  DECIMAL(5,2)    NULL DEFAULT NULL,
    `critical_focus_areas_count`    TINYINT         NOT NULL DEFAULT 0,
    `additional_labour`             INT             NOT NULL DEFAULT 0,
    `extra_budget_lac`              DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    `extra_working_hours`           INT             NOT NULL DEFAULT 0,
    `contractor_teams_added`        TINYINT         NOT NULL DEFAULT 0,
    `project_manager_notes`         TEXT            NULL DEFAULT NULL,
    `final_decision`                ENUM('Approve','Approve with Conditions','Hold') NULL DEFAULT NULL,
    `decision_conditions`           TEXT            NULL DEFAULT NULL,
    `decision_by`                   INT             NULL DEFAULT NULL,
    `decision_at`                   DATETIME        NULL DEFAULT NULL,
    `approval_status`               ENUM('Draft','PM Submitted','Management Review','Finance Clearance','Approved','Rejected') NOT NULL DEFAULT 'Draft',
    `pm_submitted_at`               DATETIME        NULL DEFAULT NULL,
    `management_reviewed_at`        DATETIME        NULL DEFAULT NULL,
    `finance_cleared_at`            DATETIME        NULL DEFAULT NULL,
    `created_by`                    INT             NULL DEFAULT NULL,
    `created_at`                    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                    TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rp_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `recovery_action_items` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `recovery_plan_id`  INT UNSIGNED    NOT NULL,
    `action_item`       TEXT            NOT NULL,
    `category`          ENUM('Labour','Procurement','MEP','Execution','Logistics','Design','Finance') NOT NULL DEFAULT 'Execution',
    `priority`          ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
    `owner`             VARCHAR(150)    NULL DEFAULT NULL,
    `start_date`        DATE            NULL DEFAULT NULL,
    `due_date`          DATE            NULL DEFAULT NULL,
    `expected_impact`   VARCHAR(100)    NULL DEFAULT NULL,
    `dependency`        VARCHAR(200)    NULL DEFAULT NULL,
    `status`            ENUM('Open','In Progress','Completed','Blocked') NOT NULL DEFAULT 'Open',
    `approval_status`   ENUM('Pending','Approved','Rejected')            NOT NULL DEFAULT 'Pending',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rai_plan` (`recovery_plan_id`),
    CONSTRAINT `fk_rai_plan` FOREIGN KEY (`recovery_plan_id`) REFERENCES `recovery_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- SECTION 3: FK for project_drawings.phase_id
-- !! SKIPPED: fk_pd_phase already exists on project_drawings
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 1;

-- Verify
SELECT 'Migration completed successfully.' AS message;
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
      'project_timelines','phase_template_defaults','project_phases',
      'phase_monthly_targets','phase_monthly_target_items',
      'phase_weekly_progress','phase_weekly_progress_items',
      'daily_progress_logs','daily_work_item_logs',
      'monthly_progress_reviews','monthly_phase_snapshots',
      'project_risks','project_delay_snapshots',
      'recovery_plans','recovery_action_items'
  )
ORDER BY TABLE_NAME;
