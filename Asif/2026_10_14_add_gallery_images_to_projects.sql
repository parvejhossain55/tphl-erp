-- Migration: Add gallery_images to projects table
-- Date: 2026-10-14
-- Author: Asif / Assistant

ALTER TABLE `projects`
ADD COLUMN `gallery_images` LONGTEXT DEFAULT NULL AFTER `image3`;
