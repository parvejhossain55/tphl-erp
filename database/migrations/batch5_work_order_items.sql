-- =============================================================================
-- Batch 5: Work Order Items Table
-- Replaces JSON work_items column with proper relational rows
-- Run: SOURCE database/migrations/batch5_work_order_items.sql
-- =============================================================================

-- Step 1: Create the new items table
CREATE TABLE IF NOT EXISTS con_work_order_items (
    id              INT          AUTO_INCREMENT PRIMARY KEY,
    work_order_id   INT          NOT NULL,
    sl_no           VARCHAR(20)  NOT NULL DEFAULT '',
    description     TEXT         NOT NULL,
    location        VARCHAR(255) DEFAULT NULL,
    unit            VARCHAR(30)  NOT NULL DEFAULT 'sqft',
    quantity        DECIMAL(12,3) NOT NULL DEFAULT 0,
    rate            DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount          DECIMAL(14,2) GENERATED ALWAYS AS (quantity * rate) STORED,
    sort_order      INT          NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_wo_id (work_order_id),
    CONSTRAINT fk_woi_work_order
        FOREIGN KEY (work_order_id) REFERENCES con_work_orders(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Add lookup index to con_work_orders for fast project+design search
ALTER TABLE con_work_orders
    ADD INDEX IF NOT EXISTS idx_project_design (project_id, design_id);

-- Done: legacy work_items JSON column is kept (not removed) for backward compat
-- New code will write to con_work_order_items; old JSON is not used for new WOs
