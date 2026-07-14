<?php
// fix_live_db.php
// Place this file in the root of your project on the live server and run it via browser: https://your-domain/fix_live_db.php

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

header('Content-Type: text/plain; charset=utf-8');
echo "=== ERP LIVE DATABASE DIAGNOSTIC & FIX RUNNER ===\n";
echo "Connected database: " . $config['db_name'] . "\n\n";

// 1. Diagnostics: Search for Abu Naym
echo "--- DIAGNOSTICS: Abu Naym ---\n";
$resUser = $db->query("SELECT id, full_name, team_id, role_id, employee_id FROM users WHERE full_name LIKE '%Abu Naym%' OR full_name LIKE '%Naym%'");
if ($resUser && $resUser->num_rows > 0) {
    while ($u = $resUser->fetch_assoc()) {
        echo "Users Table:\n";
        print_r($u);
    }
} else {
    echo "No user found in 'users' table matching Abu Naym.\n";
}

$resEmp = $db->query("SELECT id, full_name, team_id, user_id, employee_id FROM employees WHERE full_name LIKE '%Abu Naym%' OR full_name LIKE '%Naym%'");
if ($resEmp && $resEmp->num_rows > 0) {
    while ($e = $resEmp->fetch_assoc()) {
        echo "Employees Table:\n";
        print_r($e);
    }
} else {
    echo "No employee found in 'employees' table matching Abu Naym.\n";
}

// 2. Diagnostics: Search for Sales Manager of Team 7
echo "\n--- DIAGNOSTICS: Sales Manager (Rakib Hasan Rubai) ---\n";
$resMgr = $db->query("SELECT id, full_name, team_id, role_id FROM users WHERE id = 68 OR full_name LIKE '%Rubai%'");
if ($resMgr && $resMgr->num_rows > 0) {
    print_r($resMgr->fetch_assoc());
} else {
    echo "Sales Manager Rakib Hasan Rubai (ID 68) not found.\n";
}

// 3. Diagnostics: All members in Team 7 (according to users table)
echo "\n--- DIAGNOSTICS: Users currently in Team 7 (users.team_id = 7) ---\n";
$resTeam7 = $db->query("SELECT id, full_name, team_id, role_id FROM users WHERE team_id = 7");
while ($row = $resTeam7->fetch_assoc()) {
    print_r($row);
}


echo "\n================ RUNNING AUTO-FIXES ================\n";

// Fix 1: Link employees.user_id using users.employee_id
$db->query("
    UPDATE employees e 
    JOIN users u ON u.employee_id = e.id 
    SET e.user_id = u.id 
    WHERE e.user_id IS NULL OR e.user_id = 0 OR e.user_id = ''
");
echo "1. Linked missing user_ids in employees table: " . $db->affected_rows . " records fixed.\n";

// Fix 2: Sync users.team_id from employees.team_id where they differ
$db->query("
    UPDATE users u 
    JOIN employees e ON u.employee_id = e.id 
    SET u.team_id = e.team_id 
    WHERE u.team_id != e.team_id
");
echo "2. Synced team_id from employees to users: " . $db->affected_rows . " records fixed.\n";

// Fix 3: Sync leads.team_id with assigned users' team_id
$db->query("
    UPDATE leads l
    JOIN users u ON l.assigned_id = u.id
    SET l.team_id = u.team_id
    WHERE l.deleted_at IS NULL
      AND u.team_id > 0
      AND l.team_id != u.team_id
");
echo "3. Synced leads.team_id with executive's team_id: " . $db->affected_rows . " records fixed.\n";


echo "\n================ POST-FIX DIAGNOSTICS ================\n";

echo "--- Users currently in Team 7 after fixes ---\n";
$resTeam7Post = $db->query("SELECT id, full_name, team_id, role_id FROM users WHERE team_id = 7");
while ($row = $resTeam7Post->fetch_assoc()) {
    print_r($row);
}

// Check leads for Abu Naym
echo "\nChecking leads assigned to Abu Naym...\n";
$resAbuLeads = $db->query("
    SELECT COUNT(l.id) as lead_count, l.team_id, t.name as team_name
    FROM leads l
    LEFT JOIN teams t ON l.team_id = t.id
    WHERE l.assigned_id IN (SELECT id FROM users WHERE full_name LIKE '%Abu Naym%' OR full_name LIKE '%Naym%')
    GROUP BY l.team_id
");
if ($resAbuLeads && $resAbuLeads->num_rows > 0) {
    while ($row = $resAbuLeads->fetch_assoc()) {
        echo "Leads count: " . $row['lead_count'] . " | Team ID: " . $row['team_id'] . " (" . ($row['team_name'] ?? 'No Team') . ")\n";
    }
} else {
    echo "No leads assigned to Abu Naym.\n";
}

echo "\nFix runner completed successfully. Please delete this file from the server after running!\n";
