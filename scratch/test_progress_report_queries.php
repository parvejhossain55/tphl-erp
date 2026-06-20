<?php
$db = new mysqli("localhost", "root", "", "tphl_db");
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== 1. Testing Projects Dropdown List Scoping ===\n";
// Let's mock different user roles
// Site Engineer (Role 12) - e.g. userId = 13 (or let's find an engineer)
$siteEngSql = "SELECT id, name, site_engineer_id FROM projects WHERE site_engineer_id IS NOT NULL LIMIT 3";
$siteEngRes = $db->query($siteEngSql);
if ($siteEngRes && $siteEngRes->num_rows > 0) {
    while ($p = $siteEngRes->fetch_assoc()) {
        $seId = $p['site_engineer_id'];
        echo "Mocking Role 12, User ID: $seId\n";
        // Check dropdown query
        $chkRes = $db->query("SELECT id, name FROM projects WHERE site_engineer_id = $seId AND deleted_at IS NULL");
        echo "  Projects returned: " . $chkRes->num_rows . " (";
        while($chk = $chkRes->fetch_assoc()) {
            echo "{$chk['name']}, ";
        }
        echo ")\n";
    }
} else {
    echo "No projects with site_engineer_id found to test.\n";
}

echo "\n=== 2. Testing Recovery Description Trace ===\n";
// We observed in temp_query.php:
// Target item ID had gap in week 1 and recovered_qty in week 2
// Let's query one of those rows to test getRecoveryExplanation logic
$recRows = $db->query("
    SELECT pwpi.target_item_id, pwp.target_id, pwp.week_number, pwpi.recovered_qty, pmti.target_mode
    FROM phase_weekly_progress_items pwpi
    INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
    INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
    WHERE pwpi.recovered_qty > 0 OR pwpi.recovered_pct > 0
    LIMIT 3
");

function testRecoveryExplanation($db, $targetId, $targetItemId, $currentWeekNo, $recoveredQty, $targetMode = 'qty') {
    if ($recoveredQty <= 0) return '';
    
    $sql = "
        SELECT pwp.week_number, pwpi.gap_qty, pwpi.gap_pct, pmti.target_mode
        FROM phase_weekly_progress_items pwpi
        INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
        INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
        WHERE pwp.target_id = ? AND pwpi.target_item_id = ? AND pwp.week_number < ?
        ORDER BY pwp.week_number ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->bind_param('iii', $targetId, $targetItemId, $currentWeekNo);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $explanations = [];
    $remainingRecovery = (float)$recoveredQty;
    
    foreach ($rows as $row) {
        $gap = ($row['target_mode'] === 'pct') ? (float)$row['gap_pct'] : (float)$row['gap_qty'];
        if ($gap > 0) {
            $covered = min($remainingRecovery, $gap);
            $remainingRecovery -= $covered;
            $unit = ($row['target_mode'] === 'pct') ? '%' : '';
            $explanations[] = "Week {$row['week_number']}-এর বকেয়া " . number_format($covered, 2) . "{$unit} কাজ Week {$currentWeekNo}-এ সম্পন্ন হয়েছে";
            if ($remainingRecovery <= 0) break;
        }
    }
    
    if (empty($explanations)) {
        $unit = ($targetMode === 'pct') ? '%' : '';
        return "আগের বকেয়া " . number_format($recoveredQty, 2) . "{$unit} কাজ Week {$currentWeekNo}-এ সম্পন্ন হয়েছে।";
    }
    return implode(", ", $explanations) . "।";
}

if ($recRows && $recRows->num_rows > 0) {
    while ($r = $recRows->fetch_assoc()) {
        $exp = testRecoveryExplanation($db, $r['target_id'], $r['target_item_id'], $r['week_number'], $r['recovered_qty'], $r['target_mode']);
        echo "Item ID: {$r['target_item_id']}, Week: {$r['week_number']}, Recovered: {$r['recovered_qty']}\n";
        echo "  Explanation: $exp\n";
    }
} else {
    echo "No items with recovered_qty found to test.\n";
}

echo "\n=== 3. Testing Aggregation Queries (Sample of 15-Day) ===\n";
$sql15 = "
    SELECT 
        p.name AS project_name,
        pmt.target_month,
        pmti.work_item_name,
        IF(pwp.week_number <= 2, 1, 2) AS period_number,
        SUM(pwpi.planned_qty) AS planned_qty,
        SUM(pwpi.achieved_qty) AS achieved_qty,
        SUM(pwpi.gap_qty) AS gap_qty,
        SUM(pwpi.recovered_qty) AS recovered_qty
    FROM phase_weekly_progress_items pwpi
    INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
    INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
    INNER JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
    INNER JOIN projects p ON p.id = pwp.project_id
    WHERE p.deleted_at IS NULL
    GROUP BY p.id, pmti.id, period_number
    LIMIT 3
";
$res15 = $db->query($sql15);
if ($res15 && $res15->num_rows > 0) {
    while ($r = $res15->fetch_assoc()) {
        printf("Proj: %-25s | Item: %-20s | Fortnight: %d | Plan: %8.2f | Ach: %8.2f | Gap: %8.2f | Rec: %8.2f\n",
            substr($r['project_name'], 0, 25),
            substr($r['work_item_name'], 0, 20),
            $r['period_number'],
            $r['planned_qty'],
            $r['achieved_qty'],
            $r['gap_qty'],
            $r['recovered_qty']
        );
    }
} else {
    echo "No 15-day aggregated records found.\n";
}
