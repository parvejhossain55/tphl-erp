<?php

class ExternalApiService
{
    private $db;
    private $config;
    private $baseUrl;
    private $email;
    private $password;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        $this->config = require dirname(__DIR__) . '/config/config.php';

        $this->baseUrl = rtrim($this->config['tipsoi_base_url'] ?? '', '/') . '/';
        $this->email = $this->config['tipsoi_email'] ?? '';
        $this->password = $this->config['tipsoi_password'] ?? '';
    }

    /**
     * Authenticate and get Bearer Token from Tipsoi API
     * Stores token and expiration in PHP Session to prevent redundant login calls
     */
    public function getBearerToken()
    {
        if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
            session_start();
        }

        // Return cached token if valid
        if (
            !empty($_SESSION['tipsoi_api_token']) &&
            !empty($_SESSION['tipsoi_api_token_expires']) &&
            $_SESSION['tipsoi_api_token_expires'] > time()
        ) {
            return $_SESSION['tipsoi_api_token'];
        }

        $loginUrl = $this->baseUrl . 'auth/external-sync/sign-in';
        $payload = json_encode([
            'email' => $this->email,
            'password' => $this->password
        ]);

        $ch = curl_init($loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Allow local/dev environments without SSL issues

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Tipsoi Login cURL Error: " . $curlError);
            return null;
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            error_log("Tipsoi Login HTTP Failure. Code: $httpCode, Response: " . $response);
            return null;
        }

        $data = json_decode($response, true);

        // Extract token from common paths (access_token, token, data.token)
        $token = $data['token'] ?? $data['access_token'] ?? $data['data']['token'] ?? $data['data']['access_token'] ?? null;

        if ($token) {
            $_SESSION['tipsoi_api_token'] = $token;
            // Cache token for 55 minutes (usually tokens are valid for 1 hour)
            $_SESSION['tipsoi_api_token_expires'] = time() + 3300;
            return $token;
        }

        error_log("Tipsoi Token extraction failed from response: " . $response);
        return null;
    }

    /**
     * Get employees from Tipsoi API with pagination
     */
    public function getExternalEmployees($page = 1, $limit = 15)
    {
        $token = $this->getBearerToken();

        if (!$token) {
            error_log("Tipsoi API Fetch Blocked: Bearer token is unavailable.");
            return $this->getFallbackMockData($page, $limit, "Tipsoi Authentication Failed. Please check config.php credentials.");
        }

        $employeesUrl = $this->baseUrl . 'employee?' . http_build_query([
            'order' => 'asc',
            'pageNumber' => $page - 1,
            'perPage' => $limit,
            'status' => 1
        ]);

        $ch = curl_init($employeesUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Tipsoi Employees fetch cURL Error: " . $curlError);
            return $this->getFallbackMockData($page, $limit, "Tipsoi connection timed out: " . $curlError);
        }

        if ($httpCode !== 200) {
            error_log("Tipsoi Employees fetch HTTP Failure. Code: $httpCode, Response: " . $response);
            return $this->getFallbackMockData($page, $limit, "Tipsoi API returned status code $httpCode");
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->getFallbackMockData($page, $limit, "Invalid JSON received from Tipsoi API");
        }

        // Parse and return formatted results
        // Standard Tipsoi format usually wraps array in 'data', 'employees', or 'content'
        $employees = $data['data'] ?? $data['employees'] ?? $data['content'] ?? $data;
        $total = (int) ($data['total'] ?? $data['totalElements'] ?? $data['meta']['total'] ?? count($employees));

        // Auto-sync joining_date for local employees based on Tipsoi startDate (only if not already set in ERP)
        if (is_array($employees)) {
            $updateStmt = $this->db->prepare("UPDATE employees SET joining_date = ? WHERE employee_id = ? AND (joining_date IS NULL OR joining_date = '0000-00-00')");
            if ($updateStmt) {
                foreach ($employees as $extEmp) {
                    $officeId = trim((string) ($extEmp['employeeOfficeId'] ?? $extEmp['employeeId'] ?? ''));
                    if ($officeId !== '' && !empty($extEmp['startDate'])) {
                        $parsedDate = date('Y-m-d', strtotime($extEmp['startDate']));
                        if ($parsedDate && $parsedDate !== '1970-01-01') {
                            $updateStmt->bind_param("ss", $parsedDate, $officeId);
                            $updateStmt->execute();
                        }
                    }
                }
                $updateStmt->close();
            }
        }

        return [
            'success' => true,
            'data' => $employees,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'from_mock' => false
        ];
    }

    /**
     * Get attendance logs from Tipsoi API for a specific timestamp range (milliseconds)
     */
    public function getAttendanceLogs($fromTime, $toTime)
    {
        $token = $this->getBearerToken();
        if (!$token) {
            error_log("Tipsoi API Fetch Blocked: Bearer token is unavailable.");
            return null;
        }

        $url = $this->baseUrl . "attendance?" . http_build_query([
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("Tipsoi Attendance Fetch cURL Error: " . $curlError);
            return null;
        }

        if ($httpCode !== 200) {
            error_log("Tipsoi Attendance Fetch HTTP Failure. Code: $httpCode");
            return null;
        }

        $data = json_decode($response, true);
        return $data['attendance'] ?? [];
    }

    /**
     * Fallback mock data in case API endpoint is down or config is incorrect.
     * Ensures UI displays cleanly and helps in testing/debugging.
     */
    private function getFallbackMockData($page, $limit, $errorMessage = '')
    {
        $mockDb = [
            ['id' => 101, 'employee_id' => 'EXT-1001', 'full_name' => 'Abul Hasan', 'designation_name' => 'Software Engineer', 'department_name' => 'IT', 'email' => 'hasan@external.com', 'phone' => '01711122233'],
            ['id' => 102, 'employee_id' => 'EXT-1002', 'full_name' => 'Fahmida Rahman', 'designation_name' => 'HR Specialist', 'department_name' => 'HR', 'email' => 'fahmida@external.com', 'phone' => '01822233344'],
            ['id' => 103, 'employee_id' => 'EXT-1003', 'full_name' => 'Rana Chowdhury', 'designation_name' => 'Accounts Manager', 'department_name' => 'Accounts', 'email' => 'rana@external.com', 'phone' => '01933344455'],
            ['id' => 104, 'employee_id' => 'EXT-1004', 'full_name' => 'Nusrat Jahan', 'designation_name' => 'QA Engineer', 'department_name' => 'IT', 'email' => 'nusrat@external.com', 'phone' => '01644455566'],
            ['id' => 105, 'employee_id' => 'EXT-1005', 'full_name' => 'Kamal Ahmed', 'designation_name' => 'Sales Executive', 'department_name' => 'Sales', 'email' => 'kamal@external.com', 'phone' => '01555566677'],
            ['id' => 106, 'employee_id' => 'EXT-1006', 'full_name' => 'Tariqul Islam', 'designation_name' => 'Marketing Lead', 'department_name' => 'Marketing', 'email' => 'tariq@external.com', 'phone' => '01766677788'],
            ['id' => 107, 'employee_id' => 'EXT-1007', 'full_name' => 'Sadia Afrin', 'designation_name' => 'Product Owner', 'department_name' => 'IT', 'email' => 'sadia@external.com', 'phone' => '01877788899'],
            ['id' => 108, 'employee_id' => 'EXT-1008', 'full_name' => 'Mehedi Hasan', 'designation_name' => 'System Admin', 'department_name' => 'IT', 'email' => 'mehedi@external.com', 'phone' => '01988899900'],
            ['id' => 109, 'employee_id' => 'EXT-1009', 'full_name' => 'Laila Akhter', 'designation_name' => 'Receptionist', 'department_name' => 'Admin', 'email' => 'laila@external.com', 'phone' => '01599900011'],
            ['id' => 110, 'employee_id' => 'EXT-1010', 'full_name' => 'Zubair Rahman', 'designation_name' => 'Content Writer', 'department_name' => 'Marketing', 'email' => 'zubair@external.com', 'phone' => '01311122233'],
            ['id' => 111, 'employee_id' => 'EXT-1011', 'full_name' => 'Keya Sen', 'designation_name' => 'Graphic Designer', 'department_name' => 'IT', 'email' => 'keya@external.com', 'phone' => '01422233344'],
            ['id' => 112, 'employee_id' => 'EXT-1012', 'full_name' => 'Rafiqul Bari', 'designation_name' => 'Office Assistant', 'department_name' => 'Admin', 'email' => 'rafiq@external.com', 'phone' => '01533344455'],
            ['id' => 113, 'employee_id' => 'EXT-1013', 'full_name' => 'Taskin Ahmed', 'designation_name' => 'Support Engineer', 'department_name' => 'IT', 'email' => 'taskin@external.com', 'phone' => '01655566677'],
            ['id' => 114, 'employee_id' => 'EXT-1014', 'full_name' => 'Mithila Sharmin', 'designation_name' => 'Brand Executive', 'department_name' => 'Marketing', 'email' => 'mithila@external.com', 'phone' => '01777788899'],
            ['id' => 115, 'employee_id' => 'EXT-1015', 'full_name' => 'Imran Khan', 'designation_name' => 'Procurement Officer', 'department_name' => 'Accounts', 'email' => 'imran@external.com', 'phone' => '01888899900'],
            ['id' => 116, 'employee_id' => 'EXT-1016', 'full_name' => 'Shaila Alam', 'designation_name' => 'Data Analyst', 'department_name' => 'IT', 'email' => 'shaila@external.com', 'phone' => '01999900011'],
            ['id' => 117, 'employee_id' => 'EXT-1017', 'full_name' => 'Ahsan Habib', 'designation_name' => 'UX Researcher', 'department_name' => 'IT', 'email' => 'habib@external.com', 'phone' => '01344455566'],
            ['id' => 118, 'employee_id' => 'EXT-1018', 'full_name' => 'Nigar Sultana', 'designation_name' => 'HR Manager', 'department_name' => 'HR', 'email' => 'nigar@external.com', 'phone' => '01455566677']
        ];

        $offset = ($page - 1) * $limit;
        $sliced = array_slice($mockDb, $offset, $limit);

        return [
            'success' => true,
            'data' => $sliced,
            'total' => count($mockDb),
            'page' => $page,
            'limit' => $limit,
            'from_mock' => true,
            'error_message' => $errorMessage
        ];
    }

    /**
     * Authenticate an individual user directly against Tipsoi API
     * Returns JWT token on success or error info on mismatch
     */
    public function authenticateUser($email, $password)
    {
        $loginUrl = $this->baseUrl . 'auth/external-sync/sign-in';

        $ch = curl_init($loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'email' => trim($email),
            'password' => $password
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 12);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => 'Tipsoi Connection Error: ' . $curlError];
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            return ['success' => false, 'message' => 'Tipsoi credentials mismatch (HTTP ' . $httpCode . ')'];
        }

        $data = json_decode($response, true);
        $token = $data['token'] ?? $data['access_token'] ?? $data['data']['token'] ?? $data['data']['access_token'] ?? null;

        if ($token) {
            $tipsoiId = null;
            $tokenParts = explode('.', $token);
            if (isset($tokenParts[1])) {
                $payloadJson = base64_decode(str_pad(strtr($tokenParts[1], '-_', '+/'), strlen($tokenParts[1]) % 4, '=', STR_PAD_RIGHT));
                $jwtPayload = json_decode($payloadJson, true);
                $tipsoiId = $jwtPayload['sub'] ?? null;
            }

            return [
                'success' => true,
                'token' => $token,
                'tipsoi_id' => $tipsoiId,
                'expires' => time() + 3300,
                'profile' => $data['data'] ?? $data['user'] ?? null
            ];
        }

        return ['success' => false, 'message' => 'Token extraction failed from Tipsoi response.'];
    }

    /**
     * Get active Bearer Token for the logged-in user if available.
     * Default does NOT fall back to Master token to protect user identity on Tipsoi.
     */
    public function getUserBearerToken($fallbackToMaster = false)
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!empty($_SESSION['user']['tipsoi_token'])) {
            $expires = $_SESSION['user']['tipsoi_token_expires'] ?? 0;
            if ($expires > time()) {
                return $_SESSION['user']['tipsoi_token'];
            }
        }

        return $fallbackToMaster ? $this->getBearerToken() : null;
    }

    /**
     * Check if currently logged in user has their own valid active Tipsoi token
     */
    public function hasActiveUserBearerToken(): bool
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        return !empty($_SESSION['user']['tipsoi_token']) &&
               (($_SESSION['user']['tipsoi_token_expires'] ?? 0) > time());
    }

    /**
     * Get single employee basic profile directly from Tipsoi
     * GET /employee/basic-profile/{employeeId}
     */
    public function getEmployeeBasicProfile($tipsoiId, $token = null)
    {
        if (!$token) {
            $token = $this->getUserBearerToken(true);
        }
        if (!$token || !$tipsoiId) {
            return null;
        }

        $url = $this->baseUrl . "employee/basic-profile/" . urlencode($tipsoiId);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $data = json_decode($res, true);
            return $data['employee'] ?? $data['data']['employee'] ?? $data['data'] ?? null;
        }

        return null;
    }

    /**
     * Ensure local `attendances` and `attendance_sync_logs` tables exist in ERP database
     */
    public function ensureAttendanceStorageTables(): void
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `attendances` (
                `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `employee_id` INT NULL DEFAULT NULL,
                `office_id` VARCHAR(50) NOT NULL,
                `tipsoi_emp_id` VARCHAR(50) NULL DEFAULT NULL,
                `employee_name` VARCHAR(150) NULL DEFAULT NULL,
                `department_name` VARCHAR(150) NULL DEFAULT NULL,
                `designation_name` VARCHAR(150) NULL DEFAULT NULL,
                `workplace_name` VARCHAR(150) NULL DEFAULT NULL,
                `shift_start_time` VARCHAR(50) NULL DEFAULT NULL,
                `shift_end_time` VARCHAR(50) NULL DEFAULT NULL,
                `shift_group_name` VARCHAR(100) NULL DEFAULT NULL,
                `date_of_join` VARCHAR(50) NULL DEFAULT NULL,
                `attendance_date` DATE NOT NULL,
                `day_timestamp` VARCHAR(30) NULL DEFAULT NULL,
                `date_label` VARCHAR(50) NULL DEFAULT NULL,
                `first_logged_time` VARCHAR(50) NULL DEFAULT NULL,
                `last_logged_time` VARCHAR(50) NULL DEFAULT NULL,
                `total_hour` VARCHAR(50) NULL DEFAULT NULL,
                `is_present` TINYINT(1) NOT NULL DEFAULT 0,
                `is_absent` TINYINT(1) NOT NULL DEFAULT 0,
                `is_late` TINYINT(1) NOT NULL DEFAULT 0,
                `is_early` TINYINT(1) NOT NULL DEFAULT 0,
                `late_by` VARCHAR(50) NULL DEFAULT NULL,
                `late_approval_status` VARCHAR(50) NULL DEFAULT NULL,
                `is_leave` TINYINT(1) NOT NULL DEFAULT 0,
                `is_half_day` TINYINT(1) NOT NULL DEFAULT 0,
                `is_offday` TINYINT(1) NOT NULL DEFAULT 0,
                `is_holiday` TINYINT(1) NOT NULL DEFAULT 0,
                `raw_day_json` MEDIUMTEXT NULL DEFAULT NULL,
                `synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_office_att_date` (`office_id`, `attendance_date`),
                KEY `idx_att_date` (`attendance_date`),
                KEY `idx_emp_id` (`employee_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $this->db->query("
            CREATE TABLE IF NOT EXISTS `attendance_sync_logs` (
                `sync_date` DATE NOT NULL,
                `records_count` INT NOT NULL DEFAULT 0,
                `last_synced_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`sync_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");
    }

    /**
     * Normalize Tipsoi day record date/timestamp into YYYY-MM-DD (Asia/Dhaka)
     */
    protected function resolveTipsoiDayDate(array $dayAtt, $tsKey): ?string
    {
        $rawDate = $dayAtt['date'] ?? ($dayAtt['attendanceDate'] ?? null);
        if (!empty($rawDate)) {
            $s = trim((string) $rawDate);
            if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})/', $s, $m)) {
                return sprintf('%04d-%02d-%02d', (int) $m[3], (int) $m[2], (int) $m[1]);
            }
            if (preg_match('/^(\d{4})[.\/-](\d{1,2})[.\/-](\d{1,2})/', $s, $m)) {
                return sprintf('%04d-%02d-%02d', (int) $m[1], (int) $m[2], (int) $m[3]);
            }
        }

        if (is_numeric($tsKey) && $tsKey > 0) {
            $sec = $tsKey > 9999999999 ? (int) floor($tsKey / 1000) : (int) $tsKey;
            $dt = new DateTime("@$sec");
            $dt->setTimezone(new DateTimeZone('Asia/Dhaka'));
            return $dt->format('Y-m-d');
        }

        return null;
    }

    /**
     * Normalize Office ID (strip leading zeros if purely numeric)
     */
    protected function normalizeOfficeId($id): string
    {
        $str = trim((string) ($id ?? ''));
        return preg_match('/^\d+$/', $str) ? (string) ((int) $str) : $str;
    }

    /**
     * Check if Tipsoi has new data, sync into ERP `attendances` table,
     * and return attendance data exclusively from the local `attendances` table.
     */
    public function syncAndGetAttendanceFromDb(string $fromDateStr, string $toDateStr, bool $forceSync = false): array
    {
        $this->ensureAttendanceStorageTables();

        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        $fromTs = strtotime($fromDateStr);
        $toTs = strtotime($toDateStr);
        if (!$fromTs) {
            $fromDateStr = date('Y-m-d');
            $fromTs = strtotime($fromDateStr);
        }
        if (!$toTs) {
            $toDateStr = date('Y-m-d');
            $toTs = strtotime($toDateStr);
        }
        if ($fromTs > $toTs) {
            [$fromDateStr, $toDateStr] = [$toDateStr, $fromDateStr];
            [$fromTs, $toTs] = [$toTs, $fromTs];
        }

        $totalPeriodDays = max(1, (int) round(($toTs - $fromTs) / 86400) + 1);
        $todayTs = strtotime(date('Y-m-d'));
        $effectiveToTs = min($toTs, $todayTs);
        $expectedSyncedDays = $effectiveToTs >= $fromTs
            ? max(1, (int) round(($effectiveToTs - $fromTs) / 86400) + 1)
            : 0;

        $escapedFrom = $this->db->real_escape_string($fromDateStr);
        $escapedTo = $this->db->real_escape_string($toDateStr);

        // 1. Check current coverage and freshness in local `attendances` & `attendance_sync_logs`
        $needTipsoiSync = $forceSync;
        if (!$needTipsoiSync) {
            $chkRes = $this->db->query("
                SELECT COUNT(DISTINCT attendance_date) AS synced_days,
                       MAX(synced_at) AS latest_sync,
                       MIN(synced_at) AS oldest_sync
                FROM attendances
                WHERE attendance_date BETWEEN '$escapedFrom' AND '$escapedTo'
            ");
            $chkRow = $chkRes ? $chkRes->fetch_assoc() : null;
            $syncedDays = (int) ($chkRow['synced_days'] ?? 0);
            $latestSync = $chkRow['latest_sync'] ?? null;

            if (($expectedSyncedDays > 0 && $syncedDays < $expectedSyncedDays) || empty($latestSync)) {
                $needTipsoiSync = true;
            } else {
                // Re-check Tipsoi if last sync was more than 3 minutes ago to catch any newly added punches/approvals
                $secondsSinceSync = time() - strtotime($latestSync);
                if ($secondsSinceSync > 180) {
                    $needTipsoiSync = true;
                }
            }
        }

        $didSyncFromTipsoi = false;
        $syncedRecordsCount = 0;
        $tipsoiErrorMsg = null;

        // 2. If needed, fetch from Tipsoi API and save/upsert into `attendances` table
        if ($needTipsoiSync) {
            $fromTimeMs = strtotime($fromDateStr . ' 00:00:00') * 1000;
            $toTimeMs = strtotime($toDateStr . ' 23:59:59') * 1000;

            $token = $this->getBearerToken();
            if ($token) {
                $url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?" . http_build_query([
                    'officeId' => 4699,
                    'order' => 'asc',
                    'pageNumber' => 0,
                    'perPage' => 2000,
                    'reportType' => 'department',
                    'sortKey' => 'reportPosition',
                    'status' => 1,
                    'from' => $fromTimeMs,
                    'to' => $toTimeMs
                ]);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $token,
                    'Accept: application/json'
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                if (!$curlError && $httpCode === 200 && $response) {
                    $decoded = json_decode($response, true);
                    if (isset($decoded['attendance']) && is_array($decoded['attendance'])) {
                        $syncedRecordsCount = $this->saveTipsoiAttendanceToTable($decoded['attendance'], $fromDateStr, $toDateStr);
                        $didSyncFromTipsoi = true;
                    } else {
                        $tipsoiErrorMsg = $decoded['message'] ?? 'Invalid response structure from Tipsoi API.';
                    }
                } else {
                    $tipsoiErrorMsg = $curlError ?: "Tipsoi API returned HTTP $httpCode.";
                }
            } else {
                $tipsoiErrorMsg = 'Failed to authenticate with Tipsoi API.';
            }
        }

        date_default_timezone_set($oldTz);

        // 3. Always read and build the response from our ERP `attendances` table for the exact date range
        $dbAttendanceData = $this->buildAttendancePayloadFromTable($fromDateStr, $toDateStr);

        // If DB has no records AND Tipsoi failed, report error; otherwise serve from DB
        if (empty($dbAttendanceData) && !$didSyncFromTipsoi && !empty($tipsoiErrorMsg)) {
            return [
                'success' => false,
                'message' => $tipsoiErrorMsg
            ];
        }

        return [
            'success' => true,
            'data' => $dbAttendanceData,
            'source' => 'erp_attendances_table',
            'synced_from_tipsoi' => $didSyncFromTipsoi,
            'synced_records_count' => $syncedRecordsCount,
            'total_period_days' => $totalPeriodDays,
            'from_date' => $fromDateStr,
            'to_date' => $toDateStr
        ];
    }

    /**
     * Save/Upsert Tipsoi attendance payload into ERP `attendances` table
     */
    protected function saveTipsoiAttendanceToTable(array $tipsoiEmployees, string $fromDateStr, string $toDateStr): int
    {
        // Build lookup map of local ERP employees
        $empIdByOfficeId = [];
        $empByName = [];
        $empRes = $this->db->query("SELECT id, employee_id, full_name FROM employees WHERE deleted_at IS NULL");
        if ($empRes) {
            while ($er = $empRes->fetch_assoc()) {
                $normOid = $this->normalizeOfficeId($er['employee_id'] ?? '');
                if ($normOid !== '') {
                    $empIdByOfficeId[$normOid] = (int) $er['id'];
                }
                $normName = strtolower(trim($er['full_name'] ?? ''));
                if ($normName !== '') {
                    $empByName[$normName] = [
                        'id' => (int) $er['id'],
                        'office_id' => $normOid
                    ];
                }
            }
        }

        $stmt = $this->db->prepare("
            INSERT INTO `attendances` (
                `employee_id`, `office_id`, `tipsoi_emp_id`, `employee_name`,
                `department_name`, `designation_name`, `workplace_name`,
                `shift_start_time`, `shift_end_time`, `shift_group_name`, `date_of_join`,
                `attendance_date`, `day_timestamp`, `date_label`,
                `first_logged_time`, `last_logged_time`, `total_hour`,
                `is_present`, `is_absent`, `is_late`, `is_early`,
                `late_by`, `late_approval_status`,
                `is_leave`, `is_half_day`, `is_offday`, `is_holiday`,
                `raw_day_json`, `synced_at`
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?,
                ?, ?, ?, ?,
                ?, NOW()
            )
            ON DUPLICATE KEY UPDATE
                `employee_id` = COALESCE(VALUES(`employee_id`), `employee_id`),
                `tipsoi_emp_id` = VALUES(`tipsoi_emp_id`),
                `employee_name` = VALUES(`employee_name`),
                `department_name` = VALUES(`department_name`),
                `designation_name` = VALUES(`designation_name`),
                `workplace_name` = VALUES(`workplace_name`),
                `shift_start_time` = VALUES(`shift_start_time`),
                `shift_end_time` = VALUES(`shift_end_time`),
                `shift_group_name` = VALUES(`shift_group_name`),
                `date_of_join` = VALUES(`date_of_join`),
                `day_timestamp` = VALUES(`day_timestamp`),
                `date_label` = VALUES(`date_label`),
                `first_logged_time` = VALUES(`first_logged_time`),
                `last_logged_time` = VALUES(`last_logged_time`),
                `total_hour` = VALUES(`total_hour`),
                `is_present` = VALUES(`is_present`),
                `is_absent` = VALUES(`is_absent`),
                `is_late` = VALUES(`is_late`),
                `is_early` = VALUES(`is_early`),
                `late_by` = VALUES(`late_by`),
                `late_approval_status` = VALUES(`late_approval_status`),
                `is_leave` = VALUES(`is_leave`),
                `is_half_day` = VALUES(`is_half_day`),
                `is_offday` = VALUES(`is_offday`),
                `is_holiday` = VALUES(`is_holiday`),
                `raw_day_json` = VALUES(`raw_day_json`),
                `synced_at` = NOW()
        ");

        if (!$stmt) {
            error_log("Prepare failed for attendances upsert: " . $this->db->error);
            return 0;
        }

        $savedCount = 0;
        $dateCounts = [];

        $this->db->begin_transaction();
        try {
            foreach ($tipsoiEmployees as $tEmp) {
                $rawOfficeId = trim((string) ($tEmp['employeeOfficeId'] ?? $tEmp['employeeId'] ?? ''));
                $normOfficeId = $this->normalizeOfficeId($rawOfficeId);
                $empName = trim((string) ($tEmp['employeeName'] ?? ''));
                $normName = strtolower($empName);

                $localEmpId = null;
                if ($normOfficeId !== '' && isset($empIdByOfficeId[$normOfficeId])) {
                    $localEmpId = $empIdByOfficeId[$normOfficeId];
                } elseif ($normName !== '' && isset($empByName[$normName])) {
                    $localEmpId = $empByName[$normName]['id'];
                    if ($normOfficeId === '' && $empByName[$normName]['office_id'] !== '') {
                        $normOfficeId = $empByName[$normName]['office_id'];
                    }
                }

                if ($normOfficeId === '') {
                    continue;
                }

                $tipsoiEmpId = trim((string) ($tEmp['employeeId'] ?? ''));
                $deptName = trim((string) ($tEmp['departmentName'] ?? ''));
                $desgName = trim((string) ($tEmp['designationName'] ?? ''));
                $workplaceName = trim((string) ($tEmp['workplaceName'] ?? ''));
                $shiftStart = trim((string) ($tEmp['shiftStartTime'] ?? ''));
                $shiftEnd = trim((string) ($tEmp['shiftEndTime'] ?? ''));
                $shiftGroup = trim((string) ($tEmp['shiftGroupName'] ?? ''));
                $dateOfJoin = trim((string) ($tEmp['dateOfJoin'] ?? ''));

                $attMap = $tEmp['attendance'] ?? [];
                if (!is_array($attMap) || empty($attMap)) {
                    continue;
                }

                // Reconcile summary approved lates if Tipsoi summary reports approved lates not flagged on daily items
                $summ = $tEmp['summary'] ?? [];
                $summApprovedLates = !empty($summ['totalApprovedLateApplication'])
                    ? (int) round(((int) $summ['totalApprovedLateApplication']) / 2)
                    : 0;
                $dailyApprovedCount = 0;
                $unapprovedLateDates = [];

                $normalizedDays = [];
                foreach ($attMap as $tsKey => $dayAtt) {
                    if (!is_array($dayAtt)) {
                        continue;
                    }
                    $dayDate = $this->resolveTipsoiDayDate($dayAtt, $tsKey);
                    if (!$dayDate) {
                        continue;
                    }
                    // Strictly keep only dates within the requested [fromDateStr, toDateStr] range
                    if ($dayDate < $fromDateStr || $dayDate > $toDateStr) {
                        continue;
                    }

                    $lateStatus = trim((string) ($dayAtt['lateApprovalStatus'] ?? ''));
                    $lateStatusLower = strtolower($lateStatus);
                    $firstLog = trim((string) ($dayAtt['firstLoggedTime'] ?? '-'));
                    $isPres = (!empty($dayAtt['isPresent']) && $dayAtt['isPresent'] !== 'false') || ($firstLog !== '' && $firstLog !== '-');
                    $isLt = $isPres && !empty($dayAtt['isLate']) && $dayAtt['isLate'] !== 'false';

                    if ($lateStatusLower === 'accepted' || $lateStatusLower === 'approved') {
                        $dailyApprovedCount++;
                    } elseif ($isLt) {
                        $unapprovedLateDates[] = $dayDate;
                    }

                    $normalizedDays[$dayDate] = [
                        'tsKey' => (string) $tsKey,
                        'dayAtt' => $dayAtt
                    ];
                }

                // If Tipsoi summary has more approved lates than flagged on daily items, mark the earliest late days as Approved
                if ($dailyApprovedCount === 0 && $summApprovedLates > 0 && !empty($unapprovedLateDates)) {
                    $toApprove = min($summApprovedLates, count($unapprovedLateDates));
                    for ($i = 0; $i < $toApprove; $i++) {
                        $dKey = $unapprovedLateDates[$i];
                        $normalizedDays[$dKey]['dayAtt']['lateApprovalStatus'] = 'Approved';
                    }
                }

                foreach ($normalizedDays as $dayDate => $dayEntry) {
                    $tsKey = $dayEntry['tsKey'];
                    $dayAtt = $dayEntry['dayAtt'];

                    $dateLabel = trim((string) ($dayAtt['date'] ?? ''));
                    $firstLog = trim((string) ($dayAtt['firstLoggedTime'] ?? '-'));
                    $lastLog = trim((string) ($dayAtt['lastLoggedTime'] ?? '-'));
                    $totalHour = trim((string) ($dayAtt['totalHour'] ?? '-'));

                    $isPresent = ((!empty($dayAtt['isPresent']) && $dayAtt['isPresent'] !== 'false') || ($firstLog !== '' && $firstLog !== '-')) ? 1 : 0;
                    $isLeave = (!empty($dayAtt['isLeave']) && $dayAtt['isLeave'] !== 'false') ? 1 : 0;
                    $isHalfDay = (!empty($dayAtt['isHalfDay']) && $dayAtt['isHalfDay'] !== 'false') ? 1 : 0;
                    $isOffday = (!empty($dayAtt['isOffday']) && $dayAtt['isOffday'] !== 'false') ? 1 : 0;
                    $isHoliday = (!empty($dayAtt['isHoliday']) && $dayAtt['isHoliday'] !== 'false') ? 1 : 0;
                    $isLate = ($isPresent && !empty($dayAtt['isLate']) && $dayAtt['isLate'] !== 'false') ? 1 : 0;
                    $isEarly = (!empty($dayAtt['isEarly']) && $dayAtt['isEarly'] !== 'false') ? 1 : 0;
                    $isAbsent = (!$isPresent && !$isLeave && !$isOffday && !$isHoliday) ? 1 : 0;

                    $lateBy = trim((string) ($dayAtt['lateBy'] ?? ''));
                    $lateApprovalStatus = trim((string) ($dayAtt['lateApprovalStatus'] ?? ''));
                    $rawJson = json_encode($dayAtt, JSON_UNESCAPED_UNICODE);

                    $stmt->bind_param(
                        "issssssssssssssssiiiissiiiis",
                        $localEmpId,
                        $normOfficeId,
                        $tipsoiEmpId,
                        $empName,
                        $deptName,
                        $desgName,
                        $workplaceName,
                        $shiftStart,
                        $shiftEnd,
                        $shiftGroup,
                        $dateOfJoin,
                        $dayDate,
                        $tsKey,
                        $dateLabel,
                        $firstLog,
                        $lastLog,
                        $totalHour,
                        $isPresent,
                        $isAbsent,
                        $isLate,
                        $isEarly,
                        $lateBy,
                        $lateApprovalStatus,
                        $isLeave,
                        $isHalfDay,
                        $isOffday,
                        $isHoliday,
                        $rawJson
                    );

                    if ($stmt->execute()) {
                        $savedCount++;
                        $dateCounts[$dayDate] = ($dateCounts[$dayDate] ?? 0) + 1;
                    }
                }
            }

            $stmt->close();

            // Record sync log per date
            if (!empty($dateCounts)) {
                $logStmt = $this->db->prepare("
                    INSERT INTO `attendance_sync_logs` (`sync_date`, `records_count`, `last_synced_at`)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        `records_count` = VALUES(`records_count`),
                        `last_synced_at` = NOW()
                ");
                if ($logStmt) {
                    foreach ($dateCounts as $sDate => $cnt) {
                        $logStmt->bind_param("si", $sDate, $cnt);
                        $logStmt->execute();
                    }
                    $logStmt->close();
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Error saving Tipsoi attendance to table: " . $e->getMessage());
        }

        return $savedCount;
    }

    /**
     * Build Tipsoi-compatible attendance array strictly from ERP `attendances` table for [fromDateStr, toDateStr]
     */
    protected function buildAttendancePayloadFromTable(string $fromDateStr, string $toDateStr): array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM `attendances`
            WHERE `attendance_date` BETWEEN ? AND ?
            ORDER BY CAST(`office_id` AS UNSIGNED) ASC, `office_id` ASC, `attendance_date` ASC
        ");
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param("ss", $fromDateStr, $toDateStr);
        $stmt->execute();
        $res = $stmt->get_result();

        $grouped = [];
        while ($row = $res->fetch_assoc()) {
            $oid = $row['office_id'];
            if (!isset($grouped[$oid])) {
                $grouped[$oid] = [
                    'meta' => $row,
                    'days' => []
                ];
            }
            $grouped[$oid]['days'][$row['attendance_date']] = $row;
        }
        $stmt->close();

        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        // Map employee joining_date & resign_date so pre-joining and post-resignation days are never counted as absent
        $empServiceBoundsById = [];
        $empServiceBoundsByOfficeId = [];
        $empBoundsRes = $this->db->query("SELECT id, employee_id, joining_date, resign_date FROM employees WHERE deleted_at IS NULL");
        if ($empBoundsRes) {
            while ($eb = $empBoundsRes->fetch_assoc()) {
                $eid = (int) $eb['id'];
                $jDate = trim((string) ($eb['joining_date'] ?? ''));
                $rDate = trim((string) ($eb['resign_date'] ?? ''));
                $jClean = ($jDate !== '' && $jDate !== '0000-00-00' && preg_match('/^\d{4}-\d{2}-\d{2}/', $jDate)) ? substr($jDate, 0, 10) : '';
                $rClean = ($rDate !== '' && $rDate !== '0000-00-00' && preg_match('/^\d{4}-\d{2}-\d{2}/', $rDate)) ? substr($rDate, 0, 10) : '';
                $bounds = ['id' => $eid, 'join' => $jClean, 'resign' => $rClean];
                $empServiceBoundsById[$eid] = $bounds;
                $normOid = $this->normalizeOfficeId($eb['employee_id'] ?? '');
                if ($normOid !== '') {
                    $empServiceBoundsByOfficeId[$normOid] = $bounds;
                }
            }
        }

        $result = [];
        foreach ($grouped as $oid => $empBundle) {
            $meta = $empBundle['meta'];
            $days = $empBundle['days'];

            $metaEmpId = !empty($meta['employee_id']) ? (int) $meta['employee_id'] : 0;
            $normMetaOid = $this->normalizeOfficeId((string) $oid);
            $empBounds = ($metaEmpId > 0 && isset($empServiceBoundsById[$metaEmpId]))
                ? $empServiceBoundsById[$metaEmpId]
                : ($empServiceBoundsByOfficeId[$normMetaOid] ?? null);

            $empJoinDate = $empBounds['join'] ?? '';
            $empResignDate = $empBounds['resign'] ?? '';
            $resolvedErpEmpId = $empBounds['id'] ?? $metaEmpId;

            $attMap = [];
            $totalPresent = 0;
            $totalAbsent = 0;
            $totalLeave = 0.0;
            $totalWeeklyOffdays = 0;
            $totalHolidays = 0;
            $totalLateEntry = 0;
            $approvedLates = 0;

            foreach ($days as $dayDate => $r) {
                // Exclude dates outside employee's active service period (resign_date is inclusive last working day)
                if ($empJoinDate !== '' && $dayDate < $empJoinDate) {
                    continue;
                }
                if ($empResignDate !== '' && $dayDate > $empResignDate) {
                    continue;
                }

                $dayAtt = !empty($r['raw_day_json']) ? json_decode($r['raw_day_json'], true) : [];
                if (!is_array($dayAtt)) {
                    $dayAtt = [];
                }

                $isPresent = (int) $r['is_present'] === 1;
                $isLeave = (int) $r['is_leave'] === 1;
                $isHalfDay = (int) $r['is_half_day'] === 1;
                $isOffday = (int) $r['is_offday'] === 1;
                $isHoliday = (int) $r['is_holiday'] === 1;
                $isLate = (int) $r['is_late'] === 1;
                $isEarly = (int) $r['is_early'] === 1;
                $isAbsent = (!$isPresent && !$isLeave && !$isOffday && !$isHoliday);

                $lateStatus = trim((string) ($r['late_approval_status'] ?? ''));
                $lateStatusLower = strtolower($lateStatus);
                $isLateApproved = ($lateStatusLower === 'accepted' || $lateStatusLower === 'approved');

                // Mutually exclusive daily bucket tally so Working Days + Day Off strictly equals period days
                if ($isLeave) {
                    if ($isHalfDay) {
                        $totalLeave += 0.5;
                        if ($isPresent) {
                            $totalPresent += 0.5;
                        } else {
                            $totalAbsent += 0.5;
                        }
                    } else {
                        $totalLeave += 1.0;
                    }
                } elseif ($isPresent) {
                    $totalPresent += 1;
                } elseif ($isOffday) {
                    $totalWeeklyOffdays += 1;
                } elseif ($isHoliday) {
                    $totalHolidays += 1;
                } else {
                    $totalAbsent += 1;
                }

                if ($isLateApproved) {
                    $approvedLates += 1;
                } elseif ($isPresent && $isLate) {
                    $totalLateEntry += 1;
                }

                // Populate normalized fields on dayAtt
                $dayAtt['date'] = !empty($r['date_label']) ? $r['date_label'] : date('d.m.Y (D)', strtotime($dayDate));
                $dayAtt['attendanceDate'] = $dayDate;
                $dayAtt['firstLoggedTime'] = !empty($r['first_logged_time']) ? $r['first_logged_time'] : '-';
                $dayAtt['lastLoggedTime'] = !empty($r['last_logged_time']) ? $r['last_logged_time'] : '-';
                $dayAtt['totalHour'] = !empty($r['total_hour']) ? $r['total_hour'] : '-';
                $dayAtt['isPresent'] = $isPresent;
                $dayAtt['isAbsent'] = $isAbsent;
                $dayAtt['isLate'] = $isLate;
                $dayAtt['isEarly'] = $isEarly;
                $dayAtt['lateBy'] = $r['late_by'] ?? '';
                $dayAtt['lateApprovalStatus'] = $lateStatus;
                $dayAtt['isLeave'] = $isLeave;
                $dayAtt['isHalfDay'] = $isHalfDay;
                $dayAtt['isOffday'] = $isOffday;
                $dayAtt['isHoliday'] = $isHoliday;

                // Use canonical timestamp for the date so keys are 1-to-1 with calendar dates
                $canonicalTs = (string) (strtotime($dayDate . ' 00:00:00') * 1000);
                $attMap[$canonicalTs] = $dayAtt;
            }

            $result[] = [
                'erpEmployeeId' => $resolvedErpEmpId,
                'employeeId' => $meta['tipsoi_emp_id'] ?: $oid,
                'employeeOfficeId' => $oid,
                'employeeName' => $meta['employee_name'] ?? '',
                'departmentName' => $meta['department_name'] ?? '',
                'designationName' => $meta['designation_name'] ?? '',
                'workplaceName' => $meta['workplace_name'] ?? '',
                'shiftStartTime' => $meta['shift_start_time'] ?? '',
                'shiftEndTime' => $meta['shift_end_time'] ?? '',
                'shiftGroupName' => $meta['shift_group_name'] ?? '',
                'dateOfJoin' => $meta['date_of_join'] ?? '',
                'attendance' => $attMap,
                'summary' => [
                    'totalPresent' => $totalPresent,
                    'totalAbsent' => $totalAbsent,
                    'totalLeave' => $totalLeave,
                    'totalWeeklyOffdays' => $totalWeeklyOffdays,
                    'totalHolidays' => $totalHolidays,
                    'totalLateEntry' => $totalLateEntry,
                    'totalApprovedLateApplication' => $approvedLates * 2
                ]
            ];
        }

        date_default_timezone_set($oldTz);

        return $result;
    }
}
