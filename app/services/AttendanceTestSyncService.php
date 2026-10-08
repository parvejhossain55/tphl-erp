<?php

class AttendanceTestSyncService
{
    protected $db;
    protected $externalApiService;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        require_once dirname(__DIR__) . '/services/ExternalApiService.php';
        $this->externalApiService = new ExternalApiService($this->db);
    }

    /**
     * Sync attendance punches from Tipsoi for a specific date and evaluate in ERP
     *
     * @param string $date YYYY-MM-DD
     * @return array Result summary ['success' => bool, 'synced_count' => int, 'message' => string]
     */
    public function syncDate($date)
    {
        $token = $this->externalApiService->getBearerToken();
        if (!$token) {
            return [
                'success' => false,
                'message' => 'Tipsoi API authentication failed. Please check credentials.'
            ];
        }

        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');
        $fromTime = strtotime($date . ' 00:00:00') * 1000;
        $toTime = strtotime($date . ' 23:59:59') * 1000;
        date_default_timezone_set($oldTz);

        // Fetch attendance logs from Tipsoi
        $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
            'officeId' => 4699,
            'order' => 'asc',
            'pageNumber' => 0,
            'perPage' => 2000,
            'reportType' => 'department',
            'sortKey' => 'reportPosition',
            'status' => 1,
            'from' => $fromTime,
            'to' => $toTime
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$res) {
            return [
                'success' => false,
                'message' => "Tipsoi API returned HTTP status $httpCode."
            ];
        }

        $data = json_decode($res, true);
        $rawAttendance = $data['attendance'] ?? [];

        if (empty($rawAttendance)) {
            return [
                'success' => true,
                'synced_count' => 0,
                'message' => 'No attendance data returned from Tipsoi for ' . $date
            ];
        }

        // Cache local ERP employees mapped by employee_id (Office ID)
        $erpEmployees = $this->getErpEmployeesMap();

        // Cache holidays for this date
        $isPublicHoliday = $this->checkPublicHoliday($date);

        // Cache active approved leaves for this date
        $approvedLeaves = $this->getApprovedLeavesMap($date);

        // Cache weekly day-offs for this day of week
        $dayOfWeekName = date('l', strtotime($date)); // e.g. Friday, Sunday
        $empDaysOff = $this->getEmployeeDaysOffMap($dayOfWeekName);

        $syncedCount = 0;
        $currentUserId = $_SESSION['user']['id'] ?? 1;

        // Prepare UPSERT query
        $stmt = $this->db->prepare("
            INSERT INTO emp_attendances (
                employee_id, 
                attendance_date, 
                punch_in_time, 
                punch_out_time, 
                today_status, 
                shift_name, 
                shift_start_time, 
                late_minutes, 
                working_hours, 
                overtime_minutes, 
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                punch_in_time = VALUES(punch_in_time),
                punch_out_time = VALUES(punch_out_time),
                today_status = VALUES(today_status),
                shift_name = VALUES(shift_name),
                shift_start_time = VALUES(shift_start_time),
                late_minutes = VALUES(late_minutes),
                working_hours = VALUES(working_hours),
                overtime_minutes = VALUES(overtime_minutes),
                updated_at = CURRENT_TIMESTAMP
        ");

        foreach ($rawAttendance as $empTipsoi) {
            $officeId = trim((string)($empTipsoi['employeeOfficeId'] ?? $empTipsoi['employeeId'] ?? ''));
            // Normalize ID (trim leading zeros if numeric)
            $normId = preg_match('/^\d+$/', $officeId) ? (string)intval($officeId) : $officeId;

            if (!isset($erpEmployees[$normId])) {
                continue; // Employee not in local ERP database
            }

            $erpEmp = $erpEmployees[$normId];
            $localEmpId = (int)$erpEmp['id'];

            // Extract Shift Info: Prioritize local ERP shift if assigned, else Tipsoi shift
            $graceMinutes = 10;
            if (!empty($erpEmp['custom_shift_name'])) {
                $shiftGroupName = $erpEmp['custom_shift_name'];
                $shiftStartTimeFormatted = $erpEmp['custom_start_time'] ?? '09:30:00';
                $shiftEndTimeFormatted = $erpEmp['custom_end_time'] ?? '18:30:00';
                $graceMinutes = (int)($erpEmp['custom_grace_minutes'] ?? 10);
            } else {
                $rawGroupName = trim($empTipsoi['shiftGroupName'] ?? 'Regular');
                // Clean up "Friday OFF" / "Saturday OFF" display name
                if (stripos($rawGroupName, 'OFF') !== false) {
                    $shiftGroupName = 'Regular Shift (' . $rawGroupName . ')';
                } else {
                    $shiftGroupName = $rawGroupName ?: 'Regular';
                }

                $shiftStartTimeRaw = trim($empTipsoi['shiftStartTime'] ?? '09:30 AM');
                $shiftEndTimeRaw = trim($empTipsoi['shiftEndTime'] ?? '06:30 PM');
                $shiftStartTimeFormatted = date('H:i:s', strtotime($shiftStartTimeRaw));
                $shiftEndTimeFormatted = date('H:i:s', strtotime($shiftEndTimeRaw));
            }

            // Extract punches from Tipsoi day log
            $punchInStr = null;
            $punchOutStr = null;

            if (!empty($empTipsoi['attendance'])) {
                foreach ($empTipsoi['attendance'] as $dayLog) {
                    $firstLog = trim($dayLog['firstLoggedTime'] ?? '-');
                    $lastLog = trim($dayLog['lastLoggedTime'] ?? '-');

                    if ($firstLog !== '' && $firstLog !== '-') {
                        $parsedIn = date('H:i:s', strtotime($firstLog));
                        $punchInStr = $date . ' ' . $parsedIn;
                    }

                    if ($lastLog !== '' && $lastLog !== '-' && $lastLog !== $firstLog) {
                        $parsedOut = date('H:i:s', strtotime($lastLog));
                        $punchOutStr = $date . ' ' . $parsedOut;
                    }
                    break; // Single date query has only 1 day item
                }
            }

            // ─── ERP Attendance Evaluation Engine ───
            $evaluated = $this->evaluateAttendance(
                $localEmpId,
                $date,
                $punchInStr,
                $punchOutStr,
                $shiftStartTimeFormatted,
                $shiftEndTimeFormatted,
                $isPublicHoliday,
                isset($approvedLeaves[$localEmpId]),
                isset($empDaysOff[$localEmpId]),
                $graceMinutes
            );

            // Execute UPSERT
            $stmt->bind_param(
                "issssssisii",
                $localEmpId,
                $date,
                $evaluated['punch_in_time'],
                $evaluated['punch_out_time'],
                $evaluated['status'],
                $shiftGroupName,
                $shiftStartTimeFormatted,
                $evaluated['late_minutes'],
                $evaluated['working_hours'],
                $evaluated['overtime_minutes'],
                $currentUserId
            );

            if ($stmt->execute()) {
                $syncedCount++;

                // If employee punched in on an Off-Day or Holiday, grant +1 Duty Leave (Comp-off)
                if (!empty($punchInStr) && ($isDayOff || $isPublicHoliday)) {
                    $dayType = $isPublicHoliday ? 'public_holiday' : 'weekly_holiday';
                    $this->creditDutyLeaveIfEligible($localEmpId, $date, $dayType);
                }
            }
        }

        $stmt->close();

        return [
            'success' => true,
            'synced_count' => $syncedCount,
            'message' => "Successfully synced {$syncedCount} employee attendance records for {$date}."
        ];
    }

    /**
     * Business Logic Rule Evaluation
     */
    protected function evaluateAttendance(
        $employeeId,
        $date,
        $punchIn,
        $punchOut,
        $shiftStartTime,
        $shiftEndTime,
        $isPublicHoliday,
        $hasApprovedLeave,
        $isDayOff,
        $graceMinutes = 10
    ) {
        $status = 'Absent';
        $lateMinutes = 0;
        $workingHours = null;
        $overtimeMinutes = 0;

        // 1. Check Leave
        if ($hasApprovedLeave && empty($punchIn)) {
            $status = 'On Leave';
        }
        // 2. Check Weekly Day-Off
        elseif ($isDayOff && empty($punchIn)) {
            $status = 'Day Off';
        }
        // 3. Check Public Holiday
        elseif ($isPublicHoliday && empty($punchIn)) {
            $status = 'Holiday';
        }
        // 4. Punch Analysis (Working day or Duty on Off-day/Holiday)
        elseif (!empty($punchIn)) {
            $inTime = date('H:i:s', strtotime($punchIn));
            $shiftStartSeconds = strtotime($date . ' ' . $shiftStartTime);
            $inSeconds = strtotime($date . ' ' . $inTime);
            $graceSeconds = $shiftStartSeconds + ($graceMinutes * 60); // Dynamic shift grace

            // Late threshold comparison
            if ($inSeconds <= $graceSeconds) {
                $status = 'Present';
                $lateMinutes = 0;
            } elseif ($inSeconds <= strtotime($date . ' 13:00:00')) {
                $status = 'Late';
                $lateMinutes = max(0, round(($inSeconds - $shiftStartSeconds) / 60));
            } else {
                $status = 'Half-Day';
                $lateMinutes = max(0, round(($inSeconds - $shiftStartSeconds) / 60));
            }

            // If punched on Off-Day or Holiday, still mark Present with notice
            if ($isDayOff || $isPublicHoliday) {
                $status = 'Present';
            }

            // Calculate working hours & overtime if punch out is available
            if (!empty($punchOut)) {
                $outSeconds = strtotime($punchOut);
                $durationSec = max(0, $outSeconds - $inSeconds);
                $hrs = floor($durationSec / 3600);
                $mins = floor(($durationSec % 3600) / 60);
                $workingHours = "{$hrs}h {$mins}m";

                $shiftEndSeconds = strtotime($date . ' ' . $shiftEndTime);
                if ($outSeconds > $shiftEndSeconds) {
                    $overtimeMinutes = round(($outSeconds - $shiftEndSeconds) / 60);
                }
            }
        } else {
            // No punch and not holiday/offday/leave
            $status = 'Absent';
        }

        return [
            'status' => $status,
            'punch_in_time' => $punchIn,
            'punch_out_time' => $punchOut,
            'late_minutes' => $lateMinutes,
            'working_hours' => $workingHours,
            'overtime_minutes' => $overtimeMinutes
        ];
    }

    /**
     * Map all ERP employees by normalized employee_id
     */
    protected function getErpEmployeesMap()
    {
        $map = [];
        $sql = "
            SELECT 
                e.id, e.employee_id, e.full_name, e.email, e.department_id, e.shift_id,
                s.name AS custom_shift_name, s.start_time AS custom_start_time, s.end_time AS custom_end_time, s.grace_minutes AS custom_grace_minutes
            FROM employees e
            LEFT JOIN shifts s ON e.shift_id = s.id
            WHERE e.deleted_at IS NULL
        ";
        $res = $this->db->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $eid = trim((string)$row['employee_id']);
                $norm = preg_match('/^\d+$/', $eid) ? (string)intval($eid) : $eid;
                $map[$norm] = $row;
            }
        }
        return $map;
    }

    /**
     * Check if date is a company holiday
     */
    protected function checkPublicHoliday($date)
    {
        $stmt = $this->db->prepare("SELECT id FROM holidays WHERE ? BETWEEN start_date AND end_date LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();
            return ($res && $res->num_rows > 0);
        }
        return false;
    }

    /**
     * Map of employee IDs who have approved leave on this date
     */
    protected function getApprovedLeavesMap($date)
    {
        $map = [];
        $stmt = $this->db->prepare("
            SELECT employee_id 
            FROM employee_leaves 
            WHERE ? BETWEEN start_date AND end_date 
              AND approval_status = 'approved'
        ");
        if ($stmt) {
            $stmt->bind_param("s", $date);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $map[$row['employee_id']] = true;
            }
            $stmt->close();
        }
        return $map;
    }

    /**
     * Map of employee IDs whose weekly day-off matches the day of week.
     * Rule:
     * - If an employee has custom day-off entries in emp_weekly_day_off, use their custom configuration.
     * - If an employee has NO custom entries in emp_weekly_day_off, they default to Friday (standard weekly off).
     */
    protected function getEmployeeDaysOffMap($dayOfWeekName)
    {
        $map = [];
        $isFriday = (strtolower($dayOfWeekName) === 'friday');

        // 1. Get all employees who have any custom day-off configured
        $employeesWithCustomOff = [];
        $resCustom = $this->db->query("SELECT DISTINCT employee_id FROM emp_weekly_day_off");
        if ($resCustom) {
            while ($row = $resCustom->fetch_assoc()) {
                $employeesWithCustomOff[(int)$row['employee_id']] = true;
            }
        }

        // 2. Mark employees who explicitly have THIS day configured as day-off
        $stmt = $this->db->prepare("
            SELECT employee_id 
            FROM emp_weekly_day_off 
            WHERE LOWER(day_off_name) = LOWER(?)
        ");
        if ($stmt) {
            $stmt->bind_param("s", $dayOfWeekName);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $map[(int)$row['employee_id']] = true;
            }
            $stmt->close();
        }

        // 3. For Friday: all active employees who do NOT have a custom day-off configuration automatically get Friday Off!
        if ($isFriday) {
            $resAll = $this->db->query("SELECT id FROM employees WHERE deleted_at IS NULL");
            if ($resAll) {
                while ($row = $resAll->fetch_assoc()) {
                    $empId = (int)$row['id'];
                    if (!isset($employeesWithCustomOff[$empId])) {
                        $map[$empId] = true;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * Credit +1 Duty Leave (Comp-off) when employee punches in on an off-day or holiday
     * Prevents duplicate accruals via emp_duty_leave_earnings unique key
     */
    protected function creditDutyLeaveIfEligible($employeeId, $date, $dayType)
    {
        // 1. Check if already credited for this date to prevent duplicate increments
        $checkStmt = $this->db->prepare("SELECT id FROM emp_duty_leave_earnings WHERE employee_id = ? AND work_date = ?");
        $checkStmt->bind_param("is", $employeeId, $date);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($exists) {
            return; // Already awarded for this date
        }

        // 2. Insert into emp_duty_leave_earnings
        $ins = $this->db->prepare("
            INSERT INTO emp_duty_leave_earnings (employee_id, work_date, day_type, earned_days, is_credited, created_at)
            VALUES (?, ?, ?, 1.0, 1, NOW())
        ");
        $ins->bind_param("iss", $employeeId, $date, $dayType);
        $ins->execute();
        $ins->close();

        // 3. Increment Duty Leave in employee_leave_balances
        $dtRes = $this->db->query("SELECT id FROM leave_types WHERE LOWER(name) LIKE '%duty%' LIMIT 1");
        $dutyTypeId = $dtRes ? (int)($dtRes->fetch_assoc()['id'] ?? 4) : 4;

        $balStmt = $this->db->prepare("SELECT id, remaining_days FROM employee_leave_balances WHERE employee_id = ? AND leave_type_id = ?");
        $balStmt->bind_param("ii", $employeeId, $dutyTypeId);
        $balStmt->execute();
        $bal = $balStmt->get_result()->fetch_assoc();
        $balStmt->close();

        if ($bal) {
            $up = $this->db->prepare("UPDATE employee_leave_balances SET remaining_days = remaining_days + 1, updated_at = NOW() WHERE id = ?");
            $up->bind_param("i", $bal['id']);
            $up->execute();
            $up->close();
        } else {
            $inBal = $this->db->prepare("INSERT INTO employee_leave_balances (employee_id, leave_type_id, remaining_days, created_at, updated_at) VALUES (?, ?, 1, NOW(), NOW())");
            $inBal->bind_param("ii", $employeeId, $dutyTypeId);
            $inBal->execute();
            $inBal->close();
        }
    }
}
