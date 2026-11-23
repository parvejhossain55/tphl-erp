-- Create CED Project Assignment History table to track agent transitions
-- and enforce 1 active agent per project without losing historical attribution.
-- Date: 2026-11-21

CREATE TABLE IF NOT EXISTS ced_project_assignment_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_to DATETIME NULL DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_project_dates (project_id, assigned_from, assigned_to),
    KEY idx_user_dates (user_id, assigned_from, assigned_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backfill currently active assignments as baseline history (1 active agent per project)
INSERT INTO ced_project_assignment_history (project_id, user_id, assigned_by, assigned_from, assigned_to)
SELECT cpa.project_id, cpa.user_id, cpa.assigned_by, '2000-01-01 00:00:00', NULL
FROM ced_project_assignments cpa
INNER JOIN (
    SELECT project_id, MAX(id) AS max_id
    FROM ced_project_assignments
    WHERE status = 'active'
    GROUP BY project_id
) latest ON latest.max_id = cpa.id
WHERE NOT EXISTS (
    SELECT 1 FROM ced_project_assignment_history h
    WHERE h.project_id = cpa.project_id AND h.assigned_to IS NULL
);
