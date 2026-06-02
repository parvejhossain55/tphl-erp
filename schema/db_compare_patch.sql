-- easy-nirmanX Database Patch Script
-- Generated to update local database (tphl_db2.sql) to match live server database (tphl_db.sql)
-- Generated on: 2026-05-24 07:35:53

SET FOREIGN_KEY_CHECKS = 0;

-- =========================================================================
-- MISSING TABLES TO BE CREATED
-- =========================================================================

-- Table: adjustment_transactions
CREATE TABLE `adjustment_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_id` int unsigned NOT NULL COMMENT 'FK to expense_adjustments.id',
  `transaction_type` enum('cashin','cashout') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Cashin for Returns, Cashout for Extras',
  `amount` decimal(15,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `transaction_ref` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_id` int DEFAULT NULL,
  `bank_account_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cheque_no` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `online_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `disbursement_proof` text COLLATE utf8mb4_general_ci COMMENT 'JSON array of paths',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_adjustment` (`adjustment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
;

-- Table: expense_adjustments
CREATE TABLE `expense_adjustments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'User who is making the adjustment request',
  `cashout_request_id` int NOT NULL COMMENT 'FK to cashout_requests.id',
  `adjustment_type` enum('return','extra') COLLATE utf8mb4_general_ci NOT NULL COMMENT 'Return unused money or request extra money',
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `approved_amount` decimal(15,2) DEFAULT NULL,
  `due_amount` decimal(15,2) DEFAULT NULL COMMENT 'Amount remaining to be paid/received',
  `document` text COLLATE utf8mb4_general_ci COMMENT 'JSON array of paths',
  `note` text COLLATE utf8mb4_general_ci,
  `project_id` int DEFAULT NULL,
  `office` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected','partially_paid','fully_paid') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_cashout` (`cashout_request_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
;

-- =========================================================================
-- MISSING COLUMNS TO BE ADDED
-- =========================================================================

-- Table: cashout_requests
ALTER TABLE `cashout_requests` ADD COLUMN `qc_approved_by_id` int DEFAULT NULL;
ALTER TABLE `cashout_requests` ADD COLUMN `qc_approved_at` datetime DEFAULT NULL;
ALTER TABLE `cashout_requests` ADD COLUMN `qc_note` text COLLATE utf8mb4_general_ci;
ALTER TABLE `cashout_requests` ADD COLUMN `management_note` text COLLATE utf8mb4_general_ci;

-- Table: contractor_bill_requests
ALTER TABLE `contractor_bill_requests` ADD COLUMN `qc_approved_by_id` int DEFAULT NULL;
ALTER TABLE `contractor_bill_requests` ADD COLUMN `qc_approved_at` datetime DEFAULT NULL;
ALTER TABLE `contractor_bill_requests` ADD COLUMN `qc_note` text COLLATE utf8mb4_general_ci;
ALTER TABLE `contractor_bill_requests` ADD COLUMN `management_note` text COLLATE utf8mb4_general_ci;

-- Table: lead_bookings
ALTER TABLE `lead_bookings` ADD COLUMN `transfer_status` tinyint(1) DEFAULT '0';
ALTER TABLE `lead_bookings` ADD COLUMN `transfer_date` date DEFAULT NULL;
ALTER TABLE `lead_bookings` ADD COLUMN `transfer_note` text COLLATE utf8mb4_general_ci;
ALTER TABLE `lead_bookings` ADD COLUMN `transfer_updated_by` int DEFAULT NULL;

-- Table: lead_payments
ALTER TABLE `lead_payments` ADD COLUMN `bulk_pay_amount` decimal(12,2) DEFAULT '0.00';

-- Table: purchase_requests
ALTER TABLE `purchase_requests` ADD COLUMN `qc_approved_by_id` int DEFAULT NULL;
ALTER TABLE `purchase_requests` ADD COLUMN `qc_approved_at` datetime DEFAULT NULL;
ALTER TABLE `purchase_requests` ADD COLUMN `qc_note` text COLLATE utf8mb4_general_ci;
ALTER TABLE `purchase_requests` ADD COLUMN `management_note` text COLLATE utf8mb4_general_ci;
ALTER TABLE `purchase_requests` ADD COLUMN `management_approved_amount` decimal(15,2) DEFAULT NULL;

-- Table: vendor_payment_requests
ALTER TABLE `vendor_payment_requests` ADD COLUMN `qc_approved_by_id` int DEFAULT NULL;
ALTER TABLE `vendor_payment_requests` ADD COLUMN `qc_approved_at` datetime DEFAULT NULL;
ALTER TABLE `vendor_payment_requests` ADD COLUMN `qc_note` text COLLATE utf8mb4_general_ci;
ALTER TABLE `vendor_payment_requests` ADD COLUMN `management_note` text COLLATE utf8mb4_general_ci;

-- Table: weekly_progress_report
ALTER TABLE `weekly_progress_report` ADD COLUMN `qc_gain_percentage` decimal(5,2) DEFAULT '0.00';
ALTER TABLE `weekly_progress_report` ADD COLUMN `qc_photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL;
ALTER TABLE `weekly_progress_report` ADD COLUMN `qc_field_test_details` text COLLATE utf8mb4_general_ci;
ALTER TABLE `weekly_progress_report` ADD COLUMN `qc_lab_test_result` text COLLATE utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
