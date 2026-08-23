<?php
session_start();
$_SESSION['user'] = ['id' => 1, 'role' => 'admin'];
session_write_close(); // IMPORTANT to allow concurrent access by curl

$ch = curl_init('http://localhost/TPHL-ERP/api/hrm/tipsoi-email-logs');
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
curl_close($ch);
echo "Response:\n";
echo $res;
