<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$r = $mysqli->query("SELECT email, role_id, (SELECT name FROM roles WHERE id=role_id) as role_name FROM users WHERE role_id IN (12, 16, 22)");
while($row = $r->fetch_assoc()) {
    echo $row['email'] . ' - ' . $row['role_name'] . "\n";
}
