-- ============================================================
-- Migration: Add target_id to contractor_bill_requests
-- + Create con_financial_transactions table
-- + Create con_approval_logs table
-- Date: 2026-04-20
-- ============================================================

-- 1. Add target_id to contractor_bill_requests (if not exists)
ALTER TABLE `contractor_bill_requests`
    ADD COLUMN IF NOT EXISTS `target_id` INT(11) NULL DEFAULT NULL
        AFTER `operator_id`,
    ADD CONSTRAINT `fk_cbr_target_id`
        FOREIGN KEY (`target_id`) REFERENCES `target_fixed`(`id`)
        ON DELETE SET NULL ON UPDATE CASCADE;

-- 2. Create con_financial_transactions
CREATE TABLE IF NOT EXISTS `con_financial_transactions` (
    `id`             INT(11) NOT NULL AUTO_INCREMENT,
    `ref_type`       ENUM('contractor_bill','operational_fund','vendor_payment') NOT NULL,
    `ref_id`         INT(11) NOT NULL,
    `project_id`     INT(11) NULL DEFAULT NULL,
    `amount`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('Cash','Cheque','BankTransfer','MobileWallet') NOT NULL DEFAULT 'Cash',
    `payment_date`   DATE NOT NULL,
    `reference_no`   VARCHAR(100) NULL DEFAULT NULL COMMENT 'cheque no, txn id etc.',
    `notes`          TEXT NULL DEFAULT NULL,
    `processed_by`   INT(11) NULL DEFAULT NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ref` (`ref_type`, `ref_id`),
    INDEX `idx_project` (`project_id`),
    INDEX `idx_payment_date` (`payment_date`),
    CONSTRAINT `fk_cft_project`
        FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cft_processed_by`
        FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Create con_approval_logs
CREATE TABLE IF NOT EXISTS `con_approval_logs` (
    `id`           INT(11) NOT NULL AUTO_INCREMENT,
    `ref_table`    VARCHAR(100) NOT NULL COMMENT 'e.g. target_fixed, contractor_bill_requests',
    `ref_id`       INT(11) NOT NULL,
    `step_name`    VARCHAR(100) NOT NULL COMMENT 'e.g. site_eng, qc, verified, approved',
    `actioned_by`  INT(11) NULL DEFAULT NULL,
    `status`       ENUM('approved','rejected','noted') NOT NULL DEFAULT 'approved',
    `remarks`      TEXT NULL DEFAULT NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ref` (`ref_table`, `ref_id`),
    INDEX `idx_actioned_by` (`actioned_by`),
    CONSTRAINT `fk_cal_actioned_by`
        FOREIGN KEY (`actioned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
