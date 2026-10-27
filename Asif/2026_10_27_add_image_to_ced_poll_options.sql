-- Migration: Add optional image column to ced_poll_options table
-- Date: 2026-10-27
-- Description: Allows each poll option to have an optional image.

ALTER TABLE `ced_poll_options`
ADD COLUMN `image` VARCHAR(255) NULL COMMENT 'Uploaded option image path e.g. uploads/polls/options/filename.jpg'
AFTER `option_text`;
