-- ============================================================================
-- Migration: Add PO Rejection & Revision Tracking to purchase_requests
-- Target table: purchase_requests
-- Description:
--   1. Adds 'PO Rejected' to the status ENUM list.
--   2. Adds po_rejected_by_id, po_rejected_at, po_rejection_note,
--      last_rejected_po_id, and revision_count columns for PO rejection tracking.
-- ============================================================================

-- Step 1: Update status ENUM to include 'PO Rejected'
ALTER TABLE `purchase_requests`
  MODIFY COLUMN `status` ENUM('Draft','Submitted','Approved','Rejected','PO Issued','PO Rejected')
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Draft';

-- Step 2: Add missing columns for PO rejection tracking & revisions
ALTER TABLE `purchase_requests`
  ADD COLUMN `po_rejected_by_id` INT(11) NULL DEFAULT NULL AFTER `upload_from`,
  ADD COLUMN `po_rejected_at` DATETIME NULL DEFAULT NULL AFTER `po_rejected_by_id`,
  ADD COLUMN `po_rejection_note` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL AFTER `po_rejected_at`,
  ADD COLUMN `last_rejected_po_id` INT(11) NULL DEFAULT NULL AFTER `po_rejection_note`,
  ADD COLUMN `revision_count` INT(11) NOT NULL DEFAULT 0 AFTER `last_rejected_po_id`;
