<?php
// Temporary debug script — DELETE AFTER USE
// Run: http://localhost/TPHL-ERP/debug_estimate.php

$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'tphl_db'; // change to your DB name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die('DB Error: ' . $conn->connect_error);

$budgetId = 1; // change to the budget ID you're testing

echo "<h3>initial_estimate_items for budget_id=$budgetId</h3>";
$r = $conn->query("SELECT * FROM initial_estimate_items WHERE budget_id=$budgetId");
echo "<table border=1><tr><th>id</th><th>budget_id</th><th>design_type_id</th><th>material_id</th><th>material_unit</th><th>estimated_qty</th><th>unit_cost_approx</th></tr>";
while ($row = $r->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $k => $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>With JOIN (what getItemsByBudget returns)</h3>";
$sql = "SELECT i.*,
               dt.name AS design_type_name,
               COALESCE(m.name, '(Unknown)') AS material_name,
               COALESCE(i.material_unit, m.unit, '') AS material_unit_resolved
        FROM initial_estimate_items i
        LEFT JOIN design_types dt ON dt.id = i.design_type_id
        LEFT JOIN con_materials m  ON m.id  = i.material_id
        WHERE i.budget_id = $budgetId
        ORDER BY i.design_type_id ASC, i.id ASC";
$r2 = $conn->query($sql);
echo "<table border=1><tr>";
$first = true;
while ($row = $r2->fetch_assoc()) {
    if ($first) {
        foreach (array_keys($row) as $k) echo "<th>$k</th>";
        echo "</tr>";
        $first = false;
    }
    echo "<tr>";
    foreach ($row as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>con_materials table</h3>";
$r3 = $conn->query("SELECT id, name, unit FROM con_materials ORDER BY id");
echo "<table border=1><tr><th>id</th><th>name</th><th>unit</th></tr>";
while ($row = $r3->fetch_assoc()) {
    echo "<tr><td>{$row['id']}</td><td>{$row['name']}</td><td>{$row['unit']}</td></tr>";
}
echo "</table>";
