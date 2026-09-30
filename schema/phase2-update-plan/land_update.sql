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

