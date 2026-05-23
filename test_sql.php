<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$mysqli->query("UPDATE purchase_orders SET approved_by_pm = 1, pm_approved_at = NOW(), status = 'PM Approved' WHERE id = 1");
echo $mysqli->error;
