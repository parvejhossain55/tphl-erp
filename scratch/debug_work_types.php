<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== phase_target_item_work_types ===\n";
$res = $db->query("SELECT * FROM phase_target_item_work_types");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $db->error . "\n";
}

echo "\n=== work_types ===\n";
$res = $db->query("SELECT id, work_type_name, default_phase_name FROM work_types LIMIT 20");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $db->error . "\n";
}

echo "\n=== daily_work_item_logs (recent) ===\n";
$res = $db->query("SELECT * FROM daily_work_item_logs ORDER BY id DESC LIMIT 10");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $db->error . "\n";
}
?>
