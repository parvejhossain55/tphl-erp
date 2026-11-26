-- ============================================================================
-- Migration: Office Procurement (PR -> Admin/Ops Assign -> Multi-Vendor CST
--            -> OPO with Review/Revision Loop -> Payment -> Execution -> Asset)
-- Date: 2026-11-26
-- ============================================================================

-- 1. Extend project_extra_assets with assignment, OPO link, execution & receipt tracking
-- Note: Safe idempotent ALTERs are also executed automatically by OfficeProcurementOrder::ensureSchema()

CREATE TABLE IF NOT EXISTS `office_procurement_quotations` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `extra_purchase_id` INT(11) NOT NULL,
    `vendor_id` INT(11) NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `validity_date` DATE NULL DEFAULT NULL,
    `attachment` VARCHAR(255) NULL DEFAULT NULL,
    `drive_link` TEXT NULL DEFAULT NULL,
    `notes` TEXT NULL DEFAULT NULL,
    `is_selected` TINYINT(1) NOT NULL DEFAULT 0,
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_opq_pr` (`extra_purchase_id`),
    KEY `idx_opq_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `office_procurement_orders` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `opo_number` VARCHAR(40) NOT NULL,
    `extra_purchase_id` INT(11) NOT NULL,
    `selected_quotation_id` INT(11) NULL DEFAULT NULL,
    `vendor_id` INT(11) NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `order_date` DATE NOT NULL,
    `status` VARCHAR(40) NOT NULL DEFAULT 'pending_purchase_head',
    `revision_count` INT(11) NOT NULL DEFAULT 0,
    `purchase_head_status` ENUM('pending','approved','revision_requested','rejected') DEFAULT 'pending',
    `purchase_head_by` INT(11) NULL DEFAULT NULL,
    `purchase_head_at` DATETIME NULL DEFAULT NULL,
    `purchase_head_note` TEXT NULL DEFAULT NULL,
    `qc_status` ENUM('pending','approved','revision_requested','rejected') DEFAULT 'pending',
    `qc_by` INT(11) NULL DEFAULT NULL,
    `qc_at` DATETIME NULL DEFAULT NULL,
    `qc_note` TEXT NULL DEFAULT NULL,
    `op_head_status` ENUM('pending','approved','revision_requested','rejected') DEFAULT 'pending',
    `op_head_by` INT(11) NULL DEFAULT NULL,
    `op_head_at` DATETIME NULL DEFAULT NULL,
    `op_head_note` TEXT NULL DEFAULT NULL,
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_opo_number` (`opo_number`),
    KEY `idx_opo_pr` (`extra_purchase_id`),
    KEY `idx_opo_vendor` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `office_procurement_opo_revisions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `opo_id` INT(11) NOT NULL DEFAULT 0,
    `extra_purchase_id` INT(11) NOT NULL,
    `action_type` VARCHAR(40) NOT NULL,
    `stage_name` VARCHAR(80) NOT NULL,
    `old_unit_price` DECIMAL(15,2) NULL DEFAULT NULL,
    `suggested_unit_price` DECIMAL(15,2) NULL DEFAULT NULL,
    `new_unit_price` DECIMAL(15,2) NULL DEFAULT NULL,
    `note` TEXT NULL DEFAULT NULL,
    `acted_by` INT(11) NOT NULL,
    `acted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_rev_opo` (`opo_id`),
    KEY `idx_rev_pr` (`extra_purchase_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
