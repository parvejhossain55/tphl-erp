<?php
$c = new mysqli('127.0.0.1','root','','tphl_db');
$r = $c->query("SELECT id, full_name, role_id FROM users WHERE full_name LIKE '%management%' OR email LIKE '%management%'");
print_r($r->fetch_all(MYSQLI_ASSOC));
