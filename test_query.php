<?php
require __DIR__ . '/app/config/config.php';
$db = new mysqli("localhost", "root", "", "tphl_db");

$sql = "
            SELECT 
                COUNT(DISTINCT CASE 
                    WHEN (SELECT COUNT(*) 
                          FROM lead_payments lp2 
                          WHERE lp2.booking_id = lb.id 
                          AND lp2.payment_type = 'booking_money' 
                          AND lp2.payment_status = 'paid') > 0
                    THEN lb.id 
                END) as approved_bookings,
                COUNT(DISTINCT CASE 
                    WHEN (SELECT COUNT(*) 
                          FROM lead_payments lp2 
                          WHERE lp2.booking_id = lb.id 
                          AND lp2.payment_status = 'paid') > 0
                    THEN lb.id 
                END) as any_payment_bookings
            FROM leads l
            INNER JOIN lead_bookings lb ON l.id = lb.lead_id
            LEFT JOIN lead_payments lp ON lb.id = lp.booking_id AND lp.payment_status = 'paid'
        ";

$res = $db->query($sql);
print_r($res->fetch_assoc());
