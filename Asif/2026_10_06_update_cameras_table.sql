-- ============================================================================
-- Migration: Modify cameras table (Drop hls_path, last_heartbeat & Add web_link, make others optional)
-- Date: 2026-10-06
-- ============================================================================

ALTER TABLE `cameras`
  ADD COLUMN `web_link` TEXT NULL AFTER `rtsp_url`,
  MODIFY COLUMN `camera_name` VARCHAR(255) NULL,
  MODIFY COLUMN `camera_slug` VARCHAR(255) NULL,
  MODIFY COLUMN `rtsp_url` TEXT NULL,
  DROP COLUMN `hls_path`,
  DROP COLUMN `last_heartbeat`;
