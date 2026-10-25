-- ============================================================================
-- Migration: Add Soft Delete column (deleted_at) to ats_candidates
-- Date: 2026-10-25
-- Author: Asif / AI ATS Module
-- Description: Enables soft delete and trash/restore functionality for ATS candidates
-- ============================================================================

-- Add deleted_at column and index if they don't already exist
ALTER TABLE `ats_candidates` 
ADD COLUMN IF NOT EXISTS `deleted_at` TIMESTAMP NULL DEFAULT NULL AFTER `updated_at`,
ADD INDEX IF NOT EXISTS `idx_deleted_at` (`deleted_at`);
