-- Migration to fix QC approval columns in fund_requisitions, vendor_payment_requests, and con_labor_requisitions
-- Run this on live/staging database if needed

-- 1. fund_requisitions QC columns & status enum
ALTER TABLE `fund_requisitions`
  ADD COLUMN IF NOT EXISTS `qc_approved_by` INT(11) NULL DEFAULT NULL AFTER `dept_head_approval_note`,
  ADD COLUMN IF NOT EXISTS `qc_approved_at` TIMESTAMP NULL DEFAULT NULL AFTER `qc_approved_by`,
  ADD COLUMN IF NOT EXISTS `qc_approval_note` TEXT NULL DEFAULT NULL AFTER `qc_approved_at`,
  ADD COLUMN IF NOT EXISTS `qc_approved_amount` DECIMAL(12,2) NULL DEFAULT NULL AFTER `qc_approval_note`;

ALTER TABLE `fund_requisitions` 
  MODIFY COLUMN `status` ENUM('pending_dept','pending_qc','pending','approved','rejected','paid','partially_approved','partially_paid') DEFAULT 'pending';

-- 2. vendor_payment_requests QC approved amount
ALTER TABLE `vendor_payment_requests`
  ADD COLUMN IF NOT EXISTS `qc_approved_amount` DECIMAL(16,4) NULL DEFAULT NULL AFTER `net_payable`;

-- 3. con_labor_requisitions QC approved amount
ALTER TABLE `con_labor_requisitions`
  ADD COLUMN IF NOT EXISTS `qc_approved_amount` DECIMAL(12,2) NULL DEFAULT NULL AFTER `qc_approved_at`;
