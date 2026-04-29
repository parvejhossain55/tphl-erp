<?php
$mysqli = new mysqli('localhost', 'root', '', 'tphl_db');
$tables = ['land_visibility_study', 'architect_engineering', 'project_structural_designs', 'initial_estimate_budgets', 'initial_estimate_items', 'initial_estimate_labors', 'soil_tests'];
foreach($tables as $t) {
    echo "TABLE: $t\n";
    $res = $mysqli->query("DESCRIBE $t");
    while($row = $res->fetch_assoc()) {
        echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
    }
}
