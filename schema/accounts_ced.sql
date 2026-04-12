-- ============================================================
-- TPHL-ERP: Accounts & CED Feature Schema
-- Branch : features/accounts-ced-verification
-- Updated: 2026-04-12
-- ============================================================
-- Safe to re-run on an existing database:
--   • CREATE TABLE uses   IF NOT EXISTS
--   • ALTER TABLE uses    ADD COLUMN IF NOT EXISTS
--   • INSERT uses         INSERT IGNORE
-- Run each section in order (1 → 6).
-- ============================================================


-- ============================================================
-- 1. BANKS TABLE (Dynamic Bank Registry)
--    Stores bank branches selectable in payment forms.
-- ============================================================
CREATE TABLE IF NOT EXISTS `banks` (
    `id`             INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
    `bank_name`      VARCHAR(255)         NOT NULL,
    `branch_name`    VARCHAR(255)         NOT NULL,
    `routing_number` VARCHAR(150)         DEFAULT NULL,
    `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_by`     INT(11)              DEFAULT NULL,
    `created_at`     TIMESTAMP            NOT NULL DEFAULT current_timestamp(),
    `updated_at`     TIMESTAMP            NOT NULL DEFAULT current_timestamp()
                         ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Dynamic bank / branch registry used across payment forms';


-- ============================================================
-- 2. lead_payments — Financial Verification Gateway columns
--
--    Columns added this cycle (in the exact order they live in DB):
--      verification_status  — pending | verified | rejected
--      verified_by          — user ID of the Accounts officer
--      verified_at          — timestamp of the verification action
--      attachment_paths     — JSON array of proof-of-payment file paths
--      rejection_reason     — mandatory text when status = rejected
--      bank_id              — FK to banks table (bank transfer payments)
-- ============================================================

-- Core verification workflow columns
ALTER TABLE `lead_payments`
    ADD COLUMN IF NOT EXISTS `verification_status` ENUM('pending','verified','rejected')
        NOT NULL DEFAULT 'pending'
        COMMENT 'Financial gateway status — only verified payments hit fiscal reports'
        AFTER `created_at`,
    ADD COLUMN IF NOT EXISTS `verified_by` INT(11) DEFAULT NULL
        COMMENT 'User ID of the Accounts officer who verified/rejected'
        AFTER `verification_status`,
    ADD COLUMN IF NOT EXISTS `verified_at` TIMESTAMP DEFAULT NULL
        COMMENT 'Timestamp when the verification action was taken'
        AFTER `verified_by`;

-- Proof upload & rejection audit columns
ALTER TABLE `lead_payments`
    ADD COLUMN IF NOT EXISTS `attachment_paths` TEXT DEFAULT NULL
        COMMENT 'JSON array of proof-of-payment file paths (stored in /uploads/transactions/)'
        AFTER `verified_at`,
    ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT DEFAULT NULL
        COMMENT 'Mandatory reason text entered by Accounts when rejecting a payment'
        AFTER `attachment_paths`;

-- Bank Registry FK column
ALTER TABLE `lead_payments`
    ADD COLUMN IF NOT EXISTS `bank_id` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK → banks.id; populated when payment_method = bank'
        AFTER `bank_name`;

-- Due EMI amount (shown per-row on client cash-in history)
ALTER TABLE `lead_payments`
    ADD COLUMN IF NOT EXISTS `due_emi_amount` DECIMAL(12,2) DEFAULT NULL
        COMMENT 'Remaining due amount for this specific EMI instalment'
        AFTER `amount`;

-- Performance indexes for fiscal report queries
ALTER TABLE `lead_payments`
    ADD INDEX IF NOT EXISTS `idx_verification_status` (`verification_status`),
    ADD INDEX IF NOT EXISTS `idx_payment_method`      (`payment_method`);


-- ============================================================
-- 3. cashout_payments — Disbursement Proof & Bank Registry
--
--    Columns added this cycle:
--      bank_id           — FK to banks table (positioned after payment_method)
--      disbursement_proof — JSON array of proof file paths uploaded by Accounts
-- ============================================================
ALTER TABLE `cashout_payments`
    ADD COLUMN IF NOT EXISTS `bank_id` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK → banks.id; populated when payment_method = bank'
        AFTER `payment_method`,
    ADD COLUMN IF NOT EXISTS `disbursement_proof` TEXT DEFAULT NULL
        COMMENT 'JSON array of disbursement proof file paths uploaded by Accounts team'
        AFTER `online_type`;


-- ============================================================
-- 4. cashout_requests — Bank Registry FK
--
--    bank_id was added here too for consistency with request-level
--    payment method tracking.
-- ============================================================
ALTER TABLE `cashout_requests`
    ADD COLUMN IF NOT EXISTS `bank_id` INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK → banks.id; populated when payment_method = bank'
        AFTER `payment_method`;


-- ============================================================
-- 5. Legacy data migration
--    Mark all payments created BEFORE the verification gateway
--    as 'verified' so they continue to appear in fiscal reports.
--    Run ONCE on production after deploying the gateway.
-- ============================================================
UPDATE `lead_payments`
   SET `verification_status` = 'verified',
       `verified_at`          = `created_at`
 WHERE `verification_status`  = 'pending'
   AND `created_at`           < '2026-04-12 00:00:00';


-- ============================================================
-- 6. Seed: Default bank branches for immediate use
--    INSERT IGNORE skips rows that already exist (no duplicates).
-- ============================================================
INSERT IGNORE INTO `banks` (`bank_name`, `branch_name`, `routing_number`, `status`) VALUES
('Dutch-Bangla Bank',      'Principal Branch',     '090261539', 'active'),
('BRAC Bank',              'Gulshan Branch',        '060272482', 'active'),
('Pubali Bank',            'Gulshan Branch',        '185274630', 'active'),
('Islami Bank Bangladesh', 'Gulshan Branch',        '125274221', 'active'),
('Eastern Bank',           'Gulshan Branch',        '095271550', 'active'),
('Standard Chartered',     'Gulshan Branch',        '225274306', 'active'),
('Sonali Bank',            'Local Office Branch',   '200150376', 'active'),
('AB Bank',                'Principal Branch',      '015272116', 'active'),
('Agrani Bank',            'Principal Branch',      '010150376', 'active'),
('Janata Bank',            'Local Office Branch',   '135150376', 'active');
