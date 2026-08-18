<?php
require 'app/config/config.php';
require 'app/core/Database.php';
$db = (new Database(require 'app/config/config.php'))->conn;
$res = $db->query('SHOW TABLES');
while($r=$res->fetch_row()) echo $r[0]."\n";
