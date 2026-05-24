<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$r = $mysqli->query("SELECT email, (SELECT name FROM roles WHERE id=role_id) as role_name FROM users");
while($row = $r->fetch_assoc()) {
    echo $row['email'] . ' - ' . $row['role_name'] . "\n";
}
