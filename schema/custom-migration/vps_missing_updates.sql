-- =================================================================================
-- Missing SQL Queries for VPS Migration
-- Generated from analysis of project SQL files compared to tphl_db.sql
-- =================================================================================

-- --------------------------------------------------------
-- From: expense_adjustments.sql
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `expense_adjustments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'User who is making the adjustment request',
  `cashout_request_id` int(11) NOT NULL COMMENT 'FK to cashout_requests.id',
  `adjustment_type` enum('return', 'extra') NOT NULL COMMENT 'Return unused money or request extra money',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `due_amount` decimal(15,2) DEFAULT NULL COMMENT 'Amount remaining to be paid/received',
  `document` text DEFAULT NULL COMMENT 'JSON array of paths',
  `note` text DEFAULT NULL,
  `project_id` int(11) DEFAULT NULL,
  `office` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','partially_paid','fully_paid') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_cashout` (`cashout_request_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `adjustment_transactions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_id` int(11) unsigned NOT NULL COMMENT 'FK to expense_adjustments.id',
  `transaction_type` enum('cashin', 'cashout') NOT NULL COMMENT 'Cashin for Returns, Cashout for Extras',
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `transaction_ref` varchar(255) DEFAULT NULL,
  `bank_id` int(11) DEFAULT NULL,
  `bank_account_no` varchar(100) DEFAULT NULL,
  `cheque_no` varchar(100) DEFAULT NULL,
  `online_type` varchar(50) DEFAULT NULL,
  `disbursement_proof` text DEFAULT NULL COMMENT 'JSON array of paths',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_adjustment` (`adjustment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- From: refund_module_queries.sql
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `client_refund_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL COMMENT 'FK → lead_bookings.id',
  `lead_id` int(10) unsigned NOT NULL COMMENT 'FK → leads.id',
  `project_id` int(11) NOT NULL COMMENT 'FK → projects.id',
  `flat_id` int(11) NOT NULL COMMENT 'FK → flats.id',
  `total_paid` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Sum of all verified lead_payments for this booking',
  `retention_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Company charges that cannot be refunded',
  `refund_amount` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'total_paid - retention_amount',
  `reason` text DEFAULT NULL COMMENT 'Free-text refund reason',
  `document` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','refunded') NOT NULL DEFAULT 'pending',
  `cashout_request_id` int(11) DEFAULT NULL COMMENT 'FK → cashout_requests.id (created on approval)',
  `requested_by` int(11) NOT NULL COMMENT 'User who initiated the refund (Accounts)',
  `approved_by` int(11) DEFAULT NULL COMMENT 'User who approved/rejected (Management)',
  `approved_at` datetime DEFAULT NULL,
  `refunded_at` datetime DEFAULT NULL COMMENT 'Timestamp when cashout was fully disbursed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_lead` (`lead_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- From: accounts_ced.sql
-- --------------------------------------------------------
ALTER TABLE `lead_bookings`
ADD COLUMN `transfer_date` DATE NULL DEFAULT NULL AFTER `service_fee_balance`,
ADD COLUMN `transfer_note` TEXT NULL DEFAULT NULL AFTER `transfer_date`,
ADD COLUMN `transfer_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = Current, 1 = Transferred' AFTER `transfer_note`,
ADD COLUMN `transfer_updated_by` INT(11) NULL DEFAULT NULL AFTER `transfer_status`;

-- --------------------------------------------------------
-- From: csv_import_logs.sql
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `csv_import_logs` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `total_rows` int(11) NOT NULL DEFAULT 0,
  `success_count` int(11) NOT NULL DEFAULT 0,
  `error_count` int(11) NOT NULL DEFAULT 0,
  `error_details` longtext DEFAULT NULL COMMENT 'JSON array of row-level errors',
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- From: live_server_migration.sql
-- --------------------------------------------------------
ALTER TABLE `projects`
  ADD COLUMN `pm_id`              int(11) DEFAULT NULL,
  ADD COLUMN `site_engineer_id`   int(11) DEFAULT NULL,
  ADD COLUMN `landman_id`         int(11) DEFAULT NULL,
  ADD COLUMN `struct_engineer_id` int(11) DEFAULT NULL,
  ADD COLUMN `operation_head_id`  int(11) DEFAULT NULL,
  ADD COLUMN `draftsman_id`       int(11) DEFAULT NULL,
  ADD COLUMN `architect_id`       int(11) DEFAULT NULL,
  ADD COLUMN `estimator_id`       int(11) DEFAULT NULL,
  ADD COLUMN `qc_id`              int(11) DEFAULT NULL,
  ADD COLUMN `operator_id`        int(11) DEFAULT NULL;

ALTER TABLE `initial_estimate_budgets`
  ADD COLUMN `work_type_id`      bigint(20) UNSIGNED DEFAULT NULL,
  ADD COLUMN `budget_link`       varchar(255)        DEFAULT NULL,
  ADD COLUMN `work_details`      text                DEFAULT NULL,
  ADD COLUMN `other_cost_detail` text                DEFAULT NULL;

ALTER TABLE `initial_estimate_items`
  ADD COLUMN `work_type_id` bigint(20) UNSIGNED DEFAULT NULL;

ALTER TABLE `initial_estimate_labors`
  ADD COLUMN `work_type_id` bigint(20) UNSIGNED DEFAULT NULL;

ALTER TABLE `architect_engineering`
  ADD COLUMN `status` enum('draft','partially','fully') DEFAULT 'draft';

ALTER TABLE `boq_master`
  ADD COLUMN `target_id`      int(11) DEFAULT NULL,
  ADD COLUMN `design_type_id` int(11) DEFAULT NULL;

ALTER TABLE `con_financial_transactions`
  ADD COLUMN `transaction_date_str` date DEFAULT NULL,
  ADD COLUMN `disbursement_proof`   text DEFAULT NULL;

ALTER TABLE `lead_follow_ups`
  ADD COLUMN `follow_up_agent_id`        int(11)      DEFAULT NULL,
  ADD COLUMN `lead_status`               varchar(100) DEFAULT NULL,
  ADD COLUMN `next_follow_up_plan_notes` text         DEFAULT NULL;

ALTER TABLE `lead_payments`
  ADD COLUMN `due_emi_amount` decimal(12,2) DEFAULT 0.00;

ALTER TABLE `material_receiving`
  ADD COLUMN `pm_checked_by`  int(11)  DEFAULT NULL,
  ADD COLUMN `pm_note`        text     DEFAULT NULL,
  ADD COLUMN `pm_approved_at` datetime DEFAULT NULL;

ALTER TABLE `purchase_requests`
  ADD COLUMN `bom_id` int(11) DEFAULT NULL;

ALTER TABLE `target_fixed`
  ADD COLUMN `is_lab_test`    int(11) NOT NULL DEFAULT 0,
  ADD COLUMN `lab_test_date`  date    DEFAULT NULL,
  ADD COLUMN `target_details` text    DEFAULT NULL;

ALTER TABLE `weekly_progress_report`
  ADD COLUMN `approved_by_pm` int(11) DEFAULT NULL,
  ADD COLUMN `approved_by_qc` int(11) DEFAULT NULL;

-- --------------------------------------------------------
-- From: my-table-change.sql
-- --------------------------------------------------------
ALTER TABLE con_work_orders 
  ADD COLUMN description TEXT NULL AFTER title,
  ADD COLUMN notes TEXT NULL AFTER scope_of_work,
  ADD COLUMN rejection_reason TEXT NULL AFTER status,
  ADD COLUMN qc_approved_by INT NULL AFTER approved_at,
  ADD COLUMN qc_approved_at DATETIME NULL AFTER qc_approved_by,
  ADD COLUMN completed_by INT NULL AFTER qc_approved_at,
  ADD COLUMN completed_at DATETIME NULL AFTER completed_by;

-- --------------------------------------------------------
-- From: land-dept.sql
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS land_acquisitions (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    project_id       INT NOT NULL,
    land_owner_name  VARCHAR(255) NOT NULL,
    land_location    TEXT NOT NULL,
    land_area        DECIMAL(10,4) DEFAULT 0,
    land_area_unit   VARCHAR(50) DEFAULT 'Shotangsh',
    total_land_value DECIMAL(15,2) DEFAULT 0,
    status           ENUM('Selected','Bayana','Registered','Cancelled') DEFAULT 'Selected',
    notes            TEXT,
    created_by       INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS land_bayana_payments (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    bayana_amount       DECIMAL(15,2) NOT NULL,
    payment_date        DATE NOT NULL,
    payment_method      VARCHAR(100),
    receipt_no          VARCHAR(100),
    attachment          VARCHAR(500),
    status              ENUM('Pending','Paid') DEFAULT 'Pending',
    created_by          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS land_registrations (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    registration_no     VARCHAR(200),
    registration_date   DATE,
    registration_cost   DECIMAL(15,2) DEFAULT 0,
    govt_fees           DECIMAL(15,2) DEFAULT 0,
    stamp_duty          DECIMAL(15,2) DEFAULT 0,
    lawyer_fees         DECIMAL(15,2) DEFAULT 0,
    other_costs         DECIMAL(15,2) DEFAULT 0,
    document_link       VARCHAR(500),
    status              ENUM('Pending','Completed') DEFAULT 'Pending',
    created_by          INT NOT NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS land_fund_requisitions (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    land_acquisition_id INT NOT NULL,
    requisition_type    ENUM('Bayana','Registration','Extra Cost') NOT NULL,
    requested_amount    DECIMAL(15,2) NOT NULL,
    purpose             TEXT NOT NULL,
    requested_by        INT NOT NULL,
    accounts_status     ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    accounts_by         INT,
    accounts_note       TEXT,
    ced_status          ENUM('Pending','Seen','Noted') DEFAULT 'Pending',
    ced_by              INT,
    approved_amount     DECIMAL(15,2),
    approved_at         TIMESTAMP NULL,
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS land_monthly_reports (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    project_id       INT NOT NULL,
    report_month     VARCHAR(20) NOT NULL,
    total_lands      INT DEFAULT 0,
    total_area       DECIMAL(10,4) DEFAULT 0,
    total_bayana     DECIMAL(15,2) DEFAULT 0,
    total_registered DECIMAL(15,2) DEFAULT 0,
    total_cost       DECIMAL(15,2) DEFAULT 0,
    summary_notes    TEXT,
    created_by       INT NOT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
