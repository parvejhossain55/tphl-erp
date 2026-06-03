<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "=== QC Pending Query (Fixed JOINs) ===\n";
$sql = "SELECT pwp.id, 
               pwp.week_number AS week_no,
               p.name AS project_name,
               pwp.target_id,
               pmt.target_month,
               pp.phase_name
        FROM phase_weekly_progress pwp
        LEFT JOIN projects p ON p.id = pwp.project_id
        LEFT JOIN phase_monthly_targets pmt ON pmt.id = pwp.target_id
        LEFT JOIN project_phases pp ON pp.id = pmt.phase_id
        WHERE pwp.entry_status = 'Submitted' AND (pwp.qc_status = 'Pending' OR pwp.qc_status IS NULL)";
$res2 = $db->query($sql);
if ($res2) {
    while ($row = $res2->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Pending query failed: " . $db->error . "\n";
}

$db->close();
?>
