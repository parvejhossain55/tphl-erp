<?php
$db = new mysqli("127.0.0.1", "root", "", "tphl_db");
$resAll = $db->query("SHOW TABLES");
while($row = $resAll->fetch_array()) {
    $t = $row[0];
    if (str_contains($t, 'fund') || str_contains($t, 'req') || str_contains($t, 'cash') || str_contains($t, 'bill') || str_contains($t, 'voucher')) {
        echo $t . "\n";
    }
}
