<?php
$db = new mysqli('localhost', 'root', '', 'tphl-erp');
$result = $db->query('DESCRIBE qc_material_tests');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . "\n";
    }
} else {
    echo "ERROR: " . $db->error;
}
