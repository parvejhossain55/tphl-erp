

-- Added 'on_hold' to 'status' enum in 'payslips' table
ALTER TABLE `payslips` MODIFY `status` ENUM('pending','approved','paid','reject','unpaid','draft','on_hold') NOT NULL DEFAULT 'pending';
