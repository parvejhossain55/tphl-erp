<?php
require_once __DIR__ . '/../app/config/database.php';
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

$res = $db->query("SELECT SUM(amount) as amt, SUM(land_share_amount) as lsa, SUM(service_fee_amount) as sfa FROM lead_payments WHERE payment_type='land_share' AND payment_status IN ('paid', 'partially_paid') AND verification_status='verified'");
print_r($res->fetch_assoc());

$res = $db->query("SELECT SUM(amount) as amt FROM lead_payments WHERE payment_type='service_fee' AND payment_status IN ('paid', 'partially_paid') AND verification_status='verified'");
print_r($res->fetch_assoc());
