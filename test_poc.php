<?php
$c = new mysqli('127.0.0.1','root','','tphl_db');
$r = $c->query('SELECT * FROM project_other_costs');
print_r($r->fetch_all(MYSQLI_ASSOC));
