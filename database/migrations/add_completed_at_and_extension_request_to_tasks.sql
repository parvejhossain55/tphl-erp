-- Add completed_at and pending deadline extension request columns to tasks table
ALTER TABLE tasks
  ADD COLUMN completed_at DATETIME NULL DEFAULT NULL AFTER updated_at,
  ADD COLUMN pending_extension_deadline DATE NULL DEFAULT NULL AFTER completed_at,
  ADD COLUMN pending_extension_reason TEXT NULL AFTER pending_extension_deadline,
  ADD COLUMN pending_extension_by INT(11) NULL DEFAULT NULL AFTER pending_extension_reason,
  ADD COLUMN pending_extension_at DATETIME NULL DEFAULT NULL AFTER pending_extension_by;
