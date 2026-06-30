-- ================================================================
-- Labor Requisition Module — Database Migration
-- Run this SQL in phpMyAdmin or MySQL CLI
-- Generated: 2026-06-29
-- ================================================================

-- Add new columns to existing con_labor_requisitions table
-- (Run this ONLY if you already created the base table from the plan)

ALTER TABLE `con_labor_requisitions`
  -- QC Approval columns
  ADD COLUMN IF NOT EXISTS `qc_approved_by`             int(11)         DEFAULT NULL COMMENT 'QC user id'        AFTER `pm_note`,
  ADD COLUMN IF NOT EXISTS `qc_approved_at`             datetime        DEFAULT NULL                             AFTER `qc_approved_by`,
  ADD COLUMN IF NOT EXISTS `qc_note`                    text            DEFAULT NULL                             AFTER `qc_approved_at`,

  -- PM Payment Approval columns
  ADD COLUMN IF NOT EXISTS `pm_payment_approved_by`     int(11)         DEFAULT NULL COMMENT 'PM second approval' AFTER `qc_note`,
  ADD COLUMN IF NOT EXISTS `pm_payment_approved_at`     datetime        DEFAULT NULL                             AFTER `pm_payment_approved_by`,
  ADD COLUMN IF NOT EXISTS `pm_payment_note`            text            DEFAULT NULL                             AFTER `pm_payment_approved_at`,

  -- Update status ENUM to include new statuses
  MODIFY COLUMN `status` ENUM(
    'pending',
    'pm_approved',
    'rejected',
    'work_completed',
    'payment_requested',
    'qc_approved',
    'pm_payment_approved',
    'management_approved',
    'paid'
  ) NOT NULL DEFAULT 'pending';

-- Add indexes for new columns
ALTER TABLE `con_labor_requisitions`
  ADD INDEX IF NOT EXISTS `idx_clr_qc_by` (`qc_approved_by`),
  ADD INDEX IF NOT EXISTS `idx_clr_pm_pay` (`pm_payment_approved_by`);

-- ================================================================
-- If you haven't created the table yet, run this full CREATE:
-- ================================================================
/*
CREATE TABLE IF NOT EXISTS `con_labor_requisitions` (
  `id`                       int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requisition_no`           varchar(30)      DEFAULT NULL,
  `project_id`               int(11)          NOT NULL,
  `phase_id`                 int(10) unsigned DEFAULT NULL,
  `phase_target_id`          int(10) unsigned DEFAULT NULL,
  `requested_by`             int(11)          NOT NULL,
  `requisition_date`         date             NOT NULL,
  `work_date_from`           date             DEFAULT NULL,
  `work_date_to`             date             DEFAULT NULL,
  `labor_type`               enum('General','Skilled','Unskilled','Mason','Helper','Supervisor','Other') DEFAULT 'General',
  `labor_count`              int(11)          NOT NULL DEFAULT 1,
  `per_labor_rate`           decimal(10,2)    NOT NULL DEFAULT 0.00,
  `working_days`             decimal(5,2)     NOT NULL DEFAULT 1,
  `total_amount`             decimal(15,2)    GENERATED ALWAYS AS (labor_count * per_labor_rate * working_days) STORED,
  `purpose`                  text             NOT NULL,
  `remarks`                  text             DEFAULT NULL,
  `attachment`               varchar(255)     DEFAULT NULL,

  -- PM Approval
  `pm_approved_by`           int(11)          DEFAULT NULL,
  `pm_approved_at`           datetime         DEFAULT NULL,
  `pm_note`                  text             DEFAULT NULL,

  -- QC Approval
  `qc_approved_by`           int(11)          DEFAULT NULL,
  `qc_approved_at`           datetime         DEFAULT NULL,
  `qc_note`                  text             DEFAULT NULL,

  -- Payment Request
  `payment_requested_by`     int(11)          DEFAULT NULL,
  `payment_requested_at`     datetime         DEFAULT NULL,

  -- PM Payment Approval
  `pm_payment_approved_by`   int(11)          DEFAULT NULL,
  `pm_payment_approved_at`   datetime         DEFAULT NULL,
  `pm_payment_note`          text             DEFAULT NULL,

  -- Management Approval
  `management_approved_by`   int(11)          DEFAULT NULL,
  `management_approved_at`   datetime         DEFAULT NULL,
  `management_note`          text             DEFAULT NULL,
  `approved_amount`          decimal(15,2)    DEFAULT NULL,

  -- Payment
  `paid_amount`              decimal(15,2)    DEFAULT NULL,
  `paid_by`                  int(11)          DEFAULT NULL,
  `payment_date`             date             DEFAULT NULL,
  `payment_method`           enum('Cash','Bank Transfer','Mobile Banking','Cheque') DEFAULT NULL,
  `payment_reference`        varchar(100)     DEFAULT NULL,
  `payment_note`             text             DEFAULT NULL,

  -- Status
  `status` enum(
    'pending',
    'pm_approved',
    'rejected',
    'work_completed',
    'payment_requested',
    'qc_approved',
    'pm_payment_approved',
    'management_approved',
    'paid'
  ) NOT NULL DEFAULT 'pending',

  `rejection_reason`         text             DEFAULT NULL,
  `created_by`               int(11)          NOT NULL,
  `created_at`               timestamp        NOT NULL DEFAULT current_timestamp(),
  `updated_at`               timestamp        NULL     DEFAULT NULL ON UPDATE current_timestamp(),

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_requisition_no` (`requisition_no`),
  KEY `idx_clr_project`          (`project_id`),
  KEY `idx_clr_phase`            (`phase_id`),
  KEY `idx_clr_target`           (`phase_target_id`),
  KEY `idx_clr_requested`        (`requested_by`),
  KEY `idx_clr_status`           (`status`),
  KEY `idx_clr_qc_by`            (`qc_approved_by`),
  KEY `idx_clr_pm_pay`           (`pm_payment_approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Site Engineer labor requisition and payment tracking';
*/
