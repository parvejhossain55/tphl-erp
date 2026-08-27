<?php
$c = require 'app/config/config.php';
require_once 'app/core/Model.php';
require_once 'app/services/ExternalApiService.php';

$db = new mysqli($c['db_host'], $c['db_user'], $c['db_pass'], $c['db_name']);
$s = new ExternalApiService($db);
$t = $s->getBearerToken();

$ch = curl_init('https://app.tipsoi.ai/inovace-client/api/v1/leave-category');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $t]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$r = curl_exec($ch);
curl_close($ch);
echo $r;

