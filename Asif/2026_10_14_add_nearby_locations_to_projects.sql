-- Migration: Add nearby_locations to projects table
-- Date: 2026-10-14
-- Author: Asif / Assistant

ALTER TABLE `projects`
ADD COLUMN `nearby_locations` LONGTEXT DEFAULT NULL AFTER `project_map_location`,
ADD COLUMN `gallery_images` LONGTEXT DEFAULT NULL AFTER `image3`;
