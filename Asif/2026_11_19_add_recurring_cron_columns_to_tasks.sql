-- Daily Cron support for Recurring Tasks (Additive only)
-- Date: 2026-11-19
-- Description:
--   1. Adds `parent_recurring_task_id` to link cron-generated occurrences back to the root recurring task.
--   2. Adds `recurrence_occurrence_date` + UNIQUE key `(parent_recurring_task_id, recurrence_occurrence_date)`
--      so the daily cron is idempotent and never creates duplicate occurrences for the same date.

ALTER TABLE tasks
    ADD COLUMN parent_recurring_task_id INT NULL DEFAULT NULL AFTER recurrence_custom_dates,
    ADD COLUMN recurrence_occurrence_date DATE NULL DEFAULT NULL AFTER parent_recurring_task_id,
    ADD UNIQUE KEY uq_tasks_recurring_parent_date (parent_recurring_task_id, recurrence_occurrence_date),
    ADD INDEX idx_tasks_recurring_parent (parent_recurring_task_id);
