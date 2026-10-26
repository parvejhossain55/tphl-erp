-- Migration: Create CED Polls, Options, and Votes tables
-- Date: 2026-10-26

CREATE TABLE IF NOT EXISTS `ced_polls` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NULL COMMENT 'Specific project id',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL COMMENT 'Uploaded image path e.g. uploads/polls/filename.jpg',
  `poll_type` ENUM('single_choice', 'multiple_choice') NOT NULL DEFAULT 'single_choice',
  `status` ENUM('draft', 'active', 'closed') NOT NULL DEFAULT 'active',
  `start_date` DATE NULL,
  `end_date` DATE NULL,
  `created_by` INT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ced_polls_project` (`project_id`),
  INDEX `idx_ced_polls_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ced_poll_options` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `poll_id` INT UNSIGNED NOT NULL,
  `option_text` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ced_poll_options_poll` (`poll_id`),
  CONSTRAINT `fk_ced_poll_options_poll` FOREIGN KEY (`poll_id`) REFERENCES `ced_polls` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `ced_poll_votes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `poll_id` INT UNSIGNED NOT NULL,
  `option_id` INT UNSIGNED NOT NULL,
  `client_id` INT UNSIGNED NOT NULL COMMENT 'Customer ID',
  `remarks` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ced_poll_votes_poll` (`poll_id`),
  INDEX `idx_ced_poll_votes_client` (`client_id`),
  UNIQUE KEY `uk_client_poll` (`poll_id`, `client_id`),
  CONSTRAINT `fk_ced_poll_votes_poll` FOREIGN KEY (`poll_id`) REFERENCES `ced_polls` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ced_poll_votes_option` FOREIGN KEY (`option_id`) REFERENCES `ced_poll_options` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
