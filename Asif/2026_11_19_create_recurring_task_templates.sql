-- Recurring Task Templates (Template + Schedule architecture)
-- Additive only (no DROP / TRUNCATE / existing data changes)
-- Date: 2026-11-19

CREATE TABLE IF NOT EXISTS recurring_task_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    expected_output TEXT NULL,
    user_id INT NOT NULL,
    assigned_by INT NOT NULL,
    todo_project_id INT NULL DEFAULT NULL,
    project_id INT NULL DEFAULT NULL,
    description TEXT NULL,
    checklist_data LONGTEXT NULL,
    priority VARCHAR(30) NOT NULL DEFAULT 'medium',
    working_hour DECIMAL(5,2) NULL DEFAULT NULL,
    mentioned_user_ids TEXT NULL,
    cc_user_ids TEXT NULL,
    collaborator_user_ids TEXT NULL,
    attachment_image VARCHAR(255) NULL DEFAULT NULL,
    frequency ENUM('daily', 'weekly', 'monthly') NOT NULL,
    repeat_days TEXT NULL COMMENT 'JSON: weekdays ["sun","tue"] for weekly, month days [1,15,30] for monthly, NULL for daily',
    start_date DATE NOT NULL,
    end_date DATE NULL DEFAULT NULL COMMENT 'NULL = unlimited until paused/deleted',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_generated_date DATE NULL DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_rtt_active_dates (is_active, start_date, end_date),
    INDEX idx_rtt_user (user_id),
    INDEX idx_rtt_assigned_by (assigned_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE tasks
    ADD COLUMN recurring_template_id INT NULL DEFAULT NULL,
    ADD COLUMN occurrence_date DATE NULL DEFAULT NULL,
    ADD UNIQUE KEY uq_tasks_recurring_occurrence (recurring_template_id, occurrence_date),
    ADD INDEX idx_tasks_recurring_template (recurring_template_id);
