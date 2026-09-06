<?php
require 'app/config/database.php';
$res = $db->query("SELECT id, name FROM roles WHERE name LIKE '%operator%'");
while($row = $res->fetch_assoc()) {
    print_r($row);
}
