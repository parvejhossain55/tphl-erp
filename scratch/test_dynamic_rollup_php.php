<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$projectIds = [13];
$idsList = implode(',', $projectIds);

// 1. Fetch weight_percentage for the phases of these projects
$phasesRes = $db->query("
    SELECT id, project_id, weight_percentage
    FROM project_phases
    WHERE project_id IN ($idsList)
");
$phases = $phasesRes ? $phasesRes->fetch_all(MYSQLI_ASSOC) : [];

$projectPhases = [];
foreach ($phases as $phase) {
    $projectPhases[(int)$phase['project_id']][] = $phase;
}

// 2. Fetch all BOQ details for these projects' phases
$dtRes = $db->query("
    SELECT ppi.phase_id, ppi.id AS phase_item_id, ppid.id AS detail_id,
           ppid.work_type_id, ppid.total_boq_qty, ppid.weight_pct AS detail_weight,
           pp.project_id
    FROM project_phase_items ppi
    INNER JOIN project_phase_item_details ppid ON ppid.phase_item_id = ppi.id
    INNER JOIN project_phases pp ON pp.id = ppi.phase_id
    WHERE pp.project_id IN ($idsList)
");
$details = $dtRes ? $dtRes->fetch_all(MYSQLI_ASSOC) : [];

// 3. Fetch planned quantities from monthly targets
$planRes = $db->query("
    SELECT pmti.phase_item_id, pmti.work_type_id, SUM(pmti.monthly_target_qty) AS total_target_qty,
           pmt.project_id
    FROM phase_monthly_target_items pmti
    INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
    WHERE pmt.project_id IN ($idsList)
    GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
");
$plannedQuantities = $planRes ? $planRes->fetch_all(MYSQLI_ASSOC) : [];

// 4. Fetch actual completed quantities from approved daily logs
$actRes = $db->query("
    SELECT COALESCE(dwil.phase_item_detail_id, ppid.id) AS phase_item_detail_id,
           SUM(dwil.qty_done_today) AS total_actual_qty,
           dpl.project_id
    FROM daily_work_item_logs dwil
    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
    LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
    LEFT JOIN project_phase_item_details ppid ON ppid.phase_item_id = pmti.phase_item_id AND ppid.work_type_id = dwil.work_type_id
    WHERE dpl.project_id IN ($idsList)
      AND dpl.qc_status = 'Approved'
      AND dpl.pm_status = 'Approved'
    GROUP BY dpl.project_id, COALESCE(dwil.phase_item_detail_id, ppid.id)
");
$actualQuantities = $actRes ? $actRes->fetch_all(MYSQLI_ASSOC) : [];

// Map data in PHP
$targetsMap = [];
foreach ($plannedQuantities as $q) {
    $pid = (int)$q['project_id'];
    $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
    $targetsMap[$pid][$key] = (float)$q['total_target_qty'];
}

$actualsMap = [];
foreach ($actualQuantities as $q) {
    $pid = (int)$q['project_id'];
    if ($q['phase_item_detail_id'] !== null) {
        $actualsMap[$pid][(int)$q['phase_item_detail_id']] = (float)$q['total_actual_qty'];
    }
}

// Group details by project_id and phase_id
$projectPhaseDetails = [];
foreach ($details as $d) {
    $pid = (int)$d['project_id'];
    $phaseId = (int)$d['phase_id'];
    $projectPhaseDetails[$pid][$phaseId][] = $d;
}

$projectProgress = [];
foreach ($projectIds as $pid) {
    $phasesList = $projectPhases[$pid] ?? [];
    $overallActual = 0.0;
    $overallPlanned = 0.0;

    if (!empty($phasesList)) {
        foreach ($phasesList as $phase) {
            $phaseId = (int)$phase['id'];
            $phaseWeight = (float)$phase['weight_percentage'];

            $phaseDetails = $projectPhaseDetails[$pid][$phaseId] ?? [];
            $phasePlannedProgress = 0.0;
            $phaseActualProgress = 0.0;

            if (!empty($phaseDetails)) {
                foreach ($phaseDetails as $d) {
                    $boqQty  = (float)$d['total_boq_qty'];
                    $weight  = (float)$d['detail_weight'];

                    if ($boqQty > 0) {
                        // Planned
                        $targetKey = $d['phase_item_id'] . '_' . $d['work_type_id'];
                        $targetQty = $targetsMap[$pid][$targetKey] ?? 0.0;
                        $plannedRatio = min(1.0, $targetQty / $boqQty);
                        $phasePlannedProgress += $plannedRatio * $weight;

                        // Actual
                        $actualQty = $actualsMap[$pid][(int)$d['detail_id']] ?? 0.0;
                        $actualRatio = min(1.0, $actualQty / $boqQty);
                        $phaseActualProgress += $actualRatio * $weight;
                    }
                }
            }

            $overallActual += $phaseActualProgress * ($phaseWeight / 100);
            $overallPlanned += $phasePlannedProgress * ($phaseWeight / 100);
        }
    }

    $projectProgress[$pid] = [
        'actual'  => round($overallActual, 2),
        'planned' => round($overallPlanned, 2),
    ];
}

echo "=== PROJECT 13 PROGRESS ===\n";
print_r($projectProgress);
