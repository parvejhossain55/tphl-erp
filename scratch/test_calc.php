<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

function calculateDetailedProgress($db, array $projectIds) {
    if (empty($projectIds)) {
        return [];
    }

    $idsList = implode(',', array_map('intval', $projectIds));

    // 1. Fetch weight_percentage for the phases of these projects
    $phasesRes = $db->query("
        SELECT id, project_id, phase_name, weight_percentage, phase_order
        FROM project_phases
        WHERE project_id IN ($idsList)
        ORDER BY project_id, phase_order ASC
    ");
    $phases = $phasesRes ? $phasesRes->fetch_all(MYSQLI_ASSOC) : [];

    // Group phases by project
    $projectPhases = [];
    foreach ($phases as $phase) {
        $projectPhases[(int)$phase['project_id']][] = $phase;
    }

    // 2. Fetch all phase items (work items)
    $itemsRes = $db->query("
        SELECT ppi.id AS item_id, ppi.phase_id, ppi.item_name, ppi.item_order, ppi.weight_pct AS item_weight,
               pp.project_id
        FROM project_phase_items ppi
        INNER JOIN project_phases pp ON pp.id = ppi.phase_id
        WHERE pp.project_id IN ($idsList)
        ORDER BY ppi.phase_id, ppi.item_order ASC
    ");
    $items = $itemsRes ? $itemsRes->fetch_all(MYSQLI_ASSOC) : [];

    $phaseItems = [];
    foreach ($items as $item) {
        $phaseItems[(int)$item['phase_id']][] = $item;
    }

    // 3. Fetch all BOQ details for these projects' phases
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

    // Group details by project_id and phase_id
    $projectPhaseDetails = [];
    $itemDetails = [];
    foreach ($details as $d) {
        $pid = (int)$d['project_id'];
        $phaseId = (int)$d['phase_id'];
        $itemId = (int)$d['phase_item_id'];
        $projectPhaseDetails[$pid][$phaseId][] = $d;
        $itemDetails[$itemId][] = $d;
    }

    // 4. Fetch planned quantities from monthly targets
    $planRes = $db->query("
        SELECT pmti.phase_item_id, pmti.work_type_id, SUM(pmti.monthly_target_qty) AS total_target_qty,
               pmt.project_id
        FROM phase_monthly_target_items pmti
        INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
        WHERE pmt.project_id IN ($idsList)
        GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
    ");
    $plannedQuantities = $planRes ? $planRes->fetch_all(MYSQLI_ASSOC) : [];

    // 5. Fetch actual completed quantities
    // 5a. Approved weekly progress items
    $weeklyActRes = $db->query("
        SELECT pmti.phase_item_id, pmti.work_type_id, pmt.project_id,
               SUM(pwpi.achieved_qty) AS total_actual_qty
        FROM phase_weekly_progress_items pwpi
        INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
        INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
        INNER JOIN phase_monthly_targets pmt ON pmt.id = pmti.target_id
        WHERE pmt.project_id IN ($idsList)
          AND pwp.qc_status = 'Approved'
          AND pwp.pm_status = 'Approved'
        GROUP BY pmt.project_id, pmti.phase_item_id, pmti.work_type_id
    ");
    $weeklyActuals = $weeklyActRes ? $weeklyActRes->fetch_all(MYSQLI_ASSOC) : [];

    // 5b. Approved daily logs for pending/draft weeks (to avoid double counting, but capture intermediate work)
    $dailyActRes = $db->query("
        SELECT pmti.phase_item_id, pmti.work_type_id, dpl.project_id,
               SUM(dwil.qty_done_today) AS total_actual_qty
        FROM daily_work_item_logs dwil
        INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
        INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
        LEFT JOIN phase_monthly_target_items pmti ON pmti.id = dwil.target_item_id
        WHERE dpl.project_id IN ($idsList)
          AND dpl.qc_status = 'Approved'
          AND dpl.pm_status = 'Approved'
          AND NOT (pwp.qc_status = 'Approved' AND pwp.pm_status = 'Approved')
        GROUP BY dpl.project_id, pmti.phase_item_id, pmti.work_type_id
    ");
    $dailyActuals = $dailyActRes ? $dailyActRes->fetch_all(MYSQLI_ASSOC) : [];

    // Map data in PHP
    $targetsMap = [];
    foreach ($plannedQuantities as $q) {
        $pid = (int)$q['project_id'];
        $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
        $targetsMap[$pid][$key] = (float)$q['total_target_qty'];
    }

    $actualsMap = [];
    foreach ($weeklyActuals as $q) {
        $pid = (int)$q['project_id'];
        $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
        $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
    }
    foreach ($dailyActuals as $q) {
        $pid = (int)$q['project_id'];
        if ($q['phase_item_id'] !== null) {
            $key = $q['phase_item_id'] . '_' . $q['work_type_id'];
            $actualsMap[$pid][$key] = ($actualsMap[$pid][$key] ?? 0.0) + (float)$q['total_actual_qty'];
        }
    }

    $projectProgress = [];
    foreach ($projectIds as $pid) {
        $phasesList = $projectPhases[$pid] ?? [];
        $overallActual = 0.0;
        $overallPlanned = 0.0;
        $phasesData = [];

        foreach ($phasesList as $phase) {
            $phaseId = (int)$phase['id'];
            $phaseWeight = (float)$phase['weight_percentage'];

            $phaseDetails = $projectPhaseDetails[$pid][$phaseId] ?? [];
            $phasePlannedProgressWeighted = 0.0;
            $phaseActualProgressWeighted = 0.0;

            $totalBOQInPhase = 0.0;
            $totalApprovedInPhase = 0.0;
            $totalTargetInPhase = 0.0;

            foreach ($phaseDetails as $d) {
                $boqQty  = (float)$d['total_boq_qty'];
                $weight  = (float)$d['detail_weight'];
                $key = $d['phase_item_id'] . '_' . $d['work_type_id'];

                $targetQty = $targetsMap[$pid][$key] ?? 0.0;
                $actualQty = $actualsMap[$pid][$key] ?? 0.0;

                if ($boqQty > 0) {
                    // Planned (weighted)
                    $plannedRatio = min(1.0, $targetQty / $boqQty);
                    $phasePlannedProgressWeighted += $plannedRatio * $weight;

                    // Actual (weighted)
                    $actualRatio = min(1.0, $actualQty / $boqQty);
                    $phaseActualProgressWeighted += $actualRatio * $weight;
                }

                $totalBOQInPhase += $boqQty;
                $totalApprovedInPhase += $actualQty;
                $totalTargetInPhase += $targetQty;
            }

            // Phase internal progress: volume-based (e.g. 0.4% complete)
            $phaseActualVolumePct = $totalBOQInPhase > 0 ? ($totalApprovedInPhase / $totalBOQInPhase) * 100 : 0.0;
            $phasePlannedVolumePct = $totalBOQInPhase > 0 ? ($totalTargetInPhase / $totalBOQInPhase) * 100 : 0.0;

            // Project contribution (volume-based)
            $phaseContribution = $phaseActualVolumePct * ($phaseWeight / 100);

            // Add phase items
            $itemsData = [];
            $itemsInPhase = $phaseItems[$phaseId] ?? [];
            foreach ($itemsInPhase as $item) {
                $itemId = (int)$item['item_id'];
                $itemWeight = (float)$item['item_weight'];

                $detailsOfItem = $itemDetails[$itemId] ?? [];
                $itemPlannedProgressWeighted = 0.0;
                $itemActualProgressWeighted = 0.0;

                foreach ($detailsOfItem as $d) {
                    $boqQty  = (float)$d['total_boq_qty'];
                    $weight  = (float)$d['detail_weight'];
                    $key = $d['phase_item_id'] . '_' . $d['work_type_id'];

                    $targetQty = $targetsMap[$pid][$key] ?? 0.0;
                    $actualQty = $actualsMap[$pid][$key] ?? 0.0;

                    if ($boqQty > 0) {
                        $plannedRatio = min(1.0, $targetQty / $boqQty);
                        $itemPlannedProgressWeighted += $plannedRatio * $weight;

                        $actualRatio = min(1.0, $actualQty / $boqQty);
                        $itemActualProgressWeighted += $actualRatio * $weight;
                    }
                }

                $itemPlannedPct = $itemWeight > 0 ? ($itemPlannedProgressWeighted / $itemWeight) * 100 : 0.0;
                $itemActualPct = $itemWeight > 0 ? ($itemActualProgressWeighted / $itemWeight) * 100 : 0.0;

                $itemsData[] = [
                    'item_id' => $itemId,
                    'item_name' => $item['item_name'],
                    'item_order' => $item['item_order'],
                    'item_weight' => $itemWeight,
                    'item_planned_pct' => $itemPlannedPct,
                    'item_actual_pct' => $itemActualPct,
                ];
            }

            $overallActual += $phaseActualProgressWeighted * ($phaseWeight / 100);
            $overallPlanned += $phasePlannedProgressWeighted * ($phaseWeight / 100);

            $phasesData[] = [
                'phase_id' => $phaseId,
                'phase_name' => $phase['phase_name'],
                'phase_order' => $phase['phase_order'],
                'phase_weight' => $phaseWeight,
                'phase_actual_pct' => $phaseActualVolumePct,
                'phase_planned_pct' => $phasePlannedVolumePct,
                'phase_actual_weighted_pct' => $phaseActualProgressWeighted,
                'phase_planned_weighted_pct' => $phasePlannedProgressWeighted,
                'phase_contribution' => $phaseContribution,
                'items' => $itemsData,
            ];
        }

        $projectProgress[$pid] = [
            'actual'  => round($overallActual, 4),
            'planned' => round($overallPlanned, 4),
            'phases'  => $phasesData,
        ];
    }

    return $projectProgress;
}

$res = calculateDetailedProgress($db, [13]);
print_r($res[13]);
