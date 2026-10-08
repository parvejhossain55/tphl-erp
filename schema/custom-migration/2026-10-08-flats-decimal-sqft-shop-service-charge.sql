-- ============================================================================
-- 2026-10-08: Allow fractional values for flats.sqft and flats.shop_service_charge
-- Previously both were INT, so values like 1250.75 sqft or 12.5% were truncated.
-- Safe: widening INT -> DECIMAL keeps all existing values unchanged.
-- ============================================================================

ALTER TABLE `flats`
    MODIFY COLUMN `sqft` DECIMAL(14,4) NULL DEFAULT NULL,
    MODIFY COLUMN `shop_service_charge` DECIMAL(8,4) NULL DEFAULT NULL;

-- Verify
-- SHOW COLUMNS FROM `flats` WHERE Field IN ('sqft', 'shop_service_charge');
