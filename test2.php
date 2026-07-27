<?php
require 'c:\xampp\htdocs\TPHL-ERP\app\config\config.php';
// Need to include db properly
require 'c:\xampp\htdocs\TPHL-ERP\app\helpers\db.php';

$db = db();
$res = $db->query("
            SELECT COUNT(*) AS total FROM (
                SELECT (
                    SELECT lp.verification_status
                    FROM lead_payments lp
                    INNER JOIN lead_bookings lb3 ON lb3.id = lp.booking_id
                    WHERE lb3.lead_id = l.id
                    ORDER BY lp.created_at DESC LIMIT 1
                ) AS latest_verification_status
                FROM leads l
                INNER JOIN lead_bookings lb ON lb.lead_id = l.id
                GROUP BY l.id
            ) AS t
            WHERE t.latest_verification_status = 'pending' OR t.latest_verification_status IS NULL
");
if ($res) {
    echo "Count: " . $res->fetch_assoc()['total'];
} else {
    echo "Error: " . $db->error;
}
