-- =============================================================================
-- PHASE-WISE CONSTRUCTION MANAGEMENT — MIGRATION SQL
-- Version   : 1.0.0
-- Date      : 2026-05-24
-- Author    : easy-nirmanX Dev Team
-- Run on    : Live Server (tphl_db)
-- Safe      : YES — No existing table is dropped or altered destructively
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- =============================================================================
-- SECTION 1: ALTER EXISTING TABLES
-- =============================================================================

-- 1a. projects — add workflow_version to distinguish old vs new system
ALTER TABLE `projects`
    ADD COLUMN IF NOT EXISTS `workflow_version`
        ENUM('legacy','phase_based') NOT NULL DEFAULT 'legacy'
        COMMENT 'legacy=old target_fixed system | phase_based=new 7-module lifecycle'
        AFTER `name`;

-- 1b. work_types — add phase suggestion column
ALTER TABLE `work_types`
    ADD COLUMN IF NOT EXISTS `default_phase_name`
        VARCHAR(150) NULL DEFAULT NULL
        COMMENT 'Suggested construction phase for this work type'
        AFTER `design_type_id`;

-- 1c. project_drawings — add optional phase link (INT UNSIGNED to match project_phases.id)
ALTER TABLE `project_drawings`
    ADD COLUMN IF NOT EXISTS `phase_id`
        INT UNSIGNED NULL DEFAULT NULL
        COMMENT 'Optional: which construction phase this drawing belongs to'
        AFTER `project_id`;

-- =============================================================================
-- SECTION 2: CREATE NEW TABLES
-- =============================================================================

