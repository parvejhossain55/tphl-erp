-- Create task_activities table for tracking comments and system audit events
-- Date: 2026-11-11
-- Author: Asif Ikbal

CREATE TABLE IF NOT EXISTS `task_activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `task_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `type` ENUM('comment', 'system') NOT NULL DEFAULT 'comment',
  `activity` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_task_id` (`task_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

