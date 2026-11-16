-- =============================================================================
-- Migration: Add Multiple Custom Dates support for recurring tasks
-- Date: 2026-11-16
-- Description:
--   1. Extends `tasks.recurrence_type` ENUM to include `'custom'`.
--   2. Adds `recurrence_custom_dates` (TEXT, stores JSON array of YYYY-MM-DD dates).
-- =============================================================================

ALTER TABLE tasks
    MODIFY COLUMN recurrence_type ENUM('daily', 'weekly', 'monthly', 'custom') NULL DEFAULT NULL,
    ADD COLUMN recurrence_custom_dates TEXT NULL AFTER recurrence_end_date;
