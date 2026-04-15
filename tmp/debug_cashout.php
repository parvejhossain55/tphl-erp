<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die('Connect failed: ' . $db->connect_error); }

echo "=== PROJECTS TABLE (deleted_at IS NULL) ===" . PHP_EOL;
$res = $db->query("SELECT id, name, deleted_at FROM projects WHERE deleted_at IS NULL ORDER BY name");
if ($res) {
    echo "Rows: " . $res->num_rows . PHP_EOL;
    while ($row = $res->fetch_assoc()) {
        echo $row['id'] . " => " . $row['name'] . PHP_EOL;
    }
} else {
    echo "Query Error: " . $db->error . PHP_EOL;
}

echo PHP_EOL . "=== ALL PROJECTS (including deleted) ===" . PHP_EOL;
$res2 = $db->query("SELECT id, name, deleted_at FROM projects ORDER BY name LIMIT 15");
if ($res2) {
    echo "Rows: " . $res2->num_rows . PHP_EOL;
    while ($row = $res2->fetch_assoc()) {
        echo $row['id'] . " => " . $row['name'] . " | deleted_at=" . ($row['deleted_at'] ?? 'NULL') . PHP_EOL;
    }
} else {
    echo "Query Error: " . $db->error . PHP_EOL;
}

echo PHP_EOL . "=== TABLE EXISTS CHECK ===" . PHP_EOL;
$res3 = $db->query("SHOW TABLES LIKE 'projects'");
echo "Projects table exists: " . ($res3->num_rows > 0 ? 'YES' : 'NO') . PHP_EOL;

echo PHP_EOL . "=== OFFICE OPTIONS IN cashout_requests ===" . PHP_EOL;
$res4 = $db->query("SELECT DISTINCT office FROM cashout_requests WHERE office IS NOT NULL AND office != '' LIMIT 10");
if ($res4) {
    echo "Rows: " . $res4->num_rows . PHP_EOL;
    while ($row = $res4->fetch_assoc()) {
        echo "  office=" . $row['office'] . PHP_EOL;
    }
} else {
    echo "Query Error: " . $db->error . PHP_EOL;
}
