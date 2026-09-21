<?php
require __DIR__ . '/../vendor/autoload.php';
$config = require __DIR__ . '/../app/config/config.php';
require __DIR__ . '/../app/services/ExternalApiService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Database connection failed: " . $db->connect_error . "\n");
}

$api = new ExternalApiService($db);
$token = $api->getBearerToken();
if (!$token) {
    die("Failed to get Tipsoi token.\n");
}

echo "Authenticated with Tipsoi successfully.\n";

// 1. Fetch Tipsoi Employees for ID -> Name/Email mapping
$ch = curl_init($config['tipsoi_base_url'] . "employee?order=asc&pageNumber=0&perPage=2000&status=1");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$resEmp = curl_exec($ch);
curl_close($ch);
$dEmp = json_decode($resEmp, true);
$externalEmployees = $dEmp['data'] ?? $dEmp['employees'] ?? $dEmp['content'] ?? $dEmp ?? [];

$tipsoiEmployeesMap = [];
$tipsoiIdToOfficeIdMap = [];
foreach ($externalEmployees as $emp) {
    $tid = $emp['employeeId'] ?? $emp['id'] ?? null;
    $oid = $emp['employeeOfficeId'] ?? $emp['officeId'] ?? null;
    if ($tid) {
        $tipsoiEmployeesMap[$tid] = $emp;
        if ($oid) {
            $tipsoiIdToOfficeIdMap[$tid] = $oid;
        }
    }
}
echo "Total active Tipsoi employees loaded: " . count($tipsoiEmployeesMap) . "\n";

$pendingList = [];

// 2. Fetch Global Leaves
$ch = curl_init($config['tipsoi_base_url'] . "leave-management/leave-history?page=0&count=1000&status=0&keyword=%20");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$resLeaves = curl_exec($ch);
curl_close($ch);
$dLeaves = json_decode($resLeaves, true);
$globalLeaves = $dLeaves['leaveLogList'] ?? [];

echo "Fetched " . count($globalLeaves) . " pending leave requests from Tipsoi.\n";

foreach ($globalLeaves as $log) {
    $firstStatus = $log['firstApprovalStatus'] ?? 0;
    $secondStatus = $log['secondApprovalStatus'] ?? 0;

    $startDate = isset($log['startDate']) ? date('Y-m-d', (int)($log['startDate'] / 1000)) : '—';
    $endDate = isset($log['endDate']) ? date('Y-m-d', (int)($log['endDate'] / 1000)) : $startDate;

    $currentPendingWith = '';
    $currentLevel = '';
    if ($firstStatus == 0) {
        $currentPendingWith = $log['firstApproverName'] ?? '1st Approver';
        $currentLevel = '1st Approval Pending';
    } elseif ($firstStatus == 1 && $secondStatus == 0) {
        $currentPendingWith = $log['secondApproverName'] ?? '2nd Approver';
        $currentLevel = '2nd Approval Pending';
    }

    $pendingList[] = [
        'type' => 'Leave Request (' . ($log['categoryName'] ?? 'Leave') . ')',
        'category' => 'Leave',
        'employee_name' => $log['employeeName'] ?? 'Unknown',
        'employee_office_id' => $log['employeeOfficeId'] ?? ($log['employeeId'] ? ($tipsoiIdToOfficeIdMap[$log['employeeId']] ?? '') : ''),
        'department' => $log['employeeDepartmentName'] ?? $log['departmentName'] ?? 'N/A',
        'period' => ($startDate === $endDate) ? $startDate : "$startDate to $endDate",
        'reason' => $log['reason'] ?? '',
        'first_approver' => $log['firstApproverName'] ?? '—',
        'first_status' => $firstStatus,
        'second_approver' => $log['secondApproverName'] ?? '—',
        'second_status' => $secondStatus,
        'currently_pending_with' => $currentPendingWith,
        'pending_level' => $currentLevel
    ];
}

// 3. Fetch Global Manual Entries (Type 1: Manual, Type 2: Late, Type 3: Field)
$officeId = 4699;
$manualTypes = [
    1 => 'Manual Entry',
    2 => 'Late Excuse',
    3 => 'Field Visit'
];

