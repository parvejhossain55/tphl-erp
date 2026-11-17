-- My Work: Collaborators stored separately from CC
-- Collaborators act as co-assigners: the task shows in their "Assigned by me" tab.
-- Additive only (no DROP / TRUNCATE / data change).
-- Date prepared: 2026-11-10

ALTER TABLE tasks
    ADD COLUMN collaborator_user_ids TEXT NULL DEFAULT NULL;

