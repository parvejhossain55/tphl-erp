<?php
$c = new mysqli('127.0.0.1','root','','tphl_db');

$sql = "
ALTER TABLE project_other_costs
ADD COLUMN `dept_head_status` ENUM('pending','approved','rejected','not_required') NOT NULL DEFAULT 'not_required',
ADD COLUMN `dept_head_approved_by` INT(11) NULL,
ADD COLUMN `dept_head_approved_at` TIMESTAMP NULL,
ADD COLUMN `dept_head_approval_note` TEXT NULL
";

if($c->query($sql)) {
    echo "Columns added successfully!\n";
} else {
    echo "Error: " . $c->error . "\n";
}
