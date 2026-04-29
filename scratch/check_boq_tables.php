<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$tables = ['boq_master','boq_items','purchase_requests','purchase_request_items','purchase_orders','purchase_order_items','target_fixed'];
foreach($tables as $t) {
    echo "\nTABLE: $t\n";
    $res = $mysqli->query("DESCRIBE `$t`");
    if($res) {
        while($row=$res->fetch_assoc()) {
            echo "  " . $row['Field'] . " - " . $row['Type'] . ($row['Key'] ? ' ['.$row['Key'].']' : '') . "\n";
        }
    } else {
        echo "  NOT FOUND\n";
    }
}
