<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Reset weekly progress ID 1 to 'Submitted' and pending QC/PM
$sql = "UPDATE phase_weekly_progress SET 
            entry_status = 'Submitted', 
            qc_approved_id = NULL, 
            qc_status = 'Pending', 
            qc_image = NULL,
            qc_comment = NULL,
            pm_approved_id = NULL, 
            pm_status = 'Pending',
            pm_comment = NULL,
            submitted_by = 1, -- Admin or Site Engineer ID
            submitted_at = NOW()
        WHERE id = 1";

if ($db->query($sql)) {
    echo "Successfully reset weekly progress ID 1 to Submitted status.\n";
} else {
    echo "Error updating record: " . $db->error . "\n";
}

$db->close();
?>
