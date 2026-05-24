<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$r = $mysqli->query("SELECT role_id FROM users WHERE email='project.manager1@gmail.com'");
print_r($r->fetch_assoc());
