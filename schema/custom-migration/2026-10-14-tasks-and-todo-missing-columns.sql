-- =====================================================================
-- Task Management & Todo Synchronization Missing Columns Migration
-- Run this SQL on the Live Database (phpMyAdmin / MySQL Console)
-- =====================================================================

-- 1. Add 'progress' column to `todo_tasks` table
-- (Fixes: "Unknown column 'progress' in 'field list'")
ALTER TABLE `todo_tasks` 
ADD COLUMN IF NOT EXISTS `progress` INT(11) NOT NULL DEFAULT 0 AFTER `status`;

-- 2. Add 'todo_project_id' column to `tasks` table
-- (Ensures sync between tasks and todo_projects)
ALTER TABLE `tasks` 
ADD COLUMN IF NOT EXISTS `todo_project_id` INT(11) NULL DEFAULT NULL AFTER `user_id`;

-- 3. Add 'is_team_project' and 'manager_user_id' to `todo_projects` table
ALTER TABLE `todo_projects` 
ADD COLUMN IF NOT EXISTS `is_team_project` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_inbox`;

ALTER TABLE `todo_projects` 
ADD COLUMN IF NOT EXISTS `manager_user_id` INT(11) NULL DEFAULT NULL AFTER `is_team_project`;

-- 4. Sync any existing completed todo tasks to 100% progress
UPDATE `todo_tasks` SET `progress` = 100 WHERE `status` = 'completed' AND `progress` = 0;
