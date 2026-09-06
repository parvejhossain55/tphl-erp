<?php
require 'app/config/config.php';
global $db;
$res = $db->query("DESCRIBE employees");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . PHP_EOL;
}
