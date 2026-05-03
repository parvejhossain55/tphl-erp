<?php
/**
 * Debug CSV parsing for Architect Engineering import
 * Run via: php _tmp_debug_csv.php
 */

// Simulate what the controller does
$testCsv = __DIR__ . '/test_arch.csv';

// Create a test CSV (no BOM, plain text)
file_put_contents($testCsv, "Project_Name,Architect_Email,Design_Link,Notes,Status\nThe Prime Heights,,https://example.com,Test notes,draft\n");

$handle = fopen($testCsv, 'r');
$header = array_map('trim', fgetcsv($handle));
$header[0] = ltrim($header[0], "\xEF\xBB\xBF");

echo "Headers found:\n";
foreach ($header as $i => $h) {
    echo "  [$i] = '$h' (hex: " . bin2hex($h) . ")\n";
}

while (($data = fgetcsv($handle)) !== FALSE) {
    if (empty(array_filter($data))) continue;
    echo "\nData row count: " . count($data) . ", Header count: " . count($header) . "\n";
    if (count($header) !== count($data)) {
        echo "SKIP: column count mismatch!\n";
        continue;
    }
    $row = array_combine($header, $data);
    $projectName = trim($row['Project_Name'] ?? '');
    echo "Project_Name = '$projectName'\n";
    echo "All row data:\n";
    foreach ($row as $k => $v) echo "  $k => $v\n";
}
fclose($handle);

// Now test with DB
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    echo "\nDB Connect Error: " . $db->connect_error . "\n";
    exit;
}

$testName = 'The Prime Heights';
$stmt = $db->prepare('SELECT id, name FROM projects WHERE name = ? AND deleted_at IS NULL LIMIT 1');
$stmt->bind_param('s', $testName);
$stmt->execute();
$res = $stmt->get_result();
echo "\nDB lookup for '$testName': found " . $res->num_rows . " rows\n";
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    echo "  id={$row['id']}, name={$row['name']}\n";
}

// Also test with BOM prefix
$bomName = "\xEF\xBB\xBFThe Prime Heights";
$stmt2 = $db->prepare('SELECT id FROM projects WHERE name = ? LIMIT 1');
$stmt2->bind_param('s', $bomName);
$stmt2->execute();
$res2 = $stmt2->get_result();
echo "\nDB lookup with BOM prefix: found " . $res2->num_rows . " rows\n";

unlink($testCsv);
echo "\nDebug complete.\n";
