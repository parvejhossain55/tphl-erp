<?php
require 'app/config/config.php';
require 'app/core/Database.php';
$db = (new Database(require 'app/config/config.php'))->conn;
$res = $db->query('DESCRIBE users');
while($r=$res->fetch_assoc()) echo $r['Field'].' - '.$r['Type']."\n";
