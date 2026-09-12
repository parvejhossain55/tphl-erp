<?php

class MealRecord extends Model
{
    protected $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        parent::__construct($this->db);
    }

    /**
     * Get all daily meal consumption records for a specific project & branch
     * Returns: [ employee_id => [ '2026-08-26' => 1, '2026-08-27' => 0, ... ] ]
     */
    public function getRecordsByProjectAndBranch($projectId, $branchId)
    {
        $projectId = (int)$projectId;
        $branchId = (int)$branchId;

        $sql = "
            SELECT employee_id, meal_date, is_consumed
            FROM meal_records
            WHERE meal_project_id = ? AND office_branch_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("ii", $projectId, $branchId);
        $stmt->execute();
        $res = $stmt->get_result();

        $records = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $empId = (int)$row['employee_id'];
                $date = $row['meal_date'];
                $records[$empId][$date] = (int)$row['is_consumed'];
            }
        }
        $stmt->close();
        return $records;
    }

    /**
     * Check if project has any consumed meal records (is_consumed = 1)
     */
    public function hasConsumedRecords($projectId)
    {
        $projectId = (int)$projectId;
        $sql = "SELECT id FROM meal_records WHERE meal_project_id = ? AND is_consumed = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("i", $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        $has = ($res && $res->num_rows > 0);
        $stmt->close();
        return $has;
    }

    /**
     * Check if project has any meal records for a specific date
     */
    public function hasRecordsForDate($projectId, $date)
    {
        $projectId = (int)$projectId;
        $sql = "SELECT id FROM meal_records WHERE meal_project_id = ? AND meal_date = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param("is", $projectId, $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $has = ($res && $res->num_rows > 0);
        $stmt->close();
        return $has;
    }

    /**
     * Get guest meals for a specific project & branch
     * Returns: [ '2026-08-26' => 2, '2026-08-27' => 1, ... ]
     */
    public function getGuestRecordsByProjectAndBranch($projectId, $branchId)
    {
        $projectId = (int)$projectId;
        $branchId = (int)$branchId;

        $sql = "
            SELECT meal_date, guest_count
            FROM meal_guest_records
            WHERE meal_project_id = ? AND office_branch_id = ?
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("ii", $projectId, $branchId);
        $stmt->execute();
        $res = $stmt->get_result();

        $guestRecords = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $guestRecords[$row['meal_date']] = (int)$row['guest_count'];
            }
        }
        $stmt->close();
        return $guestRecords;
    }

    /**
     * Bulk upsert daily meal records for a branch
     * $items = [ [ 'employee_id' => 12, 'meal_date' => '2026-08-26', 'is_consumed' => 1 ], ... ]
     */
    public function bulkUpsertRecords($projectId, $branchId, array $items)
    {
        if (empty($items)) {
            return true;
        }

        $projectId = (int)$projectId;
        $branchId = (int)$branchId;

        $stmt = $this->db->prepare("
            INSERT INTO meal_records (meal_project_id, office_branch_id, employee_id, meal_date, is_consumed)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            is_consumed = VALUES(is_consumed)
        ");

        if (!$stmt) {
            return false;
        }

        foreach ($items as $item) {
            $empId = (int)$item['employee_id'];
            $mealDate = $item['meal_date'];
            $isConsumed = (int)$item['is_consumed'];

            $stmt->bind_param("iiisi", $projectId, $branchId, $empId, $mealDate, $isConsumed);
            $stmt->execute();
        }

        $stmt->close();
        return true;
    }

    /**
     * Bulk upsert guest records for a branch
     * $guestItems = [ '2026-08-26' => 2, '2026-08-27' => 0, ... ]
     */
    public function bulkUpsertGuestRecords($projectId, $branchId, array $guestItems)
    {
        if (empty($guestItems)) {
            return true;
        }

        $projectId = (int)$projectId;
        $branchId = (int)$branchId;

        $stmt = $this->db->prepare("
            INSERT INTO meal_guest_records (meal_project_id, office_branch_id, meal_date, guest_count)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            guest_count = VALUES(guest_count)
        ");

        if (!$stmt) {
            return false;
        }

        foreach ($guestItems as $date => $count) {
            $guestCount = max(0, (int)$count);
            $stmt->bind_param("iisi", $projectId, $branchId, $date, $guestCount);
            $stmt->execute();
        }

        $stmt->close();
        return true;
    }

    /**
     * Get aggregated total meal count for each employee in a date range or project
     * Used by Payroll Workspace to auto-populate Meal Count & Subsidized Lunch!
     * Returns: [ employee_id => total_meals_consumed ]
     */
    public function getEmployeeMealCounts($projectId = null, $startDate = null, $endDate = null)
    {
        $params = [];
        $types = "";
        $where = "WHERE is_consumed = 1";

        if ($projectId) {
            $where .= " AND meal_project_id = ?";
            $params[] = (int)$projectId;
            $types .= "i";
        } elseif ($startDate && $endDate) {
            $where .= " AND meal_date BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            $types .= "ss";
        }

        $sql = "
            SELECT employee_id, COUNT(*) AS total_meals
            FROM meal_records
            $where
            GROUP BY employee_id
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();

        $summary = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $summary[(int)$row['employee_id']] = (int)$row['total_meals'];
            }
        }
        $stmt->close();
        return $summary;
    }

    /**
     * Get branch-wise summary for History report
     */
    public function getBranchWiseSummary($projectId)
    {
        $projectId = (int)$projectId;

        // Employee meals per branch
        $empSql = "
            SELECT r.office_branch_id, ob.name as branch_name, COUNT(*) as employee_meals
            FROM meal_records r
            LEFT JOIN office_branches ob ON r.office_branch_id = ob.id
            WHERE r.meal_project_id = ? AND r.is_consumed = 1
            GROUP BY r.office_branch_id
        ";
        $stmt = $this->db->prepare($empSql);
        $branches = [];
        if ($stmt) {
            $stmt->bind_param("i", $projectId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $bId = (int)$row['office_branch_id'];
                    $branches[$bId] = [
                        'branch_id' => $bId,
                        'branch_name' => $row['branch_name'] ?? 'Unknown Branch',
                        'employee_meals' => (int)$row['employee_meals'],
                        'guest_meals' => 0
                    ];
                }
            }
            $stmt->close();
        }

        // Guest meals per branch
        $guestSql = "
            SELECT office_branch_id, SUM(guest_count) as total_guests
            FROM meal_guest_records
            WHERE meal_project_id = ?
            GROUP BY office_branch_id
        ";
        $gStmt = $this->db->prepare($guestSql);
        if ($gStmt) {
            $gStmt->bind_param("i", $projectId);
            $gStmt->execute();
            $gRes = $gStmt->get_result();
            if ($gRes) {
                while ($row = $gRes->fetch_assoc()) {
                    $bId = (int)$row['office_branch_id'];
                    if (isset($branches[$bId])) {
                        $branches[$bId]['guest_meals'] = (int)$row['total_guests'];
                    } else {
                        $branches[$bId] = [
                            'branch_id' => $bId,
                            'branch_name' => 'Branch #' . $bId,
                            'employee_meals' => 0,
                            'guest_meals' => (int)$row['total_guests']
                        ];
                    }
                }
            }
            $gStmt->close();
        }

        return $branches;
    }

    /**
     * Save employee daily meal opt-out / opt-in status
     * @param int $employeeId
     * @param string $date Y-m-d
     * @param string $status 'opt_out' or 'opt_in'
     * @param string|null $reason
     * @return bool
     */
    public function saveDailyOptOut($employeeId, $date, $status = 'opt_out', $reason = null)
    {
        $employeeId = (int)$employeeId;
        $date = trim($date);
        $status = in_array($status, ['opt_out', 'opt_in']) ? $status : 'opt_out';
        $now = date('Y-m-d H:i:s');

        $sql = "
            INSERT INTO employee_meal_opt_outs (employee_id, opt_out_date, status, opt_out_time, reason, updated_at)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                opt_out_time = VALUES(opt_out_time),
                reason = VALUES(reason),
                updated_at = VALUES(updated_at)
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param("isssss", $employeeId, $date, $status, $now, $reason, $now);
        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            // Also synchronize existing meal_records for this date if present
            $consumed = ($status === 'opt_out') ? 0 : 1;
            $updateSql = "
                UPDATE meal_records 
                SET is_consumed = ?, updated_at = NOW() 
                WHERE employee_id = ? AND meal_date = ?
            ";
            $uStmt = $this->db->prepare($updateSql);
            if ($uStmt) {
                $uStmt->bind_param("iis", $consumed, $employeeId, $date);
                $uStmt->execute();
                $uStmt->close();
            }
        }

        return $success;
    }

    /**
     * Get all opt-outs within a date range
     * Returns: [ employee_id => [ 'YYYY-MM-DD' => 'opt_out', ... ] ]
     */
    public function getOptOutsByDateRange($fromDate, $toDate)
    {
        $sql = "
            SELECT employee_id, opt_out_date, status
            FROM employee_meal_opt_outs
            WHERE opt_out_date BETWEEN ? AND ? AND status = 'opt_out'
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("ss", $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $map = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $empId = (int)$row['employee_id'];
                $d = $row['opt_out_date'];
                if (!isset($map[$empId])) {
                    $map[$empId] = [];
                }
                $map[$empId][$d] = $row['status'];
            }
        }
        $stmt->close();
        return $map;
    }

    /**
     * Check if a specific employee is opted out on a specific date
     */
    public function isEmployeeOptedOut($employeeId, $date)
    {
        $employeeId = (int)$employeeId;
        $sql = "SELECT status FROM employee_meal_opt_outs WHERE employee_id = ? AND opt_out_date = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return false;

        $stmt->bind_param("is", $employeeId, $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $status = false;
        if ($res && $row = $res->fetch_assoc()) {
            $status = ($row['status'] === 'opt_out');
        }
        $stmt->close();
        return $status;
    }

    /**
     * Sync attendance records from emp_attendances (including manual entries) with meal_records
     * Employees with is_meal_deduction = 1:
     * - If Present/Late in attendance and NOT opted out -> is_consumed = 1
     * - If Opted out before 12 PM -> is_consumed = 0
     * - If Absent/no record -> is_consumed = 0
     */
    public function syncAttendanceWithMealRecords($projectId, $branchId = null)
    {
        $projectId = (int)$projectId;
        $projSql = "SELECT from_date, to_date FROM meal_projects WHERE id = ? LIMIT 1";
        $pStmt = $this->db->prepare($projSql);
        if (!$pStmt) return ['success' => false, 'message' => 'Project not found'];

        $pStmt->bind_param("i", $projectId);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        $project = $pRes ? $pRes->fetch_assoc() : null;
        $pStmt->close();

        if (!$project) {
            return ['success' => false, 'message' => 'Project date range not defined.'];
        }

        $fromDate = $project['from_date'];
        $toDate = $project['to_date'];

        // 1. Fetch all opt-outs in range
        $optOutsMap = $this->getOptOutsByDateRange($fromDate, $toDate);

        // 2. Fetch all active employees with is_meal_deduction = 1
        $empSql = "
            SELECT id, employee_id, full_name, office_branch_id 
            FROM employees 
            WHERE employee_status = 'active' AND is_meal_deduction = 1 AND deleted_at IS NULL
        ";
        if ($branchId) {
            $empSql .= " AND office_branch_id = " . (int)$branchId;
        }
        $empRes = $this->db->query($empSql);
        $employees = [];
        if ($empRes) {
            while ($e = $empRes->fetch_assoc()) {
                $employees[] = $e;
            }
        }

        if (empty($employees)) {
            return ['success' => true, 'synced' => 0, 'message' => 'No active meal employees found.'];
        }

        // 3. Fetch all attendance records (Present / Late / Half-Day) from local database (including manual entries)
        $attSql = "
            SELECT employee_id, attendance_date, today_status
            FROM emp_attendances
            WHERE attendance_date BETWEEN ? AND ? 
              AND today_status IN ('Present', 'Late', 'Half-Day')
              AND deleted_at IS NULL
        ";
        $attStmt = $this->db->prepare($attSql);
        $attendanceMap = [];
        if ($attStmt) {
            $attStmt->bind_param("ss", $fromDate, $toDate);
            $attStmt->execute();
            $attRes = $attStmt->get_result();
            if ($attRes) {
                while ($ar = $attRes->fetch_assoc()) {
                    $eId = (int)$ar['employee_id'];
                    $d = $ar['attendance_date'];
                    $attendanceMap[$eId][$d] = true;
                }
            }
            $attStmt->close();
        }

        // 3b. Fetch from Tipsoi API if available and merge
        try {
            $apiServicePath = dirname(__DIR__) . '/services/ExternalApiService.php';
            if (file_exists($apiServicePath)) {
                require_once $apiServicePath;
                $apiService = new ExternalApiService($this->db);
                $fromTime = strtotime($fromDate . ' 00:00:00') * 1000;
                $toTime   = strtotime($toDate . ' 23:59:59') * 1000;
                $tipsoiAttendance = $apiService->getAttendanceLogs($fromTime, $toTime);

                if (!empty($tipsoiAttendance) && is_array($tipsoiAttendance)) {
                    // Map local employees by normalized ID and Name
                    $empLookup = [];
                    foreach ($employees as $emp) {
                        $eIdNorm = strtolower(trim($emp['employee_id'] ?? ''));
                        $eNameNorm = strtolower(trim($emp['full_name'] ?? ''));
                        if ($eIdNorm !== '') $empLookup['id_' . $eIdNorm] = (int)$emp['id'];
                        if ($eNameNorm !== '') $empLookup['name_' . $eNameNorm] = (int)$emp['id'];
                    }

                    foreach ($tipsoiAttendance as $tEmp) {
                        $tOfficeId = strtolower(trim($tEmp['employeeOfficeId'] ?? $tEmp['employeeId'] ?? ''));
                        $tName = strtolower(trim($tEmp['employeeName'] ?? ''));
                        $matchedEmpDbId = $empLookup['id_' . $tOfficeId] ?? ($empLookup['name_' . $tName] ?? null);

                        if ($matchedEmpDbId && !empty($tEmp['attendance']) && is_array($tEmp['attendance'])) {
                            $todayStr = date('Y-m-d');
                            foreach ($tEmp['attendance'] as $ts => $dayAtt) {
                                $rawDate = $dayAtt['date'] ?? '';
                                $dateStr = null;
                                if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})/', $rawDate, $m)) {
                                    $dateStr = "{$m[3]}-{$m[2]}-{$m[1]}";
                                } else if (is_numeric($ts)) {
                                    $sec = $ts > 9999999999 ? (int)($ts / 1000) : (int)$ts;
                                    $dtObj = new DateTime("@$sec");
                                    $dtObj->setTimezone(new DateTimeZone('Asia/Dhaka'));
                                    $dateStr = $dtObj->format('Y-m-d');
                                }

                                $isPres = !empty($dayAtt['isPresent']) 
                                    || (!empty($dayAtt['firstLoggedTime']) && trim($dayAtt['firstLoggedTime']) !== '-');

                                // Only mark present up to today (future dates cannot be present)
                                if ($dateStr && $dateStr >= $fromDate && $dateStr <= $toDate && $dateStr <= $todayStr && $isPres) {
                                    $attendanceMap[$matchedEmpDbId][$dateStr] = true;
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $te) {
            error_log("MealRecord sync Tipsoi attendance fetch skipped: " . $te->getMessage());
        }

        // 4. Generate all dates in period
        $start = new DateTime($fromDate);
        $end = new DateTime($toDate);
        $end->modify('+1 day');
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);

        $syncedCount = 0;
        $todayStr = date('Y-m-d');
        $upsertSql = "
            INSERT INTO meal_records (meal_project_id, employee_id, office_branch_id, meal_date, is_consumed, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                is_consumed = VALUES(is_consumed),
                updated_at = NOW()
        ";
        $uStmt = $this->db->prepare($upsertSql);
        if (!$uStmt) {
            return ['success' => false, 'message' => 'Failed to prepare upsert query.'];
        }

        foreach ($employees as $emp) {
            $eId = (int)$emp['id'];
            $bId = (int)($emp['office_branch_id'] ?? 1);

            foreach ($period as $dt) {
                $d = $dt->format('Y-m-d');
                $isOptedOut = isset($optOutsMap[$eId][$d]);
                $isPresent = isset($attendanceMap[$eId][$d]);

                // Future dates cannot be consumed automatically from attendance logs
                if ($d > $todayStr) {
                    $isConsumed = 0;
                } else {
                    // Attendance logic: Present AND NOT opted-out => 1; otherwise => 0
                    $isConsumed = ($isPresent && !$isOptedOut) ? 1 : 0;
                }

                $uStmt->bind_param("iiisi", $projectId, $eId, $bId, $d, $isConsumed);
                $uStmt->execute();
                $syncedCount++;
            }
        }
        $uStmt->close();

        return [
            'success' => true,
            'synced' => $syncedCount,
            'message' => "Successfully synced {$syncedCount} meal records from attendance."
        ];
    }

    /**
     * Synchronize a single employee attendance event with meal records
     * Called whenever manual attendance is created/updated or punched
     */
    public function syncSingleAttendanceRecord($employeeId, $attendanceDate, $todayStatus)
    {
        $employeeId = (int)$employeeId;
        if ($employeeId <= 0 || empty($attendanceDate)) return false;

        // Check if employee has is_meal_deduction = 1
        $eRes = $this->db->query("SELECT is_meal_deduction, office_branch_id FROM employees WHERE id = {$employeeId} AND deleted_at IS NULL LIMIT 1");
        if (!$eRes || !($eRow = $eRes->fetch_assoc())) {
            return false;
        }

        if ((int)($eRow['is_meal_deduction'] ?? 0) !== 1) {
            return false;
        }

        $branchId = (int)($eRow['office_branch_id'] ?? 1);

        // Find active or covering meal project
        $pStmt = $this->db->prepare("SELECT id FROM meal_projects WHERE ? BETWEEN from_date AND to_date ORDER BY id DESC LIMIT 1");
        if (!$pStmt) return false;
        $pStmt->bind_param("s", $attendanceDate);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        $project = $pRes ? $pRes->fetch_assoc() : null;
        $pStmt->close();

        if (!$project) {
            // Auto-provision standard monthly meal project if none exists for this month
            $monthName = date('F Y', strtotime($attendanceDate));
            $startDate = date('Y-m-01', strtotime($attendanceDate));
            $endDate   = date('Y-m-t', strtotime($attendanceDate));
            $projName  = $monthName . " Meal Project";
            $stdRate   = 60.00;

            $insStmt = $this->db->prepare("INSERT INTO meal_projects (name, from_date, to_date, standard_meal_amount, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
            if ($insStmt) {
                $insStmt->bind_param("sssd", $projName, $startDate, $endDate, $stdRate);
                if ($insStmt->execute()) {
                    $project = ['id' => $this->db->insert_id];
                }
                $insStmt->close();
            }
        }

        if (!$project) {
            return false;
        }

        $projectId = (int)$project['id'];
        $isOptedOut = $this->isEmployeeOptedOut($employeeId, $attendanceDate);
        $isPresent = in_array($todayStatus, ['Present', 'Late', 'Half-Day']);

        // Present AND NOT opted-out => 1; otherwise => 0
        $isConsumed = ($isPresent && !$isOptedOut) ? 1 : 0;

        $upsertSql = "
            INSERT INTO meal_records (meal_project_id, employee_id, office_branch_id, meal_date, is_consumed, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                is_consumed = VALUES(is_consumed),
                updated_at = NOW()
        ";
        $uStmt = $this->db->prepare($upsertSql);
        if ($uStmt) {
            $uStmt->bind_param("iiisi", $projectId, $employeeId, $branchId, $attendanceDate, $isConsumed);
            $uStmt->execute();
            $uStmt->close();
            return true;
        }
        return false;
    }
}

