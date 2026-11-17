-- Add expected_output column to tasks table
-- Date: 2026-11-11
-- Author: Asif Ikbal

ALTER TABLE `tasks`
ADD COLUMN `expected_output` TEXT NULL AFTER `title`;

