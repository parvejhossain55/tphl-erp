<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$res = $db->query("DESCRIBE lead_bookings");
while ($r = $res->fetch_assoc()) {
    if (strpos($r['Field'], 'discount') !== false || strpos($r['Field'], 'approval') !== false || strpos($r['Field'], 'status') !== false || strpos($r['Field'], 'approved') !== false) {
        echo $r['Field'] . " (" . $r['Type'] . ")\n";
    }
}
