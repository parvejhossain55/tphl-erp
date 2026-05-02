<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$config = require __DIR__ . '/app/config/config.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$leadId = 4356;
$sql = $db->prepare("
        SELECT 
            lb.id as booking_id,
            lb.booking_date,
            lb.agreement_value,
            lb.booking_status,
            lb.landshare_status,
            lb.service_fee_balance,
            lb.transfer_status,
            
            p.id as project_id,
            p.name as project_name,
            p.types as project_type,
            
            f.id as flat_id,
            f.floor_no as flat_no,
            f.sqft as flat_size,
            f.price_value as total_price,
            f.land_share_value as total_land_share,
            f.flat_name as unit_name,
            
            (SELECT u_agent.full_name
             FROM lead_payments lp_sub
             INNER JOIN users u_agent ON u_agent.id = lp_sub.created_by
             WHERE lp_sub.booking_id = lb.id AND lp_sub.payment_type = 'booking_money'
             LIMIT 1
            ) AS booking_agent_name,
            
            COALESCE(
                (SELECT SUM(lp.amount) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_paid,
            COALESCE(
                (SELECT SUM(lp.land_share_amount) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_land_share_paid,
            COALESCE(
                (SELECT SUM(lp.service_fee) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                0
            ) as total_service_fee,
            COALESCE(
                (SELECT MAX(lp.payment_date) 
                 FROM lead_payments lp 
                 WHERE lp.booking_id = lb.id 
                 AND lp.payment_status IN ('paid','partially_paid')), 
                NULL
            ) as last_payment_date
        FROM lead_bookings lb
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN flats f ON lb.flat_id = f.id
 WHERE lb.lead_id = ?
AND lb.booking_status != 'cancelled'
ORDER BY lb.booking_date DESC
    ");

if (!$sql) {
    die("Prepare failed: " . $db->error);
}

$sql->bind_param("i", $leadId);
$sql->execute();
$res = $sql->get_result()->fetch_all(MYSQLI_ASSOC);
print_r($res);
