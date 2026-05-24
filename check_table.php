<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$result = $db->query("SHOW COLUMNS FROM vendor_payment_request_documents");
if($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "Table missing or error: " . $db->error;
}
