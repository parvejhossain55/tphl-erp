-- ==============================================================================
-- CLIENT FEEDBACK REPORT TABLE
-- Date: 2026-11-24
-- Description:
--   Store reception QR / website client feedback (5 rating areas + optional
--   contact/comment + Management follow-up status).
-- ==============================================================================

CREATE TABLE IF NOT EXISTS `client_feedbacks` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `behaviour_attitude` ENUM('excellent', 'good', 'average', 'poor') NOT NULL,
    `professionalism_courtesy` ENUM('excellent', 'good', 'average', 'poor') NOT NULL,
    `response_time_speed` ENUM('excellent', 'good', 'average', 'poor') NOT NULL,
    `office_environment` ENUM('excellent', 'good', 'average', 'poor') NOT NULL,
    `overall_experience` ENUM('excellent', 'good', 'average', 'poor') NOT NULL,
    `full_name` VARCHAR(150) DEFAULT NULL,
    `mobile_number` VARCHAR(30) DEFAULT NULL,
    `comments` TEXT DEFAULT NULL,
    `may_contact` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1=Yes, 0=No',
    `source` VARCHAR(50) NOT NULL DEFAULT 'qr_web',
    `followup_status` ENUM('pending', 'reviewed', 'contacted', 'resolved') NOT NULL DEFAULT 'pending',
    `followup_note` TEXT DEFAULT NULL,
    `followed_up_by` INT DEFAULT NULL,
    `followed_up_at` DATETIME DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_overall_experience` (`overall_experience`),
    KEY `idx_may_contact` (`may_contact`),
    KEY `idx_followup_status` (`followup_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
