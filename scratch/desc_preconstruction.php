<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');

$tables = ['land_visibility_study', 'architect_engineering', 'project_structural_designs', 'soil_tests'];
foreach($tables as $t) {
    echo "\nTABLE: $t\n";
    $res = $mysqli->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . " | Default: " . $row['Default'] . "\n";
    }
}

// Also check project_drawings and design_types
$extra = ['project_drawings', 'design_types'];
foreach($extra as $t) {
    $check = $mysqli->query("SHOW TABLES LIKE '$t'");
    if($check->num_rows > 0) {
        echo "\nTABLE: $t\n";
        $res = $mysqli->query("DESCRIBE $t");
        while($row = $res->fetch_assoc()) {
            echo "  " . $row['Field'] . " - " . $row['Type'] . " | Default: " . $row['Default'] . "\n";
        }
    } else {
        echo "\nTABLE $t: NOT FOUND\n";
    }
}
