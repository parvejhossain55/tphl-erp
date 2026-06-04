<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die('CONN ERROR: ' . $db->connect_error); }

$r = $db->query("SELECT u.id, u.full_name, u.role_id, r.name AS role_name 
                 FROM users u 
                 JOIN roles r ON r.id=u.role_id 
                 WHERE u.id = 123");
$row = $r->fetch_assoc();
echo "User 123: role_id={$row['role_id']} role_name={$row['role_name']}\n";
