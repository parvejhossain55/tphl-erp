-- =============================================================================
-- TPHL-ERP — PHASE 2: CONSTRUCTION MANAGEMENT MODULE
-- Database  : tphl_db  (SAME existing database — merged strategy)
-- Engine    : MariaDB 10.4 / MySQL 8.0+
-- Charset   : utf8mb4 / utf8mb4_general_ci  (matches Phase 1)
-- =============================================================================
--
-- STRATEGY:
--   Phase 1 tables (users, roles, role_permissions, projects,
--                   approval_requests, cashout_payments) are KEPT AS-IS.
--   Phase 2 tables are ADDED to the same tphl_db.
--   All Phase 2 tables reference Phase 1's users and projects.
--
-- SHARED TABLES (Phase 1 — no re-create needed):
--   users                 — all created_by / approved_by references
--   roles                 — add new Construction roles here via INSERT
--   role_permissions      — add new permissions for construction roles
--   projects              — same projects, add `phase` column (see Section 0)
--   approval_requests     — contractor bill + fund request approvals go here
--   cashout_payments      — all construction payments recorded here
--
-- SECTION MAP:
--   0.  Phase 1 Modifications     (ALTER TABLE only)
--   1.  Pre-Construction          (land_visibility_study, architect_engineering,
--                                  design_types, project_drawings, soil_tests)
--   2.  Operations Operators      (contractors / workers — no system login)
--   3.  Materials & Inventory     (materials, warehouses, stocks, stock_movements)
--   4.  BOQ                       (boq_master, boq_items)
--   5.  Working Plan & Budget     (project_working_plan, budget_extend_requests)
--   6a. Procurement — Vendors     (vendors)
--   6b. Purchase Requests         (purchase_requests, purchase_request_items)
--   6c. Quotations / RFQ          (quotations, quotation_items)
--   6d. Comparative Statement     (quotation_comparisons, comparison_items)
--   6e. Purchase Orders           (purchase_orders, purchase_order_items)
--   7.  Material Receiving + QC   (material_receiving, material_receiving_items)
--   8.  Vendor Penalty & Rating   (vendor_penalties, vendor_ratings)
--   9.  Target Fixed              (weekly / monthly QC progress targets)
--   10. Site Execution            (daily_progress_reports, material_usage_logs)
--   11. Contractor Bill Requests  (contractor_bill_requests)
--         Flow: Draft → Submitted → Verified → Approved → Paid
--         Approval: approval_requests (source_table='contractor_bill_requests')
--         Payment : cashout_payments
--   12. Vendor Payment Requests   (vendor_payment_requests)
--   13. Operational Fund Requests (operational_fund_requests)
--         Flow: Pending → Approved → Paid  (no verification step)
--         Approval: approval_requests (source_table='operational_fund_requests')
--         Payment : cashout_payments
--   14. Financial Transactions    (full money movement ledger)
--   15. Generic Approval Audit    (con_approval_logs — construction step log)
--
-- Total new tables: 34
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;


-- =============================================================================
-- 0. PHASE 1 MODIFICATIONS  (ALTER existing Phase 1 tables)
-- =============================================================================

-- Add `phase` to projects so construction projects can be flagged
ALTER TABLE `projects`
  ADD COLUMN IF NOT EXISTS `phase`
    ENUM('sales','construction','both') NOT NULL DEFAULT 'sales'
    COMMENT 'sales=Phase1 only | construction=Phase2 | both=shared'
  AFTER `status`;

-- Add new Construction roles (INSERT — do NOT re-create the roles table)
-- Run only once; use INSERT IGNORE to avoid duplicates
INSERT IGNORE INTO `roles` (`id`, `name`) VALUES
  (20, 'Site Engineer'),
  (21, 'QC Officer'),
  (22, 'Procurement Officer'),
  (23, 'Operation Head'),
  (24, 'Construction Admin');


-- =============================================================================
-- 1. PRE-CONSTRUCTION — LAND VISIBILITY STUDY
-- =============================================================================

