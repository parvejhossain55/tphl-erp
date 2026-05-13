<?php
$ch = curl_init('http://localhost:8080/TPHL-ERP/ajax-flat-lists?draw=1&start=0&length=10');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Need to set session cookie if checkLogin is active!
// Since checkLogin redirects to admin-login, the response will just be the login page HTML!
// Let's print the headers and body.
curl_setopt($ch, CURLOPT_HEADER, true);
$res = curl_exec($ch);
echo "RESPONSE:\n";
echo $res;
