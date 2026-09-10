-- =======================================================================
-- Database Migration: Create hr_signatures Table
-- Created Date: 2026-09-10
-- Purpose: Support multiple official HR/Executive signatures with name,
--          designation, transparent signature image path, and default status.
-- =======================================================================

CREATE TABLE IF NOT EXISTS `hr_signatures` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `signatory_name` VARCHAR(150) NOT NULL COMMENT 'Full name of the signatory person',
    `signatory_title` VARCHAR(150) NOT NULL COMMENT 'Designation or title of the signatory',
    `signature_path` VARCHAR(255) NOT NULL COMMENT 'Relative file path to transparent PNG signature',
    `is_default` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 if default signatory for new letters, else 0',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_hr_sig_status` (`status`),
    INDEX `idx_hr_sig_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

