<?php
// Clear PHP OPcache if enabled
if (function_exists('opcache_reset')) {
    opcache_reset();
}

$root = dirname(__DIR__);
require_once $root . "/app/helpers/helper.php";
require_once $root . '/vendor/autoload.php';
$config = require $root . "/app/config/config.php";

$database = new Database($config);
$db = $database->conn;

echo "<h3>MySQL Database Debugger (Corrected Version)</h3>";

// Test 1: getOverdueFollowUpCountLoginUser
$today = date('Y-m-d');
$userId = 29;

$sql1 = "
    SELECT COUNT(*) as total
    FROM lead_follow_ups f
    WHERE f.next_follow_up_date IS NOT NULL
    AND f.next_follow_up_date > '1000-01-01'
    AND f.next_follow_up_date <> ''
    AND f.next_follow_up_date < ?
    AND f.status = 'pending'
    AND f.assigned_with = ?
    AND f.deleted_at IS NULL
";

echo "<h4>Testing Query 1 (getOverdueFollowUpCountLoginUser)...</h4>";
try {
    $stmt1 = $db->prepare($sql1);
    if (!$stmt1) {
        echo "<p style='color:red;'>Query 1 Prepare Failed (returned false): " . htmlspecialchars($db->error) . "</p>";
    } else {
        echo "<p style='color:green;'>Query 1 Prepare Succeeded!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Query 1 Prepare Failed (exception): " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 2: getMissingFollowUpCount
$sql2 = "
    SELECT COUNT(DISTINCT l.id) as total
    FROM leads l
    INNER JOIN (
        SELECT t1.lead_id, t1.next_follow_up_date
        FROM lead_follow_ups t1
        INNER JOIN (
            SELECT lead_id, MAX(id) AS max_id
            FROM lead_follow_ups
            WHERE deleted_at IS NULL
            GROUP BY lead_id
        ) t2 ON t1.id = t2.max_id AND t1.lead_id = t2.lead_id
    ) lf ON lf.lead_id = l.id
    WHERE l.assigned_id = ?
      AND lf.next_follow_up_date IS NOT NULL
      AND lf.next_follow_up_date > '1000-01-01'
      AND lf.next_follow_up_date <> ''
      AND DATE(lf.next_follow_up_date) <= ?
      AND l.deleted_at IS NULL
";

echo "<h4>Testing Query 2 (getMissingFollowUpCount)...</h4>";
try {
    $stmt2 = $db->prepare($sql2);
    if (!$stmt2) {
        echo "<p style='color:red;'>Query 2 Prepare Failed (returned false): " . htmlspecialchars($db->error) . "</p>";
    } else {
        echo "<p style='color:green;'>Query 2 Prepare Succeeded!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Query 2 Prepare Failed (exception): " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Describe table
echo "<h4>Table Schema for lead_follow_ups:</h4>";
$res = $db->query("DESCRIBE lead_follow_ups");
if (!$res) {
    echo "<p style='color:red;'>Describe Failed: " . htmlspecialchars($db->error) . "</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $val) {
            echo "<td>" . htmlspecialchars($val ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}
?>
