<?php
$c = require 'C:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);
$db->query("ALTER TABLE material_receiving MODIFY COLUMN overall_status ENUM('Pending QC','Accepted','Partially Accepted','Rejected','Pending PM','Completed') DEFAULT 'Pending QC'");
echo $db->error;
// Also update the GRN that just got its overall_status wiped to 'Pending PM'
$db->query("UPDATE material_receiving SET overall_status = 'Pending PM' WHERE id = 1");
