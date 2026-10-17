-- ============================================================================
-- TPHL-ERP LIVE Migration: Dynamic Safe Soft-Delete of Redundant Duplicate Leads
-- Date: 2026-10-17
-- Safe for: Live Production Server (Server 3 / VPS)
-- 
-- Rules:
-- 1. Finds duplicate phone numbers dynamically on the live database.
-- 2. Preserves ALL leads that have follow-ups, bookings, or onboarding records.
-- 3. For phone numbers where NO lead has activity, preserves the earliest lead (MIN(id)).
-- 4. Only soft-deletes (deleted_at = NOW()) redundant empty duplicate copies.
-- ============================================================================

START TRANSACTION;

-- Step 1: Create temporary table of lead IDs that MUST be kept intact
DROP TEMPORARY TABLE IF EXISTS temp_dup_leads_to_keep;
CREATE TEMPORARY TABLE temp_dup_leads_to_keep (
    id INT UNSIGNED NOT NULL PRIMARY KEY
) ENGINE=MEMORY;

-- Insert leads with any active business history (Follow-up, Booking, Onboard)
INSERT IGNORE INTO temp_dup_leads_to_keep (id)
SELECT l.id
FROM leads l
WHERE l.deleted_at IS NULL
  AND (
      EXISTS (SELECT 1 FROM lead_bookings b WHERE b.lead_id = l.id AND b.deleted_at IS NULL)
      OR EXISTS (SELECT 1 FROM lead_follow_ups f WHERE f.lead_id = l.id AND f.deleted_at IS NULL)
      OR EXISTS (SELECT 1 FROM onboard_clients o WHERE o.lead_id = l.id)
  );

-- For duplicate phone numbers where NO lead has activity, keep the earliest created lead (MIN id)
INSERT IGNORE INTO temp_dup_leads_to_keep (id)
SELECT MIN(l.id) AS id
FROM leads l
JOIN (
    SELECT RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(phone,''), phone_number),' ',''),'-',''),'+',''),10) AS ph
    FROM leads
    WHERE deleted_at IS NULL AND COALESCE(NULLIF(phone,''), phone_number) IS NOT NULL
      AND LENGTH(COALESCE(NULLIF(phone,''), phone_number)) >= 8
    GROUP BY ph HAVING COUNT(*) > 1
) dup ON dup.ph = RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(l.phone,''), l.phone_number),' ',''),'-',''),'+',''),10)
WHERE l.deleted_at IS NULL
  AND NOT EXISTS (SELECT 1 FROM lead_bookings b WHERE b.lead_id = l.id AND b.deleted_at IS NULL)
  AND NOT EXISTS (SELECT 1 FROM lead_follow_ups f WHERE f.lead_id = l.id AND f.deleted_at IS NULL)
  AND NOT EXISTS (SELECT 1 FROM onboard_clients o WHERE o.lead_id = l.id)
GROUP BY dup.ph;

-- Step 2: Soft delete only redundant duplicate leads that have NO activity and are NOT in the keep table
UPDATE leads l
JOIN (
    SELECT RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(phone,''), phone_number),' ',''),'-',''),'+',''),10) AS ph
    FROM leads
    WHERE deleted_at IS NULL AND COALESCE(NULLIF(phone,''), phone_number) IS NOT NULL
      AND LENGTH(COALESCE(NULLIF(phone,''), phone_number)) >= 8
    GROUP BY ph HAVING COUNT(*) > 1
) dup ON dup.ph = RIGHT(REPLACE(REPLACE(REPLACE(COALESCE(NULLIF(l.phone,''), l.phone_number),' ',''),'-',''),'+',''),10)
LEFT JOIN temp_dup_leads_to_keep k ON k.id = l.id
SET l.deleted_at = NOW(),
    l.updated_at = NOW()
WHERE l.deleted_at IS NULL
  AND k.id IS NULL
  AND NOT EXISTS (SELECT 1 FROM lead_bookings b WHERE b.lead_id = l.id AND b.deleted_at IS NULL)
  AND NOT EXISTS (SELECT 1 FROM lead_follow_ups f WHERE f.lead_id = l.id AND f.deleted_at IS NULL)
  AND NOT EXISTS (SELECT 1 FROM onboard_clients o WHERE o.lead_id = l.id);

-- Clean up temporary table
DROP TEMPORARY TABLE IF EXISTS temp_dup_leads_to_keep;

COMMIT;
