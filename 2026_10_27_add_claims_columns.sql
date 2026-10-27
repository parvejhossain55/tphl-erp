-- Migration: Add flat_id, split_type, and is_on_hold to project_incentive_claims
ALTER TABLE `project_incentive_claims` ADD COLUMN `flat_id` INT(11) NULL AFTER `booking_id`;
ALTER TABLE `project_incentive_claims` ADD COLUMN `split_type` ENUM('direct', 'seller_70', 'designated_30') NOT NULL DEFAULT 'direct' AFTER `is_external`;
ALTER TABLE `project_incentive_claims` ADD COLUMN `is_on_hold` TINYINT(1) NOT NULL DEFAULT 0 AFTER `claim_status`;
