-- ============================================================
-- Migration: Fix leads table AUTO_INCREMENT missing
-- Issue: "Lead creation failed: Duplicate entry '0' for key 'PRIMARY'"
-- Root Cause: leads.id column had NO AUTO_INCREMENT
--             So every INSERT got id=0, causing duplicate PK error
-- Date: 2026-04-28
-- ============================================================

-- Step 1: Fix any existing id=0 rows (update to next safe id)
-- (Check MAX id first and use MAX+1)
-- UPDATE leads SET id = (SELECT MAX(id)+1 FROM leads tmp) WHERE id = 0;
-- OR a safer version:
UPDATE leads SET id = 4275 WHERE id = 0;

-- Step 2: Add AUTO_INCREMENT to the id column
ALTER TABLE leads 
  MODIFY id int(10) unsigned NOT NULL AUTO_INCREMENT,
  AUTO_INCREMENT = 4276;

-- Verify:
-- SHOW TABLE STATUS LIKE 'leads';
-- Should show Auto_increment = 4276 (or whatever next value is)
-- DESCRIBE leads; -> id column should show 'auto_increment' in Extra column
