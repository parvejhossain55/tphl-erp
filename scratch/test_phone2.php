<?php
require_once 'app/config/config.php';
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$res = $db->query("SELECT id, full_name, phone, phone_number FROM leads WHERE full_name LIKE '%Farzana Bhuiyan%'");
var_dump($res->fetch_all(MYSQLI_ASSOC));
