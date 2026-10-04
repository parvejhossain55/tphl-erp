<?php
$db = new mysqli("localhost", "root", "", "tphl_db");
$db->query("ALTER TABLE lead_payments ADD COLUMN attachment_image2 VARCHAR(255) NULL AFTER attachment_image");
echo "Done";
