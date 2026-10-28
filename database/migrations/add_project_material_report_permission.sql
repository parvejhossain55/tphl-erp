-- Migration: Add Project Material Report Permission and assign to Management (Role 3) & Admin (Role 1)
INSERT INTO `permissions` (`name`, `status`, `created_by`, `created_at`)
SELECT 'Project Material Report', 1, 1, NOW()
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `name` = 'Project Material Report'
);

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 3, p.id, NOW()
FROM `permissions` p
WHERE p.name = 'Project Material Report'
AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp WHERE rp.role_id = 3 AND rp.permission_id = p.id
);

INSERT INTO `role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 1, p.id, NOW()
FROM `permissions` p
WHERE p.name = 'Project Material Report'
AND NOT EXISTS (
    SELECT 1 FROM `role_permissions` rp WHERE rp.role_id = 1 AND rp.permission_id = p.id
);

