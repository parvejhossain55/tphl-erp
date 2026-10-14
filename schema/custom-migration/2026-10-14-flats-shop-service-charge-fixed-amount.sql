-- ============================================================================
-- 2026-10-14: Convert flats.shop_service_charge from percentage to fixed BDT amount
-- 1. Alter flats.shop_service_charge from DECIMAL(8,4) to DECIMAL(16,2) to accommodate large BDT amounts.
-- 2. Convert all existing percentages (e.g. 15, 20) into fixed BDT:
--    shop_service_charge = ROUND(price_value * shop_service_charge / 100, 2)
-- ============================================================================

-- Step 1: Widen column to DECIMAL(16,2)
ALTER TABLE `flats`
    MODIFY COLUMN `shop_service_charge` DECIMAL(16,2) NULL DEFAULT 0.00;

-- Step 2: Convert existing percentages (<= 100) to fixed BDT amounts based on price_value
UPDATE `flats`
SET `shop_service_charge` = ROUND(`price_value` * `shop_service_charge` / 100, 2)
WHERE `is_shop` = 1
  AND `shop_service_charge` IS NOT NULL
  AND `shop_service_charge` > 0
  AND `shop_service_charge` <= 100;
