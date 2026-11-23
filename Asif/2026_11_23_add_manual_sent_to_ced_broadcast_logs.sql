-- ==============================================================================
-- ADD 'manual_sent' STATUS TO ced_broadcast_logs
-- Date: 2026-11-23
-- Description:
--   Allow CED Head to mark skipped/failed recipients as manually contacted
--   (WhatsApp, Manual SMS, Phone Call, etc.) with audit trail.
-- ==============================================================================

ALTER TABLE `ced_broadcast_logs`
    MODIFY COLUMN `status` ENUM('pending', 'processing', 'sent', 'failed', 'skipped_testing', 'skipped_invalid', 'manual_sent')
    NOT NULL DEFAULT 'pending';