foreach ($manualTypes as $typeCode => $typeName) {
    $ch = curl_init($config['tipsoi_base_url'] . "manual-entry?officeId=$officeId&order=asc&pageNumber=0&perPage=500&sortKey=reportPosition&manualEntryType=$typeCode&applicationStatus=0");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $res = curl_exec($ch);
    curl_close($ch);
    $d = json_decode($res, true);
    $entries = $d['manualEntries'] ?? [];
    echo "Fetched " . count($entries) . " pending entries for type $typeName (Code $typeCode).\n";

    foreach ($entries as $entry) {
        $firstStatus = $entry['firstManualEntryApprovalStatus'] ?? 0;
        $secondStatus = $entry['secondManualEntryApprovalStatus'] ?? 0;

        $startDate = isset($entry['startDate']) ? date('Y-m-d', (int)($entry['startDate'] / 1000)) : '—';
        $endDate = isset($entry['endDate']) ? date('Y-m-d', (int)($entry['endDate'] / 1000)) : $startDate;

        $currentPendingWith = '';
        $currentLevel = '';
        if ($firstStatus == 0) {
            $currentPendingWith = $entry['firstManualApproverName'] ?? '1st Approver';
            $currentLevel = '1st Approval Pending';
        } elseif ($firstStatus == 1 && $secondStatus == 0) {
            $currentPendingWith = $entry['secondManualApproverName'] ?? '2nd Approver';
            $currentLevel = '2nd Approval Pending';
        }

        $pendingList[] = [
            'type' => $typeName,
            'category' => $typeName,
            'employee_name' => $entry['employeeName'] ?? 'Unknown',
            'employee_office_id' => $entry['employeeOfficeId'] ?? ($entry['employeeId'] ? ($tipsoiIdToOfficeIdMap[$entry['employeeId']] ?? '') : ''),
            'department' => $entry['departmentName'] ?? 'N/A',
            'period' => ($startDate === $endDate) ? $startDate : "$startDate to $endDate",
            'reason' => $entry['reason'] ?? '',
            'first_approver' => $entry['firstManualApproverName'] ?? '—',
            'first_status' => $firstStatus,
            'second_approver' => $entry['secondManualApproverName'] ?? '—',
            'second_status' => $secondStatus,
            'currently_pending_with' => $currentPendingWith,
            'pending_level' => $currentLevel
        ];
    }
}

// 4. Also check direct late approval management endpoint
$ch = curl_init($config['tipsoi_base_url'] . "late-approval-management/approval-requests/298780");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);
$resLate = curl_exec($ch);
curl_close($ch);
$dLate = json_decode($resLate, true);
$lateApprovals = $dLate['lateApprovalApplications'] ?? $dLate['lateLogList'] ?? [];
echo "Fetched " . count($lateApprovals) . " late approvals from approval-requests/298780.\n";

// Save results to a json file in scratch
file_put_contents(__DIR__ . '/pending_results.json', json_encode($pendingList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// Group by approver
$byApprover = [];
foreach ($pendingList as $item) {
    $approver = trim($item['currently_pending_with']) ?: 'Unassigned / Unknown';
    if (!isset($byApprover[$approver])) {
        $byApprover[$approver] = [
            '1st_approval_count' => 0,
            '2nd_approval_count' => 0,
            'requests' => []
        ];
    }
    if ($item['pending_level'] === '1st Approval Pending') {
        $byApprover[$approver]['1st_approval_count']++;
    } else {
        $byApprover[$approver]['2nd_approval_count']++;
    }
    $byApprover[$approver]['requests'][] = $item;
}

echo "\n================ SUMMARY OF CURRENT PENDING REQUESTS ================\n";
echo "Total Pending Requests: " . count($pendingList) . "\n\n";

foreach ($byApprover as $approverName => $data) {
    echo "▶ Approver: $approverName\n";
    echo "   Total: " . count($data['requests']) . " (1st Approval: {$data['1st_approval_count']}, 2nd Approval: {$data['2nd_approval_count']})\n";
    foreach ($data['requests'] as $req) {
        echo "   - [{$req['pending_level']}] [{$req['type']}] {$req['employee_name']} ({$req['department']}) | Date: {$req['period']} | Reason: {$req['reason']}\n";
    }
    echo "\n";
}
