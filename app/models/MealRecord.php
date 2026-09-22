<?php

class MealRecord extends Model
{
    protected $db;
    protected $lastError = null;
    private static $tablesChecked = false;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        parent::__construct($this->db);
        $this->ensureTablesExist();
    }

    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * Auto-create meal module tables if they do not exist
     */
    public function ensureTablesExist()
    {
        if (self::$tablesChecked) {
            return;
        }

        try {
            // 1. employee_meal_opt_outs
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `employee_meal_opt_outs` (
                  `id` INT AUTO_INCREMENT PRIMARY KEY,
                  `employee_id` INT UNSIGNED NOT NULL,
                  `opt_out_date` DATE NOT NULL,
                  `status` ENUM('opt_out', 'opt_in') NOT NULL DEFAULT 'opt_out',
                  `opt_out_time` DATETIME NOT NULL,
                  `reason` VARCHAR(255) NULL,
                  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  UNIQUE KEY `uniq_emp_opt_date` (`employee_id`, `opt_out_date`),
                  KEY `idx_opt_date` (`opt_out_date`),
                  KEY `idx_emp_status` (`employee_id`, `status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 2. meal_projects
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `meal_projects` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `payroll_project_id` int(11) DEFAULT NULL,
                  `name` varchar(150) NOT NULL,
                  `from_date` date NOT NULL,
                  `to_date` date NOT NULL,
                  `standard_meal_amount` decimal(10,2) NOT NULL DEFAULT 120.00,
                  `status` enum('open','locked','settled') NOT NULL DEFAULT 'open',
                  `created_by` int(11) DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  KEY `idx_payroll_project` (`payroll_project_id`),
                  KEY `idx_dates` (`from_date`, `to_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // 3. meal_records
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `meal_records` (
                  `id` bigint(20) NOT NULL AUTO_INCREMENT,
                  `meal_project_id` int(11) NOT NULL,
                  `office_branch_id` int(11) NOT NULL,
                  `employee_id` int(10) unsigned NOT NULL,
                  `meal_date` date NOT NULL,
                  `is_consumed` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Meal consumed, 0 = No meal',
                  `is_manual` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Manually set by Admin in Workplace, 0 = Auto sync',
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_project_emp_date` (`meal_project_id`, `employee_id`, `meal_date`),
                  KEY `idx_project_branch` (`meal_project_id`, `office_branch_id`),
                  KEY `idx_emp_date` (`employee_id`, `meal_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            // Ensure is_manual column exists in existing meal_records table
            $colCheck = $this->db->query("SHOW COLUMNS FROM `meal_records` LIKE 'is_manual'");
            if ($colCheck && $colCheck->num_rows === 0) {
                $this->db->query("ALTER TABLE `meal_records` ADD COLUMN `is_manual` tinyint(1) NOT NULL DEFAULT 0 AFTER `is_consumed`");
            }

            // 4. meal_guest_records
            $this->db->query("
                CREATE TABLE IF NOT EXISTS `meal_guest_records` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `meal_project_id` int(11) NOT NULL,
                  `office_branch_id` int(11) NOT NULL,
                  `meal_date` date NOT NULL,
                  `guest_count` int(11) NOT NULL DEFAULT 0,
                  `remarks` varchar(255) DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `unique_project_branch_date` (`meal_project_id`, `office_branch_id`, `meal_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");

            self::$tablesChecked = true;
        } catch (\Throwable $te) {
            error_log("MealRecord ensureTablesExist error: " . $te->getMessage());
        }
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
        $this->ensureTablesExist();

        if (empty($items)) {
            return true;
        }

        $projectId = (int)$projectId;
        $branchId = (int)$branchId;

        $stmt = $this->db->prepare("
            INSERT INTO meal_records (meal_project_id, office_branch_id, employee_id, meal_date, is_consumed, is_manual, updated_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
            is_consumed = VALUES(is_consumed),
            is_manual = 1,
            office_branch_id = VALUES(office_branch_id),
            updated_at = NOW()
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
        $this->ensureTablesExist();
        $this->lastError = null;

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
            $this->lastError = "Prepare failed for employee_meal_opt_outs: " . $this->db->error;
            error_log($this->lastError);
            return false;
        }

        $stmt->bind_param("isssss", $employeeId, $date, $status, $now, $reason, $now);
        $success = $stmt->execute();
        if (!$success) {
            $this->lastError = "Execute failed for employee_meal_opt_outs: " . $stmt->error;
            error_log($this->lastError);
        }
        $stmt->close();

        if ($success) {
            $consumed = ($status === 'opt_out') ? 0 : 1;

            // Find or provision meal project covering this date
            $projId = null;
            $pStmt = $this->db->prepare("SELECT id FROM meal_projects WHERE ? BETWEEN from_date AND to_date ORDER BY id DESC LIMIT 1");
            if ($pStmt) {
                $pStmt->bind_param("s", $date);
                $pStmt->execute();
                $pRes = $pStmt->get_result();
                if ($pRes && $pRow = $pRes->fetch_assoc()) {
                    $projId = (int)$pRow['id'];
                }
                $pStmt->close();
            }

            if (!$projId) {
                // Auto-provision standard monthly meal project if none exists for this month
                $monthName = date('F Y', strtotime($date));
                $startDate = date('Y-m-01', strtotime($date));
                $endDate   = date('Y-m-t', strtotime($date));
                $projName  = $monthName . " Meal Project";
                $stdRate   = 60.00;

                $insStmt = $this->db->prepare("INSERT INTO meal_projects (name, from_date, to_date, standard_meal_amount, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
                if ($insStmt) {
                    $insStmt->bind_param("sssd", $projName, $startDate, $endDate, $stdRate);
                    if ($insStmt->execute()) {
                        $projId = (int)$this->db->insert_id;
                    }
                    $insStmt->close();
                }
            }

            // Get employee's office branch ID
            $branchId = 1;
            $bRes = $this->db->query("SELECT office_branch_id FROM employees WHERE id = {$employeeId} LIMIT 1");
            if ($bRes && $bRow = $bRes->fetch_assoc()) {
                $branchId = (int)($bRow['office_branch_id'] ?? 1);
            }

            // UPSERT into meal_records so that a record is GUARANTEED to exist in Meal Tracker
            if ($projId) {
                $upsertSql = "
                    INSERT INTO meal_records (meal_project_id, employee_id, office_branch_id, meal_date, is_consumed, updated_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        is_consumed = VALUES(is_consumed),
                        office_branch_id = VALUES(office_branch_id),
                        updated_at = NOW()
                ";
                $uStmt = $this->db->prepare($upsertSql);
                if ($uStmt) {
                    $uStmt->bind_param("iiisi", $projId, $employeeId, $branchId, $date, $consumed);
                    $uStmt->execute();
                    $uStmt->close();
                }
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
        $this->ensureTablesExist();
        $sql = "
            SELECT employee_id, opt_out_date, status
            FROM employee_meal_opt_outs
            WHERE opt_out_date BETWEEN ? AND ?
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
     * Get a specific employee's opt status for a specific date
     * Returns 'opt_out', 'opt_in', or null
     */
    public function getEmployeeOptStatus($employeeId, $date)
    {
        $this->ensureTablesExist();
        $employeeId = (int)$employeeId;
        $sql = "SELECT status FROM employee_meal_opt_outs WHERE employee_id = ? AND opt_out_date = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        $stmt->bind_param("is", $employeeId, $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $status = null;
        if ($res && $row = $res->fetch_assoc()) {
            $status = $row['status'];
        }
        $stmt->close();
        return $status;
    }

    /**
     * Check if a specific employee is opted out on a specific date
     */
    public function isEmployeeOptedOut($employeeId, $date)
    {
        $this->ensureTablesExist();
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
            INSERT INTO meal_records (meal_project_id, employee_id, office_branch_id, meal_date, is_consumed, is_manual, updated_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
            ON DUPLICATE KEY UPDATE
                is_consumed = IF(meal_records.is_manual = 1, meal_records.is_consumed, VALUES(is_consumed)),
                updated_at = IF(meal_records.is_manual = 1, meal_records.updated_at, NOW())
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
                $optStatus = $optOutsMap[$eId][$d] ?? null;
                $isPresent = isset($attendanceMap[$eId][$d]);

                // Future dates cannot be consumed automatically from attendance logs
                if ($d > $todayStr) {
                    $isConsumed = 0;
                } else if ($optStatus === 'opt_out') {
                    $isConsumed = 0;
                } else if ($optStatus === 'opt_in') {
                    $isConsumed = 1;
                } else {
                    // Attendance logic: Present AND NOT opted-out => 1; otherwise => 0
                    $isConsumed = $isPresent ? 1 : 0;
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
        $optStatus = $this->getEmployeeOptStatus($employeeId, $attendanceDate);
        $isPresent = in_array($todayStatus, ['Present', 'Late', 'Half-Day']);

        if ($optStatus === 'opt_out') {
            $isConsumed = 0;
        } else if ($optStatus === 'opt_in') {
            $isConsumed = 1;
        } else {
            $isConsumed = $isPresent ? 1 : 0;
        }

        $upsertSql = "
            INSERT INTO meal_records (meal_project_id, employee_id, office_branch_id, meal_date, is_consumed, is_manual, updated_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
            ON DUPLICATE KEY UPDATE
                is_consumed = IF(meal_records.is_manual = 1, meal_records.is_consumed, VALUES(is_consumed)),
                updated_at = IF(meal_records.is_manual = 1, meal_records.updated_at, NOW())
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

    /**
     * Get all daily meal records for a single employee in a specific project
     * Returns: [ '2026-08-26' => [ 'is_consumed' => 1, 'updated_at' => '2026-09-12 18:30:00' ], ... ]
     */
    public function getEmployeeDetailedRecords($employeeId, $projectId)
    {
        $employeeId = (int)$employeeId;
        $projectId = (int)$projectId;

        $sql = "
            SELECT meal_date, is_consumed, updated_at
            FROM meal_records
            WHERE employee_id = ? AND meal_project_id = ?
            ORDER BY meal_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("ii", $employeeId, $projectId);
        $stmt->execute();
        $res = $stmt->get_result();

        $records = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $records[$row['meal_date']] = [
                    'is_consumed' => (int)$row['is_consumed'],
                    'updated_at' => $row['updated_at']
                ];
            }
        }
        $stmt->close();
        return $records;
    }

    /**
     * Get opt-out records for a single employee in a date range
     */
    public function getEmployeeOptOutsInRange($employeeId, $fromDate, $toDate)
    {
        $employeeId = (int)$employeeId;
        $fromDate = trim($fromDate);
        $toDate = trim($toDate);

        $sql = "
            SELECT opt_out_date, status, opt_out_time
            FROM employee_meal_opt_outs
            WHERE employee_id = ? AND opt_out_date BETWEEN ? AND ?
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("iss", $employeeId, $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $optOuts = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $optOuts[$row['opt_out_date']] = $row;
            }
        }
        $stmt->close();
        return $optOuts;
    }

    /**
     * Get attendance records for a single employee in a date range
     */
    public function getEmployeeAttendancesInRange($employeeId, $fromDate, $toDate)
    {
        $employeeId = (int)$employeeId;
        $fromDate = trim($fromDate);
        $toDate = trim($toDate);

        $sql = "
            SELECT attendance_date, today_status, punch_in_time, punch_out_time
            FROM emp_attendances
            WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? AND deleted_at IS NULL
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return [];

        $stmt->bind_param("iss", $employeeId, $fromDate, $toDate);
        $stmt->execute();
        $res = $stmt->get_result();

        $attendances = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $attendances[$row['attendance_date']] = $row;
            }
        }
        $stmt->close();
        return $attendances;
    }
}

