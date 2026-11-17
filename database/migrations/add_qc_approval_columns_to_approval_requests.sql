-- Migration: Add QC approval stage columns to approval_requests
ALTER TABLE `approval_requests`
    ADD COLUMN IF NOT EXISTS `qc_status` VARCHAR(20) NOT NULL DEFAULT 'pending' AFTER `op_head_note`,
    ADD COLUMN IF NOT EXISTS `qc_by` INT(10) UNSIGNED NULL AFTER `qc_status`,
    ADD COLUMN IF NOT EXISTS `qc_at` DATETIME NULL AFTER `qc_by`,
    ADD COLUMN IF NOT EXISTS `qc_note` TEXT NULL AFTER `qc_at`;
