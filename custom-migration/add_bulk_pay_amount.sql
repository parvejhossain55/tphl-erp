-- ============================================================
-- Migration: Add bulk_pay_amount to lead_payments
-- Purpose: Track the original bulk payment amount when a 
--          bulk EMI discount is applied. This gives context 
--          to the discount (e.g., "৳10,000 discount given 
--          against bulk payment of ৳500,000").
-- Date: 2026-04-27
-- ============================================================

ALTER TABLE `lead_payments`
ADD COLUMN `bulk_pay_amount` DECIMAL(15,2) DEFAULT NULL
COMMENT 'Original bulk payment amount that triggered the bulk EMI discount'
AFTER `bulk_emi_discount`;
