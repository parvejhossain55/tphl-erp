<?php
// Simple test script to verify linking of project drawings to phases

// Bootstrap connection
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "Database connected successfully.\n";

// Require the model
require_once __DIR__ . '/../app/models/Con_ProjectPhase.php';

$phaseModel = new Con_ProjectPhase($db);

// We'll test with project 13
$project_id = 13;

echo "\n--- 1. Current state of drawings for Project $project_id ---\n";
$res = $db->query("
    SELECT pd.id, pd.phase_id, dt.name AS design_name, pd.version
    FROM project_drawings pd
    LEFT JOIN design_types dt ON dt.id = pd.design_type_id
    WHERE pd.project_id = $project_id
");
$drawings = $res->fetch_all(MYSQLI_ASSOC);
foreach ($drawings as $dr) {
    echo "Drawing ID: {$dr['id']} | Design: {$dr['design_name']} | Version: {$dr['version']} | Linked Phase ID: " . ($dr['phase_id'] ?? 'NULL') . "\n";
}

// Get the first phase for project 13
$phases = $phaseModel->getByProject($project_id);
if (empty($phases)) {
    die("Error: No phases found for Project $project_id to run tests. Please set up phases first.\n");
}
$testPhase = $phases[0];
$testPhaseId = (int)$testPhase['id'];
echo "\nTesting with Phase ID: $testPhaseId ('{$testPhase['phase_name']}')\n";

if (empty($drawings)) {
    die("Error: No drawings found for Project $project_id to run tests. Please add drawings first.\n");
}
$testDrawing = $drawings[0];
$testDrawingId = (int)$testDrawing['id'];
echo "Testing with Drawing ID: $testDrawingId ('{$testDrawing['design_name']}')\n";

// Setup phases array for batchUpdate simulation
$phasesPayload = [];
foreach ($phases as $ph) {
    $phasesPayload[] = [
        'id'                => $ph['id'],
        'phase_name'        => $ph['phase_name'],
        'phase_order'       => $ph['phase_order'],
        'weight_percentage' => $ph['weight_percentage'],
        'planned_start_date'=> $ph['planned_start_date'],
        'planned_end_date'  => $ph['planned_end_date'],
        'status'            => $ph['status'],
        // Set the test drawing on our test phase
        'drawing_id'        => ($ph['id'] == $testPhaseId) ? $testDrawingId : null,
    ];
}

echo "\nSimulating batchUpdate with drawing $testDrawingId linked to phase $testPhaseId...\n";
$success = $phaseModel->batchUpdate($phasesPayload, $project_id);

if ($success) {
    echo "batchUpdate call succeeded!\n";
} else {
    echo "batchUpdate call FAILED!\n";
}

// Verify database state
$res = $db->query("SELECT phase_id FROM project_drawings WHERE id = $testDrawingId");
$updatedDrawing = $res->fetch_assoc();
echo "Updated drawing $testDrawingId phase_id: " . ($updatedDrawing['phase_id'] ?? 'NULL') . "\n";

if ($updatedDrawing['phase_id'] == $testPhaseId) {
    echo "SUCCESS: Drawing was correctly linked to the phase in database!\n";
} else {
    echo "FAILURE: drawing phase_id does not match the test phase ID!\n";
}

// Restore original phase_id link just in case
$originalPhaseId = $testDrawing['phase_id'] ? (int)$testDrawing['phase_id'] : 'NULL';
$db->query("UPDATE project_drawings SET phase_id = " . ($testDrawing['phase_id'] ? $originalPhaseId : 'NULL') . " WHERE id = $testDrawingId");
echo "Restored drawing $testDrawingId phase_id to: $originalPhaseId\n";
