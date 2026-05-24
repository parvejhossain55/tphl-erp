<?php
$db = new mysqli('localhost', 'root', '', 'tphl_db');
$db->query("UPDATE cashout_requests SET due_amount = approved_amount - COALESCE((SELECT SUM(amount) FROM cashout_payments WHERE cashout_request_id = cashout_requests.id), 0) WHERE status IN ('approved', 'partial', 'partially_paid')");
echo 'Fixed existing rows';
