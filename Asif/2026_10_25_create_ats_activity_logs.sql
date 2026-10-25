-- ============================================================================
-- Migration: ATS Activity Logs / Audit Trail Table
-- Optimized for MySQL 8.0+ (InnoDB, UTF8MB4)
-- Date: 2026-10-25
-- ============================================================================

CREATE TABLE IF NOT EXISTS `ats_activity_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL COMMENT 'ID of user from session',
    `user_name` VARCHAR(150) NULL COMMENT 'Full name or username of actor',
    `user_email` VARCHAR(150) NULL COMMENT 'Email of actor',
    `action_type` VARCHAR(50) NOT NULL COMMENT 'e.g. page_view, candidate_create, inline_cell_edit, cv_upload, soft_delete, restore, force_delete, interview_schedule, call_logged, batch_csv_import, settings_change',
    `candidate_id` INT NULL COMMENT 'Associated ATS candidate ID if applicable',
    `candidate_name` VARCHAR(150) NULL COMMENT 'Snapshot of candidate name for fast search',
    `description` TEXT NOT NULL COMMENT 'Human readable summary of action',
    `details` LONGTEXT NULL COMMENT 'JSON payload for old vs new values or extra context',
    `ip_address` VARCHAR(45) NULL COMMENT 'Client IP address',
    `user_agent` VARCHAR(255) NULL COMMENT 'Client browser / user agent info',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ats_logs_user` (`user_id`),
    INDEX `idx_ats_logs_candidate` (`candidate_id`),
    INDEX `idx_ats_logs_action` (`action_type`),
    INDEX `idx_ats_logs_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
