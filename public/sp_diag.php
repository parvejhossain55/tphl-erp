<?php
/**
 * Sales Performance Live Diagnostics & Self-Healing Tool
 * Access via: https://thepremiumhomesltd.com/TPHL-ERP/sp_diag.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
require_once $root . '/app/core/Database.php';
$config = require $root . '/app/config/config.php';
$database = new Database($config);
$db = $database->conn;

// Handle OPcache Reset
$opcacheMsg = '';
if (isset($_GET['reset_opcache'])) {
    if (function_exists('opcache_reset')) {
        opcache_reset();
        $opcacheMsg = 'OPcache was successfully reset!';
    } else {
        $opcacheMsg = 'OPcache is not enabled on this server.';
    }
}

// Handle Auto-Migration Fix
$migrationMsg = '';
if (isset($_POST['run_fix'])) {
    $fixes = [
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `distribute` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0=Pending, 1=Approved'",
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `is_automated` TINYINT(1) NOT NULL DEFAULT 0",
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `first_contacted_at` DATETIME NULL",
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `assigned_by` INT(11) NULL",
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `approved_by` INT(11) NULL",
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME NULL",
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `drop_reason` VARCHAR(150) NULL",
        "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `drop_note` TEXT NULL",
        "ALTER TABLE `lead_follow_ups` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL",
        "ALTER TABLE `lead_bookings` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL",
        "ALTER TABLE `lead_payments` ADD COLUMN IF NOT EXISTS `is_trashed` TINYINT(1) NOT NULL DEFAULT 0",
        "UPDATE `leads` SET `distribute` = 1 WHERE `distribute` = 0 AND (`is_automated` = 0 OR `is_automated` IS NULL)"
    ];
    $success = 0;
    $errors = [];
    foreach ($fixes as $sql) {
        if ($db->query($sql)) {
            $success++;
        } else {
            $errors[] = $db->error;
        }
    }
    $migrationMsg = "Executed " . count($fixes) . " queries. Success: $success. Errors: " . (empty($errors) ? 'None' : implode('; ', $errors));
}

// Helper to check columns
function getTableCols($db, $table) {
    $res = $db->query("SHOW COLUMNS FROM `$table`");
    $cols = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $cols[$r['Field']] = $r['Type'];
        }
    }
    return $cols;
}

$leadsCols = getTableCols($db, 'leads');
$fuCols = getTableCols($db, 'lead_follow_ups');
$bkCols = getTableCols($db, 'lead_bookings');
$payCols = getTableCols($db, 'lead_payments');

// Check expected columns
$expectedLeads = [
    'id', 'assigned_id', 'assigned_at', 'created_at', 'deleted_at', 'project_id', 'team_id',
    'distribute', 'is_automated', 'approved_by', 'approved_at',
    'first_contacted_at', 'call_status', 'communication_type', 'lead_type', 'last_lead_action',
    'assigned_by', 'drop_reason', 'drop_note'
];

$missingLeads = [];
foreach ($expectedLeads as $col) {
    if (!isset($leadsCols[$col])) {
        $missingLeads[] = $col;
    }
}

// Test Query #1 (Current Cycle: 2026-09-26 to 2026-10-25)
$start = '2026-09-26 00:00:00';
$end = '2026-10-25 23:59:59';
$validAssignedAt = "CASE WHEN l.assigned_at IS NOT NULL AND l.assigned_at > '1970-01-01' THEN l.assigned_at ELSE l.created_at END";
$dateSqlL = " AND $validAssignedAt BETWEEN '$start' AND '$end'";

$invalid = "'Number Off', 'Wrong Number', 'Invalid Number'";
$qual = "'Prospect', 'High Prospect', 'Hot', 'Warm', 'Will Visit', 'Visited', 'Sold'";

$testSql = "
    SELECT l.id, l.assigned_id, l.assigned_at, l.created_at,
           " . (isset($leadsCols['first_contacted_at']) ? "l.first_contacted_at" : "NULL AS first_contacted_at") . ",
           " . (isset($leadsCols['call_status']) ? "l.call_status" : "'not_called' AS call_status") . ",
           " . (isset($leadsCols['communication_type']) ? "l.communication_type" : "NULL AS communication_type") . ",
           " . (isset($leadsCols['lead_type']) ? "l.lead_type" : "NULL AS lead_type") . ",
           " . (isset($leadsCols['last_lead_action']) ? "l.last_lead_action" : "NULL AS last_lead_action") . ",
           COALESCE(fu.fu_count, 0) AS fu_count,
           COALESCE(fu.f_valid, 0) AS f_valid,
           COALESCE(fu.f_qual, 0) AS f_qual,
           COALESCE(fu.f_vsched, 0) AS f_vsched,
           COALESCE(fu.f_vdone, 0) AS f_vdone,
           COALESCE(fu.f_nego, 0) AS f_nego,
           fu.first_fu_at,
           COALESCE(bk.booked, 0) AS booked,
           COALESCE(bk.confirmed, 0) AS confirmed
    FROM leads l
    LEFT JOIN (
        SELECT lead_id,
               COUNT(*) AS fu_count,
               MIN(created_at) AS first_fu_at,
               MAX(follow_up_type NOT IN ($invalid)) AS f_valid,
               MAX(lead_status IN ($qual)) AS f_qual,
               MAX(next_action = 'site visit' OR lead_status IN ('Will Visit','Visited')) AS f_vsched,
               MAX(lead_status = 'Visited' OR follow_up_type IN ('visit','Face To Face')) AS f_vdone,
               MAX(next_action = 'booking') AS f_nego
        FROM lead_follow_ups
        " . (isset($fuCols['deleted_at']) ? "WHERE deleted_at IS NULL" : "") . "
        GROUP BY lead_id
    ) fu ON fu.lead_id = l.id
    LEFT JOIN (
        SELECT b.lead_id, 1 AS booked,
               MAX(CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END) AS confirmed
        FROM lead_bookings b
        LEFT JOIN lead_payments p ON p.booking_id = b.id
             " . (isset($payCols['is_trashed']) ? "AND COALESCE(p.is_trashed, 0) = 0" : "") . "
             " . (isset($payCols['verification_status']) ? "AND p.verification_status = 'verified'" : "") . "
        WHERE " . (isset($bkCols['deleted_at']) ? "b.deleted_at IS NULL AND " : "") . " b.booking_status IN ('booked','completed')
        GROUP BY b.lead_id
    ) bk ON bk.lead_id = l.id
    WHERE " . (isset($leadsCols['deleted_at']) ? "l.deleted_at IS NULL" : "1=1") . "
      $dateSqlL
";

$testRes = $db->query($testSql);
$testError = $db->error;
$testCount = $testRes ? $testRes->num_rows : 0;

// Also test RAW query from SalesPerformanceService
$rawSql = "
    SELECT l.id, l.assigned_id, l.assigned_at, l.created_at, l.first_contacted_at, l.call_status,
           l.communication_type, l.lead_type, l.last_lead_action
    FROM leads l
    WHERE l.deleted_at IS NULL
    LIMIT 1
";
$rawRes = $db->query($rawSql);
$rawError = $db->error;

// Date counts
$cCreated = $db->query("SELECT COUNT(*) c FROM leads WHERE created_at BETWEEN '$start' AND '$end'")->fetch_assoc()['c'] ?? 0;
$cAssigned = (isset($leadsCols['assigned_at'])) ? ($db->query("SELECT COUNT(*) c FROM leads WHERE assigned_at BETWEEN '$start' AND '$end'")->fetch_assoc()['c'] ?? 0) : 'No assigned_at';

// Distribute counts
$distributeCounts = [];
if (isset($leadsCols['distribute'])) {
    $dRes = $db->query("SELECT distribute, COUNT(*) c FROM leads GROUP BY distribute");
    while ($dRes && $dr = $dRes->fetch_assoc()) {
        $distributeCounts[$dr['distribute']] = $dr['c'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Performance Diagnostics</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #1e293b; padding: 24px; }
        .card { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        h1, h2, h3 { margin-top: 0; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 5px; font-size: 12px; font-weight: 600; }
        .badge.ok { background: #dcfce7; color: #15803d; }
        .badge.err { background: #fee2e2; color: #b91c1c; }
        .badge.warn { background: #fef3c7; color: #b45309; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #e2e8f0; padding: 8px 12px; text-align: left; font-size: 13px; }
        th { background: #f1f5f9; }
        .btn { padding: 8px 16px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-green { background: #059669; color: white; }
        .btn-blue { background: #2563eb; color: white; }
        pre { background: #0f172a; color: #f8fafc; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🔬 TPHL-ERP Sales Performance Diagnostics</h1>
    <p>Database: <b><?= htmlspecialchars($config['db_name']) ?></b> | Host: <b><?= htmlspecialchars($config['db_host']) ?></b> | MySQL Version: <b><?= $db->server_info ?></b></p>

    <?php if ($opcacheMsg): ?>
        <div style="background:#dcfce7; color:#15803d; padding:12px; border-radius:6px; margin-bottom:15px;"><?= $opcacheMsg ?></div>
    <?php endif; ?>
    <?php if ($migrationMsg): ?>
        <div style="background:#e0f2fe; color:#0369a1; padding:12px; border-radius:6px; margin-bottom:15px;"><?= $migrationMsg ?></div>
    <?php endif; ?>

    <!-- 1. Column Status -->
    <div class="card">
        <h2>1. Leads Table Schema Check</h2>
        <?php if (empty($missingLeads)): ?>
            <p><span class="badge ok">✓ ALL REQUIRED COLUMNS EXIST IN `leads`</span></p>
        <?php else: ?>
            <p><span class="badge err">✗ MISSING COLUMNS DETECTED:</span> <?= implode(', ', array_map(fn($c) => "<code>$c</code>", $missingLeads)) ?></p>
            <p style="color:#b91c1c; font-size:13px;">Missing columns cause Query #1 in SalesPerformanceService to fail completely, resulting in <b>Leads = 0</b>!</p>
            <form method="POST">
                <button type="submit" name="run_fix" value="1" class="btn btn-green">⚡ Run Auto-Fix Migration to Add Missing Columns</button>
            </form>
        <?php endif; ?>

        <details style="margin-top:10px;">
            <summary style="cursor:pointer; font-weight:600;">View All <?= count($leadsCols) ?> Columns in `leads`</summary>
            <table>
                <tr><th>Column Name</th><th>Type</th><th>Status</th></tr>
                <?php foreach ($expectedLeads as $col): ?>
                    <tr>
                        <td><code><?= $col ?></code></td>
                        <td><?= $leadsCols[$col] ?? '—' ?></td>
                        <td><?= isset($leadsCols[$col]) ? '<span class="badge ok">Present</span>' : '<span class="badge err">MISSING</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </details>
    </div>

    <!-- 2. Distribute & Lead Status -->
    <div class="card">
        <h2>2. `distribute` Column Status</h2>
        <p>Total columns in leads: <?= count($leadsCols) ?> | Has distribute column: <?= isset($leadsCols['distribute']) ? '<span class="badge ok">YES</span>' : '<span class="badge err">NO</span>' ?></p>
        <table>
            <tr><th>Distribute Value</th><th>Meaning</th><th>Lead Count</th></tr>
            <?php foreach ($distributeCounts as $val => $cnt): ?>
                <tr>
                    <td><b><?= $val ?></b></td>
                    <td><?= $val == 1 ? 'Approved & Distributed (Visible to Sales)' : 'Pending Review' ?></td>
                    <td><b><?= number_format($cnt) ?></b></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- 3. Query #1 Execution Test -->
    <div class="card">
        <h2>3. Sales Performance Query #1 Test</h2>
        <p>Period: <b><?= $start ?></b> to <b><?= $end ?></b></p>
        
        <p>Raw Query Column Test (Testing <code>first_contacted_at</code>, <code>communication_type</code>, <code>last_lead_action</code>):</p>
        <?php if ($rawError): ?>
            <p><span class="badge err">SQL ERROR:</span> <?= htmlspecialchars($rawError) ?></p>
        <?php else: ?>
            <p><span class="badge ok">✓ Raw Columns Query Succeeded</span></p>
        <?php endif; ?>

        <p>Full Cohort Query Result:</p>
        <?php if ($testError): ?>
            <p><span class="badge err">SQL ERROR IN COHORT QUERY:</span> <?= htmlspecialchars($testError) ?></p>
        <?php else: ?>
            <p><span class="badge ok">✓ Full Cohort Query Succeeded!</span> Total leads matched in cycle: <b><?= number_format($testCount) ?></b></p>
        <?php endif; ?>

        <p>Date Check in Current Period (<?= $start ?> to <?= $end ?>):</p>
        <ul>
            <li>Leads created in period (<code>created_at</code>): <b><?= number_format($cCreated) ?></b></li>
            <li>Leads assigned in period (<code>assigned_at</code>): <b><?= is_numeric($cAssigned) ? number_format($cAssigned) : $cAssigned ?></b></li>
        </ul>
    </div>

    <!-- 4. OPcache & Actions -->
    <div class="card">
        <h2>4. OPcache & Server Actions</h2>
        <p>PHP Version: <b><?= PHP_VERSION ?></b> | OPcache Enabled: <b><?= function_exists('opcache_get_status') && opcache_get_status() ? '<span class="badge ok">YES</span>' : '<span class="badge warn">NO / Inactive</span>' ?></b></p>
        <p>If you recently uploaded <code>SalesPerformanceService.php</code> via cPanel/FTP, PHP OPcache might still be serving the old cached version.</p>
        <p>
            <a href="?reset_opcache=1" class="btn btn-blue">🔄 Reset PHP OPcache</a>
            <a href="sales-performance?tab=team" class="btn btn-green" target="_blank">Open Sales Performance Team Tab ↗</a>
        </p>
    </div>
</body>
</html>
