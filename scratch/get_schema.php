<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');

$tables = [
    'notification_dismissals'
];

foreach ($tables as $table) {
    $res = $db->query("SHOW CREATE TABLE $table");
    if ($res) {
        $row = $res->fetch_assoc();
        echo $row['Create Table'] . ";\n\n";
    }
}
