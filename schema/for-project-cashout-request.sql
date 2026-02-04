-- Add project_id to cashout_requests table
ALTER TABLE `cashout_requests` 
ADD COLUMN `project_id` INT(11) NULL DEFAULT NULL AFTER `id`,
ADD CONSTRAINT `fk_cashout_requests_project` 
FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;

-- Add project_id to cashout_payments table
ALTER TABLE `cashout_payments` 
ADD COLUMN `project_id` INT(11) NULL DEFAULT NULL AFTER `cashout_request_id`,
ADD CONSTRAINT `fk_cashout_payments_project` 
FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL;