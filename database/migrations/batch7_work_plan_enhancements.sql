-- =============================================================================
-- Batch 7: Project Working Plan Enhancements & Target Fixed Floor Name
-- Run: SOURCE database/migrations/batch7_work_plan_enhancements.sql
-- =============================================================================

CREATE TABLE IF NOT EXISTS project_working_plan_items (
    id              INT           AUTO_INCREMENT PRIMARY KEY,
    working_plan_id INT UNSIGNED  NOT NULL,
    sl_no           VARCHAR(20)   NOT NULL DEFAULT '',
    activity_name   TEXT          NOT NULL,
    floor_name      VARCHAR(50)   NULL, -- e.g. 'Foundation', 'Basement', 'G.floor', '1st floor'
    quantity        VARCHAR(50)   NOT NULL DEFAULT '1 Job',
    start_date      DATE          NOT NULL,
    req_days        INT           NOT NULL DEFAULT 1,
    end_date        DATE          NOT NULL,
    remarks         TEXT          NULL,
    sort_order      INT           NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pwpi_working_plan FOREIGN KEY (working_plan_id) REFERENCES project_working_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add floor_name column to target_fixed if not exists
ALTER TABLE target_fixed ADD COLUMN IF NOT EXISTS floor_name VARCHAR(50) NULL AFTER work_order_id;
