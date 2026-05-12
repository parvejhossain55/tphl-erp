<?php
$url = 'http://localhost:8080/TPHL-ERP/con-ajax-workorders/13';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
echo "Response: \n" . $response;
