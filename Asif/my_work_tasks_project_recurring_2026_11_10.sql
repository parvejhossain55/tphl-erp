-- My Work: Realestate Project tab + Recurring Task tab support
-- Additive only (no DROP / TRUNCATE / data change). Existing rows get defaults.
-- Date prepared: 2026-11-10

ALTER TABLE tasks
    ADD COLUMN project_id INT NULL DEFAULT NULL,
    ADD COLUMN is_recurring TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN recurrence_type ENUM('daily','weekly','monthly') NULL DEFAULT NULL,
    ADD COLUMN recurrence_end_date DATE NULL DEFAULT NULL;

