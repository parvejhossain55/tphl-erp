-- Migration: Add Client Payout Bank, Expected Date, and Disbursement Proof to client_refund_requests
-- For saving salesperson's client bank details and separate accounts payout proof

ALTER TABLE `client_refund_requests` 
    ADD COLUMN IF NOT EXISTS `expected_refund_date` DATE NULL AFTER `reason`,
    ADD COLUMN IF NOT EXISTS `client_bank_name` VARCHAR(150) NULL AFTER `expected_refund_date`,
    ADD COLUMN IF NOT EXISTS `client_bank_branch` VARCHAR(150) NULL AFTER `client_bank_name`,
    ADD COLUMN IF NOT EXISTS `client_bank_account_name` VARCHAR(150) NULL AFTER `client_bank_branch`,
    ADD COLUMN IF NOT EXISTS `client_bank_account_no` VARCHAR(100) NULL AFTER `client_bank_account_name`,
    ADD COLUMN IF NOT EXISTS `client_bank_routing_no` VARCHAR(50) NULL AFTER `client_bank_account_no`,
    ADD COLUMN IF NOT EXISTS `preferred_payment_method` VARCHAR(50) NULL DEFAULT 'bank' AFTER `client_bank_routing_no`,
    ADD COLUMN IF NOT EXISTS `disbursement_proof` VARCHAR(255) NULL AFTER `document`;