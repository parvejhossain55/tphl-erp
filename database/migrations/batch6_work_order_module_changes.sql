-- ============================================================
-- Work Order Module — সকল DB পরিবর্তন
-- এই session এ যা যা করা হয়েছে
-- Run with: --default-character-set=utf8mb4
-- ============================================================
SET NAMES utf8mb4;

-- ============================================================
-- ১. নতুন Table: con_work_order_items
--    (Rate Schedule rows — JSON এর বদলে proper relational rows)
-- ============================================================
CREATE TABLE IF NOT EXISTS con_work_order_items (
    id              INT           AUTO_INCREMENT PRIMARY KEY,
    work_order_id   INT           NOT NULL,
    sl_no           VARCHAR(20)   NOT NULL DEFAULT '',
    description     TEXT          NOT NULL,
    location        VARCHAR(255)  DEFAULT NULL,
    unit            VARCHAR(30)   NOT NULL DEFAULT 'sqft',
    quantity        DECIMAL(12,3) NOT NULL DEFAULT 0,
    rate            DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount          DECIMAL(14,2) GENERATED ALWAYS AS (quantity * rate) STORED,
    sort_order      INT           NOT NULL DEFAULT 0,
    created_at      TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_wo_id (work_order_id),
    CONSTRAINT fk_woi_work_order
        FOREIGN KEY (work_order_id) REFERENCES con_work_orders(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ২. con_work_orders table এ নতুন Index
--    (project_id + design_id — AJAX previous WO lookup এর জন্য)
-- ============================================================
ALTER TABLE con_work_orders
    ADD INDEX IF NOT EXISTS idx_project_design (project_id, design_id);

-- ============================================================
-- ৩. নতুন Table: con_work_order_updates
--    (Edit করার সময় তারিখ ও কী পরিবর্তন হয়েছে তার history)
-- ============================================================
CREATE TABLE IF NOT EXISTS con_work_order_updates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id   INT NOT NULL,
    update_date     DATE NOT NULL           COMMENT 'কোন তারিখে update হয়েছে',
    update_notes    TEXT NULL               COMMENT 'কী কী পরিবর্তন হয়েছে',
    updated_by      INT NOT NULL DEFAULT 0  COMMENT 'admin user id',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_wo_update (work_order_id),
    CONSTRAINT fk_wou_wo
        FOREIGN KEY (work_order_id) REFERENCES con_work_orders(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ৪. Work Types Sample Data
--    (design_type_id 1=Structural, 2=Electrical, 3=Plumbing, 4=Fire Work)
--    Note: এটা আলাদাভাবে run করতে হবে যদি data না থাকে
--    Already inserted — skip করুন যদি data আগেই থাকে
-- ============================================================
-- INSERT INTO work_types (...) -- See: database/migrations/work_types_sample_data.sql

-- ============================================================
-- Verify: সব tables আছে কিনা দেখুন
-- ============================================================
SHOW TABLES LIKE 'con_work_order%';

-- ============================================================
-- ৫. নতুন Table: con_work_order_approvals
--    (Sequential multi-role approval workflow tracking)
-- ============================================================
CREATE TABLE IF NOT EXISTS con_work_order_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT NOT NULL,
    role_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    action_by INT NULL,
    action_at DATETIME NULL,
    remarks TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_woa_work_order FOREIGN KEY (work_order_id) REFERENCES con_work_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- ৬. con_work_orders.status enum column update
--    (সব sequential statuses support করার জন্য alter করা হলো)
-- ============================================================
ALTER TABLE con_work_orders MODIFY COLUMN status ENUM('draft', 'approved', 'active', 'work_completed', 'qc_approved', 'closed', 'rejected', 'cancelled') DEFAULT 'draft';
