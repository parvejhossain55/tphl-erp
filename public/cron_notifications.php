<?php
/**
 * TPHL ERP Scheduled Notifications Cron
 * -------------------------------------------------------------
 * 1. Fetches pending requests directly from Tipsoi API, maps the active 
 *    approver by name to their email, and alerts them of their pending count.
 * 2. Alerts employees who have absent logs in Tipsoi for the month.
 * -------------------------------------------------------------
 * Trigger URLs:
 * - Weekly: curl -s "https://your-domain.com/cron_notifications.php?token=TphlSecretCron2026@#"
 * - Monthly Cutoff (24th): curl -s "https://your-domain.com/cron_notifications.php?token=TphlSecretCron2026@#"
 * - Testing: https://your-domain.com/cron_notifications.php?token=TphlSecretCron2026@#&test_email=naymtphl@gmail.com
 */

// Prevent browser and process timeouts
set_time_limit(0);
ignore_user_abort(true);

// Enable error logging but suppress browser output unless debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . "/../app/config/config.php";
$database = new Database($config);
$db = $database->conn;

// Automatically create the email_queue table if it doesn't exist (e.g., in local development)
$db->query("
    CREATE TABLE IF NOT EXISTS email_queue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        to_email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        body LONGTEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Simple Token-based security check
$token = $_GET['token'] ?? (isset($argv[1]) ? $argv[1] : '');
$secretToken = "TphlSecretCron2026";
if ($token !== $secretToken) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access Denied: Invalid Security Token.']);
    exit;
}

// Check for test email parameter
$testEmail = $_GET['test_email'] ?? '';
$isTestMode = !empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL);

$emailsQueued = 0;
$debugLogs = [];

// Fetch token from ExternalApiService
require_once __DIR__ . '/../app/services/ExternalApiService.php';
$apiService = new ExternalApiService($db);
$apiToken = $apiService->getBearerToken();

