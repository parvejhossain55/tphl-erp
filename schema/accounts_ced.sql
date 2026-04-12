-- ============================================================
-- TPHL-ERP: Accounts & CED Feature Schema
-- Branch: features/accounts-ced-verification
-- Generated: 2026-04-12
-- ============================================================
-- Run each block sequentially.
-- All changes are backward-compatible (existing data is preserved).
-- ============================================================


-- ============================================================
-- 1. BANKS TABLE (Dynamic Bank Registry)
-- ============================================================
CREATE TABLE IF NOT EXISTS `banks` (
    `id`             INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `bank_name`      VARCHAR(255)     NOT NULL,
    `branch_name`    VARCHAR(255)     NOT NULL,
    `routing_number` VARCHAR(150)     DEFAULT NULL,
    `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_by`     INT(11)          DEFAULT NULL,
    `created_at`     TIMESTAMP        NOT NULL DEFAULT current_timestamp(),
    `updated_at`     TIMESTAMP        NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- 2. lead_payments — Multi-Document Proof Upload
--    verification_status already exists (pending/verified/rejected)
--    Adding:  attachment_paths  — JSON array of uploaded file paths
--             bank_id           — FK reference to banks table for bank payments
-- ============================================================
ALTER TABLE `lead_payments`
    ADD COLUMN IF NOT EXISTS `attachment_paths` TEXT DEFAULT NULL
        COMMENT 'JSON array of proof-of-payment file paths'
        AFTER `verified_at`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT DEFAULT NULL
        COMMENT 'Reason provided by Accounts when rejecting a payment'
        AFTER `attachment_paths`,
    ADD COLUMN IF NOT EXISTS `bank_id` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK to banks table when payment_method = bank'
        AFTER `bank_name`;

-- Index to speed up verification_status queries on fiscal reports
ALTER TABLE `lead_payments`
    ADD INDEX IF NOT EXISTS `idx_verification_status` (`verification_status`),
    ADD INDEX IF NOT EXISTS `idx_payment_method`        (`payment_method`);


-- ============================================================
-- 3. cashout_requests — Mandatory disbursement fields
--    Adding:  disbursement_proof  — path of the sent-payment document
--             payment_giving_time — when Accounts commits to disburse
--             bank_id             — FK reference to banks table
-- ============================================================
ALTER TABLE `cashout_requests`
    ADD COLUMN IF NOT EXISTS `payment_giving_time` DATETIME   DEFAULT NULL
        COMMENT 'Scheduled disbursement date & time set by Accounts'
        AFTER `approved_at`,
    ADD COLUMN IF NOT EXISTS `disbursement_proof`  VARCHAR(500) DEFAULT NULL
        COMMENT 'Proof document attached upon payment completion'
        AFTER `payment_giving_time`,
    ADD COLUMN IF NOT EXISTS `bank_id`             INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK to banks table when payment_method = bank'
        AFTER `payment_method`;


-- ============================================================
-- 4. cashout_payments — match same bank_id field
-- ============================================================
ALTER TABLE `cashout_payments`
    ADD COLUMN IF NOT EXISTS `bank_id` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK to banks table'
        AFTER `payment_method`;


-- ============================================================
-- 5. Seed a couple of well-known banks for immediate use
-- ============================================================
INSERT IGNORE INTO `banks` (`bank_name`, `branch_name`, `routing_number`, `status`) VALUES
('Dutch-Bangla Bank',     'Principal Branch',   '090261539', 'active'),
('BRAC Bank',             'Gulshan Branch',     '060272482', 'active'),
('Pubali Bank',           'Gulshan Branch',     '185274630', 'active'),
('Islami Bank Bangladesh','Gulshan Branch',     '125274221', 'active'),
('Eastern Bank',          'Gulshan Branch',     '095271550', 'active'),
('Standard Chartered',    'Gulshan Branch',     '225274306', 'active'),
('Sonali Bank',           'Local Office Branch','200150376', 'active'),
('AB Bank',               'Principal Branch',   '015272116', 'active');
