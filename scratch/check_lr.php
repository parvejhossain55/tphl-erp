<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query('DESCRIBE con_labor_requisitions');
while($r = $res->fetch_assoc()) {
    if(strpos($r['Field'], 'op_') !== false || strpos($r['Field'], 'status') !== false) {
        echo $r['Field'] . " (" . $r['Type'] . ")\n";
    }
}
