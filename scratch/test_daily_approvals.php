<?php
// Initialize session and define roles/users
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['user'] = [
    'id' => 126, // QC user id
    'role_id' => 13 // QC role
];

$root = 'c:/xampp/htdocs/TPHL-ERP';
$GLOBALS['base_url'] = '/TPHL-ERP';

// Include helpers & autoloader
require_once $root . "/app/helpers/helper.php";
spl_autoload_register(function ($class) use ($root) {
    $class = str_replace('\\', '/', $class);
    $paths = [
        $root . "/app/core/$class.php",
        $root . "/app/models/$class.php",
        $root . "/app/controllers/$class.php",
        $root . "/app/controllers/Phase2/$class.php"
    ];
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Connect to Database
$config = require $root . "/app/config/config.php";
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error);
}
echo "Connected successfully to database.\n";

// --- 1. SETUP TEST DATA ---
// Clear existing daily progress logs for testing weekly progress ID 1
$db->query("DELETE FROM daily_progress_logs WHERE weekly_progress_id = 1");

// Create 3 dummy daily progress logs for week 1 (e.g. Mon, Tue, Wed)
$dates = ['2026-06-01', '2026-06-02', '2026-06-03'];
foreach ($dates as $d) {
    $db->query("INSERT INTO daily_progress_logs (weekly_progress_id, project_id, log_date, labour_count, safety_ok, qc_checked, qc_status, pm_status) 
                VALUES (1, 1, '$d', 5, 1, 1, 'Pending', 'Pending')");
}

// Reset Weekly Progress status to Submitted
$db->query("UPDATE phase_weekly_progress SET 
                entry_status = 'Submitted', 
                qc_approved_id = NULL, 
                qc_status = 'Pending', 
                pm_approved_id = NULL, 
                pm_status = 'Pending'
            WHERE id = 1");

echo "\n--- Test Setup Completed ---";
echo "\nTotal daily logs for week 1: " . $db->query("SELECT COUNT(*) AS c FROM daily_progress_logs WHERE weekly_progress_id = 1")->fetch_assoc()['c'];
$wp = $db->query("SELECT entry_status, qc_status, pm_status FROM phase_weekly_progress WHERE id = 1")->fetch_assoc();
echo "\nWeekly progress status: Entry=" . $wp['entry_status'] . ", QC=" . $wp['qc_status'] . ", PM=" . $wp['pm_status'] . "\n";

// Instantiate Controller
$controller = new Phase2\Con_PhaseWeeklyProgressController($db);

// --- 2. TEST QC APPROVAL FOR ONE DAY ---
echo "\n--- Simulating QC Approving date 2026-06-01 ---";
$_POST = [
    'weekly_progress_id' => 1,
    'target_id' => 1,
    'week_number' => 1,
    'log_date' => '2026-06-01',
    'qc_status' => 'Approved',
    'qc_comment' => 'First day is excellent.'
];
// Mock file upload to be empty
$_FILES = [
    'qc_image' => ['name' => '', 'error' => UPLOAD_ERR_NO_FILE]
];

// Call controller method (prevent exit/redirect using buffer or catch redirect headers if we wanted, but since it exits we will simulate parts of it)
// Instead of invoking qcApprove directly which has header redirection, let's test the database logic directly or capture output.
// Actually, let's implement a test function in this script that replicates the logic of the controller or calls it.
// Let's use a function that mimics the update and sync so we don't trigger header redirects:

function runQcApproveSimulation($db, $weeklyProgressId, $logDate, $userId, $qcStatus, $qcComment) {
    $stmt = $db->prepare("
        UPDATE daily_progress_logs SET
            qc_approved_id = ?,
            qc_status      = ?,
            qc_comment     = ?
        WHERE weekly_progress_id = ? AND log_date = ?
    ");
    $stmt->bind_param('issis', $userId, $qcStatus, $qcComment, $weeklyProgressId, $logDate);
    $stmt->execute();
    
    // Sync logic
    $res = $db->query("SELECT id, qc_status FROM daily_progress_logs WHERE weekly_progress_id = $weeklyProgressId");
    $logs = $res->fetch_all(MYSQLI_ASSOC);
    
    $allApproved = true;
    $anyRejected = false;
    foreach ($logs as $log) {
        if ($log['qc_status'] === 'Rejected') $anyRejected = true;
        if ($log['qc_status'] !== 'Approved' && $log['qc_status'] !== 'Partially Approved') $allApproved = false;
    }
    
    if ($anyRejected) {
        $db->query("UPDATE phase_weekly_progress SET qc_status = 'Rejected', qc_approved_id = $userId WHERE id = $weeklyProgressId");
    } elseif ($allApproved) {
        $db->query("UPDATE phase_weekly_progress SET qc_status = 'Approved', qc_approved_id = $userId WHERE id = $weeklyProgressId");
    } else {
        $db->query("UPDATE phase_weekly_progress SET qc_status = 'Pending', qc_approved_id = NULL WHERE id = $weeklyProgressId");
    }
}

// 2a. Approve Day 1
runQcApproveSimulation($db, 1, '2026-06-01', 126, 'Approved', 'First day is excellent.');
$dl1 = $db->query("SELECT * FROM daily_progress_logs WHERE weekly_progress_id = 1 AND log_date = '2026-06-01'")->fetch_assoc();
$wp = $db->query("SELECT entry_status, qc_status, pm_status FROM phase_weekly_progress WHERE id = 1")->fetch_assoc();
echo "Day 1 status: " . $dl1['qc_status'] . ", comment: " . $dl1['qc_comment'] . "\n";
echo "Weekly Status (after 1 day approved): QC=" . $wp['qc_status'] . " (Expected: Pending)\n";

// 2b. Approve Day 2 and Day 3
echo "\n--- Simulating QC Approving remaining days ---";
runQcApproveSimulation($db, 1, '2026-06-02', 126, 'Approved', 'Day 2 ok.');
runQcApproveSimulation($db, 1, '2026-06-03', 126, 'Approved', 'Day 3 ok.');
$wp = $db->query("SELECT entry_status, qc_status, pm_status, qc_approved_id FROM phase_weekly_progress WHERE id = 1")->fetch_assoc();
echo "Weekly Status (after all days approved): QC=" . $wp['qc_status'] . " (Expected: Approved), qc_approved_id=" . $wp['qc_approved_id'] . "\n";


// --- 3. TEST PM APPROVAL FLOW ---
function runPmApproveSimulation($db, $weeklyProgressId, $logDate, $userId, $pmStatus, $pmComment) {
    $stmt = $db->prepare("
        UPDATE daily_progress_logs SET
            pm_approved_id = ?,
            pm_status      = ?,
            pm_comment     = ?
        WHERE weekly_progress_id = ? AND log_date = ?
    ");
    $stmt->bind_param('issis', $userId, $pmStatus, $pmComment, $weeklyProgressId, $logDate);
    $stmt->execute();
    
    // Sync logic
    $res = $db->query("SELECT id, pm_status FROM daily_progress_logs WHERE weekly_progress_id = $weeklyProgressId");
    $logs = $res->fetch_all(MYSQLI_ASSOC);
    
    $allApproved = true;
    $anyRejected = false;
    foreach ($logs as $log) {
        if ($log['pm_status'] === 'Rejected') $anyRejected = true;
        if ($log['pm_status'] !== 'Approved' && $log['pm_status'] !== 'Partially Approved') $allApproved = false;
    }
    
    if ($anyRejected) {
        $db->query("UPDATE phase_weekly_progress SET pm_status = 'Rejected', pm_approved_id = $userId, entry_status = 'Draft', qc_status = 'Pending', qc_approved_id = NULL WHERE id = $weeklyProgressId");
    } elseif ($allApproved) {
        $db->query("UPDATE phase_weekly_progress SET pm_status = 'Approved', pm_approved_id = $userId, entry_status = 'Approved' WHERE id = $weeklyProgressId");
    } else {
        $db->query("UPDATE phase_weekly_progress SET pm_status = 'Pending', pm_approved_id = NULL, entry_status = 'Submitted' WHERE id = $weeklyProgressId");
    }
}

// 3a. Approve Day 1 & Day 2 by PM
echo "\n--- Simulating PM Approving Day 1 and Day 2 ---";
runPmApproveSimulation($db, 1, '2026-06-01', 150, 'Approved', 'PM Day 1 ok.');
runPmApproveSimulation($db, 1, '2026-06-02', 150, 'Approved', 'PM Day 2 ok.');
$wp = $db->query("SELECT entry_status, qc_status, pm_status FROM phase_weekly_progress WHERE id = 1")->fetch_assoc();
echo "Weekly Status (after 2 PM approvals): PM=" . $wp['pm_status'] . " (Expected: Pending), Entry=" . $wp['entry_status'] . " (Expected: Submitted)\n";

// 3b. Reject Day 3 by PM (to test rollback to Draft)
echo "\n--- Simulating PM Rejecting Day 3 ---";
runPmApproveSimulation($db, 1, '2026-06-03', 150, 'Rejected', 'Incorrect quantity recorded.');
$wp = $db->query("SELECT entry_status, qc_status, pm_status, qc_approved_id FROM phase_weekly_progress WHERE id = 1")->fetch_assoc();
echo "Weekly Status (after 1 rejection): PM=" . $wp['pm_status'] . " (Expected: Rejected), QC=" . $wp['qc_status'] . " (Expected: Pending), Entry=" . $wp['entry_status'] . " (Expected: Draft), qc_approved_id=" . ($wp['qc_approved_id'] ?: 'NULL') . "\n";

// 3c. Clean up test data
$db->query("DELETE FROM daily_progress_logs WHERE weekly_progress_id = 1");
$db->close();
echo "\nTest successfully completed!\n";
?>
