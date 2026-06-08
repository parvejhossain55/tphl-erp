<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// SIMULATE PROJECTS LIST QUERY
$sql = "
    SELECT  p.id,
            p.name,
            p.workflow_version,
            COALESCE(prog.progress_pct, 0)    AS progress_pct
    FROM projects p
    LEFT JOIN (
        -- Migrated: phase-based weighted progress
        SELECT pp.project_id,
               SUM((COALESCE(pp.actual_progress,0) / 100) * COALESCE(pp.weight_percentage,0)) AS progress_pct
        FROM project_phases pp
        GROUP BY pp.project_id
    ) prog ON prog.project_id = p.id
    WHERE p.id = 13
";
$res = $db->query($sql);
$projects = $res->fetch_all(MYSQLI_ASSOC);

$phaseBasedProjectIds = [13];
$idsList = implode(',', $phaseBasedProjectIds);

$phasesRes = $db->query("
    SELECT project_id, weight_percentage, actual_progress, planned_progress
    FROM project_phases
    WHERE project_id IN ($idsList)
");
$phases = $phasesRes->fetch_all(MYSQLI_ASSOC);

$projectPhases = [];
foreach ($phases as $phase) {
    $projectPhases[(int)$phase['project_id']][] = $phase;
}

foreach ($projects as &$pr) {
    $pid = (int)$pr['id'];
    $phasesList = $projectPhases[$pid] ?? [];
    $overallActual = 0.0;
    $overallPlanned = 0.0;
    
    if (!empty($phasesList)) {
        foreach ($phasesList as $phase) {
            $weight = (float)$phase['weight_percentage'];
            $actual = (float)$phase['actual_progress'];
            $planned = (float)$phase['planned_progress'];
            
            $overallActual += $actual * ($weight / 100);
            $overallPlanned += $planned * ($weight / 100);
        }
    }
    
    $pr['overall_project_actual'] = $overallActual;
    $pr['overall_project_planned'] = $overallPlanned;
}

print_r($projects);
