-- ============================================================================
-- Migration: Add total_price generated column & po_item_qty_logs table
-- Target tables: purchase_order_items, po_item_qty_logs
-- Description:
--   1. Adds `total_price` as a STORED GENERATED column to `purchase_order_items`
--      (automatically computed as quantity * unit_price) if not exists.
--   2. Creates `po_item_qty_logs` table for tracking purchase order quantity
--      revisions and audit history by approvers.
-- ============================================================================

-- Step 1: Add total_price to purchase_order_items (only if not already exists)
SET @dbname = DATABASE();
SET @tablename = 'purchase_order_items';
SET @columnname = 'total_price';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE `purchase_order_items` ADD COLUMN `total_price` DECIMAL(16,4) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED AFTER `unit_price`'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Step 2: Create po_item_qty_logs audit log table
CREATE TABLE IF NOT EXISTS `po_item_qty_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `purchase_order_id` int(10) unsigned NOT NULL,
  `purchase_order_item_id` int(10) unsigned NOT NULL,
  `material_id` int(10) unsigned NOT NULL,
  `old_quantity` decimal(12,3) NOT NULL,
  `new_quantity` decimal(12,3) NOT NULL,
  `unit_price` decimal(14,4) NOT NULL,
  `old_total_price` decimal(16,4) NOT NULL,
  `new_total_price` decimal(16,4) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_po_item_qty_logs_po` (`purchase_order_id`),
  KEY `idx_po_item_qty_logs_item` (`purchase_order_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