if ($apiToken) {
    // -------------------------------------------------------------
    // A. FETCH EMPLOYEES & BUILD NAME-TO-EMAIL MAP
    // -------------------------------------------------------------
    $tipsoiEmailMap = [];
    $tipsoiIdToOfficeIdMap = [];
    
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=2000&status=1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $resEmp = curl_exec($ch);
    curl_close($ch);
    
    $dEmp = json_decode($resEmp, true);
    $externalEmployees = $dEmp['data'] ?? $dEmp['employees'] ?? $dEmp['content'] ?? $dEmp ?? [];
    if (is_array($externalEmployees)) {
        foreach ($externalEmployees as $emp) {
            $nameKey = strtolower(trim($emp['employeeName'] ?? $emp['name'] ?? ''));
            $emailVal = trim($emp['employeeEmail'] ?? $emp['email'] ?? '');
            if ($nameKey !== '' && $emailVal !== '') {
                $tipsoiEmailMap[$nameKey] = $emailVal;
            }
            
            $tid = $emp['employeeId'] ?? $emp['id'] ?? null;
            $oid = $emp['employeeOfficeId'] ?? $emp['officeId'] ?? null;
            if ($tid !== null && $oid !== null) {
                $tipsoiIdToOfficeIdMap[$tid] = $oid;
            }
        }
    }

    // Build fallback email maps from local ERP tables
    $localEmailMap = [];
    $localQuery = $db->query("SELECT email, full_name FROM employees WHERE email IS NOT NULL AND email != ''");
    if ($localQuery) {
        while ($r = $localQuery->fetch_assoc()) {
            $localEmailMap[strtolower(trim($r['full_name']))] = trim($r['email']);
        }
    }
    $userQuery = $db->query("SELECT email, full_name FROM users WHERE email IS NOT NULL AND email != ''");
    if ($userQuery) {
        while ($r = $userQuery->fetch_assoc()) {
            $localEmailMap[strtolower(trim($r['full_name']))] = trim($r['email']);
        }
    }

    // Helper to resolve email by name
    $getEmailByName = function($name) use ($tipsoiEmailMap, $localEmailMap) {
        $key = strtolower(trim($name));
        if ($key === '') return '';
        return $tipsoiEmailMap[$key] ?? $localEmailMap[$key] ?? '';
    };

    // -------------------------------------------------------------
    // B. FETCH ALL PENDING REQUESTS FROM TIPSOI API
    // -------------------------------------------------------------
    $empId = 298780; // Main admin/approver ID on Tipsoi
    $officeId = 4699;
    $pendingRequests = [];

    // 1. Fetch Global Pending Leaves
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/leave-management/leave-history?page=0&count=100&status=0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $globalLeaves = $d['leaveLogList'] ?? [];

    // 2. Fetch Approver's Pending Leaves
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/remaining-leave/approvers/$empId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $approverLeaves = $d['leaveApplications'] ?? [];

    $allLeaves = array_merge($globalLeaves, $approverLeaves);
    foreach ($allLeaves as $log) {
        $id = $log['id'] ?? $log['leaveLogId'] ?? null;
        if ($id) {
            $pendingRequests["leave_$id"] = [
                'type' => 'Leave Request',
                'employeeName' => $log['employeeName'] ?? '',
                'firstApproverName' => $log['firstApproverName'] ?? '',
                'firstApprovalStatus' => $log['firstApprovalStatus'] ?? 0,
                'secondApproverName' => $log['secondApproverName'] ?? '',
                'secondApprovalStatus' => $log['secondApprovalStatus'] ?? 0
            ];
        }
    }

    // 3. Fetch Approver's Pending Late Excuses
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/late-approval-management/late-approvals/$empId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $lateApprovals = $d['lateLogList'] ?? [];
    foreach ($lateApprovals as $log) {
        $id = $log['lateApprovalLogId'] ?? $log['id'] ?? null;
        if ($id) {
            $pendingRequests["late_$id"] = [
                'type' => 'Late Excuse',
                'employeeName' => $log['employeeName'] ?? '',
                'firstApproverName' => $log['firstLateApproverName'] ?? '',
                'firstApprovalStatus' => $log['firstLateApprovalStatus'] ?? 0,
                'secondApproverName' => $log['secondLateApproverName'] ?? '',
                'secondApprovalStatus' => $log['secondLateApprovalStatus'] ?? 0
            ];
        }
    }

    // 4. Fetch Manual Entry Approver
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/employee/profile/manual/approver/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $manualApprover = $d['manualEntryApplications'] ?? [];

    // 5. Fetch Global Pending Corrections (Type 1)
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=1&applicationStatus=0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $globalCorrection = $d['manualEntries'] ?? [];

    // 6. Fetch Global Pending Late Excuses (Type 2)
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=2&applicationStatus=0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $globalLate = $d['manualEntries'] ?? [];

    // 7. Fetch Global Pending Field Visits (Type 3)
    $ch = curl_init("https://app.tipsoi.ai/inovace-client/api/v1/manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=200&sortKey=reportPosition&manualEntryType=3&applicationStatus=0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiToken, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $globalField = $d['manualEntries'] ?? [];

    $allManual = array_merge($manualApprover, $globalCorrection, $globalLate, $globalField);
    foreach ($allManual as $entry) {
        $id = $entry['manualEntryId'] ?? $entry['id'] ?? null;
        if ($id) {
            $pendingRequests["manual_$id"] = [
                'type' => 'Manual Correction',
                'employeeName' => $entry['employeeName'] ?? '',
                'firstApproverName' => $entry['firstManualApproverName'] ?? '',
                'firstApprovalStatus' => $entry['firstManualEntryApprovalStatus'] ?? 0,
                'secondApproverName' => $entry['secondManualApproverName'] ?? '',
                'secondApprovalStatus' => $entry['secondManualEntryApprovalStatus'] ?? 0
            ];
        }
    }

    // -------------------------------------------------------------
    // C. PROCESS PENDING APPROVAL COUNTS & QUEUE EMAILS
    // -------------------------------------------------------------
    $approverCounts = [];

    foreach ($pendingRequests as $req) {
        $approverNames = [];
        
        // 1st approval is pending
        if ((int)($req['firstApprovalStatus'] ?? 0) === 0 && !empty($req['firstApproverName'])) {
            $approverNames[] = trim($req['firstApproverName']);
        }
        
        // 2nd approval is pending
        if ((int)($req['secondApprovalStatus'] ?? 0) === 0 && !empty($req['secondApproverName'])) {
            $approverNames[] = trim($req['secondApproverName']);
        }

        // Deduplicate in case names are identical
        $approverNames = array_unique($approverNames);

        foreach ($approverNames as $approverName) {
            $approverEmail = '';
            if ($approverName !== '') {
                $approverEmail = $getEmailByName($approverName);
            }

            // Fallback if no specific approver mapped
            if (empty($approverEmail)) {
                $approverEmail = $config['tipsoi_email'] ?? 'info@dpremiumhomes.com';
                $approverName = 'HR Manager / Administrator';
            }

            $emailKey = strtolower(trim($approverEmail));
            if (!isset($approverCounts[$emailKey])) {
                $approverCounts[$emailKey] = [
                    'name' => $approverName,
                    'count' => 0
                ];
            }
            $approverCounts[$emailKey]['count']++;
        }
    }

    // Send emails to resolved active approvers
    foreach ($approverCounts as $email => $data) {
        $name = $data['name'];
        $count = $data['count'];

        $targetEmail = $isTestMode ? $testEmail : $email;
        $subject = ($isTestMode ? "[TEST] " : "") . "⚠️ Action Required: You have {$count} pending Tipsoi requests";
        
        $body = "
        <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #ffffff;'>
            <div style='background-color: #dc3545; height: 6px; border-radius: 4px 4px 0 0; margin-bottom: 20px;'></div>
            " . ($isTestMode ? "<div style='background-color: #e2e8f0; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 13px;'><strong>Test Mode:</strong> Originally intended for Approver: <strong>{$name}</strong> ({$email})</div>" : "") . "
            <h2 style='color: #2d3748; margin-top: 0; font-size: 20px; font-weight: bold;'>Pending Approvals reminder</h2>
            <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>Dear {$name},</p>
            <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>This is a scheduled reminder that you have <strong>{$count} pending Tipsoi request(s)</strong> (Leaves, Late Excuses, or Manual Corrections) currently awaiting your approval.</p>
            <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>Please open your <strong>Tipsoi Mobile App / Portal</strong> to review, approve, or disburse these pending requests.</p>
            
            <div style='text-align: center; margin: 30px 0;'>
                <span style='background-color: #198754; color: #ffffff; padding: 12px 28px; border-radius: 5px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>Please check Tipsoi App</span>
            </div>
            
            <hr style='border: 0; border-top: 1px solid #edf2f7; margin: 25px 0;'>
            <p style='color: #a0aec0; font-size: 11px; text-align: center; margin: 0;'>This is an automated system notification. Please do not reply directly to this email.</p>
        </div>";

        // Insert into email_queue
        $stmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
        if ($stmt) {
            $stmt->bind_param("sss", $targetEmail, $subject, $body);
            $stmt->execute();
            $stmt->close();
            $emailsQueued++;
            
            $debugLogs[] = [
                'type' => 'Approver Notification',
                'original_approver' => $name,
                'original_email' => $email,
                'sent_to' => $targetEmail,
                'subject' => $subject,
                'count' => $count
            ];
        }
    }

    // -------------------------------------------------------------
    // D. ABSENTEE NOTIFICATIONS (From Tipsoi Attendance API)
    // -------------------------------------------------------------
    // Calculate attendance cycle: 26th of previous month to 25th of current month
    $todayDay = (int)date('d');
    if ($todayDay >= 26) {
        $firstDay = date('Y-m-26');
        $lastDay = date('Y-m-25', strtotime('+1 month'));
    } else {
        $firstDay = date('Y-m-26', strtotime('-1 month'));
        $lastDay = date('Y-m-25');
    }

    $oldTimezone = date_default_timezone_get();
    date_default_timezone_set('Asia/Dhaka');
    $fromTime = strtotime($firstDay . ' 00:00:00') * 1000;
    $toTime = strtotime($lastDay . ' 23:59:59') * 1000;
    date_default_timezone_set($oldTimezone);

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
        'Authorization: Bearer ' . $apiToken,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $res = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($res, true);
    $attendance = $data['attendance'] ?? [];

    foreach ($attendance as $emp) {
        $summ = $emp['summary'] ?? [];
        $absent = (int)($summ['totalAbsent'] ?? 0);
        $empEmail = trim($emp['employeeEmail'] ?? $emp['email'] ?? '');
        $empName = trim($emp['employeeName'] ?? '');

        if ($absent > 0 && !empty($empEmail) && filter_var($empEmail, FILTER_VALIDATE_EMAIL)) {
            
            $targetEmail = $isTestMode ? $testEmail : $empEmail;
            $subject = ($isTestMode ? "[TEST] " : "") . "⚠️ Alert: You have {$absent} Absent Days marked this month";
            
            $body = "
            <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #ffffff;'>
                <div style='background-color: #fd7e14; height: 6px; border-radius: 4px 4px 0 0; margin-bottom: 20px;'></div>
                " . ($isTestMode ? "<div style='background-color: #e2e8f0; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 13px;'><strong>Test Mode:</strong> Originally intended for Employee: <strong>{$empName}</strong> ({$empEmail})</div>" : "") . "
                <h2 style='color: #2d3748; margin-top: 0; font-size: 20px; font-weight: bold;'>Attendance Alert: Absent Days</h2>
                <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>Dear {$empName},</p>
                <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>This is a scheduled notification regarding your attendance records for the cycle <strong>" . date('M 26', strtotime($firstDay)) . " to " . date('M 25, Y', strtotime($lastDay)) . "</strong>.</p>
                <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>According to Tipsoi attendance logs, you have been marked <strong>Absent for {$absent} day(s)</strong>.</p>
                <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>Please open the <strong>Tipsoi Mobile App</strong> and review your daily attendance. If you were on approved leave or out of office, make sure to submit a Leave or Manual Entry request in the app to reconcile this.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='background-color: #fd7e14; color: #ffffff; padding: 12px 28px; border-radius: 5px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>Please check your Tipsoi Mobile App</span>
                </div>
                
                <hr style='border: 0; border-top: 1px solid #edf2f7; margin: 25px 0;'>
                <p style='color: #a0aec0; font-size: 11px; text-align: center; margin: 0;'>This is an automated system notification. Please do not reply directly to this email.</p>
            </div>";

            // Insert into email_queue
            $stmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
            if ($stmt) {
                $stmt->bind_param("sss", $targetEmail, $subject, $body);
                $stmt->execute();
                $stmt->close();
                $emailsQueued++;

                $debugLogs[] = [
                    'type' => 'Absentee Alert',
                    'original_employee' => $empName,
                    'original_email' => $empEmail,
                    'sent_to' => $targetEmail,
                    'subject' => $subject,
                    'absent_days' => $absent
                ];
            }
        }
    }

    // -------------------------------------------------------------
    // E. EMPLOYEE UNAPPROVED REQUEST REMINDERS (RUNS ON 24TH OF THE MONTH)
    // -------------------------------------------------------------
    $isMonthlyCutoff = (date('d') === '24') || isset($_GET['force_cutoff']) || $isTestMode;
    $employeePendingCounts = [];

    if ($isMonthlyCutoff) {
        foreach ($pendingRequests as $req) {
            $empName = trim($req['employeeName'] ?? '');
            if ($empName === '') continue;

            $empEmail = $getEmailByName($empName);
            if (empty($empEmail)) continue;

            $emailKey = strtolower(trim($empEmail));
            if (!isset($employeePendingCounts[$emailKey])) {
                $employeePendingCounts[$emailKey] = [
                    'name' => $empName,
                    'count' => 0
                ];
            }
            $employeePendingCounts[$emailKey]['count']++;
        }

        // Send reminders to employees about their unapproved requests
        foreach ($employeePendingCounts as $email => $data) {
            $name = $data['name'];
            $count = $data['count'];

            $targetEmail = $isTestMode ? $testEmail : $email;
            $subject = ($isTestMode ? "[TEST] " : "") . "⚠️ Reminder: You have {$count} unapproved Tipsoi requests";

            $body = "
            <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #ffffff;'>
                <div style='background-color: #fd7e14; height: 6px; border-radius: 4px 4px 0 0; margin-bottom: 20px;'></div>
                " . ($isTestMode ? "<div style='background-color: #e2e8f0; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 13px;'><strong>Test Mode:</strong> Originally intended for Employee: <strong>{$name}</strong> ({$email})</div>" : "") . "
                <h2 style='color: #2d3748; margin-top: 0; font-size: 20px; font-weight: bold;'>Unapproved Requests Reminder</h2>
                <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>Dear {$name},</p>
                <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>This is a scheduled reminder that you currently have <strong>{$count} request(s)</strong> (Leaves, Late Excuses, or Manual Corrections) that are still **Pending Approval**.</p>
                <p style='color: #4a5568; font-size: 15px; line-height: 1.6;'>Please check your requests in the <strong>Tipsoi Mobile App / Portal</strong> and follow up with your supervisors or HR to get them approved before the monthly payroll cycle closes.</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <span style='background-color: #fd7e14; color: #ffffff; padding: 12px 28px; border-radius: 5px; font-weight: bold; font-size: 15px; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.1);'>Open Tipsoi Mobile App</span>
                </div>
                
                <hr style='border: 0; border-top: 1px solid #edf2f7; margin: 25px 0;'>
                <p style='color: #a0aec0; font-size: 11px; text-align: center; margin: 0;'>This is an automated system notification. Please do not reply directly to this email.</p>
            </div>";

            // Insert into email_queue
            $stmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body, status) VALUES (?, ?, ?, 'pending')");
            if ($stmt) {
                $stmt->bind_param("sss", $targetEmail, $subject, $body);
                $stmt->execute();
                $stmt->close();
                $emailsQueued++;

                $debugLogs[] = [
                    'type' => 'Employee Unapproved Request Reminder',
                    'original_employee' => $name,
                    'original_email' => $email,
                    'sent_to' => $targetEmail,
                    'subject' => $subject,
                    'unapproved_count' => $count
                ];
            }
        }
    }
}

// F. TRIGGER QUEUE PROCESSOR
if ($emailsQueued > 0) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base   = rtrim($GLOBALS['base_url'] ?? '/TPHL-ERP', '/');
    $processUrl = $scheme . '://' . $host . $base . '/process_email_queue.php';

    $ch = curl_init($processUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, 500);
    curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    ajax_execute_without_waiting($ch);
}

function ajax_execute_without_waiting($ch) {
    curl_exec($ch);
    curl_close($ch);
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Cron execution completed. Queued {$emailsQueued} notifications in email_queue.",
    'test_mode' => $isTestMode,
    'debug_logs' => $debugLogs
], JSON_PRETTY_PRINT);
exit;
