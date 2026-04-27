-- ====================
-- 27/04/2026
  ALTER TABLE `lead_payments`
ADD COLUMN `bulk_pay_amount` DECIMAL(15,2) DEFAULT NULL
COMMENT 'Original bulk payment amount that triggered the bulk EMI discount'
AFTER `bulk_emi_discount`;

-- ====================