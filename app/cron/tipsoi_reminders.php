<?php
require __DIR__ . '/../../vendor/autoload.php';
$config = require __DIR__ . '/../config/config.php';
require __DIR__ . '/../services/ExternalApiService.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Determine run type
$runType = 'weekly';
$dayOfMonth = date('d');
$dayOfWeek = date('w'); // 0 (Sun) to 6 (Sat)

if (isset($_GET['type'])) {
    $runType = $_GET['type'];
} elseif (isset($argv[1])) {
    $runType = $argv[1];
} else {
    // Default logic if run daily by cron
    if ($dayOfMonth == '23' || $dayOfMonth == '24') {
        $runType = 'monthly';
    } elseif ($dayOfWeek == 0) { // Sunday
        $runType = 'weekly';
    } else {
        die("Not a scheduled day for reminders.");
    }
}

echo "Running Tipsoi Reminders: $runType\n";

$api = new ExternalApiService($db);
$token = $api->getBearerToken();
if (!$token) {
    die("Failed to get Tipsoi token.");
}

// 1. Fetch Local Employees
$localQuery = $db->query("SELECT employee_id, email, full_name FROM employees");
$localEmails = [];
$erpEmployees = [];
while ($row = $localQuery->fetch_assoc()) {
    $em = strtolower(trim($row['email'] ?? ''));
    if ($em) {
        $localEmails[$em] = $row;
        $erpEmployees[$row['employee_id']] = $row;
    }
}

