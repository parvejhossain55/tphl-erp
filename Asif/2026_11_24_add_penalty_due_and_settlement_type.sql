-- ==============================================================================
-- PARTIAL PENALTY DUE TRACKING & STANDALONE PENALTY SETTLEMENT
-- Date: 2026-11-24
-- Description:
--   1. Add 'penalty_settlement' to lead_payments.payment_type ENUM
--   2. Add penalty_due DECIMAL(15,2) column to track remaining unpaid penalty
-- ==============================================================================

ALTER TABLE `lead_payments`
    MODIFY COLUMN `payment_type` ENUM(
        'booking_money',
        'registration',
        'emi',
        'land_share',
        'development_charge',
        'emi_due_settlement',
        'shop_service_charge',
        'others',
        'penalty_settlement'
    ) NOT NULL;

ALTER TABLE `lead_payments`
    ADD COLUMN `penalty_due` DECIMAL(15,2) NOT NULL DEFAULT 0.00
    COMMENT 'Remaining unpaid penalty balance carried forward for this payment'
    AFTER `penalty_waived`;
