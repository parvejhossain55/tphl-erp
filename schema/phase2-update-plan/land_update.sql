-- Migration: Land Acquisitions & Fund Requisitions Enhancement
-- Date: 2026-09-30

-- 1. land_acquisitions updates
ALTER TABLE `land_acquisitions` 
    MODIFY COLUMN `project_id` INT(11) NULL DEFAULT NULL,
    ADD COLUMN `land_project_name` VARCHAR(255) NULL AFTER `project_id`,
    ADD COLUMN `land_receiver_name` VARCHAR(255) NULL AFTER `land_owner_phone`,
    ADD COLUMN `media_broker_info` TEXT NULL AFTER `land_receiver_name`,
    ADD COLUMN `power_of_attorney` TEXT NULL AFTER `media_broker_info`;

-- 2. land_fund_requisitions updates
ALTER TABLE `land_fund_requisitions` 
    ADD COLUMN `cost_purpose` VARCHAR(255) NULL AFTER `requisition_type`,
    MODIFY COLUMN `transaction_no` VARCHAR(255) DEFAULT NULL;

-- 3. land_registrations updates for flexible deed numbering and status
ALTER TABLE `land_registrations`
    MODIFY COLUMN `registration_no` VARCHAR(255) NOT NULL,
    MODIFY COLUMN `mutation_status` VARCHAR(100) DEFAULT 'Pending';

-- 4. is_bulk_import flag for distinguishing CSV vs manual entries
ALTER TABLE `land_acquisitions`
    ADD COLUMN `is_bulk_import` TINYINT(1) NOT NULL DEFAULT 0 AFTER `remarks`;

ALTER TABLE `land_fund_requisitions`
    ADD COLUMN `is_bulk_import` TINYINT(1) NOT NULL DEFAULT 0 AFTER `purpose`;

-- Backfill already imported CSV records
UPDATE `land_fund_requisitions` 
SET `is_bulk_import` = 1 
WHERE `mgmt_note` LIKE '%bulk import%' OR `accounts_note` LIKE '%bulk import%';

UPDATE `land_acquisitions` 
SET `is_bulk_import` = 1 
WHERE `id` IN (SELECT DISTINCT `land_acquisition_id` FROM `land_fund_requisitions` WHERE `is_bulk_import` = 1);

-- 5. Fix Begum Lutfunnahar registration date (from 1970 to 22 Oct 2025)
UPDATE `land_fund_requisitions` 
SET `mgmt_approved_at` = '2025-10-22 00:00:00', `accounts_paid_at` = '2025-10-22 00:00:00' 
WHERE `purpose` LIKE '%Begum Lutfunnahar%' AND `requisition_type` = 'Registration';

UPDATE `land_registrations` lr
INNER JOIN `land_acquisitions` la ON lr.land_acquisition_id = la.id
SET lr.registration_date = '2025-10-22'
WHERE la.land_owner_name LIKE '%Begum Lutfunnahar%';

UPDATE `land_acquisitions` 
SET `registration_complete_date` = '2025-10-22' 
WHERE `land_owner_name` LIKE '%Begum Lutfunnahar%';

UPDATE `journals` 
SET `date` = '2025-10-22' 
WHERE `narration` LIKE '%Begum Lutfunnahar%' AND `narration` LIKE '%Registration%';



