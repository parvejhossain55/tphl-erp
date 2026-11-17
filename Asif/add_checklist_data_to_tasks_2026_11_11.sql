-- Add checklist_data column to tasks table for interactive acceptance checklists
-- Date: 2026-11-11
-- Author: Asif Ikbal

ALTER TABLE `tasks`
ADD COLUMN `checklist_data` LONGTEXT NULL AFTER `description`;

