-- ============================================================
-- Migration: Add missing columns to lead_follow_ups table
-- Issue: "Failed to create follow-up!" error
-- Root Cause: Model insert query referenced columns that didn't
--             exist in the database table.
-- Date: 2026-04-28
-- ============================================================

-- Add follow_up_agent_id column (who did the follow-up, different from assigned_with)
ALTER TABLE `lead_follow_ups`
  ADD COLUMN IF NOT EXISTS `follow_up_agent_id` int(11) DEFAULT NULL AFTER `created_by`;

-- Add lead_status column (stores lead_type update on each follow-up)
ALTER TABLE `lead_follow_ups`
  ADD COLUMN IF NOT EXISTS `lead_status` varchar(100) DEFAULT NULL AFTER `follow_up_agent_id`;

-- Add next_follow_up_plan_notes column (planning notes for next follow-up)
ALTER TABLE `lead_follow_ups`
  ADD COLUMN IF NOT EXISTS `next_follow_up_plan_notes` text DEFAULT NULL AFTER `lead_status`;

-- ============================================================
-- ALSO: follow_up_date is NOT NULL - ensure form always sends it
-- If NULL is passed, the model now falls back to CURDATE()
-- ============================================================

-- Verify final structure:
-- DESCRIBE lead_follow_ups;
