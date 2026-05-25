-- =============================================================================
-- FIX: project_phases proportional date distribution for project_id = 13
-- Construction period: 2026-06-26 → 2028-03-26 (638 days total)
-- Total weight = 100%. Dates distributed proportionally by weight.
-- =============================================================================

-- Phase 1: Foundation & Piling  (12% = ~77 days)  2026-06-26 → 2026-09-11
UPDATE project_phases
SET planned_start_date = '2026-06-26',
    planned_end_date   = '2026-09-11'
WHERE id = 1 AND project_id = 13;

-- Phase 2: Basement & Substructure (11% = ~70 days) 2026-09-11 → 2026-11-20
UPDATE project_phases
SET planned_start_date = '2026-09-11',
    planned_end_date   = '2026-11-20'
WHERE id = 2 AND project_id = 13;

-- Phase 3: Super Structure (28% = ~179 days) 2026-11-20 → 2027-05-18
UPDATE project_phases
SET planned_start_date = '2026-11-20',
    planned_end_date   = '2027-05-18'
WHERE id = 3 AND project_id = 13;

-- Phase 4: Masonry & Partition (12% = ~77 days) 2027-05-18 → 2027-08-03
UPDATE project_phases
SET planned_start_date = '2027-05-18',
    planned_end_date   = '2027-08-03'
WHERE id = 4 AND project_id = 13;

-- Phase 5: MEP & Utilities (18% = ~115 days) 2027-08-03 → 2027-11-26
UPDATE project_phases
SET planned_start_date = '2027-08-03',
    planned_end_date   = '2027-11-26'
WHERE id = 5 AND project_id = 13;

-- Phase 6: Finishing & Handover (19% = ~121 days) 2027-11-26 → 2028-03-26
UPDATE project_phases
SET planned_start_date = '2027-11-26',
    planned_end_date   = '2028-03-26'
WHERE id = 6 AND project_id = 13;

-- Verify
SELECT id, phase_name, phase_order, weight_percentage,
       planned_start_date, planned_end_date,
       DATEDIFF(planned_end_date, planned_start_date) AS duration_days
FROM project_phases
WHERE project_id = 13
ORDER BY phase_order;
