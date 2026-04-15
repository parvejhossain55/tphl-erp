<?php
// Simulate what CashoutRequestController::create() does
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die('Connect failed: ' . $db->connect_error); }

// getAllProjects() simulation
$sql = "SELECT id, name FROM projects WHERE deleted_at IS NULL ORDER BY name";
$result = $db->query($sql);

$projects = [];
while ($row = $result->fetch_assoc()) {
    $projects[] = $row;
}

echo "=== \$projects variable ===" . PHP_EOL;
var_dump($projects);

// officeOptions check
$officeOptions = [
    ''               => 'Select Office (Optional)',
    'Site Office'    => 'Site Office',
    'Site Office 2'  => 'Site Office 2 (Ati)',
    'Corporate Office' => 'Corporate Office',
    'Zonal Office '  => 'Zonal Office'
];

echo PHP_EOL . "=== \$officeOptions ===" . PHP_EOL;
var_dump($officeOptions);

// Check if empty() works correctly
echo PHP_EOL . "=== empty() checks ===" . PHP_EOL;
echo "empty(\$projects): " . (empty($projects) ? 'TRUE - PROBLEM!' : 'FALSE - OK') . PHP_EOL;
echo "empty(\$officeOptions): " . (empty($officeOptions) ? 'TRUE - PROBLEM!' : 'FALSE - OK') . PHP_EOL;
echo "count(\$projects): " . count($projects) . PHP_EOL;
echo "count(\$officeOptions): " . count($officeOptions) . PHP_EOL;
