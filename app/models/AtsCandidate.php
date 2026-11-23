<?php

class AtsCandidate extends Model
{
    protected $table = 'ats_candidates';
    private static bool $schemaChecked = false;

    public function __construct($db = null)
    {
        parent::__construct($db);
        $this->ensureSchemaUpdated();
    }

    /**
     * Ensure new columns and hired VARCHAR(50) exist on ats_candidates
     */
    private function ensureSchemaUpdated(): void
    {
        if (self::$schemaChecked || !$this->db) {
            return;
        }
        self::$schemaChecked = true;

        $res = $this->db->query("SHOW COLUMNS FROM `{$this->table}`");
        if (!$res) {
            return;
        }

        $cols = [];
        $hiredType = '';
        while ($row = $res->fetch_assoc()) {
            $cols[$row['Field']] = true;
            if ($row['Field'] === 'hired') {
                $hiredType = strtolower($row['Type'] ?? '');
            }
        }

        $alterParts = [];
        if (strpos($hiredType, 'enum') !== false) {
            $alterParts[] = "MODIFY COLUMN `hired` VARCHAR(50) DEFAULT 'Decision Pending'";
        }
        if (!isset($cols['current_salary'])) {
            $alterParts[] = "ADD COLUMN `current_salary` DECIMAL(12,2) DEFAULT NULL AFTER `cv_drive_link`";
        }
        if (!isset($cols['expected_salary'])) {
            $alterParts[] = "ADD COLUMN `expected_salary` DECIMAL(12,2) DEFAULT NULL AFTER `current_salary`";
        }
        if (!isset($cols['bottom_line_salary'])) {
            $alterParts[] = "ADD COLUMN `bottom_line_salary` DECIMAL(12,2) DEFAULT NULL AFTER `expected_salary`";
        }
        if (!isset($cols['notice_period'])) {
            $alterParts[] = "ADD COLUMN `notice_period` VARCHAR(30) DEFAULT NULL COMMENT 'Yes, Immediate Joiner' AFTER `bottom_line_salary`";
        }
        if (!isset($cols['notice_period_days'])) {
            $alterParts[] = "ADD COLUMN `notice_period_days` INT DEFAULT NULL COMMENT 'Notice period time in days' AFTER `notice_period`";
        }

        if (!empty($alterParts)) {
            $this->db->query("ALTER TABLE `{$this->table}` " . implode(", ", $alterParts));
        }
    }

