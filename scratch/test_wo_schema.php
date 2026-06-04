<?php
$config = require 'config/config.php';
require 'app/core/Database.php';
$db = (new Database($config['db']))->conn;
$res = $db->query('DESCRIBE con_work_order_items');
while($row=$res->fetch_assoc()) {
    echo $row['Field'].' - '.$row['Type']."\n";
}
