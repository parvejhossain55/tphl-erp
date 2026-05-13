<?php
$url = 'http://127.0.0.1/TPHL-ERP/ajax-flat-lists';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
echo "HTTP Code: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
echo "Response: " . $res . "\n";
curl_close($ch);
