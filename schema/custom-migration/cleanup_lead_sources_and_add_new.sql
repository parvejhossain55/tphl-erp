-- ====================================================================
-- Lead Sources Cleanup & Keep Only 4 Specified Sources Migration
-- Date: 2026-09-27
-- Description:
-- 1. Ensure the 4 primary digital marketing lead sources exist:
--    - Lead Form (slug: 'lead-form')
--    - Whatsapp (slug: 'whatsapp')
--    - Meta Inbox (slug: 'meta-inbox')
--    - Google Form (slug: 'google-form')
-- 2. Set source_type_id to NULL in `leads` table for any deleted source.
-- 3. Set source_id to NULL in `platform_budgets` table for any deleted source.
-- 4. Delete all other sources from `lead_sources` table.
-- ====================================================================

START TRANSACTION;

-- 1. Ensure the 4 sources exist
INSERT INTO `lead_sources` (`name`, `slug`, `is_active`, `created_at`, `updated_at`)
SELECT 'Lead Form', 'lead-form', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `lead_sources` WHERE LOWER(`name`) = 'lead form' OR `slug` = 'lead-form');

INSERT INTO `lead_sources` (`name`, `slug`, `is_active`, `created_at`, `updated_at`)
SELECT 'Whatsapp', 'whatsapp', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `lead_sources` WHERE LOWER(`name`) = 'whatsapp' OR `slug` = 'whatsapp');

INSERT INTO `lead_sources` (`name`, `slug`, `is_active`, `created_at`, `updated_at`)
SELECT 'Meta Inbox', 'meta-inbox', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `lead_sources` WHERE LOWER(`name`) = 'meta inbox' OR `slug` = 'meta-inbox');

INSERT INTO `lead_sources` (`name`, `slug`, `is_active`, `created_at`, `updated_at`)
SELECT 'Google Form', 'google-form', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `lead_sources` WHERE LOWER(`name`) = 'google form' OR `slug` = 'google-form');

-- 2. Update leads table: set source_type_id = NULL for any source NOT in the 4 allowed sources
UPDATE `leads` 
SET `source_type_id` = NULL 
WHERE `source_type_id` IS NOT NULL 
  AND `source_type_id` NOT IN (
    SELECT `id` FROM `lead_sources` WHERE `name` IN ('Lead Form', 'Whatsapp', 'Meta Inbox', 'Google Form')
  );

-- 3. Update platform_budgets table if source_id is referenced
ALTER TABLE `platform_budgets` MODIFY COLUMN `source_id` INT(11) NULL DEFAULT NULL;

UPDATE `platform_budgets`
SET `source_id` = NULL
WHERE `source_id` IS NOT NULL
  AND `source_id` NOT IN (
    SELECT `id` FROM `lead_sources` WHERE `name` IN ('Lead Form', 'Whatsapp', 'Meta Inbox', 'Google Form')
  );

-- 4. Delete all sources other than the 4 allowed ones
DELETE FROM `lead_sources`
WHERE `name` NOT IN ('Lead Form', 'Whatsapp', 'Meta Inbox', 'Google Form');

COMMIT;
