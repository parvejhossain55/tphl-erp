-- ============================================================================
-- Migration: Update ats_candidates.hired column to support extended hiring statuses
-- Date: 2026-11-04
-- Options:
--   1. Decision Pending (default)
--   2. Job Offered - Pending
--   3. Job Offered - Accepted
--   4. Job Offered - Rejected
--   5. Joined
--   6. Absent on Joining Day
-- ============================================================================

ALTER TABLE `ats_candidates`
MODIFY COLUMN `hired` VARCHAR(50) DEFAULT 'Decision Pending';
