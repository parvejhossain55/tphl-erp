<?php
require 'c:/xampp/htdocs/TPHL-ERP/app/config/config.php';
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("SELECT lb.id, l.full_name FROM lead_bookings lb JOIN leads l ON l.id = lb.lead_id WHERE lb.id = 2553");
while($row = $res->fetch_assoc()) echo json_encode($row)."\n";
