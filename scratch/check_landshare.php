<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

echo "=== Flat #3092 (Booking 155) ===\n";
$res = $mysqli->query("SELECT f.id, f.flat_name, f.price_value, f.land_share_value, f.development_charges FROM flats f WHERE f.id = 3092");
if ($res) {
    print_r($res->fetch_assoc());
}

echo "\n=== Sample Flats with land_share_value ===\n";
$res2 = $mysqli->query("SELECT id, flat_name, price_value, land_share_value FROM flats WHERE land_share_value > 0 LIMIT 5");
if ($res2) {
    while ($r = $res2->fetch_assoc()) {
        print_r($r);
    }
}
