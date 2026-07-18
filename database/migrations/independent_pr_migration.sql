-- ============================================================
-- Migration: Independent Purchase Request Support
-- Date: 2026-07-16
-- Description: Adds pr_type, required_date, purpose columns
--              to purchase_requests table.
--              Adds required_date to purchase_request_items.
-- ============================================================

-- Step 1: Add new columns to purchase_requests
ALTER TABLE `purchase_requests`
  ADD COLUMN `pr_type`       ENUM('boq_linked', 'independent') NOT NULL DEFAULT 'boq_linked'
                             AFTER `bom_id`
                             COMMENT 'PR type: boq_linked = tied to approved BOQ, independent = standalone request',
  ADD COLUMN `required_date` DATE NULL
                             AFTER `pr_type`
                             COMMENT 'Date by which the materials are needed',
  ADD COLUMN `purpose`       VARCHAR(500) COLLATE utf8mb4_general_ci NULL
                             AFTER `required_date`
                             COMMENT 'Purpose / justification for this purchase request';

-- Step 2: Add required_date to purchase_request_items (per-item delivery date, optional)
ALTER TABLE `purchase_request_items`
  ADD COLUMN `required_date` DATE NULL
                             AFTER `quantity`
                             COMMENT 'Per-item required date (optional, overrides PR header date)';

-- Verify
-- SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_NAME = 'purchase_requests'
-- ORDER BY ORDINAL_POSITION;
