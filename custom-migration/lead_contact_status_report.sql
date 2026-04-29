-- ============================================================
-- Lead Contact Status Report
-- Purpose: Dekho kon lead ke touch kora hoyeche (lead_follow_ups e ache)
--          and kon lead aborir touch hoini (no follow_up record)
-- 
-- Touched leads: lead_follow_ups e oi lead_id ache + follow_up_type & lead_type
-- Untouched leads: lead_follow_ups e kono record nei
-- ============================================================

-- ── 1. TOUCHED LEADS (Contacted) ──────────────────────────────
-- lead_follow_ups table e exists - ei leads guloke delete kora safe na
SELECT 
    l.id AS lead_id,
    l.full_name,
    l.phone_number,
    l.lead_type,              -- leads table er status
    l.created_at AS lead_created,
    lf.follow_up_type,        -- lead_follow_ups er last follow_up_type
    lf.created_at AS last_contacted_at,
    t.name AS team_name,
    u.full_name AS assigned_to
FROM leads l
INNER JOIN (
    SELECT lf1.lead_id, lf1.follow_up_type, lf1.created_at
    FROM lead_follow_ups lf1
    WHERE lf1.id = (
        SELECT MAX(lf2.id) 
        FROM lead_follow_ups lf2 
        WHERE lf2.lead_id = lf1.lead_id
        AND lf2.deleted_at IS NULL
    )
    AND lf1.deleted_at IS NULL
) lf ON lf.lead_id = l.id
LEFT JOIN teams t ON l.team_id = t.id
LEFT JOIN users u ON l.assigned_id = u.id
WHERE l.deleted_at IS NULL
ORDER BY lf.created_at DESC;


-- ── 2. UNTOUCHED LEADS (Not Contacted) ─────────────────────────
-- lead_follow_ups e kono record nei - ei leads delete korte paro safely
SELECT 
    l.id AS lead_id,
    l.full_name,
    l.phone_number,
    l.lead_type,
    l.platform,
    l.campaign_name,
    l.created_at AS lead_created,
    t.name AS team_name
FROM leads l
LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id AND lf.deleted_at IS NULL
LEFT JOIN teams t ON l.team_id = t.id
WHERE l.deleted_at IS NULL
  AND lf.lead_id IS NULL   -- follow_up nei = not contacted
ORDER BY l.created_at DESC;


-- ── 3. SUMMARY COUNT ───────────────────────────────────────────
SELECT 
    CASE WHEN lf.lead_id IS NOT NULL THEN 'Contacted' ELSE 'Not Contacted' END AS contact_status,
    COUNT(*) AS total_leads
FROM leads l
LEFT JOIN (
    SELECT DISTINCT lead_id 
    FROM lead_follow_ups 
    WHERE deleted_at IS NULL
) lf ON lf.lead_id = l.id
WHERE l.deleted_at IS NULL
GROUP BY contact_status;


-- ── 4. SAFE-TO-DELETE (Not Contacted + No lead_type or lead_type='No Response') ─
-- Ei leads gulo delete korar aghe review koro
SELECT 
    l.id AS lead_id,
    l.full_name,
    l.phone_number,
    l.platform,
    l.campaign_name,
    l.lead_type,
    l.created_at
FROM leads l
LEFT JOIN lead_follow_ups lf ON lf.lead_id = l.id AND lf.deleted_at IS NULL
WHERE l.deleted_at IS NULL
  AND lf.lead_id IS NULL  -- not contacted
  AND (l.lead_type IS NULL OR l.lead_type = 'No Response')
ORDER BY l.created_at ASC;
