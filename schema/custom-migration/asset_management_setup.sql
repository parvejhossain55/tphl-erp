-- ============================================================================
-- Asset Management Module — Database Setup
-- Run this on VPS / phpMyAdmin
-- Date: 2026-05-24 (Updated)
-- 
-- PREREQUISITE: Run qc_audit_user_setup.sql FIRST (creates QC & Internal Audit roles)
-- ============================================================================


-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
-- TABLE 1: office_assets — All purchased assets
-- No product catalog needed. item_name comes directly from purchase requests.
-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CREATE TABLE IF NOT EXISTS `office_assets` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_code`       VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Auto-generated: AST-YYYY-NNNN',
  `item_name`        VARCHAR(255) NOT NULL COMMENT 'Comes from purchase request item_name',
  `project_id`       INT DEFAULT NULL COMMENT 'NULL = Head Office',
  `department_id`    INT DEFAULT NULL,
  `assigned_to`      INT DEFAULT NULL COMMENT 'users.id — who is using it',
  `location`         VARCHAR(255) DEFAULT 'Head Office',
  `purchase_date`    DATE DEFAULT NULL,
  `purchase_price`   DECIMAL(15,2) DEFAULT 0.00,
  `warranty_expiry`  DATE DEFAULT NULL,
  `serial_no`        VARCHAR(100) DEFAULT NULL,
  `condition_status` ENUM('new','ok','used','need_repair','repairing','repaired','damaged','disposed','sold') DEFAULT 'new',
  `asset_status`     ENUM('in_use','in_store','transferred','disposed','maintenance','sold') DEFAULT 'in_store',
  `remarks`          TEXT DEFAULT NULL,
  `created_by`       INT NOT NULL,
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asset_code` (`asset_code`),
  KEY `idx_project` (`project_id`),
  KEY `idx_status` (`asset_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
-- TABLE 2: project_extra_assets — Purchase Requests
-- 6-Step Flow: Operations → Purchase Head → QC & Audit → Management → Accounts pays → Operations confirms
-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CREATE TABLE IF NOT EXISTS `project_extra_assets` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`       INT NOT NULL,
  `asset_location`   VARCHAR(255) DEFAULT NULL COMMENT 'Site Office, Zonal Office, Corporate Office etc.',
  `item_name`        VARCHAR(255) NOT NULL COMMENT 'Free-text item name',
  `quantity`         INT NOT NULL DEFAULT 1,
  `unit_price`       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_price`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `justification`    TEXT NOT NULL COMMENT 'Why is this purchase needed?',
  `attachment`       VARCHAR(500) DEFAULT NULL,

  -- Step 1: Purchase Head Approval
  `purchase_head_status`  ENUM('pending','approved','rejected') DEFAULT 'pending',
  `purchase_head_by`      INT DEFAULT NULL,
  `purchase_head_at`      TIMESTAMP NULL DEFAULT NULL,
  `purchase_head_note`    TEXT DEFAULT NULL,

  -- Step 2: QC & Internal Audit Approval
  `qc_audit_status`       ENUM('pending','approved','rejected') DEFAULT 'pending',
  `qc_audit_by`           INT DEFAULT NULL,
  `qc_audit_at`           TIMESTAMP NULL DEFAULT NULL,
  `qc_audit_note`         TEXT DEFAULT NULL,

  -- Step 3: Management Approval
  `mgmt_status`           ENUM('pending','approved','rejected') DEFAULT 'pending',
  `mgmt_by`               INT DEFAULT NULL,
  `mgmt_at`               TIMESTAMP NULL DEFAULT NULL,
  `mgmt_note`             TEXT DEFAULT NULL,

  -- Step 4: Accounts Payment
  `payment_status`        ENUM('unpaid','paid') DEFAULT 'unpaid',
  `paid_by`               INT DEFAULT NULL,
  `paid_at`               TIMESTAMP NULL DEFAULT NULL,
  `payment_method`        VARCHAR(50) DEFAULT NULL,
  `payment_attachment`    VARCHAR(500) DEFAULT NULL,

  -- Overall Status (includes 'paid' step between accounts payment and operations confirmation)
  `overall_status`        ENUM('pending','in_review','approved','rejected','paid','purchased') DEFAULT 'pending',
  -- Step 5: Operations confirms purchase received
  `purchase_document`     VARCHAR(500) DEFAULT NULL COMMENT 'Invoice/Receipt uploaded by Operations',
  `purchase_note`         TEXT DEFAULT NULL COMMENT 'Note from Operations when confirming',
  `purchase_confirmed_by` INT DEFAULT NULL COMMENT 'users.id — who confirmed purchase',
  `purchase_confirmed_at` TIMESTAMP NULL DEFAULT NULL,

  `requested_by`          INT NOT NULL,
  `purchase_date`         DATE DEFAULT NULL,
  `vendor_name`           VARCHAR(255) DEFAULT NULL,
  `invoice_no`            VARCHAR(100) DEFAULT NULL,
  `created_at`            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`            TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_project` (`project_id`),
  KEY `idx_overall` (`overall_status`),
  KEY `idx_requested_by` (`requested_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
-- TABLE 3: asset_movement_logs — Audit Trail for all asset changes
-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

CREATE TABLE IF NOT EXISTS `asset_movement_logs` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `asset_id`         INT UNSIGNED NOT NULL COMMENT 'FK → office_assets.id',
  `action`           ENUM('assigned','transferred','returned','maintenance',
                          'repair_requested','repaired','disposed','status_change','purchased') NOT NULL,
  `from_location`    VARCHAR(255) DEFAULT NULL,
  `to_location`      VARCHAR(255) DEFAULT NULL,
  `from_user_id`     INT DEFAULT NULL,
  `to_user_id`       INT DEFAULT NULL,
  `from_project_id`  INT DEFAULT NULL,
  `to_project_id`    INT DEFAULT NULL,
  `old_condition`    VARCHAR(50) DEFAULT NULL,
  `new_condition`    VARCHAR(50) DEFAULT NULL,
  `notes`            TEXT DEFAULT NULL,
  `performed_by`     INT NOT NULL COMMENT 'users.id',
  `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_asset` (`asset_id`),
  KEY `idx_action` (`action`),
  KEY `idx_performed_by` (`performed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
-- MIGRATION QUERIES (run these if tables already exist from the old schema)
-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

-- Remove product_id and add item_name to office_assets (if migrating from old schema)
-- ALTER TABLE `office_assets` DROP COLUMN `product_id`;
-- ALTER TABLE `office_assets` ADD COLUMN `item_name` VARCHAR(255) NOT NULL AFTER `asset_code`;

-- Add asset_location to project_extra_assets
-- ALTER TABLE `project_extra_assets` ADD COLUMN `asset_location` VARCHAR(255) DEFAULT NULL AFTER `project_id`;

-- Update enums to include new values
-- ALTER TABLE `office_assets` MODIFY COLUMN `condition_status` ENUM('new','ok','used','need_repair','repairing','repaired','damaged','disposed','sold') DEFAULT 'new';
-- ALTER TABLE `office_assets` MODIFY COLUMN `asset_status` ENUM('in_use','in_store','transferred','disposed','maintenance','sold') DEFAULT 'in_store';
-- ALTER TABLE `project_extra_assets` MODIFY COLUMN `overall_status` ENUM('pending','in_review','approved','rejected','paid','purchased') DEFAULT 'pending';

-- Drop the old product master table (no longer needed)
-- DROP TABLE IF EXISTS `office_asset_products`;

-- Add purchase confirmation columns to project_extra_assets
-- ALTER TABLE `project_extra_assets` ADD COLUMN `purchase_document` VARCHAR(500) DEFAULT NULL COMMENT 'Invoice/Receipt uploaded by Operations';
-- ALTER TABLE `project_extra_assets` ADD COLUMN `purchase_note` TEXT DEFAULT NULL COMMENT 'Note from Operations when confirming purchase';
-- ALTER TABLE `project_extra_assets` ADD COLUMN `purchase_confirmed_by` INT DEFAULT NULL COMMENT 'users.id — who confirmed purchase';
-- ALTER TABLE `project_extra_assets` ADD COLUMN `purchase_confirmed_at` TIMESTAMP NULL DEFAULT NULL;


-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
-- PERMISSIONS SETUP
-- ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

-- Create 'Asset Management' permission
INSERT INTO `permissions` (`name`, `status`, `created_by`, `created_at`, `updated_at`)
SELECT 'Asset Management', 1, NULL, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `name` = 'Asset Management'
);

SET @asset_perm_id = (SELECT `id` FROM `permissions` WHERE `name` = 'Asset Management' LIMIT 1);

-- Assign to Operations (role 20)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 20, @asset_perm_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = 20 AND `permission_id` = @asset_perm_id);

-- Assign to Admin (role 1)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, @asset_perm_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = 1 AND `permission_id` = @asset_perm_id);

-- Assign to Director / Super Admin (role 3)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, @asset_perm_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = 3 AND `permission_id` = @asset_perm_id);

-- Assign to QC & Internal Audit Head
SET @qc_head_role_id = (SELECT `id` FROM `roles` WHERE `name` = 'QC & Internal Audit Head' LIMIT 1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @qc_head_role_id, @asset_perm_id FROM DUAL
WHERE @qc_head_role_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = @qc_head_role_id AND `permission_id` = @asset_perm_id);

-- Assign to QC & Internal Audit Executive
SET @qc_exec_role_id = (SELECT `id` FROM `roles` WHERE `name` = 'QC & Internal Audit Executive' LIMIT 1);
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @qc_exec_role_id, @asset_perm_id FROM DUAL
WHERE @qc_exec_role_id IS NOT NULL
AND NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = @qc_exec_role_id AND `permission_id` = @asset_perm_id);

-- Assign to Purchase Head (role 17)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 17, @asset_perm_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = 17 AND `permission_id` = @asset_perm_id);

-- Assign to Accounts (role 6)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 6, @asset_perm_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = 6 AND `permission_id` = @asset_perm_id);
