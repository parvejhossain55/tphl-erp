<?php
$conn = new mysqli('localhost', 'root', '', 'tphl_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$res = $conn->query("SELECT * FROM phase_weekly_progress WHERE target_id = 1 AND week_number = 1");
if ($res) {
    $row = $res->fetch_assoc();
    echo "PHASE WEEKLY PROGRESS:\n";
    print_r($row);
} else {
    echo "No phase_weekly_progress found\n";
}

$res2 = $conn->query("SELECT * FROM daily_progress_logs WHERE weekly_progress_id = " . ($row['id'] ?? 0));
if ($res2) {
    echo "\nDAILY PROGRESS LOGS:\n";
    while ($r = $res2->fetch_assoc()) {
        print_r($r);
    }
}
