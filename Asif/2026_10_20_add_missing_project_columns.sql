-- Migration: Add missing project columns
-- Date: 2026-10-20
-- Author: Antigravity

ALTER TABLE `projects`
ADD COLUMN IF NOT EXISTS `building_face` VARCHAR(255) DEFAULT NULL AFTER `building_type`,
ADD COLUMN IF NOT EXISTS `suite_sizes` VARCHAR(255) DEFAULT NULL AFTER `building_face`,
ADD COLUMN IF NOT EXISTS `hotel_operation` VARCHAR(255) DEFAULT NULL AFTER `suite_sizes`,
ADD COLUMN IF NOT EXISTS `construction_start` VARCHAR(255) DEFAULT NULL AFTER `hotel_operation`,
ADD COLUMN IF NOT EXISTS `handover` VARCHAR(255) DEFAULT NULL AFTER `construction_start`,
ADD COLUMN IF NOT EXISTS `delivery` VARCHAR(255) DEFAULT NULL AFTER `handover`,
ADD COLUMN IF NOT EXISTS `gallery_images` LONGTEXT DEFAULT NULL AFTER `image3`,
ADD COLUMN IF NOT EXISTS `nearby_locations` LONGTEXT DEFAULT NULL AFTER `project_map_location`;
