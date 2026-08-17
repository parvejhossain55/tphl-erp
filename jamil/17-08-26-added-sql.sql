-- Database Changes Tracker

-- Added 'per_meal_amount' to 'employees' table
ALTER TABLE `employees` ADD COLUMN `per_meal_amount` DECIMAL(10,2) NOT NULL DEFAULT '0.00' AFTER `gross_salary`;

-- Added 'on_hold' to 'status' enum in 'payslips' table
ALTER TABLE `payslips` MODIFY `status` ENUM('pending','approved','paid','reject','unpaid','draft','on_hold') NOT NULL DEFAULT 'pending';
