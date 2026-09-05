<?php
$c = new mysqli('127.0.0.1','root','','tphl_db'); 
$r = $c->query('DESCRIBE project_other_costs'); 
while($row = $r->fetch_assoc()) echo $row['Field'] . "\n";
