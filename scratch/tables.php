<?php
require 'config/Database.php';
$db = (new Database())->getConnection();

$res = $db->query('SHOW TABLES');
echo "TABLES:\n";
while($row = $res->fetch_array()) {
    if(strpos(strtolower($row[0]), 'follow') !== false || strpos(strtolower($row[0]), 'ced') !== false || strpos(strtolower($row[0]), 'client') !== false) {
        echo $row[0] . "\n";
    }
}
