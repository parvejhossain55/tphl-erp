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

        // Recalculate weekly achievements to sync carryover, gaps, and stars
        $this->model->recalculateWeeklyAchievements($weeklyProgressId);

        // Fetch weekly items (Planned vs Achieved)
        $weeklyItems = $this->model->getWeeklyItems($weeklyProgressId);

        // Fetch daily target pct breakdowns for pct-mode items
        $dailyBreakdowns = [];
        foreach ($weeklyItems as $item) {
            if (($item['target_mode'] ?? 'qty') === 'pct') {
                $dailyBreakdowns[(int)$item['target_item_id']] = $this->monthlyTargetModel->getDailyPctBreakdown((int)$item['target_item_id']);
            }
        }

        // Fetch daily logs and quantities for the selected log date
        $dailyLog = $this->model->getOrCreateDailyLog($weeklyProgressId, $selectedDate, (int)$target['project_id']);
        $dailyQuantities = $this->model->getDailyQuantities($weeklyProgressId, $selectedDate);

        // Fetch persistent work types for this target
        $pwtRows = $this->model->getPersistentWorkTypesForTarget($targetId);

        // Populate virtual rows into $dailyQuantities if not locked
        $isLocked = ($weeklyProgress['entry_status'] ?? 'Draft') !== 'Draft';
        if (!$isLocked) {
            $persistentWts = [];
            foreach ($pwtRows as $r) {
                $persistentWts[(int)$r['target_item_id']][] = [
                    'work_type_id'   => (int)$r['work_type_id'],
                    'work_type_name' => $r['work_type_name']
                ];
            }

            $loggedByItem = [];
            foreach ($dailyQuantities as $dq) {
                $iid = (int)$dq['target_item_id'];
                $wtId = $dq['work_type_id'] ? (int)$dq['work_type_id'] : 0;
                $loggedByItem[$iid][$wtId] = true;
            }

            foreach ($weeklyItems as $wi) {
                $itemId = (int)$wi['target_item_id'];
                $planned = (($wi['target_mode'] ?? 'qty') === 'pct') ? (float)($wi['planned_pct'] ?? 0) : (float)$wi['planned_qty'];
                
                if ($planned > 0 && isset($persistentWts[$itemId])) {
                    // If general progress is already logged (wtId = 0), skip virtual work types to avoid confusion
                    if (isset($loggedByItem[$itemId][0])) {
                        continue;
                    }

                    foreach ($persistentWts[$itemId] as $pwt) {
                        $wtId = $pwt['work_type_id'];
                        if (!isset($loggedByItem[$itemId][$wtId])) {
                            // Append virtual row
                            $dailyQuantities[] = [
                                'log_row_id'       => null,
                                'target_item_id'   => $itemId,
                                'work_type_id'     => $wtId,
                                'qty_done_today'   => 0.00,
                                'pct_done_today'   => 0.00,
                                'remarks'          => '',
                                'item_status'      => 'Not Started',
                                'responsible_team' => $wi['responsible_team'] ?? '',
                                'work_type_name'   => $pwt['work_type_name']
                            ];
                            $loggedByItem[$itemId][$wtId] = true;
                        }
                    }
                }
            }
        }

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

        $qcName = '';
        if (!empty($dailyLog['qc_approved_id'])) {
            $uStmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
            $uId = (int)$dailyLog['qc_approved_id'];
            $uStmt->bind_param('i', $uId);
            $uStmt->execute();
            $qcUser = $uStmt->get_result()->fetch_assoc();
            $qcName = $qcUser['full_name'] ?? '';
        }

        $pmName = '';
        if (!empty($dailyLog['pm_approved_id'])) {
            $uStmt = $this->db->prepare("SELECT full_name FROM users WHERE id = ?");
            $uId = (int)$dailyLog['pm_approved_id'];
            $uStmt->bind_param('i', $uId);
            $uStmt->execute();
            $pmUser = $uStmt->get_result()->fetch_assoc();
            $pmName = $pmUser['full_name'] ?? '';
        }

        $this->view('phase2/weekly_progress/entry', [
            'current_route'     => 'con-weekly-progress',
            'page_title'        => 'Daily / Weekly Progress Entry',
            'target'            => $target,
            'weeklyProgress'    => $weeklyProgress,
            'weeklyItems'       => $weeklyItems,
            'selectedDate'      => $selectedDate,
            'dailyLog'          => $dailyLog,
            'dailyQuantities'   => $dailyQuantities,
            'dailyBreakdowns'   => $dailyBreakdowns,
            'weeklyLogsSummary' => $weeklyLogsSummary,
            'project'           => $project,
            'weekNo'            => $weekNo,
            'workTypes'         => $workTypes,
            'materialIssues'    => $materialIssues,
            'qcName'            => $qcName,
            'pmName'            => $pmName,
            'persistentWorkTypes'=> $pwtRows,
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
                'pct_done_today'   => (float)($val['pct_done_today'] ?? 0),
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

            // Persist these work types for the monthly target item
            $this->model->savePersistentWorkTypes($newWorkItemId, $newWorkTypes);
            
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
                            'pct_done_today'   => 0.00,
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
                        'pct_done_today'   => 0.00,
                        'unit'             => $unit,
                        'remarks'          => '',
                        'item_status'      => 'In Progress',
                        'responsible_team' => $team
                    ];
                }
            }
        }

        // Handle photo uploads
        $uploadedPhotos = []; // [target_item_id => photo_path]
        if (!empty($_FILES['photos']['name'])) {
            foreach ($_FILES['photos']['name'] as $idx => $name) {
                if (!empty($name) && $_FILES['photos']['error'][$idx] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['photos']['tmp_name'][$idx];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (in_array($ext, $allowed)) {
                        $uploadDir = dirname(__DIR__, 3) . '/public/uploads/photos/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $fileName = 'photo_' . uniqid() . '.' . $ext;
                        $targetPath = $uploadDir . $fileName;
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $targetItemId = (int)($rawItems[$idx]['target_item_id'] ?? 0);
                            if ($targetItemId > 0) {
                                $uploadedPhotos[$targetItemId] = 'uploads/photos/' . $fileName;
                            }
                        }
                    }
                }
            }
        }

        if ($this->model->saveDailyLog($weeklyProgressId, $logData, $itemQtys, $userId, $uploadedPhotos)) {
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
            // Fetch project name and assigned qc_id for notifications
            $pStmt = $this->db->prepare("SELECT name, qc_id FROM projects WHERE id = ?");
            $pStmt->bind_param('i', $session['project_id']);
            $pStmt->execute();
            $projectObj = $pStmt->get_result()->fetch_assoc();

            if ($projectObj) {
                $qcId = (int)($projectObj['qc_id'] ?? 0);
                $title = 'Weekly Progress Report Submitted';
                $msg = 'Weekly progress report for project ' . htmlspecialchars($projectObj['name']) . ' (Week ' . $weekNo . ') has been submitted for QC review.';
                $url = $GLOBALS['base_url'] . '/con-weekly-progress/' . $targetId . '?week=' . $weekNo;

                if ($qcId > 0) {
                    // Notify the assigned QC Specialist
                    $notifStmt = $this->db->prepare("INSERT INTO head_notifications (user_id, type, title, message, url) VALUES (?, 'weekly_progress', ?, ?, ?)");
                    $notifStmt->bind_param('isss', $qcId, $title, $msg, $url);
                    $notifStmt->execute();
                } else {
                    // Notify all users with role_id = 13 (QC Specialists)
                    $qcStmt = $this->db->prepare("SELECT id FROM users WHERE role_id = 13");
                    $qcStmt->execute();
                    $qcUsers = $qcStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    if (!empty($qcUsers)) {
                        $notifStmt = $this->db->prepare("INSERT INTO head_notifications (user_id, type, title, message, url) VALUES (?, 'weekly_progress', ?, ?, ?)");
                        foreach ($qcUsers as $qcUser) {
                            $userQcId = (int)$qcUser['id'];
                            $notifStmt->bind_param('isss', $userQcId, $title, $msg, $url);
                            $notifStmt->execute();
                        }
                    }
                }
            }

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

    // =========================================================================
    // QC APPROVAL (POST) - DATE-WISE
    // =========================================================================
    public function qcApprove() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
        }

        $id       = (int)($_POST['weekly_progress_id'] ?? 0);
        $targetId = (int)($_POST['target_id'] ?? 0);
        $weekNo   = (int)($_POST['week_number'] ?? 1);
        $logDate  = trim($_POST['log_date'] ?? '');

        if (!$id || !$targetId || !$logDate) {
            setError("Required parameters missing.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Access check: QC (role 13) or Admin (role 1)
        if (!in_array($roleId, [1, 13])) {
            setError("You do not have permission to perform QC reviews.");
            header("Location: " . $GLOBALS['base_url'] . "/con-weekly-progress/" . $targetId . "?week=" . $weekNo . "&log_date=" . $logDate);
            exit;
        }

        $qcStatus  = trim($_POST['qc_status'] ?? 'Approved');
        $qcComment = trim($_POST['qc_comment'] ?? '');

        // Handle QC photo upload
        $qcImage = null;
        if (!empty($_FILES['qc_image']['name']) && $_FILES['qc_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = dirname(__DIR__, 3) . '/public/uploads/qc-reports/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['qc_image']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $allowed)) {
                $filename = 'qc_wpr_' . uniqid() . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['qc_image']['tmp_name'], $uploadDir . $filename)) {
                    $qcImage = 'uploads/qc-reports/' . $filename;
                }
            }
        }

        // Update daily_progress_logs
        if ($qcImage) {
            $stmt = $this->db->prepare("
                UPDATE daily_progress_logs SET
                    qc_approved_id = ?,
                    qc_status      = ?,
                    qc_image       = ?,
                    qc_comment     = ?
                WHERE weekly_progress_id = ? AND log_date = ?
            ");
            $stmt->bind_param('isssis', $userId, $qcStatus, $qcImage, $qcComment, $id, $logDate);
        } else {
            $stmt = $this->db->prepare("
                UPDATE daily_progress_logs SET
                    qc_approved_id = ?,
                    qc_status      = ?,
                    qc_comment     = ?
                WHERE weekly_progress_id = ? AND log_date = ?
            ");
            $stmt->bind_param('issis', $userId, $qcStatus, $qcComment, $id, $logDate);
        }

        if ($stmt->execute()) {
            $this->syncWeeklyQCStatus($id);
            setSuccess("QC review for date " . date('d M Y', strtotime($logDate)) . " recorded successfully.");
        } else {
            setError("Failed to record QC review.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-weekly-progress/" . $targetId . "?week=" . $weekNo . "&log_date=" . $logDate);
        exit;
    }

    private function syncWeeklyQCStatus($weeklyProgressId) {
        // Find all daily logs for this week
        $res = $this->db->query("SELECT id, qc_status FROM daily_progress_logs WHERE weekly_progress_id = $weeklyProgressId");
        if (!$res) return;
        $logs = $res->fetch_all(MYSQLI_ASSOC);
        
        if (empty($logs)) return;
        
        $allApproved = true;
        $anyRejected = false;
        
        foreach ($logs as $log) {
            if ($log['qc_status'] === 'Rejected') {
                $anyRejected = true;
            }
            if ($log['qc_status'] !== 'Approved' && $log['qc_status'] !== 'Partially Approved') {
                $allApproved = false;
            }
        }
        
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        
        if ($anyRejected) {
            $stmt = $this->db->prepare("UPDATE phase_weekly_progress SET qc_status = 'Rejected', qc_approved_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $userId, $weeklyProgressId);
            $stmt->execute();
        } elseif ($allApproved) {
            $stmt = $this->db->prepare("UPDATE phase_weekly_progress SET qc_status = 'Approved', qc_approved_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $userId, $weeklyProgressId);
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("UPDATE phase_weekly_progress SET qc_status = 'Pending', qc_approved_id = NULL WHERE id = ?");
            $stmt->bind_param('i', $weeklyProgressId);
            $stmt->execute();
        }
    }

    // =========================================================================
    // PM APPROVAL (POST) - DATE-WISE
    // =========================================================================
    public function pmApprove() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
        }

        $id       = (int)($_POST['weekly_progress_id'] ?? 0);
        $targetId = (int)($_POST['target_id'] ?? 0);
        $weekNo   = (int)($_POST['week_number'] ?? 1);
        $logDate  = trim($_POST['log_date'] ?? '');

        if (!$id || !$targetId || !$logDate) {
            setError("Required parameters missing.");
            header("Location: " . $GLOBALS['base_url'] . "/con-dashboard"); exit;
        }

        $userId = (int)($_SESSION['user']['id'] ?? 0);
        $roleId = (int)($_SESSION['user']['role_id'] ?? 0);

        // Access check: PM (role 15), PM Head (role 22), or Admin (role 1)
        if (!in_array($roleId, [1, 15, 22])) {
            setError("You do not have permission to perform PM approvals.");
            header("Location: " . $GLOBALS['base_url'] . "/con-weekly-progress/" . $targetId . "?week=" . $weekNo . "&log_date=" . $logDate);
            exit;
        }

        $pmStatus  = trim($_POST['pm_status'] ?? 'Approved');
        $pmComment = trim($_POST['pm_comment'] ?? '');

        // Update daily_progress_logs
        $stmt = $this->db->prepare("
            UPDATE daily_progress_logs SET
                pm_approved_id = ?,
                pm_status      = ?,
                pm_comment     = ?
            WHERE weekly_progress_id = ? AND log_date = ?
        ");
        $stmt->bind_param('issis', $userId, $pmStatus, $pmComment, $id, $logDate);

        if ($stmt->execute()) {
            $this->syncWeeklyPMStatus($id);
            setSuccess("PM review for date " . date('d M Y', strtotime($logDate)) . " recorded successfully.");
        } else {
            setError("Failed to record PM review.");
        }

        header("Location: " . $GLOBALS['base_url'] . "/con-weekly-progress/" . $targetId . "?week=" . $weekNo . "&log_date=" . $logDate);
        exit;
    }

    private function syncWeeklyPMStatus($weeklyProgressId) {
        // Find all daily logs for this week
        $res = $this->db->query("SELECT id, pm_status FROM daily_progress_logs WHERE weekly_progress_id = $weeklyProgressId");
        if (!$res) return;
        $logs = $res->fetch_all(MYSQLI_ASSOC);
        
        if (empty($logs)) return;
        
        $allApproved = true;
        $anyRejected = false;
        
        foreach ($logs as $log) {
            if ($log['pm_status'] === 'Rejected') {
                $anyRejected = true;
            }
            if ($log['pm_status'] !== 'Approved' && $log['pm_status'] !== 'Partially Approved') {
                $allApproved = false;
            }
        }
        
        $userId = (int)($_SESSION['user']['id'] ?? 0);
        
        if ($anyRejected) {
            // Revert entire weekly report to Draft status so the Site Engineer can modify
            $stmt = $this->db->prepare("
                UPDATE phase_weekly_progress SET 
                    pm_status = 'Rejected', 
                    pm_approved_id = ?, 
                    entry_status = 'Draft',
                    qc_status = 'Pending',
                    qc_approved_id = NULL
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $userId, $weeklyProgressId);
            $stmt->execute();
        } elseif ($allApproved) {
            // Approve the entire weekly progress report
            $stmt = $this->db->prepare("
                UPDATE phase_weekly_progress SET 
                    pm_status = 'Approved', 
                    pm_approved_id = ?, 
                    entry_status = 'Approved' 
                WHERE id = ?
            ");
            $stmt->bind_param('ii', $userId, $weeklyProgressId);
            $stmt->execute();
        } else {
            // Weekly PM status remains Pending
            $stmt = $this->db->prepare("
                UPDATE phase_weekly_progress SET 
                    pm_status = 'Pending', 
                    pm_approved_id = NULL,
                    entry_status = 'Submitted' 
                WHERE id = ?
            ");
            $stmt->bind_param('i', $weeklyProgressId);
            $stmt->execute();
        }

        // Auto-sync weekly progress to project phase items
        $this->model->syncAllPhaseItemsForWeeklyProgress($weeklyProgressId);
    }
}