CREATE TABLE IF NOT EXISTS `land_visibility_study` (
  `id`                         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`                 INT(11)          NOT NULL,

  `land_legal_doc_status`      ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `digital_survey_report_link` VARCHAR(500)     DEFAULT NULL,
  `govt_approval_doc_link`     VARCHAR(500)     DEFAULT NULL,
  `others_doc_link`            VARCHAR(500)     DEFAULT NULL,

  -- QC sign-off
  `qc_reviewed_by_id`          INT(11)          DEFAULT NULL COMMENT 'FK → users.id',
  `qc_approval_status`         ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `qc_reviewed_at`             TIMESTAMP        NULL DEFAULT NULL,

  `created_by`                 INT(11)          NOT NULL,
  `created_at`                 TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                 TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lvs_project` (`project_id`),
  KEY `idx_lvs_qc` (`qc_reviewed_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Land legality & visibility study — one record per project';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `architect_engineering` (
  `id`                            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`                    INT(11)          NOT NULL,
  `user_id`                       INT(11)          NOT NULL COMMENT 'Internal architect (FK → users)',
  `full_architecture_design_link` VARCHAR(500)     DEFAULT NULL,
  `notes`                         TEXT             DEFAULT NULL,
  `created_by`                    INT(11)          NOT NULL COMMENT 'Session user who entered this record → users.id',
  `created_at`                    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ae_project`    (`project_id`),
  KEY `idx_ae_user`       (`user_id`),
  KEY `idx_ae_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Architecture & engineering design submissions per project';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `design_types` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)     NOT NULL COMMENT 'e.g. Architectural, Structural, MEP, Interior',
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` INT(11)          DEFAULT NULL COMMENT 'Session user who created this lookup → users.id',
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_design_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Lookup — design drawing types';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `project_drawings` (
  `id`                  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`          INT(11)          NOT NULL,
  `design_type_id`      INT(11) UNSIGNED NOT NULL,

  `is_in_house`         TINYINT(1)       NOT NULL DEFAULT 1
                        COMMENT '1 = internal designer | 0 = outsourced',

  `user_id`             INT(11)          DEFAULT NULL
                        COMMENT 'Internal designer (NULL if outsourced) → users.id',
  `designer_name`       VARCHAR(150)     DEFAULT NULL COMMENT 'External designer name',
  `designer_designation` VARCHAR(150)    DEFAULT NULL,
  `designer_company`    VARCHAR(150)     DEFAULT NULL,

  `approved_by_id`      INT(11)          DEFAULT NULL COMMENT 'FK → users.id',
  `approval_date`       DATE             DEFAULT NULL,

  `design_link`         VARCHAR(500)     DEFAULT NULL,
  `version`             VARCHAR(20)      DEFAULT NULL,
  `status`              ENUM('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `created_by`          INT(11)          NOT NULL COMMENT 'Session user who uploaded this drawing → users.id',
  `created_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_pd_project`     (`project_id`),
  KEY `idx_pd_design_type` (`design_type_id`),
  KEY `idx_pd_user`        (`user_id`),
  KEY `idx_pd_approved_by` (`approved_by_id`),
  KEY `idx_pd_created_by`  (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Versioned design drawings per project';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `soil_tests` (
  `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`             INT(11)          NOT NULL,
  `site_engineer_id`       INT(11)          NOT NULL COMMENT 'FK → users.id',
  `field_data`             TEXT             DEFAULT NULL,
  `lab_test_document_link` VARCHAR(500)     DEFAULT NULL,
  `test_date`              DATE             DEFAULT NULL,
  `created_by`             INT(11)          NOT NULL COMMENT 'Session user who entered this record → users.id',
  `created_at`             TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_st_project`    (`project_id`),
  KEY `idx_st_engineer`   (`site_engineer_id`),
  KEY `idx_st_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Soil test records — field data and lab reports';


-- =============================================================================
-- 2. OPERATIONS OPERATORS  (Contractors & Workers — NO system login)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `operations_operators` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(255)     NOT NULL,
  `role`        ENUM('Contractor','Subcontractor','Supervisor','Worker')
                                 NOT NULL DEFAULT 'Contractor',
  `trade`       VARCHAR(150)     DEFAULT NULL
                COMMENT 'e.g. Civil, Electrical, Plumbing, Painting',
  `phone`       VARCHAR(30)      DEFAULT NULL,
  `nid_number`  VARCHAR(50)      DEFAULT NULL
                COMMENT 'National ID for identity verification',
  `address`     TEXT             DEFAULT NULL,
  `daily_rate`  DECIMAL(10,2)    DEFAULT NULL
                COMMENT 'Default daily wage / rate (optional)',
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by`  INT(11)          NOT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_oo_role`   (`role`),
  KEY `idx_oo_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Field operators — contractors and workers who do NOT log into the system';


-- =============================================================================
-- 3. MATERIALS & INVENTORY
-- =============================================================================

CREATE TABLE IF NOT EXISTS `con_materials` (
  `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(255)     NOT NULL,
  `unit`            VARCHAR(50)      NOT NULL COMMENT 'e.g. kg, bag, m3, pcs, litre',
  `min_stock_level` DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `created_by`      INT(11)          NOT NULL,
  `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Master list of construction materials (prefixed con_ to avoid Phase1 conflicts)';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `con_warehouses` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150)     NOT NULL,
  `project_id` INT(11)          DEFAULT NULL COMMENT 'NULL = central warehouse',
  `location`   VARCHAR(255)     DEFAULT NULL,
  `created_by` INT(11)          NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Physical warehouse / store locations (site or central)';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `con_stocks` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`      INT(11) UNSIGNED NOT NULL,
  `warehouse_id`     INT(11) UNSIGNED NOT NULL,
  `current_quantity` DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `updated_at`       TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_material_warehouse` (`material_id`, `warehouse_id`),
  KEY `idx_stock_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Current stock levels — one row per material per warehouse';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `con_stock_movements` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`  INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `type`         ENUM('IN','OUT') NOT NULL,
  `quantity`     DECIMAL(12,3)    NOT NULL,
  `ref_type`     VARCHAR(100)     DEFAULT NULL
                 COMMENT 'Source table: material_receiving / material_usage_logs',
  `ref_id`       INT(11) UNSIGNED DEFAULT NULL COMMENT 'PK of the source record',
  `moved_by`     INT(11)          DEFAULT NULL COMMENT 'FK → users.id',
  `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_sm_material`  (`material_id`),
  KEY `idx_sm_warehouse` (`warehouse_id`),
  KEY `idx_sm_ref`       (`ref_type`, `ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Immutable ledger of all stock IN / OUT movements';


-- =============================================================================
-- 4. BILL OF QUANTITIES (BOQ)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `boq_master` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`  INT(11)          NOT NULL,
  `version`     VARCHAR(20)      NOT NULL DEFAULT '1.0',
  `status`      ENUM('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `approved_by` INT(11)          DEFAULT NULL COMMENT 'FK → users.id',
  `approved_at` TIMESTAMP        NULL DEFAULT NULL,
  `created_by`  INT(11)          NOT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_boq_project`     (`project_id`),
  KEY `idx_boq_approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='BOQ header — one version per project revision';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `boq_items` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `boq_id`      INT(11) UNSIGNED NOT NULL,
  `material_id` INT(11) UNSIGNED NOT NULL,
  `quantity`    DECIMAL(12,3)    NOT NULL,
  `unit_cost`   DECIMAL(14,4)    NOT NULL,
  `total_cost`  DECIMAL(16,4)    GENERATED ALWAYS AS (`quantity` * `unit_cost`) STORED,

  PRIMARY KEY (`id`),
  KEY `idx_boqi_boq`      (`boq_id`),
  KEY `idx_boqi_material` (`material_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='BOQ line items; total_cost auto-computed';


-- =============================================================================
-- 5. WORKING PLAN & BUDGET CONTROL
-- =============================================================================

CREATE TABLE IF NOT EXISTS `project_working_plan` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`       INT(11)          NOT NULL,
  `material_id`      INT(11) UNSIGNED NOT NULL,
  `year`             SMALLINT UNSIGNED NOT NULL,
  `month`            TINYINT UNSIGNED  NOT NULL COMMENT '1–12',
  `planned_quantity` DECIMAL(12,3)    NOT NULL,
  `estimated_cost`   DECIMAL(16,4)    NOT NULL,
  `created_by`       INT(11)          NOT NULL,
  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pwp_project_material_ym` (`project_id`, `material_id`, `year`, `month`),
  KEY `idx_pwp_material`   (`material_id`),
  KEY `idx_pwp_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Monthly working plan — planned material quantities and estimated costs per project';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `budget_extend_requests` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`   INT(11)          NOT NULL,
  `requested_by` INT(11)          NOT NULL COMMENT 'FK → users.id',
  `title`        VARCHAR(255)     NOT NULL,
  `amount`       DECIMAL(16,4)    NOT NULL COMMENT 'Additional budget amount requested',
  `reason`       TEXT             NOT NULL,
  `status`       ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `approved_by`  INT(11)          DEFAULT NULL,
  `approved_at`  TIMESTAMP        NULL DEFAULT NULL,
  `remarks`      TEXT             DEFAULT NULL,
  `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ber_project`      (`project_id`),
  KEY `idx_ber_requested_by` (`requested_by`),
  KEY `idx_ber_approved_by`  (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Raised when a project budget is exceeded and additional funds are needed';


-- =============================================================================
-- 6a. PROCUREMENT — VENDORS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vendors` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255)     NOT NULL,
  `phone`      VARCHAR(30)      DEFAULT NULL,
  `email`      VARCHAR(191)     DEFAULT NULL,
  `address`    TEXT             DEFAULT NULL,
  `rating`     DECIMAL(3,2)     NOT NULL DEFAULT 5.00
               COMMENT 'Aggregate rating 0.00–5.00',
  `status`     ENUM('active','blacklisted') NOT NULL DEFAULT 'active',
  `created_by` INT(11)          NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendor_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Approved vendor / supplier registry';


-- =============================================================================
-- 6b. PURCHASE REQUESTS (PR)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT(11)          NOT NULL,
  `created_by` INT(11)          NOT NULL,
  `status`     ENUM('Draft','Submitted','Approved','Rejected','PO Issued') NOT NULL DEFAULT 'Draft',
  `notes`      TEXT             DEFAULT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_pr_project`    (`project_id`),
  KEY `idx_pr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Purchase request header';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `purchase_request_items` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`  INT(11) UNSIGNED NOT NULL,
  `material_id` INT(11) UNSIGNED NOT NULL,
  `quantity`    DECIMAL(12,3)    NOT NULL,
  `created_by`  INT(11)          NOT NULL COMMENT 'Session user who added this line item → users.id',

  PRIMARY KEY (`id`),
  KEY `idx_pri_request`    (`request_id`),
  KEY `idx_pri_material`   (`material_id`),
  KEY `idx_pri_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Line items of a purchase request';


-- =============================================================================
-- 6c. QUOTATIONS / RFQ
-- =============================================================================

CREATE TABLE IF NOT EXISTS `quotations` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`    INT(11) UNSIGNED NOT NULL,
  `vendor_id`     INT(11) UNSIGNED NOT NULL,
  `validity_date` DATE             DEFAULT NULL,
  `status`        ENUM('Received','Shortlisted','Rejected') NOT NULL DEFAULT 'Received',
  `created_by`    INT(11)          NOT NULL COMMENT 'Session user who entered the quotation → users.id',
  `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_q_request`    (`request_id`),
  KEY `idx_q_vendor`     (`vendor_id`),
  KEY `idx_q_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Vendor quotation header against a purchase request';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotation_id` INT(11) UNSIGNED NOT NULL,
  `material_id`  INT(11) UNSIGNED NOT NULL,
  `quantity`     DECIMAL(12,3)    NOT NULL,
  `unit_price`   DECIMAL(14,4)    NOT NULL,
  `total_price`  DECIMAL(16,4)    GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by`   INT(11)          NOT NULL COMMENT 'Session user who entered pricing → users.id',

  PRIMARY KEY (`id`),
  KEY `idx_qi_quotation`  (`quotation_id`),
  KEY `idx_qi_material`   (`material_id`),
  KEY `idx_qi_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Line-level pricing from a vendor quotation';


-- =============================================================================
-- 6d. COMPARATIVE STATEMENT (CST)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `quotation_comparisons` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`  INT(11) UNSIGNED NOT NULL,
  `prepared_by` INT(11)          NOT NULL COMMENT 'FK → users.id',
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_request` (`request_id`),
  KEY `idx_qc_prepared_by` (`prepared_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Comparative statement (CST) header per purchase request';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `comparison_items` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comparison_id` INT(11) UNSIGNED NOT NULL,
  `material_id`   INT(11) UNSIGNED NOT NULL,
  `vendor_id`     INT(11) UNSIGNED NOT NULL,
  `unit_price`    DECIMAL(14,4)    NOT NULL,
  `is_selected`   TINYINT(1)       NOT NULL DEFAULT 0
                  COMMENT '1 = winning vendor for this material',
  `created_by`    INT(11)          NOT NULL COMMENT 'Session user who entered this CST row → users.id',

  PRIMARY KEY (`id`),
  KEY `idx_ci_comparison`  (`comparison_id`),
  KEY `idx_ci_material`    (`material_id`),
  KEY `idx_ci_vendor`      (`vendor_id`),
  KEY `idx_ci_created_by`  (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Per-material per-vendor CST rows; is_selected marks the awarded vendor';


-- =============================================================================
-- 6e. PURCHASE ORDERS (PO) — MULTI-VENDOR
-- =============================================================================

CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) UNSIGNED NOT NULL,
  `created_by` INT(11)          NOT NULL,
  `status`     ENUM('Draft','Issued','Partially Received','Fully Received','Cancelled')
                                NOT NULL DEFAULT 'Draft',
  `order_date` DATE             NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_po_request`    (`request_id`),
  KEY `idx_po_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='PO header — one PO can span multiple vendors via line items';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id`                 INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id`  INT(11) UNSIGNED NOT NULL,
  `material_id`        INT(11) UNSIGNED NOT NULL,
  `selected_vendor_id` INT(11) UNSIGNED NOT NULL,
  `quantity`           DECIMAL(12,3)    NOT NULL,
  `unit_price`         DECIMAL(14,4)    NOT NULL,
  `total_price`        DECIMAL(16,4)    GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by`         INT(11)          NOT NULL COMMENT 'Session user who added this PO line → users.id',

  PRIMARY KEY (`id`),
  KEY `idx_poi_po`         (`purchase_order_id`),
  KEY `idx_poi_material`   (`material_id`),
  KEY `idx_poi_vendor`     (`selected_vendor_id`),
  KEY `idx_poi_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='PO line items — each item locked to a specific vendor';


-- =============================================================================
-- 7. MATERIAL RECEIVING + QC
-- =============================================================================

CREATE TABLE IF NOT EXISTS `material_receiving` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id` INT(11) UNSIGNED NOT NULL,

  -- Site Engineer sign-off
  `received_by`       INT(11)          NOT NULL COMMENT 'Site engineer who received the goods → users.id',
  `received_date`     DATE             NOT NULL,
  `received_status`   ENUM('Full','Partial','Rejected') NOT NULL DEFAULT 'Partial',
  `site_eng_note`     TEXT             DEFAULT NULL,
  `site_eng_photo`    VARCHAR(500)     DEFAULT NULL,

  -- QC sign-off
  `qc_checked_by`     INT(11)          DEFAULT NULL COMMENT 'QC officer → users.id',
  `qc_note`           TEXT             DEFAULT NULL,
  `qc_photo`          VARCHAR(500)     DEFAULT NULL,

  `overall_status`    ENUM('Pending QC','Accepted','Partially Accepted','Rejected')
                                       NOT NULL DEFAULT 'Pending QC',
  `created_by`        INT(11)          NOT NULL COMMENT 'Session user who created the GRN entry → users.id',
  `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_mr_po`            (`purchase_order_id`),
  KEY `idx_mr_received_by`   (`received_by`),
  KEY `idx_mr_qc_checked_by` (`qc_checked_by`),
  KEY `idx_mr_created_by`    (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Goods Receipt Note (GRN) header — site + QC dual sign-off';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `material_receiving_items` (
  `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receiving_id`           INT(11) UNSIGNED NOT NULL,
  `purchase_order_item_id` INT(11) UNSIGNED NOT NULL,
  `ordered_quantity`       DECIMAL(12,3)    NOT NULL,
  `received_quantity`      DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `status`                 ENUM('Accepted','Partially Accepted','Rejected','Pending QC')
                                            NOT NULL DEFAULT 'Pending QC',
  `site_engineer_checked`  TINYINT(1)       NOT NULL DEFAULT 0,
  `qc_checked`             TINYINT(1)       NOT NULL DEFAULT 0,
  `issue_reason`           TEXT             DEFAULT NULL,
  `attachment`             VARCHAR(500)     DEFAULT NULL,
  `created_by`             INT(11)          NOT NULL COMMENT 'Session user who created this item row → users.id',

  PRIMARY KEY (`id`),
  KEY `idx_mri_receiving`  (`receiving_id`),
  KEY `idx_mri_poi`        (`purchase_order_item_id`),
  KEY `idx_mri_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Per-item GRN detail with dual QC and site engineer sign-off';


-- =============================================================================
-- 8. VENDOR PENALTY & RATING
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vendor_penalties` (
  `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`              INT(11) UNSIGNED NOT NULL,
  `purchase_order_item_id` INT(11) UNSIGNED NOT NULL,
  `penalty_type`           VARCHAR(100)     NOT NULL
                           COMMENT 'e.g. Late Delivery, Quality Failure, Short Supply',
  `penalty_amount`         DECIMAL(14,4)    NOT NULL DEFAULT 0.0000,
  `reason`                 TEXT             DEFAULT NULL,
  `attachment`             VARCHAR(500)     DEFAULT NULL,
  `created_by`             INT(11)          DEFAULT NULL,
  `created_at`             TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_vp_vendor` (`vendor_id`),
  KEY `idx_vp_poi`    (`purchase_order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Financial and non-financial penalties raised against vendors';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `vendor_ratings` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`  INT(11) UNSIGNED NOT NULL,
  `rated_by`   INT(11)          NOT NULL COMMENT 'FK → users.id',
  `rating`     DECIMAL(2,1)     NOT NULL COMMENT '1.0–5.0',
  `reason`     TEXT             DEFAULT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_vr_vendor`   (`vendor_id`),
  KEY `idx_vr_rated_by` (`rated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Individual rating entries; aggregate synced back to vendors.rating via app';


-- =============================================================================
-- 9. TARGET FIXED  (Weekly / Monthly QC Progress Targets)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `target_fixed` (
  `id`                   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`           INT(11)          NOT NULL,
  `user_id`              INT(11)          NOT NULL COMMENT 'Person setting the target → users.id',

  `year`                 SMALLINT UNSIGNED NOT NULL,
  `month`                TINYINT UNSIGNED  NOT NULL COMMENT '1–12',
  `week_number`          TINYINT UNSIGNED  DEFAULT NULL COMMENT '1–5; NULL = monthly target',

  `target_percentage`    DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
  `gain_percentage`      DECIMAL(5,2)     DEFAULT 0.00
                         COMMENT 'Actual gain recorded',

  `photo_link`           VARCHAR(500)     DEFAULT NULL,
  `field_test_details`   TEXT             DEFAULT NULL,
  `lab_test_result`      TEXT             DEFAULT NULL,

  -- Multi-role approval flags
  `approved_by_site_eng` TINYINT(1)       NOT NULL DEFAULT 0,
  `approved_by_qc`       TINYINT(1)       NOT NULL DEFAULT 0,
  `approved_by_pc`       TINYINT(1)       NOT NULL DEFAULT 0
                         COMMENT 'Procurement Coordinator',
  `approved_by_pd`       TINYINT(1)       NOT NULL DEFAULT 0
                         COMMENT 'Project Director',
  `approved_by_manager`  TINYINT(1)       NOT NULL DEFAULT 0,

  `qc_reviewed_by_id`    INT(11)          DEFAULT NULL COMMENT 'FK → users.id',
  `qc_reviewed_at`       TIMESTAMP        NULL DEFAULT NULL,

  `created_at`           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_tf_project`        (`project_id`),
  KEY `idx_tf_user`           (`user_id`),
  KEY `idx_tf_qc_reviewed_by` (`qc_reviewed_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Weekly/monthly progress targets per project — multi-role approval chain';


-- =============================================================================
-- 10. SITE EXECUTION
-- =============================================================================

CREATE TABLE IF NOT EXISTS `daily_progress_reports` (
  `id`                  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`          INT(11)          NOT NULL,
  `site_engineer_id`    INT(11)          NOT NULL COMMENT 'Engineer who filed the DPR → users.id',
  `work_done`           TEXT             NOT NULL,
  `progress_percentage` DECIMAL(5,2)     NOT NULL DEFAULT 0.00 COMMENT '0.00–100.00',
  `report_date`         DATE             NOT NULL,
  `weather_condition`   VARCHAR(100)     DEFAULT NULL,
  `manpower_count`      SMALLINT UNSIGNED DEFAULT NULL,
  `created_by`          INT(11)          NOT NULL COMMENT 'Session user who submitted this DPR → users.id',
  `created_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_dpr_project`    (`project_id`),
  KEY `idx_dpr_engineer`   (`site_engineer_id`),
  KEY `idx_dpr_date`       (`report_date`),
  KEY `idx_dpr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Daily Progress Report (DPR) filed by site engineers';

-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `material_usage_logs` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`    INT(11)          NOT NULL,
  `material_id`   INT(11) UNSIGNED NOT NULL,
  `warehouse_id`  INT(11) UNSIGNED NOT NULL,
  `quantity_used` DECIMAL(12,3)    NOT NULL,
  `used_by`       INT(11)          NOT NULL COMMENT 'Worker/engineer who used the material → users.id',
  `used_date`     DATE             NOT NULL,
  `notes`         TEXT             DEFAULT NULL,
  `created_by`    INT(11)          NOT NULL COMMENT 'Session user who logged this usage → users.id',
  `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_mul_project`    (`project_id`),
  KEY `idx_mul_material`   (`material_id`),
  KEY `idx_mul_warehouse`  (`warehouse_id`),
  KEY `idx_mul_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Records every material withdrawal for site use; triggers a stock OUT movement';


-- =============================================================================
-- 11. CONTRACTOR BILL REQUESTS
--
-- FLOW  : Draft → Submitted → Verified → Approved → Paid
-- WHO   : Operations team manually enters the contractor paper bill
-- VERIFY: Site Engineer / Operation Head verifies work was done
-- APPROVE: Goes to approval_requests table (source_table = 'contractor_bill_requests')
--           Finance / Management approves from existing approval dashboard
-- PAY   : Recorded in cashout_payments (same as Phase 1 cashout flow)
--
-- WHY SEPARATE FROM operational_fund_requests:
--   - Always tied to a specific contractor (operator_id required)
--   - Has a verification step BEFORE approval
--   - For SPECIFIC completed work (not general petty cash)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `contractor_bill_requests` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`       INT(11)          NOT NULL,
  `operator_id`      INT(11) UNSIGNED NOT NULL COMMENT 'FK → operations_operators',
  `created_by`       INT(11)          NOT NULL COMMENT 'Operations team member who entered the bill → users.id',

  -- Bill details (from paper bill)
  `bill_date`        DATE             NOT NULL COMMENT 'Date on the contractor paper bill',
  `work_description` TEXT             NOT NULL,
  `bill_amount`      DECIMAL(15,2)    NOT NULL,
  `attachment_path`  VARCHAR(255)     DEFAULT NULL COMMENT 'Scanned copy of physical bill',

  -- STEP 1: Site Engineer / Operation Head verifies work was actually done
  `verified_by`      INT(11)          DEFAULT NULL COMMENT 'FK → users.id',
  `verified_at`      TIMESTAMP        NULL DEFAULT NULL,
  `verification_note` TEXT            DEFAULT NULL,

  -- STEP 2: Finance approval goes to approval_requests table
  -- approval_requests.source_table = 'contractor_bill_requests'
  -- approval_requests.source_id    = this.id
  `approved_amount`  DECIMAL(15,2)    DEFAULT NULL
                     COMMENT 'Copied from approval_requests.approved_amount after approval',

  `status`           ENUM(
                       'draft',      -- saved but not submitted yet
                       'submitted',  -- submitted by operations team
                       'verified',   -- work confirmed by site engineer
                       'approved',   -- finance approved
                       'rejected',   -- finance rejected
                       'paid'        -- payment recorded in cashout_payments
                     ) NOT NULL DEFAULT 'draft',

  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_cbr_project`    (`project_id`),
  KEY `idx_cbr_operator`   (`operator_id`),
  KEY `idx_cbr_created_by` (`created_by`),
  KEY `idx_cbr_verified_by` (`verified_by`),
  KEY `idx_cbr_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Contractor bills — verify by site eng → approve via approval_requests → pay via cashout_payments';


-- =============================================================================
-- 12. VENDOR PAYMENT REQUESTS
--
-- FLOW  : Draft → Submitted → Approved → Paid
-- NOTE  : Linked to a Purchase Order; requires QC + delivery verification
--         Approval via approval_requests (source_table = 'vendor_payment_requests')
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vendor_payment_requests` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`         INT(11) UNSIGNED NOT NULL,
  `purchase_order_id` INT(11) UNSIGNED NOT NULL,
  `created_by`        INT(11)          NOT NULL COMMENT 'FK → users.id',

  `invoice_number`    VARCHAR(100)     DEFAULT NULL,
  `invoice_amount`    DECIMAL(16,4)    NOT NULL,

  `delivery_verified` TINYINT(1)       NOT NULL DEFAULT 0
                      COMMENT '1 = delivery confirmed by site eng',
  `qc_verified`       TINYINT(1)       NOT NULL DEFAULT 0
                      COMMENT '1 = quality confirmed by QC officer',

  `status`            ENUM('Draft','Submitted','Approved','Rejected','Paid')
                                       NOT NULL DEFAULT 'Draft',

  `approved_amount`   DECIMAL(16,4)    DEFAULT NULL,
  `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_vpr_vendor`     (`vendor_id`),
  KEY `idx_vpr_po`         (`purchase_order_id`),
  KEY `idx_vpr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Vendor payment request — linked to PO, requires QC and delivery verification';


-- =============================================================================
-- 13. OPERATIONAL FUND REQUESTS
--
-- FLOW  : Pending → Approved → Paid  (NO verification step)
-- WHO   : Operation Head / Site Engineer raises fund request
-- USE   : Labour wages, petty cash, daily operational cost
--         operator_id is OPTIONAL (can be for a specific worker or general)
-- APPROVE: Goes to approval_requests (source_table = 'operational_fund_requests')
-- PAY   : Recorded in cashout_payments
--
-- WHY SEPARATE FROM contractor_bill_requests:
--   - operator_id is nullable (general fund possible)
--   - No verification step — goes directly to approval
--   - For recurring/general operational costs (not a specific completed job)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `operational_fund_requests` (
  `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`      INT(11)          NOT NULL,
  `requester_id`    INT(11)          NOT NULL
                    COMMENT 'Operation Head / Site Engineer who raised the request → users.id',
  `operator_id`     INT(11) UNSIGNED DEFAULT NULL
                    COMMENT 'Specific worker / contractor (nullable = general fund) → operations_operators.id',

  `amount`          DECIMAL(15,2)    NOT NULL,
  `purpose`         TEXT             NOT NULL
                    COMMENT 'e.g. Weekly labour wages, Petty cash for tools',
  `fund_date`       DATE             NOT NULL COMMENT 'Date the fund is needed for',

  -- Approval goes to approval_requests table
  -- approval_requests.source_table = 'operational_fund_requests'
  -- approval_requests.source_id    = this.id
  `approved_amount` DECIMAL(15,2)    DEFAULT NULL,

  `status`          ENUM(
                      'pending',   -- just raised
                      'approved',  -- finance/management approved
                      'rejected',  -- rejected
                      'paid'       -- payment done via cashout_payments
                    ) NOT NULL DEFAULT 'pending',

  `created_by`      INT(11)          NOT NULL COMMENT 'Session user who submitted this fund request → users.id',
  `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_ofr_project`     (`project_id`),
  KEY `idx_ofr_requester`   (`requester_id`),
  KEY `idx_ofr_operator`    (`operator_id`),
  KEY `idx_ofr_created_by`  (`created_by`),
  KEY `idx_ofr_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Fund requests for labour wages and operational costs — no verification step';


-- =============================================================================
-- 14. FINANCIAL TRANSACTIONS  (Complete Money Movement Ledger)
--
-- Links to BOTH contractor_bill_requests AND operational_fund_requests
-- via polymorphic ref_type / ref_id fields.
-- Tracks Advance, Partial, Full payments.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `con_financial_transactions` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Polymorphic reference
  `ref_type`         ENUM(
                       'contractor_bill',   -- → contractor_bill_requests
                       'operational_fund',  -- → operational_fund_requests
                       'vendor_payment'     -- → vendor_payment_requests
                     ) NOT NULL COMMENT 'Source of this payment',
  `ref_id`           INT(11) UNSIGNED NOT NULL COMMENT 'PK of the source record',

  `payment_type`     ENUM('Advance','Partial','Full','Refund') NOT NULL,
  `payment_method`   ENUM('Cash','Cheque','BankTransfer','MobileWallet','Other') NOT NULL,

  `amount_paid`      DECIMAL(15,2)    NOT NULL,
  `reference_number` VARCHAR(150)     DEFAULT NULL
                     COMMENT 'Bank ref / cheque number / trx ID',

  `processed_by`     INT(11)          DEFAULT NULL
                     COMMENT 'Finance officer who processed the payment → users.id',
  `transaction_date` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

  `update_status`    ENUM('Completed','Failed','Pending') NOT NULL DEFAULT 'Pending',
  `notes`            TEXT             DEFAULT NULL,

  `created_by`       INT(11)          NOT NULL COMMENT 'Session user who recorded this transaction → users.id',

  PRIMARY KEY (`id`),
  KEY `idx_cft_ref`        (`ref_type`, `ref_id`),
  KEY `idx_cft_processed`  (`processed_by`),
  KEY `idx_cft_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Complete construction financial transaction ledger — every money movement';


-- =============================================================================
-- 15. APPROVAL LOG  (Construction-specific multi-step approval trail)
--
-- NOTE: The MAIN approval still uses Phase 1 approval_requests table.
--       This table is for logging EACH STEP in construction workflows
--       where multiple approvers are needed (e.g. target_fixed needs
--       site_eng → qc → pc → pd → manager).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `con_approval_logs` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_table`   VARCHAR(100)     NOT NULL COMMENT 'Target table name (e.g. target_fixed)',
  `ref_id`      INT(11) UNSIGNED NOT NULL COMMENT 'PK of the target record',
  `step_name`   VARCHAR(100)     NOT NULL COMMENT 'e.g. site_engineer, qc_officer, manager',
  `actioned_by` INT(11)          NOT NULL COMMENT 'User who took the approval action → users.id',
  `status`      ENUM('approved','rejected') NOT NULL,
  `remarks`     TEXT             DEFAULT NULL,
  `actioned_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by`  INT(11)          NOT NULL COMMENT 'Session user who inserted this log row → users.id',

  PRIMARY KEY (`id`),
  KEY `idx_cal_ref`         (`ref_table`, `ref_id`),
  KEY `idx_cal_actioned_by` (`actioned_by`),
  KEY `idx_cal_created_by`  (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Step-by-step approval audit trail for multi-approver construction workflows';


-- =============================================================================
-- END OF PHASE 2 SCHEMA
-- =============================================================================
-- Total new tables : 34
-- Shared (Phase 1) : users, roles, role_permissions, projects,
--                    approval_requests, cashout_payments
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 1;


-- =============================================================================
-- APPROVAL FLOW REFERENCE
-- =============================================================================
--
-- HOW contractor_bill_requests USES approval_requests:
-- -------------------------------------------------------
-- INSERT INTO approval_requests SET
--   department     = 'construction',
--   request_type   = 'contractor_bill',
--   title          = 'Contractor Bill — [operator_name] | [project_name]',
--   source_table   = 'contractor_bill_requests',
--   source_id      = [contractor_bill_requests.id],
--   request_amount = [bill_amount],
--   requested_by   = [session user_id],
--   approve_role   = 'finance';
--
-- HOW operational_fund_requests USES approval_requests:
-- -------------------------------------------------------
-- INSERT INTO approval_requests SET
--   department     = 'construction',
--   request_type   = 'operational_fund',
--   title          = 'Fund Request — [purpose] | [project_name]',
--   source_table   = 'operational_fund_requests',
--   source_id      = [operational_fund_requests.id],
--   request_amount = [amount],
--   requested_by   = [session user_id],
--   approve_role   = 'management';
--
-- HOW vendor_payment_requests USES approval_requests:
-- -------------------------------------------------------
-- INSERT INTO approval_requests SET
--   department     = 'construction',
--   request_type   = 'vendor_payment',
--   title          = 'Vendor Payment — [vendor_name] | Invoice [invoice_number]',
--   source_table   = 'vendor_payment_requests',
--   source_id      = [vendor_payment_requests.id],
--   request_amount = [invoice_amount],
--   requested_by   = [session user_id],
--   approve_role   = 'finance';
--
-- AFTER APPROVAL:
--   → Update source table status = 'approved' / 'rejected'
--   → Copy approved_amount from approval_requests.approved_amount
--   → Record payment in cashout_payments (same Phase 1 table)
--
-- =============================================================================
-- TABLE SUMMARY
-- =============================================================================
--
-- PHASE 1 (existing — no change)
-- ────────────────────────────────────────────────────────────────────────────
-- users                        → all FK references
-- roles                        → construction roles added via INSERT
-- role_permissions             → construction permissions added
-- projects (+phase column)     → shared, phase = 'construction'
-- approval_requests            → all construction approvals land here
-- cashout_payments             → all construction payments land here
--
-- PHASE 2 (new tables)
-- ────────────────────────────────────────────────────────────────────────────
-- PRE-CONSTRUCTION:
--   land_visibility_study
--   architect_engineering
--   design_types
--   project_drawings
--   soil_tests
--
-- OPERATIONS:
--   operations_operators
--
-- INVENTORY:
--   con_materials
--   con_warehouses
--   con_stocks
--   con_stock_movements
--
-- BOQ:
--   boq_master
--   boq_items
--
-- WORKING PLAN:
--   project_working_plan
--   budget_extend_requests
--
-- PROCUREMENT:
--   vendors
--   purchase_requests
--   purchase_request_items
--   quotations
--   quotation_items
--   quotation_comparisons
--   comparison_items
--   purchase_orders
--   purchase_order_items
--
-- RECEIVING + QC:
--   material_receiving
--   material_receiving_items
--
-- VENDOR MANAGEMENT:
--   vendor_penalties
--   vendor_ratings
--
-- TARGETS:
--   target_fixed
--
-- SITE EXECUTION:
--   daily_progress_reports
--   material_usage_logs
--
-- PAYMENTS:
--   contractor_bill_requests    → approved via approval_requests
--   vendor_payment_requests     → approved via approval_requests
--   operational_fund_requests   → approved via approval_requests
--   con_financial_transactions
--   con_approval_logs
--
-- =============================================================================