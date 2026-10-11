<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query('SELECT 2nd_lead_owner_name, 2nd_owner_phone, 2nd_owner_email, 2nd_owner_address, 2nd_owner_other_details FROM leads WHERE id=52004');
print_r($res->fetch_assoc());
