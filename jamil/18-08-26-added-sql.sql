-- Database Changes Tracker (18-08-26)

-- Added 'resign_date' to 'employees' table
ALTER TABLE `employees` ADD COLUMN `resign_date` DATE NULL DEFAULT NULL AFTER `joining_date`;
