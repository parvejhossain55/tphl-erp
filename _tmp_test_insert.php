<?php
/**
 * Test direct INSERT into architect_engineering table
 */
$db = new mysqli('localhost', 'root', '', 'tphl_db');
if ($db->connect_error) { die("DB Error: " . $db->connect_error); }

$projectId       = 17;
$architectUserId = 130;  // created_by from db
$designLink      = 'https://test.com';
$notes           = 'Test notes';
$status          = 'draft';
$createdBy       = 130;

// Check table structure
$result = $db->query("DESCRIBE architect_engineering");
echo "Table columns:\n";
while ($row = $result->fetch_assoc()) {
    echo "  {$row['Field']} | {$row['Type']} | Null:{$row['Null']} | Default:{$row['Default']}\n";
}

echo "\nAttempting INSERT...\n";
$ins = $db->prepare(
    'INSERT INTO architect_engineering
     (project_id, user_id, full_architecture_design_link, notes, status, created_by)
     VALUES (?, ?, ?, ?, ?, ?)'
);

if (!$ins) {
    echo "PREPARE FAILED: " . $db->error . "\n";
    exit;
}

$ins->bind_param('iisssi', $projectId, $architectUserId, $designLink, $notes, $status, $createdBy);

if ($ins->execute()) {
    echo "INSERT SUCCESS! New ID: " . $ins->insert_id . "\n";
    // Clean up test record
    $db->query("DELETE FROM architect_engineering WHERE id = " . $ins->insert_id);
    echo "Test record deleted.\n";
} else {
    echo "INSERT FAILED: " . $db->error . "\n";
    echo "Error number: " . $db->errno . "\n";
}

// Check if user_id=0 causes issue (NOT NULL constraint)
echo "\nTesting with user_id=0...\n";
$ins2 = $db->prepare(
    'INSERT INTO architect_engineering
     (project_id, user_id, full_architecture_design_link, notes, status, created_by)
     VALUES (?, ?, ?, ?, ?, ?)'
);
$uid = 0;
$ins2->bind_param('iisssi', $projectId, $uid, $designLink, $notes, $status, $createdBy);
if ($ins2->execute()) {
    echo "INSERT with user_id=0 SUCCESS! ID: " . $ins2->insert_id . "\n";
    $db->query("DELETE FROM architect_engineering WHERE id = " . $ins2->insert_id);
} else {
    echo "INSERT with user_id=0 FAILED: " . $db->error . "\n";
}