-- ----------------------------------------------------------------------------
-- TABLE 1: project_timelines
-- Module 1: Project Timeline Setup
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_timelines` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `project_code`              VARCHAR(50)     NULL DEFAULT NULL,
    `project_type`              VARCHAR(100)    NULL DEFAULT NULL   COMMENT 'Residential / Commercial / Industrial',
    `project_category`          VARCHAR(100)    NULL DEFAULT NULL   COMMENT 'High Rise / Low Rise / Industrial Shed',
    `handover_months`           INT             NOT NULL            COMMENT 'Total timeline in months e.g. 48',
    `construction_months`       INT             NOT NULL            COMMENT 'Construction period in months e.g. 32',
    `post_construction_months`  INT             NOT NULL DEFAULT 6  COMMENT 'Post construction months e.g. 6',
    -- pre_construction_months = handover_months - construction_months - post_construction_months (computed)
    `project_start_date`        DATE            NOT NULL            COMMENT 'Project kickoff date',
    `construction_start_date`   DATE            NOT NULL            COMMENT 'On-site construction start',
    `construction_end_date`     DATE            NOT NULL            COMMENT 'Construction completion date',
    `expected_handover_date`    DATE            NOT NULL            COMMENT 'Final handover to client',
    `current_status`            ENUM(
                                    'Planning',
                                    'Pre-Construction',
                                    'Construction',
                                    'Post-Construction',
                                    'Handover',
                                    'Completed'
                                )               NOT NULL DEFAULT 'Planning',
    `description`               TEXT            NULL DEFAULT NULL,
    `created_by`                INT             NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pt_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 1: Project timeline setup — one row per project';

-- ----------------------------------------------------------------------------
-- TABLE 2: phase_template_defaults
-- Global 6-phase default template (seed data below)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `phase_template_defaults` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `phase_name`                VARCHAR(150)    NOT NULL,
    `phase_order`               TINYINT         NOT NULL,
    `default_weight_percentage` DECIMAL(5,2)    NOT NULL COMMENT 'Must sum to 100 across all rows',
    `description`               TEXT            NULL DEFAULT NULL,
    `is_active`                 TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_phase_order` (`phase_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Global phase template — cloned into project_phases on first access';

-- Seed data: 6 default phases totaling 100%
INSERT IGNORE INTO `phase_template_defaults`
    (`phase_name`, `phase_order`, `default_weight_percentage`) VALUES
    ('Foundation & Piling',     1, 12.00),
    ('Basement & Substructure', 2, 11.00),
    ('Super Structure',         3, 28.00),
    ('Masonry & Partition',     4, 12.00),
    ('MEP & Utilities',         5, 18.00),
    ('Finishing & Handover',    6, 19.00);

-- ----------------------------------------------------------------------------
-- TABLE 3: project_phases
-- Module 2: Phase-wise Construction Plan (per project, cloned from template)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_phases` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`            INT             NOT NULL,
    `phase_name`            VARCHAR(150)    NOT NULL,
    `phase_order`           TINYINT         NOT NULL DEFAULT 1,
    `weight_percentage`     DECIMAL(5,2)    NOT NULL DEFAULT 0.00 COMMENT 'All phases must sum to 100%',
    `planned_start_date`    DATE            NULL DEFAULT NULL,
    `planned_end_date`      DATE            NULL DEFAULT NULL,
    `actual_start_date`     DATE            NULL DEFAULT NULL,
    `actual_end_date`       DATE            NULL DEFAULT NULL,
    `planned_progress`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00 COMMENT '% within this phase planned',
    `actual_progress`       DECIMAL(5,2)    NOT NULL DEFAULT 0.00 COMMENT '% within this phase achieved',
    `status`                ENUM(
                                'Not Started',
                                'In Progress',
                                'Completed',
                                'At Risk',
                                'Delayed'
                            )               NOT NULL DEFAULT 'Not Started',
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pp_project` (`project_id`),
    KEY `idx_pp_order` (`project_id`, `phase_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 2: Per-project phase definitions cloned from phase_template_defaults';

-- ----------------------------------------------------------------------------
-- TABLE 4: phase_monthly_targets
-- Module 3: Monthly Target Planner — header per project+phase+month
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `phase_monthly_targets` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `phase_id`                  INT UNSIGNED    NOT NULL,
    `target_month`              DATE            NOT NULL            COMMENT 'First day of month e.g. 2026-08-01',
    `target_month_number`       TINYINT         NOT NULL            COMMENT '1-12',
    `target_year`               YEAR            NOT NULL,
    `total_target_weight_pct`   DECIMAL(5,2)    NULL DEFAULT NULL   COMMENT '% of total project this month contributes',
    `planning_status`           ENUM(
                                    'Draft',
                                    'In Review',
                                    'Approved'
                                )               NOT NULL DEFAULT 'Draft',
    `responsible_team`          VARCHAR(150)    NULL DEFAULT NULL,
    `created_by`                INT             NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pmt_month` (`project_id`, `phase_id`, `target_month`),
    KEY `idx_pmt_phase` (`phase_id`),
    CONSTRAINT `fk_pmt_phase` FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 3: Monthly target plan header — one per project+phase+month';

-- ----------------------------------------------------------------------------
-- TABLE 5: phase_monthly_target_items
-- Module 3: Individual work items under a monthly target
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `phase_monthly_target_items` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `target_id`                 INT UNSIGNED    NOT NULL,
    `work_type_id`              BIGINT UNSIGNED NULL DEFAULT NULL   COMMENT 'Optional ref to work_types for categorization',
    `work_item_name`            VARCHAR(200)    NOT NULL,
    `unit`                      VARCHAR(50)     NULL DEFAULT NULL   COMMENT 'CFT / MT / SFT / Bags / Nos / Days',
    `monthly_target_qty`        DECIMAL(12,2)   NULL DEFAULT NULL,
    `planned_progress_pct`      DECIMAL(5,2)    NULL DEFAULT NULL   COMMENT 'Planned completion % by month end',
    `start_date`                DATE            NULL DEFAULT NULL,
    `end_date`                  DATE            NULL DEFAULT NULL,
    `responsible_team`          VARCHAR(150)    NULL DEFAULT NULL,
    `priority`                  ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
    `status`                    ENUM(
                                    'Draft',
                                    'Ready',
                                    'Approved',
                                    'At Risk',
                                    'Critical'
                                )               NOT NULL DEFAULT 'Draft',
    `week_1_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `week_2_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `week_3_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `week_4_qty`                DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `sort_order`                INT             NOT NULL DEFAULT 0,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pmti_target` (`target_id`),
    CONSTRAINT `fk_pmti_target` FOREIGN KEY (`target_id`) REFERENCES `phase_monthly_targets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 3: Work items under each monthly target plan';

-- ----------------------------------------------------------------------------
-- TABLE 6: phase_weekly_progress
-- Module 4: Weekly Progress Entry — header per week
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `phase_weekly_progress` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `phase_id`                  INT UNSIGNED    NOT NULL,
    `target_id`                 INT UNSIGNED    NOT NULL            COMMENT 'FK phase_monthly_targets',
    `week_number`               TINYINT         NOT NULL            COMMENT '1, 2, 3, or 4 within the month',
    `week_start_date`           DATE            NOT NULL,
    `week_end_date`             DATE            NOT NULL,
    `overall_progress_pct`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00 COMMENT 'Overall % for this week',
    `total_planned_qty`         DECIMAL(12,2)   NULL DEFAULT NULL,
    `total_achieved_qty`        DECIMAL(12,2)   NULL DEFAULT NULL,
    -- Resource summary for the week
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
    `entry_status`              ENUM(
                                    'Draft',
                                    'Submitted',
                                    'Approved'
                                )               NOT NULL DEFAULT 'Draft',
    `submitted_by`              INT             NULL DEFAULT NULL,
    `submitted_at`              DATETIME        NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_pwp_week` (`project_id`, `phase_id`, `target_id`, `week_number`),
    KEY `idx_pwp_target` (`target_id`),
    CONSTRAINT `fk_pwp_phase`   FOREIGN KEY (`phase_id`)  REFERENCES `project_phases`         (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_pwp_target`  FOREIGN KEY (`target_id`) REFERENCES `phase_monthly_targets`  (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 4: Weekly progress entry header — one per week per monthly target';

-- ----------------------------------------------------------------------------
-- TABLE 7: phase_weekly_progress_items
-- Module 4: Per-item progress within a week
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `phase_weekly_progress_items` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `weekly_progress_id`    INT UNSIGNED    NOT NULL,
    `target_item_id`        INT UNSIGNED    NOT NULL,
    `planned_qty`           DECIMAL(12,2)   NULL DEFAULT NULL,
    `achieved_qty`          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `today_progress_qty`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00  COMMENT 'Qty done on latest entry day',
    `percent_complete`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `responsible_team`      VARCHAR(150)    NULL DEFAULT NULL,
    `status`                ENUM(
                                'Not Started',
                                'In Progress',
                                'Completed',
                                'Delayed',
                                'Pending QA'
                            )               NOT NULL DEFAULT 'Not Started',
    `has_issue_flag`        TINYINT(1)      NOT NULL DEFAULT 0,
    `photo_path`            VARCHAR(500)    NULL DEFAULT NULL,
    `remarks_action`        TEXT            NULL DEFAULT NULL,
    `created_at`            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pwpi_weekly` (`weekly_progress_id`),
    KEY `idx_pwpi_item`   (`target_item_id`),
    CONSTRAINT `fk_pwpi_progress` FOREIGN KEY (`weekly_progress_id`) REFERENCES `phase_weekly_progress`      (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pwpi_item`     FOREIGN KEY (`target_item_id`)     REFERENCES `phase_monthly_target_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 4: Item-level weekly progress update';

-- ----------------------------------------------------------------------------
-- TABLE 8: daily_progress_logs
-- Module 4: Daily Work Report — one row per day per weekly entry
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_progress_logs` (
    `id`                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `weekly_progress_id`    INT UNSIGNED    NOT NULL,
    `project_id`            INT             NOT NULL,
    `log_date`              DATE            NOT NULL                COMMENT 'Specific site day e.g. 2026-08-16',
    -- Daily site resources
    `labour_count`          INT             NOT NULL DEFAULT 0      COMMENT 'Workers on site',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 4: Daily work report — one row per calendar day within a week';

-- ----------------------------------------------------------------------------
-- TABLE 9: daily_work_item_logs
-- Module 4: Per-item quantity done on a specific day
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_work_item_logs` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `daily_log_id`      INT UNSIGNED    NOT NULL,
    `target_item_id`    INT UNSIGNED    NOT NULL,
    `qty_done_today`    DECIMAL(12,2)   NOT NULL DEFAULT 0.00      COMMENT 'Qty done in item unit (CFT / MT / SFT...)',
    `unit`              VARCHAR(50)     NULL DEFAULT NULL,
    `remarks`           TEXT            NULL DEFAULT NULL,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_dwil_log`  (`daily_log_id`),
    KEY `idx_dwil_item` (`target_item_id`),
    CONSTRAINT `fk_dwil_log`  FOREIGN KEY (`daily_log_id`)     REFERENCES `daily_progress_logs`        (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_dwil_item` FOREIGN KEY (`target_item_id`)   REFERENCES `phase_monthly_target_items` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 4: Daily qty log per work item';

-- ----------------------------------------------------------------------------
-- TABLE 10: monthly_progress_reviews
-- Module 5: Monthly Progress Review — one per project per month
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `monthly_progress_reviews` (
    `id`                            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                    INT             NOT NULL,
    `review_month`                  DATE            NOT NULL        COMMENT 'First day of review month e.g. 2026-08-01',
    `review_month_number`           TINYINT         NOT NULL,
    `review_year`                   YEAR            NOT NULL,
    -- Overall KPIs
    `planned_progress_pct`          DECIMAL(5,2)    NULL DEFAULT NULL COMMENT 'Cumulative planned % of total project',
    `actual_progress_pct`           DECIMAL(5,2)    NULL DEFAULT NULL COMMENT 'Cumulative actual % of total project',
    `delay_gap_pct`                 DECIMAL(5,2)    NULL DEFAULT NULL COMMENT 'planned - actual (negative = behind)',
    `performance_score`             DECIMAL(5,2)    NULL DEFAULT NULL COMMENT '0-100 score',
    `risk_status`                   ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    -- Delay reason breakdown (% of delay attributed to each cause)
    `material_delay_pct`            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `labour_shortage_pct`           DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `contractor_delay_pct`          DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `drawing_pending_pct`           DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `weather_issue_pct`             DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `payment_delay_pct`             DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `approval_delay_pct`            DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    -- Recovery summary for next month
    `next_month_recovery_target`    DECIMAL(5,2)    NULL DEFAULT NULL COMMENT '+% needed next month',
    `extra_workload_needed`         DECIMAL(5,2)    NULL DEFAULT NULL,
    `critical_focal_area`           VARCHAR(200)    NULL DEFAULT NULL,
    `expected_revised_progress`     DECIMAL(5,2)    NULL DEFAULT NULL,
    -- Status & Approval
    `review_status`                 ENUM(
                                        'Draft',
                                        'Submitted',
                                        'Management Approved'
                                    )               NOT NULL DEFAULT 'Draft',
    `submitted_by`                  INT             NULL DEFAULT NULL,
    `approved_by`                   INT             NULL DEFAULT NULL,
    `approved_at`                   DATETIME        NULL DEFAULT NULL,
    `created_at`                    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                    TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_mpr_month` (`project_id`, `review_month`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 5: Monthly progress review — KPIs, delays, recovery summary';

-- ----------------------------------------------------------------------------
-- TABLE 11: monthly_phase_snapshots
-- Module 5: Per-phase performance snapshot within a monthly review
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `monthly_phase_snapshots` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `review_id`         INT UNSIGNED    NOT NULL,
    `phase_id`          INT UNSIGNED    NOT NULL,
    `planned_pct`       DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `actual_pct`        DECIMAL(5,2)    NOT NULL DEFAULT 0.00,
    `variance_pct`      DECIMAL(5,2)    NOT NULL DEFAULT 0.00       COMMENT 'planned - actual',
    `status`            ENUM(
                            'Achieved',
                            'At Risk',
                            'Delayed',
                            'Not Started'
                        )               NOT NULL DEFAULT 'Not Started',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_mps_review` (`review_id`),
    CONSTRAINT `fk_mps_review` FOREIGN KEY (`review_id`) REFERENCES `monthly_progress_reviews` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mps_phase`  FOREIGN KEY (`phase_id`)  REFERENCES `project_phases`           (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 5: Phase-level snapshot per monthly review';

-- ----------------------------------------------------------------------------
-- TABLE 12: project_risks
-- Module 6: Delay & Risk Dashboard — risk register
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_risks` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`        INT             NOT NULL,
    `phase_id`          INT UNSIGNED    NULL DEFAULT NULL,
    `risk_code`         VARCHAR(20)     NULL DEFAULT NULL            COMMENT 'Auto: R-101, R-102...',
    `issue_description` TEXT            NOT NULL,
    `category`          ENUM(
                            'Labour',
                            'Material',
                            'Design Quality',
                            'Approval Delay',
                            'Contractor Delay',
                            'Weather',
                            'Budget',
                            'Other'
                        )               NOT NULL DEFAULT 'Other',
    `impact`            ENUM('Very High','High','Medium','Low')      NOT NULL DEFAULT 'Medium',
    `likelihood`        ENUM('Very High','High','Medium','Low')      NOT NULL DEFAULT 'Medium',
    `severity`          ENUM('Critical','High','Medium','Low')       NOT NULL DEFAULT 'Medium',
    `owner`             VARCHAR(150)    NULL DEFAULT NULL,
    `due_date`          DATE            NULL DEFAULT NULL,
    `status`            ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
    `created_by`        INT             NULL DEFAULT NULL,
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pr_project` (`project_id`),
    KEY `idx_pr_phase`   (`phase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 6: Risk register — tracks all project risks with heatmap data';

-- ----------------------------------------------------------------------------
-- TABLE 13: project_delay_snapshots
-- Module 6: Delay source summary snapshot (periodic, e.g. weekly)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_delay_snapshots` (
    `id`                        INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                INT             NOT NULL,
    `snapshot_date`             DATE            NOT NULL,
    `total_delay_days`          INT             NOT NULL DEFAULT 0,
    `critical_risks_count`      INT             NOT NULL DEFAULT 0,
    `time_risk_level`           ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
    `budget_risk_amount`        DECIMAL(15,2)   NULL DEFAULT NULL    COMMENT 'Forecasted budget overrun amount',
    `handover_risk_level`       ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Low',
    `original_handover_date`    DATE            NULL DEFAULT NULL,
    `forecast_handover_date`    DATE            NULL DEFAULT NULL,
    -- Delay source counts
    `material_delay_count`      INT             NOT NULL DEFAULT 0,
    `labour_shortage_count`     INT             NOT NULL DEFAULT 0,
    `contractor_delay_count`    INT             NOT NULL DEFAULT 0,
    `design_pending_count`      INT             NOT NULL DEFAULT 0,
    `approval_delay_count`      INT             NOT NULL DEFAULT 0,
    `weather_count`             INT             NOT NULL DEFAULT 0,
    `payment_delay_count`       INT             NOT NULL DEFAULT 0,
    `confidence_level_pct`      DECIMAL(5,2)    NULL DEFAULT NULL    COMMENT 'Forecast confidence % e.g. 62%',
    `created_by`                INT             NULL DEFAULT NULL,
    `created_at`                TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_pds_project` (`project_id`),
    KEY `idx_pds_date`    (`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 6: Periodic delay source analytics snapshot';

-- ----------------------------------------------------------------------------
-- TABLE 14: recovery_plans
-- Module 7: Management Action / Recovery Plan
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recovery_plans` (
    `id`                            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `project_id`                    INT             NOT NULL,
    `review_id`                     INT UNSIGNED    NULL DEFAULT NULL COMMENT 'Linked monthly review',
    `recovery_month`                DATE            NOT NULL            COMMENT 'Month this plan targets',
    `delay_carry_forward_pct`       DECIMAL(5,2)    NULL DEFAULT NULL   COMMENT '% delay from previous month',
    `recovery_target_pct`           DECIMAL(5,2)    NULL DEFAULT NULL   COMMENT '+% to recover this month',
    `projected_extra_progress_pct`  DECIMAL(5,2)    NULL DEFAULT NULL   COMMENT 'Total extra % achievable',
    `critical_focus_areas_count`    TINYINT         NOT NULL DEFAULT 0,
    -- Resource adjustment
    `additional_labour`             INT             NOT NULL DEFAULT 0,
    `extra_budget_lac`              DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT 'In Lacs',
    `extra_working_hours`           INT             NOT NULL DEFAULT 0,
    `contractor_teams_added`        TINYINT         NOT NULL DEFAULT 0,
    -- Notes & decision
    `project_manager_notes`         TEXT            NULL DEFAULT NULL,
    `final_decision`                ENUM(
                                        'Approve',
                                        'Approve with Conditions',
                                        'Hold'
                                    )               NULL DEFAULT NULL,
    `decision_conditions`           TEXT            NULL DEFAULT NULL,
    `decision_by`                   INT             NULL DEFAULT NULL,
    `decision_at`                   DATETIME        NULL DEFAULT NULL,
    -- Approval workflow
    `approval_status`               ENUM(
                                        'Draft',
                                        'PM Submitted',
                                        'Management Review',
                                        'Finance Clearance',
                                        'Approved',
                                        'Rejected'
                                    )               NOT NULL DEFAULT 'Draft',
    `pm_submitted_at`               DATETIME        NULL DEFAULT NULL,
    `management_reviewed_at`        DATETIME        NULL DEFAULT NULL,
    `finance_cleared_at`            DATETIME        NULL DEFAULT NULL,
    `created_by`                    INT             NULL DEFAULT NULL,
    `created_at`                    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`                    TIMESTAMP       NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rp_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 7: Recovery plan — management action plan when behind schedule';

-- ----------------------------------------------------------------------------
-- TABLE 15: recovery_action_items
-- Module 7: Individual action items within a recovery plan
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `recovery_action_items` (
    `id`                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `recovery_plan_id`  INT UNSIGNED    NOT NULL,
    `action_item`       TEXT            NOT NULL,
    `category`          ENUM(
                            'Labour',
                            'Procurement',
                            'MEP',
                            'Execution',
                            'Logistics',
                            'Design',
                            'Finance'
                        )               NOT NULL DEFAULT 'Execution',
    `priority`          ENUM('High','Medium','Low') NOT NULL DEFAULT 'Medium',
    `owner`             VARCHAR(150)    NULL DEFAULT NULL,
    `start_date`        DATE            NULL DEFAULT NULL,
    `due_date`          DATE            NULL DEFAULT NULL,
    `expected_impact`   VARCHAR(100)    NULL DEFAULT NULL             COMMENT 'e.g. +5% Progress',
    `dependency`        VARCHAR(200)    NULL DEFAULT NULL,
    `status`            ENUM('Open','In Progress','Completed','Blocked') NOT NULL DEFAULT 'Open',
    `approval_status`   ENUM('Pending','Approved','Rejected')          NOT NULL DEFAULT 'Pending',
    `created_at`        TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rai_plan` (`recovery_plan_id`),
    CONSTRAINT `fk_rai_plan` FOREIGN KEY (`recovery_plan_id`) REFERENCES `recovery_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Module 7: Action items within a recovery plan';

-- =============================================================================
-- SECTION 3: Add FK for project_drawings.phase_id
-- Note: column already added in Section 1 as INT UNSIGNED — types now match
-- =============================================================================
ALTER TABLE `project_drawings`
    ADD CONSTRAINT `fk_pd_phase`
        FOREIGN KEY (`phase_id`) REFERENCES `project_phases` (`id`) ON DELETE SET NULL;

-- =============================================================================
-- DONE
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- Verify: count new tables
SELECT 'Migration completed. New tables created:' AS message;
SELECT TABLE_NAME, TABLE_ROWS, CREATE_TIME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
      'project_timelines', 'phase_template_defaults', 'project_phases',
      'phase_monthly_targets', 'phase_monthly_target_items',
      'phase_weekly_progress', 'phase_weekly_progress_items',
      'daily_progress_logs', 'daily_work_item_logs',
      'monthly_progress_reviews', 'monthly_phase_snapshots',
      'project_risks', 'project_delay_snapshots',
      'recovery_plans', 'recovery_action_items'
  )
ORDER BY TABLE_NAME;

