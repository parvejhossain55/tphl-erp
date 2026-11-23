-- ==============================================================================
-- CED CLIENT SMS & EMAIL BROADCAST TABLES
-- Date: 2026-11-23
-- Description:
--   1. Create `ced_broadcast_campaigns` to track CED Head SMS/Email campaigns
--   2. Create `ced_broadcast_logs` to track per-recipient delivery & retry queue
-- ==============================================================================

CREATE TABLE IF NOT EXISTS `ced_broadcast_campaigns` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_title` VARCHAR(200) DEFAULT NULL,
    `channel` ENUM('sms', 'email', 'both') NOT NULL DEFAULT 'sms',
    `filter_summary` TEXT DEFAULT NULL COMMENT 'JSON snapshot of applied filters',
    `sms_message` TEXT DEFAULT NULL,
    `email_subject` VARCHAR(255) DEFAULT NULL,
    `email_body` MEDIUMTEXT DEFAULT NULL,
    `include_secondary_contacts` TINYINT(1) NOT NULL DEFAULT 0,
    `is_testing` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Dry-run/Testing mode (no real SMS/Email sent)',
    `total_clients` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_jobs` INT UNSIGNED NOT NULL DEFAULT 0,
    `sms_sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `sms_failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `email_sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `email_failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'processing', 'completed', 'partial_failed', 'failed') NOT NULL DEFAULT 'pending',
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_created_by_date` (`created_by`, `created_at`),
    KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ced_broadcast_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `campaign_id` INT UNSIGNED NOT NULL,
    `lead_id` INT DEFAULT NULL,
    `booking_id` INT DEFAULT NULL,
    `project_id` INT DEFAULT NULL,
    `client_name` VARCHAR(200) DEFAULT NULL,
    `flat_name` VARCHAR(100) DEFAULT NULL,
    `channel` ENUM('sms', 'email') NOT NULL,
    `recipient_contact` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `rendered_message` MEDIUMTEXT NOT NULL,
    `status` ENUM('pending', 'processing', 'sent', 'failed', 'skipped_testing') NOT NULL DEFAULT 'pending',
    `gateway_response` TEXT DEFAULT NULL,
    `sent_at` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_campaign_status` (`campaign_id`, `status`),
    KEY `idx_campaign_channel` (`campaign_id`, `channel`),
    KEY `idx_booking_id` (`booking_id`),
    KEY `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
