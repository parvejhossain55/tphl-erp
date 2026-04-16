-- ============================================================
-- Add project_status column to projects table
-- Run this in phpMyAdmin → tphl_db → SQL tab
-- ============================================================

ALTER TABLE `projects`
  ADD COLUMN IF NOT EXISTS `project_status`
    ENUM('Planning','In Progress','On Hold','Completed','Cancelled','Delayed')
    NULL DEFAULT NULL
  AFTER `project_sale_status`;
