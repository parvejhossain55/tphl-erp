-- ============================================================================
-- QC & Internal Audit — Roles, Permission & User Setup
-- Run this on VPS / phpMyAdmin
-- Date: 2026-05-24
-- ============================================================================

-- ── Step 1: Create two new roles ─────────────────────────────────────────────
INSERT INTO `roles` (`name`, `created_by`, `created_at`, `updated_at`)
VALUES 
  ('QC & Internal Audit Head', NULL, NOW(), NOW()),
  ('QC & Internal Audit Executive', NULL, NOW(), NOW());

-- Save the IDs for later use
SET @qc_head_role_id     = (SELECT `id` FROM `roles` WHERE `name` = 'QC & Internal Audit Head' LIMIT 1);
SET @qc_exec_role_id     = (SELECT `id` FROM `roles` WHERE `name` = 'QC & Internal Audit Executive' LIMIT 1);


-- ── Step 2: Create the 'QC & Internal Audit' permission (if not exists) ──────
INSERT INTO `permissions` (`name`, `status`, `created_by`, `created_at`, `updated_at`)
SELECT 'QC & Internal Audit', 1, NULL, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `name` = 'QC & Internal Audit'
);

SET @qc_perm_id = (SELECT `id` FROM `permissions` WHERE `name` = 'QC & Internal Audit' LIMIT 1);


-- ── Step 3: Assign the permission to both new roles + Admin (1) + Director (3) ─
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @qc_head_role_id, @qc_perm_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = @qc_head_role_id AND `permission_id` = @qc_perm_id);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT @qc_exec_role_id, @qc_perm_id FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `role_permissions` WHERE `role_id` = @qc_exec_role_id AND `permission_id` = @qc_perm_id);



-- ── Step 4: Create the QC & Internal Audit user ──────────────────────────────
-- Username : qc_audit
-- Email    : qcaudit@gmail.com
-- Password : 123456 (bcrypt hashed)
-- Role     : QC & Internal Audit Head

INSERT INTO `users` (
    `employee_id`, `full_name`, `user_name`, `phone`, `email`,
    `designation_id`, `address`, `nid`, `role_id`, `password`,
    `photo`, `media_link1`, `media_link2`, `created_by`, `team_id`
) VALUES (
    NULL,
    'QC & Internal Audit',
    'qc_audit',
    '',
    'qcaudit@gmail.com',
    NULL,
    'Dhaka',
    '',
    @qc_head_role_id,
    '$2y$10$E.zADr8CuCrTUOy1RLAzU.wVaZSFKvBHOHd.BNXIu3mZOOG4ck7MG',
    NULL,
    '',
    '',
    1,
    0
);


-- ── Step 5: Verify ───────────────────────────────────────────────────────────
-- Run these SELECT queries after to confirm:

-- SELECT * FROM roles WHERE name LIKE '%QC & Internal Audit%';
-- SELECT * FROM permissions WHERE name = 'QC & Internal Audit';
-- SELECT * FROM users WHERE user_name = 'qc_audit';
-- SELECT rp.*, p.name AS permission_name, r.name AS role_name 
--   FROM role_permissions rp 
--   JOIN permissions p ON rp.permission_id = p.id 
--   JOIN roles r ON rp.role_id = r.id 
--   WHERE r.name LIKE '%QC & Internal Audit%';
