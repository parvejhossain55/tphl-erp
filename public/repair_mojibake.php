<?php
/**
 * TPHL ERP - Database Mojibake Bengali Text Repair Utility
 * 
 * This script repairs garbled Bengali characters in the database that occurred
 * due to previous double-encoding during CSV lead imports.
 * 
 * Usage:
 * 1. CLI: php public/repair_mojibake.php
 * 2. Web: Access https://your-domain.com/repair_mojibake.php (Make sure to delete/rename this file after running!)
 */

// Define environment
$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

// 1. Load config
$configPath = __DIR__ . '/../app/config/config.php';
if (!file_exists($configPath)) {
    die("Error: Config file not found at $configPath\n");
}
$config = require $configPath;

// 2. Connect to Database
$host = $config['db_host'] ?? '127.0.0.1';
$user = $config['db_user'] ?? 'root';
$pass = $config['db_pass'] ?? '';
$name = $config['db_name'] ?? 'tphl_db';

$conn = new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error . "\n");
}

$conn->set_charset("utf8mb4");

// 3. Define columns to fix in the leads table
$columns = [
    'campaign_name',
    'platform',
    'which_apartment_size_are_you_interested_in',
    'which_floor_are_you_for_shop',
    'when_are_you_planning_to_buy',
    'would_you_like_to_schedule_a_site_visit',
    'when_is_the_best_time_to_contact_you',
    'full_name',
    'job_title',
    'company_name',
    'phone_number',
    'city',
    'lead_name',
    'company',
    'phone',
    'email',
    'address',
    'permanent_address',
    'designation',
    'description'
];

echo "--- TPHL ERP Database Mojibake Repair ---\n";
echo "Connected to database: $name\n";
echo "Scanning leads table...\n\n";

$sql = "SELECT id, " . implode(', ', $columns) . " FROM leads WHERE deleted_at IS NULL";
$result = $conn->query($sql);

if (!$result) {
    die("Error running query: " . $conn->error . "\n");
}

$updateCount = 0;
while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $updates = [];
    $types = '';
    $params = [];

    foreach ($columns as $col) {
        $val = $row[$col];
        if (empty($val)) continue;

        // Try to reverse Windows-1252 to UTF-8 double-encoding
        $restored = @mb_convert_encoding($val, 'Windows-1252', 'UTF-8');
        
        // If restored text contains Bengali characters and is different from current val, fix it
        if ($restored !== false && $restored !== $val && preg_match('/[\x{0980}-\x{09FF}]/u', $restored)) {
            $updates[] = "`$col` = ?";
            $types .= 's';
            $params[] = $restored;
            
            echo "Row ID $id [Column: $col]:\n";
            echo "  Before: $val\n";
            echo "  After : $restored\n\n";
        }
    }

    if (!empty($updates)) {
        $updateSql = "UPDATE leads SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $conn->prepare($updateSql);
        if ($stmt) {
            $types .= 'i';
            $params[] = $id;
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $stmt->close();
            $updateCount++;
        }
    }
}

echo "----------------------------------------\n";
echo "Execution finished successfully!\n";
echo "Total lead rows repaired: $updateCount\n";
echo "----------------------------------------\n";

$conn->close();
