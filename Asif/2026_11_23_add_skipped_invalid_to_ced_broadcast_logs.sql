-- ==============================================================================
-- ADD 'skipped_invalid' STATUS TO ced_broadcast_logs
-- Date: 2026-11-23
-- Description:
--   Track foreign / invalid / missing phone & email clients per campaign
--   for manual SMS / WhatsApp follow-up and CSV export.
-- ==============================================================================

ALTER TABLE `ced_broadcast_logs`
    MODIFY COLUMN `status` ENUM('pending', 'processing', 'sent', 'failed', 'skipped_testing', 'skipped_invalid')
    NOT NULL DEFAULT 'pending';
