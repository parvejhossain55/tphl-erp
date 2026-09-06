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

        // Auto-sync joining_date for local employees based on Tipsoi startDate
        if (is_array($employees)) {
            $updateStmt = $this->db->prepare("UPDATE employees SET joining_date = ? WHERE employee_id = ?");
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

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
}
