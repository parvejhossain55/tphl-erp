<?php require __DIR__."/../app/config/config.php"; $db = new mysqli("localhost", "root", "", "tphl_db"); $res = $db->query("SHOW TABLES"); while($r = $res->fetch_array()) echo $r[0]."\n"; ?>
