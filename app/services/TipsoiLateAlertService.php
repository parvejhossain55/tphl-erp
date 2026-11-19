<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class TipsoiLateAlertService
{
    private $db;
    private $config;
    private $baseUrl;
    private $apiEmail;
    private $apiPassword;

    public function __construct($db = null)
    {
        $this->db = $db ?: db();
        $this->config = require dirname(__DIR__) . '/config/config.php';

        $this->baseUrl = rtrim($this->config['tipsoi_base_url'] ?? 'https://app.tipsoi.ai/inovace-client/api/v1', '/') . '/';
        $this->apiEmail = $this->config['tipsoi_email'] ?? '';
        $this->apiPassword = $this->config['tipsoi_password'] ?? '';

        $this->ensureLateEmailLogsTable();
    }

    /**
     * Ensure dedicated database table for late email logs exists
     */
    public function ensureLateEmailLogsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tipsoi_late_email_logs` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `employee_id` INT NULL,
            `employee_office_id` VARCHAR(50) NOT NULL,
            `employee_name` VARCHAR(150) NOT NULL,
            `employee_email` VARCHAR(150) NOT NULL,
            `attendance_date` DATE NOT NULL,
            `shift_start_time` VARCHAR(30) NULL,
            `first_logged_time` VARCHAR(30) NULL,
            `late_by` VARCHAR(50) NULL,
            `late_minutes` INT NOT NULL DEFAULT 0,
            `trigger_source` VARCHAR(50) NOT NULL DEFAULT 'cron',
            `status` ENUM('Success', 'Failed') NOT NULL DEFAULT 'Success',
            `error_message` TEXT NULL,
            `sent_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_late_emp_date` (`employee_office_id`, `attendance_date`),
            INDEX `idx_date` (`attendance_date`),
            INDEX `idx_emp_id` (`employee_office_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $this->db->query($sql);
    }

    /**
     * Authenticate and get Bearer Token from Tipsoi API
     */
    public function getBearerToken(): ?string
    {
        if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
            session_start();
        }

        if (
            !empty($_SESSION['tipsoi_api_token']) &&
            !empty($_SESSION['tipsoi_api_token_expires']) &&
            $_SESSION['tipsoi_api_token_expires'] > time()
        ) {
            return $_SESSION['tipsoi_api_token'];
        }

        $loginUrl = $this->baseUrl . 'auth/external-sync/sign-in';
        $payload = json_encode([
            'email' => $this->apiEmail,
            'password' => $this->apiPassword
        ]);

        $ch = curl_init($loginUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || ($httpCode !== 200 && $httpCode !== 201)) {
            error_log("TipsoiLateAlertService Login Failed: HTTP $httpCode, error: $curlError");
            return null;
        }

        $data = json_decode($response, true);
        $token = $data['token'] ?? $data['access_token'] ?? $data['data']['token'] ?? null;

        if ($token) {
            $_SESSION['tipsoi_api_token'] = $token;
            $_SESSION['tipsoi_api_token_expires'] = time() + 3300;
            return $token;
        }

        return null;
    }

    /**
     * Parse lateBy string (e.g. "00:32" or "01:15:20") into readable text and total minutes
     */
    public function parseLateDuration(?string $lateBy): array
    {
        $raw = trim((string)$lateBy);
        if (empty($raw) || $raw === '-') {
            return ['minutes' => 0, 'formatted' => 'Late'];
        }

        $parts = explode(':', $raw);
        $hours = 0;
        $minutes = 0;

        if (count($parts) >= 2) {
            $hours = (int)$parts[0];
            $minutes = (int)$parts[1];
        } else {
            $minutes = (int)$raw;
        }

        $totalMinutes = ($hours * 60) + $minutes;

        if ($hours > 0) {
            $formatted = $hours . ' hr ' . ($minutes > 0 ? $minutes . ' min' : '');
        } else {
            $formatted = $minutes . ' minutes';
        }

        return [
            'minutes' => $totalMinutes,
            'formatted' => trim($formatted)
        ];
    }

    /**
     * Fetch attendance from Tipsoi API for a date and detect all late employees
     * @param string $targetDate Y-m-d format (e.g. '2026-11-19')
     */
    public function detectTodayLateEmployees(string $targetDate): array
    {
        $token = $this->getBearerToken();
        if (!$token) {
            return ['success' => false, 'message' => 'Failed to obtain Tipsoi bearer token.', 'data' => []];
        }

        // Use Asia/Dhaka time bounds with 6-hour cushion to cover UTC clock skew safely
        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');

        $dayStartMs = strtotime($targetDate . ' 00:00:00') * 1000;
        $dayEndMs = strtotime($targetDate . ' 23:59:59') * 1000;
        $targetFormatted = date('d.m.Y', strtotime($targetDate));

        date_default_timezone_set($oldTz);

        $url = $this->baseUrl . "attendance?" . http_build_query([
            'officeId' => 4699,
            'order' => 'asc',
            'pageNumber' => 0,
            'perPage' => 2000,
            'reportType' => 'department',
            'sortKey' => 'reportPosition',
            'status' => 1,
            'from' => $dayStartMs - (6 * 3600 * 1000),
            'to' => $dayEndMs + (6 * 3600 * 1000)
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError || $httpCode !== 200 || !$response) {
            return [
                'success' => false,
                'message' => "Tipsoi API error (HTTP $httpCode): " . ($curlError ?: 'Empty response'),
                'data' => []
            ];
        }

        $decoded = json_decode($response, true);
        $attendanceList = $decoded['attendance'] ?? [];

        // Build lookup map of ERP employees by office ID
        $erpEmployees = [];
        $empRes = $this->db->query("
            SELECT e.id, e.employee_id, e.full_name, e.email, d.name AS department_name, des.name AS designation_name
            FROM employees e
            LEFT JOIN departments d ON e.department_id = d.id
            LEFT JOIN designations des ON e.designation_id = des.id
            WHERE e.deleted_at IS NULL
        ");
        if ($empRes) {
            while ($row = $empRes->fetch_assoc()) {
                $rawOid = trim((string)$row['employee_id']);
                $cleanOid = preg_match('/^\d+$/', $rawOid) ? (string)((int)$rawOid) : $rawOid;
                if ($cleanOid !== '') {
                    $erpEmployees[$cleanOid] = $row;
                }
            }
        }

        $lateList = [];

        foreach ($attendanceList as $emp) {
            $rawOfficeId = trim((string)($emp['employeeOfficeId'] ?? $emp['employeeId'] ?? ''));
            $cleanOfficeId = preg_match('/^\d+$/', $rawOfficeId) ? (string)((int)$rawOfficeId) : $rawOfficeId;
            if ($cleanOfficeId === '') {
                continue;
            }

            $empName = trim((string)($emp['employeeName'] ?? ''));
            $shiftStartTime = trim((string)($emp['shiftStartTime'] ?? '09:30 AM'));

            $dayAttendanceMap = $emp['attendance'] ?? [];
            foreach ($dayAttendanceMap as $tsKey => $day) {
                $dateLabel = $day['date'] ?? '';
                // Match today's date formatted (e.g. "19.11.2026")
                if (strpos($dateLabel, $targetFormatted) === false) {
                    continue;
                }

                $firstLoggedTime = trim((string)($day['firstLoggedTime'] ?? '-'));
                $isPresent = (!empty($day['isPresent']) && $day['isPresent'] !== 'false') || ($firstLoggedTime !== '' && $firstLoggedTime !== '-');
                $isLate = $isPresent && (!empty($day['isLate']) && $day['isLate'] !== 'false');

                if ($isLate) {
                    $lateByRaw = trim((string)($day['lateBy'] ?? ''));
                    $parsedLate = $this->parseLateDuration($lateByRaw);

                    $erpEmp = $erpEmployees[$cleanOfficeId] ?? null;
                    $employeeEmail = trim((string)($erpEmp['email'] ?? ''));

                    $lateList[] = [
                        'employee_office_id' => $cleanOfficeId,
                        'employee_name'      => $erpEmp['full_name'] ?? $empName,
                        'erp_id'             => $erpEmp['id'] ?? null,
                        'email'              => $employeeEmail,
                        'department'         => $erpEmp['department_name'] ?? ($emp['departmentName'] ?? 'General'),
                        'designation'        => $erpEmp['designation_name'] ?? ($emp['designationName'] ?? 'Employee'),
                        'attendance_date'    => $targetDate,
                        'date_label'         => $dateLabel,
                        'shift_start_time'   => $shiftStartTime,
                        'first_logged_time'  => $firstLoggedTime,
                        'late_by'            => $lateByRaw,
                        'late_minutes'       => $parsedLate['minutes'],
                        'late_formatted'     => $parsedLate['formatted'],
                        'late_approval_status' => $day['lateApprovalStatus'] ?? ''
                    ];
                }
            }
        }

        return [
            'success' => true,
            'date'    => $targetDate,
            'count'   => count($lateList),
            'data'    => $lateList
        ];
    }

    /**
     * Check if email was already sent successfully for this employee and date
     */
    public function wasEmailAlreadySent(string $officeId, string $date): bool
    {
        $stmt = $this->db->prepare("
            SELECT id FROM tipsoi_late_email_logs 
            WHERE employee_office_id = ? AND attendance_date = ? AND status = 'Success' 
            LIMIT 1
        ");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param("ss", $officeId, $date);
        $stmt->execute();
        $res = $stmt->get_result();
        $exists = ($res && $res->num_rows > 0);
        $stmt->close();
        return $exists;
    }

    /**
     * Dispatch late notification email to a single employee
     */
    public function sendLateNotificationEmail(array $info, string $triggerSource = 'cron', ?string $overrideRecipient = null): array
    {
        $officeId = $info['employee_office_id'];
        $empName  = $info['employee_name'];
        $attDate  = $info['attendance_date'];
        $targetEmail = $overrideRecipient ?: $info['email'];

        if (empty($targetEmail) || !filter_var($targetEmail, FILTER_VALIDATE_EMAIL)) {
            $errMsg = "Missing or invalid email address for employee ID $officeId ($empName).";
            $this->logDispatch($info, 'Failed', $errMsg, $triggerSource, $targetEmail);
            return [
                'success' => false,
                'status'  => 'Skipped',
                'reason'  => $errMsg
            ];
        }

        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';

        $status = 'Failed';
        $errorMsg = null;

        try {
            $mail->isSMTP();
            $mail->Host       = $this->config['smtp_host'] ?? 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->config['smtp_user'] ?? '';
            $mail->Password   = $this->config['smtp_pass'] ?? '';
            $mail->SMTPSecure = (strtolower($this->config['smtp_secure'] ?? 'ssl') === 'ssl') ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int)($this->config['smtp_port'] ?? 465);
            $mail->Timeout    = 20;

            $fromAddress = !empty($this->config['smtp_from']) ? $this->config['smtp_from'] : ($this->config['smtp_user'] ?? 'no-reply@dpremiumhomes.com');
            $fromName    = 'The Premium Homes Ltd — Attendance Alert';

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($targetEmail, $empName);
            $mail->isHTML(true);

            $formattedDate = date('d F Y (l)', strtotime($attDate));
            $mail->Subject = "Late Arrival Notice: {$empName} ({$officeId}) - {$formattedDate}";

            $htmlBody = $this->buildLateEmailTemplate($info);
            $mail->Body    = $htmlBody;
            $mail->AltBody = strip_tags($htmlBody);

            $mail->send();
            $status = 'Success';
        } catch (Exception $e) {
            $status = 'Failed';
            $errorMsg = $mail->ErrorInfo ?: $e->getMessage();
        }

        // Insert / Update in log table
        $this->logDispatch($info, $status, $errorMsg, $triggerSource, $targetEmail);

        return [
            'success' => ($status === 'Success'),
            'status'  => $status,
            'office_id' => $officeId,
            'name'    => $empName,
            'email'   => $targetEmail,
            'late_by' => $info['late_formatted'] ?? $info['late_by'],
            'error'   => $errorMsg
        ];
    }

    /**
     * Log dispatch result into database
     */
    private function logDispatch(array $info, string $status, ?string $errorMsg, string $triggerSource, string $emailUsed): void
    {
        $erpId      = !empty($info['erp_id']) ? (int)$info['erp_id'] : null;
        $officeId   = (string)$info['employee_office_id'];
        $name       = (string)$info['employee_name'];
        $date       = (string)$info['attendance_date'];
        $shiftStart = (string)($info['shift_start_time'] ?? '');
        $firstLog   = (string)($info['first_logged_time'] ?? '');
        $lateBy     = (string)($info['late_by'] ?? '');
        $minutes    = (int)($info['late_minutes'] ?? 0);

        $stmt = $this->db->prepare("
            INSERT INTO tipsoi_late_email_logs (
                employee_id, employee_office_id, employee_name, employee_email,
                attendance_date, shift_start_time, first_logged_time, late_by, late_minutes,
                trigger_source, status, error_message, sent_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                error_message = VALUES(error_message),
                first_logged_time = VALUES(first_logged_time),
                late_by = VALUES(late_by),
                late_minutes = VALUES(late_minutes),
                sent_at = NOW()
        ");

        if ($stmt) {
            $stmt->bind_param(
                "isssssssisss",
                $erpId,
                $officeId,
                $name,
                $emailUsed,
                $date,
                $shiftStart,
                $firstLog,
                $lateBy,
                $minutes,
                $triggerSource,
                $status,
                $errorMsg
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Run the complete process for today: Detect latecomers & send emails immediately
     */
    public function processDailyLateAlerts(?string $targetDate = null, bool $dryRun = false, string $triggerSource = 'cron', ?string $testOverrideEmail = null): array
    {
        $oldTz = date_default_timezone_get();
        date_default_timezone_set('Asia/Dhaka');
        $date = $targetDate ?: date('Y-m-d');
        date_default_timezone_set($oldTz);

        $detection = $this->detectTodayLateEmployees($date);
        if (!$detection['success']) {
            return [
                'success' => false,
                'message' => $detection['message'],
                'date'    => $date,
                'dispatched' => [],
                'skipped'    => []
            ];
        }

        $allLate = $detection['data'];
        $dispatched = [];
        $skipped = [];

        foreach ($allLate as $item) {
            $officeId = $item['employee_office_id'];

            // Duplicate prevention: check if already sent today
            if (!$dryRun && empty($testOverrideEmail) && $this->wasEmailAlreadySent($officeId, $date)) {
                $skipped[] = [
                    'office_id' => $officeId,
                    'name'      => $item['employee_name'],
                    'email'     => $item['email'],
                    'reason'    => 'Already notified today (Duplicate prevented)'
                ];
                continue;
            }

            if (empty($item['email'])) {
                $skipped[] = [
                    'office_id' => $officeId,
                    'name'      => $item['employee_name'],
                    'email'     => 'None',
                    'reason'    => 'No email address registered in ERP employees table'
                ];
                continue;
            }

            if ($dryRun) {
                $dispatched[] = [
                    'office_id' => $officeId,
                    'name'      => $item['employee_name'],
                    'email'     => $testOverrideEmail ?: $item['email'],
                    'in_time'   => $item['first_logged_time'],
                    'late_by'   => $item['late_formatted'],
                    'status'    => 'Dry-Run (Not sent)'
                ];
                continue;
            }

            // Real email dispatch
            $res = $this->sendLateNotificationEmail($item, $triggerSource, $testOverrideEmail);
            $dispatched[] = $res;
        }

        return [
            'success'          => true,
            'date'             => $date,
            'total_late_today' => count($allLate),
            'dispatched_count' => count($dispatched),
            'skipped_count'    => count($skipped),
            'dispatched'       => $dispatched,
            'skipped'          => $skipped
        ];
    }

    /**
     * Professional responsive HTML email template for Late Arrival Notification
     */
    public function buildLateEmailTemplate(array $info): string
    {
        $name       = htmlspecialchars($info['employee_name']);
        $officeId   = htmlspecialchars($info['employee_office_id']);
        $dept       = htmlspecialchars($info['department'] ?? 'TPHL');
        $desg       = htmlspecialchars($info['designation'] ?? 'Employee');
        $shiftStart = htmlspecialchars($info['shift_start_time'] ?? '09:30 AM');
        $inTime     = htmlspecialchars($info['first_logged_time'] ?? '-');
        $lateText   = htmlspecialchars($info['late_formatted'] ?? $info['late_by']);
        $dateStr    = date('d F, Y (l)', strtotime($info['attendance_date']));

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Late Arrival Notification</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; }
        .wrapper { width: 100%; max-width: 600px; margin: 25px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06); border: 1px solid #e2e8f0; }
        .header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; padding: 30px 24px; text-align: center; }
        .logo-text { font-size: 20px; font-weight: 700; letter-spacing: 0.5px; color: #f8fafc; }
        .logo-sub { font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; margin-top: 4px; }
        .banner-badge { display: inline-block; background-color: #fee2e2; color: #b91c1c; font-size: 12px; font-weight: 700; padding: 6px 14px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.8px; margin-top: 15px; border: 1px solid #fca5a5; }
        .content { padding: 30px 28px; }
        .salutation { font-size: 16px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
        .lead-text { font-size: 14px; line-height: 1.6; color: #475569; margin-bottom: 24px; }
        .info-card { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 20px; margin-bottom: 24px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed #cbd5e1; font-size: 13.5px; }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-weight: 500; }
        .info-value { color: #0f172a; font-weight: 600; text-align: right; }
        .late-highlight { background-color: #fef2f2; border: 1px solid #fecaca; color: #dc2626; font-size: 15px; font-weight: 700; padding: 12px 16px; border-radius: 8px; text-align: center; margin: 20px 0; }
        .policy-box { background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 14px 16px; border-radius: 0 8px 8px 0; margin-bottom: 24px; font-size: 13px; line-height: 1.5; color: #78350f; }
        .footer { background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px; text-align: center; font-size: 11.5px; color: #94a3b8; }
        .btn-portal { display: inline-block; background-color: #0f172a; color: #ffffff !important; text-decoration: none; padding: 12px 24px; font-size: 13px; font-weight: 600; border-radius: 6px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo-text">THE PREMIUM HOMES LTD</div>
            <div class="logo-sub">Human Resources Management</div>
            <div class="banner-badge">⚠️ Late Attendance Notice / দেরিতে উপস্থিতি</div>
        </div>
        <div class="content">
            <div class="salutation">Dear {$name} (ID: {$officeId}),</div>
            <div class="lead-text">
                This is an automated attendance notification to inform you that your office arrival today was recorded as <strong>Late</strong> in our Tipsoi Biometric Attendance System.
            </div>

            <div class="info-card">
                <table width="100%" cellpadding="6" cellspacing="0" style="font-size: 13.5px;">
                    <tr>
                        <td style="color: #64748b; font-weight: 500;">Employee Name:</td>
                        <td style="color: #0f172a; font-weight: 600; text-align: right;">{$name}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; font-weight: 500;">Employee Office ID:</td>
                        <td style="color: #0f172a; font-weight: 600; text-align: right;">{$officeId}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; font-weight: 500;">Department & Role:</td>
                        <td style="color: #0f172a; font-weight: 600; text-align: right;">{$dept} • {$desg}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; font-weight: 500;">Attendance Date:</td>
                        <td style="color: #0f172a; font-weight: 600; text-align: right;">{$dateStr}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; font-weight: 500;">Shift Scheduled Start:</td>
                        <td style="color: #0f172a; font-weight: 600; text-align: right;">{$shiftStart}</td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; font-weight: 500;">Actual Punch-in Time:</td>
                        <td style="color: #b91c1c; font-weight: 700; text-align: right;">{$inTime}</td>
                    </tr>
                </table>
            </div>

            <div class="late-highlight">
                Late By: {$lateText}
            </div>

            <div class="policy-box">
                <strong>📌 Company Attendance Policy Reminder:</strong><br>
                অনুগ্রহ করে অবগত থাকুন যে, কোম্পানির এইচআর পলিসি অনুযায়ী মাসে <strong>প্রতি ৩টি অননুমোদিত দেরিতে উপস্থিতির (Unapproved Lates) জন্য ১ দিনের মূল বেতন কর্তন</strong> প্রযোজ্য।<br><br>
                আপনার দেরির কোনো যৌক্তিক বা অনুমোদিত কারণ থাকলে অতিসত্বর <strong>Tipsoi মোবাইল অ্যাপ</strong> থেকে <em>'Late Excuse'</em> আবেদন জমা দিয়ে আপনার লাইন ম্যানেজারের নিকট থেকে অনুমোদন করিয়ে নেওয়ার অনুরোধ করা হচ্ছে।
            </div>

            <div style="text-align: center; margin-top: 15px;">
                <a href="https://thepremiumhomesltd.com/TPHL-ERP/" class="btn-portal">Log in to ERP Portal</a>
            </div>
        </div>
        <div class="footer">
            This is an automated notification from TPHL ERP System.<br>
            Please do not reply directly to this no-reply email.<br>
            © 2026 The Premium Homes Ltd. All rights reserved.
        </div>
    </div>
</body>
</html>
HTML;
    }
}
