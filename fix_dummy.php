<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$db->query("UPDATE vendor_payment_requests SET created_by = 1 WHERE created_by IS NULL OR created_by = 0");
$db->query("UPDATE contractor_bill_requests SET created_by = 1 WHERE created_by IS NULL OR created_by = 0");
echo "Done";
