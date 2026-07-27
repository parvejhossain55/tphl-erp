<?php

$mysqli = new mysqli("127.0.0.1", "root", "", "tphl_db");

$userId = 29;

$sql = "SELECT 
            lb.id AS booking_id,
            lb.project_id,
            lb.flat_id,
            lb.lead_id,
            lb.created_at AS booking_date,
            lb.sales_by_id,
            l.full_name AS customer_name,
            l.phone AS phone,
            p.name AS project_name,
            p.category_id,
            pc.name AS category_name,
            CASE 
                WHEN IFNULL(p.incentive_percentage, 0) > 0 THEN p.incentive_percentage 
                WHEN IFNULL(pc.incentive_percentage, 0) > 0 THEN pc.incentive_percentage 
                ELSE 0.00 
            END AS incentive_percentage,
            f.flat_name,
            f.sqft,
            IFNULL(f.price_value, 0.00) AS flat_price,
            (SELECT IFNULL(SUM(lp.amount), 0.00) FROM lead_payments lp WHERE lp.booking_id = lb.id AND lp.verification_status = 'verified' AND lp.payment_status IN ('paid', 'partially_paid')) AS total_collected_amount,
            (SELECT IFNULL(SUM(lp.amount), 0.00) FROM lead_payments lp WHERE lp.booking_id = lb.id AND lp.payment_type = 'booking_money' AND lp.verification_status = 'verified' AND lp.payment_status IN ('paid', 'partially_paid')) AS booking_money_amount,
            c.id AS claim_id,
            c.claim_status,
            c.claimed_at,
            c.approved_at,
            c.remarks
        FROM lead_bookings lb
        LEFT JOIN leads l ON lb.lead_id = l.id
        LEFT JOIN projects p ON lb.project_id = p.id
        LEFT JOIN project_categories pc ON p.category_id = pc.id
        LEFT JOIN flats f ON lb.flat_id = f.id
        LEFT JOIN project_incentive_claims c ON (c.booking_id = lb.id AND c.is_external = 0)
        WHERE (lb.sales_by_id = $userId OR l.assigned_id = $userId)
          AND lb.deleted_at IS NULL
        ORDER BY lb.id DESC";

$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    print_r($row);
}
