-- ====================
-- 27/04/2026
  ALTER TABLE `lead_payments`
ADD COLUMN `bulk_pay_amount` DECIMAL(15,2) DEFAULT NULL
COMMENT 'Original bulk payment amount that triggered the bulk EMI discount'
AFTER `bulk_emi_discount`;

-- ====================
-- 27/04/2026 (Transfer Note System)
ALTER TABLE `lead_bookings`
ADD COLUMN `transfer_date` DATE NULL DEFAULT NULL AFTER `service_fee_balance`,
ADD COLUMN `transfer_note` TEXT NULL DEFAULT NULL AFTER `transfer_date`,
ADD COLUMN `transfer_status` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = Current, 1 = Transferred' AFTER `transfer_note`,
ADD COLUMN `transfer_updated_by` INT(11) NULL DEFAULT NULL AFTER `transfer_status`;

-- ====================