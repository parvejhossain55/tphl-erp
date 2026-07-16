<?php
require 'app/config/config.php';
require 'app/core/Database.php';
$db = (new Database(require 'app/config/config.php'))->conn;
$res = $db->query('SELECT * FROM erp_tracker_champions');
while($row = $res->fetch_assoc()) {
    print_r($row);
}
