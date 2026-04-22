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
--   5.  Working Plan & Budget     (project_working_plans, budget_extend_requests)
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
ALTER TABLE `projects` ADD `project_status` ENUM('Planning','In Progress','On Hold','Completed','Cancelled','Delayed') NULL DEFAULT NULL AFTER `project_sale_status`;

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
-- =============================================================================
-- PHASE 2 CONSTRUCTION MANAGEMENT SYSTEM SCHEMA
-- =============================================================================
-- start table --
SET FOREIGN_KEY_CHECKS = 0;

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
  `qc_reviewed_by_id`          INT(11)          DEFAULT NULL COMMENT 'FK users.id',
  `qc_approval_status`         ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `qc_reviewed_at`             TIMESTAMP        NULL DEFAULT NULL,
  `created_by`                 INT(11)          NOT NULL,
  `created_at`                 TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`                 TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lvs_project` (`project_id`),
  KEY `idx_lvs_qc` (`qc_reviewed_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `architect_engineering` (
  `id`                            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`                    INT(11)          NOT NULL,
  `user_id`                       INT(11)          NOT NULL COMMENT 'Internal architect FK users.id',
  `full_architecture_design_link` VARCHAR(500)     DEFAULT NULL,
  `notes`                         TEXT             DEFAULT NULL,
  `created_by`                    INT(11)          NOT NULL,
  `created_at`                    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ae_project`    (`project_id`),
  KEY `idx_ae_user`       (`user_id`),
  KEY `idx_ae_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `design_types` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)     NOT NULL,
  `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by` INT(11)          DEFAULT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_design_type_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `project_drawings` (
  `id`                  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`          INT(11)          NOT NULL,
  `design_type_id`      INT(11) UNSIGNED NOT NULL,
  `is_in_house`         TINYINT(1)       NOT NULL DEFAULT 1,
  `user_id`             INT(11)          DEFAULT NULL,
  `designer_name`       VARCHAR(150)     DEFAULT NULL,
  `designer_designation` VARCHAR(150)    DEFAULT NULL,
  `designer_company`    VARCHAR(150)     DEFAULT NULL,
  `approved_by_id`      INT(11)          DEFAULT NULL,
  `approval_date`       DATE             DEFAULT NULL,
  `design_link`         VARCHAR(500)     DEFAULT NULL,
  `version`             VARCHAR(20)      DEFAULT NULL,
  `status`              ENUM('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `created_by`          INT(11)          NOT NULL,
  `created_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pd_project`     (`project_id`),
  KEY `idx_pd_design_type` (`design_type_id`),
  KEY `idx_pd_user`        (`user_id`),
  KEY `idx_pd_approved_by` (`approved_by_id`),
  KEY `idx_pd_created_by`  (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `soil_tests` (
  `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`             INT(11)          NOT NULL,
  `site_engineer_id`       INT(11)          NOT NULL,
  `field_data`             TEXT             DEFAULT NULL,
  `lab_test_document_link` VARCHAR(500)     DEFAULT NULL,
  `test_date`              DATE             DEFAULT NULL,
  `created_by`             INT(11)          NOT NULL,
  `created_at`             TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_st_project`    (`project_id`),
  KEY `idx_st_engineer`   (`site_engineer_id`),
  KEY `idx_st_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- =============================================================================
-- 2. OPERATIONS OPERATORS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `contractors` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(255)     NOT NULL,
  `role`        ENUM('Contractor','Subcontractor','Supervisor','Worker') NOT NULL DEFAULT 'Contractor',
  `trade`       VARCHAR(150)     DEFAULT NULL,
  `phone`       VARCHAR(30)      DEFAULT NULL,
  `nid_number`  VARCHAR(50)      DEFAULT NULL,
  `address`     TEXT             DEFAULT NULL,
  `daily_rate`  DECIMAL(10,2)    DEFAULT NULL,
  `status`      ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_by`  INT(11)          NOT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_oo_role`   (`role`),
  KEY `idx_oo_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 3. MATERIALS & INVENTORY
-- =============================================================================

CREATE TABLE IF NOT EXISTS `con_materials` (
  `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(255)     NOT NULL,
  `unit`            VARCHAR(50)      NOT NULL,
  `min_stock_level` DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `created_by`      INT(11)          NOT NULL,
  `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `con_warehouses` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150)     NOT NULL,
  `project_id` INT(11)          DEFAULT NULL,
  `location`   VARCHAR(255)     DEFAULT NULL,
  `created_by` INT(11)          NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `con_stocks` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`      INT(11) UNSIGNED NOT NULL,
  `warehouse_id`     INT(11) UNSIGNED NOT NULL,
  `current_quantity` DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `updated_at`       TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stock_material_warehouse` (`material_id`, `warehouse_id`),
  KEY `idx_stock_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `con_stock_movements` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `material_id`  INT(11) UNSIGNED NOT NULL,
  `warehouse_id` INT(11) UNSIGNED NOT NULL,
  `type`         ENUM('IN','OUT') NOT NULL,
  `quantity`     DECIMAL(12,3)    NOT NULL,
  `ref_type`     VARCHAR(100)     DEFAULT NULL,
  `ref_id`       INT(11) UNSIGNED DEFAULT NULL,
  `moved_by`     INT(11)          DEFAULT NULL,
  `created_at`   TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sm_material`  (`material_id`),
  KEY `idx_sm_warehouse` (`warehouse_id`),
  KEY `idx_sm_ref`       (`ref_type`, `ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



--=============================================================================
-- 2.0. Initial Estimate Budgets From Estimator
--=============================================================================
-- ১. মেইন বাজেট সামারি টেবিল
CREATE TABLE IF NOT EXISTS `initial_estimate_budgets` (
  `id`                    INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`            INT(11)          NOT NULL,
  `architecture_id`       INT(11) UNSIGNED NOT NULL,
  `total_area_sqft`       DECIMAL(12,2)    DEFAULT NULL,
  `material_cost_total`   DECIMAL(15,2)    DEFAULT 0.00 COMMENT 'Sum of all design items',
  `labor_cost_total`      DECIMAL(15,2)    DEFAULT 0.00 COMMENT 'Sum of all design labors',
  `other_costs`           DECIMAL(15,2)    DEFAULT 0.00,
  `start_date`           Date Default Null,
  `end_date`          Date Default Null,
  `total_estimated_cost`  DECIMAL(15,2)    DEFAULT 0.00 COMMENT 'Grand Total',
  `status`                ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
  `approved_by`           INT(11)          NULL,
  `approval_date`         TIMESTAMP        NULL,
  `created_by`            INT(11)          NOT NULL,
  `created_at`            TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ২. মেটেরিয়াল আইটেম টেবিল (ডিজাইন ভিত্তিক)
CREATE TABLE IF NOT EXISTS `initial_estimate_items` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id`         INT(11) UNSIGNED NOT NULL,
  `design_type_id`    INT(11) UNSIGNED NOT NULL COMMENT '1=Construction, 2=Electrical, 3=Plumbing',
  `material_id`       INT(11) UNSIGNED NOT NULL,
  `material_unit`     VARCHAR(50)      NOT NULL,
  `estimated_qty`     DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `unit_cost_approx`  DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
  `subtotal`          DECIMAL(15,2)    AS (estimated_qty * unit_cost_approx) VIRTUAL,
  `remarks`           VARCHAR(255)     DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_iei_budget` FOREIGN KEY (`budget_id`) REFERENCES `initial_estimate_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ৩. লেবার কস্ট টেবিল (ডিজাইন ভিত্তিক)
CREATE TABLE IF NOT EXISTS `initial_estimate_labors` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `budget_id`        INT(11) UNSIGNED NOT NULL,
  `design_type_id`   INT(11) UNSIGNED NOT NULL COMMENT '1=Construction, 2=Electrical, 3=Plumbing',
  `labor_type_name`  VARCHAR(100)     NOT NULL,
  `rate_per_sqft`    DECIMAL(10,2)    NOT NULL DEFAULT 0.00,
  `area_sqft`        DECIMAL(12,2)    NOT NULL,
  `subtotal`         DECIMAL(15,2)    AS (rate_per_sqft * area_sqft) VIRTUAL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_iel_budget` FOREIGN KEY (`budget_id`) REFERENCES `initial_estimate_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-
-- =============================================================================
-- 5. WORKING PLAN & BUDGET CONTROL
-- =============================================================================

CREATE TABLE IF NOT EXISTS `project_working_plans` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`        INT(11)          NOT NULL,
  `initial_budget_id`         INT(11) UNSIGNED NOT NULL,
  `design_type_id`    INT(11) UNSIGNED NOT NULL COMMENT '1=Construction, 2=Electrical, etc.',
  `year`              SMALLINT UNSIGNED NOT NULL,
  `month`             TINYINT UNSIGNED  NOT NULL,
  
  -- কাজের বিবরণ যোগ করা হয়েছে
  `work_description`  TEXT             NOT NULL COMMENT 'কী কী কাজ করা হবে তার বিস্তারিত',
  
  `planned_quantity`  DECIMAL(12,3)    DEFAULT 0.000 COMMENT 'ঐ মাসে কতটুকু মেটেরিয়াল লাগবে (ঐচ্ছিক)',
  `estimated_cost`    DECIMAL(16,2)    NOT NULL DEFAULT 0.00 COMMENT 'ঐ মাসের আনুমানিক খরচ',
  
  `created_by`        INT(11)          NOT NULL,
  `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_pwp_budget` FOREIGN KEY (`initial_budget_id`) REFERENCES `initial_estimate_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `budget_extend_requests` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`   INT(11)          NOT NULL,
  `initial_budget_id` INT(11) UNSIGNED NOT NULL,
  `requested_by` INT(11)          NOT NULL,
  `title`        VARCHAR(255)     NOT NULL,
  `amount`       DECIMAL(16,4)    NOT NULL,
  -- নতুন যুক্ত করা কলামসমূহ
  `attachment_file`   VARCHAR(255)     DEFAULT NULL COMMENT 'File path for uploaded evidence (PDF/Images)',
  `docs_link`         VARCHAR(500)     DEFAULT NULL COMMENT 'External cloud link for supporting docs',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


- =============================================================================
-- 4. BILL OF QUANTITIES (BOQ)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `boq_master` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`  INT(11)          NOT NULL,
  `version`     VARCHAR(20)      NOT NULL DEFAULT '1.0',
  `status`      ENUM('Draft','Submitted','Approved','Rejected') NOT NULL DEFAULT 'Draft',
  `approved_by` INT(11)          DEFAULT NULL,
  `approved_at` TIMESTAMP        NULL DEFAULT NULL,
  `created_by`  INT(11)          NOT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_boq_project`     (`project_id`),
  KEY `idx_boq_approved_by` (`approved_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- =============================================================================
-- 6a. PROCUREMENT — VENDORS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vendors` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(255)     NOT NULL,
  `phone`      VARCHAR(30)      DEFAULT NULL,
  `company_name`      VARCHAR(30)      DEFAULT NULL,
  `trade_license_no` VARCHAR(255)     DEFAULT NULL,
  `tin_no` VARCHAR(255)     DEFAULT NULL,
  `email`      VARCHAR(191)     DEFAULT NULL,
  `address`    TEXT             DEFAULT NULL,
  `rating`     DECIMAL(3,2)     NOT NULL DEFAULT 5.00,
  `status`     ENUM('active','blacklisted') NOT NULL DEFAULT 'active',
  `created_by` INT(11)          NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vendor_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 6b. PURCHASE REQUESTS (PR)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT(11)          NOT NULL,
  `project_working_plans_id` INT(11) UNSIGNED NOT NULL,
  `created_by` INT(11)          NOT NULL,
  `status`     ENUM('Draft','Submitted','Approved','Rejected','PO Issued') NOT NULL DEFAULT 'Draft',
  `notes`      TEXT             DEFAULT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pr_project`    (`project_id`),
  KEY `idx_pr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `purchase_request_items` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`  INT(11) UNSIGNED NOT NULL,
  `material_id` INT(11) UNSIGNED NOT NULL,
  `quantity`    DECIMAL(12,3)    NOT NULL,
  `created_by`  INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pri_request`    (`request_id`),
  KEY `idx_pri_material`   (`material_id`),
  KEY `idx_pri_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 6c. QUOTATIONS / RFQ
-- =============================================================================

CREATE TABLE IF NOT EXISTS `quotations` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`    INT(11) UNSIGNED NOT NULL,
  `vendor_id`     INT(11) UNSIGNED NOT NULL,
  `validity_date` DATE             DEFAULT NULL,
  `status`        ENUM('Received','Shortlisted','Rejected') NOT NULL DEFAULT 'Received',
  `created_by`    INT(11)          NOT NULL,
  `created_at`    TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_q_request`    (`request_id`),
  KEY `idx_q_vendor`     (`vendor_id`),
  KEY `idx_q_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `quotation_items` (
  `id`           INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotation_id` INT(11) UNSIGNED NOT NULL,
  `material_id`  INT(11) UNSIGNED NOT NULL,
  `quantity`     DECIMAL(12,3)    NOT NULL,
  `unit_price`   DECIMAL(14,4)    NOT NULL,
  `total_price`  DECIMAL(16,4)    GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by`   INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qi_quotation`  (`quotation_id`),
  KEY `idx_qi_material`   (`material_id`),
  KEY `idx_qi_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 6d. COMPARATIVE STATEMENT (CST)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `quotation_comparisons` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`  INT(11) UNSIGNED NOT NULL,
  `prepared_by` INT(11)          NOT NULL,
  `created_at`  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_qc_request` (`request_id`),
  KEY `idx_qc_prepared_by` (`prepared_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `comparison_items` (
  `id`            INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `comparison_id` INT(11) UNSIGNED NOT NULL,
  `material_id`   INT(11) UNSIGNED NOT NULL,
  `vendor_id`     INT(11) UNSIGNED NOT NULL,
  `unit_price`    DECIMAL(14,4)    NOT NULL,
  `is_selected`   TINYINT(1)       NOT NULL DEFAULT 0,
  `created_by`    INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ci_comparison`  (`comparison_id`),
  KEY `idx_ci_material`    (`material_id`),
  KEY `idx_ci_vendor`      (`vendor_id`),
  KEY `idx_ci_created_by`  (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 6e. PURCHASE ORDERS (PO)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `purchase_orders` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) UNSIGNED NOT NULL,
  `created_by` INT(11)          NOT NULL,
  `status`     ENUM('Draft','Issued','Partially Received','Fully Received','Cancelled') NOT NULL DEFAULT 'Draft',
  `order_date` DATE             NOT NULL,
  `delivery_deadline` Date NOT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_po_request`    (`request_id`),
  KEY `idx_po_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `purchase_order_items` (
  `id`                 INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id`  INT(11) UNSIGNED NOT NULL,
  `material_id`        INT(11) UNSIGNED NOT NULL,
  `selected_vendor_id` INT(11) UNSIGNED NOT NULL,
  `quantity`           DECIMAL(12,3)    NOT NULL,
  `unit_price`         DECIMAL(14,4)    NOT NULL,
  `total_price`        DECIMAL(16,4)    GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `created_by`         INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_poi_po`         (`purchase_order_id`),
  KEY `idx_poi_material`   (`material_id`),
  KEY `idx_poi_vendor`     (`selected_vendor_id`),
  KEY `idx_poi_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 7. MATERIAL RECEIVING + QC
-- =============================================================================

CREATE TABLE IF NOT EXISTS `material_receiving` (
  `id`                INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `purchase_order_id` INT(11) UNSIGNED NOT NULL,
  `received_by`       INT(11)          NOT NULL,
  `received_date`     DATE             NOT NULL,
  `received_status`   ENUM('Full','Partial','Rejected') NOT NULL DEFAULT 'Partial',
  `site_eng_note`     TEXT             DEFAULT NULL,
  `site_eng_photo`    VARCHAR(500)     DEFAULT NULL,
  `qc_checked_by`     INT(11)          DEFAULT NULL,
  `qc_note`           TEXT             DEFAULT NULL,
  `qc_photo`          VARCHAR(500)     DEFAULT NULL,
  `overall_status`    ENUM('Pending QC','Accepted','Partially Accepted','Rejected') NOT NULL DEFAULT 'Pending QC',
  `created_by`        INT(11)          NOT NULL,
  `warehouse_id`      INT(11)          NOT NULL, 
  `created_at`        TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mr_po`            (`purchase_order_id`),
  KEY `idx_mr_received_by`   (`received_by`),
  KEY `idx_mr_qc_checked_by` (`qc_checked_by`),
  KEY `idx_mr_created_by`    (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `material_receiving_items` (
  `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `receiving_id`           INT(11) UNSIGNED NOT NULL,
  `purchase_order_item_id` INT(11) UNSIGNED NOT NULL,
  `ordered_quantity`       DECIMAL(12,3)    NOT NULL,
  `received_quantity`      DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `status`                 ENUM('Accepted','Partially Accepted','Rejected','Pending QC') NOT NULL DEFAULT 'Pending QC',
  `return_quantity`        DECIMAL(12,3)    NOT NULL DEFAULT 0.000,
  `penalty_applied`        TINYINT(1)       NOT NULL DEFAULT 0,
  `site_engineer_checked`  TINYINT(1)       NOT NULL DEFAULT 0,
  `qc_checked`             TINYINT(1)       NOT NULL DEFAULT 0,
  `issue_reason`           TEXT             DEFAULT NULL,
  `attachment`             VARCHAR(500)     DEFAULT NULL,
  `created_by`             INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_mri_receiving`  (`receiving_id`),
  KEY `idx_mri_poi`        (`purchase_order_item_id`),
  KEY `idx_mri_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 8. VENDOR PENALTY & RATING
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vendor_penalties` (
  `id`                     INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`              INT(11) UNSIGNED NOT NULL,
  `is_deducted_from_bill`        TINYINT(1)       NOT NULL DEFAULT 0,    
  `payment_request_id`      INT(11) UNSIGNED NOT NULL,
  `purchase_order_id`      INT(11) UNSIGNED NOT NULL,
  `receiving_id`           INT(11) UNSIGNED DEFAULT NULL COMMENT 'Link to specific delivery',
  `penalty_category`       ENUM('Late Delivery', 'Quality Issue', 'Shortage', 'Others') NOT NULL,
  `penalty_amount`         DECIMAL(14,2)    NOT NULL DEFAULT 0.00,
  `is_fixed_amount`        TINYINT(1)       DEFAULT 1 COMMENT '1=Fixed, 0=Percentage of bill',
  `reason`                 TEXT             NOT NULL,
  `action_taken`           ENUM('Deduct from Bill', 'Replacement Taken', 'Fine Paid') DEFAULT 'Deduct from Bill',
  `penalty_status`         ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',   
  `attachment`             VARCHAR(500)     DEFAULT NULL COMMENT 'Photo of bad material',
  `created_by`             INT(11)          NOT NULL,
  `created_at`             TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_vp_vendor` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `vendor_ratings` (
  `id`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`  INT(11) UNSIGNED NOT NULL,
  `rated_by`   INT(11)          NOT NULL,
  `rating`     DECIMAL(2,1)     NOT NULL,
  `reason`     TEXT             DEFAULT NULL,
  `created_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vr_vendor`   (`vendor_id`),
  KEY `idx_vr_rated_by` (`rated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `con_material_issues` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id`    INT NOT NULL,
  `target_id`     INT UNSIGNED NOT NULL,
  `work_plan_id`  INT UNSIGNED NOT NULL,
  `warehouse_id`  INT UNSIGNED NOT NULL,
  `material_id`   INT UNSIGNED NOT NULL,
  `quantity`      DECIMAL(12,3) NOT NULL,
  `issued_to`     VARCHAR(255) DEFAULT NULL COMMENT 'Person/contractor name',
  `purpose`       TEXT DEFAULT NULL,
  `issued_by`     INT NOT NULL,
  `issue_date`    DATE NOT NULL,
  `created_at`    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- 2. Items Table: Material Issue Items
CREATE TABLE IF NOT EXISTS `con_material_issue_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `issue_id` INT NOT NULL,
    `material_id` INT NOT NULL,
    `quantity` DECIMAL(15, 3) NOT NULL DEFAULT 0.000,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Foreign keys
    CONSTRAINT fk_items_issue_header FOREIGN KEY (issue_id) 
        REFERENCES `con_material_issues` (`id`) ON DELETE CASCADE
    -- CONSTRAINT fk_items_material FOREIGN KEY (material_id) REFERENCES con_materials(id)
)

-- =============================================================================
-- 9. TARGET FIXED (Weekly/Monthly QC Progress Targets)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `target_fixed` (
  `id`                   INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`           INT(11)          NOT NULL,
  `work_plan_id`         INT(11)          NOT NULL,
  `target_title`         VARCHAR(255)     NOT NULL,
  `user_id`              INT(11)          NOT NULL,
  `year`                 SMALLINT UNSIGNED NOT NULL,
  `month`                TINYINT UNSIGNED  NOT NULL,
  `week_number`          TINYINT UNSIGNED  DEFAULT NULL,
  `target_percentage`    DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
  `gain_percentage`      DECIMAL(5,2)     DEFAULT 0.00,
  `photo_link`           VARCHAR(500)     DEFAULT NULL,
  `field_test_details`   TEXT             DEFAULT NULL,
  `lab_test_result`      TEXT             DEFAULT NULL,
  `approved_by_site_eng` TINYINT(1)       NOT NULL DEFAULT 0,
  `approved_by_qc`       TINYINT(1)       NOT NULL DEFAULT 0,
  `approved_by_pc`       TINYINT(1)       NOT NULL DEFAULT 0,
  `approved_by_pd`       TINYINT(1)       NOT NULL DEFAULT 0,
  `approved_by_manager`  TINYINT(1)       NOT NULL DEFAULT 0,
  `qc_reviewed_by_id`    INT(11)          DEFAULT NULL,
  `qc_reviewed_at`       TIMESTAMP        NULL DEFAULT NULL,
  `created_at`           TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`           TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tf_project`        (`project_id`),
  KEY `idx_tf_user`           (`user_id`),
  KEY `idx_tf_qc_reviewed_by` (`qc_reviewed_by_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 10. SITE EXECUTION
-- =============================================================================

CREATE TABLE IF NOT EXISTS `daily_progress_reports` (
  `id`                  INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`          INT(11)          NOT NULL,
  `site_engineer_id`    INT(11)          NOT NULL,
  `work_done`           TEXT             NOT NULL,
  `progress_percentage` DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
  `report_date`         DATE             NOT NULL,
  `weather_condition`   VARCHAR(100)     DEFAULT NULL,
  `manpower_count`      SMALLINT UNSIGNED DEFAULT NULL,
  `created_by`          INT(11)          NOT NULL,
  `created_at`          TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dpr_project`    (`project_id`),
  KEY `idx_dpr_engineer`   (`site_engineer_id`),
  KEY `idx_dpr_date`       (`report_date`),
  KEY `idx_dpr_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



-- =============================================================================
-- 11. CONTRACTOR BILL REQUESTS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `contractor_bill_requests` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`       INT(11)          NOT NULL,
  `target_id`        INT(11)          NOT NULL,
  `operator_id`      INT(11) UNSIGNED NOT NULL,
  `created_by`       INT(11)          NOT NULL,
  `bill_date`        DATE             NOT NULL,
  `work_description` TEXT             NOT NULL,
  `bill_amount`      DECIMAL(15,2)    NOT NULL,
  `attachment_path`  VARCHAR(255)     DEFAULT NULL,
  `verified_by`      INT(11)          DEFAULT NULL,
  `verified_at`      TIMESTAMP        NULL DEFAULT NULL,
  `verification_note` TEXT            DEFAULT NULL,
  `approved_amount`  DECIMAL(15,2)    DEFAULT NULL,
  `status`           ENUM('draft','submitted','verified','approved','rejected','paid') NOT NULL DEFAULT 'draft',
  `created_at`       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cbr_project`    (`project_id`),
  KEY `idx_cbr_operator`   (`operator_id`),
  KEY `idx_cbr_created_by` (`created_by`),
  KEY `idx_cbr_verified_by` (`verified_by`),
  KEY `idx_cbr_status`     (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 12. VENDOR PAYMENT REQUESTS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `vendor_payment_requests` (
  `id`                 INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `vendor_id`          INT(11) UNSIGNED NOT NULL,
  `project_id`          INT(11) UNSIGNED NOT NULL,
  `purchase_order_id`  INT(11) UNSIGNED NOT NULL,
  `invoice_number`     VARCHAR(100)     DEFAULT NULL,
  `invoice_amount`     DECIMAL(16,4)    NOT NULL COMMENT 'Vendor actual bill',
  
  -- পেনাল্টি অ্যাডজাস্টমেন্ট
  `penalty_amount`     DECIMAL(16,4)    DEFAULT 0.0000 COMMENT 'Sum of all adjusted penalties',
  `net_payable_amount` DECIMAL(16,4)    DEFAULT 0.0000 COMMENT 'invoice_amount - penalty_amount',
  `payment_type` ENUM('Advance', 'Partial', 'Final') NOT NULL DEFAULT 'Final'
  `delivery_verified`  TINYINT(1)       NOT NULL DEFAULT 0,
  `qc_verified`        TINYINT(1)       NOT NULL DEFAULT 0,
  `status`             ENUM('Draft','Submitted','Approved','Rejected','Paid') NOT NULL DEFAULT 'Draft',
  `approved_amount`    DECIMAL(16,4)    DEFAULT NULL COMMENT 'Final amount approved by Accounts',
  `net_payable`    DECIMAL(16,4)    DEFAULT 0,
  
  `created_by`         INT(11)          NOT NULL,
  `created_at`         TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_vpr_vendor`     (`vendor_id`),
  KEY `idx_vpr_po`         (`purchase_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- =============================================================================
-- 13. OPERATIONAL FUND REQUESTS
-- =============================================================================

CREATE TABLE IF NOT EXISTS `operational_fund_requests` (
  `id`              INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`      INT(11)          NOT NULL,
  `requester_id`    INT(11)          NOT NULL,
  `operator_id`     INT(11) UNSIGNED DEFAULT NULL,
  `amount`          DECIMAL(15,2)    NOT NULL,
  `purpose`         TEXT             NOT NULL,
  `fund_date`       DATE             NOT NULL,
  `approved_amount` DECIMAL(15,2)    DEFAULT NULL,
  `status`          ENUM('pending','approved','rejected','paid') NOT NULL DEFAULT 'pending',
  `created_by`      INT(11)          NOT NULL,
  `created_at`      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP        NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ofr_project`     (`project_id`),
  KEY `idx_ofr_requester`   (`requester_id`),
  KEY `idx_ofr_operator`    (`operator_id`),
  KEY `idx_ofr_created_by`  (`created_by`),
  KEY `idx_ofr_status`      (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 14. FINANCIAL TRANSACTIONS (Complete Money Movement Ledger)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `con_financial_transactions` (
  `id`               INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_type`         ENUM('contractor_bill','operational_fund','vendor_payment') NOT NULL,
  `ref_id`           INT(11) UNSIGNED NOT NULL,
  `project_id`       INT(11) UNSIGNED NOT NULL,
  `payment_type`     ENUM('Advance','Partial','Full','Refund') NOT NULL,
  `payment_method`   ENUM('Cash','Cheque','BankTransfer','MobileWallet','Other') NOT NULL,
  `amount_paid`      DECIMAL(15,2)    NOT NULL,
  `reference_number` VARCHAR(150)     DEFAULT NULL,
  `processed_by`     INT(11)          DEFAULT NULL,
  `transaction_date` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `update_status`    ENUM('Completed','Failed','Pending') NOT NULL DEFAULT 'Pending',
  `notes`            TEXT             DEFAULT NULL,
  `created_by`       INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cft_ref`        (`ref_type`, `ref_id`),
  KEY `idx_cft_processed`  (`processed_by`),
  KEY `idx_cft_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =============================================================================
-- 15. APPROVAL LOG (Construction-specific multi-step approval trail)
-- =============================================================================

CREATE TABLE IF NOT EXISTS `con_approval_logs` (
  `id`          INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_table`   VARCHAR(100)     NOT NULL,
  `ref_id`      INT(11) UNSIGNED NOT NULL,
  `step_name`   VARCHAR(100)     NOT NULL,
  `actioned_by` INT(11)          NOT NULL,
  `status`      ENUM('approved','rejected') NOT NULL,
  `remarks`     TEXT             DEFAULT NULL,
  `actioned_at` TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by`  INT(11)          NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cal_ref`         (`ref_table`, `ref_id`),
  KEY `idx_cal_actioned_by` (`actioned_by`),
  KEY `idx_cal_created_by`  (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1

--- =========== END TABLE ======= -----
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
--   contractors
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