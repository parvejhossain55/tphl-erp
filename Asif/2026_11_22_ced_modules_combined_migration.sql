-- ==============================================================================
-- CED MODULES COMBINED MIGRATION SCRIPT
-- Date: 2026-11-22
-- Description:
--   1. Create `ced_project_assignment_history` for CED officer reassignment/history tracking
--   2. Backfill existing active assignments as baseline history
--   3. Create `project_emi_schedules` for project-level EMI setup
--   4. Create `project_emi_schedule_items` for per-installment activation & due dates
-- ==============================================================================

-- 1. Create CED Project Assignment History Table
CREATE TABLE IF NOT EXISTS `ced_project_assignment_history` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `project_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `assigned_by` INT NOT NULL,
    `assigned_from` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `assigned_to` DATETIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_project_dates` (`project_id`, `assigned_from`, `assigned_to`),
    KEY `idx_user_dates` (`user_id`, `assigned_from`, `assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Backfill Currently Active Assignments as Baseline History
INSERT INTO `ced_project_assignment_history` (`project_id`, `user_id`, `assigned_by`, `assigned_from`, `assigned_to`)
SELECT cpa.project_id, cpa.user_id, cpa.assigned_by, '2000-01-01 00:00:00', NULL
FROM `ced_project_assignments` cpa
INNER JOIN (
    SELECT project_id, MAX(id) AS max_id
    FROM `ced_project_assignments`
    WHERE status = 'active'
    GROUP BY project_id
) latest ON latest.max_id = cpa.id
WHERE NOT EXISTS (
    SELECT 1 FROM `ced_project_assignment_history` h
    WHERE h.project_id = cpa.project_id AND h.assigned_to IS NULL
);

-- 3. Create Project EMI Schedules Configuration Table
CREATE TABLE IF NOT EXISTS `project_emi_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `emi_start_date` DATE DEFAULT NULL COMMENT 'First EMI start date',
  `total_emi_no` INT DEFAULT 36 COMMENT 'Total EMI count (months)',
  `emi_due_day` TINYINT UNSIGNED DEFAULT 10 COMMENT 'Monthly due day (1-31)',
  `default_emi_amount` DECIMAL(12,2) DEFAULT NULL COMMENT 'Default monthly EMI amount (optional)',
  `remarks` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 4. Create Project EMI Schedule Items (Per-Installment Due Dates & Activation)
CREATE TABLE IF NOT EXISTS `project_emi_schedule_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT NOT NULL,
  `emi_no` INT NOT NULL,
  `due_date` DATE NOT NULL,
  `emi_amount` DECIMAL(12,2) DEFAULT NULL,
  `remarks` VARCHAR(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Active, 0=Deactive',
  `updated_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_project_emi_no` (`project_id`, `emi_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
