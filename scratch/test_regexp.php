<?php
require_once 'app/config/config.php';
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$res = $db->query("SELECT REGEXP_REPLACE('8801910‑703190', '[^0-9]', '') AS cleaned");
var_dump($res->fetch_assoc());
