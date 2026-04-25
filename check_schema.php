<?php
$db = new mysqli("localhost", "root", "", "tphl_db");
$res = $db->query("DESCRIBE lead_follow_ups");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "LEADS:\n";
$res = $db->query("DESCRIBE leads");
while($row = $res->fetch_assoc()) {
    if ($row['Field'] == 'lead_type') {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
}
