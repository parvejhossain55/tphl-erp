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

// G. PROCESS TODO LIST TASK REMINDERS
require_once __DIR__ . '/../app/core/TodoMailService.php';
$todoMailService = new TodoMailService($db);

$todoRes = $db->query("
    SELECT r.id as reminder_id, r.max_reminders, r.reminders_sent_count, r.trigger_time,
           t.id as task_id, t.title, t.description, t.priority, t.location, t.user_id, t.due_date,
           u.email as user_email, u.full_name as user_full_name,
           e.email as employee_email, e.full_name as employee_name
    FROM todo_task_reminders r
    JOIN todo_tasks t ON r.todo_task_id = t.id
    JOIN users u ON t.user_id = u.id
    LEFT JOIN employees e ON u.employee_id = e.id
    WHERE r.is_active = 1 
      AND r.trigger_time <= NOW() 
      AND r.reminders_sent_count < r.max_reminders
      AND t.status = 'pending'
");

$todoRemindersSent = 0;
if ($todoRes && $todoRes->num_rows > 0) {
    while ($row = $todoRes->fetch_assoc()) {
        $recipientEmail = !empty($row['employee_email']) ? $row['employee_email'] : $row['user_email'];
        $recipientName = !empty($row['employee_name']) ? $row['employee_name'] : (!empty($row['user_full_name']) ? $row['user_full_name'] : 'User');
        $newSentCount = (int)$row['reminders_sent_count'] + 1;
        $maxReminders = (int)$row['max_reminders'];

        $targetEmail = $isTestMode ? $testEmail : $recipientEmail;
        $subject = ($isTestMode ? "[TEST] " : "") . "Task Reminder ({$newSentCount}/{$maxReminders}): " . $row['title'];
        
        $body = "
        <div style='font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; padding: 35px 15px;'>
            <div style='max-width: 580px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                <div style='background: linear-gradient(135deg, #16a34a, #15803d); padding: 22px 28px;'>
                    <div style='font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,0.8); margin-bottom: 4px;'>TPHL ERP &middot; Task Management</div>
                    <h2 style='margin: 0; color: #ffffff; font-size: 20px; font-weight: 700;'>Task Reminder Notification</h2>
                </div>
                <div style='padding: 28px;'>
                    <p style='color: #334155; font-size: 15px; margin: 0 0 14px;'>Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,</p>
                    <p style='color: #475569; font-size: 14px; line-height: 1.6; margin: 0 0 20px;'>This is a scheduled reminder (<strong>Reminder {$newSentCount} of {$maxReminders}</strong>) for your pending task:</p>
                    
                    <div style='background-color: #f1f5f9; border-left: 4px solid #16a34a; border-radius: 6px; padding: 18px 20px; margin-bottom: 24px;'>
                        <div style='font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 8px;'>" . htmlspecialchars($row['title']) . "</div>";
                        if (!empty($row['description'])) {
                            $body .= "<div style='font-size: 13.5px; color: #475569; margin-bottom: 10px;'>" . nl2br(htmlspecialchars($row['description'])) . "</div>";
                        }
                        $body .= "<div style='font-size: 12.5px; color: #64748b;'>";
                        if (!empty($row['due_date'])) {
                            $body .= "<span style='margin-right: 15px;'>📅 <strong>Due:</strong> " . date('d M Y', strtotime($row['due_date'])) . "</span>";
                        }
                        if (!empty($row['location'])) {
                            $body .= "<span>📍 <strong>Location:</strong> " . htmlspecialchars($row['location']) . "</span>";
                        }
                        $body .= "</div>
                    </div>
                    
                    <div style='text-align: center; margin-top: 25px; margin-bottom: 10px;'>
                        <a href='https://thepremiumhomesltd.com/TPHL-ERP/todo' style='background-color: #16a34a; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-block;'>View Task in Todo List &rarr;</a>
                    </div>
                </div>
                <div style='background-color: #f8fafc; padding: 14px 28px; text-align: center; border-top: 1px solid #f1f5f9;'>
                    <p style='margin: 0; font-size: 11.5px; color: #94a3b8;'>This is an automated notification from TPHL ERP. Please do not reply directly.</p>
                </div>
            </div>
        </div>";

        if (!empty($targetEmail)) {
            $todoMailService->send($targetEmail, $subject, $body);
        }

        // Insert into notifications table
        $msg = "Reminder for task: " . $db->real_escape_string($row['title']);
        $db->query("INSERT INTO notifications (user_id, message) VALUES ({$row['user_id']}, '$msg')");

        // Update Reminder Logic
        if ($newSentCount >= $maxReminders) {
            $db->query("UPDATE todo_task_reminders SET reminders_sent_count = $newSentCount, is_active = 0 WHERE id = {$row['reminder_id']}");
        } else {
            $intervalHours = 24 / $maxReminders;
            $intervalMinutes = round($intervalHours * 60);
            $db->query("UPDATE todo_task_reminders SET reminders_sent_count = $newSentCount, trigger_time = DATE_ADD(trigger_time, INTERVAL $intervalMinutes MINUTE) WHERE id = {$row['reminder_id']}");
        }
        $todoRemindersSent++;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Cron execution completed. Queued {$emailsQueued} notifications in email_queue, sent {$todoRemindersSent} Todo reminders.",
    'test_mode' => $isTestMode,
    'todo_reminders_sent' => $todoRemindersSent,
    'debug_logs' => $debugLogs
], JSON_PRETTY_PRINT);
exit;
