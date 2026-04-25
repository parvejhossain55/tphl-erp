<?php
// Temporary debug file - delete after testing
// Access: https://thepremiumhomesltd.com/TPHL-ERP/test_leads_ajax.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$start = microtime(true);

require_once __DIR__ . '/app/config/config.php';

$config = require __DIR__ . '/app/config/config.php';

// Direct DB connect
$db = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);

if ($db->connect_error) {
    die("DB Connection failed: " . $db->connect_error);
}

echo "<h2>Lead AJAX Debug Test</h2>";
echo "<p>PHP Version: " . PHP_VERSION . "</p>";
echo "<p>Max Execution Time: " . ini_get('max_execution_time') . "s</p>";
echo "<p>Memory Limit: " . ini_get('memory_limit') . "</p>";

// Test 1: Count
$countResult = $db->query("SELECT COUNT(*) AS total FROM leads WHERE deleted_at IS NULL");
$count = $countResult->fetch_assoc()['total'];
echo "<h3>Total Leads (no filter): $count</h3>";

// Test 2: Simple paginated query (what AJAX does)
$limit = 50;
$offset = 0;

$t1 = microtime(true);
$sql = "SELECT l.*, u.full_name AS agent_name
    FROM leads AS l
    LEFT JOIN users AS u ON l.assigned_id = u.id
    WHERE l.deleted_at IS NULL
    ORDER BY l.created_at DESC, l.id DESC
    LIMIT $limit OFFSET $offset";

$result = $db->query($sql);
$t2 = microtime(true);
$rows = $result ? $result->num_rows : 0;

echo "<p>✅ Simple Query Time: " . round(($t2 - $t1) * 1000, 2) . "ms | Rows: $rows</p>";

// Test 3: Complex query (with follow_up JOIN)
$t3 = microtime(true);
$sql2 = "SELECT l.*, u.full_name AS agent_name,
        lf.next_action, lf.follow_up_type, lf.next_follow_up_date
    FROM leads AS l
    LEFT JOIN users AS u ON l.assigned_id = u.id
    LEFT JOIN (
        SELECT lf1.lead_id, lf1.next_action, lf1.follow_up_type, lf1.next_follow_up_date
        FROM lead_follow_ups lf1
        WHERE lf1.id = (
            SELECT MAX(lf2.id) FROM lead_follow_ups lf2
            WHERE lf2.lead_id = lf1.lead_id
        )
    ) lf ON lf.lead_id = l.id
    WHERE l.deleted_at IS NULL
    ORDER BY l.created_at DESC, l.id DESC
    LIMIT $limit OFFSET $offset";

$result2 = $db->query($sql2);
$t4 = microtime(true);
$rows2 = $result2 ? $result2->num_rows : 0;

echo "<p>" . ($result2 ? "✅" : "❌") . " Optimized Complex Query Time: " . round(($t4 - $t3) * 1000, 2) . "ms | Rows: $rows2</p>";

if (!$result2) {
    echo "<p style='color:red'>Query Error: " . $db->error . "</p>";
}

// Test 4: Check if lead_follow_ups table exists
$tableCheck = $db->query("SHOW TABLES LIKE 'lead_follow_ups'");
$tableExists = $tableCheck->num_rows > 0;
echo "<p>lead_follow_ups table exists: " . ($tableExists ? "✅ Yes" : "❌ No") . "</p>";

if ($tableExists) {
    $followCount = $db->query("SELECT COUNT(*) AS total FROM lead_follow_ups")->fetch_assoc()['total'];
    echo "<p>Total follow-up records: $followCount</p>";
}

// Test 5: Check PHP timeout settings
echo "<h3>Server Config</h3>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";

$total = round((microtime(true) - $start) * 1000, 2);
echo "<h3>Total Test Time: {$total}ms</h3>";

echo "<p style='color:orange'><strong>⚠️ Delete this file after testing!</strong></p>";
echo "<pre>Path: " . __FILE__ . "</pre>";
