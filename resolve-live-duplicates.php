<?php
session_start();

// রান হওয়ার সময়সীমা আনলিমিটেড করা হলো
set_time_limit(0);

// মূল কনফিগ ফাইল থেকে ডাটাবেজ কনফিগারেশন অটো-লোড
$configPath = __DIR__ . '/../app/config/config.php';
if (!file_exists($configPath)) {
    die("Configuration file not found. Make sure this script is placed in the 'public/' directory.");
}

$config = require $configPath;

$db_host = $config['db_host'] ?? '127.0.0.1';
$db_user = $config['db_user'] ?? 'root';
$db_pass = $config['db_pass'] ?? '';
$db_name = $config['db_name'] ?? 'final_erp_db';


$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ১. শুধুমাত্র একটিভ সেলস এক্সিকিউটিভদের আইডি লোড করা (Resigned/Inactive এবং যাদের role_id খালি বা ০ তাদের বাদ দিয়ে)
$activeUsersRes = $conn->query("
    SELECT u.id, e.team_id 
    FROM users u
    JOIN employees e ON e.user_id = u.id
    WHERE (e.employee_status IS NULL OR e.employee_status NOT IN ('resigned', 'inactive', 'terminated'))
      AND u.role_id IS NOT NULL 
      AND u.role_id != 0 
      AND u.role_id != ''
");
$activeUserIds = [];
$userTeams = [];
while ($row = $activeUsersRes->fetch_assoc()) {
    $uid = (int)$row['id'];
    $activeUserIds[] = $uid;
    $userTeams[$uid] = (int)$row['team_id'];
}

echo "Total Active Sales Executives (with valid roles): " . count($activeUserIds) . "<br>\n";

$isUserActive = function($userId) use ($activeUserIds) {
    return in_array((int)$userId, $activeUserIds, true);
};

// ২. ক্রমানুসারে ডিলিট না হওয়া এবং অলরেডি অ্যাসাইনড সকল লিড লোড করা
$leadsRes = $conn->query("
    SELECT id, phone_number, phone, email, assigned_id, team_id, created_at
    FROM leads
    WHERE deleted_at IS NULL 
      AND assigned_id IS NOT NULL
    ORDER BY created_at ASC
");

$leads = [];
while ($row = $leadsRes->fetch_assoc()) {
    $leads[] = $row;
}

echo "Total Assigned Leads Loaded: " . count($leads) . "<br>\n";

// ফোন ও ইমেল অনুসারে ডুপ্লিকেট গ্রুপ করা
$phoneGroups = [];
$emailGroups = [];

foreach ($leads as $lead) {
    $phone1 = trim($lead['phone_number'] ?? '');
    $phone2 = trim($lead['phone'] ?? '');
    $email = trim($lead['email'] ?? '');
    
    $phone = !empty($phone1) ? $phone1 : $phone2;
    
    if (!empty($phone)) {
        $phoneGroups[$phone][] = $lead;
    }
    if (!empty($email)) {
        $emailGroups[strtolower($email)][] = $lead;
    }
}

$reassignedCount = 0;

// ফোন ডুপ্লিকেট প্রসেস
foreach ($phoneGroups as $phone => $group) {
    if (count($group) < 2) continue;
    
    // ক্রমানুসারে প্রথম একটিভ এবং বৈধ রোলের ওনার নির্বাচন করা
    $firstActiveLead = null;
    foreach ($group as $lead) {
        if ($isUserActive($lead['assigned_id'])) {
            $firstActiveLead = $lead;
            break; // প্রথম সঠিক ইউজার পেলেই লুপ ব্রেক হবে
        }
    }
    
    if (!$firstActiveLead) continue;
    
    $targetAssigneeId = $firstActiveLead['assigned_id'];
    $targetTeamId = $userTeams[$targetAssigneeId] ?? $firstActiveLead['team_id'];
    
    foreach ($group as $lead) {
        if ((int)$lead['assigned_id'] !== (int)$targetAssigneeId) {
            $stmt = $conn->prepare("UPDATE leads SET assigned_id = ?, team_id = ? WHERE id = ?");
            $lid = $lead['id'];
            $stmt->bind_param("iii", $targetAssigneeId, $targetTeamId, $lid);
            if ($stmt->execute()) {
                $reassignedCount++;
            }
        }
    }
}

// ইমেল ডুপ্লিকেট প্রসেস
foreach ($emailGroups as $email => $group) {
    if (count($group) < 2) continue;
    
    // ক্রমানুসারে প্রথম একটিভ এবং বৈধ রোলের ওনার নির্বাচন করা
    $firstActiveLead = null;
    foreach ($group as $lead) {
        if ($isUserActive($lead['assigned_id'])) {
            $firstActiveLead = $lead;
            break;
        }
    }
    
    if (!$firstActiveLead) continue;
    
    $targetAssigneeId = $firstActiveLead['assigned_id'];
    $targetTeamId = $userTeams[$targetAssigneeId] ?? $firstActiveLead['team_id'];
    
    foreach ($group as $lead) {
        $checkStmt = $conn->prepare("SELECT assigned_id FROM leads WHERE id = ?");
        $lid = $lead['id'];
        $checkStmt->bind_param("i", $lid);
        $checkStmt->execute();
        $curr = $checkStmt->get_result()->fetch_assoc();
        $currAssigneeId = $curr ? (int)$curr['assigned_id'] : null;
        
        if ($currAssigneeId !== null && $currAssigneeId !== $targetAssigneeId) {
            $stmt = $conn->prepare("UPDATE leads SET assigned_id = ?, team_id = ? WHERE id = ?");
            $stmt->bind_param("iii", $targetAssigneeId, $targetTeamId, $lid);
            if ($stmt->execute()) {
                $reassignedCount++;
            }
        }
    }
}

echo "<h3>Live Cleanup Complete!</h3>";
echo "Successfully reassigned <strong>" . $reassignedCount . "</strong> existing duplicate leads to their first active assignee.<br>";
$conn->close();
