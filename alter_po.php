<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$mysqli->query("ALTER TABLE purchase_orders MODIFY COLUMN status ENUM('Draft','Issued','Partially Received','Fully Received','Cancelled','Approved','QC Approved') NOT NULL DEFAULT 'Draft'");
$mysqli->query("ALTER TABLE purchase_orders ADD COLUMN qc_approved_by_id INT(11) NULL AFTER approved_by_pm");
$mysqli->query("ALTER TABLE purchase_orders ADD COLUMN qc_approved_at DATETIME NULL AFTER pm_approved_at");
$mysqli->query("ALTER TABLE purchase_orders ADD COLUMN qc_note TEXT NULL AFTER qc_approved_at");
echo "Done\n";
