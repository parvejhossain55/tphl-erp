<?php
$ch = curl_init('http://localhost/TPHL-ERP/api/hrm/tipsoi-email-logs');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);
echo "Response Length: " . strlen($res) . "\n";
echo "First 100 chars: " . substr($res, 0, 100) . "\n";
