-- ============================================================================
-- Simple Production Migration SQL for phpMyAdmin
-- Date: 2026-11-11
-- Author: Asif Ikbal
-- Description: Adds new columns to `tasks` and creates `task_activities` table
-- ============================================================================

-- 1. Add new columns to `tasks` table
ALTER TABLE `tasks`
ADD COLUMN `expected_output` TEXT NULL AFTER `title`,
ADD COLUMN `checklist_data` LONGTEXT NULL AFTER `description`;

-- 2. Create `task_activities` table for comments, system logs and attachments
CREATE TABLE IF NOT EXISTS `task_activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `type` ENUM('comment', 'system') NOT NULL DEFAULT 'comment',
  `activity` TEXT NOT NULL,
  `attachment_name` VARCHAR(255) NULL DEFAULT NULL,
  `attachment_path` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_task_id` (`task_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