// 2. Fetch Tipsoi Employees
$ch = curl_init($config['tipsoi_base_url'] . "employee?order=asc&pageNumber=0&perPage=2000&status=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resEmp = curl_exec($ch);
curl_close($ch);
$dEmp = json_decode($resEmp, true);
$externalEmployees = $dEmp['data'] ?? $dEmp['employees'] ?? $dEmp['content'] ?? $dEmp ?? [];

$tipsoiEmployeesMap = []; // By Tipsoi ID
foreach ($externalEmployees as $emp) {
    $tid = $emp['employeeId'] ?? $emp['id'] ?? null;
    if ($tid) {
        $tipsoiEmployeesMap[$tid] = $emp;
    }
}

$pendingByApprover = []; // Structure: [ 'email' => [ 'name' => '...', 'requests' => [...] ] ]

function addPendingRequest(&$pendingByApprover, $approverId, $approverName, $requestData, $tipsoiEmployeesMap, $localEmails, $level) {
    global $runType;
    if (!$approverId) return;

    // Find email of approver
    $approverEmail = '';
    if (isset($tipsoiEmployeesMap[$approverId])) {
        $approverEmail = strtolower(trim($tipsoiEmployeesMap[$approverId]['email'] ?? ''));
    }
    
    // Fallback if Tipsoi doesn't have email
    if (empty($approverEmail)) {
        // We can't send an email if we don't know it!
        return;
    }

    if (!isset($pendingByApprover[$approverEmail])) {
        $pendingByApprover[$approverEmail] = [
            'name' => $approverName,
            'requests' => []
        ];
    }
    
    $requestData['pending_level'] = $level;
    $pendingByApprover[$approverEmail]['requests'][] = $requestData;
}

// 3. Fetch Global Leaves
$ch = curl_init($config['tipsoi_base_url'] . "leave-management/leave-history?page=0&count=1000&status=0");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resLeaves = curl_exec($ch);
curl_close($ch);
$dLeaves = json_decode($resLeaves, true);
$globalLeaves = $dLeaves['leaveLogList'] ?? [];

foreach ($globalLeaves as $log) {
    $reqData = [
        'type' => 'Leave Request (' . ($log['categoryName'] ?? '') . ')',
        'employee' => $log['employeeName'] ?? 'Unknown',
        'date' => isset($log['startDate']) ? date('Y-m-d', (int)($log['startDate'] / 1000)) : '—',
        'reason' => $log['reason'] ?? '—'
    ];

    if (($log['firstApprovalStatus'] ?? -1) == 0) {
        addPendingRequest($pendingByApprover, $log['firstApproverId'] ?? null, $log['firstApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '1st Approval');
    } elseif (($log['firstApprovalStatus'] ?? -1) == 1 && ($log['secondApprovalStatus'] ?? -1) == 0) {
        addPendingRequest($pendingByApprover, $log['secondApproverId'] ?? null, $log['secondApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '2nd Approval');
    }
}

$officeId = 4699;

// 4. Fetch Global Manual Entries (Type 1: Manual, Type 2: Late, Type 3: Field)
$manualEntries = [];

// Type 1 (Manual)
$ch = curl_init($config['tipsoi_base_url'] . "manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=500&sortKey=reportPosition&manualEntryType=1&applicationStatus=0");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$d = json_decode($res, true);
if (!empty($d['manualEntries'])) $manualEntries = array_merge($manualEntries, $d['manualEntries']);

// Type 2 (Late)
$ch = curl_init($config['tipsoi_base_url'] . "manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=500&sortKey=reportPosition&manualEntryType=2&applicationStatus=0");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$d = json_decode($res, true);
if (!empty($d['manualEntries'])) $manualEntries = array_merge($manualEntries, $d['manualEntries']);

// Type 3 (Field)
$ch = curl_init($config['tipsoi_base_url'] . "manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=500&sortKey=reportPosition&manualEntryType=3&applicationStatus=0");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);
$d = json_decode($res, true);
if (!empty($d['manualEntries'])) $manualEntries = array_merge($manualEntries, $d['manualEntries']);

foreach ($manualEntries as $log) {
    $typeVal = $log['manualEntryType'] ?? 1;
    $reqType = ($typeVal == 1) ? 'Manual Entry' : (($typeVal == 2) ? 'Late Excuse' : 'Field Visit');
    
    $reqData = [
        'type' => $reqType,
        'employee' => $log['employeeName'] ?? 'Unknown',
        'date' => isset($log['startDate']) ? date('Y-m-d', (int)($log['startDate'] / 1000)) : '—',
        'reason' => $log['reason'] ?? '—'
    ];
    if (($log['firstManualEntryApprovalStatus'] ?? -1) == 0) {
        addPendingRequest($pendingByApprover, $log['firstManualEntryApproverId'] ?? null, $log['firstManualApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '1st Approval');
    } elseif (($log['firstManualEntryApprovalStatus'] ?? -1) == 1 && ($log['secondManualEntryApprovalStatus'] ?? -1) == 0) {
        addPendingRequest($pendingByApprover, $log['secondManualEntryApproverId'] ?? null, $log['secondManualApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '2nd Approval');
    }
}

// 5. Fetch Late and Movement by iterating over potential approvers (all Tipsoi employees)
echo "Fetching late/movement requests for " . count($tipsoiEmployeesMap) . " employees...\n";
foreach ($tipsoiEmployeesMap as $tid => $emp) {
    // Late Approvals
    $ch = curl_init($config['tipsoi_base_url'] . "late-approval-management/late-approvals/$tid");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resLate = curl_exec($ch);
    curl_close($ch);
    $dLate = json_decode($resLate, true);
    $lateList = $dLate['lateLogList'] ?? [];
    
    foreach ($lateList as $log) {
        $reqData = [
            'type' => 'Late Excuse',
            'employee' => $log['employeeName'] ?? 'Unknown',
            'date' => isset($log['date']) ? date('Y-m-d', (int)($log['date'] / 1000)) : '—',
            'reason' => $log['reason'] ?? '—'
        ];
        if (($log['firstLateApprovalStatus'] ?? -1) == 0) {
            addPendingRequest($pendingByApprover, $log['firstLateApproverId'] ?? $log['firstApproverId'] ?? null, $log['firstLateApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '1st Approval');
        } elseif (($log['firstLateApprovalStatus'] ?? -1) == 1 && ($log['secondLateApprovalStatus'] ?? -1) == 0) {
            addPendingRequest($pendingByApprover, $log['secondLateApproverId'] ?? $log['secondApproverId'] ?? null, $log['secondLateApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '2nd Approval');
        }
    }

    // Movement Approvals
    $ch = curl_init($config['tipsoi_base_url'] . "employee-movement/approvers/$tid?page=0&count=100&status=0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resMove = curl_exec($ch);
    curl_close($ch);
    $dMove = json_decode($resMove, true);
    $moveList = $dMove['movementLogList'] ?? [];

    foreach ($moveList as $log) {
        $reqData = [
            'type' => 'Movement Request',
            'employee' => $log['employeeName'] ?? 'Unknown',
            'date' => isset($log['date']) ? date('Y-m-d', (int)($log['date'] / 1000)) : '—',
            'reason' => $log['reason'] ?? '—'
        ];
        if (($log['firstApprovalStatus'] ?? -1) == 0) {
            addPendingRequest($pendingByApprover, $log['firstApproverId'] ?? null, $log['firstApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '1st Approval');
        } elseif (($log['firstApprovalStatus'] ?? -1) == 1 && ($log['secondApprovalStatus'] ?? -1) == 0) {
            addPendingRequest($pendingByApprover, $log['secondApproverId'] ?? null, $log['secondApproverName'] ?? '', $reqData, $tipsoiEmployeesMap, $localEmails, '2nd Approval');
        }
    }
}

// 6. Send Emails and Log
echo "Sending emails...\n";

foreach ($pendingByApprover as $email => $data) {
    if (empty($data['requests'])) continue;

    $approverName = $data['name'];
    $subject = "Tipsoi Pending Requests Reminder ($runType)";
    
    // Build HTML table
    $html = "<h3>Hello $approverName,</h3>";
    $html .= "<p>You have the following pending requests in Tipsoi that require your approval:</p>";
    $html .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%;'>";
    $html .= "<tr style='background-color:#f2f2f2;'><th>Type</th><th>Employee</th><th>Date</th><th>Reason</th><th>Pending Level</th></tr>";
    
    $logDetails = [];
    foreach ($data['requests'] as $req) {
        $html .= "<tr>";
        $html .= "<td>{$req['type']}</td>";
        $html .= "<td>{$req['employee']}</td>";
        $html .= "<td>{$req['date']}</td>";
        $html .= "<td>{$req['reason']}</td>";
        $html .= "<td>{$req['pending_level']}</td>";
        $html .= "</tr>";

        $logDetails[] = "{$req['type']} for {$req['employee']} on {$req['date']} ({$req['pending_level']})";
    }
    $html .= "</table>";
    $html .= "<br><p>Please log in to your Tipsoi account or ERP to approve/reject these requests.</p>";
    $html .= "<p>Thanks,<br>TPHL HR Team</p>";

    // Send Mail
    $mail = new PHPMailer(true);
    $status = 'Failed';
    $errorMsg = '';
    
    try {
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'] ?? '';
        $mail->Password   = $config['smtp_pass'] ?? '';
        $mail->SMTPSecure = $config['smtp_secure'] ?? 'ssl';
        $mail->Port       = $config['smtp_port'] ?? 465;

        $mail->setFrom($config['smtp_user'], 'TPHL ERP Notification');
        $mail->addAddress($email, $approverName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;

        $mail->send();
        $status = 'Success';
    } catch (Exception $e) {
        $status = 'Failed';
        $errorMsg = $mail->ErrorInfo;
    }

    // Insert into DB log
    $detailsStr = $db->real_escape_string(implode("\n", $logDetails));
    $levelStr = $db->real_escape_string($data['requests'][0]['pending_level'] . (count($data['requests']) > 1 ? " & others" : ""));
    $emailEsc = $db->real_escape_string($email);
    $nameEsc = $db->real_escape_string($approverName);
    $statusEsc = $db->real_escape_string($status);
    $errorEsc = $db->real_escape_string($errorMsg);

    $db->query("INSERT INTO tipsoi_email_logs (approver_name, approver_email, pending_level, request_details, status, error_message) VALUES ('$nameEsc', '$emailEsc', '$levelStr', '$detailsStr', '$statusEsc', '$errorEsc')");

    echo "Email to $email: $status\n";
}

echo "Done.\n";
