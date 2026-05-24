<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');

$d = $db->query("SELECT * FROM designations WHERE name = 'QC & Internal Audit'");
if ($d->num_rows == 0) {
    echo "Inserting QC & Internal Audit designation...\n";
    $db->query("INSERT INTO designations (name, department_id) VALUES ('QC & Internal Audit', 23)"); // Dept 23 based on previous output
} else {
    echo "QC & Internal Audit designation already exists.\n";
}
