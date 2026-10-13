-- Migration: Add new project fields
-- Date: 2026-10-13
-- Author: Asif / Assistant

ALTER TABLE `projects`
ADD COLUMN `building_face` VARCHAR(255) DEFAULT NULL AFTER `building_type`,
ADD COLUMN `suite_sizes` VARCHAR(255) DEFAULT NULL AFTER `building_face`,
ADD COLUMN `hotel_operation` VARCHAR(255) DEFAULT NULL AFTER `suite_sizes`,
ADD COLUMN `construction_start` VARCHAR(255) DEFAULT NULL AFTER `hotel_operation`,
ADD COLUMN `handover` VARCHAR(255) DEFAULT NULL AFTER `construction_start`,
ADD COLUMN `delivery` VARCHAR(255) DEFAULT NULL AFTER `handover`;
