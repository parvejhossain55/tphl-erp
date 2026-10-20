<?php

class AtsCandidate extends Model
{
    protected $table = 'ats_candidates';

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Build WHERE clause, params, and types based on filters
     */
    private function buildFilterConditions(array $filters): array
    {
        $conditions = ["1=1"];
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

        // 3. Department Filter
        if (!empty($filters['department'])) {
            $conditions[] = "department = ?";
            $params[] = trim($filters['department']);
            $types .= "s";
        }

        // 4. Position Filter
        if (!empty($filters['position'])) {
            $conditions[] = "position = ?";
            $params[] = trim($filters['position']);
            $types .= "s";
        }

        // 5. Source Filter
        if (!empty($filters['source'])) {
            $conditions[] = "source = ?";
            $params[] = trim($filters['source']);
            $types .= "s";
        }

        // 6. Invitation Status Filter
        if (!empty($filters['invitation_status'])) {
            $conditions[] = "invitation_status = ?";
            $params[] = trim($filters['invitation_status']);
            $types .= "s";
        }

        // 7. Interview Attendance Filter
        if (!empty($filters['interview_attendance'])) {
            $conditions[] = "interview_attendance = ?";
            $params[] = trim($filters['interview_attendance']);
            $types .= "s";
        }

        // 8. Hired Filter
        if (!empty($filters['hired'])) {
            $conditions[] = "hired = ?";
            $params[] = trim($filters['hired']);
            $types .= "s";
        }

        // 9. Date Range Filter
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

        // Fetch Data
        $dataSql = "
            SELECT * FROM {$this->table}
            WHERE {$whereClause}
            ORDER BY 
                CASE WHEN interview_date = CURDATE() THEN 0 ELSE 1 END ASC,
                interview_date DESC,
                id DESC
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
                name, phone, email, position, department, source,
                invitation_status, referred_by, remarks,
                interview_call_date, interview_date, interview_time, interview_location,
                email_sent, sms_sent, interview_attendance,
                cv_source, cv_file_path, cv_drive_link,
                hired, joining_date, onboarding_salary, entry_mode, created_by
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");

        if (!$stmt) {
            error_log("[AtsCandidate::create] Prepare failed: " . $this->db->error);
            return false;
        }

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
        
        $hired = in_array($data['hired'] ?? '', ['yes', 'no']) ? $data['hired'] : 'pending';
        $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : null;
        $onboardingSalary = !empty($data['onboarding_salary']) ? (float) $data['onboarding_salary'] : null;
        $entryMode = in_array($data['entry_mode'] ?? '', ['single', 'csv', 'ai']) ? $data['entry_mode'] : 'single';
        $createdBy = !empty($data['created_by']) ? (int) $data['created_by'] : null;

        $stmt->bind_param(
            "sssssssssssssiissssssdsi",
            $name, $phone, $email, $position, $department, $source,
            $invitationStatus, $referredBy, $remarks,
            $interviewCallDate, $interviewDate, $interviewTime, $interviewLocation,
            $emailSent, $smsSent, $interviewAttendance,
            $cvSource, $cvFilePath, $cvDriveLink,
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
                hired = ?, joining_date = ?, onboarding_salary = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            error_log("[AtsCandidate::update] Prepare failed: " . $this->db->error);
            return false;
        }

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
        
        $hired = in_array($data['hired'] ?? '', ['yes', 'no']) ? $data['hired'] : 'pending';
        $joiningDate = !empty($data['joining_date']) ? $data['joining_date'] : null;
        $onboardingSalary = !empty($data['onboarding_salary']) ? (float) $data['onboarding_salary'] : null;

        $stmt->bind_param(
            "sssssssssssssiissssssdi",
            $name, $phone, $email, $position, $department, $source,
            $invitationStatus, $referredBy, $remarks,
            $interviewCallDate, $interviewDate, $interviewTime, $interviewLocation,
            $emailSent, $smsSent, $interviewAttendance,
            $cvSource, $cvFilePath, $cvDriveLink,
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
            'joining_date', 'onboarding_salary',
            'cv_source', 'cv_file_path', 'cv_drive_link'
        ];

        if (!in_array($fieldName, $allowedFields)) return false;

        // If date/time/salary/location field is empty string, convert to NULL for MySQL strict mode safety
        $nullableFields = [
            'interview_date', 'interview_time', 'interview_location',
            'interview_call_date', 'joining_date', 'onboarding_salary',
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

        if (isset($updates['hired']) && in_array($updates['hired'], ['pending', 'yes', 'no'])) {
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
     * Delete candidate record
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        if (!$stmt) return false;
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    /**
     * Bulk delete candidates by IDs
     */
    public function bulkDelete(array $ids): int
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
     * Get recruitment analytics dashboard data
     */
    public function getDashboardMetrics(?string $fromDate = null, ?string $toDate = null): array
    {
        $dateCondition = "1=1";
        $params = [];
        $types = "";

        if (!empty($fromDate) && !empty($toDate)) {
            $dateCondition = "DATE(created_at) BETWEEN ? AND ?";
            $params = [$fromDate, $toDate];
            $types = "ss";
        } elseif (!empty($fromDate)) {
            $dateCondition = "DATE(created_at) >= ?";
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
