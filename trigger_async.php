<?php
$ch = curl_init('http://localhost:8080/TPHL-ERP/test_async.php');
curl_setopt($ch, CURLOPT_TIMEOUT_MS, 100);
curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
curl_exec($ch);
curl_close($ch);
echo "Triggered!";
