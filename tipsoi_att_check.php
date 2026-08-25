<?php
$config = require 'app/config/config.php';
require_once 'app/services/ExternalApiService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$serv = new ExternalApiService($db);
$token = $serv->getBearerToken();

$url = "https://app.tipsoi.ai/inovace-client/api/v1/attendance?officeId=4699&order=asc&pageNumber=0&perPage=1&reportType=department&sortKey=reportPosition&status=1&from=1704067200000&to=1704153600000";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept: application/json"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
print_r($data['attendance'][0]);
