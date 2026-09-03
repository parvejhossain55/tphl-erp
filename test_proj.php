<?php
$c = new mysqli('127.0.0.1','root','','tphl_db');
$r=$c->query('SELECT id, name, under_construction FROM projects WHERE deleted_at IS NULL LIMIT 5');
while($row = $r->fetch_assoc()){
    print_r($row);
}
