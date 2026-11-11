-- Add attachment columns to task_activities for My Work comments
-- Date: 2026-11-11
-- Author: Asif Ikbal

ALTER TABLE `task_activities` 
ADD COLUMN `attachment_name` VARCHAR(255) NULL AFTER `activity`,
ADD COLUMN `attachment_path` VARCHAR(255) NULL AFTER `attachment_name`;

