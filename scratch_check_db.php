<?php

try {
    $db = new mysqli('localhost', 'root', '', 'tphl_db');
    if ($db->connect_error) {
        die("Connection failed: " . $db->connect_error);
    }
    
    // Check latest 5 leads
    $result = $db->query("SELECT id, full_name, phone, created_at, uploaded_from FROM leads ORDER BY id DESC LIMIT 5");
    echo "--- Latest Leads ---\n";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
    
    // Check latest 5 bookings
    $result = $db->query("SELECT id, lead_id, project_id, flat_id, created_at, uploaded_from FROM lead_bookings ORDER BY id DESC LIMIT 5");
    echo "\n--- Latest Bookings ---\n";
    while($row = $result->fetch_assoc()) {
        print_r($row);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
