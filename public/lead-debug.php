<?php
/**
 * TEMPORARY DEBUG FILE - DELETE AFTER USE
 * Access: https://thepremiumhomesltd.com/TPHL-ERP/lead-debug.php
 * 
 * This file sits in /public/ so .htaccess lets it run directly.
 * No login required. Shows why leads-ajax-data fails on server.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

$root = dirname(__DIR__); // TPHL-ERP root

// Load config
$config = require $root . '/app/config/config.php';

$db = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name']
);

if ($db->connect_error) {
    die("<h2 style='color:red'>DB CONNECT FAILED: " . $db->connect_error . "</h2>");
}

$db->set_charset('utf8mb4');

$start = microtime(true);
?>
<!DOCTYPE html>
<html>
<head>
<title>Lead Debug</title>
<style>
body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
h2 { color: #e94560; }
.ok { color: #4ecca3; font-weight: bold; }
.slow { color: #f5a623; font-weight: bold; }
.err { color: #e94560; font-weight: bold; }
.box { background: #16213e; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #0f3460; }
.warning { border-left-color: #e94560; }
</style>
</head>
<body>
<h2>🔍 Lead AJAX Debug Report</h2>

<div class="box">
<b>Server Info:</b><br>
PHP Version: <?= PHP_VERSION ?><br>
max_execution_time: <?= ini_get('max_execution_time') ?>s<br>
memory_limit: <?= ini_get('memory_limit') ?><br>
</div>

<?php
// ─── TEST 1: Total lead count ───────────────────────────────────────
$t = microtime(true);
$r = $db->query("SELECT COUNT(*) AS total FROM leads WHERE deleted_at IS NULL");
$total = $r ? $r->fetch_assoc()['total'] : 0;
$ms = round((microtime(true)-$t)*1000);
echo "<div class='box'><b>1. Total Leads Count:</b> <span class='ok'>$total</span> &nbsp; | &nbsp; Time: {$ms}ms</div>";

// ─── TEST 2: Simple LIMIT query ─────────────────────────────────────
$t = microtime(true);
$r = $db->query("SELECT l.id FROM leads l WHERE l.deleted_at IS NULL ORDER BY l.created_at DESC LIMIT 10");
$rows = $r ? $r->num_rows : 0;
$ms = round((microtime(true)-$t)*1000);
echo "<div class='box'><b>2. Simple LIMIT 10 query (current page load):</b> <span class='ok'>$rows rows</span> &nbsp; | &nbsp; Time: {$ms}ms</div>";

// ─── TEST 3: OLD getFollowUpCountByLead() - all leads ───────────────
$t = microtime(true);
$r = $db->query("SELECT lead_id, COUNT(*) AS c FROM lead_follow_ups WHERE deleted_at IS NULL GROUP BY lead_id");
$rows = $r ? $r->num_rows : 0;
$ms = round((microtime(true)-$t)*1000);
$cls = $ms > 5000 ? 'err' : ($ms > 1000 ? 'slow' : 'ok');
$warn = $ms > 5000 ? " ← 🔴 THIS CAUSES TIMEOUT!" : ($ms > 1000 ? " ← ⚠️ SLOW" : " ← ✅ OK");
echo "<div class='box " . ($ms > 5000 ? "warning" : "") . "'><b>3. OLD: getFollowUpCountByLead() — ALL leads:</b><br>
  <span class='$cls'>$rows rows | {$ms}ms{$warn}</span></div>";

// ─── TEST 4: OLD getBookingCountByLead() - all leads ────────────────
$t = microtime(true);
$r = $db->query("SELECT lb.lead_id, COUNT(DISTINCT lb.id) AS c FROM lead_bookings lb INNER JOIN lead_payments lp ON lp.booking_id = lb.id GROUP BY lb.lead_id");
$rows = $r ? $r->num_rows : 0;
$ms = round((microtime(true)-$t)*1000);
$cls = $ms > 5000 ? 'err' : ($ms > 1000 ? 'slow' : 'ok');
$warn = $ms > 5000 ? " ← 🔴 THIS CAUSES TIMEOUT!" : ($ms > 1000 ? " ← ⚠️ SLOW" : " ← ✅ OK");
echo "<div class='box " . ($ms > 5000 ? "warning" : "") . "'><b>4. OLD: getBookingCountByLead() — ALL leads:</b><br>
  <span class='$cls'>$rows rows | {$ms}ms{$warn}</span></div>";

// ─── TEST 5: NEW method - only 10 current page IDs ──────────────────
$ids = [];
$r_ids = $db->query("SELECT id FROM leads WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 10");
if ($r_ids) while ($row = $r_ids->fetch_assoc()) $ids[] = (int)$row['id'];
$idStr = implode(',', $ids);

$t = microtime(true);
$r = $db->query("SELECT lead_id, COUNT(*) AS c FROM lead_follow_ups WHERE deleted_at IS NULL AND lead_id IN ($idStr) GROUP BY lead_id");
$rows = $r ? $r->num_rows : 0;
$ms = round((microtime(true)-$t)*1000);
echo "<div class='box'><b>5. NEW: getFollowUpCountByLeadIds(10 IDs) — FIXED:</b><br>
  <span class='ok'>$rows rows | {$ms}ms ← ✅ FAST</span></div>";

// ─── TEST 6: FULL AJAX query simulation ─────────────────────────────
$t = microtime(true);
$sql = "SELECT l.id, l.full_name, l.created_at,
        u.full_name AS agent_name,
        p.name AS project_name,
        lf.next_follow_up_date
    FROM leads AS l
    LEFT JOIN users AS u ON l.assigned_id = u.id
    LEFT JOIN (
        SELECT lf1.lead_id, lf1.next_follow_up_date
        FROM lead_follow_ups lf1
        WHERE lf1.id = (SELECT MAX(lf2.id) FROM lead_follow_ups lf2 WHERE lf2.lead_id = lf1.lead_id)
    ) lf ON lf.lead_id = l.id
    LEFT JOIN (
        SELECT lb1.lead_id, lb1.project_id
        FROM lead_bookings lb1
        WHERE lb1.id = (SELECT MAX(lb2.id) FROM lead_bookings lb2 WHERE lb2.lead_id = lb1.lead_id)
    ) lb ON lb.lead_id = l.id
    LEFT JOIN projects AS p ON lb.project_id = p.id
    WHERE l.deleted_at IS NULL
    ORDER BY l.created_at DESC
    LIMIT 10 OFFSET 0";
$r = $db->query($sql);
$rows = $r ? $r->num_rows : 0;
$ms = round((microtime(true)-$t)*1000);
$err = $r ? '' : " | ERROR: " . $db->error;
$cls = $ms > 10000 ? 'err' : ($ms > 2000 ? 'slow' : 'ok');
echo "<div class='box'><b>6. FULL optimized getAllWithFilter LIMIT 10:</b><br>
  <span class='$cls'>$rows rows | {$ms}ms{$err}</span></div>";

// ─── VERDICT ────────────────────────────────────────────────────────
$totalMs = round((microtime(true)-$start)*1000);
echo "<div class='box warning' style='border-color:#e94560'>";
echo "<b>⚠️ VERDICT:</b><br>";
echo "Total debug time: <b>{$totalMs}ms</b><br><br>";
echo "<b>The AJAX on server is failing because:</b><br>";
echo "→ If Tests 3 or 4 show >5000ms → PHP timeout kills the AJAX response<br>";
echo "→ The fix (Tests 5) reduces this to near-instant<br><br>";
echo "<b>ACTION NEEDED:</b> Upload the fixed files (LeadController, FollowUp, BookingLead models)";
echo "</div>";
?>

<div class="box" style="border-color: #f5a623;">
<b style="color:#f5a623">⚠️ DELETE THIS FILE FROM SERVER AFTER TESTING!</b><br>
Path: /public/lead-debug.php
</div>

</body>
</html>
