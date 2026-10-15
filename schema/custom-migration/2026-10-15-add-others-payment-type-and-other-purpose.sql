-- 2026-10-15: Add 'others' to lead_payments.payment_type and add other_purpose column
ALTER TABLE `lead_payments` 
    MODIFY COLUMN `payment_type` ENUM('booking_money','registration','emi','land_share','development_charge','emi_due_settlement','shop_service_charge','others') NOT NULL;

ALTER TABLE `lead_payments` 
    ADD COLUMN `other_purpose` VARCHAR(255) NULL AFTER `payment_type`;
