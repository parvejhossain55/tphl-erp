<?php
namespace Phase2;

use Controller;
use Con_PhaseWeeklyProgress;
use Con_MonthlyTarget;

class Con_PhaseWeeklyProgressController extends Controller {

    protected $db;
    protected $model;
    protected $monthlyTargetModel;

    public function __construct($db) {
        $this->db                 = $db;
        $this->model              = new Con_PhaseWeeklyProgress($db);
        $this->monthlyTargetModel = new Con_MonthlyTarget($db);

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user'])) {
            header("Location: " . $GLOBALS['base_url'] . "/login"); exit;
        }
    }

    // =========================================================================
    // SHOW WEEKLY PROGRESS & DAILY LOG ENTRY PAGE
    // =========================================================================
    public function show($targetId) {
        $targetId = (int)$targetId;

        // Fetch monthly target details
        $target = $this->monthlyTargetModel->find($targetId);
        if (!$target) {
            setError("Monthly target not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-projects");
            exit;
        }

        // Determine Week Number: default to 1, or read from query parameter
        $weekNo = isset($_GET['week']) ? (int)$_GET['week'] : 1;
        if ($weekNo < 1 || $weekNo > 4) $weekNo = 1;

        // Fetch or create the weekly progress master record
        $weeklyProgress = $this->model->getOrCreate($targetId, $weekNo);
        if (empty($weeklyProgress)) {
            setError("Could not load weekly progress.");
            header("Location: " . $GLOBALS['base_url'] . "/con-monthly-target/" . $targetId);
            exit;
        }

        $weeklyProgressId = (int)$weeklyProgress['id'];

        // Determine Selected Log Date: default to today or week start date
        $selectedDate = isset($_GET['log_date']) ? trim($_GET['log_date']) : date('Y-m-d');
        
        // Ensure selected date falls within the week start and end dates
        $start = $weeklyProgress['week_start_date'];
        $end   = $weeklyProgress['week_end_date'];
        if ($selectedDate < $start || $selectedDate > $end) {
            $selectedDate = $start; // Reset to start of week if out of range
        }

        // Fetch weekly items (Planned vs Achieved)
        $weeklyItems = $this->model->getWeeklyItems($weeklyProgressId);

        // Fetch daily logs and quantities for the selected log date
        $dailyLog = $this->model->getOrCreateDailyLog($weeklyProgressId, $selectedDate, (int)$target['project_id']);
        $dailyQuantities = $this->model->getDailyQuantities($weeklyProgressId, $selectedDate);

        // Fetch daily logs summary list for Mon-Sun progress visual block
        $weeklyLogsSummary = $this->model->getDailyLogsForWeek($weeklyProgressId);

        // Get project detail
        $pStmt = $this->db->prepare("SELECT id, name FROM projects WHERE id = ?");
        $pStmt->bind_param('i', $target['project_id']);
        $pStmt->execute();
        $project = $pStmt->get_result()->fetch_assoc();

        // Fetch all work types for this active phase name
        $wtStmt = $this->db->prepare("
            SELECT wt.id, wt.work_type_name, dt.name AS design_type_name
            FROM work_types wt
            LEFT JOIN design_types dt ON dt.id = wt.design_type_id
            WHERE wt.default_phase_name = ?
            ORDER BY wt.work_type_name ASC
        ");
        $phaseName = $target['phase_name'];
        $wtStmt->bind_param('s', $phaseName);
        $wtStmt->execute();
        $workTypes = $wtStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($workTypes)) {
            $wtRes = $this->db->query("
                SELECT wt.id, wt.work_type_name, dt.name AS design_type_name
                FROM work_types wt
                LEFT JOIN design_types dt ON dt.id = wt.design_type_id
                ORDER BY wt.work_type_name ASC
            ");
            $workTypes = $wtRes ? $wtRes->fetch_all(MYSQLI_ASSOC) : [];
        }

        // ── Fetch Material Issues linked to this monthly target ───────────────
        $materialIssues = [];
        try {
            $miStmt = $this->db->prepare("
                SELECT mi.id, mi.quantity, mi.issue_date, mi.purpose,
                       m.name AS material_name, m.unit,
                       c.name AS contractor_name
                FROM con_material_issues mi
                LEFT JOIN con_materials m ON m.id = mi.material_id
                LEFT JOIN contractors  c ON c.id = mi.issued_to
                WHERE mi.target_id = ?
                ORDER BY mi.issue_date DESC, mi.id DESC
            ");
            $miStmt->bind_param('i', $targetId);
            $miStmt->execute();
            $materialIssues = $miStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (\Throwable $e) {}

        $this->view('phase2/weekly_progress/entry', [
            'current_route'     => 'con-weekly-progress',
            'page_title'        => 'Daily / Weekly Progress Entry',
            'target'            => $target,
            'weeklyProgress'    => $weeklyProgress,
            'weeklyItems'       => $weeklyItems,
            'selectedDate'      => $selectedDate,
            'dailyLog'          => $dailyLog,
            'dailyQuantities'   => $dailyQuantities,
            'weeklyLogsSummary' => $weeklyLogsSummary,
            'project'           => $project,
            'weekNo'            => $weekNo,
            'workTypes'         => $workTypes,
            'materialIssues'    => $materialIssues,
        ]);
    }

    // =========================================================================
    // SAVE DAILY LOG (POST)
    // =========================================================================
    public function saveDailyLog() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        $weeklyProgressId = (int)($_POST['weekly_progress_id'] ?? 0);
        $targetId         = (int)($_POST['target_id'] ?? 0);
        $weekNo           = (int)($_POST['week_number'] ?? 1);
        $logDate          = trim($_POST['log_date'] ?? '');

        if (!$weeklyProgressId || !$targetId || !$logDate) {
            setError("Required parameters missing.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // Assemble daily progress log resource fields
        $logData = [
            'project_id'          => (int)($_POST['project_id'] ?? 0),
            'log_date'            => $logDate,
            'labour_count'        => (int)($_POST['labour_count'] ?? 0),
            'cement_bags'         => (int)($_POST['cement_bags'] ?? 0),
            'concrete_mt'         => (float)($_POST['concrete_mt'] ?? 0),
            'concrete_cft'         => (float)($_POST['concrete_cft'] ?? 0),
            'materials_used'      => (int)($_POST['materials_used'] ?? 0),
            'equipment_active'    => (int)($_POST['equipment_active'] ?? 0),
            'weather'             => trim($_POST['weather'] ?? 'Clear'),
            'temperature_celsius' => trim($_POST['temperature_celsius'] ?? ''),
            'safety_ok'           => isset($_POST['safety_ok']) ? 1 : 0,
            'qc_checked'          => isset($_POST['qc_checked']) ? 1 : 0,
            'site_remarks'        => trim($_POST['site_remarks'] ?? '')
        ];

        // Assemble item quantities from form array
        $rawItems = $_POST['items'] ?? [];
        $itemQtys = [];
        foreach ($rawItems as $val) {
            $itemQtys[] = [
                'target_item_id'   => (int)($val['target_item_id'] ?? 0),
                'work_type_id'     => !empty($val['work_type_id']) ? (int)$val['work_type_id'] : null,
                'qty_done_today'   => (float)($val['qty_done_today'] ?? 0),
                'unit'             => trim($val['unit'] ?? ''),
                'remarks'          => trim($val['remarks'] ?? ''),
                'item_status'      => trim($val['item_status'] ?? 'In Progress'),
                'responsible_team' => trim($val['responsible_team'] ?? ''),
            ];
        }

        // Handle dynamically selected new work item and work types from the bottom selector
        $newWorkItemId = (int)($_POST['new_work_item_id'] ?? 0);
        if ($newWorkItemId > 0) {
            $newWorkTypes = $_POST['new_work_types'] ?? [];
            // Convert checked work type IDs to integers
            $newWorkTypes = array_map('intval', $newWorkTypes);
            
            // 1. Remove any items from $itemQtys for this newWorkItemId where work_type_id is NOT in $newWorkTypes
            $filteredItemQtys = [];
            foreach ($itemQtys as $iq) {
                if ($iq['target_item_id'] === $newWorkItemId) {
                    if ($iq['work_type_id'] !== null && in_array($iq['work_type_id'], $newWorkTypes, true)) {
                        $filteredItemQtys[] = $iq;
                    }
                } else {
                    $filteredItemQtys[] = $iq;
                }
            }
            $itemQtys = $filteredItemQtys;
            
            // 2. Fetch the item details (like unit and responsible team)
            $itemQuery = $this->db->prepare("SELECT unit, responsible_team FROM phase_monthly_target_items WHERE id = ?");
            $itemQuery->bind_param('i', $newWorkItemId);
            $itemQuery->execute();
            $itemInfo = $itemQuery->get_result()->fetch_assoc();
            $unit = $itemInfo['unit'] ?? 'RFT / NO';
            $team = $itemInfo['responsible_team'] ?? '';

            // 3. Add any items from $newWorkTypes that are not already in $itemQtys
            if (!empty($newWorkTypes)) {
                foreach ($newWorkTypes as $wtId) {
                    $exists = false;
                    foreach ($itemQtys as $iq) {
                        if ($iq['target_item_id'] === $newWorkItemId && $iq['work_type_id'] === $wtId) {
                            $exists = true;
                            break;
                        }
                    }
                    if (!$exists) {
                        $itemQtys[] = [
                            'target_item_id'   => $newWorkItemId,
                            'work_type_id'     => $wtId,
                            'qty_done_today'   => 0.00,
                            'unit'             => $unit,
                            'remarks'          => '',
                            'item_status'      => 'In Progress',
                            'responsible_team' => $team
                        ];
                    }
                }
            } else {
                // If no work types checked, add one with null work_type_id if it doesn't exist
                $exists = false;
                foreach ($itemQtys as $iq) {
                    if ($iq['target_item_id'] === $newWorkItemId && $iq['work_type_id'] === null) {
                        $exists = true;
                        break;
                    }
                }
                if (!$exists) {
                    $itemQtys[] = [
                        'target_item_id'   => $newWorkItemId,
                        'work_type_id'     => null,
                        'qty_done_today'   => 0.00,
                        'unit'             => $unit,
                        'remarks'          => '',
                        'item_status'      => 'In Progress',
                        'responsible_team' => $team
                    ];
                }
            }
        }

        if ($this->model->saveDailyLog($weeklyProgressId, $logData, $itemQtys, $userId)) {
            setSuccess("Daily progress saved successfully.");
        } else {
            setError("Failed to save progress.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-weekly-progress/" . $targetId . "?week=" . $weekNo . "&log_date=" . $logDate);
        exit;
    }

    // =========================================================================
    // SUBMIT WEEKLY UPDATE (POST)
    // =========================================================================
    public function submitWeeklyUpdate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); exit;
        }

        $id       = (int)($_POST['weekly_progress_id'] ?? 0);
        $targetId = (int)($_POST['target_id'] ?? 0);
        $weekNo   = (int)($_POST['week_number'] ?? 1);

        if (!$id || !$targetId) {
            setError("Invalid weekly progress session.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);

        // Fetch current session details
        $chk = $this->db->prepare("SELECT * FROM phase_weekly_progress WHERE id = ?");
        $chk->bind_param('i', $id);
        $chk->execute();
        $session = $chk->get_result()->fetch_assoc();

        if (!$session) {
            setError("Weekly session not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-monthly-target/" . $targetId);
            exit;
        }

        $updateData = [
            'overall_progress_pct'  => (float)($session['overall_progress_pct']),
            'weather_condition'     => trim($_POST['weather_condition'] ?? $session['weather_condition']),
            'temperature_celsius'   => $session['temperature_celsius'],
            'site_engineer_remarks' => trim($_POST['site_engineer_remarks'] ?? ''),
            'safety_ok'             => isset($_POST['safety_ok']) ? 1 : 0,
            'qc_checked'            => isset($_POST['qc_checked']) ? 1 : 0,
            'material_received'     => isset($_POST['material_received']) ? 1 : 0,
            'entry_status'          => 'Submitted',
            'submitted_by'          => $userId
        ];

        if ($this->model->updateWeeklyProgressDetails($id, $updateData)) {
            setSuccess("Weekly progress report submitted successfully for review.");
        } else {
            setError("Failed to submit weekly progress report.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-weekly-progress/" . $targetId . "?week=" . $weekNo);
        exit;
    }

    // =========================================================================
    // DELETE DAILY PROGRESS LOG ITEM (ROUTE)
    // =========================================================================
    public function deleteDailyLogItem($logRowId) {
        $logRowId = (int)$logRowId;
        if (!$logRowId) {
            setError("Invalid log row ID.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
            exit;
        }

        $meta = $this->model->deleteDailyLogItem($logRowId);
        if (!empty($meta)) {
            setSuccess("Work item removed from schedule.");
            header("Location: " . $GLOBALS['base_url'] . "/con-weekly-progress/" . $meta['target_id'] . "?week=" . $meta['week_number'] . "&log_date=" . $meta['log_date']);
        } else {
            setError("Failed to delete log entry or entry not found.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard");
        }
        exit;
    }
}
