<?php
$config = require 'app/config/config.php';
$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);

$refundId = 1;

// Get refund info
$res = $db->query("SELECT * FROM client_refund_requests WHERE id = $refundId");
$refund = $res->fetch_assoc();

if($refund && empty($refund['cashout_request_id'])) {
    echo "Fixing refund {$refundId}...\n";

    // 1. Update lead_bookings
    $db->query("UPDATE lead_bookings SET booking_status = 'refunded', landshare_status = 'cancelled' WHERE id = {$refund['booking_id']}");
    
    // 2. Release flat
    $db->query("UPDATE flats SET status = 'available', is_booked = 0 WHERE id = {$refund['flat_id']}");
    
    // 3. Update leads
    $db->query("UPDATE leads SET last_lead_action = 'cancel' WHERE id = {$refund['lead_id']}");
    
    // 4. Cancel EMIs
    $db->query("UPDATE emi_schedules SET status = 'cancelled' WHERE lead_booking_id = {$refund['booking_id']} AND status IN ('pending', 'missed')");
    
    // 5. Create cashout request
    $purpose = "Client Refund Auto Fix";
    $amount = $refund['refund_amount'];
    $db->query("INSERT INTO cashout_requests (requested_by, purpose_paid_to, amount, description, payment_method, project_id, status, approved_by, approved_at, approved_amount) 
                VALUES ({$refund['requested_by']}, '$purpose', $amount, 'Auto-generated fix', 'cash', {$refund['project_id']}, 'approved', 6, NOW(), $amount)");
    
    $cashoutId = $db->insert_id;
    
    // 6. Link cashout
    $db->query("UPDATE client_refund_requests SET cashout_request_id = $cashoutId WHERE id = $refundId");
    
    // 7. Auto approve cashout in approval requests
    $db->query("INSERT INTO approval_requests (department, request_type, title, source_table, source_id, requested_by, approve_role, request_amount, status, approved_amount, approved_by, approved_at) 
                VALUES ('accounts', 'cashout', '$purpose', 'cashout_requests', $cashoutId, {$refund['requested_by']}, 'management', $amount, 'approved', $amount, 6, NOW())");
                
    echo "Done! Flat is available, lead is cancelled, cashout created.\n";
} else {
    echo "No fix needed.\n";
}
