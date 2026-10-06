<?php
$db = new mysqli('127.0.0.1', 'root', '', 'tphl_db');
$q = "SELECT t.id, t.purpose, ar.accounts_head_by, ar.accounts_head_at FROM fund_requisitions t LEFT JOIN approval_requests ar ON ar.source_table = 'fund_requisitions' AND ar.source_id = t.id LIMIT 5";
$res = $db->query($q);
while($r = $res->fetch_assoc()) { print_r($r); }
