<?php
$config = require 'app/config/config.php';
require_once 'app/services/ExternalApiService.php';

$db = new mysqli($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
$serv = new ExternalApiService($db);
$token = $serv->getBearerToken();

$url = "https://app.tipsoi.ai/inovace-client/api/v1/employee?order=asc&pageNumber=0&perPage=1&status=1";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Accept: application/json"]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
$emps = $data['data'] ?? $data['employees'] ?? $data['content'] ?? $data;
print_r($emps[0]);
