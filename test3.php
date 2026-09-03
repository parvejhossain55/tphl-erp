<?php
$c = new mysqli('127.0.0.1','root','','tphl_db');
$r = $c->query("SELECT id, role_id, full_name FROM users WHERE full_name LIKE '%Purchase Head%'");
while($row = $r->fetch_assoc()) {
    print_r($row);
}
