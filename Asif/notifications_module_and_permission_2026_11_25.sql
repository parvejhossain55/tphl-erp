-- Dedicated Notification Page & Task Action Tracking Migration
-- Date: 2026-11-25

-- 1. Extend existing notifications table (non-destructive)
ALTER TABLE notifications
    ADD COLUMN IF NOT EXISTS module VARCHAR(50) NOT NULL DEFAULT 'task' AFTER message,
    ADD COLUMN IF NOT EXISTS action_type VARCHAR(50) NULL DEFAULT NULL AFTER module,
    ADD COLUMN IF NOT EXISTS target_url VARCHAR(255) NULL DEFAULT NULL AFTER action_type,
    ADD COLUMN IF NOT EXISTS read_at DATETIME NULL DEFAULT NULL AFTER is_read;

-- 2. Add index for fast per-user notification queries
ALTER TABLE notifications
    ADD INDEX IF NOT EXISTS idx_notifications_user_read_created (user_id, is_read, created_at);

-- 3. Add 'Notifications' permission for Role & Permission control
INSERT INTO permissions (name, status, created_by)
SELECT 'Notifications', 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE LOWER(name) = 'notifications'
);

-- 4. Grant 'Notifications' permission to all existing roles by default
-- (Admin can later enable/disable per role from Role & Permission settings)
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE LOWER(p.name) = 'notifications'
  AND NOT EXISTS (
      SELECT 1 FROM role_permissions rp
      WHERE rp.role_id = r.id AND rp.permission_id = p.id
  );
