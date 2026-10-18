-- =========================================================================
-- Live Database Fix: Sales Performance Missing Columns & Distribution Sync
-- Run this script in phpMyAdmin on the Live database (e.g. final_erp_db)
-- =========================================================================

-- 1. Ensure all required columns exist in `leads` table
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `distribute` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=Pending, 1=Approved';
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `is_automated` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `assigned_at` DATETIME NULL AFTER `assigned_id`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `assigned_by` INT(11) NULL AFTER `assigned_at`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `first_contacted_at` DATETIME NULL AFTER `assigned_by`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `approved_by` INT(11) NULL AFTER `is_automated`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL AFTER `approved_by`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `drop_reason` VARCHAR(150) NULL AFTER `lead_type`;
ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `drop_note` TEXT NULL AFTER `drop_reason`;

-- 2. Ensure supporting columns exist in related tables
ALTER TABLE `lead_follow_ups` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL;
ALTER TABLE `lead_bookings` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL;
ALTER TABLE `lead_payments` ADD COLUMN IF NOT EXISTS `is_trashed` TINYINT(1) NOT NULL DEFAULT 0;

-- 3. Set distribute = 1 for all non-automated leads (ensures all regular leads are visible)
UPDATE `leads` SET `distribute` = 1 WHERE `distribute` = 0 AND (`is_automated` = 0 OR `is_automated` IS NULL);

-- 4. Backfill first_contacted_at from the earliest follow up if empty
UPDATE `leads` l 
INNER JOIN (
    SELECT `lead_id`, MIN(`created_at`) AS `first_time` 
    FROM `lead_follow_ups` 
    WHERE `deleted_at` IS NULL 
    GROUP BY `lead_id`
) fu ON l.id = fu.lead_id 
SET l.`first_contacted_at` = fu.`first_time` 
WHERE l.`first_contacted_at` IS NULL;

-- 5. Backfill assigned_at from created_at if assigned_at is empty
UPDATE `leads` 
SET `assigned_at` = `created_at` 
WHERE `assigned_id` IS NOT NULL AND (`assigned_at` IS NULL OR `assigned_at` <= '1970-01-01');
