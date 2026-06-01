<?php

class Con_PhaseWeeklyProgress {
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // =========================================================================
    // GET OR CREATE WEEKLY PROGRESS SESSION
    // =========================================================================
    public function getOrCreate(int $targetId, int $weekNo): array {
        // Find monthly target details to get dates, project_id, and phase_id
        $stmt = $this->db->prepare("
            SELECT pmt.*, pp.phase_name, pp.planned_start_date, pp.planned_end_date
            FROM phase_monthly_targets pmt
            INNER JOIN project_phases pp ON pp.id = pmt.phase_id
            WHERE pmt.id = ?
        ");
        $stmt->bind_param('i', $targetId);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        if (!$target) {
            return [];
        }

        $projectId = (int)$target['project_id'];
        $phaseId   = (int)$target['phase_id'];

        // ── Base date: always start from the 1st of the TARGET MONTH ──────────
        // Use target_month (e.g. 2026-06-01) NOT phase planned_start_date,
        // because each monthly target covers exactly its own calendar month.
        $monthStart = new DateTime(substr($target['target_month'], 0, 7) . '-01');
        $baseDate   = clone $monthStart;

        // Last day of the target month (for capping week 4)
        $monthEnd = new DateTime($monthStart->format('Y-m-t')); // Y-m-t = last day of month

        // Calculate week start and end dates (7-day blocks within the month)
        if ($weekNo === 1) {
            $weekStart = $baseDate->format('Y-m-d');
            $weekEnd   = (clone $baseDate)->modify('+6 days')->format('Y-m-d');
        } elseif ($weekNo === 2) {
            $weekStart = (clone $baseDate)->modify('+7 days')->format('Y-m-d');
            $weekEnd   = (clone $baseDate)->modify('+13 days')->format('Y-m-d');
        } elseif ($weekNo === 3) {
            $weekStart = (clone $baseDate)->modify('+14 days')->format('Y-m-d');
            $weekEnd   = (clone $baseDate)->modify('+20 days')->format('Y-m-d');
        } else {
            // Week 4: starts on day 22, ends at the LAST DAY OF THE MONTH
            $weekStart = (clone $baseDate)->modify('+21 days')->format('Y-m-d');
            $weekEnd   = $monthEnd->format('Y-m-d');
            // Safety: if month has fewer than 22 days, fall back to +6 days
            if ($weekEnd < $weekStart) {
                $weekEnd = (clone $baseDate)->modify('+27 days')->format('Y-m-d');
            }
        }

        // Check if weekly progress session already exists
        $chk = $this->db->prepare("
            SELECT * FROM phase_weekly_progress 
            WHERE project_id = ? AND phase_id = ? AND target_id = ? AND week_number = ?
        ");
        $chk->bind_param('iiii', $projectId, $phaseId, $targetId, $weekNo);
        $chk->execute();
        $session = $chk->get_result()->fetch_assoc();

        if (!$session) {
            // Insert new session
            $ins = $this->db->prepare("
                INSERT INTO phase_weekly_progress 
                    (project_id, phase_id, target_id, week_number, week_start_date, week_end_date, entry_status)
                VALUES (?, ?, ?, ?, ?, ?, 'Draft')
            ");
            $ins->bind_param('iiiiss', $projectId, $phaseId, $targetId, $weekNo, $weekStart, $weekEnd);
            $ins->execute();
            $sessionId = (int)$this->db->insert_id;

            // Fetch target session again
            $chk->execute();
            $session = $chk->get_result()->fetch_assoc();
        } else {
            $sessionId = (int)$session['id'];
            // Sync start/end dates if they differ
            if ($session['week_start_date'] !== $weekStart || $session['week_end_date'] !== $weekEnd) {
                $upd = $this->db->prepare("
                    UPDATE phase_weekly_progress 
                    SET week_start_date = ?, week_end_date = ? 
                    WHERE id = ?
                ");
                $upd->bind_param('ssi', $weekStart, $weekEnd, $sessionId);
                $upd->execute();
                $session['week_start_date'] = $weekStart;
                $session['week_end_date'] = $weekEnd;
            }
        }

        // Sync weekly progress items with the monthly target items
        $itemsStmt = $this->db->prepare("
            SELECT * FROM phase_monthly_target_items WHERE target_id = ?
        ");
        $itemsStmt->bind_param('i', $targetId);
        $itemsStmt->execute();
        $mItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Fetch existing weekly progress items
        $wItemsStmt = $this->db->prepare("
            SELECT id, target_item_id, planned_qty FROM phase_weekly_progress_items WHERE weekly_progress_id = ?
        ");
        $wItemsStmt->bind_param('i', $sessionId);
        $wItemsStmt->execute();
        $wItems = $wItemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $wItemsByTargetId = [];
        foreach ($wItems as $wi) {
            $wItemsByTargetId[(int)$wi['target_item_id']] = $wi;
        }

        $insItem = $this->db->prepare("
            INSERT INTO phase_weekly_progress_items 
                (weekly_progress_id, target_item_id, planned_qty, achieved_qty, percent_complete, responsible_team, status)
            VALUES (?, ?, ?, 0.00, 0.00, ?, 'Not Started')
        ");

        $updItem = $this->db->prepare("
            UPDATE phase_weekly_progress_items 
            SET planned_qty = ?, responsible_team = ?
            WHERE id = ?
        ");

        foreach ($mItems as $mi) {
            // Determine target quantity based on week number
            $plannedQty = 0.00;
            if ($weekNo === 1) $plannedQty = (float)$mi['week_1_qty'];
            elseif ($weekNo === 2) $plannedQty = (float)$mi['week_2_qty'];
            elseif ($weekNo === 3) $plannedQty = (float)$mi['week_3_qty'];
            elseif ($weekNo === 4) $plannedQty = (float)$mi['week_4_qty'];

            $team = $mi['responsible_team'] ?? '';
            $targetItemId = (int)$mi['id'];

            if (isset($wItemsByTargetId[$targetItemId])) {
                $existingWi = $wItemsByTargetId[$targetItemId];
                if ((float)$existingWi['planned_qty'] !== $plannedQty) {
                    $updItem->bind_param('dsi', $plannedQty, $team, $existingWi['id']);
                    $updItem->execute();
                }
            } else {
                $insItem->bind_param('iids', $sessionId, $targetItemId, $plannedQty, $team);
                $insItem->execute();
            }
        }

        return $session;
    }

    // =========================================================================
    // GET WEEKLY ITEMS
    // =========================================================================
    public function getWeeklyItems(int $weeklyProgressId): array {
        $stmt = $this->db->prepare("
            SELECT pwpi.*,
                   pmti.work_item_name,
                   pmti.unit,
                   pmti.priority,
                   pmti.monthly_target_qty,
                   pmti.actual_qty          AS monthly_actual_qty,
                   pmti.actual_progress_pct AS monthly_actual_pct,
                   pmti.planned_progress_pct AS monthly_planned_pct,
                   pmti.start_date          AS item_start_date,
                   pmti.end_date            AS item_end_date,
                   wt.work_type_name,
                   ppi.weight_pct           AS sub_item_weight,
                   ppi.id                   AS phase_item_id
            FROM phase_weekly_progress_items pwpi
            INNER JOIN phase_monthly_target_items pmti ON pmti.id = pwpi.target_item_id
            INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
            LEFT JOIN work_types wt ON wt.id = pmti.work_type_id
            LEFT JOIN project_phase_items ppi ON ppi.phase_id = pwp.phase_id 
                 AND (
                     ppi.item_name = pmti.work_item_name COLLATE utf8mb4_general_ci
                     OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, ' - %')
                     OR pmti.work_item_name LIKE CONCAT(ppi.item_name COLLATE utf8mb4_general_ci, '%')
                 )
            WHERE pwpi.weekly_progress_id = ?
            ORDER BY pmti.sort_order ASC, pwpi.id ASC
        ");
        $stmt->bind_param('i', $weeklyProgressId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // GET OR CREATE DAILY PROGRESS LOG
    // =========================================================================
    public function getOrCreateDailyLog(int $weeklyProgressId, string $date, int $projectId): array {
        $stmt = $this->db->prepare("
            SELECT * FROM daily_progress_logs 
            WHERE weekly_progress_id = ? AND log_date = ?
        ");
        $stmt->bind_param('is', $weeklyProgressId, $date);
        $stmt->execute();
        $log = $stmt->get_result()->fetch_assoc();

        if (!$log) {
            $ins = $this->db->prepare("
                INSERT INTO daily_progress_logs 
                    (weekly_progress_id, project_id, log_date)
                VALUES (?, ?, ?)
            ");
            $ins->bind_param('iis', $weeklyProgressId, $projectId, $date);
            $ins->execute();

            $stmt->execute();
            $log = $stmt->get_result()->fetch_assoc();
        }

        return $log;
    }

    // =========================================================================
    // SAVE DAILY LOG & UPDATE CUMULATIVE PROGRESS
    // =========================================================================
    public function saveDailyLog(int $weeklyProgressId, array $logData, array $itemQtys, int $userId, array $uploadedPhotos = []): bool {
        $this->db->begin_transaction();
        try {
            $date = $logData['log_date'];
            $projectId = (int)$logData['project_id'];

            // 0. Update photo paths for uploaded photos
            if (!empty($uploadedPhotos)) {
                $updPhoto = $this->db->prepare("
                    UPDATE phase_weekly_progress_items 
                    SET photo_path = ? 
                    WHERE weekly_progress_id = ? AND target_item_id = ?
                ");
                foreach ($uploadedPhotos as $itemId => $photoPath) {
                    $updPhoto->bind_param('sii', $photoPath, $weeklyProgressId, $itemId);
                    $updPhoto->execute();
                }
            }

            // 1. Get or create daily log
            $log = $this->getOrCreateDailyLog($weeklyProgressId, $date, $projectId);
            $logId = (int)$log['id'];

            // 2. Update daily progress log resource fields
            $updLog = $this->db->prepare("
                UPDATE daily_progress_logs SET
                    labour_count        = ?,
                    cement_bags         = ?,
                    concrete_mt         = ?,
                    concrete_cft        = ?,
                    materials_used      = ?,
                    equipment_active    = ?,
                    weather             = ?,
                    temperature_celsius = ?,
                    safety_ok           = ?,
                    qc_checked          = ?,
                    site_remarks        = ?,
                    created_by          = ?
                WHERE id = ?
            ");
            
            $labourCount   = (int)($logData['labour_count'] ?? 0);
            $cementBags    = (int)($logData['cement_bags'] ?? 0);
            $concreteMt    = (float)($logData['concrete_mt'] ?? 0);
            $concreteCft   = (float)($logData['concrete_cft'] ?? 0);
            $materialsUsed = (int)($logData['materials_used'] ?? 0);
            $equipActive   = (int)($logData['equipment_active'] ?? 0);
            $weather       = $logData['weather'] ?? null;
            $tempC         = !empty($logData['temperature_celsius']) ? (float)$logData['temperature_celsius'] : null;
            $safetyOk      = (int)($logData['safety_ok'] ?? 0);
            $qcChecked     = (int)($logData['qc_checked'] ?? 0);
            $remarks       = $logData['site_remarks'] ?? null;

            $updLog->bind_param('iiddiisdiiisi', 
                $labourCount, $cementBags, $concreteMt, $concreteCft,
                $materialsUsed, $equipActive, $weather, $tempC,
                $safetyOk, $qcChecked, $remarks, $userId, $logId
            );
            $updLog->execute();

            // 3. Update daily work item quantities
            // Clean up existing logs for this date first, then re-insert active logs
            $delStmt = $this->db->prepare("DELETE FROM daily_work_item_logs WHERE daily_log_id = ?");
            $delStmt->bind_param('i', $logId);
            $delStmt->execute();

            $insItem = $this->db->prepare("
                INSERT INTO daily_work_item_logs 
                    (daily_log_id, target_item_id, work_type_id, qty_done_today, unit, remarks, item_status, responsible_team)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($itemQtys as $item) {
                $itemId       = (int)$item['target_item_id'];
                $wtId         = !empty($item['work_type_id']) ? (int)$item['work_type_id'] : null;
                $qtyToday     = (float)($item['qty_done_today'] ?? 0);
                $unit         = !empty($item['unit']) ? $item['unit'] : null;
                $itemRemarks  = trim($item['remarks'] ?? '');
                $itemStatus   = trim($item['item_status'] ?? 'In Progress');
                $respTeam     = trim($item['responsible_team'] ?? '');

                if ($qtyToday > 0 || $itemRemarks !== '' || $itemStatus !== 'Not Started') {
                    $insItem->bind_param('iiidssss', $logId, $itemId, $wtId, $qtyToday, $unit, $itemRemarks, $itemStatus, $respTeam);
                    $insItem->execute();
                }
            }

            // 4. Recalculate Weekly Achievements for each item
            // Get all weekly progress item rows for this week
            $wItems = $this->getWeeklyItems($weeklyProgressId);
            $updWeeklyItem = $this->db->prepare("
                UPDATE phase_weekly_progress_items SET
                    achieved_qty     = ?,
                    percent_complete = ?,
                    status           = ?
                WHERE id = ?
            ");

            foreach ($wItems as $wi) {
                $targetItemId = (int)$wi['target_item_id'];
                $plannedQty   = (float)$wi['planned_qty'];

                // Sum quantities done across all days in this weekly progress session
                $sumQuery = $this->db->prepare("
                    SELECT SUM(dwil.qty_done_today) AS total_achieved
                    FROM daily_work_item_logs dwil
                    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                    WHERE dpl.weekly_progress_id = ? AND dwil.target_item_id = ?
                ");
                $sumQuery->bind_param('ii', $weeklyProgressId, $targetItemId);
                $sumQuery->execute();
                $totalAchieved = (float)($sumQuery->get_result()->fetch_assoc()['total_achieved'] ?? 0);

                // Calculate percent complete
                $pct = 0.00;
                if ($plannedQty > 0) {
                    $pct = ($totalAchieved / $plannedQty) * 100;
                }

                // Determine Status
                $status = 'Not Started';
                
                // Get the latest status from daily logs for this week
                $statusQuery = $this->db->prepare("
                    SELECT dwil.item_status 
                    FROM daily_work_item_logs dwil
                    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                    WHERE dpl.weekly_progress_id = ? AND dwil.target_item_id = ?
                    ORDER BY dpl.log_date DESC, dwil.id DESC
                    LIMIT 1
                ");
                $statusQuery->bind_param('ii', $weeklyProgressId, $targetItemId);
                $statusQuery->execute();
                $latestDailyStatus = $statusQuery->get_result()->fetch_assoc()['item_status'] ?? null;

                if ($pct >= 100 && $plannedQty > 0) {
                    $status = 'Completed';
                } elseif ($latestDailyStatus) {
                    $status = $latestDailyStatus;
                } elseif ($totalAchieved > 0) {
                    $status = 'In Progress';
                }

                $pwpiId = (int)$wi['id'];
                $updWeeklyItem->bind_param('ddsi', $totalAchieved, $pct, $status, $pwpiId);
                $updWeeklyItem->execute();

                // 5. Update Monthly Target Item's Actual Quantity and Actual Progress
                // Cumulative Achieved: Sum achieved quantities across all weekly sessions for this target_item_id
                $cumQuery = $this->db->prepare("
                    SELECT SUM(pwpi.achieved_qty) AS cumulative_achieved
                    FROM phase_weekly_progress_items pwpi
                    INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                    WHERE pwpi.target_item_id = ?
                ");
                $cumQuery->bind_param('i', $targetItemId);
                $cumQuery->execute();
                $cumulativeAchieved = (float)($cumQuery->get_result()->fetch_assoc()['cumulative_achieved'] ?? 0);

                // Fetch monthly target item to get monthly target qty
                $mItemStmt = $this->db->prepare("SELECT monthly_target_qty FROM phase_monthly_target_items WHERE id = ?");
                $mItemStmt->bind_param('i', $targetItemId);
                $mItemStmt->execute();
                $mItem = $mItemStmt->get_result()->fetch_assoc();
                
                $mTargetQty = (float)($mItem['monthly_target_qty'] ?? 0);
                $cumPct = 0.00;
                if ($mTargetQty > 0) {
                    $cumPct = ($cumulativeAchieved / $mTargetQty) * 100;
                }
                $cumPct = min(100.0, $cumPct);

                $updMItem = $this->db->prepare("
                    UPDATE phase_monthly_target_items SET
                        actual_qty = ?,
                        actual_progress_pct = ?
                    WHERE id = ?
                ");
                $updMItem->bind_param('ddi', $cumulativeAchieved, $cumPct, $targetItemId);
                $updMItem->execute();
            }

            // 5. Aggregate weekly totals into phase_weekly_progress
            $aggQuery = $this->db->prepare("
                SELECT 
                    SUM(labour_count) AS total_labour,
                    SUM(cement_bags) AS total_cement,
                    SUM(concrete_mt) AS total_mt,
                    SUM(concrete_cft) AS total_cft,
                    SUM(materials_used) AS total_materials,
                    SUM(equipment_active) AS total_equip
                FROM daily_progress_logs
                WHERE weekly_progress_id = ?
            ");
            $aggQuery->bind_param('i', $weeklyProgressId);
            $aggQuery->execute();
            $agg = $aggQuery->get_result()->fetch_assoc();

            $totalPlannedQuery = $this->db->prepare("
                SELECT SUM(planned_qty) AS tot_plan, SUM(achieved_qty) AS tot_achieved, AVG(percent_complete) AS avg_pct
                FROM phase_weekly_progress_items
                WHERE weekly_progress_id = ?
            ");
            $totalPlannedQuery->bind_param('i', $weeklyProgressId);
            $totalPlannedQuery->execute();
            $planAgg = $totalPlannedQuery->get_result()->fetch_assoc();

            $updWeekMaster = $this->db->prepare("
                UPDATE phase_weekly_progress SET
                    overall_progress_pct = ?,
                    total_planned_qty    = ?,
                    total_achieved_qty   = ?,
                    labour_used          = ?,
                    cement_used_bags     = ?,
                    concrete_poured_mt   = ?,
                    concrete_poured_cft  = ?,
                    materials_used       = ?,
                    equipment_active     = ?
                WHERE id = ?
            ");
            
            $avgPct = (float)($planAgg['avg_pct'] ?? 0);
            $totPlan = (float)($planAgg['tot_plan'] ?? 0);
            $totAchieved = (float)($planAgg['tot_achieved'] ?? 0);
            $totLabour = (int)($agg['total_labour'] ?? 0);
            $totCement = (int)($agg['total_cement'] ?? 0);
            $totMt = (float)($agg['total_mt'] ?? 0);
            $totCft = (float)($agg['total_cft'] ?? 0);
            $totMaterials = (int)($agg['total_materials'] ?? 0);
            $totEquip = (int)($agg['total_equip'] ?? 0);

            $updWeekMaster->bind_param('dddiidddii',
                $avgPct, $totPlan, $totAchieved,
                $totLabour, $totCement, $totMt, $totCft,
                $totMaterials, $totEquip, $weeklyProgressId
            );
            $updWeekMaster->execute();

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('saveDailyLog transaction error: ' . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // UPDATE WEEKLY PROGRESS SESSION STATUS
    // =========================================================================
    public function updateWeeklyProgressDetails(int $id, array $d): bool {
        $stmt = $this->db->prepare("
            UPDATE phase_weekly_progress SET
                overall_progress_pct  = ?,
                weather_condition     = ?,
                temperature_celsius   = ?,
                site_engineer_remarks = ?,
                safety_ok             = ?,
                qc_checked            = ?,
                material_received     = ?,
                entry_status          = ?,
                submitted_by          = ?,
                submitted_at          = NOW(),
                updated_at            = NOW()
            WHERE id = ?
        ");
        
        $pct      = (float)($d['overall_progress_pct'] ?? 0);
        $weather  = $d['weather_condition'] ?? null;
        $temp     = !empty($d['temperature_celsius']) ? (float)$d['temperature_celsius'] : null;
        $remarks  = $d['site_engineer_remarks'] ?? null;
        $safety   = (int)($d['safety_ok'] ?? 0);
        $qc       = (int)($d['qc_checked'] ?? 0);
        $matRec   = (int)($d['material_received'] ?? 0);
        $status   = $d['entry_status'] ?? 'Draft';
        $subBy    = $d['submitted_by'] ?? null;

        $stmt->bind_param('dsdsiiiisi', 
            $pct, $weather, $temp, $remarks,
            $safety, $qc, $matRec, $status, $subBy, $id
        );
        return $stmt->execute();
    }

    // =========================================================================
    // GET DAILY WORK LOG QUANTITIES FOR A GIVEN DATE
    // =========================================================================
    public function getDailyQuantities(int $weeklyProgressId, string $date): array {
        $stmt = $this->db->prepare("
            SELECT dwil.id AS log_row_id, dwil.target_item_id, dwil.work_type_id,
                   dwil.qty_done_today, dwil.remarks,
                   dwil.item_status, dwil.responsible_team,
                   wt.work_type_name
            FROM daily_work_item_logs dwil
            INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
            LEFT JOIN work_types wt ON wt.id = dwil.work_type_id
            WHERE dpl.weekly_progress_id = ? AND dpl.log_date = ?
        ");
        $stmt->bind_param('is', $weeklyProgressId, $date);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // GET ALL DAILY LOGS FOR PROGRESS VISUALS
    // =========================================================================
    public function getDailyLogsForWeek(int $weeklyProgressId): array {
        $stmt = $this->db->prepare("
            SELECT dpl.*,
                   (SELECT COUNT(*) FROM daily_work_item_logs WHERE daily_log_id = dpl.id AND qty_done_today > 0) AS active_items_count
            FROM daily_progress_logs dpl
            WHERE dpl.weekly_progress_id = ?
            ORDER BY dpl.log_date ASC
        ");
        $stmt->bind_param('i', $weeklyProgressId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // =========================================================================
    // DELETE DAILY LOG ITEM & RECALCULATE PROGRESS
    // =========================================================================
    public function deleteDailyLogItem(int $logRowId): array {
        $this->db->begin_transaction();
        try {
            // 1. Fetch metadata before deleting
            $stmt = $this->db->prepare("
                SELECT dwil.target_item_id, dpl.weekly_progress_id, dpl.log_date, pwp.target_id, pwp.week_number
                FROM daily_work_item_logs dwil
                INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                INNER JOIN phase_weekly_progress pwp ON pwp.id = dpl.weekly_progress_id
                WHERE dwil.id = ?
            ");
            $stmt->bind_param('i', $logRowId);
            $stmt->execute();
            $meta = $stmt->get_result()->fetch_assoc();
            if (!$meta) {
                $this->db->rollback();
                return [];
            }

            $targetItemId = (int)$meta['target_item_id'];
            $weeklyProgressId = (int)$meta['weekly_progress_id'];

            // 2. Delete the row
            $delStmt = $this->db->prepare("DELETE FROM daily_work_item_logs WHERE id = ?");
            $delStmt->bind_param('i', $logRowId);
            $delStmt->execute();

            // 3. Recalculate Weekly Achievements for all items in this weekly session
            $wItems = $this->getWeeklyItems($weeklyProgressId);
            $updWeeklyItem = $this->db->prepare("
                UPDATE phase_weekly_progress_items SET
                    achieved_qty     = ?,
                    percent_complete = ?,
                    status           = ?
                WHERE id = ?
            ");

            foreach ($wItems as $wi) {
                $tItemId = (int)$wi['target_item_id'];
                $plannedQty = (float)$wi['planned_qty'];

                // Sum quantities done across all days in this weekly progress session
                $sumQuery = $this->db->prepare("
                    SELECT SUM(dwil.qty_done_today) AS total_achieved
                    FROM daily_work_item_logs dwil
                    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                    WHERE dpl.weekly_progress_id = ? AND dwil.target_item_id = ?
                ");
                $sumQuery->bind_param('ii', $weeklyProgressId, $tItemId);
                $sumQuery->execute();
                $totalAchieved = (float)($sumQuery->get_result()->fetch_assoc()['total_achieved'] ?? 0);

                // Calculate percent complete
                $pct = 0.00;
                if ($plannedQty > 0) {
                    $pct = ($totalAchieved / $plannedQty) * 100;
                }

                // Determine Status
                $status = 'Not Started';

                // Get the latest status from daily logs for this week
                $statusQuery = $this->db->prepare("
                    SELECT dwil.item_status 
                    FROM daily_work_item_logs dwil
                    INNER JOIN daily_progress_logs dpl ON dpl.id = dwil.daily_log_id
                    WHERE dpl.weekly_progress_id = ? AND dwil.target_item_id = ?
                    ORDER BY dpl.log_date DESC, dwil.id DESC
                    LIMIT 1
                ");
                $statusQuery->bind_param('ii', $weeklyProgressId, $tItemId);
                $statusQuery->execute();
                $latestDailyStatus = $statusQuery->get_result()->fetch_assoc()['item_status'] ?? null;

                if ($pct >= 100 && $plannedQty > 0) {
                    $status = 'Completed';
                } elseif ($latestDailyStatus) {
                    $status = $latestDailyStatus;
                } elseif ($totalAchieved > 0) {
                    $status = 'In Progress';
                }

                $pwpiId = (int)$wi['id'];
                $updWeeklyItem->bind_param('ddsi', $totalAchieved, $pct, $status, $pwpiId);
                $updWeeklyItem->execute();

                // 4. Update Monthly Target Item's Actual Quantity and Actual Progress
                $cumQuery = $this->db->prepare("
                    SELECT SUM(pwpi.achieved_qty) AS cumulative_achieved
                    FROM phase_weekly_progress_items pwpi
                    INNER JOIN phase_weekly_progress pwp ON pwp.id = pwpi.weekly_progress_id
                    WHERE pwpi.target_item_id = ?
                ");
                $cumQuery->bind_param('i', $tItemId);
                $cumQuery->execute();
                $cumulativeAchieved = (float)($cumQuery->get_result()->fetch_assoc()['cumulative_achieved'] ?? 0);

                // Fetch monthly target item to get monthly target qty
                $mItemStmt = $this->db->prepare("SELECT monthly_target_qty FROM phase_monthly_target_items WHERE id = ?");
                $mItemStmt->bind_param('i', $tItemId);
                $mItemStmt->execute();
                $mItem = $mItemStmt->get_result()->fetch_assoc();

                $mTargetQty = (float)($mItem['monthly_target_qty'] ?? 0);
                $cumPct = 0.00;
                if ($mTargetQty > 0) {
                    $cumPct = ($cumulativeAchieved / $mTargetQty) * 100;
                }
                $cumPct = min(100.0, $cumPct);

                $updMItem = $this->db->prepare("
                    UPDATE phase_monthly_target_items SET
                        actual_qty = ?,
                        actual_progress_pct = ?
                    WHERE id = ?
                ");
                $updMItem->bind_param('ddi', $cumulativeAchieved, $cumPct, $tItemId);
                $updMItem->execute();
            }

            // 5. Aggregate weekly totals into phase_weekly_progress
            $aggQuery = $this->db->prepare("
                SELECT 
                    SUM(labour_count) AS total_labour,
                    SUM(cement_bags) AS total_cement,
                    SUM(concrete_mt) AS total_mt,
                    SUM(concrete_cft) AS total_cft,
                    SUM(materials_used) AS total_materials,
                    SUM(equipment_active) AS total_equip
                FROM daily_progress_logs
                WHERE weekly_progress_id = ?
            ");
            $aggQuery->bind_param('i', $weeklyProgressId);
            $aggQuery->execute();
            $agg = $aggQuery->get_result()->fetch_assoc();

            $totalPlannedQuery = $this->db->prepare("
                SELECT SUM(planned_qty) AS tot_plan, SUM(achieved_qty) AS tot_achieved, AVG(percent_complete) AS avg_pct
                FROM phase_weekly_progress_items
                WHERE weekly_progress_id = ?
            ");
            $totalPlannedQuery->bind_param('i', $weeklyProgressId);
            $totalPlannedQuery->execute();
            $planAgg = $totalPlannedQuery->get_result()->fetch_assoc();

            $updWeekMaster = $this->db->prepare("
                UPDATE phase_weekly_progress SET
                    overall_progress_pct = ?,
                    total_planned_qty    = ?,
                    total_achieved_qty   = ?,
                    labour_used          = ?,
                    cement_used_bags     = ?,
                    concrete_poured_mt   = ?,
                    concrete_poured_cft  = ?,
                    materials_used       = ?,
                    equipment_active     = ?
                WHERE id = ?
            ");

            $avgPct = (float)($planAgg['avg_pct'] ?? 0);
            $totPlan = (float)($planAgg['tot_plan'] ?? 0);
            $totAchieved = (float)($planAgg['tot_achieved'] ?? 0);
            $totLabour = (int)($agg['total_labour'] ?? 0);
            $totCement = (int)($agg['total_cement'] ?? 0);
            $totMt = (float)($agg['total_mt'] ?? 0);
            $totCft = (float)($agg['total_cft'] ?? 0);
            $totMaterials = (int)($agg['total_materials'] ?? 0);
            $totEquip = (int)($agg['total_equip'] ?? 0);

            $updWeekMaster->bind_param('dddiidddii',
                $avgPct, $totPlan, $totAchieved,
                $totLabour, $totCement, $totMt, $totCft,
                $totMaterials, $totEquip, $weeklyProgressId
            );
            $updWeekMaster->execute();

            $this->db->commit();
            return $meta;
        } catch (\Exception $e) {
            $this->db->rollback();
            error_log('deleteDailyLogItem transaction error: ' . $e->getMessage());
            return [];
        }
    }
}