    /**
     * Build WHERE clause, params, and types based on filters
     */
    private function buildFilterConditions(array $filters): array
    {
        $isTrash = !empty($filters['trash']);
        if ($isTrash) {
            $conditions = ["1=1", "deleted_at IS NOT NULL"];
        } else {
            $conditions = ["1=1", "deleted_at IS NULL"];
        }
        $params = [];
        $types = "";

        // 1. Search Query (Name, Phone, Email)
        if (!empty($filters['search'])) {
            $search = "%" . trim($filters['search']) . "%";
            $conditions[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "sss";
        }

        // 2. Tab Filter
        $tab = $filters['tab'] ?? 'all';
        if ($tab === 'calling_queue') {
            $conditions[] = "invitation_status IN ('Pending', 'Not Sure', 'Didn\'t Pick', 'Busy', 'Number Off', 'Will Inform Later')";
        } elseif ($tab === 'today') {
            $conditions[] = "interview_date = CURDATE()";
        } elseif ($tab === 'upcoming') {
            $conditions[] = "interview_date > CURDATE()";
        } elseif ($tab === 'passed') {
            $conditions[] = "interview_date < CURDATE()";
        } elseif ($tab === 'shortlisted') {
            $conditions[] = "interview_attendance = 'present' AND hired = 'pending'";
        } elseif ($tab === 'hired') {
            $conditions[] = "hired = 'yes'";
        }

        // Helper to add condition for string or array (multi-select)
        $addMultiOrSingleCondition = function($field, $val) use (&$conditions, &$params, &$types) {
            if (empty($val)) return;
            if (is_array($val)) {
                $filteredVals = array_values(array_filter(array_map('trim', $val), fn($v) => $v !== ''));
                if (!empty($filteredVals)) {
                    $placeholders = implode(',', array_fill(0, count($filteredVals), '?'));
                    $conditions[] = "`{$field}` IN ({$placeholders})";
                    foreach ($filteredVals as $item) {
                        $params[] = $item;
                        $types .= "s";
                    }
                }
            } else {
                $trimmed = trim((string)$val);
                if ($trimmed !== '') {
                    $conditions[] = "`{$field}` = ?";
                    $params[] = $trimmed;
                    $types .= "s";
                }
            }
        };

        // 3. Department Filter (Multi or Single)
        $addMultiOrSingleCondition('department', $filters['department'] ?? null);

        // 3b. Job Posting Filter
        if (!empty($filters['job_id'])) {
            $conditions[] = "`job_id` = ?";
            $params[] = (int)$filters['job_id'];
            $types .= "i";
        }

        // 4. Position Filter (Multi or Single)
        $addMultiOrSingleCondition('position', $filters['position'] ?? null);

        // 5. Source Filter (Multi or Single)
        $addMultiOrSingleCondition('source', $filters['source'] ?? null);

        // 6. Invitation Status Filter (Multi or Single)
        $addMultiOrSingleCondition('invitation_status', $filters['invitation_status'] ?? null);

        // 7. Interview Attendance Filter (Multi or Single)
        $addMultiOrSingleCondition('interview_attendance', $filters['interview_attendance'] ?? null);

        // 8. Interview Location Filter (Multi or Single)
        $addMultiOrSingleCondition('interview_location', $filters['interview_location'] ?? null);

        // 9. Hired / Final Decision Filter (Multi or Single)
        $addMultiOrSingleCondition('hired', $filters['hired'] ?? null);

        // 10. CV Uploaded Filter ('yes' / 'no')
        if (!empty($filters['cv_uploaded'])) {
            $cvVal = strtolower(trim((string)$filters['cv_uploaded']));
            if ($cvVal === 'yes' || $cvVal === '1') {
                $conditions[] = "((cv_file_path IS NOT NULL AND cv_file_path != '') OR (cv_drive_link IS NOT NULL AND cv_drive_link != ''))";
            } elseif ($cvVal === 'no' || $cvVal === '0') {
                $conditions[] = "((cv_file_path IS NULL OR cv_file_path = '') AND (cv_drive_link IS NULL OR cv_drive_link = ''))";
            }
        }

        // 11. Email Sent Filter ('yes' / 'no' / '1' / '0')
        if (isset($filters['email_sent']) && $filters['email_sent'] !== '') {
            $emailVal = strtolower(trim((string)$filters['email_sent']));
            if ($emailVal === 'yes' || $emailVal === '1') {
                $conditions[] = "email_sent = 1";
            } elseif ($emailVal === 'no' || $emailVal === '0') {
                $conditions[] = "(email_sent = 0 OR email_sent IS NULL)";
            }
        }

        // 12. SMS Sent Filter ('yes' / 'no' / '1' / '0')
        if (isset($filters['sms_sent']) && $filters['sms_sent'] !== '') {
            $smsVal = strtolower(trim((string)$filters['sms_sent']));
            if ($smsVal === 'yes' || $smsVal === '1') {
                $conditions[] = "sms_sent = 1";
            } elseif ($smsVal === 'no' || $smsVal === '0') {
                $conditions[] = "(sms_sent = 0 OR sms_sent IS NULL)";
            }
        }

        // 13. Date Range Filter
        $dateType = $filters['date_type'] ?? 'interview_date';
        $allowedDateCols = ['interview_date', 'interview_call_date', 'created_at'];
        if (!in_array($dateType, $allowedDateCols)) {
            $dateType = 'interview_date';
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE({$dateType}) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE({$dateType}) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $whereClause = implode(" AND ", $conditions);
        return [$whereClause, $params, $types];
    }

    /**
     * Get paginated candidates with multi-field search and quick tabs
     */
    public function getFiltered(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        if ($perPage >= 1000) {
            $perPage = 10000;
            $offset = 0;
        } else {
            $perPage = max(5, $perPage);
            $offset = ($page - 1) * $perPage;
        }

        [$whereClause, $params, $types] = $this->buildFilterConditions($filters);

        // Count Total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE {$whereClause}";
        $countStmt = $this->db->prepare($countSql);
        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->get_result()->fetch_assoc()['total'] ?? 0);

        // Dynamic Column Sorting
        $sortableColumns = [
            'id' => 'id',
            'name' => 'name',
            'position' => 'position',
            'department' => 'department',
            'invitation_status' => 'invitation_status',
            'interview_date' => 'interview_date',
            'interview_attendance' => 'interview_attendance',
            'hired' => 'hired',
            'email_sent' => 'email_sent',
            'cv_file_path' => 'cv_file_path',
            'created_at' => 'created_at',
        ];

        $sortBy = $filters['sort_by'] ?? '';
        $sortOrder = strtoupper($filters['sort_order'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

        if (!empty($sortBy) && isset($sortableColumns[$sortBy])) {
            $col = $sortableColumns[$sortBy];
            if ($col === 'interview_date') {
                $orderByClause = "interview_date IS NULL, interview_date {$sortOrder}, interview_time {$sortOrder}, id DESC";
            } elseif ($col === 'position') {
                $orderByClause = "position {$sortOrder}, department {$sortOrder}, id DESC";
            } elseif ($col === 'cv_file_path') {
                $orderByClause = "CASE WHEN (cv_file_path IS NOT NULL AND cv_file_path != '') OR (cv_drive_link IS NOT NULL AND cv_drive_link != '') THEN 0 ELSE 1 END {$sortOrder}, id DESC";
            } else {
                $orderByClause = "{$col} {$sortOrder}, id DESC";
            }
        } else {
            $orderByClause = "
                CASE WHEN interview_date = CURDATE() THEN 0 ELSE 1 END ASC,
                interview_date DESC,
                id DESC
            ";
        }

        // Fetch Data
        $dataSql = "
            SELECT * FROM {$this->table}
            WHERE {$whereClause}
            ORDER BY {$orderByClause}
            LIMIT ? OFFSET ?
        ";

        $dataStmt = $this->db->prepare($dataSql);
        $fetchParams = $params;
        $fetchParams[] = $perPage;
        $fetchParams[] = $offset;
        $fetchTypes = $types . "ii";

        $dataStmt->bind_param($fetchTypes, ...$fetchParams);
        $dataStmt->execute();
        $data = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get Quick Tab Counts
        $counts = $this->getTabCounts();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => max(1, (int) ceil($total / $perPage)),
            'counts' => $counts
        ];
    }

    /**
     * Get all filtered candidates without pagination for export (PDF / Excel)
     */
    public function getAllFiltered(array $filters = []): array
    {
        [$whereClause, $params, $types] = $this->buildFilterConditions($filters);

        $sql = "
            SELECT * FROM {$this->table}
            WHERE {$whereClause}
            ORDER BY 
                CASE WHEN interview_date = CURDATE() THEN 0 ELSE 1 END ASC,
                interview_date DESC,
                id DESC
        ";

        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get counts for quick filter tabs
     */
    public function getTabCounts(): array
    {
        $sql = "
            SELECT 
                COUNT(*) as all_count,
                SUM(CASE WHEN invitation_status IN ('Pending', 'Not Sure', 'Didn\'t Pick', 'Busy', 'Number Off', 'Will Inform Later') THEN 1 ELSE 0 END) as calling_queue_count,
                SUM(CASE WHEN interview_date = CURDATE() THEN 1 ELSE 0 END) as today_count,
                SUM(CASE WHEN interview_date > CURDATE() THEN 1 ELSE 0 END) as upcoming_count,
                SUM(CASE WHEN interview_date < CURDATE() THEN 1 ELSE 0 END) as passed_count,
                SUM(CASE WHEN interview_attendance = 'present' AND hired = 'pending' THEN 1 ELSE 0 END) as shortlisted_count,
                SUM(CASE WHEN hired = 'yes' THEN 1 ELSE 0 END) as hired_count
            FROM {$this->table}
            WHERE deleted_at IS NULL
        ";

        $res = $this->db->query($sql);
        if ($res && $row = $res->fetch_assoc()) {
            return [
                'all' => (int) $row['all_count'],
                'calling_queue' => (int) $row['calling_queue_count'],
                'today' => (int) $row['today_count'],
                'upcoming' => (int) $row['upcoming_count'],
                'passed' => (int) $row['passed_count'],
                'shortlisted' => (int) $row['shortlisted_count'],
                'hired' => (int) $row['hired_count'],
            ];
        }

        return ['all' => 0, 'calling_queue' => 0, 'today' => 0, 'upcoming' => 0, 'passed' => 0, 'shortlisted' => 0, 'hired' => 0];
    }

    /**
     * Find candidate by ID
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Create candidate (Single or CSV)
     */
    public function create(array $data): int|bool
    {
        // Name and Phone mandatory
        if (empty($data['name']) || empty($data['phone'])) {
            return false;
        }

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (
                job_id, name, phone, email, position, department, source,
                invitation_status, referred_by, remarks,
                interview_call_date, interview_date, interview_time, interview_location,
                email_sent, sms_sent, interview_attendance,
                cv_source, cv_file_path, cv_drive_link,
                current_salary, expected_salary, bottom_line_salary, notice_period, notice_period_days,
                hired, joining_date, onboarding_salary, entry_mode, created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");

        if (!$stmt) {
            error_log("[AtsCandidate::create] Prepare failed: " . $this->db->error);
            return false;
        }

        $jobId = !empty($data['job_id']) ? (int) $data['job_id'] : null;
        $name = trim($data['name']);
        $phone = trim($data['phone']);
        $email = !empty($data['email']) ? trim($data['email']) : '';
        $position = trim($data['position'] ?? 'Not Specified');
        $department = trim($data['department'] ?? 'General');
        $source = !empty($data['source']) ? trim($data['source']) : null;
        $invitationStatus = !empty($data['invitation_status']) ? trim($data['invitation_status']) : 'Pending';
        $referredBy = !empty($data['referred_by']) ? trim($data['referred_by']) : null;
        $remarks = !empty($data['remarks']) ? trim($data['remarks']) : null;
        
        $interviewCallDate = !empty($data['interview_call_date']) ? $data['interview_call_date'] : null;
        $interviewDate = !empty($data['interview_date']) ? $data['interview_date'] : null;
        $interviewTime = !empty($data['interview_time']) ? $data['interview_time'] : null;
        $interviewLocation = !empty($data['interview_location']) ? trim($data['interview_location']) : null;
        
        $emailSent = !empty($data['email_sent']) && in_array(strtolower($data['email_sent']), ['1', 'yes', 'true']) ? 1 : 0;
        $smsSent = !empty($data['sms_sent']) && in_array(strtolower($data['sms_sent']), ['1', 'yes', 'true']) ? 1 : 0;
        $interviewAttendance = in_array($data['interview_attendance'] ?? '', ['present', 'absent']) ? $data['interview_attendance'] : 'pending';
        
        $cvSource = in_array($data['cv_source'] ?? '', ['file', 'drive_link']) ? $data['cv_source'] : 'file';
        $cvFilePath = !empty($data['cv_file_path']) ? $data['cv_file_path'] : null;
        $cvDriveLink = !empty($data['cv_drive_link']) ? $data['cv_drive_link'] : null;

        $currentSalary = (isset($data['current_salary']) && $data['current_salary'] !== '' && $data['current_salary'] !== null) ? (float) $data['current_salary'] : null;
        $expectedSalary = (isset($data['expected_salary']) && $data['expected_salary'] !== '' && $data['expected_salary'] !== null) ? (float) $data['expected_salary'] : null;
        $bottomLineSalary = (isset($data['bottom_line_salary']) && $data['bottom_line_salary'] !== '' && $data['bottom_line_salary'] !== null) ? (float) $data['bottom_line_salary'] : null;
        $noticePeriod = in_array($data['notice_period'] ?? '', ['Yes', 'Immediate Joiner'], true) ? $data['notice_period'] : null;
        $noticePeriodDays = (isset($data['notice_period_days']) && $data['notice_period_days'] !== '' && $data['notice_period_days'] !== null) ? (int) $data['notice_period_days'] : null;
        
        $allowedHired = [
            'Decision Pending',
            'Not Selected',
            'Job Offered - Pending',
            'Job Offered - Accepted',
            'Job Offered - Rejected',
            'Joined',
            'Absent on Joining Day',
            'pending', 'yes', 'no'
        ];
        $hired = in_array($data['hired'] ?? '', $allowedHired, true) ? $data['hired'] : 'Decision Pending';
        $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : null;
        $onboardingSalary = !empty($data['onboarding_salary']) ? (float) $data['onboarding_salary'] : null;
        $entryMode = in_array($data['entry_mode'] ?? '', ['single', 'csv', 'ai', 'website']) ? $data['entry_mode'] : 'single';
        $createdBy = !empty($data['created_by']) ? (int) $data['created_by'] : null;

        $stmt->bind_param(
            "isssssssssssssiissssdddsissdsi",
            $jobId, $name, $phone, $email, $position, $department, $source,
            $invitationStatus, $referredBy, $remarks,
            $interviewCallDate, $interviewDate, $interviewTime, $interviewLocation,
            $emailSent, $smsSent, $interviewAttendance,
            $cvSource, $cvFilePath, $cvDriveLink,
            $currentSalary, $expectedSalary, $bottomLineSalary, $noticePeriod, $noticePeriodDays,
            $hired, $joiningDate, $onboardingSalary, $entryMode, $createdBy
        );

        if ($stmt->execute()) {
            return $this->db->insert_id;
        }

        error_log("[AtsCandidate::create] Execute failed: " . $stmt->error);
        return false;
    }

    /**
     * Update candidate record
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE {$this->table} SET
                name = ?, phone = ?, email = ?, position = ?, department = ?, source = ?,
                invitation_status = ?, referred_by = ?, remarks = ?,
                interview_call_date = ?, interview_date = ?, interview_time = ?, interview_location = ?,
                email_sent = ?, sms_sent = ?, interview_attendance = ?,
                cv_source = ?, cv_file_path = COALESCE(?, cv_file_path), cv_drive_link = ?,
                current_salary = ?, expected_salary = ?, bottom_line_salary = ?, notice_period = ?, notice_period_days = ?,
                hired = ?, joining_date = ?, onboarding_salary = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            error_log("[AtsCandidate::update] Prepare failed: " . $this->db->error);
            return false;
        }

        $jobId = !empty($data['job_id']) ? (int) $data['job_id'] : null;
        $name = trim($data['name']);
        $phone = trim($data['phone']);
        $email = !empty($data['email']) ? trim($data['email']) : '';
        $position = trim($data['position'] ?? 'Not Specified');
        $department = trim($data['department'] ?? 'General');
        $source = !empty($data['source']) ? trim($data['source']) : null;
        $invitationStatus = !empty($data['invitation_status']) ? trim($data['invitation_status']) : 'Pending';
        $referredBy = !empty($data['referred_by']) ? trim($data['referred_by']) : null;
        $remarks = !empty($data['remarks']) ? trim($data['remarks']) : null;
        
        $interviewCallDate = !empty($data['interview_call_date']) ? $data['interview_call_date'] : null;
        $interviewDate = !empty($data['interview_date']) ? $data['interview_date'] : null;
        $interviewTime = !empty($data['interview_time']) ? $data['interview_time'] : null;
        $interviewLocation = !empty($data['interview_location']) ? trim($data['interview_location']) : null;
        
        $emailSent = !empty($data['email_sent']) && in_array(strtolower((string)$data['email_sent']), ['1', 'yes', 'true']) ? 1 : 0;
        $smsSent = !empty($data['sms_sent']) && in_array(strtolower((string)$data['sms_sent']), ['1', 'yes', 'true']) ? 1 : 0;
        $interviewAttendance = in_array($data['interview_attendance'] ?? '', ['present', 'absent']) ? $data['interview_attendance'] : 'pending';
        
        $cvSource = in_array($data['cv_source'] ?? '', ['file', 'drive_link']) ? $data['cv_source'] : 'file';
        $cvFilePath = !empty($data['cv_file_path']) ? $data['cv_file_path'] : null;
        $cvDriveLink = !empty($data['cv_drive_link']) ? $data['cv_drive_link'] : null;

        $currentSalary = (isset($data['current_salary']) && $data['current_salary'] !== '' && $data['current_salary'] !== null) ? (float) $data['current_salary'] : null;
        $expectedSalary = (isset($data['expected_salary']) && $data['expected_salary'] !== '' && $data['expected_salary'] !== null) ? (float) $data['expected_salary'] : null;
        $bottomLineSalary = (isset($data['bottom_line_salary']) && $data['bottom_line_salary'] !== '' && $data['bottom_line_salary'] !== null) ? (float) $data['bottom_line_salary'] : null;
        $noticePeriod = in_array($data['notice_period'] ?? '', ['Yes', 'Immediate Joiner'], true) ? $data['notice_period'] : null;
        $noticePeriodDays = (isset($data['notice_period_days']) && $data['notice_period_days'] !== '' && $data['notice_period_days'] !== null) ? (int) $data['notice_period_days'] : null;
        
        $allowedHired = [
            'Decision Pending',
            'Not Selected',
            'Job Offered - Pending',
            'Job Offered - Accepted',
            'Job Offered - Rejected',
            'Joined',
            'Absent on Joining Day',
            'pending', 'yes', 'no'
        ];
        $hired = in_array($data['hired'] ?? '', $allowedHired, true) ? $data['hired'] : 'Decision Pending';
        $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : null;
        $onboardingSalary = !empty($data['onboarding_salary']) ? (float) $data['onboarding_salary'] : null;

        $stmt->bind_param(
            "sssssssssssssiissssdddsissdi",
            $name, $phone, $email, $position, $department, $source,
            $invitationStatus, $referredBy, $remarks,
            $interviewCallDate, $interviewDate, $interviewTime, $interviewLocation,
            $emailSent, $smsSent, $interviewAttendance,
            $cvSource, $cvFilePath, $cvDriveLink,
            $currentSalary, $expectedSalary, $bottomLineSalary, $noticePeriod, $noticePeriodDays,
            $hired, $joiningDate, $onboardingSalary, $id
        );

        if ($stmt->execute()) {
            return true;
        }

        error_log("[AtsCandidate::update] Execute failed: " . $stmt->error);
        return false;
    }

    /**
     * Quick 1-click update single column directly from table
     */
    public function updateField(int $id, string $fieldName, mixed $fieldValue): bool
    {
        $allowedFields = [
            'invitation_status', 'interview_attendance', 'hired', 
            'interview_date', 'interview_time', 'interview_location',
            'interview_call_date', 'email_sent', 'sms_sent', 'remarks',
            'current_salary', 'expected_salary', 'bottom_line_salary',
            'notice_period', 'notice_period_days',
            'joining_date', 'onboarding_salary',
            'cv_source', 'cv_file_path', 'cv_drive_link'
        ];

        if (!in_array($fieldName, $allowedFields)) return false;

        // If date/time/salary/location field is empty string, convert to NULL for MySQL strict mode safety
        $nullableFields = [
            'interview_date', 'interview_time', 'interview_location',
            'interview_call_date', 'joining_date', 'onboarding_salary',
            'current_salary', 'expected_salary', 'bottom_line_salary',
            'notice_period', 'notice_period_days',
            'remarks'
        ];
        if (in_array($fieldName, $nullableFields) && ($fieldValue === '' || $fieldValue === null)) {
            $stmt = $this->db->prepare("UPDATE {$this->table} SET `{$fieldName}` = NULL WHERE id = ?");
            if (!$stmt) return false;
            $stmt->bind_param("i", $id);
            return $stmt->execute();
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET `{$fieldName}` = ? WHERE id = ?");
        if (!$stmt) return false;

        $stmt->bind_param("si", $fieldValue, $id);
        return $stmt->execute();
    }

    /**
     * Quick update candidate contact details (Name, Phone, Email)
     */
    public function updateContactDetails(int $id, string $name, string $phone, string $email): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET name = ?, phone = ?, email = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("sssi", $name, $phone, $email, $id);
        return $stmt->execute();
    }

    /**
     * Quick update candidate position and department
     */
    public function updatePositionDept(int $id, string $position, string $department): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET position = ?, department = ? WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ssi", $position, $department, $id);
        return $stmt->execute();
    }

    /**
     * Bulk update multiple candidate records
     */
    public function bulkUpdate(array $ids, array $updates): int
    {
        if (empty($ids) || empty($updates)) return 0;

        $validIds = array_filter(array_map('intval', $ids));
        if (empty($validIds)) return 0;

        $setClauses = [];
        $params = [];
        $types = "";

        if (isset($updates['invitation_status']) && $updates['invitation_status'] !== '') {
            $setClauses[] = "invitation_status = ?";
            $params[] = $updates['invitation_status'];
            $types .= "s";
        }

        if (isset($updates['interview_attendance']) && in_array($updates['interview_attendance'], ['pending', 'present', 'absent'])) {
            $setClauses[] = "interview_attendance = ?";
            $params[] = $updates['interview_attendance'];
            $types .= "s";
        }

        if (isset($updates['interview_date'])) {
            if ($updates['interview_date'] === '') {
                $setClauses[] = "interview_date = NULL";
            } else {
                $setClauses[] = "interview_date = ?";
                $params[] = $updates['interview_date'];
                $types .= "s";
            }
        }

        if (isset($updates['interview_time'])) {
            if ($updates['interview_time'] === '') {
                $setClauses[] = "interview_time = NULL";
            } else {
                $setClauses[] = "interview_time = ?";
                $params[] = $updates['interview_time'];
                $types .= "s";
            }
        }

        if (isset($updates['interview_location'])) {
            if ($updates['interview_location'] === '') {
                $setClauses[] = "interview_location = NULL";
            } else {
                $setClauses[] = "interview_location = ?";
                $params[] = $updates['interview_location'];
                $types .= "s";
            }
        }

        if (isset($updates['department']) && $updates['department'] !== '') {
            $setClauses[] = "department = ?";
            $params[] = $updates['department'];
            $types .= "s";
        }

        if (isset($updates['position']) && $updates['position'] !== '') {
            $setClauses[] = "position = ?";
            $params[] = $updates['position'];
            $types .= "s";
        }

        $allowedHired = [
            'Decision Pending',
            'Not Selected',
            'Job Offered - Pending',
            'Job Offered - Accepted',
            'Job Offered - Rejected',
            'Joined',
            'Absent on Joining Day',
            'pending', 'yes', 'no'
        ];
        if (isset($updates['hired']) && in_array($updates['hired'], $allowedHired, true)) {
            $setClauses[] = "hired = ?";
            $params[] = $updates['hired'];
            $types .= "s";
        }

        if (isset($updates['email_sent'])) {
            $setClauses[] = "email_sent = ?";
            $params[] = !empty($updates['email_sent']) ? 1 : 0;
            $types .= "i";
        }

        if (isset($updates['sms_sent'])) {
            $setClauses[] = "sms_sent = ?";
            $params[] = !empty($updates['sms_sent']) ? 1 : 0;
            $types .= "i";
        }

        if (empty($setClauses)) return 0;

        $idPlaceholders = implode(',', array_fill(0, count($validIds), '?'));
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE id IN ({$idPlaceholders})";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return 0;

        foreach ($validIds as $id) {
            $params[] = $id;
            $types .= "i";
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    /**
     * Soft delete candidate record (Move to Trash)
     */
    public function delete(int $id): bool
    {
        return $this->softDelete($id);
    }

    public function softDelete(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Bulk soft delete candidates by IDs (Move to Trash)
     */
    public function bulkDelete(array $ids): int
    {
        return $this->bulkSoftDelete($ids);
    }

    public function bulkSoftDelete(array $ids): int
    {
        $validIds = array_filter(array_map('intval', $ids));
        if (empty($validIds)) return 0;

        $idPlaceholders = implode(',', array_fill(0, count($validIds), '?'));
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NOW() WHERE id IN ({$idPlaceholders})");
        if (!$stmt) return 0;

        $types = str_repeat("i", count($validIds));
        $stmt->bind_param($types, ...$validIds);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    /**
     * Restore candidate from Trash
     */
    public function restore(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NULL WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Bulk restore candidates from Trash
     */
    public function bulkRestore(array $ids): int
    {
        $validIds = array_filter(array_map('intval', $ids));
        if (empty($validIds)) return 0;

        $idPlaceholders = implode(',', array_fill(0, count($validIds), '?'));
        $stmt = $this->db->prepare("UPDATE {$this->table} SET deleted_at = NULL WHERE id IN ({$idPlaceholders})");
        if (!$stmt) return 0;

        $types = str_repeat("i", count($validIds));
        $stmt->bind_param($types, ...$validIds);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    /**
     * Hard delete candidate permanently from database
     */
    public function forceDelete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Bulk hard delete candidates permanently
     */
    public function bulkForceDelete(array $ids): int
    {
        $validIds = array_filter(array_map('intval', $ids));
        if (empty($validIds)) return 0;

        $idPlaceholders = implode(',', array_fill(0, count($validIds), '?'));
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id IN ({$idPlaceholders})");
        if (!$stmt) return 0;

        $types = str_repeat("i", count($validIds));
        $stmt->bind_param($types, ...$validIds);
        $stmt->execute();
        return $stmt->affected_rows;
    }

    /**
     * Empty all records in trash permanently
     */
    public function emptyTrash(): int
    {
        $this->db->query("DELETE FROM {$this->table} WHERE deleted_at IS NOT NULL");
        return $this->db->affected_rows;
    }

    /**
     * Get count of deleted candidates in trash
     */
    public function getTrashCount(): int
    {
        $res = $this->db->query("SELECT COUNT(*) as total FROM {$this->table} WHERE deleted_at IS NOT NULL");
        if ($res && $row = $res->fetch_assoc()) {
            return (int) $row['total'];
        }
        return 0;
    }

    /**
     * Get recruitment analytics dashboard data
     */
    public function getDashboardMetrics(?string $fromDate = null, ?string $toDate = null): array
    {
        $dateCondition = "deleted_at IS NULL";
        $params = [];
        $types = "";

        if (!empty($fromDate) && !empty($toDate)) {
            $dateCondition .= " AND DATE(created_at) BETWEEN ? AND ?";
            $params = [$fromDate, $toDate];
            $types = "ss";
        } elseif (!empty($fromDate)) {
            $dateCondition .= " AND DATE(created_at) >= ?";
            $params = [$fromDate];
            $types = "s";
        }

        // Summary counts
        $summarySql = "
            SELECT 
                COUNT(*) as total_shortlisted,
                SUM(CASE WHEN interview_call_date IS NOT NULL OR invitation_status NOT IN ('Pending', 'Not Sure') THEN 1 ELSE 0 END) as total_invitations,
                SUM(CASE WHEN interview_date < CURDATE() THEN 1 ELSE 0 END) as passed_interviews,
                SUM(CASE WHEN interview_date < CURDATE() AND interview_attendance = 'present' THEN 1 ELSE 0 END) as attended_count,
                SUM(CASE WHEN interview_date < CURDATE() AND interview_attendance = 'absent' THEN 1 ELSE 0 END) as absent_count,
                SUM(CASE WHEN hired = 'yes' THEN 1 ELSE 0 END) as hired_count
            FROM {$this->table}
            WHERE {$dateCondition}
        ";

        $summaryStmt = $this->db->prepare($summarySql);
        if (!empty($params)) {
            $summaryStmt->bind_param($types, ...$params);
        }
        $summaryStmt->execute();
        $summary = $summaryStmt->get_result()->fetch_assoc() ?? [];

        $attended = (int) ($summary['attended_count'] ?? 0);
        $absent = (int) ($summary['absent_count'] ?? 0);
        $totalShowable = $attended + $absent;
        $showUpRate = $totalShowable > 0 ? round(($attended / $totalShowable) * 100, 1) : 0;

        // Department Breakdown
        $deptSql = "
            SELECT department, COUNT(*) as count,
                   SUM(CASE WHEN interview_attendance = 'present' THEN 1 ELSE 0 END) as present_count,
                   SUM(CASE WHEN hired = 'yes' THEN 1 ELSE 0 END) as hired_count
            FROM {$this->table}
            WHERE {$dateCondition}
            GROUP BY department
            ORDER BY count DESC
        ";
        $deptStmt = $this->db->prepare($deptSql);
        if (!empty($params)) {
            $deptStmt->bind_param($types, ...$params);
        }
        $deptStmt->execute();
        $deptBreakdown = $deptStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Status Breakdown
        $statusSql = "
            SELECT invitation_status, COUNT(*) as count
            FROM {$this->table}
            WHERE {$dateCondition}
            GROUP BY invitation_status
            ORDER BY count DESC
        ";
        $statusStmt = $this->db->prepare($statusSql);
        if (!empty($params)) {
            $statusStmt->bind_param($types, ...$params);
        }
        $statusStmt->execute();
        $statusBreakdown = $statusStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Source Breakdown
        $sourceSql = "
            SELECT COALESCE(NULLIF(source, ''), 'Not Specified') as source_name, COUNT(*) as count
            FROM {$this->table}
            WHERE {$dateCondition}
            GROUP BY source_name
            ORDER BY count DESC
        ";
        $sourceStmt = $this->db->prepare($sourceSql);
        if (!empty($params)) {
            $sourceStmt->bind_param($types, ...$params);
        }
        $sourceStmt->execute();
        $sourceBreakdown = $sourceStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'total_shortlisted' => (int) ($summary['total_shortlisted'] ?? 0),
            'total_invitations' => (int) ($summary['total_invitations'] ?? 0),
            'passed_interviews' => (int) ($summary['passed_interviews'] ?? 0),
            'attended_count' => $attended,
            'absent_count' => $absent,
            'show_up_rate' => $showUpRate,
            'hired_count' => (int) ($summary['hired_count'] ?? 0),
            'department_breakdown' => $deptBreakdown,
            'status_breakdown' => $statusBreakdown,
            'source_breakdown' => $sourceBreakdown,
        ];
    }
}
