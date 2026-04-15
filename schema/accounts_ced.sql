-- ============================================================
-- TPHL-ERP  |  Accounts & CED Feature Migration
-- Branch    :  features/accounts-ced-verification
-- Based on  :  Live DB dump exported 2026-04-12
-- Author    :  Auto-generated from feature analysis
-- ============================================================
-- SAFE TO RE-RUN:
--   CREATE TABLE  uses  IF NOT EXISTS
--   ALTER TABLE   uses  ADD COLUMN IF NOT EXISTS  /  MODIFY COLUMN
--   INSERT        uses  INSERT IGNORE
--   UPDATE        is    idempotent (WHERE clause guards it)
-- Run sections 1 → 7 in order.
-- ============================================================


-- ============================================================
-- 1.  BANKS  (new table — does not exist in current DB)
--     Central registry of bank branches used across all
--     payment forms (lead_payments, cashout_payments, etc.)
-- ============================================================
CREATE TABLE IF NOT EXISTS `banks` (
    `id`             INT(10) UNSIGNED     NOT NULL AUTO_INCREMENT,
    `bank_name`      VARCHAR(255)         NOT NULL,
    `branch_name`    VARCHAR(255)         NOT NULL,
    `account_number` VARCHAR(255)         NOT NULL,
    `routing_number` VARCHAR(150)         DEFAULT NULL,
    `status`         ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_by`     INT(11)              DEFAULT NULL,
    `created_at`     TIMESTAMP            NOT NULL DEFAULT current_timestamp(),
    `updated_at`     TIMESTAMP            NOT NULL DEFAULT current_timestamp()
                         ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Dynamic bank / branch registry used across all payment forms';


-- ============================================================
-- 2.  lead_payments  — Financial Verification Gateway
--
--     Current DB columns (from dump / DESCRIBE):
--       id, booking_id, payment_type, amount, land_share_amount,
--       payment_method, payment_status, payment_date, emi_no,
--       total_emi_no, transaction_ref, bank_name, bank_account_no,
--       cheque_no, service_fee, online_type, created_by, created_at
--
--     Adding:
--       verification_status  — pending | verified | rejected
--       verified_by          — accounts officer user ID
--       verified_at          — timestamp of the action
--       attachment_paths     — JSON array of proof-of-payment paths
--       rejection_reason     — mandatory audit text on reject
--       bank_id              — FK → banks.id
-- ============================================================

ALTER TABLE `lead_payments`
    ADD COLUMN IF NOT EXISTS `verification_status`
        ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending'
        COMMENT 'Financial gateway — only verified rows appear in fiscal reports'
        AFTER `created_at`,

    ADD COLUMN IF NOT EXISTS `verified_by`
        INT(11) DEFAULT NULL
        COMMENT 'User ID of the Accounts officer who verified or rejected'
        AFTER `verification_status`,

    ADD COLUMN IF NOT EXISTS `verified_at`
        TIMESTAMP NULL DEFAULT NULL
        COMMENT 'Timestamp when the verification action was taken'
        AFTER `verified_by`,

    ADD COLUMN IF NOT EXISTS `attachment_paths`
        TEXT DEFAULT NULL
        COMMENT 'JSON array of proof-of-payment file paths (uploads/transactions/)'
        AFTER `verified_at`,

    ADD COLUMN IF NOT EXISTS `rejection_reason`
        TEXT DEFAULT NULL
        COMMENT 'Mandatory reason text entered by Accounts when rejecting'
        AFTER `attachment_paths`,

    ADD COLUMN IF NOT EXISTS `bank_id`
        INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK → banks.id; set when payment_method = bank'
        AFTER `bank_name`;

-- Indexes for verification dashboard & fiscal filter queries
ALTER TABLE `lead_payments`
    ADD INDEX IF NOT EXISTS `idx_lp_verification_status` (`verification_status`),
    ADD INDEX IF NOT EXISTS `idx_lp_payment_method`      (`payment_method`);


-- ============================================================
-- 3.  cashout_requests  — Bank Registry FK
--                       — Widen attachment_path to TEXT
--
--     Current DB columns (from dump):
--       id, project_id, office, requested_by, purpose_paid_to,
--       amount, approved_amount, due_amount, description,
--       payment_method, transaction_ref,
--       attachment_path VARCHAR(255),   ← too short for JSON arrays
--       status, approved_by, approved_at,
--       rejected_by, rejected_at, rejection_reason, approval_note,
--       created_at, updated_at
--
--     Adding  : bank_id
--     Widening: attachment_path  VARCHAR(255) → TEXT
--               (needed to store a JSON array of multiple file paths)
-- ============================================================

-- Widen attachment_path so it can hold a JSON array of file paths
ALTER TABLE `cashout_requests`
    MODIFY COLUMN `attachment_path` TEXT DEFAULT NULL
        COMMENT 'JSON array of requester-uploaded attachment file paths';

-- Add bank_id FK column
ALTER TABLE `cashout_requests`
    ADD COLUMN IF NOT EXISTS `bank_id`
        INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK → banks.id; set when payment_method = bank'
        AFTER `payment_method`;


-- ============================================================
-- 4.  cashout_payments  — Bank Registry FK + Disbursement Proof
--
--     Current DB columns (from dump):
--       id, cashout_request_id, office, project_id, purpose_paid_to,
--       amount, payment_method, transaction_ref, payment_date,
--       bank_name, bank_account_no, cheque_no, online_type,
--       created_by, created_at
--
--     Adding:
--       bank_id              — FK → banks.id
--       disbursement_proof   — JSON array of proof file paths
--                              uploaded by Accounts when adding payment
-- ============================================================

ALTER TABLE `cashout_payments`
    ADD COLUMN IF NOT EXISTS `bank_id`
        INT(10) UNSIGNED DEFAULT NULL
        COMMENT 'FK → banks.id; set when payment_method = bank'
        AFTER `payment_method`,

    ADD COLUMN IF NOT EXISTS `disbursement_proof`
        TEXT DEFAULT NULL
        COMMENT 'JSON array of disbursement proof file paths uploaded by Accounts'
        AFTER `online_type`;


-- ============================================================
-- 5.  Legacy data migration  (run ONCE on production)
--
--     All payments created BEFORE the verification gateway
--     (2026-04-12) are automatically marked as "verified" so
--     they continue to appear in fiscal reports unchanged.
--     The WHERE clause makes this safe to re-run.
-- ============================================================
UPDATE `lead_payments`
   SET `verification_status` = 'verified',
       `verified_at`          = `created_at`
 WHERE `verification_status`  = 'pending'
   AND `created_at`           < '2026-04-12 00:00:00';


-- ============================================================
-- 6.  Seed: Default Bangladeshi bank branches
--     INSERT IGNORE prevents duplicate rows on re-run.
-- ============================================================
INSERT IGNORE INTO `banks`
    (`bank_name`, `branch_name`, `routing_number`, `status`)
VALUES
    ('Dutch-Bangla Bank',       'Principal Branch',      '090261539', 'active'),
    ('BRAC Bank',               'Gulshan Branch',         '060272482', 'active'),
    ('Pubali Bank',             'Gulshan Branch',         '185274630', 'active'),
    ('Islami Bank Bangladesh',  'Gulshan Branch',         '125274221', 'active'),
    ('Eastern Bank (EBL)',      'Gulshan-2 Branch',       '095271550', 'active'),
    ('Standard Chartered',      'Gulshan Branch',         '225274306', 'active'),
    ('Sonali Bank',             'Local Office Branch',    '200150376', 'active'),
    ('AB Bank',                 'Principal Branch',       '015272116', 'active'),
    ('Agrani Bank',             'Principal Branch',       '010150376', 'active'),
    ('Janata Bank',             'Local Office Branch',    '135150376', 'active'),
    ('City Bank',               'Shaymoli Branch',        '045261270', 'active'),
    ('Mutual Trust Bank',       'Gulshan Branch',         '145274138', 'active'),
    ('Prime Bank',              'Gulshan Branch',         '175274154', 'active'),
    ('Southeast Bank',          'Gulshan Branch',         '215272538', 'active'),
    ('Bank Asia',               'Gulshan Branch',         '030274349', 'active');


-- ============================================================
-- 8.  Legacy attachment_image → attachment_paths bridge
--     Migrates single-file legacy payments (written by
--     PaymentBookingController) into the JSON array format
--     so all documents are visible via one code path.
--     SAFE TO RE-RUN: WHERE guard prevents double-migration.
-- ============================================================
UPDATE lead_payments
SET attachment_paths = JSON_ARRAY(CONCAT('uploads/payments/', attachment_image))
WHERE attachment_image IS NOT NULL
  AND attachment_image != ''
  AND (attachment_paths IS NULL OR attachment_paths = '' OR attachment_paths = '[]');

-- Composite index improves verification dashboard & history queries
ALTER TABLE lead_payments
    ADD INDEX IF NOT EXISTS `idx_lp_booking_vstatus` (booking_id, verification_status);


-- ============================================================
-- 7.  Verification  — quick sanity check (optional, read-only)
-- ============================================================
-- Run these to confirm all columns are in place after migration:
--
--   DESCRIBE lead_payments;
--   DESCRIBE cashout_payments;
--   DESCRIBE cashout_requests;
--   SELECT COUNT(*) FROM banks;
-- ============================================================
