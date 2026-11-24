-- ============================================================================
-- Migration: Add Board of Director (BOD) Approval to purchase_orders
-- Target table: purchase_orders
-- Description:
--   1. Adds bod_approved_by_id, bod_approved_at, and bod_note columns.
--   2. Ensures status ENUM (if ENUM) includes 'Operation Head Approved'.
-- ============================================================================

ALTER TABLE `purchase_orders`
  ADD COLUMN `bod_approved_by_id` INT NULL DEFAULT NULL AFTER `op_head_note`,
  ADD COLUMN `bod_approved_at` DATETIME NULL DEFAULT NULL AFTER `bod_approved_by_id`,
  ADD COLUMN `bod_note` TEXT NULL DEFAULT NULL AFTER `bod_approved_at`;

-- If purchase_orders.status is an ENUM, update it to include 'Operation Head Approved':
ALTER TABLE `purchase_orders`
  MODIFY COLUMN `status` ENUM('Draft','Issued','Partially Received','Fully Received','Cancelled','PM Approved','QC Approved','Approved','Purchase Head Approved','Operation Head Approved')
  NOT NULL DEFAULT 'Draft';
