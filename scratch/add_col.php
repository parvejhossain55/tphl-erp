<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$res = $db->query("ALTER TABLE lead_payments ADD COLUMN bulk_pay_amount DECIMAL(15,2) DEFAULT 0.00 AFTER bulk_emi_discount");
if($res) echo "Column added";
else echo $db->error;
