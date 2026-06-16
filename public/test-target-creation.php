<?php
/**
 * Diagnostic Script for Monthly Target Creation
 * This script runs checks on the database tables and tests the insert query,
 * printing any database errors that might cause the HTTP 500 Network Error on live.
 * WARNING: Delete this file after use.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$root = dirname(__DIR__);
$configPath = $root . "/app/config/config.php";

if (!file_exists($configPath)) {
    die("Error: Config file not found at " . htmlspecialchars($configPath));
}

$config = require $configPath;
$dbHost = $config['db_host'] ?? 'localhost';
$dbUser = $config['db_user'] ?? 'root';
$dbPass = $config['db_pass'] ?? '';
$dbName = $config['db_name'] ?? '';

echo "<h3>Database Diagnostics for Monthly Target Creation (Project 9)</h3>";

$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($db->connect_error) {
    die("Database connection failed: " . htmlspecialchars($db->connect_error));
}
$db->set_charset("utf8mb4");

// 1. Check if tables exist
$tables = ['projects', 'project_phases', 'initial_estimate_budgets', 'phase_monthly_targets', 'contractors'];
foreach ($tables as $table) {
    $res = $db->query("SHOW TABLES LIKE '$table'");
    if ($res && $res->num_rows > 0) {
        echo "Table <strong style='color:green;'>$table</strong> exists.<br>";
    } else {
        echo "Table <strong style='color:red;'>$table</strong> <span style='color:red;font-weight:bold;'>does NOT exist!</span><br>";
    }
}

// 2. Check if project 9 exists
$projRes = $db->query("SELECT * FROM projects WHERE id = 9");
if ($projRes && $projRes->num_rows > 0) {
    $project = $projRes->fetch_assoc();
    echo "Project 9 exists: <strong>" . htmlspecialchars($project['name']) . "</strong><br>";
} else {
    echo "Project 9 <strong style='color:red;'>does NOT exist in 'projects' table!</strong><br>";
}

// 3. Check if budget estimate exists for project 9
$budgetRes = $db->query("SELECT COUNT(*) AS total FROM initial_estimate_budgets WHERE project_id = 9");
$budgetCount = $budgetRes ? (int)$budgetRes->fetch_assoc()['total'] : 0;
echo "Initial estimate budget count for project 9: <strong>$budgetCount</strong><br>";

// 4. Check project phases for project 9
$phaseRes = $db->query("SELECT id, phase_name, weight_percentage FROM project_phases WHERE project_id = 9");
if ($phaseRes && $phaseRes->num_rows > 0) {
    echo "Phases defined for project 9:<br>";
    while ($ph = $phaseRes->fetch_assoc()) {
        echo " - ID: " . $ph['id'] . " | Name: " . htmlspecialchars($ph['phase_name']) . " | Weight: " . $ph['weight_percentage'] . "%<br>";
    }
} else {
    echo "Phases for project 9: <strong style='color:red;'>None found!</strong><br>";
}

// 5. Check if we already have a target for project 9, phase 8 (Foundation & Piling) and June 2026
$targetMonth = '2026-06-01';
$existRes = $db->query("SELECT id FROM phase_monthly_targets WHERE project_id = 9 AND phase_id = 8 AND target_month = '$targetMonth'");
if ($existRes && $existRes->num_rows > 0) {
    $targetId = $existRes->fetch_assoc()['id'];
    echo "A target already exists for project 9, phase 8, month $targetMonth with ID: <strong>$targetId</strong>.<br>";
} else {
    echo "No target exists for project 9, phase 8, month $targetMonth (clear to insert).<br>";
}

// 6. Test prepare INSERT statement
echo "<h4>Testing INSERT prepare statement...</h4>";
$insertSql = "
    INSERT INTO phase_monthly_targets
        (project_id, phase_id, target_month, target_month_number, target_year,
         planning_status, responsible_team, contractor_id, created_by)
    VALUES (?,?,?,?,?,?,?,?,?)
";

$stmt = $db->prepare($insertSql);
if ($stmt) {
    echo "<span style='color:green;font-weight:bold;'>[SUCCESS]</span> INSERT statement prepared successfully.<br>";
    
    // Attempt dry-run insert inside transaction (to roll back afterward)
    $db->begin_transaction();
    
    // Let's find a valid phase_id for project 9 to use for test
    $phaseRes = $db->query("SELECT id FROM project_phases WHERE project_id = 9 LIMIT 1");
    $testPhaseId = $phaseRes && $phaseRes->num_rows > 0 ? (int)$phaseRes->fetch_assoc()['id'] : 99999;
    
    // Use a unique month to avoid UK violation (e.g. 2035-12-01)
    $testMonth = '2035-12-01';
    $mnum = 12;
    $yr = 2035;
    $planningStatus = 'Draft';
    $responsibleTeam = 'Test Team';
    $contractorId = null;
    $createdBy = 1;
    
    $projectId = 9;
    $stmt->bind_param('iisiissii',
        $projectId,
        $testPhaseId,
        $testMonth,
        $mnum,
        $yr,
        $planningStatus,
        $responsibleTeam,
        $contractorId,
        $createdBy
    );
    
    echo "Attempting test execution (inserting Project 9, Phase $testPhaseId, Month $testMonth)...<br>";
    if ($stmt->execute()) {
        echo "<span style='color:green;font-weight:bold;'>[SUCCESS]</span> Test target inserted successfully with ID: " . $db->insert_id . "<br>";
    } else {
        echo "<span style='color:red;font-weight:bold;'>[FAILED]</span> Execute failed: (" . $db->errno . ") " . htmlspecialchars($db->error) . "<br>";
    }
    
    $db->rollback();
    echo "Transaction rolled back. Database is clean.<br>";
    $stmt->close();
} else {
    echo "<span style='color:red;font-weight:bold;'>[FAILED]</span> Prepare failed: (" . $db->errno . ") " . htmlspecialchars($db->error) . "<br>";
}

// 7. Test project_phases INSERT statement
echo "<h4>Testing project_phases INSERT prepare statement...</h4>";
$phaseSql = "
    INSERT INTO project_phases
        (project_id, phase_name, phase_order, weight_percentage,
         planned_start_date, planned_end_date, status)
    VALUES (?, ?, ?, ?, ?, ?, ?)
";
$pStmt = $db->prepare($phaseSql);
if ($pStmt) {
    echo "<span style='color:green;font-weight:bold;'>[SUCCESS]</span> project_phases INSERT prepared successfully.<br>";
    $db->begin_transaction();
    $testPhaseName = "Test Phase";
    $testOrder = 99;
    $testWeight = 0.00;
    $testStart = "2026-06-01";
    $testEnd = "2026-08-01";
    $testStatus = "Not Started";
    $pStmt->bind_param('isidsss',
        $projectId,
        $testPhaseName,
        $testOrder,
        $testWeight,
        $testStart,
        $testEnd,
        $testStatus
    );
    if ($pStmt->execute()) {
        echo "<span style='color:green;font-weight:bold;'>[SUCCESS]</span> Test phase inserted successfully with ID: " . $db->insert_id . "<br>";
    } else {
        echo "<span style='color:red;font-weight:bold;'>[FAILED]</span> project_phases Execute failed: (" . $db->errno . ") " . htmlspecialchars($db->error) . "<br>";
    }
    $db->rollback();
    $pStmt->close();
} else {
    echo "<span style='color:red;font-weight:bold;'>[FAILED]</span> project_phases Prepare failed: (" . $db->errno . ") " . htmlspecialchars($db->error) . "<br>";
}

$db->close();
echo "<h4>Done</h4>";
