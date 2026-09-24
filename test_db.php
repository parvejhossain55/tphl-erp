<?php
$host = 'localhost';
$db = 'tphl_db';
$user = 'root';
$pass = '';

$mysqli = new mysqli($host, $user, $pass, $db);
$res = $mysqli->query("SELECT u.id, u.full_name, u.email, u.role_id, e.employee_id, d.name as designation FROM users u LEFT JOIN employees e ON (e.user_id = u.id OR e.id = u.employee_id) LEFT JOIN designations d ON COALESCE(e.designation_id, u.designation_id) = d.id WHERE u.id = 247");
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
$mysqli->close();
