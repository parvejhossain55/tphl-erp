<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$r = $mysqli->query("SELECT id, status, approved_by_pm, qc_approved_by_id FROM purchase_orders ORDER BY id DESC LIMIT 1");
print_r($r->fetch_assoc());
